<script type="text/javascript">
<!--
function checksize(t) {
   if(t.value.length>511) {
      t.value=t.value.substr(0,511);
      alert("You've exceeded the limit of 511 characters");
   }
}
// -->
</script>
<?php

// DO NOT INCLUDE THIS FILE DIRECTLY - IT IS INCLUDED AUTOMATICALLY BY common.php

// $p = array of publication data
// $userid = user ID
// $newpubtype = code of new publication type, if chosen

  $allpubtypes = getPubTypesAllData();

  if($p==null && isset($allpubtypes[$newpubtype]))
    $p['reftype'] = $newpubtype;

?>
<form action="./" method="post" name="pubform" id="pubform"  onSubmit="return (validateAuthors() && validateEditors() && validateSeriesEditors());">
<input type="hidden" name="returnurl" value="<?php echo htmlspecialchars($_REQUEST['returnurl']) ?>" />
<input type="hidden" name="returnname" value="<?php echo htmlspecialchars($_REQUEST['returnname']) ?>" />
<table border="1" align="center" cellspacing="0" style="border: 1px solid gray;">
 <tr bgcolor="#000066">
   <th colspan="3"><div align="center"><font color="#FFFFFF">Main details</font></div></th>
   </tr>
 <tr>
    <th>Publication type:</th>
<?php
//if($p!=null || isset($allpubtypes[$newpubtype]))
if(isset($p['reftype']))
{
  ?>
  <td colspan="2">
    <p><?php echo $allpubtypes[$p['reftype']]['reftypename']; 

// Also include the publication ID if known
if($p['pubid']>0)
{
  echo " <br /><em>(Record ID: <a href='$config[pageshomeurl]?action=search&pubid=$p[pubid]'>$p[pubid]</a>)</em>";
}
?>

	<input name="reftype" type="hidden" value="<?php echo htmlspecialchars($p['reftype']) ?>" /></p>
  </td>
  <?php
}
/* 

     DELETEME - Remove this whole chunk since it's way way outdated


else
{
  ?>
	<td><select name="reftype" onchange="setreftypehint(document.pubform.reftype.value)">
<?php
if(strlen($p['reftype'])>0)
  $rtype = $p['reftype'];
else
  $rtype = 'JOUR';
foreach($allpubtypes as $k=>$v)
  echo "\n       <option value=\"$k\"" . (($rtype==$k)?' selected="selected"':'') . ">$v[reftypename]</option>";
?>
	</select>
	</td>
	<td>
	  <em>Hints about reference types:</em><br />
	  <textarea name="reftypehints" cols="30" rows="4" readonly="readonly" wrap="virtual">
	  </textarea>
<script type="text/javascript">
  var reftypehintlist = Array();
  <?php
     // Now access the help hints from the database and just write them out one-by-one.
	 foreach(getPubTypesHelp() as $k => $v)
	   echo "\n   reftypehintlist['$k'] = \"" . preg_replace("/\\r\\n|\\r|\\n/", '\n', addslashes($v)) . "\";";
  ?>
  
  function setreftypehint(newreftype)
  {
    document.pubform.reftypehints.value = reftypehintlist[newreftype];
    // document.pubform.reftypehints.value = newreftype;
  }
  setreftypehint('JOUR');
</script>
	</td>
<?php
} // End of the "do we allow them to choose the reftype or not?" clause
*/
?>
 </tr>
