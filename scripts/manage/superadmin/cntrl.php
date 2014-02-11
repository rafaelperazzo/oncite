<?php

// Script to go through the TITLES of publications and remove/convert control characters added by Reference Manager
// $action = 'cntrl';

$newtitle = $_REQUEST['newtitle'];

if(is_array($newtitle))
{
  $updatecount = 0;
  foreach($newtitle as $pubid=>$newt)
  {
    $q = "UPDATE PUBLICATIONS SET title='" . mysql_real_escape_string($newt) 
	            . "' WHERE pubid=" . intval($pubid) . " LIMIT 1";
    if(mysql_query($q, connectpubsdb()))
	  $updatecount++;
  }
  echo "<p>$updatecount records have successfully been updated.</p>";
}
else
{

	$q = "SELECT pubid, title FROM PUBLICATIONS WHERE title REGEXP '[[:cntrl:]]+'";
	$res = mysql_query($q, connectpubsdb());
	
	?>
	<form action="./" method="post">
	<input type="hidden" name="action" value="cntrl" />
	<?php
	
	if($res)
	{
	  while($row = mysql_fetch_assoc($res))
	  {
		$newtitle = convertRefManControlChars($row['title']);
		echo "\n<p><input type='checkbox' name='newtitle[$row[pubid]]' value='" 
			   . htmlspecialchars($newtitle) 
			   . "' checked='checked' />$row[title] => <br/>$newtitle</p>";
	  }
	}
	else
	  echo "<p>Database error - sorry. Please contact the site administrator if this continues.</p>";
	
	?>
	<input type="submit" value="Store changes" />
	</form>
	<?php

} // End of no-updates-submitted

?>