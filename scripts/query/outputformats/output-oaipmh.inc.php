<?php


// Output in OAI-PMH 2.0 format.

// All the important work is done by the oailib file:
require_once(dirname(dirname(dirname(__FILE__))) . '/oai/oailib.inc.php');


function output_oaipmh($data){
  global $utcrightnow, $config;
  // Output the XML header
  header('Content-type: text/xml');
  ?><?xml version="1.0" encoding="utf-8"?>
<!-- <?xml-stylesheet type='text/xsl' href='<?php echo $config['scriptshomeurl'] ?>oai/oai2.xsl' ?> -->
<OAI-PMH xmlns="http://www.openarchives.org/OAI/2.0/"
         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/
         http://www.openarchives.org/OAI/2.0/OAI-PMH.xsd">

<!-- This data is formatted according to the OAI-PMH v2 specification,
but it is retrieved from a user search rather than from the system's 
OAI-PMH interface. The 'real' OAI-PMH interface is exposed at:
<?php echo $config['scriptshomeurl'] ?>oai/?verb=Identify

 -->
   <request verb="ListRecords"
 metadataPrefix='oai_dc' >http://www.ucl.ac.uk/research/publications/new/dev/myopia/scripts/oai/?verb=ListRecords&amp;metadataPrefix=oai_dc</request>
  <responseDate><?php echo $utcrightnow ?></responseDate>
  <ListRecords><?php


  // Output the records, one by one
  foreach($data as $p){
    writeSingleOaiItem($p);
  }

  // Output the end of the XML
  ?></ListRecords></OAI-PMH><?php

} // End of output_oaipmh() function




?>
