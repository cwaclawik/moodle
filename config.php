<?php  /// Moodle Configuration File 

unset($CFG);
$CFG = new stdClass();

$CFG->dbtype    = 'mysqli';
$CFG->dblibrary = 'native';
$CFG->dbhost    = 'localhost';
$CFG->dbname    = 'moodle';
$CFG->dbuser    = 'root';
$CFG->dbpass    = 'party25';
$CFG->prefix    = 'mdl_';
$CFG->dboptions = array (
  'dbpersit' => 0,
);

$CFG->wwwroot   = 'http://localhost/moodle';
$CFG->dirroot   = '/var/www/moodle';
$CFG->dataroot  = '/var/moodledata';
$CFG->admin     = 'admin';

$CFG->directorypermissions = 00777;  // try 02777 on a server in Safe Mode

require_once("$CFG->dirroot/lib/setup.php");

// There is no php closing tag in this file,
// it is intentional because it prevents trailing whitespace problems!