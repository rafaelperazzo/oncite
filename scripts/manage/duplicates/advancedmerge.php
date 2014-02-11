<?php

/* 
Supply multiple publication IDs by passing pubid[] arguments.
The user can then choose from the various options for fields.
*/



require_once(dirname(dirname(__FILE__)) . '/common.php');
require_once(dirname(__FILE__) . '/duplicatelib.inc.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/query/formataref.php');

/* echo "<pre>";
print_r($_REQUEST);
echo "</pre>"; */
$pubid = $_REQUEST['pubid'];
$mergedata = $_REQUEST['mergedata'];

if(!is_array($pubid) || sizeof($pubid)<2){
  die('<p style="padding: 50px;">At least two publication IDs must be supplied for this page.</p>');
}
// Clean up the data before using it:
foreach($pubid as $k=>$v)
  $pubid[$k] = intval($v);
$pubid = array_unique($pubid);


if((sizeof($mergedata) > 5) && ($mergedata['pubid']>0)){
  $mergedata['keywords'] = implode(',', $mergedata['keywords']);
  if(!is_array($mergedata['userlist']))
    $mergedata['userlist'] = splitDeptListString($mergedata['userlist']);
  $mergedata['userlist'] = mergeDeptListString($mergedata['userlist']);

  // NB Must make sure that none of the approved depts remain on the "pending" list
  $mergedata['pendingdepts'] = mergeDeptListString(array_diff(splitDeptListString($mergedata['pendingdepts']), splitDeptListString($mergedata['deptlist'])));
  $mergedata['deptlist'] = mergeDeptListString(splitDeptListString($mergedata['deptlist']));

  $mergedata['anydepts'] = $mergedata['deptlist'] != ',' ? 1 : 0;
  $mergedata['anypending'] = $mergedata['pendingdepts'] != ',' ? 1 : 0;
  foreach($pubid as $key=>$val){
    if($val==$mergedata['pubid'])
      unset($pubid[$key]);
  }

//  echo "<pre>".print_r($pubid, true).print_r($mergedata, true)."</pre>";

  // Update the record whose pubid we've decided to converge upon.
  $q = "UPDATE PUBLICATIONS SET timestamp=NOW()";
  foreach($mergedata as $key=>$val){
    $q .= ', '.mysql_real_escape_string($key) . '="'.mysql_real_escape_string($val).'"';
  }
  $q .= " WHERE pubid=".intval($mergedata['pubid'])." LIMIT 1";
  
  //echo $q;
  $res = mysql_query($q, connectpubsdb());
  
  // ENSURE that exactly one record in the database has been updated, before deleting others
  if((!$res) || (mysql_affected_rows()!=1)){
    die("ERROR during merge process. Please contact the support team, giving as much details as possible. (The pubid numbers involved will be particularly useful.)");
  }
  
  // Delete the others, storing the log data at the same time
  $q = "DELETE FROM PUBLICATIONS WHERE pubid IN (".implode(',', $pubid).")";
  mysql_query($q, connectpubsdb());
  $q = "UPDATE RAEMYPUBS SET pubid='".$mergedata['pubid']."' WHERE pubid IN (".implode(',', $pubid).")";
  mysql_query($q, connectpubsdb());
  foreach($pubid as $idnum){
    recordtransaction('mergeadv:'.$mergedata['pubid'], $idnum);
  }
  ?><p>Successfully merged records into 
  <!-- <a href="<?php echo $config['pageshomeurl']?>?action=search&pubid=<?php echo $mergedata['pubid'] ?>"> -->record #<?php echo $mergedata['pubid'] ?><!-- </a> -->.
  </p><p>You may wish to 
  <a href="<?php echo $config['pageshomeurl']?>manage/?action=edit&pubid=<?php echo $mergedata['pubid'] ?>">edit the record</a>.
  </p>
  <p style="padding: 50px;"><?php echo formataref($mergedata) ?></p>
  <p><a href="<?php echo $config['pageshomeurl']?>manage/">Return to main admin page</a></p>
  <?php

}else{





  // Display the interactive merge form

$q = "SELECT * FROM PUBLICATIONS WHERE pubid IN (".implode(',', $pubid).")";

$res = mysql_query($q, connectpubsdb());
if(!$res)
  die('Error performing database query');

$pubs = array();
$fields = array();
while($row = mysql_fetch_assoc($res)){
  $pubs[] = $row;
  foreach($row as $key=>$val)
    $fields[$key][] = $val;
}
foreach($fields as $key=>$val){
  $fields[$key] = array_unique($val);
  foreach($fields[$key] as $pos=>$vval)
    if(trim($vval)=='')
      unset($fields[$key][$pos]);
  $fields[$key] = array_values($fields[$key]);
}
$fields['userlist'] = splitDeptListString(mergeDeptListString($fields['userlist']));
$fields['deptlist'] = splitDeptListString(mergeDeptListString($fields['deptlist']));
$fields['pendingdepts'] = splitDeptListString(mergeDeptListString($fields['pendingdepts']));
$fields['keywords'] = array_unique(explode(',', implode(',', $fields['keywords'])));


?>
<p>Merging the following records:</p><ul><?php
foreach($pubs as $p)
  echo "\n  <li>".formataref($p)."<br /><em>(ID number: $p[pubid])</em></li>";
?></ul>
<!-- <p>In the following form, please select the desired option where there are multiple fields.
For some (e.g. keywords) you can choose some or all of the options, but for others (e.g. year)
you may only select one.</p> -->
<form method="post" action="./advancedmerge.php" style="border: 1px solid black; margin: 4px 40px; padding: 10px;">
<p>Choose the best option from any pull-down lists which appear below. 
[<a href="<?php echo $config['scriptshomeurl']?>manage/duplicates/about-advancedmerge.htm" target="_blank" onclick="window.open('<?php echo $config['scriptshomeurl']?>manage/duplicates/about-advancedmerge.htm', 'helpwin', 'width=500,height=600,toolbar=no,location=no,status=no,menubar=no,resizable=yes,scrollbars=yes'); return false;">Help</a>]</p>
<?php
foreach($pubid as $idnum)
  echo "<input type='hidden' name='pubid[]' value='$idnum' />\n";

//echo "<pre>".print_r($fields, true)."</pre>";

foreach($fields as $field=>$opts){
  echo "<p>";
  switch($field){
    // Entries to be completely ignored - they are either calc'ed automatically or must be recreated
    case 'anydepts':
    case 'anypending':
    case 'timestamp':
    case 'approvedby':
    case 'approvaldate':
      break;
    case 'userlist':
    case 'deptlist':
    case 'pendingdepts':
      $data = mergeDeptListString(splitDeptListString(implode(',', $opts)));
      advancedmerge_nochoice($field, $data, '', true);
      break;
    default:
      if(sizeof($opts)>1){
        $multi = ($field=='keywords' || $field=='userlist' || $field=='deptlist');
	   advancedmerge_oneline($field, $opts, '', $multi);
      }else{
        advancedmerge_nochoice($field, $opts[0]);
	 }
      }
  echo "</p>";
}
?>
<p style="border: 2px solid red; padding: 2px; text-align: left;">WARNING: If you submit this form, the records will be merged into one single record, 
and any data that is not 
selected in the above form will be DELETED. <input type="submit" value="Merge records" /></p>
</form>
<?php
} // End of the has-merge-data-been-submitted clause

