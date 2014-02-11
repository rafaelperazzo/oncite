<?php
// require_once('/nfs/rcs/www/webdocs/lifesciences-faculty/publications/manage/config.inc.php');
require_once(dirname(dirname(__FILE__)) . '/config.inc.php');

$pmsearchuniversaldatabaseconnectionthingy = null;
function pmsearchconnectpubsdb()
{
  global $pmsearchuniversaldatabaseconnectionthingy, $config;

  if($pmsearchuniversaldatabaseconnectionthingy!=null)
    return $pmsearchuniversaldatabaseconnectionthingy;

  $dbcon = mysql_connect($config['db_addr'], $config['db_user'], $config['db_pass']);
  if($dbcon)
  {
    mysql_select_db($config['db_db'], $dbcon);
    $pmsearchuniversaldatabaseconnectionthingy = $dbcon;
  }

  return $dbcon;
}
pmsearchconnectpubsdb();


$userpmurl = 'http://www.ncbi.nlm.nih.gov/entrez/query.fcgi?cmd=Retrieve&db=PubMed&dopt=Abstract&list_uids=';
$baseeutilsurl = 'http://eutils.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?db=PubMed&field=titl&tool=myopia&email=' . $config['webmasteremail'];
$baseesummaryurl = 'http://eutils.ncbi.nlm.nih.gov/entrez/eutils/esummary.fcgi?db=pubmed&tool=myopia&email=' . $config['webmasteremail'] . '&retmode=xml&id=';

function pubMedLocate($title)
{
  global $userpmurl, $baseeutilsurl;

  $eutilsurl = $baseeutilsurl . "&term=\"" . strip_tags($title) . "\"";
  $ret = getRequest($eutilsurl);

  if(preg_match_all('|<Id>(\d+)</Id>|', $ret, $matches))
    return $matches[1];
  else
    return array();
}

function pubMedLookUpTitles($id) // The $id variable should be an array of the ids we want to look up
{
  global $userpmurl, $baseesummaryurl;

  foreach($id as $k=>$v)
    $id[$k] = intval($v);

  $eutilsurl = $baseesummaryurl . implode(',', $id);
  $ret = getRequest($eutilsurl);

  $reta = array();

  if(preg_match_all('|<DocSum>.*<Id>(.*)</Id>.*<Item Name="Title" Type="String">(.*)</Item>.*</DocSum>|Us', $ret, $matches, PREG_SET_ORDER))
  {
    foreach($matches as $match)
	  $reta[] = $match[2];    
  }

  if(0)
  {
    echo "<h2>eSummary result:</h2><pre>";
    print_r($matches);
    //  echo htmlspecialchars($ret);
    echo "</pre>";
  }
  return $reta;
}



function getRequest($url)
{
  // Make a GET request - N.B. It MUST be a full http URL. This script doesn't check that.
  // Returns the CONTENT of the page that is returned (i.e. not the headers)

  $url = str_replace(' ','%20',$url);
//  $url = urlencode($url);

//  echo "<p>About to request $url</p>";

  $temp = explode('/',$url,4);
  $servername = $temp[2];
  $filepath = '/' . $temp[3];

  $con = fsockopen($servername,80);
  if(!$con)
  {
    die("<p>Our servers have been unable to make connection to the PubMed website.
     Please accept our apologies for this inconvenience!<br /><br />$url</p>");
  }

  $post = "GET $filepath HTTP/1.1
Host: $servername

";
//  echo "<pre>";
//  print_r($post);
//  echo "</pre>";
  $post = str_replace("\n","\r\n",$post);
  fwrite($con,$post);

  $return = '';
  while (!feof($con))
    $return .= fgets ($con,128);
  $return = explode("\r\n\r\n",$return,2);
  $return = trim($return[1]);
  fclose ($con);
  return $return;
}

function storeurl($pubid, $url)
{
  $q = "UPDATE PUBLICATIONS SET url='" . mysql_real_escape_string($url) . "' WHERE pubid=" . intval($pubid) . " LIMIT 1";
  return mysql_query($q, pmsearchconnectpubsdb());
}

function reallystoreurl($xid)
{
  $xid = intval($xid);

  $q = "SELECT * FROM PUBLICATIONSPMIDPLEASE WHERE xid=$xid AND pmid>0 LIMIT 1";
  if($res = mysql_query($q, pmsearchconnectpubsdb()))
  {
    $row = mysql_fetch_assoc($res);
	$pmurl = 'pm:' . intval($row['pmid']);	
    $q = "UPDATE PUBLICATIONS SET url=('$pmurl') WHERE (url='' AND pubid=$row[pubid]) LIMIT 1";
    return mysql_query($q, pmsearchconnectpubsdb())
        && mysql_query("DELETE FROM PUBLICATIONSPMIDPLEASE WHERE xid=$xid LIMIT 1", pmsearchconnectpubsdb());
  }
  else return false;
}

?>