<?php
require_once(dirname(dirname(__FILE__)) . '/manage/config.inc.php');

function formataref($v, $userid, $showeditoptions, $detailclick, $showdeleteoption, $showeditoption=false, $showdissociateoption=false)
{
  $opts = '';
  if($showeditoptions)
    $opts .= 'edit,dissociate';
  if($showeditoption)
    $opts .= ',edit';
  if($showdissociateoption)
    $opts .= ',dissociate';
  if($showdeleteoption)
    $opts .= ',delete';
  if($detailclick!='n')
    $opts .= ',detail';
  return formataref2($v, $userid, $opts);
}
function formataref2($v, $userid, $XXXshowopts='')
{
  global $showunconfirmedentryflag, $showopts, $config;
//  echo "\n\n<!-- \$showopts = $showopts -->\n";
  $ret = '';
  if($config['debug']) $ret = 'Pubid = ' . $v[pubid] . ': ';
  if($v['year']==9999)
//    $v['year'] = ONCITE_INPRESSSTRING;
    $v['year'] = $config['inpressstring'];
  
  // Add spaces into the author listings. This helps stop authorlistings become a single massive "word" which pushes the page far too wide.
  $v['authorlist'] = preg_replace('/,(\w\w+)/', ", $1", $v['authorlist']);
  $v['secondaryauthorlist'] = preg_replace('/,(\w\w+)/', ", $1", $v['secondaryauthorlist']);
  $v['seriesauthorlist'] = preg_replace('/,(\w\w+)/', ", $1", $v['seriesauthorlist']);
	
    switch($v['reftype']){
    case 'JOUR':
    case 'ABST':
    case 'NEWS':
    case 'MGZN':
    case 'STAT':
      // Journal article
	  $journalname = strlen($v['journal'])==0 ? $v['journalabbrev'] : $v['journal'];
      $ret .=  farFormatAuthorListYear($v['authorlist'], $v['year'])
	        . ". "
            . farFormatMainTitle($v['title'])
			. " <i>$journalname</i> $v[volume]"
	        . (intval($v['issue'])>0?"($v[issue])":'') 
			. ($v['volume']!='' || $v['issue']!=''?', ':' ')
			. (strlen($v['startpage'])>0?("$v[startpage]" . (strlen($v['endpage'])>0?"-$v[endpage]":'')):'')
	        . (strlen($v['issnisbn'])>0?". ISSN: $v[issnisbn]":'')
	        ;
        break;
	 case 'JFULL':
	 case 'JFUL':
      // Full journal
	  $journalname = strlen($v['journal'])==0 ? $v['journalabbrev'] : $v['journal'];
      $ret .= farFormatAuthorListYear($v['authorlist'], $v['year'])
	        . ". "
            . farFormatMainTitle($v['title'])
            . " <i>$journalname</i>"
	        . (strlen($v['volume'])>0?", $v[volume]":'')
	        . (intval($v['issue'])>0?" ($v[issue])":'') . ''
			. ($v['volume']!='' || $v['issue']!=''?', ':' ')
			. (strlen($v['startpage'])>0?("$v[startpage]" . (strlen($v['endpage'])>0?"-$v[endpage]":'')):'')
	        . (strlen($v['issnisbn'])>0?". ISSN: $v[issnisbn]":'')
	        ;
        break;
	 case 'CONF':
	 case 'CASE':
      $ret .= farFormatAuthorListYear($v['authorlist'], $v['year'])
	        . ". "
          . farFormatMainTitle($v['secondarytitle'])
          . " <i>$v[title]</i>"
          . (preg_match('/\w/',substr($v['title'],-1))?", ":' ')
		  . (strlen($v['secondaryauthorlist'])>0?"$v[secondaryauthorlist] (ed.) ":' ')
		  . (strlen($v['seriestitle'])>0 ? "$v[seriestitle] series. ": '')
	      . (strlen($v['seriesauthorlist'])>0 ? "Series edited by $v[seriesauthorlist]. ": '')
	      . (strlen($v['city'])>0 ? "$v[city]" . (strlen($v['publisher'])>0?':':'') : ' ')
		  . $v['publisher']
          . (strlen($v['volume'])>0?", $v[volume]":'')
	      . (intval($v['issue'])>0?" ($v[issue])":'')
		  . (strlen($v['startpage'])>0?(", $v[startpage]" . (strlen($v['endpage'])>0?"-$v[endpage]":'')):'')
	        . (strlen($v['issnisbn'])>0?". ISSN: $v[issnisbn]":'')
	      ;
        break;
	 case 'CHAP':
      // Chapter in book
      $ret .= farFormatAuthorListYear($v['authorlist'], $v['year'])
	        . ". "
          . farFormatMainTitle($v['title'])
	      . (intval($v['issue'])>0?" Chapter $v[issue] in ":' in ')
		  
		  . (strlen($v['secondaryauthorlist'].$v['secondarytitle'])>0?
                /*  " In " 
	              . */(strlen($v['secondaryauthorlist'])>0 ? "$v[secondaryauthorlist] (ed.) ": ''):'')
	              . "<i>" . (strlen($v['secondarytitle'])>0 ? $v['secondarytitle']:$v['journal']) . "</i>. "
	         
		  
		  . (strlen($v['seriestitle'])>0 ? "$v[seriestitle] series. ": '')
	      . (strlen($v['seriesauthorlist'])>0 ? "Series edited by $v[seriesauthorlist]. ": '')
	      . (strlen($v['city'])>0 ? "$v[city]: ": '')
		  . $v['publisher']
          . (strlen($v['volume'])>0?', ' . addcardinal($v['volume']) . ' edition':'')
		  . (strlen($v['startpage'])>0?(", $v[startpage]" . (strlen($v['endpage'])>0?"-$v[endpage]":'')):'')
	        . (strlen($v['issnisbn'])>0?". ISBN: $v[issnisbn]":'')
	      ;
        break;
	 case 'BOOK':
      // Book
	  if($v['authorlist']=='' && $v['secondaryauthorlist']!='')
	  {
	    $v['authorlist'] = $v['secondaryauthorlist'] . ' (ed.)';
		$v['secondaryauthorlist'] = '';
	  }
      $ret .= farFormatAuthorListYear($v['authorlist'], $v['year'])
	        . ", "
          . "<i>" . farFormatMainTitle($v['title'], ',') . "</i>"
	      . (strlen($v['secondaryauthorlist'])>0 ? "$v[secondaryauthorlist] (ed.) ": '')
	      . (strlen($v['seriestitle'])>0 ? "$v[seriestitle] series. ": '')
	      . (strlen($v['seriesauthorlist'])>0 ? "Series edited by $v[seriesauthorlist]. ": '')
	      . (strlen($v['city'])>0 ? "$v[city]: ": '')
          .  "$v[publisher].";
	  if(strlen($v['issnisbn'])!=0) $ret .= " ISBN: $v[issnisbn].";
	  if($v['endpage']!=0) $ret .= " $v[endpage]pp.";
	  elseif($v['startpage']!=0) $ret .= ", $v[startpage]pp.";
        break;
	 case 'PAT':
      // Patent
      $ret .= farFormatAuthorListYear($v['authorlist'], $v['year'])
	        . ", "
          . "Patent <i>" . farFormatMainTitle($v['title']) . "</i> ";
        break;
	 case 'INTV':
      // Interview
      $ret .= farFormatAuthorListYear($v['authorlist'], $v['year'])
	        . ", "
         .  "Interview: <i>" . farFormatMainTitle($v['title']). "</i>"
	     . (strlen($v['seriestitle'])>0 ? " $v[seriestitle] series. ": '')
         . (strlen($v['seriesauthorlist'])>0 ? "Series edited by $v[seriesauthorlist]. ": '');
        break;
	 case 'COMP':
      // Computer program
      $ret .= farFormatAuthorListYear($v['authorlist'], $v['year'])
	        . ", "
         .  "<i>" . farFormatMainTitle($v['title']) . "</i>"
	     . ($v['issue']>0 ? " Version $v[issue]. ": '') 
	     . (strlen($v['seriestitle'])>0 ? " $v[seriestitle] series. ": '')
         . (strlen($v['seriesauthorlist'])>0 ? "Series edited by $v[seriesauthorlist]. ": '');
        break;
	 case 'BILL':
      // Exhibition
      $ret .= farFormatAuthorListYear($v['authorlist'], $v['year'])
	        . ", "
         .  "<i>" . farFormatMainTitle($v['title']) . "</i>"
	     . (strlen($v['seriestitle'])>0 ? " $v[seriestitle] exhibition. ": '')
         . (strlen($v['seriesauthorlist'])>0 ? "Series edited by $v[seriesauthorlist]. ": '');
        break;
	 case 'RPRT':
      // General publication display
      $ret .= farFormatAuthorListYear($v['authorlist'], $v['year'])
	        . ", "
         .  "<i>" . farFormatMainTitle($v['title']) . "</i> "
	     . (strlen($v['seriestitle'])>0 ? "$v[seriestitle] series, ": '')
	     . (strlen($v['volume'])>0 ? "No. $v[volume]. ": '')
         . (strlen($v['seriesauthorlist'])>0 ? "Series edited by $v[seriesauthorlist]. ": '')
	      . (strlen($v['city'])>0 ? "$v[city]: ": '')
	     . (strlen($v['publisher'])>0 ? " $v[publisher]. ": '');
        break;
	 default:
      // General publication display
      $ret .= farFormatAuthorListYear($v['authorlist'], $v['year'])
	        . ", "
         .  "<i>". farFormatMainTitle($v['title']) . "</i> "
	     . (strlen($v['seriestitle'])>0 ? " $v[seriestitle] series. ": '')
         . (strlen($v['seriesauthorlist'])>0 ? "Series edited by $v[seriesauthorlist]. ": '')
	        . (strlen($v['issnisbn'])>0?" ISBN/ISSN: $v[issnisbn]":'')
		 ;
      } // End of main switch statement

    $ret = str_replace('&', '&amp;', $ret); // Make sure ampersands are escaped as they should be in XHTML

    if(strpos($showopts,'detail')!==false)
	  $ret .= " [<a href=\"./?pubid=$v[pubid]&amp;action=search"
	         . (strlen($userid)>0?"&amp;userid=$userid":'')
			 . ($v['year']==$config['inpressstring']?"&amp;includeinpress=y":'') 
			 . "\">Detail</a>]";
    $link = buildthelinktext($v['url'], $v['oaiid']);
    if(strlen($link)>0)
	  $ret .= " <span class='publink'>$link</span>";
	if(intval($v['eprint'])>0)
	  $ret .= " [<a href='$config[eprints_prefix]" .
	             str_pad($v['eprint'], 8, "0", STR_PAD_LEFT) . "/'>$config[institutionname] eprint</a>]";
     if($v['DOI'] && ($doilinktext = translateDoiToLink($v['DOI'], 'DOI link')))
	  $ret .= ' ['.$doilinktext.']';
	if(strpos($showopts,'edit')!==false)
	  $ret .= " [<a href=\"./?pubid=$v[pubid]&amp;action=edit"
	         . (strlen($userid)>0?"&amp;userid=$userid":'') . "&amp;dummy=" . time() . "&amp;returnname=the%20list%20view&amp;returnurl=".rawurlencode($_ENV['REDIRECT_SCRIPT_URI'].'?'.$_ENV['QUERY_STRING'])."\">Edit</a>]";
	if(strpos($showopts,'dissociate')!==false  && !$v['personalonly'])
	  $ret .= " [<a href=\"./?pubid=$v[pubid]&amp;action=dissociate&amp;dummy=" . time()
	         . (strlen($userid)>0?"&amp;userid=$userid":'') . "\">Delete from personal list</a>]";
/*
	if(strpos($showopts,'notinmydept')!==false)
	  $ret .= " [<a href=\"./?pubid=$v[pubid]&amp;action=notinmydept&amp;dummy=" . time()
	         . (strlen($userid)>0?"&amp;userid=$userid":'') . "\">Remove&nbsp;from&nbsp;my&nbsp;department</a>]";
*/
	if((strpos($showopts,'delete')!==false)
	                   || 
					         ( // The originator is allowed to delete unapproved/personalonly pubs
							      ($v['deptlist']==',') 
								  && ($_SERVER['REMOTE_USER']!='')
								  && ($_SERVER['REMOTE_USER']==$v['originator'])
							 )) {
       // Decide whether the LABEL for the link indicates complete deletion or not. NB complete deletion may happen anyway - if all depts are removed by an admin.
	  $deleteentirely = $v['personalonly'] || (function_exists('isGlobalAdmin') && isGlobalAdmin($_SERVER['REMOTE_USER']));

	  $ret .= " [<a href=\"javascript:if(confirm('Are you sure you want to do this?"
			 . ($deleteentirely?'':"\\n\\nThis will remove it "
			 . "from your department\\'s listings."
			 . "\\n(It will be COMPLETELY DELETED if it is associated with no other departments.)"
			 . "\\n\\nIf you just want to remove it from personal "
			 			. "listings, please choose Dissociate instead.")
			 . "')) { location.href='./?pubid=$v[pubid]&amp;action=delete&amp;dummy=" . time()
	         . (strlen($userid)>0?"&amp;userid=$userid":'') . "&amp;returnurl=" . urlencode($_SERVER['REQUEST_URI']) . "'}\">Delete " 
		             . ($deleteentirely?"entirely":"from dept list") . "</a>]";
	}
	if(strpos($showopts,'edit')!==false && $v['reftype']=='JOUR' && $v['url']=='')
	  $ret .= " [<a href=\"$config[scriptshomeurl]manage/pubmedlocate/?title=" . rawurlencode($v['title']) . "&pubid=$v[pubid]\" target=\"_blank\" onclick='window.open(\"$config[scriptshomeurl]manage/pubmedlocate/?title=" . rawurlencode($v['title']) . "&pubid=$v[pubid]\",\"pubmedlocator\",\"width=440,height=400,\"); return false'>Automatic&nbsp;PubMed&nbsp;lookup</a>]";
	if(strpos($showopts,'duplication')!==false)
	  $ret .= " [<a href=\"" . $config['pageshomeurl'] . "manage/duplicates/singleduplicate.php?pubid=$v[pubid]\">Manage&nbsp;duplication</a>]";
	if(strpos($showopts,'superadmin')!==false)
	  $ret .= " [<a href=\"$config[pageshomeurl]manage/superadmin/?action=querylog&pubid=$v[pubid]\">Audit trail</a>]";

//  if((!$v['personalonly']) && (strlen($v['approvedby'])<4) && $showunconfirmedentryflag!='n' && $v['year']>1900 && $v['year']<9000)
//    $ret .= " <strong>[N.B. Entry not yet approved]</strong>";
  if((preg_match('/\w/', $v['pendingdepts']) && !preg_match('/\w/', $v['deptlist']))
                          && $showunconfirmedentryflag!='n' && $v['year']>1900 && $v['year']<9000)
    $ret .= " [N.B. Deparmental association(s) pending approval]";

  // This command (which removes dodgy characters exported from RefMan) should be thought of as a temporary hack
  // - much better to remove the dodgy characters while importing, and to purge them from existent records.
  // The characters arise from RefMan's way of handling formatting (italic, bold, etc) and also something to do with URLs
  $ret = preg_replace('/[\x00-\x1F]/', '', $ret);

  // This attempts to fix the extended character set problems of displaying "HTML entities"...
  $ret = preg_replace('/&amp;#(\d+);/', "&#$1;", $ret);

  return $ret;
}


