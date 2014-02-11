<?php

error_reporting(E_ALL ^ E_NOTICE);

require_once(dirname(dirname(__FILE__)) . '/manage/config.inc.php');

$utcrightnow = timestampToUtc(time());


function timestampToUtc($time)
{
  return date('Y-m-d', $time) . 'T' . date('H:i:s', $time) . 'Z';
}

function utcToUnixStamp($utc)
{
  preg_match('/^(\d\d\d\d)-(\d\d)-(\d\d)/', $utc, $m);
//  echo "<!-- utcToUnixStamp: " .  print_r($m, true) . " -->";
  return mktime(0, 0, 0, intval($m[2]), intval($m[3]), intval($m[1]));
}
function utcToMysqlStamp($utc)
{
  preg_match('/^(\d\d\d\d)-(\d\d)-(\d\d)/', $utc, $m);
  return "$m[1]$m[2]$m[3]";
}

function mySqlStampToSimple($t)
{
  return substr($t, 0, 4) . '-' . substr($t, 4, 2) . '-' . substr($t, 6, 2);
}

function oaiErrorXml($errortype, $errormessage)
{
  global $utcrightnow, $config;
    ?><?xml version="1.0" encoding="utf-8"?>
<?xml-stylesheet type='text/xsl' href='<?php echo $config['scriptshomeurl'] ?>oai/oai2.xsl' ?>
<OAI-PMH xmlns="http://www.openarchives.org/OAI/2.0/" 
         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/
         http://www.openarchives.org/OAI/2.0/OAI-PMH.xsd">
  <responseDate><?php echo $utcrightnow ?></responseDate>
  <request>http://<?php echo htmlspecialchars($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?></request>
  <error code="<?php echo utf8_encode($errortype) ?>"><?php echo utf8_encode($errormessage) ?></error>
</OAI-PMH><?php
}

function dublinCoreXml($row){
  global $config;
  $ret = '<oai_dc:dc 
          xmlns:oai_dc="http://www.openarchives.org/OAI/2.0/oai_dc/" 
          xmlns:dc="http://purl.org/dc/elements/1.1/" 
          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
          xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/oai_dc/ 
          http://www.openarchives.org/OAI/2.0/oai_dc.xsd">
        <dc:identifier>' . $config['pageshomeurl'] . '?action=search&amp;pubid=' . $row['pubid'] . '</dc:identifier>
';

if($row['reftype']=='CONF' || $row['reftype']=='CASE'){
  $ret .= xmlTagIfData('dc:title', $row['secondarytitle']);
  $ret .= xmlTagIfData('dc:relation.isFormatOf', $row['title']);
}else{
  $ret .= xmlTagIfData('dc:title', $row['title']);
}
$ret .= xmlTagIfData('dc:creator', $row['authorlist']);
$ret .= xmlTagIfData('dc:type', $row['reftype']);
$ret .= xmlTagIfData('dc:subject', $row['keywords']);
$ret .= xmlTagIfData('dc:description', $row['abstract']);
$ret .= xmlTagIfData('dc:date', $row['year']);
$ret .= xmlTagIfData('dc:source', $row['issnisbn']);
$ret .= xmlTagIfData('dc:identifier', $row['DOI']);
if(strlen($row['startpage'].$row['endpage'])>0)
  $ret .= xmlTagIfData('dc:relation.isFormatOf.pageRange', $row['startpage'].'-'.$row['endpage']);
$ret .= xmlTagIfData('dc:relation.isFormatOf.number', $row['issue']);

switch($row['reftype']){
  case 'JOUR':
  case 'JFUL':
	// For journal articles
	$ret .= xmlTagIfData('dc:relation.isFormatOf', $row['journal']);
	$ret .= xmlTagIfData('dc:relation.isFormatOf.volume', $row['volume']);
	break;
  case 'CHAP':
	$ret .= xmlTagIfData('dc:relation.isFormatOf', $row['secondarytitle']);
	break;
}

  return $ret . '</oai_dc:dc>';
}

function xmlTagIfData($tagname, $data){
  if(strlen($data)>0)
    return "\n<$tagname>". utf8_encode(htmlspecialchars($data)) . "</$tagname>";
  else
    return '';
}

function writeSingleOaiItem($row, $headersonly=false){
  ?>
 <record>
   <header>
    <identifier>oai:ucl.ac.uk:MyOPIA_<?php echo $row['pubid'] ?></identifier>
    <datestamp><?php echo mySqlStampToSimple($row['timestamp']) ?></datestamp>
<?php

outputSetInfo($row);

?>
   </header>
   <?php if(!$headersonly){
     ?><metadata>
     <?php echo dublinCoreXml($row); ?>
     </metadata><?php
   }
   ?>
 </record>
  <?php
}

function outputSetInfo($row)
{
        $depts = preg_split('/[, \.]+/', $row['deptlist']);
        foreach($depts as $d)
          if(strlen($d)>0)
                echo utf8_encode("\n   <setSpec>dept:" . str_replace('/', '.', $d) . "</setSpec>");

        $userids = preg_split('/[, \.]+/', $row['userlist']);
        foreach($userids as $u)
          if(strlen($u)>0)
                echo utf8_encode("\n   <setSpec>user:$u</setSpec>");
}


?>
