<?php

/*

displayqueryterms - a simple script to write out what someone's search terms were.

*/

require_once(dirname(dirname(__FILE__)) . '/manage/config.inc.php');

function displayqueryterms($q)
{
  global $config;
	$depts = $q['depts']; // Dept'l codes
	$years = $q['years']; // NB 9999 = in press
	$wordsearch = rawurldecode($q['wordsearch']);
	$namesearch = rawurldecode($q['namesearch']);
	
	$querystrings = array();
	if(is_array($depts))
	{
	  if(sizeof($depts)>6)
	  {
		$querystrings[] = "various departments";
	  }
		elseif(sizeof($depts)>0)
		{
		  $tempdbcon = mysql_connect($config['db_addr'],$config['db_user'],$config['db_pass']);
		  @mysql_select_db($config['db_db']);
		  foreach($depts as $dk=>$dy)
		    $depts[$dk]=mysql_real_escape_string($dy);
		  $q = "SELECT name FROM DEPTS WHERE deptid IN ('" 
				  . implode("','", $depts) 
				  . "')";
		  $res = mysql_query($q, $tempdbcon);
		  $deptsnames = array();
		  while($row = mysql_fetch_assoc($res))
			$deptsnames[] = $row['name'];
		  @mysql_close($tempdbcon);
		  $querystrings[] = //((sizeof($deptsnames)>1)?'Departments ':'') . 
		                      implode(', ', $deptsnames);
		}
	}
	
	if(is_array($years))
	{
	  if(sizeof($years)>6)
	  {
		$querystrings[] = "various years";
	  }
	  elseif(sizeof($years)>0)
	  {
		$yearsputput = array();
		foreach($years as $y)
		  if($y=='9999')
			$yearsoutput[] = $config['inpressstring'];
		  else
			$yearsoutput[] = $y;
		$querystrings[] = (sizeof($yearsoutput)>1?'Years ':'') . implode(', ', $yearsoutput);
	  }
	}


    if(strlen($wordsearch)>0)
	  $querystrings[] = "<em>" . htmlspecialchars(stripslashes($wordsearch)) . "</em> in text";

    if(strlen($namesearch)>0)
	  $querystrings[] = "<em>" . htmlspecialchars(stripslashes($namesearch)) . "</em> in authors";

    if($q['eprintsonly']=='y')
      $querystrings[] = "<a href='$config[eprints_publicurl]'>Eprints</a> records only";

    return implode('; ', $querystrings);

}
?>