function buildthelinktext($url, $oaiid='')
{
  global $config;
  $pm=false;
  if(trim($url)=='' && trim($oaiid)=='')
    return '';

  $ret = '';
  $eprinted = false;
  // Add a link to eprints if possible
  if(preg_match('/(\d+)$/', $oaiid, $matches)){
    $ret .= "[<a href=\"$config[eprints_prefix]$matches[1]\">Eprint</a>] ";
    $eprinted = true;
  }

  if(strtolower(substr(trim($url),0,3)) == 'pm:')
  {
    $furl = farConvertPubMedLink($url);
    $pm=true;
  }
//  else if(strcasecmp(substr(trim($url),0,4),'isi:')==0)
//    $furl = "http://www.isi.com............" . substr(trim($url),4);
  else
    $furl = str_replace('&', '&amp;', trim($url));

  if($pm)
    $ret .= "<a href=\"$furl\"><img src=\"$config[imageshomeurl]pm.gif\" alt=\"PubMed link\" align=\"top\" border=\"0\" /></a>";
  else if(strtolower(substr($furl,-3))=='pdf')
    $ret .= "<a href=\"$furl\"><img src=\"$config[imageshomeurl]pdf.gif\" alt=\"PDF link\" align=\"top\" border=\"0\" /></a>";
  else if(strtolower(substr($furl,-3))=='doc')
    $ret .= "<a href=\"$furl\"><img src=\"$config[imageshomeurl]doc.gif\" alt=\"Microsoft Word document link\" align=\"top\" border=\"0\" /></a>";
  else if(strpos(trim($url), $config['eprints_prefix'])===0){
    if(!$eprinted)
      $ret .= "[<a href=\"$furl\">Eprint</a>]";
  }
  else
    $ret .= "[<a href=\"$furl\">Online</a>]";
  return $ret;
}

