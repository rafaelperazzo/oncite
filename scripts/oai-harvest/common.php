<?php

require_once(dirname(dirname(__FILE__)) . '/manage/config.inc.php');
require_once($config['homedir']  . 'query/formataref.php');
require_once($config['homedir']  . 'manage/common.php');

class xmlElement
{
  var $name = '';
  var $attributes = array();
  var $content = array();

  function xmlElement($myname = '', $myattributes = array(), $mycontent = array())
  {
    $this->name = $myname;
	$this->attributes = $myattributes;
	$this->content = $mycontent;
  }
}


$file = $_REQUEST['file'];
$elements = $stack = array();
$total_elements = $total_chars = 0;

$submitted = $_REQUEST; // The "$submitted" parameter normally comes from POST/GET data
                        //   but can be used in other ways

$internaloaielementstack = array(); // Use push and pop to maintain a context list in this array

$token = '';
$records = array();
$currentrecord = array();
$unidentifieddepts = array();
$depts = getDepts();
$sets = listSets();
$autoresume = true;
$autoresumedelay = 5;
$oaiimportnote = "Imported via OAI, " . date('G:i:s jS M Y');
$deptsdropdownoptions = '\n<option value="">Ignore for now</option>\n<option value="-1">Ignore FOR EVER - NEVER interpret this as a potential department code</option>\n<option value="">----------------------------------------------------</option>';
foreach($depts as $dept)
  $deptsdropdownoptions .= "\n<option value=\"" . htmlspecialchars($dept['DEPTID']) . "\">" 
                              . htmlspecialchars($dept['NAME']) . "</option>";

$fieldtranslations = array(
              'identifier'=>'oaiid'
              ,'datestamp'=>'oaidatestamp'
              ,'dc:title'=>'title'
              ,'dc:creator'=>'authorlist'
              ,'dc:contributor'=>'secondaryauthorlist'
              ,'dc:description'=>'abstract'
              ,'dc:date'=>'year'
              ,'dc:identifier'=>'url'
              ,'dc:type'=>'dc:type'
              ,'dc:subject'=>'dc:subject'
              ,'setSpec'=>'setSpec'
			  ,'dc:relation.isFormatOf'=>'secondarytitle'
			  ,'dc:publisher'=>'publisher'
			  ,'dc:source'=>'issnisbn'
			  ,'dc:relation.isFormatOf.pagerange'=>'startpage'  // Remember that this must be decomposed into start & end
              ,'dc:relation.isFormatOf.volume'=>'volume'
              ,'dc:relation.isFormatOf.number'=>'issue'
              ,'dc:relation.isFormatOf.series'=>'seriestitle'
              ,'dc:relation.isFormatOf.place_of_pub'=>'city'
              ,'dc:relation.isFormatOf.location'=>'city'
              ,'dc:relation.isFormatOf.id_number'=>'DOI'
      //        ,''=>''
      //        ,''=>''
      //        ,''=>''
      //        ,''=>''
      //        ,''=>''
			  );

if(!isset($interactive))
  $interactive = $_REQUEST['interactive'];


function getRecentRecords()
{
  getRecordsSince(getDatestampForQueryingOai());
}
function getRecordsSince($datestamp)
{
  global $config, $autoresume, $autoresumedelay, $interactive;
  set_time_limit(120);
  $newtoken = processXmlUrl($config['oai_baseurl'] . '?verb=ListRecords&metadataPrefix=oai_dc'
                       . (strlen($datestamp)>0?'&from=' . $datestamp : ''));
  if($newtoken)
  {
    if($autoresume)
	{
	  if($interactive!='n')
	    echo "<p>Resumption token found ($newtoken). Please wait...</p>";
	  flush();
	  ob_flush();
	  sleep($autoresumedelay);
	  if($interactive!='n')
	    echo "<p> ...resuming now.</p>";
	  flush();
	  ob_flush();
	  resumeGetRecentRecords($newtoken);
	}
	else
      handleNewResumptionToken($newtoken);
  }
  else
  {
    if($interactive!='n')
	  echo "<p>No resumption token found - finished!</p>";
	else
	   echo "\nNo resumption token found - OAI import has loaded all data, will now store this.\n";
    storeDatestampForQueryingOai(); // UNCOMMENT THIS WHEN WE'RE READY FOR THINGS TO BE STORED ONCE ONLY!
    if($interactive!='n')
      displayRetrievedRecords();
	storeRetrievedRecords();
  }
}
function resumeGetRecentRecords($token)
{
  global $config, $autoresume, $autoresumedelay, $interactive;
  set_time_limit(120);
  $newtoken = processXmlUrl($config['oai_baseurl'] . '?verb=ListRecords&resumptionToken=' . $token);
  if($newtoken)
  {
    if($autoresume)
	{
	  if($interactive!='n')
	    echo "<p>Resumption token found ($newtoken). Please wait...</p>";
	  flush();
	  ob_flush();
	  sleep($autoresumedelay);
	  if($interactive!='n')
	    echo "<p> ...resuming now.</p>";
	  flush();
	  ob_flush();
	  resumeGetRecentRecords($newtoken);
	}
	else
      handleNewResumptionToken($newtoken);
  }
  else
  {
    if($interactive!='n')
	  echo "<p>No resumption token found - finished!</p>";
	else
	   echo "\nNo resumption token found - OAI import has loaded all data, will now store this.\n";
    storeDatestampForQueryingOai(); // UNCOMMENT THIS WHEN WE'RE READY FOR THINGS TO BE STORED ONCE ONLY!
    if($interactive!='n')
      displayRetrievedRecords();
	storeRetrievedRecords();
  }
}

