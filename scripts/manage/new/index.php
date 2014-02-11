<h2>Add new entry</h2>
<?php
require_once(dirname(dirname(__FILE__)) . '/common.php');
// include_once('/nfs/rcs/www/webdocs/lifesciences-faculty/publications/manage/common.php');
if(authorisedForPubs($_SERVER['REMOTE_USER']))
{
  if(!isset($newpubtype))
    $newpubtype=$_REQUEST['newpubtype'];

  if($action=='storepub')
    storepub();
  else if($action=='storepubassocs')
    storepubassocs();
  else if(!(strlen($newpubtype)>0))
  {
    echo "<p>Please choose which type of publication you wish to add:</p>";
    chooseNewPublicationTypeForm();
  }
  else
    singlePublicationForm(null);
}
else
{
  ?>
  <p>You are not authorised to add a new entry to the database using this page. You can add an entry 
  via your personal listings. If this is an error or if you need more help please contact the 
  webmaster.</p>
  <?php
}

// echo editableentrytable(array());
?>