<?php
if($allpubtypes[$p['reftype']]['DISPLAYauthorlist']!='')
{
?>
 <tr>
    <th><?php 
echo ($allpubtypes[$p['reftype']]['DISPLAYauthorlist']=='Y')
?'Authors':$allpubtypes[$p['reftype']]['DISPLAYauthorlist'] ?></th><td colspan="2"><textarea name="authorlist" id="authorlist" cols="50" rows="2" wrap="virtual"><?php 

$aut = trim($p['authorlist']);
if(strlen($aut)==0)
{
  if(strlen($userid)>0 && ($_SERVER['REMOTE_USER']!=$userid)) // If an admin editing someone else's page
  {
//    echo "[\$userid = $userid]";
    $aut = getUserBiblioName($userid);
  }
  elseif(!authorisedForPubs($_SERVER['REMOTE_USER'])) // or if an academic
  {
//    echo "[\$_SERVER['REMOTE_USER'] = $_SERVER['REMOTE_USER']]";
    $aut = getUserBiblioName($_SERVER['REMOTE_USER']);
  }
}
echo $aut;

	 ?></textarea><?php specialcharslink('pubform','authorlist') ?>
    <br />
	   For various reasons, the 
	   list of authors must be in the<br> 
	   following
       format: <strong>Smith,N.,Jones,D.,van Sant,L.G.</strong><br />
	   (i.e. no &quot;and&quot; or &quot;&amp;&quot;; commas to separate each surname and initials)
	   <br /></td>
<script type="text/javascript">
<!--

document.getElementById("authorlist").focus();


/*
THIS REGULAR EXPRESSION IS IMPORTANT - It defines the allowed format for author/editor-type fields.
Do not alter it unless you're SURE what the effect is going to be, since if it goes wrong, various people
with slightly unusual names might end up being unable to type themselves in!
*/
//var authorMatcher = new RegExp("^([^,]+,\\s*([a-z]\\.\\s*)+[,;]\\s*)*([^,]+,\\s*([a-z]\\.\\s*)+\\s*)$", "i");
var authorMatcher = /^([^,]+,\s*([a-z]\.\s*)+[,;]\s*)*([^,]+,\s*([a-z]\.\s*)+\s*)$/i/;

function isCorrectAuthorFormat(inString)
{
  matches = authorMatcher.exec(inString);
  return matches!=null;
}

function validateAuthors()
{
  if(!(document.getElementById))
    return true;
  var val = document.getElementById("authorlist").value;
  if(val.length<1)
  {
    return confirm("Are you sure the 'Author(s)' field should be blank?\n\nThis may be OK (e.g. if you're listed a book's editors only)\nbut is not usual.");
  }
  // Now the hard bit: use a regular expression to check the well-formedness of the input
  if(!isCorrectAuthorFormat(val))
  {
    return confirm("The 'Author(s)' field seems to be incorrectly filled in. You entered:\n\n "+val+"\n\nThe required format for an author's name is \"LASTNAME-comma-INITIAL-fullstop\", and authors should be separated by commas - for example:\n\n O'Keefe,P.T., van Salinsky,E., Smythe,T.P.T.\n\nPlease press 'Cancel' unless you're sure you want to proceed with the text you've entered.");
  }
  return true;
}

function validateEditors()
{
  if(!(document.getElementById && document.getElementById("secondaryauthorlist")))
    return true;
  var val = document.getElementById("secondaryauthorlist").value;
  if(val.length<3)
  {
    return true;
  }
  // Now the hard bit: use a regular expression to check the well-formedness of the input
  if(!isCorrectAuthorFormat(val))
  {
    return confirm("The 'Editor(s)' field seems to be incorrectly filled in. You entered:\n\n "+val+"\n\nThe required format for an author's name is \"LASTNAME-comma-INITIAL-fullstop\", and authors should be separated by commas - for example:\n\n O'Keefe,P.T., van Salinsky,E., Smythe,T.P.T.\n\nPlease press 'Cancel' unless you're sure you want to proceed with the text you've entered.");
  }
  return true;
}

function validateSeriesEditors()
{
  if(!(document.getElementById && document.getElementById("seriesauthorlist")))
    return true;
  var val = document.getElementById("seriesauthorlist").value;
  if(val.length<3)
  {
    return true;
  }
  // Now the hard bit: use a regular expression to check the well-formedness of the input
  if(!isCorrectAuthorFormat(val))
  {
    return confirm("The 'Series editor(s)' field seems to be incorrectly filled in. You entered:\n\n "+val+"\n\nThe required format for an author's name is \"LASTNAME-comma-INITIAL-fullstop\", and authors should be separated by commas - for example:\n\n O'Keefe,P.T., van Salinsky,E., Smythe,T.P.T.\n\nPlease press 'Cancel' unless you're sure you want to proceed with the text you've entered.");
  }
  return true;
}


// -->
</script>
 </tr>
<?php
}
if($allpubtypes[$p['reftype']]['DISPLAYyear']!='')
{
?>
 <tr>
    <th><?php 
         echo ($allpubtypes[$p['reftype']]['DISPLAYyear']=='Y')
                     ?'Year':$allpubtypes[$p['reftype']]['DISPLAYyear'] ?></th>
	<td>  <span id="yeardmy1"><label>Day:<input name="yearday" type="text" value="<?php echo $p['yearday'] ?>" size="2" maxlength="2" /></label>
&nbsp;<label>Month:<input name="yearmonth" type="text" value="<?php echo $p['yearmonth'] ?>" size="2" maxlength="2" /></label>&nbsp;Year:</span><input name="year" type="text" value="<?php if(strlen($p['year'])==0) echo date('Y'); elseif($p['year']!=9999)echo $p['year'] ?>" maxlength="4" size="6" />
<span id="yeardmy2">&nbsp;<label>Other:<input name="yearother" type="text" value="<?php echo $p['yearother'] ?>" size="8" maxlength="8" /></label><br /></span> - <label>or
	for "<?php echo $config['inpressstring'] ?>" tick this box: <input name="inpress" type="checkbox" value="y" <?php if($p['year']==9999)
	echo 'checked="checked"' ?>/></label></td>
	<td align="right">
	<div id="yeardmyswitch" style="display:none;"><a href="javascript:yearDMY();" title="Switch to more detailed day/month/year view">dd/mm/yy</a></div>
	<div id="yearyswitch" style="display:none;"><a href="javascript:yearY();" title="Switch to year only (no day/month info)">yyyy</a></div></td>
<script type="text/javascript">
<!--
function yearDMY()
{
  if(document.getElementById)
  {
    document.getElementById("yeardmyswitch").style.display = "none";
    document.getElementById("yearyswitch").style.display = "block";
    document.getElementById("yeardmy1").style.display = "inline";
    document.getElementById("yeardmy2").style.display = "inline";
  }
}
function yearY()
{
  if(document.getElementById)
  {
    document.getElementById("yeardmyswitch").style.display = "block";
    document.getElementById("yearyswitch").style.display = "none";
    document.getElementById("yeardmy1").style.display = "none";
    document.getElementById("yeardmy2").style.display = "none";
  }
}
yearY();
//-->
</script>
 </tr>
<?php
}
if($allpubtypes[$p['reftype']]['DISPLAYtitle']!='')
{
?>
 <tr>
    <th><?php 
echo ($allpubtypes[$p['reftype']]['DISPLAYtitle']=='Y')
?'Title':$allpubtypes[$p['reftype']]['DISPLAYtitle'] ?>:</th><td colspan="2">	<textarea name="title" id="title" cols="50" rows="2" wrap="VIRTUAL" onkeyup="checksize(this)" onpaste="checksize(this)"><?php echo $p['title'] ?></textarea><?php specialcharslink('pubform','title') ?></td>
 </tr>
<?php
}
?>
 <tr>
    <th>URL:</th><td colspan="2">	<input name="url" type="text" value="<?php echo $p['url'] ?>" size="50" /><br />To add a link to PubMed, enter 
	        it in the format
       <strong>pm:00347629435</strong>.<br />
