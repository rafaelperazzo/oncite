<?php

require_once(dirname(dirname(__FILE__)) . '/common.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/query/formataref.php');

echo "\n<p style='text-align:right;'>[<a href='$config[pageshomeurl]manage/'>Back to administration homepage</a>]</p>";

$data = stripslashes($_REQUEST['data']);
$duplicatecheck = $_REQUEST['duplicatecheck'];
$autoapprove = $_REQUEST['autoapprove'];
$uploadRISfile = $_FILES['uploadRISfile']['tmp_name'];

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
	'L1' => 'url',
	'L2' => 'url',
	'L3' => 'url',
	'M1' => 'medium',
	'M2' => 'medium',
	'M3' => 'medium',
	'U2' => 'DOI',
	'U3' => 'U3',
	'U4' => 'U4',
	'U5' => 'U5',
	'AV' => 'availability',
    'Y2' => 'Y2' 
				);

$fp = false; // File pointer to uploaded data file

// This "if" function about uploading a file into a string was taken from the phpMyAdmin code
if (!empty($uploadRISfile))
{
    $data = "";
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
                $fp = fopen($uploadRISfile, 'r');
				$fpsize = filesize($uploadRISfile);
            }
            else
			{
                $ris_file_new = './tmp/'
                              . basename($uploadRISfile);
                move_uploaded_file($uploadRISfile, $ris_file_new);
                $data = fread(fopen($ris_file_new, 'r'), filesize($ris_file_new));
                $fp = fopen($ris_file_new, 'r');
				$fpsize = filesize($ris_file_new);
               //  unlink($ris_file_new);
            }
        }
        else
		{
            // read from the normal upload dir
			$fp = fopen($uploadRISfile, 'r');
			$fpsize = filesize($uploadRISfile);
        }

        $pleasestripslashes = (get_magic_quotes_runtime() == 1);
        
		if($fp)
		{
		  // Spool the start of the datafile into $data 
		  //   so we can check that it's really a RIS file
		  while(strlen(trim($data))==0)
		  {
		    if(feof($fp)) break; // Stop if end of file

		    $line = fgets($fp,10240); // 10240 = read up to 10Kb of data in a single line
		    if($pleasestripslashes)
		      $line = stripslashes($line);
		    $data .= $line;
		  }
        }

        if($duplicatecheck && ($fpsize>1048576))
		{
		  $duplicatecheck = '';
		  echo "\n<p>[Duplicate checking is deactivated for very large files (>1MB)]</p>\n";
		}
    } // end uploaded file stuff
}



if(strlen($data)>0 && strpos($data, 'TY  - ')===false)
{
  ?>
  <div style="margin: 10px; padding: 10px; border: 1px solid red; background: rgb(255,204,204);">
    <h2>Data file error</h2>
	<p>The data file you loaded does not seem to be a properly-formed RIS file! I cannot extract 
	  the data from anything other than a properly-formed RIS file - if the data is corrupted, or 
	  if you have tried to upload some other type of file, then I can't do anything.</p>
	<p>If you're having 
	  problems with this RIS import feature, you may wish to contact 
	  <?php echo $config['webmasteremail'] ?>.</p>
  </div>
  <?php
  $data = ''; // Unset the data since it's not right
  fclose($fp);
  return;
}

