<?php

require_once('../common.php');

$action = trim($_REQUEST['action']);

if($action=='')
{
  ?>
  <p><a href="./?action=lookup">Look up the identified entries, in PubMed (SLOW!)</a></p>
  <p><a href="./?action=checktitles">Check the titles of the retrieved links</a></p>
  <?php
}
if($action=='lookup')
{
  $q = "SELECT xid, PUBLICATIONSPMIDPLEASE.pubid, PUBLICATIONS.title FROM PUBLICATIONSPMIDPLEASE "
            . " LEFT JOIN PUBLICATIONS USING (pubid) WHERE pmid=0 LIMIT 20";

  $res = mysql_query($q, pmsearchconnectpubsdb());
  $results = mysql_num_rows($res);
  while($row = mysql_fetch_assoc($res))
  {
    echo "<hr /><p>Entry $row[pubid]: <em>$row[title]</em></p>";
    $r = pubMedLocate($row['title']);
    $num = sizeof($r);
    if($num==1)
      $id = intval($r[0]);
    else
      $id = -1;
    mysql_query("UPDATE PUBLICATIONSPMIDPLEASE SET pmid=$id WHERE pubid=$row[pubid] LIMIT 1", pmsearchconnectpubsdb());
    echo "<p>Entries found: <strong>$num</strong>" . ($id==-1?'':' - stored reference.') . "</p>";
  }

  ?>
  <p>&nbsp;</p>
<?php
if(sizeof($results)==20)
{
  ?>
  <p align="center"><a href="./?action=lookup">Do another batch</a></p>
<script type="text/javascript">
document.location.href="./?action=lookup&dummy=<?php echo time() ?>";
</script>
  <?php
  }
}// End of action==lookup
elseif($action=='checktitles')
{

  // If submissions have been made, process them
  $store = $_REQUEST['store'];
  if(is_array($store) && sizeof($store)>0)
  foreach($store as $k=>$v)
      if($v=='y')
	    echo 
	      reallystoreurl($k)?
		  "":
		  "<p>Entry $k <strong>failed</strong> to store! : " . mysql_error() . '</p>';
	  elseif($v=='n')
        mysql_query('DELETE FROM PUBLICATIONSPMIDPLEASE WHERE xid=' . intval($k) . ' LIMIT 1', pmsearchconnectpubsdb());



  $q = "SELECT xid, PUBLICATIONSPMIDPLEASE.pubid, PUBLICATIONSPMIDPLEASE.pmid, PUBLICATIONS.title AS title FROM PUBLICATIONSPMIDPLEASE "
            . " LEFT JOIN PUBLICATIONS USING (pubid) WHERE pmid>0 ORDER BY RAND() LIMIT 20";

  $res = mysql_query($q, pmsearchconnectpubsdb());
  $results = array();
  $ids = array();
  while($row = mysql_fetch_assoc($res))
  {
    $results[] = $row;
	$ids[] = $row['pmid'];
  }

  $pmtitles = pubMedLookUpTitles($ids);
  
  if(sizeof($pmtitles)!=sizeof($ids))
  {
    ?><p>Warning: not all titles from this batch were retrieved. Mismatch possible....</p><?php
  }
  
  ?>
  <form action="./" method="post">
  <table width="100%" border="1" cellspacing="0">
    <tr><th width="4%">OK</th>
    <th width="48%">Title on our database</th>
    <th width="48%">Title extracted from PubMed</th>
    </tr>
  <?php

  foreach($results as $k => $row)
  {
    echo "\n<tr>"
	   . "\n  <td valign=\"top\" align=\"center\"><input type=\"radio\" name=\"store[" . $row['xid'] . "]\" value=\"y\" checked=\"checked\" />"
	   . "<input type=\"radio\" name=\"store[" . $row['xid'] . "]\" value=\"n\" />"
	   . "<br />Y/N</td>"
	   . "\n  <td valign=\"top\">$row[title]</td>"
	   . "\n  <td valign=\"top\">$pmtitles[$k]</td>"
	   . "\n</tr>";
  }

  ?>
</table>
  <p align="center"><input type="submit" value="Store decisions" />
  <input type="hidden" name="action" value="checktitles" /></p>
  <?php
}// End of action==checktitles


?>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
