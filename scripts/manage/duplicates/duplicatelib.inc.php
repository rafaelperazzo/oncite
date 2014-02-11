<?php

function judge_duplicateness($a, $b, $ignoreyear=false){ // Return TRUE if the entries are considered to be duplicates, FALSE otherwise
  if($a['pubid']==$b['pubid'])
    return false; // Make sure we don't accidentally think the exact same record is a duplicate of itself!

  if(   ($ignoreyear || ($a['year']==$b['year'])) // If year matches exactly
	    &&
	   compare_duplicate_titles($b['title'], $a['title'], 5) // If the title matches approximately
        &&
	  // If start-page is present in both and matches in both
	  (intval($a['startpage'])==0 || intval($b['startpage'])==0
	      || (intval($a['startpage'])==intval($b['startpage'])))
        &&
	  // If conference paper, check paper title too
	  (strlen(trim($a['U5']))==0 || strlen(trim($b['U5']))==0 
	      || compare_duplicate_titles($b['U5'], $a['U5']) )
        &&
	  // And secondary title (eg name of book, for a chapter) as well
	  (strlen(trim($a['secondarytitle']))==0 || strlen(trim($b['secondarytitle']))==0 
	      || compare_duplicate_titles($b['secondarytitle'], $a['secondarytitle']) )
    )
  {
    return true;
  }else{
    return false;
  }
}

function compare_duplicate_titles($a, $b, $limit = 10){ // Returns true if titles seem to match approximately. The lower the limit, the stricter the match.
   return levenshtein(
             trim(strip_tags(strtolower($b))),
		   trim(strip_tags(strtolower($a)))
		           )<$limit;
}

function marked_as_nonduplicate($a, $b){ // Return TRUE if the entries are specified as nonduplicates, FALSE otherwise
  $nondupres = mysql_query("SELECT * FROM PUBLICATIONSNONDUPLICATES WHERE firstid='" . 
          min($a['pubid'],$b['pubid']) 
		  . "' AND secondid='" . max($a['pubid'],$b['pubid']) 
		  . "' LIMIT 1", connectpubsdb());
  return ($nondupres && mysql_num_rows($nondupres)==1);
}


function recordIsInOneOfMyDepts($p, $mydeptcodes){
  foreach($mydeptcodes as $code)
    if((strpos($p['deptlist'], ",$code,")!==false) || (strpos($p['pendingdepts'], ",$code,")!==false))
      return true;
  return false;
}


function duplicate_merge($merge, $delete){ // $merge is the ID of the pub to keep, $delete is the ID of the pub to remove but save some data from
  global $config;
  $merge = intval($merge);
  $delete = intval($delete);

  $res = mysql_query("SELECT userlist, deptlist, pendingdepts, keywords, oaiid, url, abstract, DOI FROM PUBLICATIONS WHERE pubid='$delete' LIMIT 1", connectpubsdb());
  $one = mysql_fetch_assoc($res);
  $res = mysql_query("SELECT userlist, deptlist, pendingdepts, keywords, oaiid, url, abstract, DOI FROM PUBLICATIONS WHERE pubid='$merge' LIMIT 1", connectpubsdb());
  $two = mysql_fetch_assoc($res);
  $dept1 = splitDeptListString($one['deptlist']);
  $dept2 = splitDeptListString($two['deptlist']);
  $pdept1 = splitDeptListString($one['pendingdepts']);
  $pdept2 = splitDeptListString($two['pendingdepts']);
  $user1 = explode(',',trim($one['userlist']));
  $user2 = explode(',',trim($two['userlist']));
  $kw1 = explode(',',trim($one['keywords']));
  $kw2 = explode(',',trim($two['keywords']));
  $newoaiid = mysql_real_escape_string(strlen($two['oaiid'])>0 ? $two['oaiid'] : $one['oaiid']);
  $newdoi = mysql_real_escape_string(strlen($two['DOI'])>0 ? $two['DOI'] : $one['DOI']);
  $deptlist = mysql_real_escape_string(mergeDeptListString(array_merge($dept1, $dept2)));
  $pendingdepts = mysql_real_escape_string(mergeDeptListString(array_diff(array_merge($pdept1, $pdept2), $dept1, $dept2)));
  $userlist = mysql_real_escape_string(implode(',',array_unique(array_merge($user1, $user2))));
  $newurl = mysql_real_escape_string(strlen($two['url'])>0 ? $two['url'] : $one['url']);
  $newabstract = mysql_real_escape_string(strlen($two['abstract'])>0 ? $two['abstract'] : $one['abstract']);
  $kw = mysql_real_escape_string(implode(',',array_unique(array_merge($kw1, $kw2))));
  $mergeq = "UPDATE PUBLICATIONS SET userlist='$userlist', deptlist='$deptlist', "
  							. "pendingdepts='$pendingdepts', "
							. "keywords='$kw', "
  							. "url='$newurl', "
  							. "abstract='$newabstract', "
  							. "DOI='$newdoi', "
							. "oaiid='$newoaiid' WHERE pubid='$merge' LIMIT 1";
  $res = mysql_query($mergeq, connectpubsdb());

  // Must also attempt to update the associations in the RAEMYPUBS table - which might not exist, by the way!
  mysql_query("UPDATE RAEMYPUBS SET pubid=".intval($merge)." WHERE pubid=".intval($delete), connectpubsdb());
  mysql_query("UPDATE PUBLICATIONSNOTMINE SET pubid=".intval($merge)." WHERE pubid=".intval($delete), connectpubsdb());

//  if($config['debug'])
//    echo "<p>MySQL merge query: " . $mergeq . "</p>";

  if($res)
    $res2 = mysql_query("DELETE FROM PUBLICATIONS WHERE pubid='$delete' LIMIT 1", connectpubsdb());
  elseif($config['debug'])
    echo "<p>MySQL error message: " . mysql_error() . "</p>";
  $res &= $res2;

  // Record a note of the merge so that issues can be traced
  recordtransaction("mergefrom:$delete", $merge);
  recordtransaction("mergeto:$merge", $delete);

  return $res;
}

