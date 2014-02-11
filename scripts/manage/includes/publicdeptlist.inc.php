<?php

require_once(dirname(dirname(__FILE__)) . '/common.php');
?>
<h1>Registered publications administrators in OnCite</h1>
<p>Sections on this page:</p>
<ol>
  <li><a href="#deptl">Single-department administrators</a></li>
  <li><a href="#multi">Multi-departmental administrators</a></li>
  <li><a href="#global">Sitewide administrators</a></li>
</ol>
<script type="text/javascript">
function writeTo(dom, u)
{
  var a = '@';
  document.location.href='ma'+'il'+'to'+':'+u+a+dom;
}
</script>
<?php

function obscEmailLink($email)
{
  return preg_replace('/^(.*)@(.*)$/', "<a href=\"javascript:writeTo('$2', '$1')\">$1{at}$2</a>", $email);
}

// List all dept admins, grouped by faculty, as well as wider administrators
$depts = getalldepts();
$facs = getallfaculties();
$admins = getPubsAdmins();
$fda = groupAdminsByDeptAndFac($admins, $depts, $facs);
?>
<h1><a name="deptl"></a>1. Single-department administrators</h1>
Grouped according to faculty:
<ul>
<?php
foreach($fda as $fkey=>$fac)
{
  if($fkey!=0)
    echo "\n      <li><a href=\"#$fkey\">" . $facs[$fkey]['TITLE'] . "</a></li>";
}
?></ul>
<?php
foreach($fda as $fkey=>$fac)
{
  if($fkey==0)
	echo "\n<h2><a name='0'></a>Departments with no assigned faculty</h2>";
  else
	echo "\n<h2><a name='$fkey'></a>" . $facs[$fkey]['TITLE'] . "</h2>";
  echo "\n  <ol>";
  foreach($fac as $dkey=>$dept)
  {

	
	// echo "\n    <li>$dept[NAME] ($dept[DEPTID])<br />";
	echo "\n    <li>";
	if ($dept['depturl'] != "") {
		echo "<a href = \"" . $dept['depturl'] . "\">";
	}	
	echo "$dept[NAME] ($dept[DEPTID])";
	
	// output website if exists
	if ($dept['depturl'] != "") {
		echo "</a>";
	}
		echo "<br />";

	
	if(is_array($dept['admins']))
	{
	  echo "\n  <ul>";
	  foreach($dept['admins'] as $adk=>$admin)
	  {
		echo "<li>"
			  . (strlen($admin['firstname'].$admin['lastname']) ? "$admin[firstname] $admin[lastname] ($adk)" : $adk )
			  . (strlen($admin['email'])>0 ? ' ['.obscEmailLink($admin['email']).']' : '')
			  .  "</li>";
	  }
	  echo "\n  </ul>";
	}
	else
	{
	  // Determine whether a multi-deptl admin covers this department
	  $multicovers = array();
	  foreach($admins as $admin)
	    if(strpos($admin['deptslist'], ",$dept[DEPTID],")!==false)
		  $multicovers[] = "$admin[firstname] $admin[lastname]";
	  if(sizeof($multicovers)==0)
	    echo "<ul><li><em>(No administrators listed)</em></li></ul>";
	  elseif(sizeof($multicovers)==1)
	    echo "<ul><li>Covered by multi-department administrator ".$multicovers[0]."</li></ul>";
	  else
	    echo "<ul><li>See multi-department administrators: ".implode(', ', $multicovers)."</li></ul>";
	}
	echo "</li>";
  }
  echo "\n  </ol>";
}
?>
<h1><a name="multi"></a>2. Multi-department administrators</h1>
<ul>
<?php
foreach($admins as $admin)
  if(!   (isGlobalAdmin($admin['userid']) || singleDeptAdmin($admin['userid']))     )
	 echo "<li>"
			  . (strlen($admin['firstname'].$admin['lastname']) ? "$admin[firstname] $admin[lastname] ($admin[userid])" : $admin['userid'] )
			  . (strlen($admin['email'])>0 ? ' ['.obscEmailLink($admin['email']).']' : '')
			  .  " (Departments:$admin[deptslist])</li>";
?>
</ul>
<h1><a name="global"></a>3. Sitewide administrators</h1>
<?php
// We don't actually want to list the global admins - provide the blurb instead
echo $config['globaladminstext'];
/*
echo "\n<ul>";
foreach($admins as $admin)
  if(isGlobalAdmin($admin['userid']))
	 echo "\n   <li>"
			  . (strlen($admin['firstname'].$admin['lastname']) ? "$admin[firstname] $admin[lastname] ($admin[userid])" : $admin['userid'] )
			  . (strlen($admin['email'])>0 ? ' ['.obscEmailLink($admin['email']).']' : '')
			  .  "</li>";
echo "\n</ul>";
*/


?>