// This function will STORE the records in the database - as long as they are PUBLISHED
//
//     FIXME: IT SHOULD ALSO CHECK THE "oaidatestamp" against the existing entry's datestamp, etc
//
function storeRetrievedRecords()
{
  global $records, $config, $oaiimportnote, $interactive;
  
  $numstored = $numupdated = 0;
  
  foreach($records as $fullrecord)
    if($fullrecord['published'] && !isset($fullrecord['idduplicate']))
    {
       // First remove the fields that aren't expected by the generic "insert" function
	   $record = trimRecordForInserting($fullrecord);

	   // This will make sure the record goes in as already approved
	//   $record['approvedby'] = 'OAI_imp';
	   
	   // Use the main common.php's standard data-insertion function
//	   if($config['debug'])
//	     echo "<pre>" . print_r($record, true) . "</pre>";
	   if(insertentry($record))
	     $numstored++;
       
	   if($interactive=='n')
	     echo "\nStored new record " . mysql_insert_id(connectpubsdb());
    }
    elseif($fullrecord['published']) // but there may be a duplicate listed
    {
      // An ID-duplicate has been detected. So we make use of the already-calulated $record['idduplicatedatecomp'] to see whether we should overwrite
	  // By checking for +1, we're only overwriting if the OAI record is definitely newer
      if($fullrecord['idduplicatedatecomp'] == +1)
	  {
	    $updateid = intval($fullrecord['idduplicate']['pubid']);
		
        // First remove the fields that aren't expected by the generic "insert" function
	    $record = trimRecordForInserting($fullrecord);
	    // Things that OAI should NEVER OVERWRITE - esp the deptlist
	    unset($record['oaiid'], $record['reftype'], $record['notes'], $record['deptlist']);
	   
	    // Now go through, field by field, making changes if necessary
	    foreach($record as $key=>$value)
	    {
	      if(trim($value)!='')
	      {
	        $q = "UPDATE PUBLICATIONS SET " . mysql_real_escape_string($key) . "='" . mysql_real_escape_string($value) . "' WHERE pubid=$updateid LIMIT 1";
		    //echo "<p>$q</p>";
		    mysql_query($q, connectpubsdb());
  	        if($interactive=='n')
	          echo "\nUpdated record $updateid";
	      }
        }
	    // APPEND a note about the import
	    $q = "UPDATE PUBLICATIONS SET notes=CONCAT(notes, '; \\n" . mysql_real_escape_string($fullrecord['notes']) . "') WHERE pubid=$updateid LIMIT 1";
	    //echo "<p>$q</p>";
	    mysql_query($q, connectpubsdb());
	    $numupdated++;
	  }
	  
	  
    } // End of foreach OAI record
  if($interactive!='n')
    echo "<p>Stored $numstored new records, updated $numupdated existing records. <a href=\"../?action=querybynotes&q=" 
              . rawurlencode($oaiimportnote) 
			  . "\">To view them click here</a>.</p>";
  else
    echo "\nStored $numstored new records, updated $numupdated existing records.\n---------------------------------";
}