function compactdoubleentrytable($e, $f, $dboffset=0)
{
  global $year, $config;
  $alwaysmerge = !isGlobalAdmin($_SERVER['REMOTE_USER']); // Only global admins can genuinely delete
  $r = '<table border="1" width="100%" cellspacing="0">';
  foreach($e as $k=>$v){


   if($k=='deptlist'){
        $r .= "<tr><th>Department(s)</th><td align=\"center\">" 
		             . implode(', ', deptListToDeptNames($v)). "</td><td align=\"center\">" 
		             . implode(', ', deptListToDeptNames($f[$k])). "</td></tr>";
   }
   elseif($k=='pubid'){
        $r .= "<tr><th>Record ID</th><td align=\"center\">" 
		             . "<a href='$config[pageshomeurl]manage/?pubid=$v&action=search'>" . $v. "</a></td><td align=\"center\">" 
		             . "<a href='$config[pageshomeurl]manage/?pubid=$f[$k]&action=search'>" . $f[$k]. "</td></tr>";
   }
   elseif($k!='timestamp' && $k!='oaidatestamp' && $k!='U2' && $k!= 'approvaldate' 
   				&& $k!='chc' && $k!='approvedby' && $k!='yearmonth' && $k!='yearday'
   				&& $k!='Y2')
    if(strlen(trim($v))>0 || strlen(trim($f[$k]))>0  || $k=='keywords' || $k=='anydepts' || $k=='anypending')
      if(trim($v)==trim($f[$k]))
        $r .= "<tr><th>" . ucfirst($k) . "</th><td colspan=\"2\" align=\"center\">$v&nbsp;</td></tr>";
      else
        $r .= "<tr><th>" . ucfirst($k) . "</th><td align=\"center\">$v&nbsp;</td><td align=\"center\">" 
		             . $f[$k] . "</td></tr>";
  }
  $r .= "<tr><td>Action to take:</td><td><ul>"
       . ($alwaysmerge?'':"<li><a href=\"./?ignore=$f[pubid]&delete=$e[pubid]"
              . ($alwaysmerge?"&merge=$f[pubid]":'')
	          . "&action=duplicates&year=$year&dboffset=$dboffset\">Delete this one</a></li>")
	   . "<li><a href=\"./?ignore=$f[pubid]&merge=$f[pubid]&delete=$e[pubid]&action=duplicates&year=$year&dboffset=$dboffset\">Merge this one into the other one</a>"
	   . " <em>[<a href='javascript:alert(\"Merging will delete this record, but will copy the departmental and user associations "
	   . "to the other record so that depts/authors still have ownership of the merged record. URL, keywords, and abstract are also "
	   . "copied to the other record where appropriate.\")'>Details</a>]</em></li>"
	   . "<li><a href=\"../?action=edit&pubid=$e[pubid]\">Edit this one</a></li></ul>"
	   .  '</td><td>'
	   . "<ul>"
	   . ($alwaysmerge?'':"<li><a href=\"./?ignore=$e[pubid]&delete=$f[pubid]"
              . ($alwaysmerge?"&merge=$e[pubid]":'')
	          . "&action=duplicates&year=$year&dboffset=$dboffset\">Delete this one</a></li>")
	   . "<li><a href=\"./?ignore=$e[pubid]&merge=$e[pubid]&delete=$f[pubid]&action=duplicates&year=$year&dboffset=$dboffset\">Merge this one into the other one</a> "
	   . "<em>[<a href='javascript:alert(\"Merging will delete this record, but will copy the departmental and user associations "
	   . "to the other record so that depts/authors still have ownership of the merged record. URL, keywords, and abstract are also "
	   . "copied to the other record where appropriate.\")'>Details</a>]</em></li>"
	   . "<li><a href=\"../?action=edit&pubid=$f[pubid]\">Edit this one</a></li></ul>"
	   . "</td></tr>";

    $r .= "<tr><td>Or:</td><td colspan='2'><p>If this is <b>not</b> actually a case of duplication, "
	   . "<a href=\"./?ignore=$f[pubid]&ignore2=$e[pubid]&action=duplicates&year=$year&dboffset=$dboffset\">click here to continue looking</a>.<br />(They will be marked as not being a duplicate pair.)</p>"
       . "<p>Or to ignore this pair but to store <strong>no decision</strong> for now, "
	   . "<a href=\"./?ignore=$f[pubid]&ignore2=$e[pubid]&decision=none&action=duplicates&year=$year&dboffset=$dboffset\">click here to continue</a>.</p>"
	   . "</td></tr>";


  $r .= '</table><p>&nbsp;</p>';
  return $r;
}

