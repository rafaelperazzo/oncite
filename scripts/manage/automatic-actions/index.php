<?php

/*

This script carries out ALL of the automatic actions.

*/


require_once(dirname(dirname(__FILE__)) . '/common.php');
error_reporting(E_ALL ^ E_NOTICE);

$ret = '';

$ret .= require_once('deleteolddata.inc.php');

echo "<pre>$ret</pre>";

?>