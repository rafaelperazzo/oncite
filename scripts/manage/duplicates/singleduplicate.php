<?php

// Supply a single pub ID and we'll try to find all possible matches that might be duplicates...


require_once(dirname(dirname(__FILE__)) . '/common.php');
require_once(dirname(__FILE__) . '/duplicatelib.inc.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/query/formataref.php');

$pubid = intval($_REQUEST['pubid']);
$match = $_REQUEST['match'];

$q = "SELECT * FROM PUBLICATIONS WHERE pubid=$pubid LIMIT 1";
$res = mysql_query($q, connectpubsdb());
if($res && ($p = mysql_fetch_assoc($res))){
  ?>
  <form action="<?php echo $_SERVER['PHP_SELF'] ?>" style="border: 1px solid gray; padding: 5px; margin: 5px 100px;">
    <p>Search for entries where:</p>
    <ul>
       <li><label>Title <select name="match[title]"><?php echo textmatchdropdown($match['title']?$match['title']:'almost') ?></select></label></li>
       <li><label>Year <select name="match[year]"><?php echo textmatchdropdown($match['year']) ?></select></label></li>
       <li><label>Author list <select name="match[authorlist]"><?php echo textmatchdropdown($match['authorlist']?$match['authorlist']:'almost') ?></select></label></li>
       <li><label>Periodical title <select name="match[journal]"><?php echo textmatchdropdown($match['journal']?$match['journal']:'approx') ?></select></label></li>
       <li><label>Publication type <select name="match[pubtype]">
	<option value='exact' selected="selected">matches exactly</option>
     <option value='any'>can be anything</option></select></label></li>
    </ul>
    <p><strong>Please note:</strong> The more approximate your search parameters, the slower the search. 
       <br />Try to use 
       &quot;matches exactly&quot; when possible.</p>
    <input type="hidden" name="pubid" value="<?php echo $pubid ?>" />
    <input type="hidden" name="action" value="singleduplicate" />
    <input type="submit" value="Search" />
  </form>
<form action="advancedmerge.php" method="post">
<h2>Current record:</h2>
  <ul><li><label><input type="checkbox" name="pubid[]" checked="checked" value="<?php echo $p['pubid'] ?>" />
  <?php echo formataref2($p, '') ?> <br /><em>(ID number: <?php echo $p['pubid'] ?>)</em></label></li></ul>
<h2>Suggested matches:</h2>
  <?php
  
  if(is_array($match) && sizeof($match)>0){
    // Perform the search!
    
    ?>
    <div id="searchingpleasewait" style="padding: 20px 100px; margin: 2px 60px; background-color: silver; border: 10px solid gray; font-size: x-large;">
    Searching - please wait...
    </div>
    <?php
    echo "\n\n";
    ob_flush();
    flush();
    set_time_limit(90);


   /*
    $q = "SELECT * FROM PUBLICATIONS WHERE pubid<>$pubid";
    if($match['title']=='exact'){
      $q .= " AND title='".mysql_real_escape_string($p['title'])."'";
    }elseif($match['title']!='any' && trim($p['title'])!=''){
      $q .= " AND title<>''";
    }
    if($match['year']=='exact'){
      $q .= " AND year=".intval($p['year'])."";
    }elseif($match['year']=='almost'){
      $q .= " AND (year=9999 OR (year>".(intval($p['year'])-2)." AND year<".(intval($p['year'])+2)."))";
    }elseif($match['year']=='approx'){
      $q .= " AND (year=9999 OR (year>".(intval($p['year'])-4)." AND year<".(intval($p['year'])+4)."))";
    }
    if($match['authorlist']=='exact'){
      $q .= " AND authorlist='".mysql_real_escape_string($p['authorlist'])."'";
    }elseif($match['authorlist']!='any' && trim($p['authorlist'])!=''){
      $q .= " AND authorlist<>''";
    }
    if($match['journal']=='exact'){
      $q .= " AND journal='".mysql_real_escape_string($p['journal'])."'";
    }elseif($match['journal']!='any' && trim($p['journal'])!=''){
      $q .= " AND journal<>''";
    }
    if($match['pubtype']=='exact'){
      $q .= " AND reftype='".mysql_real_escape_string($p['reftype'])."'";
    }
    
    $res = mysql_query($q, connectpubsdb());
    if($config['debug']){
//      echo "<p>$q</p>";
//	 echo "<p>Records retrieved by SQL: ".mysql_num_rows($res)."</p>";
    }
    $numfound = 0;
    ?>
    <ul><?php
    while($row = mysql_fetch_assoc($res)){

      // Apply the approximate match tests
      if($match['title']=='almost' && !string_compare_almostexact($p['title'], $row['title'])) continue;
      if($match['title']=='approx' && !string_compare_approximate($p['title'], $row['title'])) continue;
      if($match['authorlist']=='almost' && !string_compare_almostexact($p['authorlist'], $row['authorlist'])) continue;
      if($match['authorlist']=='approx' && !string_compare_approximate($p['authorlist'], $row['authorlist'])) continue;
      if($match['journal']=='almost' && !string_compare_almostexact($p['journal'], $row['journal'])) continue;
      if($match['journal']=='approx' && !string_compare_approximate($p['journal'], $row['journal'])) continue;
      if($match['year']=='almost' && abs($p['year']-$row['year'])>1) continue;
      if($match['year']=='approx' && abs($p['year']-$row['year'])>3) continue;

      $numfound++;
	 echo "\n  <li><label><input type='checkbox' name='pubid[]' value='$row[pubid]' />".formataref2($row, '', '')."<br /><em>(ID number: $row[pubid])</em></label></li>";
    }
*/
    $results = singleDuplicateQuery($p, $match, $pubid);
    $numfound = sizeof($results);
    
    if($numfound == 0){
      ?><li>No matches found.</li><?php
    }else{
	 foreach($results as $row)
	   echo "\n  <li><label><input type='checkbox' name='pubid[]' value='$row[pubid]' />".formataref2($row, '', '')."<br /><em>(ID number: $row[pubid])</em></label></li>";
    }
    ?></ul>
    <script type="text/javascript">
    if(document.getElementById){
      document.getElementById('searchingpleasewait').style.display='none';
    }
    </script>
    Tick the boxes for the records which represent the SAME publication entry, then click the button: <input type="submit" value="Advanced merge of ALL ticked records" />
    <?php
  }else{
    ?>
    <div id="searchingpleasewait" style="padding: 20px 100px; margin: 2px 60px; background-color: white; border: 10px solid rgb(250,250,250); font-size: x-large; font-style: italic; color: silver;">
    Search results will appear here when you submit the form.
    </div>
    <?php
  }
  ?></form><?php
  
  
  
}else{
  ?><p>Unable to retrieve selected record.</p><?php
}




function textmatchdropdown($default='exact'){
  return "
     <option value='exact'".($default=='exact'?' selected="selected"':'').">matches exactly</option>
     <option value='almost'".($default=='almost'?' selected="selected"':'').">matches almost exactly</option>
     <option value='approx'".($default=='approx'?' selected="selected"':'').">matches approximately</option>
     <option value='any'".($default=='any'?' selected="selected"':'').">can be anything</option>
  ";
}



?>