function string_compare_almostexact($a, $b){ // Return true for match, false for no match
  return string_comparison_value($a, $b) < 8;
}

function string_compare_approximate($a, $b){ // Return true for match, false for no match
  return string_comparison_value($a, $b) < 12;
}

function string_comparison_value($a, $b){ // Return true for match, false for no match
	
	/*
	echo "a => ";
	print_r($a);
  	echo "<br/>";
	
	echo "b => ";
	print_r($b);
  	echo "<br/>";
	*/
  $lev = levenshtein(substr(strtolower(preg_replace('/\s+/', '', $a)), 0, 200), 
                     substr(strtolower(preg_replace('/\s+/', '', $b)), 0, 200));
//  echo "<p>$lev difference value for strings:<br />$a<br />$b</p>";
  return $lev;
}


function compare_by_similarity($b, $a){ // This will tell if $a or $b is closer to $target
  global $target_for_similarity_comparison;
  $a_t = string_comparison_value($a['title'], $target_for_similarity_comparison['title']);
  $b_t = string_comparison_value($b['title'], $target_for_similarity_comparison['title']);
  $da = $a_t - $b_t;
//  echo $a_t.''.$b_t.'<br />';
//  print_r($target_for_similarity_comparison);
//  $a_a = string_comparison_value($a['authorlist'], $target_for_similarity_comparison['authorlist']);
//  $b_a = string_comparison_value($b['authorlist'], $target_for_similarity_comparison['authorlist']);
//  $a_j = string_comparison_value($a['journal'], $target_for_similarity_comparison['journal']);
//  $b_j = string_comparison_value($b['journal'], $target_for_similarity_comparison['journal']);
//  return ($a_t + $a_a + $a_j) - ($b_t + $b_a + $b_j);
  return $da;
}

function sort_by_similarity($target, &$list){ // Will sort according to similarity to the target publication
  global $target_for_similarity_comparison;
  $target_for_similarity_comparison = $target;
  usort($list, 'compare_by_similarity');
}


function singleDuplicateQuery($p, $match, $pubid=0) { // Used by the "single duplicate" screen - supply one pub's data and the search criteria and it returns an array of matches
  global $config;


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
    // echo "SINGLE DUPLICATE QUERY -> <p>" . $q . "</p>";
    $res = mysql_query($q, connectpubsdb());
    if($config['debug']){
      echo "<p>$q</p>";
//	 echo "<p>Records retrieved by SQL: ".mysql_num_rows($res)."</p>";
    }
    $results = array();
   
	while($row = mysql_fetch_assoc($res)){
    
	/*
	echo "<br/>";
	print_r($row);
	echo "<br/><br/>";
	*/
      // Apply the approximate match tests, more "subtle" than the database-level matching
	  // Check for matches between titles and secondary titles (added by FK - 14/06/2007)
      if($match['title']=='almost' && (!string_compare_almostexact($p['title'], $row['title'])) && (!string_compare_almostexact($p['title'], $row['secondarytitle'])) && (!string_compare_almostexact($p['secondarytitle'], $row['title'])))
	  	{ continue; }
	  
      if($match['title']=='approx' && !string_compare_approximate($p['title'], $row['title'])) { continue; }
      if($match['authorlist']=='almost' && !string_compare_almostexact($p['authorlist'], $row['authorlist'])) { continue; }
      if($match['authorlist']=='approx' && !string_compare_approximate($p['authorlist'], $row['authorlist'])) { continue; }
      if($match['journal']=='almost' && !string_compare_almostexact($p['journal'], $row['journal'])) { continue; }
      if($match['journal']=='approx' && !string_compare_approximate($p['journal'], $row['journal'])) { continue; }
      if($match['year']=='almost' && abs($p['year']-$row['year'])>1) { continue; }
      if($match['year']=='approx' && abs($p['year']-$row['year'])>3) { continue; }

	  
      $results[] = $row;
    }
    return $results;
}

?>
