<?php

error_reporting(E_ALL ^ E_NOTICE);

/*

 oai/index.php

This is MyOPIA's handler for all OAI requests that come in.
They are simply passed on to the relevant script, one for each type of request ("verb")

One issue to solve in general is how to generate the date format required
(e.g. 2002-02-08T08:55:46Z) they call it "UTCdatetime"

*/

header("Content-type: text/xml");
require_once(dirname(dirname(__FILE__)) . '/manage/config.inc.php');
require_once(dirname(__FILE__) . '/oailib.inc.php');


switch($_REQUEST['verb'])
{
  case 'GetRecord':
    require_once('oai-getrecord.php');
    break;
  case 'Identify':
    require_once('oai-identify.php');
    break;
  case 'ListIdentifiers':
    require_once('oai-listidentifiers.php');
    break;
  case 'ListMetadataFormats':
    require_once('oai-listmetadataformats.php');
    break;
  case 'ListRecords':
    require_once('oai-listrecords.php');
    break;
  case 'ListSets':
    require_once('oai-listsets.php');
    break;
  default:
    oaiErrorXml("badVerb", "Illegal OAI verb");
    break;
}


?>