function farConvertPubMedLink($url){
  return "http://www.ncbi.nlm.nih.gov/entrez/query.fcgi?cmd=Retrieve&amp;db=PubMed&amp;list_uids=" . preg_replace('/\D+/','',substr(trim($url),3)) . "&amp;dopt=Abstract";
}

function addcardinal($str)
{
  $last = substr($str,-1);
  if(!is_numeric($last))
    return $str;
  switch(intval($last))
  {
    case 1:
	  return $str . 'st';
	case 2:
	  return $str . 'nd';
	case 3:
	  return $str . 'rd';
	default:
	  return $str . 'th';
  }
}

function translateDoiToLink($doi, $linktext='')
{ // DOI references look like this: doi:10.1186/ar1480
  // And we can look them up by using URL http://dx.doi.org/10.1186/ar1480
  if(preg_match('/^doi:\s*(.*)$/', $doi, $matches))
    return "<a href=\"http://dx.doi.org/" . htmlspecialchars($matches[1]) . "\">" . htmlspecialchars($linktext?$linktext:$doi) . "</a>";
  else
    return false;
}

// Function to do the standard "authorlist year" formatting
function farFormatAuthorListYear($authorlist, $year){
  return "<span class='pubauth'>$authorlist</span> <span class='pubyear'>($year)</span>";
}
function farFormatMainTitle($title, $punctuation = '.'){
  return "<span class='pubtitle'>$title" . (preg_match('/\w/',substr($title,-1))?$punctuation:'') . "</span>";
}

?>
