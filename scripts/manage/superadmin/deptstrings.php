<?php

$q = "SELECT deptlist, COUNT(*) AS cnt FROM PUBLICATIONS GROUP BY deptlist ORDER BY cnt DESC";

$res = mysql_query($q, connectpubsdb());

if(!$res){
  die("Database error while trying to fetch deptlist data");
}

$go = $_REQUEST['go']=='go';

?><p class="simplepaddedmessagebox"><?php
if($go){
  ?>The changes are being carried out.<?php
}else{
  ?>NB - the changes listed below are NOT being carried out - this is a dry run.
  In order to carry them out please use this link: 
  <a href="./?action=deptstrings&go=go">Carry out the changes</a><?php
}
?></p><?php

?><ul><?php
$changed = 0;
$unchanged = 0;

while($row = mysql_fetch_assoc($res)){
  echo "\n  <li><b>$row[deptlist]</b> ($row[cnt] entries)";
  $new = mergeDeptListString(splitDeptListString($row['deptlist']));
  if($new != $row['deptlist']){
    echo " - change to <b>$new</b>";
    if($go)
      mysql_query("UPDATE PUBLICATIONS SET deptlist='"
	        .mysql_real_escape_string($new)."' WHERE deptlist='"
	        .mysql_real_escape_string($row['deptlist'])."'", connectpubsdb());
    $changed += $row['cnt'];
  }else{
    echo " - no change";
    $unchanged += $row['cnt'];
  }
  echo "</li>";
}
?></ul><?php

if($go)
  mysql_query("OPTIMIZE TABLE PUBLICATIONS", connectpubsdb());

echo "<p>$changed changed, $unchanged unchanged.</p>";

?>