function trimRecordForInserting($record)
{
  unset($record['dc:title'], $record['identifier'], $record['datestamp'], $record['setSpec'], 
	         $record['dc:creator'], $record['dc:subject'], $record['dc:publisher'], $record['dc:date'], 
			 $record['dc:type'], $record['dc:identifier'], $record['dc:isPartOf'], 
			 $record['dc:contributor'], $record['dc:description'], 
			 $record['published'], $record['oaidatestamp'], 
			 $record['idduplicate'], $record['idduplicatedatecomp']
			 ,$record['dc:source']
			 ,$record['dc:relation.isFormatOf']
			 ,$record['dc:relation.isFormatOf.pagerange']
			 ,$record['dc:relation.isFormatOf.volume']
			 ,$record['dc:relation.isFormatOf.number']
			 ,$record['dc:relation.isFormatOf.series']
			 ,$record['dc:relation.isFormatOf.place_of_pub']
			 ,$record['dc:relation.isFormatOf.location']
			 ,$record['dc:relation.isFormatOf.id_number']
			 );
  return $record;
}

function displayRetrievedRecords()
{
  global $internaloaielementstack, $records, $currentrecord, $showunconfirmedentryflag, 
            $token, $unidentifieddepts, $deptsdropdownoptions, $_REQUEST, $config, $oaiimportnote;

	  echo "<p>The note that will be added to these records is &quot;$oaiimportnote&quot;</p>";
	  echo "\n<h3>" . sizeof($records) . " records found:</h3>";
	  echo "\n<ul class='publicationsul'>";
	  $showunconfirmedentryflag = 'n';
	  foreach($records as $record)
	  {
		echo "\n  <li" . ($record['published']?'':' style="color: gray;"') . ">";
		echo formataref2($record, '', '') 
				. "<br /><i>OAI datestamp = $record[oaidatestamp]</i>"
				. "<br /><i>OAI identifier = <a href=\"" 
//				. preg_replace('/^(.+)?\?.*$/', "$1", $file) // Assume a live OAI URL, change it so we can call GetRecord on it
				. $config['oai_baseurl']
				. "?verb=GetRecord&metadataPrefix=oai_dc&identifier=$record[oaiid]\">$record[oaiid]</a></i>"
		//		. "<br /><i>Department(s) = " . implode(', ', $record['dc:subject']) . "</i>"
				. "<br /><i>Department code(s) = $record[deptlist]</i>"
				. "<br />This <strong>" . ($record['published']?"is":"isn't") . "</strong> marked as published"
				. "<br /><i>DC type(s) = " . implode(', ', $record['dc:type']) . "</i>"
				. (isset($record['idduplicate'])?"<br /><i>A record exists in MyOPIA with matching identifier - in MyOPIA it's record #" . $record['idduplicate']['pubid'] . ", timestamp " . date('Y-m-d', $record['idduplicate']['utimestamp']) . " (Datestamp comparison gives " . $record['idduplicatedatecomp'] . ")</i>":'')
				. "<br /><i>RefMan ref type = $record[reftype]</i>";
		echo "</li>";
	  }
	  echo "\n</ul>";

}

