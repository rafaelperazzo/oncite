<?php

// include_once('/nfs/rcs/www/webdocs/lifesciences-faculty/publications/manage/common.php');
require_once(dirname(dirname(__FILE__)) . '/common.php');
require_once($config['homedir'] . 'query/formataref.php');

echo "\n<p style='text-align:right;'>[<a href='$config[pageshomeurl]manage/'>Back to administration homepage</a>]</p>";

$data = stripslashes($_REQUEST['data']);
$autoapprove = $_REQUEST['autoapprove'];
$chosendept = $_REQUEST['chosendept'];
$ensuredept = intval($_REQUEST['ensuredept']);
$uploadRISfile = $_FILES['uploadRISfile']['tmp_name'];

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

if(strlen($data)>0 && !preg_match('/^\d+: .+?\bPMID: \d/s', $data))
{
  ?>
  <div style="margin: 10px; padding: 10px; border: 1px solid red; background: rgb(255,204,204);">
    <h2>Data file error</h2>
	<p>The data file you loaded does not seem to be a PubMed &quot;Summary&quot; file! I cannot
	  extract the data from anything other than properly-formed PubMed &quot;Summary&quot;
	  data - if the
	  data is corrupted, or if you have tried to upload some other type of file,
	  then I can't do anything.</p>
	<p>If you're having 
	  problems with this PubMed import feature, you may wish to contact your
	  FISO, or the DeMIST team.</p>
	  <?php echo "\n\n" . $data; ?>
  </div>
  <?php
  $data = ''; // Unset the data since it's not right
}

if($ensuredept>0)
{
  if($mydept = singleDeptAdmin($_SERVER['REMOTE_USER']))
	$addthedept = $mydept;
  else
	$addthedept = $chosendept;
  $q = "UPDATE PUBLICATIONS SET deptlist = CONCAT(deptlist, '$addthedept,') WHERE pubid=$ensuredept LIMIT 1";
  //echo "<pre>$q</pre>";
  $res = mysql_query($q, connectpubsdb());
  //echo "<p>Result = $res</p>";
}

