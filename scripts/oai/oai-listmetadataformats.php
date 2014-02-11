<?php

// For the purposes of the OAI Repository Explorer's validation of MyOPIA, need to make sure that if
//   an identifier is passed in, it is well-formed and really exists

$identifier = $_REQUEST['identifier'];
if(strlen($identifier)>0)
{

  // Check that the identifier is correctly-patterned for MyOPIA's scheme (or give idDoesNotExist error)
  // Should be similar to oai:ucl.ac.uk:MyOPIA_53256
  if(!preg_match('/oai:(.+?):MyOPIA_(\d+)/', $identifier, $matches))
  {
    oaiErrorXml("idDoesNotExist", "Error in identifier string - it should be in the format
                   oai:ucl.ac.uk:MyOPIA_1234 with &quot;1234&quot; being the record ID number.");
    exit();
  }

  // Now convert a long-format identifier into a single numerical ID
  $id = intval($matches[2]);

  // Now look the thing up in the database
  $q = "SELECT timestamp FROM PUBLICATIONS WHERE pubid=$id LIMIT 1";
  $dbcon = mysql_connect($config['db_addr'], $config['db_user'], $config['db_pass']);
  mysql_select_db($config['db_db'], $dbcon);

  $res = mysql_query($q, $dbcon);

  if(mysql_num_rows($res)==0)
  {
    // If it doesn't exist then give an idDoesNotExist error
    oaiErrorXml("idDoesNotExist", "Record with ID $identifier was not found");
    exit();
  }
}


?><?xml version="1.0" encoding="utf-8"?>
<?xml-stylesheet type='text/xsl' href='<?php echo $config['scriptshomeurl'] ?>oai/oai2.xsl' ?>
<OAI-PMH xmlns="http://www.openarchives.org/OAI/2.0/" 
         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/
         http://www.openarchives.org/OAI/2.0/OAI-PMH.xsd">
  <responseDate><?php echo $utcrightnow ?></responseDate>
  <request verb="ListMetadataFormats"
    identifier="oai:perseus.tufts.edu:Perseus:text:1999.02.0119">
    http://<?php echo htmlspecialchars($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?></request>
  <ListMetadataFormats>
   <metadataFormat>
     <metadataPrefix>oai_dc</metadataPrefix>
     <schema>http://www.openarchives.org/OAI/2.0/oai_dc.xsd
       </schema>
     <metadataNamespace>http://www.openarchives.org/OAI/2.0/oai_dc/
       </metadataNamespace>
   </metadataFormat>
 </ListMetadataFormats>
</OAI-PMH>