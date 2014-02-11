<?php
/*

This is the CONFIG FILE for "OnCite"
(c) 2004-2006 University College London
Licensed under the Academic Free Licence.

*/



// Settings for the MySQL database connection
$config['db_addr'] = ''; // The location of the DB
$config['db_user'] = ''; // The MySQL user used to access the database
$config['db_pass'] = ''; // The MySQL password used to access the database
$config['db_db'] = ''; // The database name

// Settings which tell us WHERE the system has been installed
// URLs must end with a slash
$config['scriptshomeurl'] = '';
$config['imageshomeurl'] = '';
$config['pageshomeurl'] = ''; 
$config['homedir'] = ''; // Filesystem path to the "scripts" directory


// The above settings should be sufficient to get up and running.


// eprints / OAI information - for importing data from an OAI data source
$config['eprints_prefix'] = ''; // Add a ref ID on the end of this, and you can look up an eprint
$config['eprints_publicurl'] = ''; // Where to send the public
$config['oai_baseurl'] = '';
$config['eprints_aboutpage'] = ''; // Replace this with a normal link to an info page, if you like

$config['webmasteremail'] = ''; // Full email address of pubs-system administrator
$config['emaildomain'] = ''; // What to append, to convert an Apache user ID to an email address
$config['institutionname'] = ''; // Short name for the institution

$config['userselfregister'] = true; // Whether or not users should be allowed to register themselves for the system

$config['automailadmins'] = true; // Whether or not the system should send out automatic mails
$config['automailacads'] = false; // Whether or not the system should send out automatic mails

$config['authorguidanceurl'] = ''; // Tells authors what to submit

// Usually we don't want to list the global admins directly, so here's some blurb about them to go in its place:
$config['globaladminstext'] = 'To contact the sitewide administrators please email ' . $config['webmasteremail'];

$config['statusnotifyaddress'] = ''; // Email address to receive notifications when depts alter their 'status' indicator

$config['inpressstring'] = 'Forthcoming'; // The text to use for "forthcoming", i.e. not yet published, records

// This should be used carefully, to output debug info if needed
$config['debug'] = false;
// This PREVENTS PEOPLE FROM USING THE SYSTEM AT ALL! ONLY SET TO TRUE IF YOU NEED TO!
$config['downformaintenance'] = false;
$config['readonly'] = false; // Used to disable all editing, while searching is still allowed

/*
$config[''] = ''; // 
$config[''] = ''; // 
$config[''] = ''; // 
$config[''] = ''; // 
$config[''] = ''; // 
$config[''] = ''; // 
$config[''] = ''; // 
$config[''] = ''; // 
$config[''] = ''; // 
*/

