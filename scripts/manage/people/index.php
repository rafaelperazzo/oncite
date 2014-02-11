<?php

require_once(dirname(dirname(__FILE__)) . '/common.php');
// include_once('/nfs/rcs/www/webdocs/lifesciences-faculty/publications/manage/common.php');

if(!(strlen($action)>0))
  $action = $_REQUEST['action'];
if(!(strlen($userid)>0))
  $userid = mysql_real_escape_string($_REQUEST['userid']);

if($action=='lastnamesearch')
{
  if(!(strlen($lastname)>0))
    $lastname = $_REQUEST['lastname'];

  $finds = lastnamesearch($lastname, $theinitial, $userid);

//  $lastname = explode('|', stripslashes($lastname));
  $pregmatch = '/' . preg_replace('/\b/','\s*', $lastname) . '/i';
  echo "<p>" . sizeof($finds) . " unmatched publications found with an author whose name is similar to $lastname,$theinitial</p>"; //<p>Query: $q.</p>";

  if(sizeof($finds)>0)
  {

    $maxatfirst = 4;


    // This bit is for the "mark all of them as mine" button
    if(sizeof($finds)>$maxatfirst){
       ?>
	  <script>
	  function tickAllYesses(){
	    el = document.getElementsByName('associate[]');
	   // alert("Getting all associate[] elements. Number found: " + el.length);
	    for(i=0; i<el.length; i++)
	      el[i].checked = true;
	  }
	  </script>
	  <p><label>To mark <em><strong>all</strong></em> these suggested matches as yours: <input type="button" value="Select all" onclick="tickAllYesses()"/></label></p>
	  <?php
    }



  echo "<form action=\"./\"><table border=\"1\">";
  foreach($finds as $k=>$v)
  {
    if((sizeof($finds)>($maxatfirst*2)) && ($k==$maxatfirst))
	{
	  $tablehasbeensplit = true;
	  ?>
	  </table>
	  <p id="linktoshowremainder" align="right"><em><a href="javascript:showremainder()" style="color: rgb(153,153,204);">(Only
	  the first <?php echo $maxatfirst ?> are shown. To see the remaining <?php echo (sizeof($finds)-$maxatfirst) ?> 
	  suggested matches click here)</a></em></p>
	  <div id="remainingsuggestionstable">
	  <table border="1">
	  <?php
	}
    echo trOnePotentialAssociation($v, $lastname, $userid, $pregmatch);
  }
     ?></table><?php
    if($tablehasbeensplit)
    {
       ?>
	   </div>
	  <script type="text/javascript">
	  if(document.getElementById)
	    document.getElementById("remainingsuggestionstable").style.display='none';
	  function showremainder()
	  {
	    if(document.getElementById)
		{
	      document.getElementById("remainingsuggestionstable").style.display='block';
	      document.getElementById("linktoshowremainder").style.display='none';
		}
	  }
	  </script>
	   <?php
    }
  echo ""
     . "<input type=\"hidden\" name=\"action\" value=\"associatepubs\" />"
     . "<input type=\"hidden\" name=\"userid\" value=\"$userid\" />"
     . "<input type=\"hidden\" name=\"dummy\" value=\"" . time() . "\" />"
     . "<input type=\"submit\" value=\"Store ticked associations\" />"
	 . "</form>";
  } // End of "did we find any?"
}
else if($action=='associatepubs')
{
  // echo "<p>I'd associate some publications here:</p>";
  $successes  = 0;
  $failures   = 0;
  $successes2 = 0;
  $failures2  = 0;
  if(is_array($_REQUEST['notmine']))
    foreach($_REQUEST['notmine'] as $v)
    {
      $res = mysql_query("INSERT INTO PUBLICATIONSNOTMINE SET pubid='" 
	        . intval($v) ."', userid='" . mysql_real_escape_string($userid) . "'", connectpubsdb());
	  if($res)
	    $successes2++;
      else
	    $failures2++;
    }
  if(is_array($_REQUEST['associate']))
  foreach($_REQUEST['associate'] as $v)
  {
    $res = mysql_query("SELECT userlist FROM PUBLICATIONS WHERE pubid='" 
	        . intval($v) ."' LIMIT 1", connectpubsdb());
    if(!$res) continue;
    $tempuserlist = mysql_fetch_assoc($res);
	// print_r($tempuserlist);
	$tempuserlist = $tempuserlist['userlist'];
	
	if(strlen($tempuserlist)>0)
	  $tempuserlist .= ',';
    $tempuserlist = str_replace(mysql_real_escape_string($userid) . ',','',$tempuserlist)
	                    . mysql_real_escape_string($userid);

    // Also make sure the user's department is stored as an association
	$qqq = "SELECT deptid FROM USERS WHERE userid='" . mysql_real_escape_string($userid) . "' LIMIT 1";
	$rrr = mysql_query($qqq, connectpubsdb());
	$rrrow = mysql_fetch_assoc($rrr);
        $mydept = mysql_real_escape_string($rrrow['deptid']);
	
/* making change here */
    $q = "UPDATE PUBLICATIONS SET userlist='$tempuserlist' "
	           . (strlen($rrrow['deptid'])>0?",
pendingdepts=CONCAT(pendingdepts,'$mydept,'),
deptlist=REPLACE(deptlist, ',$mydept,', ',')":'')
			   . "WHERE pubid='" 
	           . intval($v) ."' LIMIT 1";
    if(mysql_query($q, connectpubsdb()))
    {
          recordtransaction('storepubassocs', intval($v));
	  $successes++;
	  mysql_query("UPDATE USERS SET pubslastupdated=null WHERE userid='"
	             . mysql_real_escape_string($userid) . "' LIMIT 1", connectpubsdb());
	}
	else
	  $failures++;
  }
  if(is_array($_REQUEST['notmine']))
    echo "<p>Successfully recorded $successes2 dissociations; failed to record $failures2.</p>";
  if(is_array($_REQUEST['associate']))
    echo "<p>Successfully created $successes new associations; failed to create $failures.</p>";
  echo "<p><a href=\"./?userid=$userid&dummy=" . time() . "\">Back to personal publications pages</a>.</p>";
}
else
{

$p = getallpeople();
shuffle($p);

$q = "SELECT pubid, userlist FROM PUBLICATIONS WHERE userlist NOT LIKE ''";
$res = mysql_query($q, connectpubsdb());
$pubassocs = array();
$peopleassocs = array();
$temp = array();
while($row = mysql_fetch_assoc($res))
{
  $temp[] = $row;
  $people = explode(',',$row['userlist']);
  foreach($people as $v)
  {
    $pubassocs[] = $row['pubid'];
    $peopleassocs[] = trim($v);
  }
}
?>
<table border="1" cellspacing="0" cellpadding="5">
  <tr>
    <th scope="col">User ID</th>
    <th scope="col">Name</th>
    <th scope="col">Publications</th>
  </tr>
<?php
foreach($p as $v)
{
?>
  <tr>
    <td><?php echo $v['USERID'] ?></td>
    <td><?php echo $v['TITLE'] ?> <?php echo $v['FIRSTNAME'] ?> <b><?php echo $v['LASTNAME'] ?></b></td>
    <td><?php 
	 $count = 0;
	 foreach($peopleassocs as $k=>$vv)
	   if($vv==$v['USERID'])
	     $count++;
	 echo "$count";
     if($count)
	   echo "<br />&middot;<a href=\"./?action=viewpubs&userid=$v[USERID]\">View associated publications</a>";
     echo "<br />&middot;<a href=\"?action=lastnamesearch&lastname=" . urlencode($v['LASTNAME']) . ','
		  . substr($v['FIRSTNAME'],0,1)
	      . "&userid=$v[USERID]\">Search for un-associated publications with &quot;$v[LASTNAME],"
		  . substr($v['FIRSTNAME'],0,1)
		  . "*&quot; listed as author</a>";
	 ?></td>
  </tr>
<?php
}
?>
</table>

<?php
} // End of "action" choices



function trOnePotentialAssociation($v, $lastname, $userid, $pregmatch)
{
    return "<tr><td><i>$v[title]</i><br />by "
	  . str_replace('.,', '., ', preg_replace($pregmatch,"<strong>$lastname</strong>",$v['authorlist']))
//	  . str_replace($lastname,"<strong>$lastname[0]</strong>",$v['authorlist'])
	  . "<br />$v[year]"
	  . "<div style=\"text-align:center\">"
	  . "<input type=\"checkbox\" name=\"associate[]\" value=\"$v[pubid]\" />"
	  . " Tick this box to associate the publication with userid <i>$userid</i>"
	  . "<br />Or if the publication should not be associated with <i>$userid</i> tick this box:&nbsp;"
	  . "<input type=\"checkbox\" name=\"notmine[]\" value=\"$v[pubid]\" />"
	  . "</div>"
	  . "</td></tr>";
}
?>
