<?php

require_once(dirname(dirname(__FILE__)) . '/formataref.php');

function output_bibtex($data){
  @header("Content-Type: text/plain");
  ?>@COMMENT {{ Generated by OnCite, an open-source project from UCL. BibTeX export is still experimental so error reports would be welcome. }}

<?php
  echo recordsToBibtex($data);
}

function recordsToBibtex($d)
{
  $ret = '';
  foreach($d as $p)
    $ret .= oneRecordToBibtex($p);
  return $ret;
}


function oneRecordToBibtex($p)
{
  $ret = "\n@"; // Start the entry with its @ sign
  
  // Now output the ref type
  switch($p['reftype'])
  {
    case 'ABST': $ret .= 'misc'; break;
    case 'ADVS': $ret .= 'misc'; break;
    case 'ART':  $ret .= 'misc'; break;
    case 'BILL': $ret .= 'misc'; break;
    case 'BOOK': $ret .= 'book'; break;
    case 'CASE': $ret .= 'conference'; break;
    case 'CHAP': $ret .= 'inbook'; break;
    case 'COMP': $ret .= 'misc'; break;
    case 'CONF': $ret .= 'conference'; break;
    case 'CTLG': $ret .= 'misc'; break;
    case 'DATA': $ret .= 'misc'; break;
    case 'GEN':  $ret .= 'misc'; break;
    case 'HEAR': $ret .= 'misc'; break;
    case 'JFUL': $ret .= 'article'; break;
    case 'JOUR': $ret .= 'article'; break;
    case 'MAP':  $ret .= 'misc'; break;
    case 'MGZN': $ret .= 'misc'; break;
    case 'MPCT': $ret .= 'misc'; break;
    case 'MUSI': $ret .= 'misc'; break;
    case 'NEWS': $ret .= 'misc'; break;
    case 'PAMP': $ret .= 'misc'; break;
    case 'PAT':  $ret .= 'patent'; break;
    case 'PCOM': $ret .= 'misc'; break;
    case 'RPRT': $ret .= 'techreport'; break;
    case 'SER':  $ret .= 'misc'; break;
    case 'SLID': $ret .= 'misc'; break;
    case 'SOUN': $ret .= 'misc'; break;
    case 'STAT': $ret .= 'misc'; break;
    case 'THES': $ret .= 'misc'; break;
    case 'UNBI': $ret .= 'misc'; break;
    case 'VIDE': $ret .= 'misc'; break;
  }
  
  $ret .= "{OnCite$p[pubid],\n"; // Opening brace for the whole entry, plus the index number
  
  $addfields = array();
  foreach($p as $k=>$v)
  {
    if($v=='') continue;
    $v = bibtex_convertstringentities($v);
    switch($k)
	{
	  case 'authorlist':
	    $authors = bibtex_splitauthorstring($v);
//         $addfields[] =  "   NOTauthor = \{$v}";
//	    foreach($authors as $vv)
//	      $addfields[] =  "   author = \{$vv}";
         $addfields[] =  '   author = {' . implode(' and ', $authors) . '}';
	    break;
	  case 'secondaryauthorlist':
	    $authors = bibtex_splitauthorstring($v);
//         $addfields[] =  "   NOTauthor = \{$v}";
//	    foreach($authors as $vv)
//	      $addfields[] =  "   editor = \{$v}";
         $addfields[] =  '   editor = {' . implode(' and ', $authors) . '}';
	    break;
	  case 'startpage':
	    if($p['endpage']>0)
		  $v .= "--$p[endpage]";
	    $addfields[] =  "   pages = \{$v}";
	    break;
	  case 'city':
	    $addfields[] =  "   location = \{$v}";
	    break;
	  case 'url':
	    if(strtolower(substr(trim($v),0,3))=='pm:')
           $v = farConvertPubMedLink($v);
	    $addfields[] =  "   URL = \{$v}";
	    break;
	  case 'secondarytitle':
	    if($p['reftype']=='CHAP')
	      $addfields[] =  "   booktitle = \{".bibtex_addbracketstocapitals($v)."}";
	    break;
	  case 'issue':
	    if($p['reftype']=='CHAP')
	      $addfields[] =  "   chapter = \{$v}";
	    elseif($p['reftype']=='CONF' || $p['reftype']=='CASE')
	      $addfields[] =  "   edition = \{$v}";
	    break;
	  case 'volume':
	    if($p['reftype']=='ADVS'  || $p['reftype']=='ART'  || $p['reftype']=='CHAP'  ||
		   $p['reftype']=='CTLG'  || $p['reftype']=='MAP'  || $p['reftype']=='MUSI'  || $p['reftype']=='NEWS'  || 
		   $p['reftype']=='PAMP')
	      $addfields[] =  "   edition = \{$v}";
	    elseif($p['reftype']=='ABST'  || $p['reftype']=='BOOK'  || $p['reftype']=='CASE'  || $p['reftype']=='CONF'  || 
		   $p['reftype']=='GEN'  || $p['reftype']=='JFUL'  || $p['reftype']=='JOUR'  || $p['reftype']=='MGZN'  || 
		   $p['reftype']=='SER'  || $p['reftype']=='STAT' )
	      $addfields[] =  "   volume = \{$v}";
	    break;
	  case 'issnisbn':
	    if($p['reftype']=='JOUR'  || $p['reftype']=='JFUL'  || $p['reftype']=='MGZN'  ||
		   $p['reftype']=='CASE'  || $p['reftype']=='CONF' )
	      $addfields[] =  "   issn = \{$v}";
	    elseif($p['reftype']=='BOOK'  || $p['reftype']=='CHAP'  || $p['reftype']=='MUSI' )
	      $addfields[] =  "   isbn = \{$v}";
	    break;
	  case 'journalabbrev':
         $jabbrev = $v;
         break;
	  // These fields are the very easiest to output! :)
	  case 'title':
	    $addfields[] =  "   $k = \{".bibtex_addbracketstocapitals($v)."}";
	    break;
	  case 'journal':
         $journalincluded = true; // Just so we know not to include the jabbrev
	  case 'abstract':
	  case 'keywords':
	  case 'publisher':
	  case 'year':
	    $addfields[] =  "   $k = \{$v}";
	    break;
	} // End of switch
  } // End of foreach field

  if($jabbrev && !$journalincluded){
    $addfields[] =  "   journal = \{$jabbrev}";
  }
  
  $ret .= implode(",\n", $addfields);
  
  $ret .= "\n}\n"; // Closing brace for the whole entry
  return $ret;
}

function bibtex_splitauthorstring($s){
  if(preg_match_all('/\s*([^,]{1,},\s*[\w\.]{1,})\s*/', $s, $matches, PREG_PATTERN_ORDER))
    return $matches[1];
  else
    return array($s);
}

function bibtex_convertstringentities($s) { // Looks for HTML superscript/subscript and converts them. Also converts spaced dashes to --- (em dash)
//  $s = preg_replace('|<sup>(.*?)</sup>|', "\{^\{$1}}", $s);
//  $s = preg_replace('|<sub>(.*?)</sub>|', "\{_\{$1}}", $s);
  $s = preg_replace('|<sup>(.*?)</sup>|', "\$^$1\$", $s);
  $s = preg_replace('|<sub>(.*?)</sub>|', "\$_$1\$", $s);
  $s = preg_replace('| - |', " --- ", $s);
  return $s;
}

function bibtex_addbracketstocapitals($s) { // Used for titles
  $s = preg_replace('|(?<!^)([A-Z]+)|', "\{$1}", $s);
  return $s;
}

?>