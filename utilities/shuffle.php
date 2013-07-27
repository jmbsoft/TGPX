<?php
// Copyright 2011 JMB Software, Inc.
//
// Licensed under the Apache License, Version 2.0 (the "License");
// you may not use this file except in compliance with the License.
// You may obtain a copy of the License at
//
//    http://www.apache.org/licenses/LICENSE-2.0
//
// Unless required by applicable law or agreed to in writing, software
// distributed under the License is distributed on an "AS IS" BASIS,
// WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
// See the License for the specific language governing permissions and
// limitations under the License.

// Run from shell only
if( isset($_SERVER['REQUEST_METHOD']) )
{
    echo "This script can only be run from the command line or through cron\n";
    exit;
}

// Make sure CLI API is being used
if( php_sapi_name() != 'cli' )
{
    echo "Invalid access: This script requires the CLI version of PHP\n";
    exit;
}

define('TGPX', TRUE);

require_once('../includes/common.php');
require_once("{$GLOBALS['BASE_DIR']}/includes/mysql.class.php");
require_once("{$GLOBALS['BASE_DIR']}/admin/includes/functions.php");

set_error_handler('ShuffleError');

$DB = new DB($C['db_hostname'], $C['db_username'], $C['db_password'], $C['db_name']);
$DB->Connect();


echo "Checking database consistency...\n";
$fields = $DB->GetColumns('tx_galleries', TRUE);
if( isset($fields['newid']) )
{
    echo "ERROR: The database is in an inconsistent state.  Please restore from the last known good backup\n";
    exit;
}


echo "Performing database cleanup...\n";
$tables = array('tx_gallery_previews', 'tx_gallery_used', 'tx_gallery_confirms', 'tx_gallery_fields', 'tx_gallery_icons');
foreach( $tables as $table )
{
    $DB->Update('DELETE # FROM # LEFT JOIN `tx_galleries` USING (`gallery_id`) WHERE `tx_galleries`.`gallery_id` IS NULL', array($table, $table));
}


echo "Generating database backup...\n";
$tables[] = 'tx_galleries';
$backup_files = array('sql-file' => 'shuffle-sql-backup.txt');
DoDatabaseBackup($backup_files, TRUE);


echo "Locking tables...\n";
$DB->Update('LOCK TABLES ' . join(' WRITE, ', $tables) . ' WRITE');


echo "Generating new gallery IDs...\n";
$DB->Update('ALTER TABLE `tx_galleries` ADD COLUMN `newid` INT');
$DB->Update('SET @ID:=0');
$DB->Update('UPDATE `tx_galleries` SET `newid`=@ID:=@ID+1 ORDER BY RAND()');


$tables = array('tx_gallery_confirms', 'tx_gallery_fields');
foreach( $tables as $table )
{
    echo "Updating $table...\n";
    $DB->Update('ALTER TABLE # MODIFY COLUMN `gallery_id` INT, DROP PRIMARY KEY', array($table));
    $DB->Update('UPDATE `tx_galleries` JOIN # USING (`gallery_id`) SET #.`gallery_id`=`newid`', array($table, $table));
    $DB->Update('ALTER TABLE # MODIFY COLUMN `gallery_id` INT NOT NULL PRIMARY KEY', array($table));
}


$tables = array('tx_gallery_previews');
foreach( $tables as $table )
{
    echo "Updating $table...\n";
    $DB->Update('UPDATE `tx_galleries` JOIN # USING (`gallery_id`) SET #.`gallery_id`=`newid`', array($table, $table));
}


echo "Updating tx_gallery_used...\n";
$DB->Update('ALTER TABLE `tx_gallery_used` DROP PRIMARY KEY');
$DB->Update('UPDATE `tx_galleries` JOIN `tx_gallery_used` USING (`gallery_id`) SET `tx_gallery_used`.`gallery_id`=`newid`');
$DB->Update('ALTER TABLE `tx_gallery_used` ADD PRIMARY KEY(gallery_id,page_id)');


echo "Updating tx_gallery_icons...\n";
$DB->Update('ALTER TABLE `tx_gallery_icons` DROP PRIMARY KEY');
$DB->Update('UPDATE `tx_galleries` JOIN `tx_gallery_icons` USING (`gallery_id`) SET `tx_gallery_icons`.`gallery_id`=`newid`');
$DB->Update('ALTER TABLE `tx_gallery_icons` ADD PRIMARY KEY(gallery_id,icon_id)');


echo "Updating tx_galleries...\n";
$DB->Update('ALTER TABLE `tx_galleries` MODIFY COLUMN `gallery_id` INT, DROP PRIMARY KEY');
$DB->Update('UPDATE `tx_galleries` SET `gallery_id`=`newid`');
$DB->Update('ALTER TABLE `tx_galleries` MODIFY COLUMN `gallery_id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT');
$DB->Update('ALTER TABLE `tx_galleries` DROP COLUMN `newid`');


echo "Unlocking tables...\n";
$DB->Update('UNLOCK TABLES');


restore_error_handler();
@unlink("{$GLOBALS['BASE_DIR']}/data/{$backup_files['sql-file']}");


echo "\nShuffling complete!\n\n";

function ShuffleError($code, $string, $file, $line)
{
    global $C, $DB, $backup_files;
    
    $reporting = error_reporting();
    
    if( $reporting == 0 || !($code & $reporting) )
    {
        return;
    }
    
    $string = str_replace('<br />', "\n", $string);
    
    echo "Error on line $line of file $file\n" .
         "$string\n\n";

    $DB->Update('UNLOCK TABLES');
         
    // Automatic restore of database
    echo "Automatically restoring previous database tables...\n";
    if( file_exists("{$GLOBALS['BASE_DIR']}/data/{$backup_files['sql-file']}") )
    {
        DoDatabaseRestore($backup_files, TRUE);
    }
    
    echo "Database has been restored to it's previous state\n\n";
    echo "Please report this error message in the support forums\n";
    echo "http://www.jmbsoft.com/support/\n\n";
    
    exit;
}


?>