// If data is sent (and we've got a file pointer open to the file), try to incorporate it
if($fp) // && strlen($data)>0)
{

  // First thing to do - scan very quickly through the RIS file and check if any dept codes are missing (from the U1 field).
  // This is NOT needed if the user is a single-dept admin since we know what dept they're assigning things to.
  if(!singleDeptAdmin($_SERVER['REMOTE_USER'])){
    while($str = fgets($fp)){
      if(trim($str)=='U1  -'){
        ?>
	   <h2>Department codes missing</h2>
	   <p>One or more of the records in the RIS file does not include a department code. This is required in order to 
	     upload RIS-formatted data. The entire file has been rejected.</p>
	   <p>Please check the file, add the neccesary department code/s and import the entire file again.</p>
	   <p>Contact the support team if you have any queries about how to import data.</p>
	   <?php
        die('');
      }
    }
    rewind($fp); // And rewind the pointer back to the start
  }




/*
  // CONNECT TO THE DATABASE - This DOESN'T use the function defined in "common.php"
  //   since we're avoiding common.php in order to help improve efficiency of this functionality
  $dbcon = mysql_connect($config['db_addr'], $config['db_user'], $config['db_pass']);
  if(!$dbcon)
  {
    echo "<p><strong>Database error</strong> - unable to connect to the database, I'm afraid.</p>";
    return;
  }
  mysql_select_db($config['db_db'], $dbcon);
*/

  $dbcon = connectpubsdb();

  // Having connected to the database, we need to look up the current user's authorisations
if ($_REQUEST["admin"]==1) {
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
  if(preg_match_all('/[^\s,\.]+/',$remoteuserdeptlist, $ignoreme)==1)
	$singledeptadmin =  preg_replace('/[\s,\.]/','',$remoteuserdeptlist);
  else
    $singledeptadmin =  false;
}

  $thisok = ($duplicatecheck!='y') || $_REQUEST['thisok']; // Duplicate check deactivated means assume it's OK
  $total = intval($_REQUEST['total']);
  $addingdata = true;
  $showfinishedsign = true;
  while($addingdata)
  {
      set_time_limit(30); // Allow 30 seconds for EACH entry - should be well sufficient

      // Read some more data from the file into the $data string, 
	  //   until we get EOF or an "ER  - " line
	  while(!feof($fp))
	  {
		$line = fgets($fp,10240); // 10240 = read up to 10Kb of data in a single line
		if($pleasestripslashes)
		  $line = stripslashes($line);

        // THIS LINE identifies the "ER" delimiter for records, as in the RIS specification
		// The delimiter is NOT ever included in $data
        if(trim($line)=='ER  -')
		  break;

		$data .= $line;
	  }


    if($data=='')
	{
	  $showfinishedsign = true;
	  break;
	}

    $choppeddata = $data; // This may be used later, if a duplicate entry is found...
	$current = processRISentry(trim($data));
        if ($_REQUEST["admin"]==0)
             $current["userlist"] = $_SERVER['REMOTE_USER'];
	$data = '';

	// If !$thisok Check whether the record has potential duplicates in the DB
	if(!($thisok=='y'))
	{
      /*
	   Search for results where:
	   startpage = startpage or null;
	   year = year;
	   title is similar to title; (this is a tricky one)
	  */
      $q = "SELECT * FROM PUBLICATIONS WHERE (startpage='" . mysql_real_escape_string($current['startpage'][0]) 
	     . "' OR startpage='' OR startpage=0) "
	     . "AND year=" . $current['year'][0] . " "
	      . "AND LOCATE('" . mysql_real_escape_string(substr($current['title'][0], 4, 17)) . "', title)" ;
	  
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
	  if($current && insertentry($current))
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
	  
	  // We need to read the whole file into memory so we can output it as hidden field...
	  while(!feof($fp))
	    $data .= fgets($fp, 10240);
	  ?>
	  </ul>
	  <p>Please decide whether the reference should be inserted or not and 
	       then choose from one of these two options:</p>
      <table align="center"><tr><td>
      <form method="post" action="./">
        <input name="total" type="hidden" value="<?php echo $total ?>" />
        <input name="autoapprove" type="hidden" value="<?php echo $autoapprove ?>" />
        <input name="thisok" type="hidden" value="y" />
        <input name="data" type="hidden" value="<?php echo htmlspecialchars($choppeddata . "\r\nER  - \r\n" . $data) ?>" />
	    <input type="submit" value="Insert entry">
	  </form>
	  </td>
	  <td>
      <form method="post" action="./">
        <input name="total" type="hidden" value="<?php echo $total ?>" />
        <input name="autoapprove" type="hidden" value="<?php echo $autoapprove ?>" />
        <input name="data" type="hidden" value="<?php echo htmlspecialchars($data) ?>" />
	    <input type="submit" value="Skip entry">
	  </form>
	  </td></tr></table>
	  <?php
	}
  } // End of while($addingdata=true)
  if($showfinishedsign)
  {
	  ?>
	  <p>Finished! Inserted <?php echo $total  ?> references into the database.</p>
<?php if ($_REQUEST["admin"]==1): ?>
	  <p>Please note: The data does not contain connections between a given 
	    publication and a given <?php echo $config['institutionname'] ?> user account - 
		so no associations have been made between user ID and publication.</p>
	  <p>If you like, you can <a href="<?php echo $config['pageshomeurl'] ?>manage/?action=userlesspubs">interactively manage the publications in your department(s) with
	    no associated authors</a>.</p>
<?php endif; ?>
	  <?php
  }
}
else
{  // Provide a form with which to insert data
?>
<h1>Import RIS data into publications database</h1>
<form action="./" method="post" enctype="multipart/form-data"> <!-- onSubmit="alert('If the file is large it could take\nup to 5 minutes to be processed.\nPlease be patient!')"> -->
<p>To load data from a RIS file (taken from Reference Manager,
  for example, or from the Publications Exercise) into the online publications
  database, choose the file and then press submit.
<?php if ($_REQUEST["admin"]==0): ?>
Your department code <B>must</B> be entered against the publications in the RIS file in the field labelled USERDEF1. If one publication is missing a department code the file will be rejected. For a list of department codes
<a href="http://www.ucl.ac.uk/management-systems/msapps/msts/oncite/deptlistbyname.php" target="_blank">click here</a>.
<p>If a publication is to be shared between two or more departments the codes
should be separated by a comma. For example: MN,FD
<?php endif; ?>
<p align="center"><input name="uploadRISfile" type="file" size="30" /></p>
<?php if ($_REQUEST["admin"]==1): ?>
<p align="center">	<label style="padding: 1px 40px 1px 1px;"><input name="autoapprove" type="radio" value="1" checked="checked" />Approve immediately</label>
	<label style="padding: 1px 1px 1px 40px;"><input name="autoapprove" type="radio" value="0" />Don't approve immediately</label></p>
        <input name="admin" type="hidden" value=1 />
<?php else: ?>
        <input name="autoapprove" type="hidden" value=0 />
        <input name="admin" type="hidden" value=0 />
<?php endif; ?>
<p>The maximum file size that will be accepted is about 
<?php 

echo($numberofmeg = return_megabytes(ini_get('upload_max_filesize')));

?> megabytes, which should allow for approx 
<?php echo ($numberofmeg * 1000) ?> records.</p>

<p>WARNING! Do NOT try to load a Reference Manager <strong>.rmd</strong> file! The data must first have been 
  <em>exported</em> from Reference Manager in RIS format!</p>
<!-- <p><label><input type="checkbox" name="duplicatecheck" value='y' checked='checked' />Check for duplicates whilst importing</label></p> -->
<p align="center"><input type="submit" value="Submit" /></p>
</form>


<p><br>
  <?php
}

