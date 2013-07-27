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

define('TGPX', TRUE);

$path = realpath(dirname(__FILE__));
chdir($path);

// Make sure CLI API is being used
if( php_sapi_name() != 'cli' )
{
    echo "Invalid access: This script requires the CLI version of PHP\n";
    exit;
}

// Make sure safe_mode is disabled
if( ini_get('safe_mode') )
{
    echo "ERROR: The CLI version of PHP is running with safe_mode enabled\n";
    exit;
}

require_once('../includes/common.php');
require_once("{$GLOBALS['BASE_DIR']}/includes/mysql.class.php");
require_once("{$GLOBALS['BASE_DIR']}/includes/template.class.php");
require_once("{$GLOBALS['BASE_DIR']}/admin/includes/functions.php");

$DB = new DB($C['db_hostname'], $C['db_username'], $C['db_password'], $C['db_name']);
$DB->Connect();

// Run function based on command line argument
switch($GLOBALS['argv'][1])
{
case '--build-with-new':
    $args = ParseCommandLine();
    
    if( $args['override-lock'] )
    {
        $GLOBALS['override_page_lock'] = TRUE;
    }
    
    if( !IsEmptyString($args['pages']) )
    {
        if( preg_match('~(\d+)-(\d+)~', $args['pages'], $matches) )
        {
            $args['pages'] = join(',', range($matches[1], $matches[2]));
        }
        
        BuildNewSelected(explode(',', $args['pages']));
    }
    else if( !IsEmptyString($args['tags']) )
    {
        BuildNewTagged($args['tags']);
    }
    else
    {
        BuildNewAll();
    }
    break;

case '--build':
    $args = ParseCommandLine();
    
    if( $args['override-lock'] )
    {
        $GLOBALS['override_page_lock'] = TRUE;
    }
    
    if( !IsEmptyString($args['pages']) )
    {
        if( preg_match('~(\d+)-(\d+)~', $args['pages'], $matches) )
        {
            $args['pages'] = join(',', range($matches[1], $matches[2]));
        }
        
        BuildSelected(explode(',', $args['pages']));
    }
    else if( !IsEmptyString($args['tags']) )
    {
        BuildTagged($args['tags']);
    }
    else
    {
        BuildAll();
    }
    break;

case '--process-clicklog':
    ProcessClickLog();
    break;
        
case '--backup':
    $args = ParseCommandLine();
    
    if( IsEmptyString($args['sql-file']) )
    {
        echo "ERROR: You must specify at least a SQL data backup filename when using the --backup function\n" .
             "Example:\n" .
             "{$_SERVER['_']} $path/{$GLOBALS['argv'][0]} --backup --sql-file=sql-backup.txt --thumbs-file=thumbs-backup.txt --archive-file=backup.tar.gz\n";
        break;   
    }
    
    DoDatabaseBackup($args, TRUE);
    break;
    
case '--restore':
    $args = ParseCommandLine();
    
    if( IsEmptyString($args['sql-file']) )
    {
        echo "ERROR: You must specify at least a SQL data backup filename when using the --restore function\n" .
             "Example:\n" .
             "{$_SERVER['_']} $path/{$GLOBALS['argv'][0]} --restore --sql-file=sql-backup.txt --thumbs-file=thumbs-backup.txt\n";
        break;   
    }
    
    DoDatabaseRestore($args, TRUE);
    break;
    
case '--export':
    DoGalleryExport(null, TRUE);
    break;
    
case '--optimize':
    OptimizeDatabase();
    break;
    
case '--daily-partner':
    DailyPartnerMaintenance();
    break;
    
case '--cleanup';
    ScheduledCleanup(FALSE);
    break;
    
case '--reset-clicks-submitted':
    $DB->Update('UPDATE `tx_galleries` SET `clicks`=0 WHERE `type`=?', array('submitted'));
    break;
    
case '--reset-clicks-permanent':
    $DB->Update('UPDATE `tx_galleries` SET `clicks`=0 WHERE `type`=?', array('permanent'));
    break;
}

