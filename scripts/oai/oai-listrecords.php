<?php
/*

THIS SCRIPT DOES BOTH ListRecords AND ListIdentifiers - the latter if $headersonly==true


Arguments:
from - an optional argument with a UTCdatetime value, which specifies a lower bound for datestamp-based selective harvesting.
until - an optional argument with a UTCdatetime value, which specifies a upper bound for datestamp-based selective harvesting.
metadataPrefix - IGNORED BY MyOPIA since only DC is supported. a required argument, which specifies that headers should be returned only if the metadata format matching the supplied metadataPrefix is available or, depending on the repository's support for deletions, has been deleted. The metadata formats supported by a repository and for a particular item can be retrieved using the ListMetadataFormats request.
set - an optional argument with a setSpec value , which specifies set criteria for selective harvesting.
resumptionToken - an exclusive argument with a value that is the flow control token returned by a previous ListIdentifiers request that issued an incomplete list.

Error and Exception Conditions:
badArgument - The request includes illegal arguments or is missing required arguments.
badResumptionToken - The value of the resumptionToken argument is invalid or expired.
cannotDisseminateFormat - The value of the metadataPrefix argument is not supported by the repository.
noRecordsMatch- The combination of the values of the from, until, and set arguments results in an empty list.
noSetHierarchy - The repository does not support sets.



MyOPIA system for sets:
- departments are given by "dept:" plus their department code
- users are given by "user:" plus their userID



THE BIG QUESTION is how many records we should be willing to output in a single request.
On the order of 10,000 perhaps, like in the public search?

Or should it be smaller, in case the database gets polled a lot and ends up suffering?

I tried it with a limit of 2000 and something seemed to time out - most likely the validator.

So it's now down to 500.
*/
$maxrecords = 500;

$from  = $_REQUEST['from'];
$until = $_REQUEST['until'];

$SET_DEPTID = 1;
$SET_USERID = 2;
$SET_UNKNOWNFORMAT = 3;
$SET_NOSET = 4;

$set = $_REQUEST['set'];
if(trim($set)=='')
{
  $settype = $SET_NOSET;
}
elseif(substr($set,0,5)=='dept:')
{
  $settype = $SET_DEPTID;
  $set = str_replace('.', '/', substr($set, 5)); // Since the forward-slash is not an allowed character in OAI-PMH, but it is allowed in MyOPIA, we expose it externally as a dot
}
elseif(substr($set,0,5)=='user:')
{
  $settype = $SET_USERID;
  $set = substr($set, 5);
}
else
  $settype = $SET_UNKNOWNFORMAT;


$resumptionToken = $_REQUEST['resumptionToken'];
$metadataPrefix  = $_REQUEST['metadataPrefix'];

// First check that the required arguments are present
if(!(strlen($resumptionToken)>0 || strlen($metadataPrefix)>0))
{
    oaiErrorXml("badArgument", "Either resumptionToken or metadataPrefix must be supplied.");
    exit();
}
if(strlen($resumptionToken)>0 && 
     (strlen($metadataPrefix)>0 || strlen($from)>0 || strlen($until)>0 || strlen($set)>0))
{
    oaiErrorXml("badArgument", "If you supply resumptionToken, you must not supply metadataPrefix, from, until, or set.");
    exit();
}
// Bloomin' OAI explorer won't let mw get away with ignoring multiple mdp specification
if(preg_match('/\bmetadataPrefix=.*&metadataPrefix=/', $_SERVER['QUERY_STRING']))
{
    oaiErrorXml("badArgument", "You cannot supply more than one metadataPrefix.");
    exit();
}
// If metadataPrefix!='oai_dc' then we give an error
if(strlen($resumptionToken)==0 && $metadataPrefix!='oai_dc')
{
    oaiErrorXml("cannotDisseminateFormat", "Only oai_dc (Dublin Core) metadata is output by this repository.");
    exit();
}
if(strlen($from)>0 && strlen($from)!=10)
{
    oaiErrorXml("badArgument", "The 'from' argument must be in format YYYY-MM-DD.");
    exit();
}
if(strlen($until)>0 && strlen($until)!=10)
{
    oaiErrorXml("badArgument", "The 'until' argument must be in format YYYY-MM-DD.");
    exit();
}
if($settype==$SET_UNKNOWNFORMAT)
{
    oaiErrorXml("badArgument", "The 'set' argument does not correspond to a recognised set for this system: $set");
    exit();
}