// This function must RETURN a resumption token if it finds one. Otherwise it returns FALSE
function processXmlUrl($file)
{
  global $internaloaielementstack, $records, $currentrecord, $showunconfirmedentryflag, 
            $token, $unidentifieddepts, $deptsdropdownoptions, $_REQUEST, $config, $oaiimportnote, $interactive;

  $token = '';

  if($interactive!='n')
    echo "<p>Parsing <a href=\"$file\">$file</a></p>";
  else
    echo "\nParsing $file";

  // Initialise variables
  $internaloaielementstack = array();

  // Initialise the XML parser
  $parser = xml_parser_create();
  xml_set_element_handler($parser, 'oaiparser_start_element', 'oaiparser_stop_element');
  xml_set_character_data_handler($parser, 'oaiparser_char_data');
  xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
  xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, 'ISO-8859-1');
  
  //Parse the file
  $ret = parseOaiXmlFromFile($parser, $file);
  if(!$ret)
    die(sprintf("XML error: %s at line %d", xml_error_string(xml_get_error_code($parser)),
	                                        xml_get_current_line_number($parser)));
  // Free the parser
  xml_parser_free($parser);

  if($interactive!='n')
  {
    ?>
    <form action="./" method="post">
    <?php
  }
  //if($_REQUEST['deptiddone']!=1 && sizeof($unidentifieddepts)>0)
  
  
  // If the import has found "setSpec" values which were not expected, then something is up!
  // Probably we need to refresh our own list of the sets that the OAI repository uses, so we need to 
  //   prompt a human administrator to log in and do the manual set-matching...
  if(sizeof($unidentifieddepts)>0)
  {
    $unidentifieddepts = array_unique($unidentifieddepts);
    natcasesort($unidentifieddepts);
	
	$emailmsg = "
This is an automatic message from OnCite's script for importing data from 
an OAI data repository. 

The following 'sets' (which usually correspond to departments, subjects, 
etc.) were found in the data but have not been defined in MyOPIA, so 
MyOPIA doesn't know what to do with them:

" . implode("\n", $unidentifieddepts) . "

Please log in to the following OnCite admin page to correct this:
http://" . $_SERVER["SERVER_NAME"] . "$config[pageshomeurl]manage/superadmin/?action=oaisets&action2=getsets

";
    if($interactive!='n')
	{
	  echo "<pre style='border: 1px solid black; padding: 10px; margin: 10px;'>$emailmsg</pre></form>";
	  exit("<p>Aborting import due to unrecognised set ID(s).</p>");
	}
	else
	{
	  mail($config['webmasteremail'], "OnCite automatic message: OAI import sets problem", $emailmsg);
	  exit("\n\nABORTING import due to unrecognised set ID(s). Message sent to $config[webmasteremail].");
	}
  }
  else
  {
    // We're not dealing with department codes, instead we're entering records
      if(strlen(trim($token))>0)
	    return $token;
	  else
	    return false;
  }
  ?>
  <?php
}

// The function which pushes all the data through the Expat parser
function parseOaiXmlFromFile($parser, $file)
{
  if(!($fp = @fopen($file, 'r')))
    die("Can't find file \"$file\".");
  
  while($data = fread($fp, 4096))
    if(!xml_parse($parser, $data, feof($fp)))
	  return false;
  fclose($fp);

  return true;
}

// Expat calls this when it finds an opening tag
function oaiparser_start_element($parser, $name, $attrs)
{
  global $internaloaielementstack, $token, $records, $currentrecord, $oaiimportnote;
  
  $element = new xmlElement;
  $element->name = $name;
  $element->attributes = $attrs;
  
  // Add it to the context list
  array_push($internaloaielementstack, $element);


  switch($name)
  {
	  case 'request':
	  case 'responseDate':
	  case 'OAI-PMH':
	  case 'ListRecords':
	  case 'dc:relation':
	  case 'dc:format':
	  case 'header':
	  case 'metadata':
	  case 'oai_dc:dc':
	    return;   // These are OAI elements which AREN'T USED AT ALL
	  case 'resumptionToken':
	    break;
	  case 'dc:description':
	  case 'identifier':
	  case 'dc:identifier':
	  case 'datestamp':
	  case 'dc:date':
	  case 'dc:title':
//	  case 'dc:isPartOf':
	  case 'dc:relation.isFormatOf':
	  case 'dc:relation.isFormatOf.pagerange':
	  case 'dc:relation.isFormatOf.volume':
	  case 'dc:relation.isFormatOf.number':
	  case 'dc:relation.isFormatOf.id_number':
	  case 'dc:relation.isFormatOf.series':
	  case 'dc:relation.isFormatOf.place_of_pub':
	  case 'dc:relation.isFormatOf.location':
	  case 'dc:source':
	  case 'dc:subject':
	  case 'dc:type':
	  case 'dc:creator':
	  case 'dc:contributor':
	  case 'setSpec':
	  case 'dc:publisher':
	    $currentrecord[$name][] = '';
	    break;
	  case 'record':
	     $currentrecord=array('notes'=>$oaiimportnote); // Initialise
//         echo '<dl><dt>Record:</dt>';
//  echo "<pre>" . print_r($internaloaielementstack, true) . "</pre>";
        break;
	  default:
	    echo "<dl><dt style='display: preformatted;'>&lt;$name&gt;</dt><dd>";
	    break;
  }
}

