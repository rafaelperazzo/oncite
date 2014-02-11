<?php
// "standalone" means not included - i.e. should be full HTML
$standalone = (__FILE__ == $_ENV['PATH_TRANSLATED']) && (strtolower($_REQUEST['fragment'])!='y');

if($standalone)
{
  ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1" />
<title>Publications</title>
</head>
<body>
  <?php
}

if($_REQUEST['action']=='search')
{
  require_once(dirname(dirname(__FILE__)) . '/index.php');
}
else
{
  if(preg_match('/^[\d]+/',$_ENV['QUERY_STRING']))
    $facultyid = preg_replace('/^([\d]+).*$/', "$1", $_ENV['QUERY_STRING']);
  require_once(dirname(dirname(__FILE__)) . '/searchform.inc.php');
}


if($standalone)
{
  ?>
</body></html>
  <?php
}

?>