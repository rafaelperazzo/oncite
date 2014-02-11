<html>
<head>
<title>Untitled Document</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<?php

error_reporting(E_ALL ^ E_NOTICE);

if($_REQUEST['msgseen']!='1' && intval($_REQUEST['pubid'])>0)
{
  echo "<meta http-equiv=\"Refresh\" content=\"0;url=./?title=" . rawurlencode(strip_tags(stripslashes($_REQUEST['title']))) . "&msgseen=1&pubid=" . rawurlencode($_REQUEST['pubid']) . "\">";
}
?>
</head>

<body>
<?php
if(!(intval($_REQUEST['pubid'])>0))
{
  die("Publication ID not supplied....");
}
elseif($_REQUEST['msgseen']=='1' && $_REQUEST['action']=='store' && intval($_REQUEST['pmid'])>0 && intval($_REQUEST['pubid'])>0)
{
  require_once('common.php');
  // Store the PubMed link
  $pmurl = 'pm:' . intval($_REQUEST['pmid']);
  $pubid = intval($_REQUEST['pubid']);
  if(storeurl($pubid, $pmurl))
  {
    ?>
  <p><strong>Thank you</strong> - the link has been stored.<br>
    Publication #<strong><?php echo $pubid ?></strong>, link <strong><?php echo $pmurl ?></strong><br>
    <a href="javascript:window.close()"><br>
  Close this window</a></p>
    <?php
  }
  else
  {
    ?>
	<p><strong>Sorry</strong> - there has been an error storing the link.<br>
<br>
Technical details: <?php echo mysql_error() ?></p>
	<?php
  }
}
elseif($_REQUEST['msgseen']=='1')
{
  require_once('common.php');
  $r = pubMedLocate(stripslashes($_REQUEST['title']));
  if(sizeof($r)==0)
  {
    ?>
<p><em><?php echo htmlspecialchars(stripslashes($_REQUEST['title'])) ?></em> <strong>was not located</strong> in the
  PubMed database. </p>
<p>If you think it should be there, you could <a href="<?php echo $userpmurl ?>" target="_blank">search PubMed yourself....</a></p>
<p>Alternatively, just close this window.</p>
<?php
  }
  else
  {
    ?>
<p>The following PubMed ID(s) matched the title<br>
&quot;<em><?php echo htmlspecialchars(stripslashes($title)) ?></em>&quot;<br />
  Click on &quot;View PubMed record&quot; to view the full PubMed entry.<br>
  <strong>If it's the correct record, click on &quot;Store link&quot;</strong> to store the PubMed link in our
database.</p>
	<?php
    foreach($r as $v)
      echo "<p><strong>#$v</strong> &middot; [<a href=\"$userpmurl$v\" target=\"_blank\">View PubMed record</a>] &middot; " 
	     . "[<a href=\"./?msgseen=1&pubid=" . rawurlencode($_REQUEST['pubid']) . "&pmid=$v&action=store\">Store link</a>]</p>";
  }
}
else
{
  ?>
  <p style="padding: 100px;"><strong>Please wait:<br>
    </strong>Querying  <strong>PubMed</strong> to
    search for <br>
    &quot;<em><?php echo htmlspecialchars(stripslashes($title)) ?></em>&quot;....<br>
    <br>
    It 
    is perfectly safe to close this window if you don't want to wait.<?php
}
?>
</p>
</body>
</html>
