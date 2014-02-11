<?php
$showlist = true;
if(strlen($editadmin)>0 && (strlen($firstname)==0 || strlen($lastname)==0 || strlen($email)==0) ){
  $q = "SELECT * FROM PUBLICATIONSADMINS LEFT JOIN USERS USING (userid) WHERE PUBLICATIONSADMINS.userid='" 
	                   . mysql_real_escape_string($editadmin) . "' LIMIT 1";
  $res = mysql_query($q, connectpubsdb());
  if($res && ($a = mysql_fetch_assoc($res))){
    ?>
    <form action="./" method="post">
    <p>User ID: <strong><?php echo $editadmin ?></strong></p>
    <p>
      <label>First name:     <input type="text" name="firstname" value="<?php echo htmlspecialchars($a['firstname']) ?>" />
      </label>
    </p>
    <p>
      <label>Last name:    <input type="text" name="lastname" value="<?php echo htmlspecialchars($a['lastname']) ?>" />
      </label>
    </p>
    <p>
      <label>Email address:    <input type="text" name="email" value="<?php echo htmlspecialchars($a['email']) ?>" />
      </label>
    </p>
    <p>Department(s):<br /><select name="deptid[]" size="<?php echo sizeof($depts) ?>" multiple="multiple">
		 <?php
		 foreach($depts as $dept)
		   echo "\n  <option value='$dept[DEPTID]'"
		           . (strpos($a['deptslist'], ','.$dept['DEPTID'].',')!==false ? ' selected="selected"' : '')
				   . ">$dept[NAME]</option>";
		 ?>
 	      </select></p>

    <p><input type="submit" value="Store" />
	<input type="hidden" name="action" value="admins" />
	<input type="hidden" name="editadmin" value="<?php echo htmlspecialchars($editadmin) ?>" />
	</p>
  </form>
  <?php
    $showlist = false;
  }
  else
    echo "<p class='errormessage'><strong>Error.</strong> Failed to extract database details for administrator $editadmin.<br />
	Error details: " . mysql_error() . "</p>";
}