$resumefrom = 0;

// At this point we know we need to establish a connection to the database
$dbcon = mysql_connect($config['db_addr'], $config['db_user'], $config['db_pass']);
mysql_select_db($config['db_db'], $dbcon);
if(strlen($resumptionToken)>0)
{
  // Try to resume. Look up the token and if it's there perform the query.
  $q = "SELECT * FROM OAITOKENS WHERE tokenid='" . mysql_real_escape_string($resumptionToken) . "' LIMIT 1";
  $res = mysql_query($q, $dbcon);
  if(@mysql_num_rows($res)!=1)
  {
    oaiErrorXml("badResumptionToken", "Could not find the resumption token. It is either invalid or has expired.");
    exit();
  }
  //         else echo "<!-- Database error: " . mysql_error() . " -->";
  // Now use the token to determine what our query should be like
  $token = mysql_fetch_assoc($res);

  $set = $token['theset'];
  switch($token['settype'])
  {
    case 'user':
	  $settype = $SET_USERID;
	  break;
    case 'dept':
	default:
	  $settype = $SET_DEPTID;
	  break;
  }
  $resumefrom = intval($token['resumefrom']);
  $from = $token['thefrom'];
  $until = $token['theuntil'];
}



// "from", "until", and "set" have been provided either from the token or from the query,
// and any of them may be blank. Use them to perform a simple query on the database

$q = "SELECT * FROM PUBLICATIONS WHERE (1 AND (year!=9999) AND (LENGTH(approvedby)>3) AND (deptlist RLIKE '[[:alnum:]]') ";
if(strlen($set)>0)
	switch($settype){
	  case $SET_USERID:
		$q .= " AND (userlist LIKE '%$set%') ";
		break;
	  case $SET_DEPTID:
	  default:
		$q .= " AND (deptlist LIKE '%,$set,%') ";
		break;
	}

$q .= (strlen($from)>0 ? " AND (timestamp >= " . utcToMysqlStamp($from) . ')' : '')
           . (strlen($until)>0 ? " AND (timestamp <= " . utcToMysqlStamp($until) . ')' : '')
           .  ") ORDER BY timestamp DESC, year DESC, title, authorlist LIMIT $resumefrom, 18446744073709551615";

// echo "\n\n<!-- From: $from ; Until: $until; Query: $q -->\n\n";

$res = mysql_query($q, $dbcon);
$totalnumreturning = mysql_num_rows($res);

// echo "<!-- Total num records found = $totalnumreturning, while max num records = $maxrecords -->";

if($totalnumreturning == 0)
{
    oaiErrorXml("noRecordsMatch", "No records found to match criteria.");
    exit();
}
elseif($totalnumreturning>$maxrecords)
{
  // If there are more to come then we need to generate a resumption token 

  // First some housekeeping - delete tokens older than 7 days
  mysql_query("DELETE FROM OAITOKENS WHERE created<()", $dbcon);

  // Then insert our new token
  $resumq = "INSERT INTO OAITOKENS SET theset='" . mysql_real_escape_string($set) 
                      . "', resumefrom='" .  ($resumefrom + $maxrecords)
                      . "', thefrom='" . mysql_real_escape_string($from) 
                      . "', theuntil='" . mysql_real_escape_string($until) 
                      . "', settype='" . ($settype==$SET_USERID ? 'user' : 'dept') 
                      . "'";
  $resumres = mysql_query($resumq, $dbcon);
  // And finally find out what it's ID number is
  $newResumptionToken = intval(mysql_insert_id());

 //  echo "<!-- While creating resumption token, mysql_error is " 
 //            . mysql_error() . "\n and newResumptionToken is $newResumptionToken -->";;
}
else
  $newResumptionToken = false;



