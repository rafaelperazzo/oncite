<?php

// If $config['userselfregister'] == true then we provide a form for self-registration.
// Otherwise we simply say "no, sorry".



if($config['userselfregister'] && strlen($_SERVER['REMOTE_USER'])>0 && strlen($_REQUEST['firstname'])>0 && strlen($_REQUEST['lastname'])>0 && strlen($_REQUEST['email'])>0 && strlen($_REQUEST['deptid'])>0)
{
  $q = "INSERT INTO USERS SET userid='"
                    . mysql_real_escape_string($_SERVER['REMOTE_USER']) . "', deptid='"
                    . mysql_real_escape_string(stripslashes($_REQUEST['deptid'])) . "', title='"
                    . mysql_real_escape_string(stripslashes($_REQUEST['title'])) . "', email='"
                    . mysql_real_escape_string(stripslashes($_REQUEST['email'])) . "', firstname='"
                    . mysql_real_escape_string(stripslashes($_REQUEST['firstname'])) . "', lastname='"
                    . mysql_real_escape_string(stripslashes($_REQUEST['lastname'])) . "', honorifics='"
                    . mysql_real_escape_string(stripslashes($_REQUEST['honorifics'])) . "', deptconfirmed=0";
  mysql_query($q, connectpubsdb());
  if(is_array($_REQUEST['otherdepts'])){
    addUserOtherDepts($_SERVER['REMOTE_USER'], $_REQUEST['otherdepts'], false);
  }
  ?>

  <p style="margin: 50px;">Thanks for registering. A departmental administrator will confirm your departmental association soon, but in the 
  mean time feel free to use the system. 
  Please <a href="<?php echo $config['pageshomeurl'] ?>personal/?action=associatepubs">click 
   here to add a publication(s) to your listings</a>.</p>
  <?php
}
elseif($config['userselfregister'] && strlen($_SERVER['REMOTE_USER'])>0)
{
  ?>
  <p style="margin: 50px;"><strong>Welcome!</strong> Your user ID, <?php echo $_SERVER['REMOTE_USER'] ?>, has not been found in the online database, so this 
   must be your first visit. Please fill in the following details to continue:</p>
  <p style="font-style: italic; padding: 10px 40px;">(NB: This form is to register as an <strong>academic</strong> user of the system. To register as an <strong>administrator</strong> please contact <a href="mailto:<?php echo $config['webmasteremail'] ?>"><?php echo $config['webmasteremail'] ?></a>.)</p>
<?php 
if(sizeof($_POST)>0) // A very simple-minded way to tell if someone's already submitted the form or not
{
?>
<p class="errormessage">REQUIRED FIELDS: First name, last name, email address, home department.</p>
<?php
}
?>
<form method="post" action="<?php echo $config['pageshomeurl'] ?>personal/" name="selfregform" id="selfregform">
     <table border="0" align="center" cellpadding="4" cellspacing="0">
       <tr>
         <th scope="row" align="right"><label for="title">Title:</label></th>
         <td><input name="title" type="text" id="title" size="8" maxlength="8" value="<?php echo htmlspecialchars(stripslashes($_POST['title'])) ?>" /></td>
       </tr>
       <tr>
         <th scope="row" align="right"><label for="firstname">First name(s):</label></th>
         <td><input name="firstname" type="text" id="firstname" size="32" maxlength="64" value="<?php echo htmlspecialchars(stripslashes($_POST['firstname'])) ?>" /></td>
       </tr>
       <tr>
         <th scope="row" align="right"><label for="lastname">Last name(s):</label></th>
         <td><input name="lastname" type="text" id="lastname" size="32" maxlength="64" value="<?php echo htmlspecialchars(stripslashes($_POST['lastname'])) ?>" /></td>
       </tr>
       <tr>
         <th scope="row" align="right">Honorifics: </th>
         <td><input name="honorifics" type="text" id="honorifics" size="16" maxlength="32" value="<?php echo htmlspecialchars(stripslashes($_POST['honorifics'])) ?>" /></td>
       </tr>
       <tr>
         <th scope="row" align="right"><label for="email">Email address:</label></th>
         <td><input name="email" type="text" id="email" size="64" maxlength="128" value="<?php echo htmlspecialchars(stripslashes($_POST['email'])) ?>" /></td>
       </tr>
       <tr>
         <th scope="row" align="right"><label for="deptid">Home department:</label></th>
         <td><select name="deptid" id="deptid"><option value="">--- Please select: ---</option><?php
$depts = getDepts();
foreach($depts as $id=>$dept)
  echo "\n<option value='" . htmlspecialchars($id) . "'" 
                     . ($id==$_POST['deptid'] ? ' selected="selected"' : '')
					 . ">" . htmlspecialchars($dept['NAME']) . "</option>";
		 ?></select></td>
       </tr>
       <tr>
         <th scope="row" align="right"><label for="deptid">Other department(s)<br />(only if jointly appointed):</label></th>
         <td><select name="otherdepts[]" id="otherdepts[]" multiple="multiple" size="4"><option value="">--- Please select: ---</option><?php
foreach($depts as $id=>$dept)
  echo "\n<option value='" . htmlspecialchars($id) . "'" 
                     . ($id==$_POST['otherdepts'] ? ' selected="selected"' : '')
					 . ">" . htmlspecialchars($dept['NAME']) . "</option>";
		 ?></select>
           <br />  
         <em>(Hold the Control key to select multiple entries if needed.)</em></td>
       </tr>
     </table>
     <p>It's important that the home department is chosen correctly, because the publications administrator for 
	   your department is involved in confirming entries that you add in to the database.</p>

     <p style="text-align: center;">
       <input type="submit" name="Submit" value="Submit">
    <!--   <input type="button" name="Submit" value="FOCUS" onclick="document.selfregform.title.focus()"> -->
     </p>

</form>
<script type="text/javascript">
<!--

if(document.selfregform && document.selfregform.title)
  document.selfregform.title.focus();

//-->
</script>
  <p>
    <?php
}
else // No self-registration
{
  ?>
  </p>
  <p style="margin: 50px;">Your user ID, <?php echo $_SERVER['REMOTE_USER'] ?>, has not been found in the online database. 
    To be added to the 
    list of staff, please <a href="mailto:<?php echo $config['webmasteremail'] ?>">email the webmaster</a> - giving 
	your <strong>full name</strong>, 
	<strong>user ID</strong>, and <strong>department</strong>.</p>
  <?php
}


?>