// Expat calls this when it finds a closing tag
function oaiparser_stop_element($parser, $name)
{
  global $internaloaielementstack, $token, $records, $currentrecord, $showunconfirmedentryflag, $unidentifieddepts, $sets, $config;

  if(sizeof($internaloaielementstack)>1)
  {
    // Remove it from the context list
    array_pop($internaloaielementstack);
  }
  switch($name)
  {
	  case 'request':
	  case 'responseDate':
	  case 'OAI-PMH':
	  case 'ListRecords':
	  case 'dc:relation':
	  case 'dc:format':
	  case 'header':
	  case 'metadata':
	  case 'oai_dc:dc':
	    return;   // These are OAI elements which AREN'T USED AT ALL
	  case 'dc:description':
	  case 'dc:date':
	  case 'datestamp':
	  case 'dc:subject':
	  case 'identifier':
	  case 'dc:identifier':
	  case 'dc:title':
	  case 'dc:type':
	  case 'dc:publisher':
//	  case 'dc:isPartOf':
	  case 'dc:relation.isFormatOf':
	  case 'dc:relation.isFormatOf.pagerange':
	  case 'dc:relation.isFormatOf.volume':
	  case 'dc:relation.isFormatOf.number':
	  case 'dc:relation.isFormatOf.id_number':
	  case 'dc:relation.isFormatOf.series':
	  case 'dc:relation.isFormatOf.place_of_pub':
	  case 'dc:relation.isFormatOf.location':
	  case 'dc:source':
	  case 'dc:creator':
	  case 'dc:contributor':
	  case 'setSpec':
	  case 'resumptionToken':
	    break; // Most elements, we don't need to do anything when they close
	  case 'record':
		
		// Process the author lists into a single list
		if(is_array($currentrecord['authorlist']))
		{
		  foreach($currentrecord['authorlist'] as $k=>$v)
		  {
		    if(!preg_match('/^.*?,\s*(\w\.)+$/', $v)) // If not already in a nice "Surname,I.N." format
		      $currentrecord['authorlist'][$k] = preg_replace('/^(.*?),\s*(\w).*$/s', "$1,$2.", $v); // Force it to be
		  }
		  $currentrecord['authorlist'] = implode(', ', $currentrecord['authorlist']);
		}
		// Ditto for editor lists
		if(is_array($currentrecord['secondaryauthorlist']))
		{
		  foreach($currentrecord['secondaryauthorlist'] as $k=>$v)
		    $currentrecord['secondaryauthorlist'][$k] = preg_replace('/^(.*?),\s*(\w).*$/s', "$1,$2.", $v);
		  $currentrecord['secondaryauthorlist'] = implode(', ', $currentrecord['secondaryauthorlist']);
		}

		$currentrecord['title'] = implode(', ', $currentrecord['title']);

		if(is_array($currentrecord['secondarytitle']))
		  $currentrecord['secondarytitle'] = array_shift($currentrecord['secondarytitle']); // Only take the FIRST instance of dc:isPartOf
		//  $currentrecord['secondarytitle'] = implode(', ', $currentrecord['secondarytitle']);

		if(is_array($currentrecord['publisher']))
		  $currentrecord['publisher'] = implode(', ', $currentrecord['publisher']);
		if(is_array($currentrecord['oaiid']))
		  $currentrecord['oaiid'] = implode('', $currentrecord['oaiid']);
		if(is_array($currentrecord['url']))
		  $currentrecord['url'] = implode('', $currentrecord['url']);
		if(is_array($currentrecord['oaidatestamp']))
		  $currentrecord['oaidatestamp'] = implode('', $currentrecord['oaidatestamp']);
		if(is_array($currentrecord['year']))
		  $currentrecord['year'] = implode('', $currentrecord['year']);
		if(is_array($currentrecord['abstract']))
		  $currentrecord['abstract'] = implode(' ', $currentrecord['abstract']);
		if(is_array($currentrecord['volume']))
		  $currentrecord['volume'] = implode('', $currentrecord['volume']);
		if(is_array($currentrecord['issue']))
		  $currentrecord['issue'] = implode('', $currentrecord['issue']);
		if(is_array($currentrecord['issnisbn']))
		  $currentrecord['issnisbn'] = implode('', $currentrecord['issnisbn']);
		if(is_array($currentrecord['startpage']))
		  $currentrecord['startpage'] = implode('', $currentrecord['startpage']);
		// The "startpage" actually comes from the "pagerange" field - so attempt to split it explicitly into start and end
		if(preg_match('/^(.+)-(.+?)$/', $currentrecord['startpage'], $matches))
		{
		  $currentrecord['startpage'] = trim($matches[1]);
		  $currentrecord['endpage'] = trim($matches[2]);
		}
		
		// Process the date data
		if(preg_match('/(\d\d\d\d)(?:-(\d\d)(?:-(\d\d))?)?/', preg_replace('/\s+/','',$currentrecord['year']), $matches))
		{
		  $currentrecord['year'] = $matches[1];
		  $currentrecord['yearmonth'] = $matches[2];
		  $currentrecord['yearday'] = $matches[3];
		}
		else
		  echo "Warning: 'Year' data could not be properly processed: $currentrecord[year]";
		
		// Decide on a "reftype"
		if(array_search('Book', $currentrecord['dc:type'])!==false)
		  $currentrecord['reftype']='BOOK';
		elseif(array_search('Book section', $currentrecord['dc:type'])!==false)
		  $currentrecord['reftype']='CHAP';
		elseif(array_search('Article', $currentrecord['dc:type'])!==false)
		{
		  $currentrecord['reftype']='JOUR';
		  // The dc:isPartOf is here remapped over to the journal title
		  $currentrecord['journal'] = $currentrecord['secondarytitle'];
		  unset($currentrecord['secondarytitle']);
		}
		elseif(array_search('Thesis', $currentrecord['dc:type'])!==false)
		  $currentrecord['reftype']='THES';
		elseif(array_search('Monograph', $currentrecord['dc:type'])!==false)
		{
		  $currentrecord['reftype']='RPRT';

		  // In the case of monographs, the "series number" is stored in the "Volume" field, not the "Issue" field as expected (thanks RefMan!)
		  $currentrecord['volume'] = $currentrecord['issue'];
		  unset($currentrecord['issue']);
		}
		elseif(array_search('Conference, workshop or other event', $currentrecord['dc:type'])!==false)
		{
	      if(array_search('PeerReviewed', $currentrecord['dc:type'])!==false)
		    $currentrecord['reftype']='CONF';
		  else
		    $currentrecord['reftype']='CASE';
          
		  // In the case of conferences, the "title" and "secondarytitle" must be swapped (thanks RefMan!)
		  $temp = $currentrecord['title'];
		  $currentrecord['title'] = $currentrecord['secondarytitle'];
		  $currentrecord['secondarytitle'] = $temp;
		}
		else
		  $currentrecord['reftype']='GEN';
		
		// Process the "setSpec" information, which hopefully should tell us two things:
		//  - departmental associations
		//  - published or not
		$currentrecord['pendingdepts'] = ',';
		$currentrecord['published'] = false; // Record will ONLY be inserted if this gets made true
		foreach($currentrecord['setSpec'] as $setSpec)
		{
	        foreach($config['oai_flatten_sets'] as $flatten)
		      if(strpos($setSpec, $flatten)===0)
		      {
		        $setSpec = $flatten;
			    break;
		      }
		
		  if(trim($sets[$setSpec]['deptid']) != '')
		    $currentrecord['pendingdepts'] .= trim($sets[$setSpec]['deptid']) . ',';
		  elseif($sets[$setSpec]['ispublished'] == 1)
		    $currentrecord['published'] = true;
		  elseif(!isset($sets[$setSpec]))
		  {
            $ignorethis = false;
			// If the set is within the oai_ignore_sets list then we don't need to worry
			foreach($config['oai_ignore_sets'] as $ignore)
			  if(strpos($setSpec, $ignore)===0)
			  {
                $ignorethis = true;
				break;
			  }
			if(!$ignorethis)
			  $unidentifieddepts[] = $setSpec;
		  }
//		  else
//		    echo "<pre>Unused setSpec: $setSpec</pre>";
		}
		
		
		
		// Look up the url in PUBLICATIONS.url, and/or the OAI identifier in PUBLICATIONS.oaiid
		// If it already exists in the database, what do we do?
		//    ...we compare the OAI datestamp with MyOPIA's datestamp, and hopefully make a decision based on that.
		//     (1) OAI datestamp == MyOPIA's own datestamp - do nothing
		//     (2) OAI datestamp < MyOPIA's own datestamp - do nothing
		//     (3) OAI datestamp > MyOPIA's own datestamp - THIS IS THE TRICKY ONE! What to do?

        $q = "SELECT *, UNIX_TIMESTAMP(timestamp) as utimestamp FROM PUBLICATIONS WHERE oaiid='" . mysql_real_escape_string($currentrecord['oaiid']) 
		         . "' LIMIT 1"
// It's FAR TOO TIME-INTENSIVE to query both the URL and the oaiid I'm afraid...		         . " OR url='" . mysql_real_escape_string($currentrecord['url']) . "'"
				 ;
        $duplicateres = mysql_query($q, connectpubsdb());
		if($duplicateres)
		{
		  if($row = mysql_fetch_assoc($duplicateres))
		  {
		    $currentrecord['idduplicate'] = $row;
		    $currentrecord['idduplicatedatecomp'] = compareTwoDatestamps($currentrecord['oaidatestamp'], $row['utimestamp']);
		  }
		}else{
		    $currentrecord['idduplicate'] = 'ERROR'; // This should prevent the record being inserted
		}

	    $records[] = $currentrecord;
        break;
	  default:
	    echo // "<pre>&lt;/$name&gt;</pre>" . 
		      "</dd></dl>";
	    break;
  }
}