?>
<?xml version="1.0" encoding="utf-8"?>
<?xml-stylesheet type='text/xsl' href='<?php echo $config['scriptshomeurl'] ?>oai/oai2.xsl' ?>
<OAI-PMH xmlns="http://www.openarchives.org/OAI/2.0/" 
         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/
         http://www.openarchives.org/OAI/2.0/OAI-PMH.xsd">
  <responseDate><?php echo $utcrightnow ?></responseDate>
   <request verb="<?php echo ($headersonly ? 'ListIdentifiers' : 'ListRecords') ?>"
<?php 

if(strlen($resumptionToken)>0)
  echo utf8_encode(" resumptionToken='$resumptionToken' ");
else
{
  if(strlen($metadataPrefix)>0)
    echo " metadataPrefix='$metadataPrefix' ";
  if(strlen($from)>0)
    echo " from='$from' ";
  if(strlen($until)>0)
    echo " until='$until' ";
  if(strlen($set)>0)
    echo " set='$set' ";
}

?>>http://<?php echo htmlspecialchars($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?></request>
<?php
if($headersonly)
{
  ?>

  <ListIdentifiers>
<?php
  $count = 0;
  while(($count++ < $maxrecords) && $row = mysql_fetch_assoc($res))
  {
    ?>
   <header>  
    <identifier>oai:ucl.ac.uk:MyOPIA_<?php echo $row['pubid'] ?></identifier>
    <datestamp><?php echo mySqlStampToSimple($row['timestamp']) ?></datestamp>
<?php 

outputSetInfo($row);
?>
   </header>
   <?php
  }


if($newResumptionToken)
{
 ?>
   <resumptionToken expirationDate="2002-06-01T23:20:00Z" 
      completeListSize="<?php echo ($resumefrom + $totalnumreturning) ?>" 
      cursor="0"><?php echo utf8_encode($newResumptionToken) ?></resumptionToken>
<?php
}
?>
 </ListIdentifiers>
<?php

}
else // Not just headers only - i.e. this is a ListRecords request
{
  ?>

  <ListRecords>
<?php
  $count = 0;
  while(($count++ < $maxrecords) && $row = mysql_fetch_assoc($res))
  {
    ?>
 <record>
   <header>  
    <identifier>oai:ucl.ac.uk:MyOPIA_<?php echo $row['pubid'] ?></identifier>
    <datestamp><?php echo mySqlStampToSimple($row['timestamp']) ?></datestamp>
<?php 

outputSetInfo($row);

?>
   </header>
   <metadata>
   <?php echo dublinCoreXml($row); ?>
   </metadata>
 </record>
   <?php
  }


if($newResumptionToken)
{
 ?>
   <resumptionToken expirationDate="<?php echo timestampToUtc(time() + 604800) ?>" 
      completeListSize="<?php echo ($resumefrom + $totalnumreturning) ?>" 
      cursor="<?php echo ($resumefrom + $maxrecords) ?>"><?php echo utf8_encode($newResumptionToken) ?></resumptionToken>
<?php
}
?>
 </ListRecords>
  <?php
}


/* DELETEME
function outputSetInfo($row)
{
	$depts = preg_split('/[, \.]+/', $row['deptlist']);
	foreach($depts as $d)
	  if(strlen($d)>0)
		echo utf8_encode("\n   <setSpec>dept:" . str_replace('/', '.', $d) . "</setSpec>");
	
	$userids = preg_split('/[, \.]+/', $row['userlist']);
	foreach($userids as $u)
	  if(strlen($u)>0)
		echo utf8_encode("\n   <setSpec>user:$u</setSpec>");
}
*/



?>
</OAI-PMH>
