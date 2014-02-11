<style type="text/css">
th[scope="row"] {vertical-align: top; text-align: right;}
th[scope="col"] {vertical-align: bottom; text-align: left;}
</style>

<?php
require_once(dirname(__FILE__) . '/manage/common.php');

// The following script will update the stats in the database, if they're sufficiently old
require_once($config['homedir'] . 'manage/automatic-actions/updatestats-timedinterval.inc.php');

$grab = array('show','facultyid');
foreach($grab as $v)
  $$v = $_REQUEST[$v];

if($res = mysql_query("SELECT UNIX_TIMESTAMP(ts) AS uts FROM PUBLICATIONSTIMESTAMPS WHERE name='statsupdated'", connectpubsdb()))
{
  $row = mysql_fetch_assoc($res);
  $lastupdated = date('H:i', $row['uts']) . ' on ' . date('jS M Y', $row['uts']);
}
else
  $lastupdated = '[unknown - database error]';

$depts = getDepts();
$facs = getallfaculties();
$reftypes = getPubTypes();
?>
<h2>Statistics</h2>
<p>This page displays subtotals of numbers of confirmed publications stored in the publications database. The numbers 
displayed on this page were last updated at <?php echo $lastupdated ?>.</p>
<p>The subtotals presented on this page <strong>should not be misinterpreted</strong> as any kind of indicator of relative &quot;quality&quot; of departments. Many factors affect the number of publications put out by a department, including the number of research staff and the different publishing conventions of different academic disciplines. Although every publication counts as 1 &quot;unit&quot; in these subtotals, they do not necessarily represent a standard unit of academic effort!</p>
<p>Please also bear in mind that not all publications for the current year may yet have been indexed. </p>
<h3>Department-by-department statistics </h3>
<form name="form1" method="get" action="<?php echo $_SERVER['PHP_SELF'] ?>">
  <p>Display 
    <select name="show">
      <option value="deptsperyear"<?php if($show=='deptsperyear') echo ' selected="selected"' ?>>departments' publications for each year</option>
      <option value="typesperdept"<?php if($show=='typesperdept') echo ' selected="selected"' ?>>types of publication produced by each department</option>
 <!--     <option value="typesperyear"<?php if($show=='typesperyear') echo ' selected="selected"' ?>>types of publication produced each year</option> -->
    </select>
for 
<select name="facultyid">
      <option value="0">all faculties</option>
<?php 
foreach($facs as $k=>$fac)
  echo "\n         <option value='$k'" .  ($k==$facultyid ? ' selected="selected"':'') . ">$fac[TITLE]</option>";
?>
</select> 
<input type="submit" value="Go">
</p>
</form>
<?php

