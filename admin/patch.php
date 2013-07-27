<?PHP
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

// Do not allow browsers to cache this script
header("Expires: Mon, 26 Jul 1990 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");

define('TGPX', TRUE);

require_once('../includes/common.php');
require_once("{$GLOBALS['BASE_DIR']}/includes/mysql.class.php");

$DB = new DB($C['db_hostname'], $C['db_username'], $C['db_password'], $C['db_name']);
$DB->Connect();


// Load table
IniParse("{$GLOBALS['BASE_DIR']}/includes/tables.php", TRUE, $table_defs);


// Create tx_ads if it doesn't already exist
$tables = $DB->GetTables();
if( !isset($tables['tx_ads']) )
{      
    $DB->Update("CREATE TABLE IF NOT EXISTS `tx_ads` ( {$table_defs['tx_ads']} ) TYPE=MyISAM");
}

// Create tx_ads_used if it doesn't already exist
$tables = $DB->GetTables();
if( !isset($tables['tx_ads_used']) )
{      
    $DB->Update("CREATE TABLE IF NOT EXISTS `tx_ads_used` ( {$table_defs['tx_ads_used']} ) TYPE=MyISAM");
}

// Create tx_ads_used_page if it doesn't already exist
$tables = $DB->GetTables();
if( !isset($tables['tx_ads_used_page']) )
{      
    $DB->Update("CREATE TABLE IF NOT EXISTS `tx_ads_used_page` ( {$table_defs['tx_ads_used_page']} ) TYPE=MyISAM");
}

// Create tx_iplog_ads if it doesn't already exist
$tables = $DB->GetTables();
if( !isset($tables['tx_iplog_ads']) )
{      
    $DB->Update("CREATE TABLE IF NOT EXISTS `tx_iplog_ads` ( {$table_defs['tx_iplog_ads']} ) TYPE=MyISAM");
}

// Create tx_scanner_history if it doesn't already exist
$tables = $DB->GetTables();
if( !isset($tables['tx_scanner_history']) )
{      
    $DB->Update("CREATE TABLE IF NOT EXISTS `tx_scanner_history` ( {$table_defs['tx_scanner_history']} ) TYPE=MyISAM");
}

// Create tx_gallery_used_page if it doesn't already exist
if( !isset($tables['tx_gallery_used_page']) && isset($table_defs['tx_gallery_used_page']) )
{      
    $DB->Update("CREATE TABLE IF NOT EXISTS `tx_gallery_used_page` ( {$table_defs['tx_gallery_used_page']} ) TYPE=MyISAM");
}

// Create tx_saved_searches if it doesn't already exist
if( !isset($tables['tx_saved_searches']) && isset($table_defs['tx_saved_searches']) )
{      
    $DB->Update("CREATE TABLE IF NOT EXISTS `tx_saved_searches` ( {$table_defs['tx_saved_searches']} ) TYPE=MyISAM");
}

// Create tx_undos if it doesn't already exist
if( !isset($tables['tx_undos']) && isset($table_defs['tx_undos']) )
{      
    $DB->Update("CREATE TABLE IF NOT EXISTS `tx_undos` ( {$table_defs['tx_undos']} ) TYPE=MyISAM");
}

// Create tx_rss_feeds if it doesn't already exist
if( !isset($tables['tx_rss_feeds']) && isset($table_defs['tx_rss_feeds']) )
{      
    $DB->Update("CREATE TABLE IF NOT EXISTS `tx_rss_feeds` ( {$table_defs['tx_rss_feeds']} ) TYPE=MyISAM");
}

// Create tx_rss_feeds if it doesn't already exist
if( !isset($tables['tx_build_history']) && isset($table_defs['tx_build_history']) )
{      
    $DB->Update("CREATE TABLE IF NOT EXISTS `tx_build_history` ( {$table_defs['tx_build_history']} ) TYPE=MyISAM");
}

// Create tx_search_terms if it doesn't already exist
if( !isset($tables['tx_search_terms']) && isset($table_defs['tx_search_terms']) )
{      
    $DB->Update("CREATE TABLE IF NOT EXISTS `tx_search_terms` ( {$table_defs['tx_search_terms']} ) TYPE=MyISAM");
}

// Create tx_template_globals if it doesn't already exist
if( !isset($tables['tx_template_globals']) && isset($table_defs['tx_template_globals']) )
{      
    $DB->Update("CREATE TABLE IF NOT EXISTS `tx_template_globals` ( {$table_defs['tx_template_globals']} ) TYPE=MyISAM");
}

//{SERVER_EDITION}
// Create tx_domains if it doesn't already exist
if( !isset($tables['tx_domains']) && isset($table_defs['tx_domains']) )
{      
    $DB->Update("CREATE TABLE IF NOT EXISTS `tx_domains` ( {$table_defs['tx_domains']} ) TYPE=MyISAM");
}
//{/SERVER_EDITION}

// Add index to the category_id row of the tx_pages table
$indexes =& $DB->FetchAll('SHOW INDEX FROM `tx_pages`', null, 'Column_name');
if( !isset($indexes['category_id']) )
{
    $DB->Update('ALTER TABLE `tx_pages` ADD INDEX (`category_id`)');
}

// Add index to the filename row of the tx_pages table
if( !isset($indexes['filename']) )
{
    $DB->Update('ALTER TABLE `tx_pages` ADD INDEX (`filename`(100))');
}

// Recreate the tx_gallery_used table if it is old format
$columns = $DB->GetColumns('tx_gallery_used');
if( in_array('category_id', $columns) )
{
    $DB->Update('DROP TABLE `tx_gallery_used`');
    $DB->Update("CREATE TABLE `tx_gallery_used` ( {$table_defs['tx_gallery_used']} ) TYPE=MyISAM");
}


// Update the tx_categories table
$columns = $DB->GetColumns('tx_categories');
if( !in_array('meta_description', $columns) )
{
    $DB->Update("ALTER TABLE `tx_categories` ADD COLUMN `meta_description` TEXT");
}

if( !in_array('meta_keywords', $columns) )
{
    $DB->Update("ALTER TABLE `tx_categories` ADD COLUMN `meta_keywords` TEXT");
}


// Update tx_partners table
$columns = $DB->GetColumns('tx_partners');
if( !in_array('categories', $columns) )
{
    $DB->Update("ALTER TABLE `tx_partners` ADD COLUMN `categories` TEXT AFTER `weight`");
}

if( !in_array('categories_as_exclude', $columns) )
{
    $DB->Update("ALTER TABLE `tx_partners` ADD COLUMN `categories_as_exclude` TINYINT NOT NULL AFTER `categories`");
}

if( !in_array('domains', $columns) )
{
    $DB->Update("ALTER TABLE `tx_partners` ADD COLUMN `domains` TEXT AFTER `categories_as_exclude`");
}

if( !in_array('domains_as_exclude', $columns) )
{
    $DB->Update("ALTER TABLE `tx_partners` ADD COLUMN `domains_as_exclude` TINYINT NOT NULL AFTER `domains`");
}


// Update the tx_pages table
$columns = $DB->GetColumns('tx_pages');
if( !in_array('locked', $columns) )
{
    $DB->Update("ALTER TABLE `tx_pages` ADD COLUMN `locked` TINYINT NOT NULL AFTER `build_order`");
}


// Add requests_waiting field to the tx_administrators table
$columns = $DB->GetColumns('tx_administrators');
if( !in_array('requests_waiting', $columns) )
{
    $DB->Update('ALTER TABLE `tx_administrators` ADD COLUMN `requests_waiting` INT');
}

$DB->Disconnect();


echo "Patching has been completed successfully";

?>