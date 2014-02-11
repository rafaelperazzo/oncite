<?php

// require_once(dirname(dirname(__FILE__)) . '/common.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/query/formataref.php');

$data = stripslashes($_REQUEST['data']);
$duplicatecheck = $_REQUEST['duplicatecheck'];

$ristranslation = array(
	'TY' => 'reftype',
//	'ID' => 'RefmanID',
	'A1' => 'authorlist',
	'AU' => 'authorlist',
	'T1' => 'title',
	'TI' => 'title',
	'CT' => 'title',
	'BT' => 'title',
	'T2' => 'secondarytitle',
	'T3' => 'seriestitle',
	'A2' => 'secondaryauthorlist',
	'ED' => 'secondaryauthorlist',
	'A3' => 'seriesauthorlist',
	'Y1' => 'year',
	'PY' => 'year',
	'N1' => 'notes',
	'AB' => 'notes',
	'N2' => 'abstract',
	'KW' => 'keywords',
	'JF' => 'journal',
	'JO' => 'journalabbrev',
	'JA' => 'journalabbrev',
	'J1' => 'journalabbrev',
	'J2' => 'journalabbrev',
	'VL' => 'volume',
	'IS' => 'issue',
	'CP' => 'issue',
	'SP' => 'startpage',
	'EP' => 'endpage',
	'CY' => 'city',
	'PB' => 'publisher',
	'SN' => 'issnisbn',
	'AD' => 'address',
  	'U1' => 'deptlist',
	'UR' => 'url',
	'M1' => 'medium',
	'M2' => 'medium',
	'M3' => 'medium',
	'U2' => 'U2',
	'U3' => 'U3',
	'U4' => 'U4',
	'U5' => 'U5',
	'AV' => 'availability',
    'Y2' => 'Y2' 
				);

// This "if" function about uploading a file into a string was taken from the phpMyAdmin code
if (!empty($uploadRISfile))
{
    if (file_exists($uploadRISfile) && is_uploaded_file($uploadRISfile))
	{
        $open_basedir = @ini_get('open_basedir');
        if (empty($open_basedir))
            $open_basedir = @get_cfg_var('open_basedir');
        // If we are on a server with open_basedir, we must move the file
        // before opening it. The doc explains how to create the "./tmp" directory
        if (!empty($open_basedir))
		{
            // check if '.' is in open_basedir
            $split_char = ':';
            $pos        = ereg('(^|' . $split_char . ')\\.(' . $split_char . '|$)', $open_basedir);
            // from the PHP annotated manual
            if (!$pos)
			{
                // if no '.' in openbasedir, do not move the file (open_basedir
                // may only be a prefix), force the error and let PHP report
                // it
                error_reporting(E_ALL);
                $myrisdata = fread(fopen($uploadRISfile, 'r'), filesize($uploadRISfile));
            }
            else
			{
                $ris_file_new = './tmp/'
                              . basename($uploadRISfile);
                move_uploaded_file($uploadRISfile, $ris_file_new);
                $data = fread(fopen($ris_file_new, 'r'), filesize($ris_file_new));
                unlink($ris_file_new);
            }
        }
        else
		{
            // read from the normal upload dir
            $data = fread(fopen($uploadRISfile, 'r'), filesize($uploadRISfile));
        }

        if (get_magic_quotes_runtime() == 1)
            $data = stripslashes($data);
    } // end uploaded file stuff
}

$data = trim($data);

if(strlen($data)>0 && strpos($data, 'TY  - ')!==0)
{
  ?>
  <div style="margin: 10px; padding: 10px; border: 1px solid red; background: rgb(255,204,204);">
    <h2>Data file error</h2>
	<p>The data file you loaded does not seem to be a properly-formed RIS file! I cannot extract 
	  the data from anything other than a properly-formed RIS file - if the data is corrupted, or 
	  if you have tried to upload some other type of file, then I can't do anything.</p>
	<p>If you're having 
	  problems with this RIS import feature, you may wish to contact your FISO, or the DeMIST team.</p>
  </div>
  <?php
  $data = ''; // Unset the data since it's not right
}