NB: you still need to enter the complete author, title, journal details.<br />
Otherwise, please enter a full URL beginning with <i>http://</i></td>
 </tr>
<!--
 <tr>
    <th><a href="<?php echo $config['eprints_prefix'] ?>" target="_blank"><?php echo $config['institutionname'] ?> eprints</a> ref.no.:</th><td colspan="2">
	<input name="eprint" type="text" value="<?php if(intval($p['eprint'])>0) echo $p['eprint'] ?>" size="8" maxlength="8" />
</td>
 </tr>
-->
 <tr>
    <th>Keywords:</th><td colspan="2">	<input name="keywords" type="text" value="<?php echo $p['keywords'] ?>" size="50" /><br />
	      Please separate each keyword with a comma.
<!--
    <p align="right">You might like to pick keywords from this list:
	<select name="keywordchooser" onchange="document.pubform.keywords.value+=document.pubform.keywordchooser.value">
	<?php
	/*
	foreach(getUsedKeywords('') as $v)
	  echo "\n     <option value=\"" . htmlspecialchars($v) ."\">" 
	                  . htmlspecialchars((strlen($v)>60)?substr($v,0,57).'...':$v) . "</option>";
	*/
	?>
	</select></p>
-->
		  </td>
 </tr>
 <tr>
   <th colspan="3" bgcolor="#000066"><div align="center"><font color="#FFFFFF">Other
      details</font></div></th>
   </tr>
