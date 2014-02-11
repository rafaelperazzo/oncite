<script type="text/javascript">
function checksearchform()
{
  if(!document.getElementById) return true;
  var f = document.getElementById("pubsearchformform");
  
  var deptsel = false;
  if(f.depts)
    for(var i = 0; i != f.depts.options.length; i++)
    {
      if(f.depts.options[i].selected)
	    deptsel = true;
    }
  else
    deptsel = true; // This extra complication is because for single-dept faculties, the dept field doesn't appear

  var yearsel = false;
  for(var i = 0; i != f.years.options.length; i++)
  {
    if(f.years.options[i].selected)
	  yearsel = true;
  }

  if(deptsel || yearsel || f.wordsearch.value!="" || f.namesearch.value!="")
    return true;
  else
  {
    alert("Please select at least one option \n(dept/year/text/author)");
	return false;
  }
}
</script>

 <?php 
 
 require_once(dirname(dirname(__FILE__)) . '/manage/config.inc.php');

if($config['downformaintenance'])
{
  ?>
    <div style="padding: 100px;">
	<h2>We're doing a little maintenance....</h2>
	<p>This information is currently unavailable, as our web database is being 
      upgraded. Please bear with us!</p>
	<p>This outage is not expected to last more than two hours. There is no need to contact 
	  the webmaster, as normal service will resume shortly.</p>
	</div>
  <?php
  exit();
}

 $dbcon = @mysql_connect($config['db_addr'], $config['db_user'], $config['db_pass']);
 if(!$dbcon)
 {
   ?><p class="highlypaddedmessagebox">Unable to connect to the database at present. 
   Please try reloading this page again in a few minutes,
   and if the error persists, please contact the 
   <a href="mailto:<?php echo $config['webmasteremail'] ?>">website administrator</a>. 
   Thank you.</p><?php
   echo "\n\n<!-- " . mysql_error() . " -->\n\n";
   return;
 }
 mysql_select_db($config['db_db'], $dbcon);

 
 // Get the list of departments. If "facultyid" is set then we only get one faculty's depts. Otherwise we get them all.
 if(!isset($facultyid))
   $facultyid = $_REQUEST['facultyid'];
 if(strlen($facultyid)>0)
 {
   $thedepts = getdeptsinfaculty($facultyid);
 }
 else
   $thedepts = getdepts();	
 ?>


<form onsubmit="return checksearchform()" action="<?php 
		if(!empty($formtarget))
		  echo htmlspecialchars($formtarget);
		elseif(!empty($_REQUEST['formtarget']))
		  echo htmlspecialchars($_REQUEST['formtarget']);
        else
		  echo preg_replace('|index\.php$|', '', $_SERVER['REDIRECT_URL']);
		 ?>" method="get" id="pubsearchformform" class="pubsearchformform">
<table border="0" cellspacing="5" cellpadding="0">
	<tr align="left" valign="top">
    <?php
    //RAFAEL
    if(!sizeof($thedepts)!=1)
    { //print("HAHAHA");
      ?>
		<th>Department</th>
		<td rowspan="5" style="border-left: 1px dotted gray;"><img src="/images/spacer.gif" width="1" height="1" alt="" /></td>
    <?php
    } // End of "is-there-more-than-one-dept?"
    ?>
  <th>Year</th>
  <td rowspan="4" style="border-left: 1px dotted gray;"><img src="/images/spacer.gif" width="1" height="1" alt="" /></td>
    <th>Advanced preferences</th>
  </tr>
  <tr align="left" valign="top">
    <?php //RAFAEL
    if(!sizeof($thedepts)!=1)
    {
      ?>
    <td rowspan="4">
      <select name="depts[]" size="<?php echo min(20, sizeof($thedepts));  ?>" multiple="multiple" id="depts">
	  <?php
	  foreach($thedepts as $k=>$v)
	  {
	    ?>
        <option value="<?php echo $k ?>"><?php echo $v['NAME'] ?></option>
        <?php
	  }
	  ?>
    </select></td>
    <?php
    } // End of "is-there-more-than-one-dept?"
    ?>
    <td><select name="years[]" size="5" multiple="multiple" id="years">
