<?php

// Find entries with the same OAI ID and merge them!

// $action==oaiduplicates

require_once(dirname(dirname(__FILE__)) . '/common.php');
require_once(dirname(dirname(__FILE__)) . '/duplicates/duplicatelib.inc.php');



if(is_array($_REQUEST['merge'])){
  foreach($_REQUEST['merge'] as $a=>$b){
    $res = duplicate_merge($a, $b); // The first one is the one that is kept
    echo "<p>Merging $a and $b was ".($res?'':'NOT ')." successful.</p>";
  }
}

// Now find duplicates to suggest.
$q = 'SELECT p1.pubid id1, p2.pubid id2, p1.title t1, p2.title t2, p1.oaiid oaiid FROM PUBLICATIONS p1 LEFT JOIN PUBLICATIONS p2 ON (p1.pubid<>p2.pubid) '
      . ' WHERE p1.oaiid<>"" AND p1.oaiid=p2.oaiid AND p1.pubid<p2.pubid ORDER BY p1.oaiid';

$res = mysql_query($q, connectpubsdb());

if(mysql_num_rows($res)==0){
  ?><p>No OAI ID duplication is now detected in the database.</p><p>&nbsp;</p><p>&nbsp;</p><?php
}else{
?>
<p>The following pairs have exactly the same OAI ID, which should only happen if some duplication has occurred in 
the OAI import process.</p>
<form action="./" method="post"><ul><?php
while($row = mysql_fetch_assoc($res)){
  echo "<li><label><input type='checkbox' name='merge[$row[id1]]' value='$row[id2]' checked='checked' />
  $row[oaiid]<br />$row[t1]<br />$row[t2]</label></li>";
}
?></ul>
<input name="action" type="hidden" value="oaiduplicates"/>
<input type="submit" value="Merge selected"/>
</form><?php
}


?>
