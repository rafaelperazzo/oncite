<?php

// $action==keywords

// Delete keywords
if(isset($_REQUEST['delkeywords']) && is_array($_REQUEST['delkeywords']))
{
  foreach($_REQUEST['delkeywords'] as $k=>$v)
    if($v=='y')
	{ // Delete keyword $k
	  set_time_limit(30);
	  $oldkw = stripslashes($k);
	  echo "\n\n<div class='simplepaddedmessagebox '>";
	  echo "\n<p>Deleting keyword &quot;$oldkw&quot;</p>";
	  $q = "SELECT pubid, keywords FROM PUBLICATIONS WHERE KEYWORDS RLIKE '(^|,) *"
					. mysql_real_escape_string($oldkw) . " *(,|$)'";
	  $res = mysql_query($q, connectpubsdb());
	  if(!$res)
		return("<p>A database error has occurred while trying to fetch all entries using this keyword. Please retry, and contact the site administrator(s) if the error continues.</p>");
	  echo "<p>" . mysql_num_rows($res) . " entries to change.</p>";
	  while($row = mysql_fetch_assoc($res))
	  {
		// Four regex replacements - one for the beginning of the field...
		$newkwstring = preg_replace('/^\s*' . preg_quote($oldkw, '/') . '\s*,/i', "", $row['keywords']);
		// ...one for the middle...
		$newkwstring = preg_replace('/(,\s*)+' . preg_quote($oldkw, '/') . '(\s*,)+/i', ",", $newkwstring);
		// ...one for the end...
		$newkwstring = preg_replace('/(,\s*)+' . preg_quote($oldkw, '/') . '\s*$/i', "", $newkwstring);
		// ...and one for if it's the whole field
		$newkwstring = preg_replace('/^\s*' . preg_quote($oldkw, '/') . '\s*$/i', "", $newkwstring);
	//    echo "\n<p>#$row[pubid]: &quot;$row[keywords]&quot; =>  &quot;$newkwstring&quot; </p>";
		
		mysql_query("UPDATE PUBLICATIONS SET keywords='" . mysql_real_escape_string($newkwstring) 
							  . "' WHERE pubid=$row[pubid] LIMIT 1", connectpubsdb());
	  }
	  echo "\n</div>";
	}
}
// Globally edit keywords
if(isset($_REQUEST['oldkw']) && isset($_REQUEST['newkw']))
{
  $oldkw = stripslashes($_REQUEST['oldkw']);
  $newkw = stripslashes($_REQUEST['newkw']);
  echo "\n\n<div class='simplepaddedmessagebox '>";
  echo "\n<p>Global keyword editing - changing &quot;$oldkw&quot; to &quot;$newkw&quot;</p>";
  $q = "SELECT pubid, keywords FROM PUBLICATIONS WHERE KEYWORDS RLIKE '(^|,) *"
                . mysql_real_escape_string($oldkw) . " *(,|$)'";
  $res = mysql_query($q, connectpubsdb());
  if(!$res)
    return("<p>A database error has occurred while trying to fetch all entries using this keyword. Please retry, and contact the site administrator(s) if the error continues.</p>");
  echo "<p>" . mysql_num_rows($res) . " entries to change.</p>";
  while($row = mysql_fetch_assoc($res))
  {
    // Four regex replacements - one for the beginning of the field...
    $newkwstring = preg_replace('/^\s*' . addslashes($oldkw) . '\s*,/i', "$newkw,", $row['keywords']);
    // ...one for the middle...
    $newkwstring = preg_replace('/(,\s*)' . addslashes($oldkw) . '(\s*,)/i', "$1$newkw$2", $newkwstring);
    // ...one for the end...
    $newkwstring = preg_replace('/(,\s*)' . addslashes($oldkw) . '\s*$/i', ",$newkw", $newkwstring);
    // ...and one for if it's the whole field
    $newkwstring = preg_replace('/^\s*' . addslashes($oldkw) . '\s*$/i', "$newkw", $newkwstring);
//    echo "\n<p>#$row[pubid]: &quot;$row[keywords]&quot; =>  &quot;$newkwstring&quot; </p>";
	
	mysql_query("UPDATE PUBLICATIONS SET keywords='" . mysql_real_escape_string($newkwstring) 
	                      . "' WHERE pubid=$row[pubid] LIMIT 1", connectpubsdb());
  }
  echo "\n</div>";
}

// List all keywords used

$q = "SELECT COUNT(*) as count, keywords FROM PUBLICATIONS WHERE keywords != '' GROUP BY keywords ORDER BY count";

$res = mysql_query($q, connectpubsdb());

if(!$res)
  die("<p>Database error: " . mysql_error() . "</p>");

$keywords = array();
while($row = mysql_fetch_assoc($res))
{
//  echo "\n<p>$row[count] occurrences:<br />$row[keywords]</p>";
  $temp = explode(',', $row['keywords']);
  foreach($temp as $t)
    if(trim($t)!='')
      $keywords[trim($t)] += intval($row['count']);
}
?>
<h2>Keywords found more than 5 times:</h2>
<p>(NB - The links to search pages here are convenient, but they don't search just the keywords field so the results they retrieve may include more than you expect.)</p>
<form action="./" method="get">
<input type="hidden" name="action" value="keywords" />
<script type="text/javascript">
function changeKeyword(oldkw)
{
  var newkw = prompt("Change keyword \"" + oldkw + "\" to:", oldkw);
  if(newkw && (oldkw!=newkw) && confirm("Are you sure?\nThis is a global operation and will affect a number of records:\n\n  Change \"" + oldkw + "\" to \"" + newkw + "\""))
  {
    oldkw = oldkw.replace(/&/g, '%26').replace(/ /g, '%20');
    newkw = newkw.replace(/&/g, '%26').replace(/ /g, '%20');
	location.href="./?action=keywords&oldkw=" + oldkw + "&newkw=" + newkw;
  }
}
</script>
<?php
arsort($keywords);
foreach($keywords as $kw => $cnt)
{
  if($cnt<6) continue;
  echo "\n<p>$cnt occurrences: <a href='$config[scriptshomeurl]query/?showopts=detail&wordsearch=$kw'>$kw</a>
   - <label><input type='checkbox' name='delkeywords["
    . htmlspecialchars($kw) . "]' value='y'/>Delete</label>
   - or <a href='javascript:changeKeyword(\""
    . htmlspecialchars(addslashes($kw)) . "\")'>edit</a></p>";
}
?>
<input type="submit" value="Carry out deletions" />
</form>
