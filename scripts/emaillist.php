<?php

/*
This script is publicly-accessible and will give an email distribution list for MyOPIA administrators.

To prevent unwanted access, the variable "pass" must be passed in using a hard-coded password.

Very simple, weak protection, but that's all that's needed here - the email addresses are on public 
display elsewhere, just not in such a machine-readable format.

The password is also going to be hard-coded (at UCL, at least) in our cron job which will extract 
the email list and write it to a file
*/

// HERE'S WHERE THE PASSWORD IS HARD-CODED
if($_REQUEST['pass'] != 'accurate'){
  ?>
  <form action="emaillist.php">You must supply the password: 
    <input type="text" name="pass" /><input type="submit" value="OK"/></form>
  <?php
}else{
  $justemails = true;
  require_once(dirname(__FILE__) . '/manage/emaillist.php');
}


?>