<?php
$thisyear = intval(date('Y'));
do
  echo "\n    <option>$thisyear</option>";
while ($thisyear-- > 1997);
?>
    </select></td>
    <td rowspan="3">
	  <p><label>Results format:<br />      
         <select name="format">
          <option value="normal" selected="selected" />Normal</option>
          <option value="ris" />RIS (for bibliographic databases)</option>
          <option value="bibtex" />BibTeX</option>
          <option value="oaipmh" />XML (OAI-PMH 2.0)</option>
          <option value="csv" />CSV (for spreadsheets)</option>
	    </select>
	   </label>
        </p>
<!--	  <p><label for="format">Results format:</label><br />      
          <input name="format" type="radio" value="normal" checked="checked" />
        Normal<br />
        <input type="radio" name="format" value="ris" />
        RIS (for bibliographic databases)<br />
        <input type="radio" name="format" value="csv" />
        CSV (for spreadsheets)
        </p>
-->
	  <p>
	    <label for="group">Group results: <span class="greyedout">(only applicable for normal <!-- or print -->
          format)</span></label>
	    <br />
	      <input name="group" type="radio" value="year" />
	      by year<br />
	      <input name="group" type="radio" value="type" />
	      by type<br />
	      <input name="group" type="radio" value="periodical" />
	      by journal<br />
	      <input name="group" type="radio" value="author" />
	      by author	
	      </p>	  <p>
	        <!-- <label><input type="checkbox" name="eprintsonly" value="y" /> -->
	        <!-- Eprints only</label> <em>(<a href="<?php echo $config['eprints_aboutpage'] ?>">What are eprints?</a>)</em></p> -->
    </td>
    </tr>
  <tr align="left" valign="top">
    <th>Search for word or word fragment(s)</th>
    </tr>
  <tr align="left" valign="top">
    <td><label>Text search:<br /><input name="wordsearch" type="text" id="wordsearch" /></label>
        <br />
  <label>Author search:<br /><input name="namesearch" type="text" id="namesearch" /></label>
    </td>
    </tr>
   <tr align="left" valign="top">
     <td colspan="3" align="center" valign="middle">
	          <p><span class="pubsearchformgobutton">
			    <input type="submit" value="&nbsp;&nbsp;&nbsp;Search&nbsp;&nbsp;&nbsp;" /></span></p>
 <p><input name="Reset" type="reset" value="Clear&nbsp;form" /></p>
       <input name="action" type="hidden" id="action" value="search" />
<?php

 if(strlen($facultyid)>0)
   echo "\n<input type='hidden' name='defaultdepts' value='" . htmlspecialchars(implode(',', array_keys($thedepts))) . "' />\n";

?>
<p>&nbsp;</p>
<p>&nbsp; </p>
			 </td>
     </tr>
</table>
</form>
<?php
// Here are the functions - they have literally just been copied from common.php
// The reason for duplication is to make searchform.inc.php as efficient as possible
//   since it is likely to be one of the most heavily-hit pages

$universaldeptlist = array();
function getdepts()
{
  global $universaldeptlist, $dbcon;
  if(sizeof($universaldeptlist)>1) return $universaldeptlist;
  $res = mysql_query("SELECT * FROM DEPTS ORDER BY NAME", $dbcon);

  while($row = mysql_fetch_assoc($res))
    $universaldeptlist[$row['DEPTID']] = $row;
  return $universaldeptlist;
}

function getdeptsinfaculty($facultyid)
{
  global $dbcon;
  $id = mysql_real_escape_string($facultyid);
  $res = mysql_query("SELECT * FROM DEPTS WHERE FACULTYID='$id'", $dbcon);
  while($row = mysql_fetch_assoc($res))
    $ret[$row['DEPTID']] = $row;
  return $ret;
}

?>
