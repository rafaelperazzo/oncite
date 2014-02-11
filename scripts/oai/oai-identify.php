<?php

if((sizeof($_GET)+sizeof($_POST))!=1)
{
  oaiErrorXml("badArgument", "No arguments must be passed when using the Identify verb.");
  exit();
}

?><?xml version="1.0" encoding="utf-8"?>
<?xml-stylesheet type='text/xsl' href='<?php echo $config['scriptshomeurl'] ?>oai/oai2.xsl' ?>
<OAI-PMH xmlns="http://www.openarchives.org/OAI/2.0/" 
         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/
         http://www.openarchives.org/OAI/2.0/OAI-PMH.xsd">
  <responseDate><?php echo $utcrightnow ?></responseDate>
  <request verb="Identify">http://<?php echo htmlspecialchars($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?></request>
  <Identify>
    <repositoryName>UCL Research Publications Index (MyOPIA)</repositoryName>
    <baseURL><?php echo utf8_encode($config['scriptshomeurl']) ?>oai/</baseURL>
    <protocolVersion>2.0</protocolVersion>
    <adminEmail><?php echo utf8_encode($config['webmasteremail']) ?></adminEmail>
    <earliestDatestamp><?php 

// To get the datestamp we need to query the database

$dbcon = mysql_connect($config['db_addr'], $config['db_user'], $config['db_pass']);
mysql_select_db($config['db_db'], $dbcon);
$q = "SELECT timestamp FROM PUBLICATIONS WHERE timestamp>0 ORDER BY timestamp ASC LIMIT 1";
// $q = "SELECT year FROM PUBLICATIONS WHERE year>1000 ORDER BY year ASC LIMIT 1";
$res = mysql_query($q, $dbcon);
$row = mysql_fetch_assoc($res);
echo mySqlStampToSimple($row['timestamp']);

?></earliestDatestamp>
    <deletedRecord>no</deletedRecord>
    <granularity>YYYY-MM-DD</granularity>
 <description>
      <eprints
         xmlns="http://www.openarchives.org/OAI/1.1/eprints"
         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:schemaLocation="http://www.openarchives.org/OAI/1.1/eprints 
         http://www.openarchives.org/OAI/1.1/eprints.xsd">
        <content>
          <URL>http://www.ucl.ac.uk/research/publications/new</URL>
          <text>Index of publications produced 
		             by University College London, 1997-present</text>
        </content>
        <metadataPolicy/>
        <dataPolicy/>
      </eprints>
    </description>
<description>
      <friends xmlns="http://www.openarchives.org/OAI/2.0/friends/" 
          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
          xsi:schemaLocation="http://www.openarchives.org/OAI/2.0/friends/
         http://www.openarchives.org/OAI/2.0/friends.xsd">
       <baseURL>http://eprints.ucl.ac.uk/perl/oai2</baseURL>
     </friends>
   </description>
 </Identify>
</OAI-PMH>