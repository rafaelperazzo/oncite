<?php

// Script to go through the ISSN numbers of journals/journal articles 
// and spread them through the system where possible
// $action = 'issn';

//$subaction could be 'inconsistent' or 'blank' - to handle inconsistent ISSNs, or missing ISSNs

$subaction = $_REQUEST['subaction'];

if(is_array($_REQUEST['coerceissn'])){
  $totalaffected = 0;
  echo "<p>Updating - please wait...</p>";
  foreach($_REQUEST['coerceissn'] as $rawjrnl=>$newissn){
    if(strlen($newissn)==0) continue;
    set_time_limit(20);
    $journal = rawurldecode($rawjrnl);
//	$newissn = preg_replace('/^(\d{4})\D*(\d{3}[\dX])/', "$1-$2", $newissn);
	$newissn = fixIssnIsbn($newissn);
	$q = "UPDATE PUBLICATIONS SET issnisbn='".mysql_real_escape_string($newissn)."' WHERE journal='".mysql_real_escape_string($journal)."'";
	$res = mysql_query($q, connectpubsdb());
	$totalaffected += mysql_affected_rows();
    // echo "$q<br />";
  }
  echo "<hr /><p>Thank you - $totalaffected records have been updated to make sure the ISSN is correct.</p>";
}


$q = "SELECT COUNT(*) AS cnt, journal, issnisbn FROM PUBLICATIONS WHERE journal<>'' "
         . "GROUP BY journal, issnisbn ORDER BY journal";

$res = mysql_query($q, connectpubsdb());

$j = array(); // Will hold the big list

while($row = mysql_fetch_assoc($res)){
  $newissn = preg_replace('/^(\d{4})\D*(\d{3}[\dXx])/', "$1-$2", $row['issnisbn']);
  $j[strtoupper($row['journal'])][$newissn] += $row['cnt'];
}

?>
<form method="post" action="./">
<?php
if($subaction=='blank' || $subaction=='blankonly'){
	// Show the ones where no ISSN has EVER been entered...
	?>
	<h2>Journals with no ISSN references at all</h2><?php
	foreach($j as $journal=>$issns){
	  if(sizeof($issns)!=1 || !isset($issns['']))
		continue;

	  echo "\n<h3>$journal</h3>";
	  echo "<label>Coerce all ISSN fields for this record to: <input type='text' name='coerceissn[".rawurlencode($journal)."]' value='' size='9' maxlength='9' /></label>";
	}
}elseif($subaction=='issnfromabbrev'){
  ?>
<input type="hidden" name="subaction" value="issnfromabbrev" />
<input type="hidden" name="ok" value="ok" />
  <?php
    if($_REQUEST['ok']!='ok'){
	  $q = "SELECT journalabbrev, IMPACTFACTORS.issnisbn AS issn FROM PUBLICATIONS LEFT JOIN IMPACTFACTORS ON "
	      . " (journalabbrev=jabbrev) WHERE journalabbrev<>'' AND PUBLICATIONS.issnisbn='' AND IMPACTFACTORS.issnisbn<>''";
	  $res = mysql_query($q, connectpubsdb());
	  if(!$res) echo mysql_error();
	  else{
	    echo "<p>" . mysql_num_rows($res) . " new matches will be made. Please review these, and then press the Submit button to commit them.</p>";
	    while($row = mysql_fetch_assoc($res)){
	      echo "<p>$row[journalabbrev] : $row[issn]</p>";
	    }
	  }
	}
	else{
		$q = "UPDATE PUBLICATIONS LEFT JOIN IMPACTFACTORS ON  (journalabbrev=jabbrev)
			SET PUBLICATIONS.issnisbn=IMPACTFACTORS.issnisbn
			WHERE journalabbrev<>'' AND PUBLICATIONS.issnisbn='' AND IMPACTFACTORS.issnisbn<>''";
	  $res = mysql_query($q, connectpubsdb());
	  if(!$res) echo mysql_error();
	  else
	    echo "<p class='highlypaddedmessagebox'>Update successfully carried out, on " . mysql_affected_rows() . " rows.</p>";
	}
	


}elseif($subaction=='fixabbrevs'){
 	$q = "UPDATE PUBLICATIONS SET journalabbrev=UPPER(REPLACE(journalabbrev, '.', ' '))";
	mysql_query($q, connectpubsdb());
	echo "<p>Done.</p>";
}elseif($subaction=='fixissns'){

  echo "<ul>";
  echo "\n<li>Remove 'ISSN ' if that has been typed in</li>";
  mysql_query("UPDATE PUBLICATIONS SET issnisbn = SUBSTRING(issnisbn, 6) "
        . " WHERE (reftype='JOUR' OR reftype='JFUL') AND issnisbn LIKE 'ISSN '", connectpubsdb());
  echo "\n<li>Convert 12345678 to 1234-5678</li>";
  mysql_query("UPDATE PUBLICATIONS SET issnisbn = CONCAT(SUBSTRING(issnisbn, 1, 4), '-', SUBSTRING(issnisbn, 5, 4)) "
        . " WHERE (reftype='JOUR' OR reftype='JFUL') AND LENGTH(issnisbn)=8 AND issnisbn NOT LIKE '%-%'", connectpubsdb());
  echo "\n<li>Make sure X is upper-case</li>";
  mysql_query("UPDATE PUBLICATIONS SET issnisbn = REPLACE(issnisbn, 'x', 'X') "
        . " WHERE (reftype='JOUR' OR reftype='JFUL') AND issnisbn LIKE '%x%'", connectpubsdb());



	echo "</ul><p>Done.</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>";
}elseif ($subaction != 'donothing'){ // $subaction=='inconsistent' or anything else
	// Show the ones where there is inconsistent ISSN data available
	
	$issnjifs = allIssnsWithImpactFactors();
	?>
	<h2>Journals with inconsistent ISSN data entered</h2><?php
	foreach($j as $journal=>$issns){
	  if(sizeof($issns)==1)
		continue;
	  $onlyonetochoose = (sizeof($issns)==2 && isset($issns['']));
	  echo "\n<h3>$journal</h3>";
	  foreach($issns as $issn=>$cnt){
		echo "\n" . ($issn==''?"<i><strong>No ISSN specified</strong></i>: $cnt occurrences":"<strong><a href='http://www.google.co.uk/search?q=issn+$issn' target='_blank'>$issn</a></strong> : $cnt occurrences");
		if(isset($issnjifs[$issn]))
		  echo " <strong>(This ISSN has an impact factor)</strong>";
		echo "<br />";
		if($onlyonetochoose && $issn!='')
		  echo "<label><input type='checkbox' name='coerceissn[".rawurlencode($journal)."]' value='$issn' />Coerce all ISSN fields for this record to $issn</label>";
	  }
	  if(!$onlyonetochoose)
		echo "<label>Coerce all ISSN fields for this record to: <input type='text' name='coerceissn[".rawurlencode($journal)."]' value='' size='9' maxlength='9' /></label>";
	}
}
?>
<input type="hidden" name="action" value="issn" />

<?php if ($subaction=='blankonly')
   echo '<input type="hidden" name="subaction" value="donothing" />';
?>
<?php if ($subaction != 'donothing')
   echo '<input type="submit" value="Submit" />';
?>

</form>
<?php
?>