switch($show)
{
  case 'deptsperyear':
    $q = "SELECT SUM(count) as subtot, DEPTS.deptid, year, DEPTS.FACULTYID FROM STATSSUBTOTALS LEFT JOIN DEPTS USING (deptid) "
	       . ($facultyid>0 ? " WHERE FACULTYID='" . mysql_real_escape_string($facultyid) . "' " : '')
		   . " GROUP BY deptid, year";
	$res = mysql_query($q, connectpubsdb());
	if($res)
	{
	  $stats = array();	  $rows = array();	  $cols = array();	  $rowsubs = array();	  $colsubs = array();
	  while($row = mysql_fetch_assoc($res))
	  {
	    if($row['year']==0) $row['year']=9999; // Fix dodgy entries...

	    $stats[$row['deptid']][$row['year']] = $row['subtot'];
		$rows[$row['deptid']] = isset($depts[$row['deptid']]) ? $depts[$row['deptid']]['NAME'] : "Unknown department $row[deptid]";
		$cols[$row['year']] = ($row['year']==9999) ? $config['inpressstring'] : $row['year'];
		$rowsubs[$row['deptid']] += $row['subtot'];
		$colsubs[$row['year']] += $row['subtot'];
	  }
      natsort($rows);
	  natsort($cols);
	  writeStatsTable($stats, $rows, $cols, $rowsubs, $colsubs, 'depts[]', 'years[]');
	}
	else
	  echo "<p>Database error - sorry. Please contact the webmaster if this persists. ". mysql_error() . "</p>";
    break;
  case 'typesperdept':
    $q = "SELECT SUM(count) as subtot, DEPTS.deptid, reftype, DEPTS.FACULTYID FROM STATSSUBTOTALS LEFT JOIN DEPTS USING (deptid) "
	       . ($facultyid>0 ? " WHERE FACULTYID='" . mysql_real_escape_string($facultyid) . "' " : '')
		   . " GROUP BY deptid, reftype";
	$res = mysql_query($q, connectpubsdb());
	if($res)
	{
	  $stats = array();	  $rows = array();	  $cols = array();	  $rowsubs = array();	  $colsubs = array();
	  while($row = mysql_fetch_assoc($res))
	  {
	//    if($row['year']==0) $row['year']=9999; // Fix dodgy entries...

	    $stats[$row['deptid']][$row['reftype']] = $row['subtot'];
		$rows[$row['deptid']] = isset($depts[$row['deptid']]) ? $depts[$row['deptid']]['NAME'] : "Unknown department $row[deptid]";
		$cols[$row['reftype']] = isset($reftypes[$row['reftype']]) ? $reftypes[$row['reftype']] : "Unknown type: $row[reftype]";
		$rowsubs[$row['deptid']] += $row['subtot'];
		$colsubs[$row['reftype']] += $row['subtot'];
	  }
      natsort($rows);
	  natsort($cols);
	  writeStatsTable($stats, $rows, $cols, $rowsubs, $colsubs, 'depts[]', 'pubtype');
	}
	else
	  echo "<p>Database error - sorry. Please contact the webmaster if this persists. ". mysql_error() . "</p>";
    break;
  case 'typesperyear':
    $q = "SELECT SUM(count) as subtot, year, reftype, DEPTS.FACULTYID FROM STATSSUBTOTALS LEFT JOIN DEPTS USING (deptid) "
	       . ($facultyid>0 ? " WHERE FACULTYID='" . mysql_real_escape_string($facultyid) . "' " : '')
		   . " GROUP BY year, reftype";
	$res = mysql_query($q, connectpubsdb());
	if($res)
	{
	  $stats = array();	  $rows = array();	  $cols = array();	  $rowsubs = array();	  $colsubs = array();
	  while($row = mysql_fetch_assoc($res))
	  {
	    if($row['year']==0) $row['year']=9999; // Fix dodgy entries...

	    $stats[$row['year']][$row['reftype']] = $row['subtot'];
		$rows[$row['year']] = ($row['year']==9999) ? $config['inpressstring'] : $row['year'];
		$cols[$row['reftype']] = isset($reftypes[$row['reftype']]) ? $reftypes[$row['reftype']] : "Unknown type: $row[reftype]";
		$rowsubs[$row['year']] += $row['subtot'];
		$colsubs[$row['reftype']] += $row['subtot'];
	  }
      natsort($rows);
	  natsort($cols);
	  
	  // We also need to be able to restrict by faculty, since the department is not a parameter here.
	  $queryextra = '';
	  if($facultyid!=0)
	  {
	    $subsetdepts = getdeptsinfaculty($facultyid);
		foreach($subsetdepts as $dk=>$dv)
		  $queryextra .= '&depts[]=' . htmlspecialchars($dk);
	  }
	  writeStatsTable($stats, $rows, $cols, $rowsubs, $colsubs, 'years[]', 'pubtype', $queryextra);
	}
	else
	  echo "<p>Database error - sorry. Please contact the webmaster if this persists. ". mysql_error() . "</p>";
    break;
  default:
    break;
}


function writeStatsTable($stats, $rows, $cols, $rowsubs, $colsubs, $queryrowname, $querycolname, $queryextra='')
{
  global $config;
	  echo "\n<table border='1' cellspacing='0' class='myopiastatstable'><tr><td></td>";
      foreach($cols as $colk => $col)
        echo "\n    <th scope='col'>" . $col . "</th>";
	  echo "<th scope='col'>Subtotals</th></tr>";
	  foreach($rows as $rowk => $row)
	  {
		echo "\n  <tr>\n    <th scope='row'>" . $row . "</th>";
	    foreach($cols as $colk => $col)
		{
		  echo "\n    <td>" . (isset($stats[$rowk][$colk])?
		            ("<a href='$config[scriptshomeurl]query/?$queryrowname=$rowk&$querycolname=$colk&includeinpress=y$queryextra'>" . $stats[$rowk][$colk] . "</a>")
					:'&nbsp;') . "</td>";
		}
		echo "\n  <td><strong>" . $rowsubs[$rowk] . "</strong></td>\n  </tr>";
	  }
	  echo "\n  <tr><th scope='row'>Subtotals</th>";
	  $grandtotal = 0;
      foreach($cols as $colk => $col)
	  {
        echo "\n    <td><strong>" . $colsubs[$colk] . "</strong></td>";
		$grandtotal += $colsubs[$colk];
	  }
	  echo "<td><strong>$grandtotal</strong></td></tr></table>";
}
?>