// Expat calls this when it finds character data
function oaiparser_char_data($parser, $data)
{
  global $internaloaielementstack, $token, $records, $currentrecord, $fieldtranslations;
  
  // $data = $data;
  
  if(trim($data)!='')
  {
    $internaloaielementstack[sizeof($internaloaielementstack)-1]->content[] = $data;
    $name = $internaloaielementstack[sizeof($internaloaielementstack)-1]->name;

    switch($name)
	{
	  case 'request':
	  case 'responseDate':
	  case 'OAI-PMH':
	  case 'ListRecords':
	  case 'dc:relation':
	  case 'dc:format':
	  case 'header':
	  case 'metadata':
	  case 'oai_dc:dc':
	    return;   // These are OAI elements which AREN'T USED AT ALL
	  case 'dc:description':
	  case 'identifier':
	  case 'dc:identifier':
	  case 'dc:date':
	  case 'datestamp':
//	  case 'dc:isPartOf':
	  case 'dc:relation.isFormatOf':
	  case 'dc:relation.isFormatOf.pagerange':
	  case 'dc:relation.isFormatOf.volume':
	  case 'dc:relation.isFormatOf.number':
	  case 'dc:relation.isFormatOf.id_number':
	  case 'dc:relation.isFormatOf.series':
	  case 'dc:relation.isFormatOf.place_of_pub':
	  case 'dc:relation.isFormatOf.location':
	  case 'dc:title':
	  case 'dc:type':
	  case 'dc:subject':
	  case 'dc:creator':
	  case 'dc:contributor':
	  case 'dc:source':
	  case 'setSpec':
	  case 'dc:publisher':
	    $currentrecord[$fieldtranslations[$name]][sizeof($currentrecord[$name])-1] .= $data;
		break;
	  case 'resumptionToken':
        $token .= trim($data);
	    break;
	  default:
	    echo "<p>Unused data: $data</p>";
	    break;
	}
  }
}