<?php
if($allpubtypes[$p['reftype']]['DISPLAYjournal']!='')
{
?>
 <tr>
    <th valign="top"><?php 
echo ($allpubtypes[$p['reftype']]['DISPLAYjournal']=='Y')
?'Periodical title':$allpubtypes[$p['reftype']]['DISPLAYjournal'] ?>:</th><td colspan="2">
	<p>
	  <input name="journal" id="journal" type="text" value="<?php echo $p['journal'] ?>" size="50" />
	</p>
	  <?php
	  $journalstooffer = array();
	  if(!authorisedForPubs($_SERVER['REMOTE_USER']))
		$journalstooffer = getAllUsersJournals($_SERVER['REMOTE_USER']);
	  if(sizeof($journalstooffer)==0)
		$journalstooffer = getAllOriginatedJournals($_SERVER['REMOTE_USER']);

	  if(sizeof($journalstooffer)!=0)
	  {
	    ?>

	  <p align="right">You might like to pick a periodical title from this list:
	  <select name="journalchooser" onchange="document.pubform.journal.value=document.pubform.journalchooser.value.substring(10);document.pubform.issnisbn.value=document.pubform.journalchooser.value.substring(0,9);">
	  <option value=""> - Choose a periodical - </option>
      <?php
	  foreach($journalstooffer as $issn => $v)
		echo "\n     <option value=\"" . htmlspecialchars(str_pad(preg_replace('/^(\d{4})\D*(\d{3}[\dXx])/', "$1-$2", substr(trim($issn),0,9)), 10)) . htmlspecialchars($v) ."\"" . ($p['journal']==$v?' selected':'') .">" 
						. htmlspecialchars((strlen($v)>60)?substr($v,0,57).'...':$v) . "</option>";
	  ?>
	  </select></p>
	  <?php
	  }
	  ?>
	</td>
 </tr>
<?php
}
?>
<?php
if($allpubtypes[$p['reftype']]['DISPLAYissnisbn']!='')
{
?>
 <tr>
    <th><?php 
echo ($allpubtypes[$p['reftype']]['DISPLAYissnisbn']=='Y')
?'Serial number (ISSN/ISBN)':$allpubtypes[$p['reftype']]['DISPLAYissnisbn'] ?>:</th><td colspan="2">	<input name="issnisbn" type="text" value="<?php echo $p['issnisbn'] ?>" /></td>
 </tr>
<?php
}
?>
<?php
if($allpubtypes[$p['reftype']]['DISPLAYabstract']!='')
{
?>
 <tr>
    <th valign="top"><?php 
echo ($allpubtypes[$p['reftype']]['DISPLAYabstract']=='Y')
?'Abstract':$allpubtypes[$p['reftype']]['DISPLAYabstract'] ?>:</th><td colspan="2">
	  <div id="abstractdiv">
	  <textarea name="abstract" cols="60" rows="10" id="abst"><?php 
	     echo htmlspecialchars($p['abstract']) 
	    ?></textarea><?php specialcharslink('pubform','abst') ?></div>
	  <script type="text/javascript">
	  if(document.getElementById)
	  {
	    document.write('<div id="abstractrevealerlink"><a href="javascript:revealAbstract()">Click here to add/edit <?php 
echo ($allpubtypes[$p['reftype']]['DISPLAYabstract']=='Y')
?'abstract':$allpubtypes[$p['reftype']]['DISPLAYabstract'] ?></a></div>');
	    document.getElementById("abstractdiv").style.display='none';
	  }
	  function revealAbstract()
	  {
	    document.getElementById("abstractdiv").style.display='block';
	    document.getElementById("abstractrevealerlink").style.display='none';
	    document.getElementById("abst").focus();
	  }
	  </script>
	</td>
 </tr>
<?php
}
if($allpubtypes[$p['reftype']]['DISPLAYvolume']!='')
{
?>
 <tr>
    <th><?php 
echo ($allpubtypes[$p['reftype']]['DISPLAYvolume']=='Y')
?'Volume':$allpubtypes[$p['reftype']]['DISPLAYvolume'] ?>
:</th>
    <td colspan="2">	<input name="volume" type="text" value="<?php echo $p['volume'] ?>" size="6" /></td>
 </tr>
<?php
}
if($allpubtypes[$p['reftype']]['DISPLAYissue']!='')
{
?>
 <tr>
    <th><?php 
echo ($allpubtypes[$p['reftype']]['DISPLAYissue']=='Y')
?'Issue':$allpubtypes[$p['reftype']]['DISPLAYissue'] ?>:</th>
    <td colspan="2">	<input name="issue" type="text" value="<?php echo $p['issue'] ?>" size="6" /></td>
 </tr>
<?php
}
?>
 <tr>
    <th>Start page:</th>
    <td colspan="2">	<input name="startpage" type="text" value="<?php echo $p['startpage'] ?>" size="8" /></td>
 </tr>
<?php
?>
 <tr>
    <th>End page:</th>
    <td colspan="2">	<input name="endpage" type="text" value="<?php echo $p['endpage'] ?>" size="8" /></td>
 </tr>
<?php
if($allpubtypes[$p['reftype']]['DISPLAYchapter']!='')
{
?>
 <tr>
    <th><?php 
echo ($allpubtypes[$p['reftype']]['DISPLAYchapter']=='Y')
?'Chapter':$allpubtypes[$p['reftype']]['DISPLAYchapter'] ?>:</th><td colspan="2">	<input name="chapter" type="text" value="<?php echo $p['chapter'] ?>" size="6" /></td>
 </tr>
<?php
}
if($allpubtypes[$p['reftype']]['DISPLAYsecondarytitle']!='')
{
?>
 <tr>
    <th><?php 
echo ($allpubtypes[$p['reftype']]['DISPLAYsecondarytitle']=='Y')
?'Secondary title':$allpubtypes[$p['reftype']]['DISPLAYsecondarytitle'] ?>:</th>
	<td colspan="2">	<input name="secondarytitle" id="secondarytitle" type="text" value="<?php echo $p['secondarytitle'] ?>" size="50" /><?php specialcharslink('pubform','secondarytitle') ?></td>
 </tr>
<?php
}
if($allpubtypes[$p['reftype']]['DISPLAYsecondaryauthorlist']!='')
{
?>
 <tr>
    <th><?php 
echo ($allpubtypes[$p['reftype']]['DISPLAYsecondaryauthorlist']=='Y')
?'Secondary authors':$allpubtypes[$p['reftype']]['DISPLAYsecondaryauthorlist'] ?>:</th>
	<td colspan="2">	<input name="secondaryauthorlist" type="text" id="secondaryauthorlist" size="50" value="<?php echo $p['secondaryauthorlist'] ?>" /><?php specialcharslink('pubform','secondaryauthorlist') ?>
	  <br />For various reasons, this list must be in the<br>
following format: <strong>Smith,N.,Jones,D.,van Sant,L.G.</strong><br />
(i.e. no &quot;and&quot; or &quot;&amp;&quot;; commas to separate each surname
and initials)</td>
 </tr>
<?php
}
if($allpubtypes[$p['reftype']]['DISPLAYseriestitle']!='')
{
?>
 <tr>
    <th><?php 
echo ($allpubtypes[$p['reftype']]['DISPLAYseriestitle']=='Y')
?'Series title':$allpubtypes[$p['reftype']]['DISPLAYseriestitle'] ?>:</th><td colspan="2">	<input name="seriestitle" id="seriestitle" type="text" value="<?php echo $p['seriestitle'] ?>" size="50" /><?php specialcharslink('pubform','seriestitle') ?></td>
 </tr>
<?php
}
if($allpubtypes[$p['reftype']]['DISPLAYseriesauthorlist']!='')
{
?>
 <tr>
    <th><?php 
echo ($allpubtypes[$p['reftype']]['DISPLAYseriesauthorlist']=='Y')
?'Series editors':$allpubtypes[$p['reftype']]['DISPLAYseriesauthorlist'] ?>:</th><td colspan="2">	<input name="seriesauthorlist" id="seriesauthorlist" type="text" value="<?php echo $p['seriesauthorlist'] ?>" size="50" /><?php specialcharslink('pubform','seriesauthorlist') ?></td>
 </tr>
<?php
}
?>
 <tr>
    <th>Correspondence address:</th><td colspan="2">	<input name="address" type="text" value="<?php echo $p['address'] ?>" size="50" /></td>
 </tr>
<?php
if($allpubtypes[$p['reftype']]['DISPLAYpublisher']!='')
{
?>
 <tr>
    <th><?php 
echo ($allpubtypes[$p['reftype']]['DISPLAYpublisher']=='Y')
?'Publisher':$allpubtypes[$p['reftype']]['DISPLAYpublisher'] ?>:</th><td colspan="2">	<input name="publisher" type="text" value="<?php echo $p['publisher'] ?>" size="50" /></td>
 </tr>
<?php
}
if($allpubtypes[$p['reftype']]['DISPLAYcity']!='')
{
?>
 <tr>
    <th><?php 
echo ($allpubtypes[$p['reftype']]['DISPLAYcity']=='Y')
?'City / place of publication':$allpubtypes[$p['reftype']]['DISPLAYcity'] ?>:</th><td colspan="2">	<input name="city" id="city" type="text" value="<?php echo $p['city'] ?>" size="50" maxlength="255" /><?php specialcharslink('pubform','city') ?></td>
 </tr>
<?php
}
if($allpubtypes[$p['reftype']]['DISPLAYmedium']!='')
{
?>
 <tr>
    <th><?php 
echo ($allpubtypes[$p['reftype']]['DISPLAYmedium']=='Y')
?'Medium':$allpubtypes[$p['reftype']]['DISPLAYmedium'] ?>:</th><td colspan="2">	<input name="medium" type="text" value="<?php echo $p['medium'] ?>" /></td>
 </tr>
<?php
}
?>
 <tr>
    <th><span onclick="if(confirm('DOI is the Digital Object Identifier, a unique way of \nreferring to digital objects such as online documents. \nClick OK if you\'d like to read more about DOI.')) {window.open('http://www.doi.org/', 'doiwin');}"><abbr title="Digital Object Identifier">DOI</abbr></span>:</th><td colspan="2">	<input name="DOI" type="text" value="<?php echo htmlspecialchars($p['DOI']) ?>" maxlength="255" size="50" /></td>
 </tr>
<?php
if($allpubtypes[$p['reftype']]['DISPLAYY2']!='')
{
?>
 <tr>
    <th><?php 
	$y2 = explode('/',trim($p['Y2']));
echo ($allpubtypes[$p['reftype']]['DISPLAYY2']=='Y')
?'Secondary date':$allpubtypes[$p['reftype']]['DISPLAYY2'] ?>:</th><td colspan="2"><label>Day:<input name="Y2d" type="text" value="<?php echo $y2[2] ?>" size="2" maxlength="2" /></label>
&nbsp;<label>Month:<input name="Y2m" type="text" value="<?php echo $y2[1] ?>" size="2" maxlength="2" /></label>&nbsp;<label>Year:<input name="Y2y" type="text" value="<?php echo $y2[0] ?>" size="4" maxlength="4" /></label>&nbsp;<label>Other:<input name="Y2o" type="text" value="<?php echo $y2[3] ?>" size="8" maxlength="8" /></label></td>
 </tr>
<?php
}
?>
 <tr>
    <th valign="top">Notes:</th><td colspan="2">
	  <div id="notesdiv">
	  <textarea name="notes" cols="60" rows="10" id="notes"><?php 
	     echo htmlspecialchars($p['notes']) 
	    ?></textarea><?php specialcharslink('pubform','notes') ?></div>
	  <script type="text/javascript">
	  if(document.getElementById)
	  {
	    document.write('<div id="notesrevealerlink"><a href="javascript:revealNotes()">Click here to add/edit notes</a></div>');
	    document.getElementById("notesdiv").style.display='none';
	  }
	  function revealNotes()
	  {
	    document.getElementById("notesdiv").style.display='block';
	    document.getElementById("notesrevealerlink").style.display='none';
	    document.getElementById("notes").focus();
	  }
	  </script>
	</td>
 </tr>
 <tr>
    <th>Category codes:</th><td colspan="2">	
<?php
if(authorisedForPubs($_SERVER['REMOTE_USER']))
{
  ?>
      <input name="catkw" type="text" value="<?php echo $p['catkw'] ?>" size="50" maxlength="128" />
 <?php
} else {
  ?>
      <input name="catkw" type="hidden" value="<?php echo $p['catkw'] ?>" /><?php echo $p['catkw'] ?>
 <?php
}
?>
	 <br />
	      (For use by departmental admins/webmasters) <a title="This can be used for departmental web purposes" href="javascript:alert('Category codes can be used by departmental webmasters to group results on webpages. Please DO NOT enter anything in this field unless you know the codes your departmental webmaster is using. And DO NOT DELETE data from the field - it may be important data for another department.')">[?]</a>
		  </td>
 </tr>
<?php

// Now for the "don't approve just yet" button, only available for administrators

if(authorisedForPubs($_SERVER['REMOTE_USER']))
{

  $deptsarewaiting = false;
  if(isGlobalAdmin($_SERVER['REMOTE_USER']) && preg_match('/[a-z]/i', $p['pendingdepts'])){
    $deptsarewaiting = true;
  }else{
    $mydepts = splitDeptListString(getAuthorisations($_SERVER['REMOTE_USER']));
//    echo "<p>Searching through mydepts for $p[pendingdepts]. Mydepts is " . print_r($mydepts, true) . "</p>";
    foreach($mydepts as $dcode){
      if(strpos($p['pendingdepts'], ",$dcode,")!==false){ 
        $deptsarewaiting = true;
//	   echo "<p>Found</p>";
      }
    }
  }


if(!(strlen($p['pubid'])>0) || $showapprovechoices || $deptsarewaiting) // The "showapprovechoices" shouldn't REALLY be needed, since deptsarewaiting should cover it
{
?>
 <tr>
    <th>&nbsp;</th>
    <td align="center" colspan="2">
	<label style="padding: 1px 40px 1px 1px;"><input name="autoapprove" type="radio" value="1" checked="checked" />Approve immediately</label>
	<label style="padding: 1px 1px 1px 40px;"><input name="autoapprove" type="radio" value="0" />Don't approve immediately</label>
    </td>
 </tr>
<?php
}
}
?>
 <tr>
    <td colspan="3" align="center">
	<input name="action" type="hidden" value="storepub" />
	<input name="userid" type="hidden" value="<?php echo $userid ?>" />
	<input name="pubid" type="hidden" value="<?php echo (strlen($p['pubid'])>0?$p['pubid']:'-1') ?>" />
	<input type="submit" value="Store" /></td>
 </tr>
</table>
</form>
<?php


?>
