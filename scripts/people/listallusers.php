<?php

error_reporting(E_ALL ^ E_NOTICE);

// Simply list all users. If "deptid" is supplied then restrict to that dept.

require_once(dirname(dirname(__FILE__)) . '/manage/common.php');
if(!isset($deptid) && isset($_REQUEST['deptid']))
  $deptid = $_REQUEST['deptid'];

$depts = getalldepts();

$q = "SELECT USERS.*, PUBLICATIONSADMINS.deptslist AS deptslist FROM USERS LEFT JOIN PUBLICATIONSADMINS USING (userid) ";
if(strlen($deptid)>0){
  $q .= " WHERE USERS.deptid='".mysql_real_escape_string($deptid)."' ";
  // Or if they're listed as secondary depts...
  $q .= " OR (USERS.otherdepts<>',' AND USERS.otherdepts LIKE '%,".mysql_real_escape_string($deptid).",%')";
}
$q .= " ORDER BY lastname, firstname";

if(!($res = mysql_query($q, connectpubsdb()))){
  echo "<p>Database error. Please try refreshing the page, and contact the system administrator if this error persists.</p>";
}else{
  $subcategorised = array();
  while($row = mysql_fetch_assoc($res)){
    $line = "<li><a href='$config[pageshomeurl]?users%5B%5D=$row[userid]&action=search'><strong>$row[lastname]</strong>, $row[firstname]</a> <small>";
    if(isset($depts[$row['deptid']])){
      $line .= '(department: <a href="'.$depts[$row['deptid']]['depturl'].'">'.$depts[$row['deptid']]['NAME'].'</a>';
	 if($row['otherdepts']!=','){
	   $ods = splitDeptListString($row['otherdepts']);
	   foreach($ods as $od){
	     $line .= ' and ' . $depts[$od]['NAME'];
	   }
	 }
	 $line .= ')';
    }
    if(strlen($row['deptslist'])>0)
      $line .= " <em>(publications administrator)</em> ";
    $line .= "</small></li>";
    $subcategorised[strtoupper(substr($row['lastname'], 0, 1))][] = $line;
  }
  foreach($subcategorised as $letter=>$sublist){
    ?>&nbsp;<a href="#cat_<?php echo $letter ?>"><?php echo $letter ?></a>&nbsp;<?php
  }
  foreach($subcategorised as $letter=>$sublist){
    ?><a name="cat_<?php echo $letter ?>"></a><ul><?php
    echo implode("\n", $sublist);
    ?></ul><?php
  }
  
}


?>