$DB->Disconnect();

function DailyPartnerMaintenance()
{
    global $DB, $C;
    
    $args = ParseCommandLine();
    
    // Remove inactive partner accounts    
    if( isset($args['remove-inactive']) && is_numeric($args['remove-inactive']) )
    {
        $min_last_submit = gmdate(DF_DATETIME, TimeWithTz() - $args['remove-inactive'] * 86400);
        $result = $DB->Query('SELECT * FROM `tx_partners` WHERE `date_last_submit` <= ? OR (`date_last_submit` IS NULL AND `date_added` <= ?)', array($min_last_submit, $min_last_submit));
        while( $partner = $DB->NextRow($result) )
        {
            DeletePartner($partner['username'], $partner);
        }
        $DB->Free($result);        
    }
    
    // Send an e-mail message to partner accounts that are inactive
    if( isset($args['email-inactive']) && is_numeric($args['email-inactive']) )
    {
        // Prepare the template
        $t = new Template();
        $t->assign_by_ref('config', $C);
        $t->assign('inactive', $args['email-inactive']);
        
        // Determine the time range to select
        $start = gmdate(DF_DATE, strtotime('-'.$args['email-inactive'].' day', TimeWithTz())) . ' 00:00:00';
        $end = gmdate(DF_DATE, strtotime('-'.$args['email-inactive'].' day', TimeWithTz())) . ' 23:59:59';
        
        // Find matching partners
        $result = $DB->Query('SELECT * FROM `tx_partners` WHERE `date_last_submit` BETWEEN ? AND ? OR (`date_last_submit` IS NULL AND `date_added` BETWEEN ? AND ?)', array($start, $end, $start, $end));
        while( $partner = $DB->NextRow($result) )
        {
            $t->assign_by_ref('partner', $partner);     
            SendMail($partner['email'], 'email-partner-inactive.tpl', $t);
        }
        $DB->Free($result);    
    }
    
    // Send an e-mail message to partner accounts that are expiring soon
    if( isset($args['email-expiring']) && is_numeric($args['email-expiring']) )
    {
        // Prepare the template
        $t = new Template();
        $t->assign_by_ref('config', $C);
        
        // Determine the time range to select
        $start = gmdate(DF_DATE, strtotime('+'.$args['email-expiring'].' day', TimeWithTz())) . ' 00:00:00';
        $end = gmdate(DF_DATE, strtotime('+'.$args['email-expiring'].' day', TimeWithTz())) . ' 23:59:59';
        
        // Find matching partners
        $result = $DB->Query('SELECT * FROM `tx_partners` WHERE `date_end` BETWEEN ? AND ?', array($start, $end));
        while( $partner = $DB->NextRow($result) )
        {
            $partner['date_end'] = strtotime($partner['date_end']);
            $t->assign_by_ref('partner', $partner);            
            SendMail($partner['email'], 'email-partner-expiring.tpl', $t);
        }
        $DB->Free($result);    
    }
}

function ParseCommandLine()
{
    $args = array();
    
    foreach( $GLOBALS['argv'] as $arg )
    {
        // Check if this is a valid argument in --ARG or --ARG=SOMETHING format
        if( preg_match('~--([a-z0-9\-_]+)(=?)(.*)?~i', $arg, $matches) )
        {
            if( $matches[2] == '=' )
            {
                $args[$matches[1]] = $matches[3];
            }
            else
            {
                $args[$matches[1]] = TRUE;
            }
            
        }
    }
    
    return $args;
}

function OptimizeDatabase()
{
    global $DB;
    
    $tables = array();
    IniParse("{$GLOBALS['BASE_DIR']}/includes/tables.php", TRUE, $tables);

    foreach( array_keys($tables) as $table )
    {
        $DB->Update('REPAIR TABLE #', array($table));
        $DB->Update('OPTIMIZE TABLE #', array($table));
    }
}


?>