// If data is sent, try to incorporate it
if(strlen($data)>0)
{
  // CONNECT TO THE DATABASE - This DOESN'T use the function defined in "common.php"
  //   since we're avoiding common.php in order to help improve efficiency of this functionality
  $dbcon = mysql_connect($config['db_addr'], $config['db_user'], $config['db_pass']);
  if(!$dbcon)
  {
    echo "<p><strong>Database error</strong> - unable to connect to the database, I'm afraid.</p>";
    return;
  }
  mysql_select_db($config['db_db'], $dbcon);

  // Having connected to the database, we need to look up the current user's authorisations
  $remoteuserdeptlist = 'NONE';
  $q = "SELECT deptslist FROM PUBLICATIONSADMINS WHERE userid='" . mysql_real_escape_string($_SERVER['REMOTE_USER']) . "' LIMIT 1";
  $res = mysql_query($q,$dbcon);
  if((!$res) || mysql_num_rows($res)==0)
  {
    echo "<p><strong>Database error</strong> - unable to find your user details ($_SERVER[REMOTE_USER]) in the "
	. " database list of administrators.</p>";
    return;
  }
  $temp = mysql_fetch_assoc($res);
  $remoteuserdeptlist = $temp['deptslist'];

  // This variable is false if the user isn't a single-dept admin; otherwise, it's the dept code.
  $singledeptadmin = (strlen($auth)>0 && strpos($auth,',')===false) ? trim($auth) : false;

  $thisok = ($duplicatecheck!='y') || $_REQUEST['thisok']; // Duplicate check deactivated means assume it's OK
  $total = intval($_REQUEST['total']);
  $addingdata = true;
  $showfinishedsign = true;
  while($addingdata)
  {
    if($data=='')
	{
	  "<p>FINISHED! Inserted $total references into the database.</p>";
	  break;
	}
    // Chop the first entry off the head of the data
    $choppeddata = explode('ER  - ',$data,2);
    $current = processRISentry(trim($choppeddata[0]));
	$data = trim($choppeddata[1]);

    if($config['debug']){
	  echo "<!--\n";
      print_r($choppeddata);
	  echo "\n-->";
    }

	// If !$thisok Check for a duplicate of the first head of data
	if(!($thisok=='y'))
	{
      /*
	   Search for results where:
	   startpage = startpage or null;
	   year = year;
	   SOUNDEX(title)=SOUNDEX(title);
	  */
      $q = "SELECT * FROM PUBLICATIONS WHERE (startpage='" . mysql_real_escape_string($current['startpage'][0]) 
	     . "' OR startpage='' OR startpage=0) "
	     . "AND year=" . $current['year'][0] . " "
	      . "AND LOCATE(SUBSTRING('" . mysql_real_escape_string($current['title'][0]) . "',4,7), title)" ;
	  $res = mysql_query($q,$dbcon);
	  if(!$res)
	    die(mysql_error());
      if(mysql_num_rows($res)>0)
	  {
	    while($row = mysql_fetch_assoc($res))
        {
		  $results[] = $row;
		}
		$thisok='n';
	  }
	  else
	  {
	    $thisok='y'; // Found no duplicates
	  }
	} // End of should-we-be-checking-it-for-duplicates

	// If $thisok or if there is no duplicate, then insert it
	if($thisok=='y')
    {
	  if($duplicatecheck=='y') // If we're duplicate checking then we need to stop assuming all is OK
	    $thisok='n';
	  if(insertentry($current))
	    $total++;
	// 	echo "<p>I would have inserted " . formataref(collapseArrayParts($current), '', false, 'n') . "</p>";
	}
	else
	{
      // If there IS a duplicate then $addingdata is false - present information and request guidance
      $addingdata=false;
      $showfinishedsign = false;
	  ?>
	  <p><strong>Possible duplicates have been located.</strong> The reference you are inserting is:</p>
      <ul><li><?php echo formataref2(collapseArrayParts($current), '', '');  ?></li></ul>
	  <p>The following reference(s) are in the database already and have 
	     been identified as possibly being the 
	     same entry:</p>
	  <ul>
	  <?php
	  foreach($results as $v)
	    echo "\n  <li>" . formataref2($v, '', '') . "</li>";
	  ?>
	  </ul>
	  <p>Please decide whether the reference should be inserted or not and 
	       then choose from one of these two options:</p>
      <table align="center"><tr><td>
      <form method="post" action="./">
        <input name="total" type="hidden" value="<?php echo $total ?>" />
        <input name="thisok" type="hidden" value="y" />
        <input name="data" type="hidden" value="<?php echo htmlspecialchars($choppeddata[0] . "\r\nER  - \r\n" . $data) ?>" />
	    <input type="submit" value="Insert entry">
	  </form>
	  </td>
	  <td>
      <form method="post" action="./">
        <input name="total" type="hidden" value="<?php echo $total ?>" />
        <input name="data" type="hidden" value="<?php echo htmlspecialchars($data) ?>" />
	    <input type="submit" value="Skip entry">
	  </form>
	  </td></tr></table>
	  <?php
	}
  } // End of while($addingdata=true)
  if($showfinishedsign)
    echo "<p>Finished adding data. Inserted $total references into the database.</p>";
}
else
{  // Provide a form with which to insert data
?>
<h1>Import RIS data into publications database</h1>
<form action="./" method="post" enctype="multipart/form-data">
<p>To load data from a RIS file (taken from Reference Manager,
  for example, or from the Publications Exercise) into the online publications
  database, choose the file and then press submit:</p>
<p align="center"><input name="uploadRISfile" type="file" size="30" /></p>
<p>WARNING! Do NOT try to load a Reference Manager <strong>.rmd</strong> file! The data must first have been 
  <em>exported</em> from Reference Manager in RIS format!</p>
<p><label><input type="checkbox" name="duplicatecheck" value='y' checked='checked' />Check for duplicates whilst importing</label></p>
<p align="center"><input type="submit" value="Submit" /></p>
</form>


<p><br>
  <?php
}

