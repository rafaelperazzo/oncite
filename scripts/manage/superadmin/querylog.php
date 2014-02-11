<?php

//$action == querylog

// Form for querying the "PUBLICATIONSTRANS" table which stores actions such as add, delete, merge


$grab = array('actiontype');
foreach($grab as $g)
  $$g = $_REQUEST[$g];
?><form action="./" method="get">
<input type="hidden" name="action" value="querylog" />
<label>Pub  ID: <input type="text" name="pubid" size="8" value="<?php echo htmlspecialchars($pubid) ?>" /></label>
<label>User ID: <input type="text" name="userid" size="8" value="<?php echo htmlspecialchars($userid) ?>" /></label>
<label>Action type: <select name="actiontype">
  <option value="">Any</option>
  <optgroup label="Publications:">
  <option>insertentry</option>
  <option>storepub</option>
  <option>storepubassocs</option>
  <option>approvepub</option>
  <option>rejectpub</option>
  <option>merge</option>
  <option>dissociate</option>
  <option>changepubtype</option>
  <option>deletepub</option>
  </optgroup>
  <optgroup label="Users:">
  <option>addnewperson</option>
  <option>updatename</option>
  </optgroup>
</select></label>
<input type="submit" value="Search" />
</form><?php

if($pubid || $actiontype || $userid){
  $q = "SELECT PUBLICATIONSTRANS.*, DATE_FORMAT(PUBLICATIONSTRANS.timestamp,'%d.%m.%Y %H:%i:%s') as eurotimestamp, USERS.firstname, USERS.lastname FROM PUBLICATIONSTRANS LEFT JOIN USERS USING (userid) WHERE (1";
  if($pubid)
    $q .= " AND pubid='".intval($pubid)."'";
  if($userid)
    $q .= " AND userid LIKE '".mysql_real_escape_string($userid)."%'";
  if($actiontype)
    $q .= " AND action LIKE '".mysql_real_escape_string($actiontype)."%'";
  $q .= ") ORDER BY PUBLICATIONSTRANS.timestamp ASC";
  $res = mysql_query($q, connectpubsdb());
  if(!$res)
    echo mysql_error();
  elseif(mysql_num_rows($res)==0){
    echo "<p>No results found.</p>";
  }else{
    ?><table style="border-collapse: collapse; border: 1px solid black;" border="1"><tr>
      <th>pubid</th>
      <th>userid</th>
      <th>user name</th>
      <th>timestamp</th><?php
    if ($usertype == "superadmin")
      echo "<th>ipaddress</th>";
    ?><th>action</th>
    </tr><?php
    
    while($row = mysql_fetch_assoc($res)){
      ?><tr><td><a href="<?php echo $config[pageshomeurl] ?>manage/?action=edit&pubid=<?php echo $row['pubid'] ?>" target="_blank"><?php echo htmlspecialchars($row['pubid']);
      ?></a></td><td><?php echo htmlspecialchars($row['userid']);
      ?></td><td><?php echo htmlspecialchars($row['firstname'].' '.$row['lastname']);
      ?></td><td><?php echo htmlspecialchars($row['eurotimestamp']);
    if ($usertype == "superadmin")
      echo "</td><td>",htmlspecialchars($row['ipaddress']);
      ?></td><td><?php echo htmlspecialchars($row['action']);
      ?></td></tr><?php
    }
    ?></table><?php
  }
}



?>
