<?php

/*

For each of the departments in the database we want to output:

  <set>
    <setSpec>dept:DC</setSpec>
    <setName>Department name</setName>
  </set>

For each of the users in the database we want to output:

  <set>
    <setSpec>user:ucqfrrw</setSpec>
    <setName>Person name</setName>
  </set>

*/

$q = "SELECT DEPTID, NAME FROM DEPTS ORDER BY NAME";
$dbcon = mysql_connect($config['db_addr'], $config['db_user'], $config['db_pass']);
mysql_select_db($config['db_db'], $dbcon);

$res = mysql_query($q, $dbcon);

$depts = array();
while($row = mysql_fetch_assoc($res))
  $depts[] = $row;

?>
<?xml version="1.0" encoding="utf-8"?>
<?xml-stylesheet type='text/xsl' href='<?php echo $config['scriptshomeurl'] ?>oai/oai2.xsl' ?>
<OAI-PMH xmlns="http://www.openarchives.org/OAI/2.0/" 
         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/
         http://www.openarchives.org/OAI/2.0/OAI-PMH.xsd">
  <responseDate><?php echo $utcrightnow ?></responseDate>
 <request verb="ListSets">http://<?php echo htmlspecialchars($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?></request>
 <ListSets>
<?php

foreach($depts as $d)
{
  ?>
  <set>
    <setSpec>dept:<?php echo utf8_encode(str_replace('/', '.', htmlspecialchars($d['DEPTID']))); ?></setSpec>
    <setName><?php echo utf8_encode(htmlspecialchars($d['NAME'])); ?></setName>
  </set>
  <?php
}

$q = "SELECT userid, lastname, firstname, title, honorifics FROM USERS ORDER BY lastname, firstname";
$dbcon = mysql_connect($config['db_addr'], $config['db_user'], $config['db_pass']);
mysql_select_db($config['db_db'], $dbcon);

$res = mysql_query($q, $dbcon);

$users = array();
while($row = mysql_fetch_assoc($res))
{
  ?>
  <set>
    <setSpec>user:<?php echo utf8_encode(str_replace('/', '.', htmlspecialchars($row['userid']))); ?></setSpec>
    <setName><?php echo utf8_encode(htmlspecialchars("$row[title] $row[firstname] $row[lastname] $row[honorifics]")); ?></setName>
  </set>
  <?php
}


?>
 </ListSets>
</OAI-PMH>