function advancedmerge_oneline($field, $opts, $fieldname='', $multi=false){
   if($fieldname=='')
     $fieldname = ucwords($field);

   ?><label><strong><?php echo $fieldname ?>:</strong>
   <select name="mergedata[<?php echo $field ?>]<?php if($multi) echo '[]' ?>" <?php if($multi) echo ' multiple="multiple" size="'.sizeof($opts).'"' ?> style="vertical-align: top;"><?php
   foreach($opts as $optkey=>$opt)
     echo "\n  <option".($multi?' selected="selected"':'').">".htmlspecialchars($opt)."</option>";
   ?></select></label><?php
   if($multi) echo '<em>All fields are selected by default. Hold [Ctrl] while clicking to change selection.'
           . ($field=='deptlist'||$field=='userlist'?' <br />You are recommended NOT to remove any of the options from the '.$field.' field.':'') . '</em>';
}

function advancedmerge_nochoice($field, $opt, $fieldname='', $hide=false){
   if($fieldname=='')
     $fieldname = ucwords($field);
  ?><input type="hidden" name="mergedata[<?php echo htmlspecialchars($field) ?>]" value="<?php echo htmlspecialchars($opt) ?>" /><?php
  if((!$hide) && ($opt!='0') && ($opt!='') && ($opt!='///') && ($field != 'reftype')){
    ?><label><strong><?php echo htmlspecialchars($fieldname) ?>:</strong> <?php echo htmlspecialchars($opt) ?></label><?php
  }else{
    //echo "(\$hide=$hide) NOT DISPLAYING $field: $opt";
  }
}

?>