if($showlist){
?>

	<ul>
	  <li><a href="#deptl">Single-department admins</a></li>
	  <li><a href="#multi">Multi-departmental admins</a></li>
	  <li><a href="#global">Global admins</a></li>
	  <li><a href="#addnew">Add a new administrator</a></li>
	</ul>
	<script type="text/javascript">
	function editadmin(id, firstname, lastname, email)
	{
	  var url = './?action=admins&editadmin=' + id;
	  var answer;
	  answer = prompt("First name for user " + id, firstname);
	  if(!answer) return;
	  url += '&firstname=' + answer;
	  answer = prompt("Last name for user " + id, lastname);
	  if(!answer) return;
	  url += '&lastname=' + answer;
	  answer = prompt("Email address for user " + id, email);
	  if(!answer) return;
	  url += '&email=' + answer;
	  location.href = url;
	}
	</script>
	<?php
    // If deleting an administrator
	if(strlen($deladmin)>3)
	{
	  $q = "DELETE FROM PUBLICATIONSADMINS WHERE userid='" 
	                   . mysql_real_escape_string($deladmin) . "' LIMIT 1";
      mysql_query($q, connectpubsdb());
	}
	// Else if adding an administrator
	elseif(strlen($newadmin)>3 && sizeof($deptid)>0)
	{
	  // 'newadmin'==userid, 'deptid'==array of dept id codes
      // print_r("DEPTID = " . $deptid);
	  $q = "INSERT INTO PUBLICATIONSADMINS SET userid='" 
	                   . mysql_real_escape_string($newadmin) . "', email='" 
	                   . mysql_real_escape_string($email) . "', deptslist='," 
	                   . mysql_real_escape_string(implode(',', $deptid)) . ",'";
      mysql_query($q, connectpubsdb());
	  $q = "INSERT INTO USERS SET userid='" 
	                   . mysql_real_escape_string($newadmin) . "', email='" 
	                   . mysql_real_escape_string($email) . "', firstname='" 
	                   . mysql_real_escape_string($firstname) . "', lastname='" 
	                   . mysql_real_escape_string($lastname) . "' "
					   . (sizeof($deptid)==1 ? ", deptid='" 
	                   . mysql_real_escape_string(implode(',', $deptid)) . "', deptconfirmed=1" : '');
      mysql_query($q, connectpubsdb());
	  //echo "<p>Error for query $q: " . mysql_error() . '</p>';
	}
	// Else if editing an admin's details
	elseif(strlen($editadmin)>0 && strlen($firstname)>0 && strlen($lastname)>0 && strlen($email)>0)
	{
	  $q = "UPDATE PUBLICATIONSADMINS SET email='" 
	                   . mysql_real_escape_string($email) . "', "
	                   . "deptslist = '," . mysql_real_escape_string(implode(',', $deptid)) . ",'"
					   . " WHERE userid='" 
	                   . mysql_real_escape_string($editadmin) . "' LIMIT 1";
	  mysql_query($q, connectpubsdb());
	  $q = "SELECT userid FROM USERS WHERE userid='" 
	                   . mysql_real_escape_string($editadmin) . "' LIMIT 1";
      $res = mysql_query($q, connectpubsdb());
	  if(mysql_num_rows($res)==1)
	  {
	    $q = "UPDATE USERS SET email='" 
	                   . mysql_real_escape_string($email) . "', firstname='" 
	                   . mysql_real_escape_string($firstname) . "', lastname='" 
	                   . mysql_real_escape_string($lastname) . "' WHERE userid='" 
	                   . mysql_real_escape_string($editadmin) . "' LIMIT 1";
      }
	  else
	  {
	    $q = "INSERT INTO USERS SET email='" 
	                   . mysql_real_escape_string($email) . "', firstname='" 
	                   . mysql_real_escape_string($firstname) . "', lastname='" 
	                   . mysql_real_escape_string($lastname) . "', userid='" 
	                   . mysql_real_escape_string($editadmin) . "'";
      }
      mysql_query($q, connectpubsdb());
	}
	
	// List all dept admins, grouped by faculty, as well as wider administrators
	$admins = getPubsAdmins();
	$fda = groupAdminsByDeptAndFac($admins, $depts, $facs);
	?>
	<h1><a name="deptl"></a>Single-department admins</h1>
	<?php
	foreach($fda as $fkey=>$fac)
	{
	  if($fkey==0)
		echo "\n<h2>Departments with no assigned faculty</h2>";
	  else
		echo "\n<h2>" . $facs[$fkey]['TITLE'] . "</h2>";
	  echo "\n  <ol>";
	  foreach($fac as $dkey=>$dept)
	  {
		echo "\n    <li>$dept[NAME]<br />";
	    if(is_array($dept['admins']))
		{
		  echo "\n  <ul>";
	      foreach($dept['admins'] as $adk=>$admin)
		  {
		    echo "<li>"
			      . (strlen($admin['firstname'].$admin['lastname']) ? "$admin[firstname] $admin[lastname] ($adk)" : $adk )
				  . " &middot; "
//				  . "<a href=\"javascript:editadmin('$admin[userid]', '$admin[firstname]', '$admin[lastname]', '$admin[email]');\">"
				  . "<a href=\"./?action=admins&editadmin=$admin[userid]\">"
				  . "Edit</a> &middot; <a href='./?action=admins&deladmin=$adk' onclick='return confirm(\"Are you absolutely sure you want to delete this administrator?\")'>Delete</a></li>";
		  }
		  echo "\n  </ul>";
		}
		else
		  echo "<strong>No administrators listed.</strong>";
        echo "</li>";
	  }
	  echo "\n  </ol>";
	}
	?>
	<h1><a name="multi"></a>Multi-department admins</h1>
	<ul>
	<?php
	foreach($admins as $admin)
      if(!   (isGlobalAdmin($admin['userid']) || singleDeptAdmin($admin['userid']))     )
		 echo "<li>"
			      . (strlen($admin['firstname'].$admin['lastname']) ? "$admin[firstname] $admin[lastname] ($admin[userid])" : $admin['userid'] )
				  .  " (Departments: "
				  . preg_replace('/^,(.*),$/',"$1",$admin['deptslist'])
				  . ") &middot; "
//				  . "<a href=\"javascript:editadmin('$admin[userid]', '$admin[firstname]', '$admin[lastname]', '$admin[email]');\">"
				  . "<a href=\"./?action=admins&editadmin=$admin[userid]\">"
				  . "Edit</a> &middot; <a href='./?action=admins&deladmin=$admin[userid]' onclick='return confirm(\"Are you absolutely sure you want to delete this administrator?\")'>Delete</a></li>";
    ?>
	</ul>
	<h1><a name="global"></a>Global admins</h1>
	<em>(These can only be added/deleted directly to the MySQL table, for safety...)</em>
	<ul>
	<?php
	foreach($admins as $admin)
	  if(isGlobalAdmin($admin['userid']))
		 echo "<li>"
			      . (strlen($admin['firstname'].$admin['lastname']) ? "$admin[firstname] $admin[lastname] ($admin[userid])" : $admin['userid'] )
				  .  "</li>";
    ?>
	</ul>
	<form method="post" action="./" style="border: 1px solid black; padding: 5px; margin: 5px;">
	  <h2><a name="addnew"></a>To add a new administrator:</h2>
	  <p>Enter their user ID: <input type="text" name="newadmin" maxlength="7" size="10" />
	    and department(s): 
		 <select name="deptid[]" size="10" multiple="multiple">
		 <?php
		 foreach($depts as $dept)
		   echo "\n  <option value='$dept[DEPTID]'>$dept[NAME]</option>";
		 ?>
 	      </select></p>
	  <p>Additional information:</p>
	  <table>
	   <tr>
	     <th>Firstname</th>
	     <th>Lastname</th>
	     <th>Email address</th>
	     <th></th>
	     <th></th>
	   </tr>
	   <tr>
	     <td><input type="text" name="firstname" size="16" /></td>
	     <td><input type="text" name="lastname" size="16" /></td>
	     <td><input type="text" name="email" size="32" /></td>
	     <td></td>
	     <td></td>
	   </tr>
	  </table>
	  <p align="center">
	    <input type="hidden" name="action" value="admins" />
	    <input type="submit" value="Submit" />
	  </p>
	</form>
<?php
} // End of decision whether to show the list or not
?>