function collapseArrayParts($v)
{
  foreach($v as $k => $vv)
    if(is_array($vv))
	  $v[$k] = implode(', ',$vv);
  return $v;
}

function processRISentry($e)
{
  global $ristranslation, $config, $singledeptadmin;
  $e = explode("\n",stripslashes(trim($e)));
  if(sizeof($e)<2) return false;
  
  $ret = array();
// Removed this clause because a user HAS to be an admin, in order to use this page
//  if(authorisedForPubs($_SERVER['REMOTE_USER']))
//  {
    $ret['approvedby'][] = mysql_real_escape_string($_SERVER['REMOTE_USER']);
    $ret['approvaldate'][] = "NOW()";
	if($singledeptadmin)
	  $ret['deptlist'][] = $singledeptadmin;
//  }
  foreach($e as $v)
  {
    $vv = trim($v);
    $datatype = substr($vv,0,2);
    if(isset($ristranslation[$datatype]))
	  $datatype = $ristranslation[$datatype];
	$data = substr($vv,6);
    if(strlen($data))
	{
	  if($datatype=='year')
	  {
	    // $data = intval(substr($data,0,strpos($data,'/')));
		$data = explode('/',$data,4);
	    $ret['year'][] = intval($data[0]);
        if(strlen(trim($data[1]))>0)
		  $ret['yearmonth'][] = intval($data[1]);
        if(strlen(trim($data[2]))>0)
		  $ret['yearday'][] = intval($data[2]);
        if(strlen(trim($data[3]))>0)
		  $ret['yearother'][] = trim($data[3]);
		continue;
	  }
/*  REMOVED - I used to truncate departmental codes to 2 characters, but that's undesirable!
	  if($datatype=='deptlist')
	    $data = substr($data,0,2);
*/
//	  if($datatype=='url')
        $data = preg_replace('/[\x00-\x1F]/', '', $data); // Assumes ASCII and removes all bizarre character codes
	  $ret[$datatype][] = $data;
	}
  }
  return $ret;
}

function insertentry($data)
{
  global $config, $dbcon;
  $q = "INSERT INTO PUBLICATIONS SET ";
  $inserts = array("originator='" . mysql_real_escape_string($_SERVER['REMOTE_USER']) . "'");
  foreach($data as $k=>$v)
  {
    if($k=='reftype' && (substr(trim($v[0]),0,4)=='ICOM' || substr(trim($v[0]),0,4)=='ELEC'))
	  $inserts[] = "reftype='GEN'";
    elseif($k=='reftype' && substr(trim($v[0]),0,4)=='INPR')
	{
	  $inserts[] = "reftype='GEN'";
	  $inserts[] = "year='9999'";
	  unset($data['year']);
    }
	elseif(/* $k!='U1' && */ $k!='ID' && $k!='RP' && sizeof($data[$k])>0)
	  $inserts[] = "$k='" . mysql_real_escape_string(implode(", ",$v)) . "'";
// REMOVED departmental code truncation
//	else if($k=='U1') // Get the dept information working...
//	  $inserts[] = "$k='" . substr(mysql_real_escape_string(implode(", ",$v)),0,2) . "'";
  }
  $q .= implode(', ',$inserts);
  if(false)
  {
    echo "<!--\n\n";
	echo $q;
	echo "\n\n-->";
  }
  $success = mysql_query($q, $dbcon);
  if(!$success)
  {
    echo "<h3>Error while inserting entry.</h3><p>Query: $q</p><p>Error detail: " . mysql_error() ."</p>";
    return false;
  }
  // REMOVED... recordtransaction('insertentry',mysql_insert_id());
  return true;
}


?>