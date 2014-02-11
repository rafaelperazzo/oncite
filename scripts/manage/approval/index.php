<?php

require_once(dirname(dirname(__FILE__)) . '/config.inc.php');


include_once($config['homedir'] . 'manage/common.php');
$showunconfirmedentryflag = 'n';
include_once($config['homedir'] . 'query/formataref.php');

?><p style='text-align:right;'>[<a href="<?php echo $config['pageshomeurl'] ?>manage">Back to administration homepage</a>]</p>
<!-- <h2>Publications submitted for approval</h2> -->
<?php

$rejectreasons = array('Other - please contact','Other - unspecified','Unverifiable entry','Inappropriate entry');


$displaymode = 'ref';

$auth = getAuthorisations($_SERVER['REMOTE_USER']);
if($auth===false)
{
  ?>
  <p>You are not authorised to access this page. If this is a mistake then please contact the 
  webmaster for athorisation.</p>
  <?php
}
else
{
  $p = getPubsWaitingForApproval($_SERVER['REMOTE_USER']);
/*
  $q = "SELECT * FROM PUBLICATIONS WHERE approvedby='' ORDER BY title, authorlist";
  $res = mysql_query($q,connectpubsdb());
*/
  if($p===false)
  {
    ?>
	<p>Sorry, there has been a database error. (This is not your fault!) Please try again by 
	refreshing this page, and if that doesn't work please contact the 
	webmaster with details of what happened, so that the error can be corrected promptly.</p>
	<?php
	// echo "<!-- " . mysql_error() . "-->";
  }
  elseif(sizeof($p)==0)
  {
    ?>
	<p>No publications have been found in the database for you to approve. Less work for you!</p>
	<?php
	if($_REQUEST['source']=='email')
	{
	  ?>
	  <p>If you got to this page by clicking on a link in an automatic email, then you might be confused 
	    as to why you received an email when there aren't any publications waiting for you. The reason 
		for this is most likely that someone else has already logged in and submitted 
		decisions about the publications that needed approving/rejecting.</p>
	  <?php
	}
  }
  else
  {
    ?>
 <!--   <p class="simplepaddedmessagebox">N.B. Please do not reject publications simply because they may be duplicates of existing records.
    Instead, use the <a href="../duplicates/">duplicate checking process</a> to merge the duplicate reocrds together.</p> -->
    <p class="simplepaddedmessagebox"><strong>N.B.</strong> If you see entries which may be duplicates of existing records,
    use each record's &quot;Manage duplication&quot; link to merge it into the original copy.</p>
    <?php
	$singledept = singleDeptAdmin($_SERVER['REMOTE_USER']);

    if($displaymode=='tab')
	  $keys = array(
	         'year' => 'Year'
	         ,'reftype' => 'Reference type'
	         ,'authorlist' => 'Authors'
	         ,'title' => 'Title'
	         ,'journal' => 'Periodical title'
	         ,'startpage' => 'Start page'
	         ,'endpage' => 'End page'
	         ,'volume' => 'Volume'
	         ,'issue' => 'Issue'
	         ,'abstract' => 'Abstract'
	         ,'url' => 'URL'
	         ,'secondarytitle' => 'Secondary title'
	         ,'secondaryauthorlist' => 'Secondary authors (e.g. editors)'
	         ,'seriestitle' => 'Series title'
	         ,'seriesauthorlist' => 'Series authors/editors'
	         ,'date2' => 'Secondary date'
	         ,'notes' => 'Notes'
	         ,'publisher' => 'Publisher'
	         ,'issnisbn' => 'Serial no.'
	         ,'chapter' => 'Chapter'
	         ,'keywords' => 'Keywords'
	         ,'address' => 'Address'
	         ,'city' => 'City/place of publication'
	         ,'medium' => 'Medium'
	         ,'chc' => 'CHC membership'
	             );
    else
	  $keys = array('detail' => 'Detail');


    if(sizeof($p)>4)
	{
	  // "Select all"-type options
	  ?><script type="text/javascript">
	  var buttonNum = new Array(<?php echo sizeof($p); ?>);
	  var buttonNumRealSize = 0;
/*
	  var approveButtons = new Array(<?php echo sizeof($p); ?>);
	  var rejectButtons = new Array(<?php echo sizeof($p); ?>);
	  var ignoreButtons = new Array(<?php echo sizeof($p); ?>);
*/
	  <?php
	  foreach($p as $pkpk=>$v)
      {
	    if(strlen(trim($_REQUEST['action' . $v['pubid']]))!=0)
		   continue;
	    ?>
		buttonNum[buttonNumRealSize++] = '<?php echo $v['pubid'] ?>';
		<?php
	  }
	  ?>
	  function approveAll()
	  {
	    tickAll('approveButton', 'none');
	  }
	  function rejectAll()
	  {
	    tickAll('rejectButton', 'none');
	  }
	  function ignoreAll()
	  {
	    tickAll('ignoreButton', 'none');
	  }
	  
	  function warnAboutDeletions()
	  {
		  var el;
		  
		  var theSize = buttonNumRealSize;
		  var maxIESize = 50;
		  if((theSize>maxIESize) && isInternetExplorer())
		  {
			theSize = maxIESize;
		  }
		  var rejectCount = 0;
	      for(var i=0; i<theSize; i++)
		  {
		    el = document.getElementById('rejectButton' + buttonNum[i]);
		    if(el)
			{
		      if(el.checked)
			    rejectCount++;
		    }
		  }
	    if(rejectCount==0) return true;
		else return confirm("The " + (isInternetExplorer()?'':rejectCount) + " items you have rejected will be deleted from your department(s). Continue?");
	  }

		// Used for the detection of (god_DAMN) internet explorer, because of its annoying buggy behaviour...
		function checkIt(string)
		{
			place = detect.indexOf(string) + 1;
			thestring = string;
			return place;
		}
		var detect = navigator.userAgent.toLowerCase();
		function isInternetExplorer()
		{
			if (checkIt('konqueror'))return false;
			else if (checkIt('safari')) return false;
			else if (checkIt('omniweb')) return false;
			else if (checkIt('opera')) return false;
			else if (checkIt('webtv')) return false;
			else if (checkIt('icab')) return false;
			else if (checkIt('msie')) return true;
			else return false;
		}
	  function tickAll(elementPrefix, msgdisplaystate)
	  {
	    if(confirm("Are you sure?"))
		{
		  var el;
		  
		  var theSize = buttonNumRealSize;
		  var maxIESize = 50;
		  if((theSize>maxIESize) && isInternetExplorer())
		  {
		    alert("Unfortunately Internet Explorer has a bug which means this functionality is limited to a maximum of " + maxIESize + " records. An alternative browser such as Mozilla or Firefox may be worth considering.");
			theSize = maxIESize;
		  }
	      for(var i=0; i<theSize; i++)
		  {
		    el = document.getElementById(elementPrefix + buttonNum[i]);
		    if(el)
			{
		      el.checked=true;
		    }
		    setDisplayState('msgtoauthorlabel' + buttonNum[i], msgdisplaystate);
		  }
	    }
	  }
	  </script>
	  <p class="approvalmarkallopts">
	  Bulk options:<br />
	  <a href="javascript:approveAll();">Mark all as approved</a><br />
	  <a href="javascript:rejectAll();">Mark all as rejected</a><br />
	  <a href="javascript:ignoreAll();">Mark all as undecided</a></p>
	  <?php
	}
	?>
	<script type="text/javascript">
	  function setDisplayState(itemname, state){
	    if(document.getElementById && document.getElementById(itemname)){
	      document.getElementById(itemname).style.display = state;
	    }
	  }
	</script>
	<?php


	echo "<form action=\"./\" onsubmit=\"return warnAboutDeletions();\" method=\"post\">"
	       . "<p align=\"center\">Remember to <input type=\"submit\" value=\"Submit these decisions\" />!</p>" 
	       . "\n\n<table border=\"1\" class=\"approvaltable\">\n  <tr><th>Action&nbsp;to&nbsp;take</th>";
    foreach($keys as $k)
	  echo "\n    <th>$k</th>";
	echo "\n  </tr>";
	$reftypes = getPubTypes();
	foreach($p as $pkpk=>$v)
	{
	  if($pkpk%10==9)
	  {
	    ?>
		<tr><td colspan="2" style="text-align: center;"><input type="submit" value="Submit these decisions" /></td></tr>
		<?php
	  }
	  echo "\n  <tr><td align=\"left\">";
	  // NOW - If the decision has been supplied, we'll try to execute it. If not, we'll provide options.
      $showchoices=true;
	  if($_REQUEST['action' . $v['pubid']]=='approve')
	  {
		 $res = approvePub($v['pubid']);
		 if(!$res)
		   echo "Database error - failed to carry out action.";
		 else
		 {
		   echo "<p style='background:rgb(204,255,204);padding:10px;'>Entry approved - thank you.</p><p><a href=\"" . $config['pageshomeurl'] . "manage/?pubid=$v[pubid]&action=edit\">Edit entry</a></p>";
		   $showchoices=false;
		 }
	  }
	  else if($_REQUEST['action' . $v['pubid']]=='reject')
	  {
	  
	      // All the rejecting functionality is now bundled into the common rejectPub() function
		 if(rejectPub($v['pubid'], $rejectreasons[$_REQUEST['msgtoauthor'][$v['pubid']]])){
		   echo '<p style="background:rgb(255,255,204);padding:10px;">Entry deleted from your departmental list(s) - thank you.</p>';
		   $showchoices=false;
		 }else{
		   echo '<p style="background:rgb(255,204,204);padding:10px;">Error - rejection of publication seems not to have worked. Please contact technical support.</p>';
		   $showchoices=true;
		 }
	  }
	  elseif($singledept && $_REQUEST['action' . $v['pubid']]=='notmydept')
	  {
		 if(!pubIsNotForDepartment($v['pubid'], $singledept))
		   echo "<p>Database error - failed to carry out action.</p>";
		 else
		   echo "<p>Departmental association removed - thank you.</p>";
		 $showchoices=false;
	  }
	  if($showchoices)
	  {
	    $oemail = $v['originator'] . $config['emaildomain'];
		if($res = mysql_query("SELECT email FROM USERS WHERE userid='$v[originator]' LIMIT 1", connectpubsdb()))
		{
		  if(($row = mysql_fetch_assoc($res)) && strlen($row['email'])>2)
		    $oemail = $row['email'];
		  else
		  {
		    echo "\n\n<!--\$row:\n";
		    print_r($row);
		    echo "\n-->\n\n";
		  }
		}
		else
		{
		    echo "\n\n<!--Error:\n";
		    print_r(mysql_error());
		    echo "\n-->\n\n";
	    }
		echo "<label><input type=\"radio\" id=\"approveButton" . $v['pubid'] . "\" name=\"action" . $v['pubid'] . "\" value=\"approve\" onclick=\"setDisplayState('msgtoauthorlabel" . $v['pubid'] . "', 'none')\" />Approve</label><br />"
	      . "<label><input type=\"radio\" id=\"rejectButton" . $v['pubid'] . "\" name=\"action" . $v['pubid'] . "\" value=\"reject\" onclick=\"setDisplayState('msgtoauthorlabel" . $v['pubid'] . "', 'none')\" />Reject&nbsp;(DELETE&nbsp;from&nbsp;dept".($singledept?'':'s').")</label><br />"
	//      . ($singledept?"<input type=\"radio\" name=\"action" . $v['pubid'] . "\" value=\"notmydept\" />It&nbsp;doesn't&nbsp;belong&nbsp;under&nbsp;this&nbsp;department<br />":'')
	      . "<label><input type=\"radio\" id=\"ignoreButton" . $v['pubid'] . "\" name=\"action" . $v['pubid'] . "\" value=\"ignore\" onclick=\"setDisplayState('msgtoauthorlabel" . $v['pubid'] . "', 'none')\" />I'll&nbsp;decide&nbsp;later</label><br />"
//	      . "...or&nbsp;<a href=\"" . $config['pageshomeurl']  . "manage/?action=edit&pubid=$v[pubid]&showapprovechoices=1\">Edit&nbsp;before&nbsp;approving</a><br />"
//           . "<label id='msgtoauthorlabel" . $v['pubid'] . "' style='display: none; border: 1px solid rgb(0,102,0); padding: 0px; text-align: center; background: rgb(235,255,255); color: black;'>Message to author:<textarea cols='18' rows='4' name='msgtoauthor[" . $v['pubid'] . "]' id='msgtoauthor" . $v['pubid'] . "'>Please contact the administrator if you wish to know why the entry was not confirmed.</textarea></label>"
           . "<label id='msgtoauthorlabel" . $v['pubid'] . "' style='display: none; border: 1px solid rgb(0,102,0); padding: 0px; text-align: center; background: rgb(235,255,255); color: black;'>Reason for rejection:
		 <select name='msgtoauthor[" . $v['pubid'] . "]' id='msgtoauthor" . $v['pubid'] . "'>";
		 foreach($rejectreasons as $kr => $vr){
		   echo "\n  <option value=\"$kr\">$vr</option>";
		 }
		 echo "</select>
		 </label>"
		    ;
      }
	  echo "</td>";
	  foreach($keys as $k=>$ignorethisvariable)
	  {
	    echo "\n    <td valign=\"top\">";
        switch($k)
		{
		  case 'detail':
	         $showopts = 'duplication';
		    echo "<b><i>Reference type: $v[reftype]</i></b><br />" .
			       formataref2($v, $_SERVER['REMOTE_USER'], '') .
				  " [<a href=\"" . $config['pageshomeurl']  . "manage/?action=edit&pubid=$v[pubid]&showapprovechoices=1\">Edit</a>]"
				  ; 
	         $showopts = '';
		     echo  "<br /><br />&nbsp;&nbsp;<em>Extra options:</em> "
				   . "[<a href=\"mailto:$oemail?subject=Re:%20Online%20publication%20submission&body=" . rawurlencode(strip_tags(formataref($v, $_SERVER['REMOTE_USER'], false, 'n', false))) . "\">Email&nbsp;the&nbsp;originator</a>]  "
				   . (($v['reftype']=='JOUR' && $v['url']=='')?"[<a href=\"\" target=\"_blank\" onclick='window.open(\"" . $config['scriptshomeurl'] . "/manage/pubmedlocate/?title=" . rawurlencode($v['title']) . "&pubid=$v[pubid]\",\"pubmedlocator\",\"width=440,height=400,\"); return false'>Automatic&nbsp;PubMed&nbsp;lookup</a>]":'');
			$depts = getalldeptsnames();
			$deptswap = array();
			foreach($depts as $kd=>$vd)
			  $deptswap['|\b' . $kd . '\b|'] = "$vd ($kd)";
			$deptlist = preg_replace('/^,(.*?),?$/',"$1",preg_replace(array_keys($deptswap), array_values($deptswap), preg_replace('/[, ]+/',', ',$v['deptlist'])));
			$pendingdeptlist = preg_replace('/^,(.*?),?$/',"$1",preg_replace(array_keys($deptswap), array_values($deptswap), preg_replace('/[, ]+/',', ',$v['pendingdepts'])));
			echo '<br />';
			if(strlen(trim($pendingdeptlist))!=0)
			  echo "<br /><span style=\"color: rgb(153,153,153);\">Pending department(s): $pendingdeptlist</span>";
			if(strlen(trim($deptlist))!=0)
			  echo "<br /><span style=\"color: rgb(153,153,153);\">Department(s): $deptlist</span>";
			echo "<br /><span style=\"color: rgb(153,153,153);\"> Last modified: " 
			             . date('j/n/Y', $v['utstamp']) . " - <a href = \"" . $config['pageshomeurl'] . "manage/?action=querylog&pubid=" . $v['pubid'] . "\">click here for audit trail</a></span>";
			break;
		  case 'year':
	        echo ($v[$k]==9999?$config['inpressstring']:$v[$k]);
			break;
		  case 'reftype':
	        echo $reftypes[$v[$k]];
			break;
		  default:
	        echo "$v[$k]&nbsp;";
		}
        echo "</td>";
	  }
	  echo "\n  </tr>";
	}
    echo "\n</table>"
	     . "<p align=\"center\"><input type=\"submit\" value=\"Submit these decisions\" />"
	//	 . "&nbsp;or&nbsp;<input type=\"button\" value=\"Return to management homepage\" onclick=\"document.location='../'\" />"
         . "<input type=\"hidden\" name=\"dummy\" value=\"" . time() . "\" />"
		 . "</p>"
	     . "</form>";
  }
} // End of "are-they-authorised-at-all?"



?>
