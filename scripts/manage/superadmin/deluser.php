<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<?php

$userid     = mysql_real_escape_string($_REQUEST['userid'], connectpubsdb());
$confirm    = $_POST['confirm'];
$deleteuser = $_POST['deleteuser'];


if($confirm == 'y' && strlen($userid)){
  $q = "DELETE FROM USERS WHERE userid='$userid' LIMIT 1";
  $res = mysql_query($q, connectpubsdb());
  if($res){
    echo "<p>Thank you - the user has been deleted from the USERS table.</p>";
  }else{
    echo "<p>Deletion failed - there may have been a database error. Please contact technical support.</p>";
  }
}elseif(strlen($userid)){
  // Fetch the user's details and show them to the admin, so they can be sure they're deleting the right person
  $q = "SELECT USERS.*, DEPTS.NAME AS deptname FROM USERS LEFT JOIN DEPTS USING (deptid) WHERE USERID='$userid' LIMIT 1";
  $res = mysql_query($q, connectpubsdb());
  if($res && $row = mysql_fetch_assoc($res)){
    ?> 
  <form action="./" method="post">
  <input type="hidden" name="action" value="deluser" />
  <input type="hidden" name="confirm" value="y" />
   <p>Please check the user's details before confirming that they should be COMPLETELY deleted:</p>
    <ul>
    <li><strong>User ID:</strong> <?php echo $row['userid'] ?></li>
    <li><strong>First name:</strong> <?php echo $row['firstname'] ?></li>
    <li><strong>Last name:</strong> <?php echo $row['lastname'] ?></li>
    <li><strong>Department:</strong> <?php echo $row['deptname'] ?></li>
    </ul>
  <input type="hidden" name="userid" value="<?php echo htmlspecialchars($row['userid']) ?>" /></label>
  <input type="submit" value="DELETE COMPLETELY" />
  </form>
    <?php
  }else{
    ?><p>Failed to retrieve user record.</p><?php
  }
}else{
  ?>
  <p>Deleting a user is a PERMANENT and IRREVERSIBLE process.</p>
  <form action="./" method="post">
  <input type="hidden" name="action" value="deluser" />
  <label>
    Enter the user ID:
  <input type="text" name="userid" value="" /></label>
  <input type="submit" value="Check for user ID in database" />
  </form>
  <?php
}





?>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>