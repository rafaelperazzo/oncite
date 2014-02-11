<?php

require_once('manage/config.inc.php');
require_once('query/displayqueryterms.inc.php');
//RAFAEL - Inclui a proxima linha
//require_once(dirname(dirname(__FILE__)) . 'manage/common.php');
$displayterms = displayqueryterms($_REQUEST);
//RAFAEL - Iniciei a sessao
session_start();
if($_REQUEST['format']!='ris' && $_REQUEST['format']!='RIS' 
       && $_REQUEST['format']!='csv' && $_REQUEST['format']!='CSV' 
       && $_REQUEST['format']!='bibtex' && $_REQUEST['format']!='BibTeX' 
       && $_REQUEST['format']!='oaipmh'
          &&  $_REQUEST['format']!='print')
  $html=true;
if($html)
{
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-US" lang="en-UK">
<head>
<title><?php if(strlen($displayterms)>0) echo strip_tags($displayterms) . ' :: '; ?>IME/USP Research Publications</title>

<style type="text/css">
body {margin: 0px; padding: 0px;}
body, h1, p {font-family: "Times New Roman", Times, serif;}
h1 {border-bottom: 2px solid rgb(255,102,0); text-align: center; margin-bottom: 5px; padding-bottom: 5px;}
.lhmenu {float: left; width: 15%; min-height: 250px; font-size: small; 
     padding: 0px 10px 10px 0px; margin: 10px 10px 10px 0px;
     background-color: silver; color: black; border: 1px solid black; border-left: none;}
li {margin-bottom: 10px;}
</style>
</head>
<body>
<h1>Research Publication System - IME/USP (Computer Systems Group)</h1>
<div class="lhmenu">
<ul>
  <li><a href="./">Home</a></li>
  <?php
	if (isset($_SESSION['userid']))
		print("<li><a href=\"personal/\">My personal page</a></li>");
	else 
		print("<li><a href=\"personal/\">Log in</a></li>");
  ?>
  <!-- <li><a href="personal/">Log in</a></li> -->
  <li><a href="manage/">Manage</a></li>
  <li><a href="manage/superadmin/">Super Admin</a></li>
  <li><a href="people/listallusers.php">List all users</a></li>
  <li><a href="about.php">About</a></li>

</ul>
<p style="font-size:14px">Based on UCL <a href="http://myopia.sourceforge.net"/>OnCite </a></p>
</div>

<?php
}

if($_REQUEST['action']=='search')
{
  if($html)
  {
    echo "<h2><a href=\"./\">Publications database</a> - search results</h2>";
	echo "<p>Searched for: $displayterms</p>";
  }
  $timetaken='y';
  $currentyearwarning='y';
  $excludedeptless='y';
  if($_REQUEST['format']=='print')
    $showopts = '';
  else
    $showopts = 'detail';
  include_once($config['homedir'] . 'query/index.php');
  if($html)
    echo "<p><a href=\"./\">New search</a></p>";
}
else
{
?><h2>Search</h2><?php

require_once($config['homedir'] . 'query/searchform.inc.php');


?>
<p>&nbsp;</p><p>&nbsp; </p><?php
}

if($html)
{
?>
 
</body>
</html><?php
}
?>