function handleNewResumptionToken($token)
{
  global $_REQUEST;
  if(strlen(trim($token))>0)
	echo "<p>The OAI repository returned some records, but not all of them.
   To get the more of the records, please: <a href='./?action=$_REQUEST[action]&action2=resume&amp;token=$token'>click here to resume</a>.</p>";
  else
  {
    storeDatestampForQueryingOai();
	echo "<p>No resumption token - query is complete.</p>";
  }
}

function storeDatestampForQueryingOai()
{
  $q = "SELECT UNIX_TIMESTAMP(ts) AS uts FROM PUBLICATIONSTIMESTAMPS WHERE name='oailastharvest' LIMIT 1";
  $res = mysql_query($q, connectpubsdb());
  if(mysql_num_rows($res)==0)
    $q = "INSERT INTO PUBLICATIONSTIMESTAMPS SET name='oailastharvest', ts=NOW()";
  else
    $q = "UPDATE PUBLICATIONSTIMESTAMPS SET ts=NOW() WHERE name='oailastharvest' LIMIT 1";
  $res = mysql_query($q, connectpubsdb());
}
function getDatestampForQueryingOai()
{
  // This function doesn't return the most recent OAI datestamp in the PUBLICATIONS table, as you might expect.
  // The reason is that it's entirely possible that resumptionTokens weren't followed, and therefore that
  //   there are queries that were never "finished".
  // Instead, whenever the query in "finished" - i.e. we get no resumptionToken in the response - we store 
  //   that datestamp in the PUBLICATIONSTIMESTAMPS table.
  // This gives the handy feature that to query the OAI in its entirety, all we have to do is reset the
  //   "oailastharvest" timestamp.
  
  $q = "SELECT UNIX_TIMESTAMP(ts) AS uts FROM PUBLICATIONSTIMESTAMPS WHERE name='oailastharvest' LIMIT 1";
  $res = mysql_query($q, connectpubsdb());
  if((!$res) || mysql_num_rows($res)==0)
    return '';
  $row = mysql_fetch_assoc($res);
  return date('Y-m-d', $row['uts']);
}

