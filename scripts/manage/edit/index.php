<?php
require_once(dirname(dirname(__FILE__)) . '/common.php');
// include_once('/nfs/rcs/www/webdocs/lifesciences-faculty/publications/manage/common.php');

$pubid = intval($_REQUEST['pubid']);
$action = $_REQUEST['action'];

if($pubid>0 || $action=='storepub')
{
  if($action=='delete')
  {
    deletepub($pubid);
  }
  else if($action=='storepub')
  {
    storepub();
  }
  else if($action=='storepubassocs')
  {
    storepubassocs();
  }
  else if($action=='changepubtype')
  {
    // Display the form
    ?><?php
    $q = "SELECT * FROM PUBLICATIONS "
     . " WHERE pubid='$pubid' LIMIT 1";
    $res = mysql_query($q, connectpubsdb());
    $p = mysql_fetch_assoc($res);
    
    require_once($config['homedir'] . 'manage/includes/changePubTypeForm.inc.php');

  }
  else
  {
    // First check if a forced pubtype change has been supplied, and carry it out
	if(isset($_REQUEST['changepubtype']))
	  changePubType($pubid, stripslashes($_REQUEST['changepubtype']));
  
  
    // Provide editable form for the single publication
    $q = "SELECT * FROM PUBLICATIONS "
     . " WHERE pubid='$pubid' LIMIT 1";
    $res = mysql_query($q, connectpubsdb());
    $p = mysql_fetch_assoc($res);

    // Check that this publication is editable by the user.
    if(!canUserEditPub($p, $_SERVER['REMOTE_USER'])){
      ?><p style="padding: 50px;">You do not seem to be authorised to edit entry #<?php echo $pubid ?>. If this is in error, please contact the support team.</p><?php
	 if(preg_match('/^[,]*$/', $p['deptlist'].$p['pendingdepts'])){
        ?><p style="padding: 0px 50px 50px;">Reason: Entry is marked as a "personal" entry, i.e. not associated with any department.</p><?php     
	 }
      return;
    }

    // If the above pubtype-change has happened, then...
    if(isset($_REQUEST['changepubtype']) && isset($_REQUEST['oldpubtype'])) {
      $oldpt = mysql_real_escape_string($_REQUEST['oldpubtype']);
      $newpt = mysql_real_escape_string($_REQUEST['changepubtype']);
      // Check if any fields have NOT been carried across from the old type
      $q = "SELECT * FROM PUBLICATIONSREFTYPES WHERE reftype IN ('$oldpt', '$newpt')";
      if($res = mysql_query($q)){
	   // Retrieve the two rows
	   $row = mysql_fetch_assoc($res);
	   $typedef[$row['reftype']] = $row;
	   $row = mysql_fetch_assoc($res);
	   $typedef[$row['reftype']] = $row;
	   // Now go through the data, checking for missingnesses
	   $missingnesses = array();
	   foreach($typedef[$newpt] as $newfield=>$newdisplaymode){
	     $fieldname = substr($newfield, 7);
	     if($newdisplaymode=='' && $typedef[$oldpt][$newfield]!='' && trim($p[$fieldname])!=''){
		  $missingnesses[($typedef[$oldpt][$newfield]=='Y' ? $fieldname : $typedef[$oldpt][$newfield])] 
		       = htmlspecialchars($p[$fieldname]);
		}
	   }
	   if(sizeof($missingnesses)>0){
	     ?>
		<p>Some data elements are not displayed in the new reference type. If you need to make use
		  of them you should copy-and-paste them from this list:</p>
		  <dl>
		  <?php 
		  foreach($missingnesses as $key=>$val){
		    echo "\n  <dt>$key</dt><dd>$val</dd>";
		  }
		  ?>
		  </dl>
		<?php
	   }
	 }
    }

    $showapprovechoices = $_REQUEST['showapprovechoices'];
    singlepublicationform($p);
	
	if($pubid>0){
  	  ?>
	  <p style="margin: 20px; color: gray; font-style: italic; "><a href="./?action=changepubtype&pubid=<?php echo $pubid ?>">
	  Change publication type<br />for this record</a></p>
	  <?php
	}
  }
}
else
{
  // Provide a list of publications to edit
  // (all pubs if user is authorised, otherwise the ones they authored)
  // Parameters are passed in here through the URL e.g. ?years%5B%5D=1999
  ?>
  <p>Hint: Use [Ctrl+F] to find the desired publication using a word from its title</p>
  <?php
  
  // Make sure that it's the correct list of departments included
  $deptslist = trim(getAuthorisations($_SERVER['REMOTE_USER']));
  if(!isGlobalAdmin($_SERVER['REMOTE_USER']))
  {
    $deptstemp = explode(',', $deptslist);
	$depts = array();
	foreach($deptstemp as $tempv)
	  if(trim($tempv)!='')
	    $depts[] = $tempv;
  }
  
  //DELETEME - Removing this seems to fix the problem of dissociate opt appearing when not wanted: $showeditoptions = true;
  $includeinpress = 'y';
  print_r($users);
  $showopts = 'detail,edit,delete' . ((sizeof($users)>0)?',dissociate':'') 
                  . (authorisedForPubs($_SERVER['REMOTE_USER'])?',duplication':'')
                  . (isGlobalAdmin($_SERVER['REMOTE_USER'])?',superadmin':'')
                          . (singleDeptAdmin($_SERVER['REMOTE_USER'])?',notinmydept':'');
  $group = 'y';
  include_once($config['homedir'] . 'query/index.php');
  ?>
  <p>&nbsp;</p>
  <p>&nbsp;</p>
  <p>&nbsp;</p>
  <hr size="2" noshade="noshade" />
  <p>If you wish to insert a new publication entry into the database, 
      choose the type of publication from this list:</p>
  <?php
    chooseNewPublicationTypeForm();
}

?>