function return_megabytes($val) {
   $val = trim($val);
   $last = strtolower($val{strlen($val)-1});
   switch($last) {
       // The 'G' modifier is available since PHP 5.1.0
       case 'g':
           $val *= 1024;
       case 'm':
           $val *= 1024;
       case 'k':
           $val *= 1024;
   }

   return round($val/1048576,1);
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
  global $ristranslation, $config, $singledeptadmin, $autoapprove;
  $e = explode("\n",stripslashes(trim($e)));
  if(sizeof($e)<2) return false;
  
  $ret = array();
  if($autoapprove)
  {
	$ret['approvedby'][] = mysql_real_escape_string($_SERVER['REMOTE_USER']);
	$ret['approvaldate'][] = "NOW()";
     if($singledeptadmin)
       $ret['deptlist'] = array(','.$singledeptadmin.','); // FORCE the department to be the one specified by user's affiliation
  }else{
    if($singledeptadmin)
      $ret['pendingdepts'] = array(','.$singledeptadmin.','); // FORCE the department to be the one specified by user's affiliation
  }

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
	  elseif($datatype=='deptlist'){
	    $data = ",$data,"; // This wraps commas around the data to make sure search works OK
         if(!$autoapprove)
	      $datatype='pendingdepts'; // If we aren't auto-approving we need to shunt the data over to pending
	  }elseif($datatype=='U5' && ($ret['reftype'][0]=='CONF' || $ret['reftype'][0]=='CASE') )
	  {
        // This handles a curious "feature" of the conference reftypes
		//   - i.e. that the paper title goes in "U5" rather than in secondarytitle as expected
	    $ret['secondarytitle'][] = preg_replace('/[\x00-\x1F]/', '', $data);
        //echo "<p>Handled the stupidity of conference papers: U5=$data</p>";
	  }

//	  if($datatype=='url')
        $data = preg_replace('/[\x00-\x1F]/', '', $data); // Assumes ASCII and removes all bizarre character codes
	  $ret[$datatype][] = $data;
	}
  }
  return $ret;
}


?>