// If data is sent, try to incorporate it
if(strlen($data)>0)
{


  $pmregex = '/^(\d+): (.+?)\.(?:\r\n|\r|\n){1,2}'
		 . ' (.+)(?:\r\n|\r|\n)'
		 . '(.+?)\. (\d\d\d\d)(.*?);(.+?):(\w+)-?(\w*?)\.(.*)(?:\r\n|\r|\n)'
		 . 'PMID: (\d+) \[.+?\]'
		 . '/s';

  // echo "<pre>Regex:<br />$pmregex</pre>";

  $thisok = $_REQUEST['thisok'];
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
//    $choppeddata = explode('ER  - ',$data,2);
//    $choppeddata = explode(']\n',$data,2);
	$choppeddata = preg_split('/(?<=])([\r\n]{1,2}){2,2}(?=\d+: )/',$data,2);
    $current = processPubMedEntry(trim($choppeddata[0]));
	$data = trim($choppeddata[1]);
	//$mergenotinsert = false;

    if($config['debug']){
	  echo "\n\n<!--\nCHOPPED DATA[0]:\n";
      print_r($choppeddata[0]);
	  echo "\n-->\n\n";
    }
	
	if($current==false)
	{
	  echo "<p>Warning! One PubMed entry could not be processed. Its data is listed below. You're "
	    . " advised to enter this entry manually.</p>";
      echo "\n\n<pre>$choppeddata[0]</pre>\n\n";
	  echo "<p>The system will continue to process all the other entries in the datafile....</p>";
	  continue;
	}

	// If !$thisok Check for a duplicate of the first head of data
	if(!($thisok=='y'))
	{
	  /*
	    The first check simply looks for a record where the PMID is already present in the database,
		and merges the dept association if so.
	  */
	    $q = "SELECT pubid, url, deptlist FROM PUBLICATIONS WHERE url='" . mysql_real_escape_string($current['url'][0]) . "' LIMIT 1";
	    $res = mysql_query($q,connectpubsdb());
		if($res && (mysql_num_rows($res)!=0)){
		  $existing = mysql_fetch_assoc($res);
		  if(strpos($existing, $current['deptlist'][0])===false)
		    mysql_query("UPDATE PUBLICATIONS SET deptlist='" 
			   . mysql_real_escape_string($existing['deptlist'] . substr($current['deptlist'][0], 1))
			   . "' WHERE pubid=$existing[pubid] LIMIT 1", connectpubsdb());
		  echo "\n<p>The record with PubMed ID <a href=\"$config[pageshomeurl]manage/?action=edit&amp;pubid=$existing[pubid]\">{$current[url][0]}</a> (<strong><em>"
		    . htmlspecialchars($current['title'][0]) . "</em></strong>) is already in the database, so rather than inserting a duplicate, "
		    . " the system has associated your department with the existing record.</p>";
		  $total++;
		  continue; // We skip the rest of the loop
		}
      /*
	   Search for results where:
	   startpage = startpage or null;
	   year = year;
	   SOUNDEX(title)=SOUNDEX(title);
	  */
      $q = "SELECT * FROM PUBLICATIONS WHERE (startpage='" . mysql_real_escape_string($current['startpage'][0]) 
	     . "' OR startpage='' OR startpage=0) "
	     . "AND year=" . $current['year'][0] . " "
	      . "AND LOCATE('" . mysql_real_escape_string(substr($current['title'][0], 4, 17)) . "', title)" ;
      
	  // echo("<p>Duplication test query: $q</p>");

	  $res = mysql_query($q,connectpubsdb());
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
	    $thisok='y';
  	    // echo "<p>Found no duplicates.</p>";
	  }
	} // End of should-we-be-checking-it-for-duplicates

	// If $thisok or if there is no duplicate, then insert it
	if($thisok=='y')
    {
	  $thisok='n';
	  
	  if(insertentry($current))
	    $total++;
//	 	echo "<p>I would have inserted " . formataref2(collapseArrayParts($current), '', '') . "</p>";
	}
	else
	{
      // If there IS a duplicate then $addingdata is false - present information and request guidance
      $addingdata=false;
      $showfinishedsign = false;
	  ?>
	  <p><strong>Possible duplicates have been located.</strong> The reference you are inserting is:</p>
      <ul><li><?php echo formataref2(collapseArrayParts($current), '', ''); /* formatpub($current) */ ?></li></ul>
	  <p>The following reference(s) are in the database already and have been identified as possibly being the same entry:</p>
	  <ul>
	  <?php
	  foreach($results as $v)
	    echo "\n  <li>" . formataref2($v, '', '') . "</li>";
	  ?>
	  </ul>
	  <p>Please decide whether the reference should be inserted or not and then choose from one of these options:</p>
      <table align="center"><tr><td>
      <form method="post" action="./">
        <input name="total" type="hidden" value="<?php echo $total ?>" />
        <input name="autoapprove" type="hidden" value="<?php echo $autoapprove ?>" />
        <input name="chosendept" type="hidden" value="<?php echo $chosendept ?>" />
        <input name="thisok" type="hidden" value="y" />
        <input name="data" type="hidden" value="<?php echo htmlspecialchars($choppeddata[0] . "\r\n\r\n" . $data) ?>" />
	    <input type="submit" value="Insert entry">
	  </form>
	  </td>
	  <td>
      <form method="post" action="./">
        <input name="total" type="hidden" value="<?php echo $total ?>" />
        <input name="autoapprove" type="hidden" value="<?php echo $autoapprove ?>" />
        <input name="chosendept" type="hidden" value="<?php echo $chosendept ?>" />
        <input name="data" type="hidden" value="<?php echo htmlspecialchars($data) ?>" />
	    <input type="submit" value="Skip entry">
	  </form>
	  </td>
	  <td>
	  <?php
	  // If skipping, then we may also need to ensure the EXISTING publication has the current dept code
	  if(sizeof($results)==1)
	  {
		  if($mydept = singleDeptAdmin($_SERVER['REMOTE_USER']))
			$addthedept = $mydept;
		  else
			$addthedept = $chosendept;
		  if(strpos($results[0]['deptlist'], $addthedept)===false)
		  {
		  ?>
		  <form method="post" action="./">
			<input name="total" type="hidden" value="<?php echo $total ?>" />
			<input name="autoapprove" type="hidden" value="<?php echo $autoapprove ?>" />
			<input name="chosendept" type="hidden" value="<?php echo $chosendept ?>" />
			<input name="ensuredept" type="hidden" value="<?php echo $results[0]['pubid'] ?>" />
			<input name="data" type="hidden" value="<?php echo htmlspecialchars($data) ?>" />
			<input type="submit" value="Skip entry, and add existing entry to your department">
		  </form>
		  </td>
		  <?php
		  }
	  }
	  ?>
	  </tr></table>
	  <?php
	}
  } // End of while($addingdata=true)
  if($showfinishedsign)
  {
	  ?>
	  <p>Finished adding data.</p>
	  <div class="highlypaddedmessagebox "><p>Please note: The data does not contain connections between a given 
	    publication and a given <?php echo $config['institutionname'] ?> user account - 
		so no associations have been made between user ID and publication.</p>
	  <p>If you like, you can <a href="<?php echo $config['pageshomeurl'] ?>manage/?action=userlesspubs">interactively manage the publications in your department(s) with
	    no associated authors</a>.</p></div>
	  <?php
  }
}
else
{  // Provide a form with which to insert data
?>
<h1>Import PubMed summary data into publications database</h1>
<form action="./" method="post" enctype="multipart/form-data">
  <p class="simplepaddedmessagebox"><em>&quot;PubMed summary data&quot;</em> is
    obtained as follows: Search PubMed for the references you want, and display
    them on the screen. <strong>Make sure that &quot;Display&quot; is set to &quot;Summary&quot;.</strong> Then
    choose the &quot;Send to &gt; File&quot; option, and you will be prompted
    to save a file to disk.</p>
  <p>To load PubMed summary data into the online publications
  database:</p>
<div style="margin: 10px 3px 10px 100px;">
<p><input name="uploadRISfile" type="file" size="30" /></p>
<p>	<label style="padding: 1px 40px 1px 1px;"><input name="autoapprove" type="radio" value="1" checked="checked" />Approve immediately</label>
	<label style="padding: 1px 1px 1px 40px;"><input name="autoapprove" type="radio" value="0" />Don't approve immediately</label></p>
<?php
if(singleDeptAdmin($_SERVER['REMOTE_USER'])==false)
{
  $mydepts = getMyDepartments($_SERVER['REMOTE_USER']);
  echo "<p>Choose department to assign data to:
  <select name='chosendept'>";
  foreach($mydepts as $dcode=>$ddept)
    echo "<option value='$dcode'>$ddept[NAME]</option>";
  echo "</select></p>";
}
?>
<p align="center"><input type="submit" value="Submit" /></p>
</div>
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


function processPubMedEntry($t)
{
  global $pmregex, $autoapprove, $chosendept;
/*  if($config['debug'])
  {
    echo "\n\n<!--\$t:\n\n";
	print_r($t);
	echo "\n\n-->\n\n";
  }
  */
  if(!preg_match($pmregex, trim($t), $matches))
    return false;
  $ret = array('reftype'=>array('JOUR'), 
               'notes'=>array('Imported from PubMed ' . date('d/m/Y'))
			   );

  if(($autoapprove!='0') && authorisedForPubs($_SERVER['REMOTE_USER']))
  {
    $ret['approvedby'][] = mysql_real_escape_string($_SERVER['REMOTE_USER']);
    $ret['approvaldate'][] = "NOW()";
    if($mydept = singleDeptAdmin($_SERVER['REMOTE_USER']))
      $ret['deptlist'][] = ",$mydept,";
    else
      $ret['deptlist'][] = ",$chosendept,";
  }else{
    if($mydept = singleDeptAdmin($_SERVER['REMOTE_USER']))
      $ret['pendingdepts'][] = ",$mydept,";
    else
      $ret['pendingdepts'][] = ",$chosendept,";
  }

  $ret['authorlist'] = explode(',',preg_replace('/\r\n|\r|\n/', ' ', $matches[2]));

  foreach($ret['authorlist'] as $k=>$v)
    if(preg_match('/^(.+) (\w+)$/', trim($v), $authormatches))
	  $ret['authorlist'][$k] =  $authormatches[1] . ',' . preg_replace('/\.+/','.',implode('.', preg_split('/(?<=\w)(?=\w)/',$authormatches[2]))) . '.';
	else
	  $ret['authorlist'][$k] = preg_replace('/(.+) (\w+)/', '$1,$2.', trim($v));

//  foreach($ret['authorlist'] as $k=>$v)
//    $ret['authorlist'][$k] = preg_replace('/(.+) (\w+)/', '$1,$2', trim($v));
  $ret['title'][] = trim(preg_replace('/\r\n|\r|\n/', ' ', $matches[3]));
  $ret['journal'][] = $matches[4];
  $ret['year'][] = intval($matches[5]);
  $ret['yearother'][] = $matches[6];

  $tempvi = preg_split('/\D+/',$matches[7], 3);
  $ret['volume'][] = ($tempvi[0]);
  $ret['issue'][] = ($tempvi[1]);

  $ret['startpage'][] = ($matches[8]);
  $ret['endpage'][] = ($matches[9]);
  if(strlen($ret['endpage'][0])>0 && strlen($ret['endpage'][0])<strlen($ret['startpage'][0]))
  {
    $ret['endpage'][0] = (substr($ret['startpage'][0],0,strlen($ret['startpage'][0])-strlen($ret['endpage'][0]))
	                     . $ret['endpage'][0]);
  }

  if(trim($matches[10])!='')
    $ret['notes'][] = $matches[9];

  $ret['url'][] = 'pm:' . $matches['11'];

  if(true)
  {
    echo "\n\n<!--\n\n";
	print_r($ret);
	echo "\n\n-->\n\n";
  }
  return $ret;
}

?>