function storeSetLookup($a) // Normally pass in $_POST['newassoc']
{
  foreach($a as $setSpec => $assoc)
  {
     if($assoc=='')
	 {
       $q = "INSERT INTO OAISETLOOKUP SET setSpec='" . mysql_real_escape_string($setSpec) 
		             . "', deptid=''";
	   mysql_query($q, connectpubsdb());
//	   echo "<p>Ignore field: $q</p>";
	 }
     elseif(substr($assoc, 0, 7)=='status:')
	 {
	   if(substr($assoc,7,3)=='pub')
	   {
	     $q = "INSERT INTO OAISETLOOKUP SET setSpec='" . mysql_real_escape_string($setSpec) 
		             . "', deptid='', ispublished=1";
	   }
	   mysql_query($q, connectpubsdb());
//       echo "<p>Publishedness: $q</p>";
	 }
     elseif(substr($assoc, 0, 5)=='dept:')
	 {
       $q = "INSERT INTO OAISETLOOKUP SET setSpec='" . mysql_real_escape_string($setSpec) 
		             . "', deptid='" . mysql_real_escape_string(substr($assoc,5)) . "'";
	   mysql_query($q, connectpubsdb());
//       echo "<p>Dept association: $q</p>";
	 }
  }
}

function listSets()
{
  $q = "SELECT * FROM OAISETLOOKUP";
  $ret = array();
  $res = mysql_query($q, connectpubsdb());
  if($res)
    while($row = mysql_fetch_assoc($res))
	{
	  $ret[$row['setSpec']] = $row;
//	  echo "<p>Found a stored set: " . print_r($row, true) . "</p>";
	}
  else
    echo mysql_error();
  return $ret;
}


function compareTwoDatestamps($oai, $myopia)
{
  // OAI datestamp will be a string of format yyyy-mm-dd, or possibly more finely as yyyy-mm-ddThh:mm:ssZ
  // MyOPIA datestamp will be a UNIX timestamp, as fetched from MySQL using the UNIX_TIMESTAMP() function
  // This function should return +1 if the OAI seems newer
  // It should return -1 if the MyOPIA seems newer
  // It should return 0 if they're the same, or if they seem the same according to the supplied granularity

  // Compare years
  if( intval(substr($oai,0,4)) > intval(date('Y', $myopia)) )			return +1;
  elseif( intval(substr($oai,0,4)) < intval(date('Y', $myopia)) )		return -1;
  // Compare months
  if( intval(substr($oai,5,2)) > intval(date('m', $myopia)) )			return +1;
  elseif( intval(substr($oai,5,2)) < intval(date('m', $myopia)) )		return -1;
  // Compare days
  if( intval(substr($oai,8,2)) > intval(date('d', $myopia)) )			return +1;
  elseif( intval(substr($oai,8,2)) < intval(date('d', $myopia)) )		return -1;

  // At this point the year/month/day appears the same. If the OAI string is no more detailed than this then we have to return zero.
  if(strlen($oai)<20)													return 0;

  // Compare hours
  if( intval(substr($oai,11,2)) > intval(date('H', $myopia)) )			return +1;
  elseif( intval(substr($oai,11,2)) < intval(date('H', $myopia)) )		return -1;
  // Compare minutes
  if( intval(substr($oai,14,2)) > intval(date('i', $myopia)) )			return +1;
  elseif( intval(substr($oai,14,2)) < intval(date('i', $myopia)) )		return -1;
  // Compare seconds
  if( intval(substr($oai,17,2)) > intval(date('s', $myopia)) )			return +1;
  elseif( intval(substr($oai,17,2)) < intval(date('s', $myopia)) )		return -1;
  
  // The timestamps are equal down to the level of seconds (!) so we return zero
  return 0;
}

?>
