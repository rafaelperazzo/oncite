<?php

/*

Arguments to GetRecord:
identifier - should be similar to: oai:ucl.ac.uk:MyOPIA_53256
metadataPrefix - erm... are we only allowing dublin core in MyOPIA?

Error and Exception Conditions:
badArgument - The request includes illegal arguments or is missing required arguments.
cannotDisseminateFormat - The value of the metadataPrefix argument is not supported by the item identified by the value of the identifier argument.
idDoesNotExist - The value of the identifier argument is unknown or illegal in this repository.

*/

$identifier = $_REQUEST['identifier'];
$metadataPrefix = $_REQUEST['metadataPrefix'];

if(strlen($identifier)==0 || strlen($metadataPrefix)==0)
{
  oaiErrorXml("badArgument", "Either one or both of the two required arguments,
            identifier and metadataPrefix, were missing.");
  exit();
}
elseif($metadataPrefix != 'oai_dc')
{
  oaiErrorXml("cannotDisseminateFormat", "MyOPIA only supports the standard 
            &quot;oai_dc&quot; (Dublin Core) metadata format.");
  exit();
}
// Also check that the identifier is correctly-patterned for MyOPIA's scheme (or give idDoesNotExist error)
// Should be similar to oai:ucl.ac.uk:MyOPIA_53256
if(!preg_match('/oai:(.+?):MyOPIA_(\d+)/', $identifier, $matches))
{
  oaiErrorXml("idDoesNotExist", "Error in identifier string - it should be in the format
                   oai:ucl.ac.uk:MyOPIA_1234 with &quot;1234&quot; being the record ID number.");
  exit();
}

// Now convert a long-format identifier into a single numerical ID
$id = intval($matches[2]);

// Now look the thing up in the database
$q = "SELECT *, UNIX_TIMESTAMP(timestamp) AS utime FROM PUBLICATIONS WHERE pubid=$id LIMIT 1";
$dbcon = mysql_connect($config['db_addr'], $config['db_user'], $config['db_pass']);
mysql_select_db($config['db_db'], $dbcon);

$res = mysql_query($q, $dbcon);

if(mysql_num_rows($res)==0)
{
  // If it doesn't exist then give an idDoesNotExist error
    oaiErrorXml("idDoesNotExist", "Record with ID $identifier was not found");
  exit();
}

$p = mysql_fetch_assoc($res);



?>
<?xml version="1.0" encoding="utf-8"?> 
<?xml-stylesheet type='text/xsl' href='<?php echo $config['scriptshomeurl'] ?>oai/oai2.xsl' ?>
<OAI-PMH xmlns="http://www.openarchives.org/OAI/2.0/" 
         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/
         http://www.openarchives.org/OAI/2.0/OAI-PMH.xsd">
  <responseDate><?php echo $utcrightnow ?></responseDate>
  <request verb="GetRecord" identifier="<?php echo $identifier ?>"
           metadataPrefix="oai_dc">http://<?php echo htmlspecialchars($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?></request>
  <GetRecord>
   <record> 
    <header>
      <identifier>oai:ucl.ac.uk:MyOPIA_<?php echo htmlspecialchars($p['pubid']);  ?></identifier> 
      <datestamp><?php echo htmlspecialchars(timestampToUtc($p['utime']));  ?></datestamp>
<?php 

$depts = preg_split('/[, \.]+/', $p['deptlist']);
foreach($depts as $d)
  if(strlen($d)>0)
    echo utf8_encode("\n   <setSpec>" . str_replace('/', '.', $d) . "</setSpec>");
?>
    </header>
    <metadata>
<?php echo dublinCoreXml($row); ?>
    </metadata>
  </record>
 </GetRecord>
</OAI-PMH>