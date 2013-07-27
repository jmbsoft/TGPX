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

require_once('../includes/common.php');
require_once("{$GLOBALS['BASE_DIR']}/includes/mysql.class.php");
require_once("{$GLOBALS['BASE_DIR']}/includes/http.class.php");
require_once("{$GLOBALS['BASE_DIR']}/includes/template.class.php");
require_once("{$GLOBALS['BASE_DIR']}/includes/imager.class.php");
require_once("{$GLOBALS['BASE_DIR']}/admin/includes/functions.php");


// Get imager object for preview cropping/resizing
$imager = GetImager();

// Get the configuration ID from command line parameter
$config_id = $GLOBALS['argv'][1];

// Define penalties
$penalties = array('ignore' => 0x00000000,
                   'report' => 0x00000001,
                   'disable' => 0x00000002,
                   'delete' => 0x00000004,
                   'blacklist' => 0x00000008);

// Exception bitmasks
$exceptions = array('connect' => 0x00000001,
                    'forward' => 0x00000002,
                    'broken' => 0x00000004,
                    'blacklist' => 0x00000008,
                    'norecip' => 0x00000010,
                    'no2257' => 0x00000020,
                    'excessivelinks' => 0x00000040,
                    'thumbchange' => 0x00000080,
                    'pagechange' => 0x00000100,
                    'content_server' => 0x00000200,
                    'badformat' => 0x00000400);

$DB = new DB($C['db_hostname'], $C['db_username'], $C['db_password'], $C['db_name']);
$DB->Connect();

// Get scanner configuration information
$config = $DB->Row('SELECT * FROM `tx_scanner_configs` WHERE `config_id`=?', array($config_id));
if( !$config )
{
    echo "Invalid configuration ID $config_id\n";
    exit;
}
$configuration = unserialize($config['configuration']);


// See if another instance of this scanner configuration is already running
if( $config['pid'] != 0 && $config['status_updated'] > time() - 300 )
{
    echo "This scanner configuration is already running\n";
    exit;
}


// Clear previous scan results
$DB->Update('DELETE FROM `tx_scanner_results` WHERE `config_id`=?', array($config_id));


// Make sure safe_mode is disabled
if( ini_get('safe_mode') )
{
    $DB->Update('UPDATE `tx_scanner_configs` SET `current_status`=?,`status_updated`=?,`date_last_run`=?,`pid`=? WHERE `config_id`=?',
                array('ERROR: The CLI version of PHP is running with safe_mode enabled',
                      time(),
                      MYSQL_NOW,
                      getmypid(),
                      $config_id));

    echo "ERROR: The CLI version of PHP is running with safe_mode enabled\n";
    exit;
}



// Set the last run time, pid, and status
$DB->Update('UPDATE `tx_scanner_configs` SET `current_status`=?,`status_updated`=?,`date_last_run`=?,`pid`=? WHERE `config_id`=?',
            array('Starting...',
                  time(),
                  MYSQL_NOW,
                  getmypid(),
                  $config_id));


// Import galleries from RSS before starting the scan
if( $configuration['import_rss'] )
{
    // Update scanner status
    $DB->Update('UPDATE `tx_scanner_configs` SET `current_status`=?,`status_updated`=? WHERE `config_id`=?',
                array("Importing galleries from RSS",
                      time(),
                      $config_id));

    $result = $DB->Query('SELECT * FROM `tx_rss_feeds`');
    $total_feeds = $DB->NumRows($result);
    $current_feed = 0;
    while( $feed = $DB->NextRow($result) )
    {
        $current_feed++;

        $DB->Update('UPDATE `tx_scanner_configs` SET `current_status`=?,`status_updated`=? WHERE `config_id`=?',
                array("Importing galleries from RSS feed $current_feed of $total_feeds",
                      time(),
                      $config_id));

        ImportFromRss($feed);
    }
    $DB->Free($result);
}


// Setup the MySQL query
$s =& GenerateQuery();


// Get the galleries to scan
$result = $DB->Query($s->Generate(), $s->binds);
$current_gallery = 0;
$total_galleries = $DB->NumRows($result);


// Create history entry
$DB->Update('INSERT INTO `tx_scanner_history` VALUES (?,?,?,?,?,?,?,?,?,?)',
            array(null,
                  $config_id,
                  MYSQL_NOW,
                  null,
                  $total_galleries,
                  0,
                  0,
                  0,
                  0,
                  0));

$history_id = $DB->InsertID();

if( $total_galleries == 0 )
{
    $DB->Update('UPDATE `tx_scanner_configs` SET `current_status`=?,`status_updated`=? WHERE `config_id`=?',
                array("No galleries to scan - exiting",
                      time(),
                      $config_id));

    sleep(10);
}

while( $gallery = $DB->NextRow($result) )
{
    $exception = 0x00000000;
    $current_gallery++;

    // Exit if stopped (pid set to 0)
    $pid = $DB->Count('SELECT `pid` FROM `tx_scanner_configs` WHERE `config_id`=?', array($config_id));
    if( $pid == 0 )
    {
        break;
    }

    // Update scanner status
    $DB->Update('UPDATE `tx_scanner_configs` SET `current_status`=?,`status_updated`=? WHERE `config_id`=?',
                array("Scanning gallery $current_gallery of $total_galleries",
                      time(),
                      $config_id));

    // Update history
    $DB->Update('UPDATE `tx_scanner_history` SET `scanned`=? WHERE `history_id`=?', array($current_gallery, $history_id));


    // Get partner account, if any
    $partner = null;
    if( $gallery['partner'] )
    {
        $partner = $DB->Row('SELECT * FROM `tx_partners` WHERE `username`=?', array($gallery['partner']));
    }

    // Record current thumbnail count
    $gallery['thumbnails_old'] = $gallery['thumbnails'];

    // Check if the gallery is whitelisted
    $whitelisted = MergeWhitelistOptions(CheckWhitelist($gallery), $partner);

    // Get categories this gallery is in
    $categories = CategoriesFromTags($gallery['categories']);

    // Scan the gallery
    $scan =& ScanGallery($gallery, $categories[0], $whitelisted, TRUE);

    // Bad URL
    if( !$scan['success'] )
    {
        // Bad status code
        if( !empty($scan['status']) )
        {
            if( preg_match('~^3\d\d~', $scan['status']) )
            {
                $exception = $exceptions['forward'];
            }
            else
            {
                $exception = $exceptions['broken'];
            }
        }

        // Connection error
        else
        {
            $exception = $exceptions['connect'];
        }
    }

    // Working URL
    else
    {
        // No reciprocal link found
        if( !$scan['has_recip'] && !$whitelisted['allow_norecip']  )
        {
            $exception |= $exceptions['norecip'];
        }

        // Check the blacklist
        $gallery['html'] = $scan['html'];
        $gallery['headers'] = $scan['headers'];
        if( $configuration['action_blacklist'] != 0 && ($blacklisted = CheckBlacklistGallery($gallery)) !== FALSE )
        {
            $exception |= $exceptions['blacklist'];
            $scan['blacklist_item'] = $blacklisted[0]['match'];
        }

        // Check for 2257 code
        if( !$scan['has_2257'] )
        {
            $exception |= $exceptions['no2257'];
        }

        // Check for excessive links
        if( $C['max_links'] != -1 && $scan['links'] > $C['max_links'] )
        {
            $exception |= $exceptions['excessivelinks'];
        }

        // Check for change in thumbnail count (but not from 0)
        if( $gallery['thumbnails'] > 0 && $scan['thumbnails'] != $gallery['thumbnails'] )
        {
            $exception |= $exceptions['thumbchange'];
        }

        // Update thumbnail count
        if( $configuration['process_updatethumbcount'] )
        {
            $gallery['thumbnails'] = $scan['thumbnails'];
        }

        // Check for page changed
        if( !IsEmptyString($gallery['page_hash']) && $scan['page_hash'] != $gallery['page_hash'] )
        {
            $gallery['page_hash'] = $scan['page_hash'];
            $exception |= $exceptions['pagechange'];
        }

        // Get format information for this gallery
        $format = GetCategoryFormat($scan['format'], $categories[0]);
        $annotation =& LoadAnnotation($format['annotation'], $categories[0]['name']);


        // See if category allows this format
        if( !$format['allowed'] )
        {
            $exception |= $exceptions['badformat'];
            $gallery['allow_preview'] = FALSE;
        }

        // Load existing previews
        $previews =& $DB->FetchAll('SELECT * FROM `tx_gallery_previews` WHERE `gallery_id`=?', array($gallery['gallery_id']));

        // Determine the preview size to generate
        list($preview_width, $preview_height) = GetPreviewDimensions($scan['format'], $format);

        // Generate preview thumbnail
        if( $gallery['allow_preview'] )
        {
            $create_new_preview = $configuration['process_createpreview'] && $DB->Count('SELECT COUNT(*) FROM `tx_gallery_previews` WHERE `dimensions`=? AND `gallery_id`=?', array($preview_width.'x'.$preview_height, $gallery['gallery_id'])) < 1;
            $redo_existing_preview = $configuration['process_redopreview'] && count($previews) > 0;
            $resize_existing_preview = $configuration['process_resizepreview'] && count($previews) > 0;

            // Create a new preview
            if( $create_new_preview )
            {
                $http = new Http();
                if( $http->Get($scan['preview'], TRUE, $scan['end_url']) )
                {
                    $imagefile = "{$GLOBALS['BASE_DIR']}/cache/" . md5(uniqid(rand(), true)) . ".jpg";
                    FileWrite($imagefile, $http->body);

                    $imagesize = @getimagesize($imagefile);

                    if( $imagesize !== FALSE && $imagesize[2] = IMAGETYPE_JPEG )
                    {
                        $imager->ResizeAuto($imagefile, $preview_width.'x'.$preview_height, $annotation, $C['landscape_bias'], $C['portrait_bias']);
                        AddPreview($gallery['gallery_id'], $preview_width.'x'.$preview_height, $imagefile);
                    }
                    else
                    {
                        @unlink($imagefile);
                    }
                }
            }

            // Resize existing preview
            else if( $resize_existing_preview )
            {
                $sizes = array_unique(explode(',', $configuration['new_size']));
                $original = $DB->Row('SELECT * FROM `tx_gallery_previews` WHERE `gallery_id`=? AND `dimensions`=?', array($gallery['gallery_id'], $configuration['original_size']));

                if( $original && stristr($original['preview_url'], $C['preview_url']) )
                {
                    $original_imagefile = "{$C['preview_dir']}/{$original['preview_id']}.jpg";

                    foreach( $sizes as $size )
                    {
                        // Invalid size format
                        if( !preg_match('~^\d+x\d+$~', $size) )
                        {
                            continue;
                        }

                        // Do not overwrite existing, if option not enabled
                        $existing = $DB->Row('SELECT * FROM `tx_gallery_previews` WHERE `gallery_id`=? AND `dimensions`=?', array($gallery['gallery_id'], $size));
                        if( !$configuration['process_resize_overwrite'] && $existing )
                        {
                            continue;
                        }

                        if( $existing )
                        {
                            $DB->Update('DELETE FROM `tx_gallery_previews` WHERE `preview_id`=?', array($existing['preview_id']));
                            $existing_imagefile = "{$C['preview_dir']}/{$existing['preview_id']}.jpg";

                            if( is_file($existing_imagefile) )
                            {
                                @unlink($existing_imagefile);
                            }
                        }

                        $imagefile = "{$GLOBALS['BASE_DIR']}/cache/" . md5(uniqid(rand(), true)) . ".jpg";

                        if( is_file($original_imagefile) )
                        {
                            copy($original_imagefile, $imagefile);

                            $imager->ResizeAuto($imagefile, $size, $annotation, $C['landscape_bias'], $C['portrait_bias'], TRUE);
                            AddPreview($gallery['gallery_id'], $size, $imagefile);
                        }
                    }

                    // Remove original, if option not enabled
                    if( !$configuration['process_resize_keep_orig'] )
                    {
                        $DB->Update('DELETE FROM `tx_gallery_previews` WHERE `preview_id`=?', array($original['preview_id']));

                        if( is_file($original_imagefile) )
                        {
                            @unlink($original_imagefile);
                        }
                    }
                }
            }

            // Re-create all existing previews
            else if( $redo_existing_preview )
            {
                foreach( $previews as $preview )
                {
                    $preview_url = $scan['thumbs'][array_rand($scan['thumbs'])]['full'];

                    if( IsEmptyString($preview['dimensions']) )
                    {
                        $preview['dimensions'] = $format['preview_size'];
                    }

                    $http = new Http();
                    if( $http->Get($preview_url, TRUE, $scan['end_url']) )
                    {
                        $imagefile = "{$GLOBALS['BASE_DIR']}/cache/" . md5(uniqid(rand(), true)) . ".jpg";
                        FileWrite($imagefile, $http->body);

                        $imagesize = @getimagesize($imagefile);

                        if( $imagesize !== FALSE && $imagesize[2] = IMAGETYPE_JPEG )
                        {
                            $imager->ResizeAuto($imagefile, $preview['dimensions'], $annotation, $C['landscape_bias'], $C['portrait_bias']);
                            AddPreview($gallery['gallery_id'], $preview['dimensions'], $imagefile);
                        }
                        else
                        {
                            @unlink($imagefile);
                        }
                    }
                }
            }
        }



        // Download thumbnail(s) from remote server
        if( $configuration['process_downloadpreview'] )
        {
            foreach( $previews as $preview )
            {
                if( !preg_match('~^'.$C['preview_url'].'~', $preview['preview_url']) )
                {
                    $http = new Http();

                    // Download the image
                    if( $http->Get($preview['preview_url'], TRUE, $gallery['gallery_url']) )
                    {
                        $imagefile = "{$GLOBALS['BASE_DIR']}/cache/" . md5(uniqid(rand(), true)) . ".jpg";
                        FileWrite($imagefile, $http->body);

                        $imagesize = @getimagesize($imagefile);

                        if( $imagesize !== FALSE && $imagesize[2] = IMAGETYPE_JPEG )
                        {
                            $width = $imagesize[0];
                            $height = $imagesize[1];
                            $resized = FALSE;

                            // Resize thumb to specific size
                            if( $configuration['process_downloadresize'] && ($width != $preview_width || $height != $preview_height) )
                            {
                                $imager->ResizeAuto($imagefile, $preview_width.'x'.$preview_height, $annotation, $C['landscape_bias'], $C['portrait_bias']);
                                $width = $preview_width;
                                $height = $preview_height;
                                $resized = TRUE;
                            }

                            // Annotate the image
                            if( !$resized && !empty($annotation) )
                            {
                                $imager->Annotate($imagefile, $annotation);
                            }

                            // Write image to permanent filename
                            @rename($imagefile, "{$C['preview_dir']}/{$preview['preview_id']}.jpg");
                            @chmod("{$C['preview_dir']}/{$preview['preview_id']}.jpg", $GLOBALS['FILE_PERMISSIONS']);

                            // Update database information to point to the new file
                            $DB->Update('UPDATE `tx_gallery_previews` SET ' .
                                        '`preview_url`=?, ' .
                                        '`dimensions`=? ' .
                                        'WHERE `preview_id`=?',
                                        array("{$C['preview_url']}/{$preview['preview_id']}.jpg",
                                              $width.'x'.$height,
                                              $preview['preview_id']));
                        }
                        else
                        {
                            @unlink($imagefile);
                        }
                    }
                }
            }
        }

        $gallery['gallery_ip'] = $scan['gallery_ip'];
        $gallery['has_recip'] = $scan['has_recip'];

        if( $configuration['process_updateformat'] )
        {
            $gallery['format'] = $scan['format'];
        }
    }


    // Handle any exceptions
    $processed = FALSE;
    if( $exception )
    {
        $processed = ProcessGallery($gallery, $scan, $exception);
    }


    // Re-enable a gallery if there are no exceptions AND it's partner account is not suspended
    if( $configuration['enable_disabled'] && !$processed && !$exception && $gallery['status'] == 'disabled' )
    {
        if( !($partner && $partner['status'] == 'suspended') )
        {
            $gallery['status'] = $gallery['previous_status'];
            $gallery['previous_status'] = null;
            $gallery['admin_comments'] = null;
        }
    }

    // Update gallery information
    if( !$processed )
    {
        $gallery['date_scanned'] = gmdate(DF_DATETIME, TimeWithTz());
        $DB->Update('UPDATE `tx_galleries` SET ' .
                    '`thumbnails`=?, ' .
                    '`admin_comments`=?, ' .
                    '`gallery_ip`=?, ' .
                    '`format`=?, ' .
                    '`has_recip`=?, ' .
                    '`page_hash`=?, ' .
                    '`status`=?, ' .
                    '`previous_status`=?, ' .
                    '`date_scanned`=? ' .
                    'WHERE `gallery_id`=?',
                    array($gallery['thumbnails'],
                          $gallery['admin_comments'],
                          $gallery['gallery_ip'],
                          $gallery['format'],
                          $gallery['has_recip'],
                          $gallery['page_hash'],
                          $gallery['status'],
                          $gallery['previous_status'],
                          gmdate(DF_DATETIME, TimeWithTz()),
                          $gallery['gallery_id']));
    }

    unset($scan);
}

$DB->Free($result);

// Update history
$DB->Update('UPDATE `tx_scanner_history` SET `date_end`=? WHERE `history_id`=?', array(gmdate(DF_DATETIME, TimeWithTz()), $history_id));

// Mark the scanner as no longer running
$DB->Update('UPDATE `tx_scanner_configs` SET `current_status`=?,`status_updated`=?,`pid`=? WHERE `config_id`=?',
            array('Not Running',
                  time(),
                  0,
                  $config_id));


// E-mail administrators
if( $configuration['process_emailadmin'] )
{
    $administrators =& $DB->FetchAll('SELECT * FROM `tx_administrators`');

    $t = new Template();
    $t->assign_by_ref('config', $C);
    $t->assign('total', $total_galleries);
    $t->assign('scanned', $current_gallery);
    $t->assign('config_id', $config_id);

    foreach( $administrators as $administrator )
    {
        if( $administrator['notifications'] & E_SCANNER_COMPLETE )
        {
            SendMail($administrator['email'], 'email-admin-scanner.tpl', $t);
        }
    }
}


// Rebuild TGP pages
if( $configuration['process_rebuild'] )
{
    BuildAll();
}


$DB->Disconnect();

exit;

function GetPreviewDimensions($scan_format, &$format)
{
    global $configuration;

    $height = 0;
    $width = 0;

    if( $scan_format == FMT_PICTURES )
    {
        // Use default
        if( empty($configuration['pics_preview_size']) )
        {
            list($width, $height) = explode('x', $format['preview_size']);
        }
        else
        {
             list($width, $height) = explode('x', $configuration['pics_preview_size']);
        }
    }
    else
    {
        // Use default
        if( empty($configuration['movies_preview_size']) )
        {
            list($width, $height) = explode('x', $format['preview_size']);
        }
        else
        {
             list($width, $height) = explode('x', $configuration['movies_preview_size']);
        }
    }

    return array($width, $height);
}

function ProcessGallery(&$gallery, &$scan, &$exception)
{
    global $configuration, $exceptions, $penalties, $DB, $config_id, $history_id;

    $removed = FALSE;
    $message = '';
    $penalty = 0x00000000;
    $reasons =  array('connect' => "Connection error: {$scan['errstr']}",
                      'forward' => "Redirecting URL: {$scan['status']}",
                      'broken' => "Broken URL: {$scan['status']}",
                      'blacklist' => "Blacklisted data: " . htmlspecialchars($scan['blacklist_item']),
                      'norecip' => "No reciprocal link found",
                      'no2257' => "No 2257 code found",
                      'excessivelinks' => "Too many links found on the gallery: {$scan['links']}",
                      'thumbchange' => "Thumbnail count has changed from {$gallery['thumbnails_old']} to {$scan['thumbnails']}",
                      'pagechange' => "Page content has changed",
                      'content_server' => 'The gallery content is not hosted on the same server as the gallery',
                      'badformat' => 'The gallery format is not allowed in this category');


    // Determine the most strict penalty based on the infractions that were found
    foreach( $exceptions as $key => $value )
    {
        if( ($exception & $value) && ($configuration['action_'.$key] >= $penalty) )
        {
            $message = $reasons[$key];
            $penalty = intval($configuration['action_'.$key], 16);
        }
    }


    // Blacklist
    if( $penalty & $penalties['blacklist'] )
    {
        $action = 'Blacklisted';
        $removed = TRUE;

        AutoBlacklist($gallery);
        DeleteGallery($gallery['gallery_id'], $gallery);

        // Update history
        $DB->Update('UPDATE `tx_scanner_history` SET `exceptions`=`exceptions`+1,`blacklisted`=`blacklisted`+1 WHERE `history_id`=?', array($history_id));
    }

    // Delete
    else if( $penalty & $penalties['delete'] )
    {
        $action = 'Deleted';
        $removed = TRUE;

        DeleteGallery($gallery['gallery_id'], $gallery);

        // Update history
        $DB->Update('UPDATE `tx_scanner_history` SET `exceptions`=`exceptions`+1,`deleted`=`deleted`+1 WHERE `history_id`=?', array($history_id));
    }

    // Disable
    else if( $penalty & $penalties['disable'] )
    {
        $action = 'Disabled';

        // Don't re-disable a gallery
        if( $gallery['status'] != 'disabled' )
        {
            //$DB->Update('UPDATE `tx_galleries` SET `status`=?,`admin_comments`=? WHERE `gallery_id`=?', array('disabled', $message, $gallery['gallery_id']));
            $gallery['previous_status'] = $gallery['status'];
            $gallery['status'] = 'disabled';
            $gallery['admin_comments'] = $message;
        }

        // Update history
        $DB->Update('UPDATE `tx_scanner_history` SET `exceptions`=`exceptions`+1,`disabled`=`disabled`+1 WHERE `history_id`=?', array($history_id));
    }

    // Display in report
    else if( $penalty & $penalties['report'] )
    {
        $action = 'Unchanged';

        // Update history
        $DB->Update('UPDATE `tx_scanner_history` SET `exceptions`=`exceptions`+1 WHERE `history_id`=?', array($history_id));
    }

    // Ignore
    else
    {
        // Do nothing
        $exception = 0x00000000;
        return $removed;
    }


    $DB->Update('INSERT INTO `tx_scanner_results` VALUES (?,?,?,?,?,?,?)',
                array($config_id,
                      $gallery['gallery_id'],
                      $gallery['gallery_url'],
                      $scan['status'],
                      gmdate(DF_DATETIME, TimeWithTz()),
                      $action,
                      $message));

    return $removed;
}

function &GenerateQuery()
{
    global $DB, $configuration;

    $s = new SelectBuilder('*', 'tx_galleries');
    $s->AddWhere('allow_scan', ST_MATCHES, 1);

    if( count($configuration['status']) > 0 && count($configuration['status']) < 5 )
    {
        $s->AddWhere('status', ST_IN, join(',', array_keys($configuration['status'])));
    }

    if( count($configuration['type']) == 1 )
    {
        $keys = array_keys($configuration['type']);
        $s->AddWhere('type', ST_MATCHES, $keys[0]);
    }

    if( count($configuration['format']) == 1 )
    {
        $keys = array_keys($configuration['format']);
        $s->AddWhere('format', ST_MATCHES, $keys[0]);
    }

    if( is_numeric($configuration['id_start']) && is_numeric($configuration['id_end']) )
    {
        $s->AddWhere('gallery_id', ST_BETWEEN, "{$configuration['id_start']},{$configuration['id_end']}");
    }

    if( preg_match(RE_DATETIME, $configuration['date_added_start']) && preg_match(RE_DATETIME, $configuration['date_added_end']) )
    {
        $s->AddWhere('date_added', ST_BETWEEN, "{$configuration['date_added_start']},{$configuration['date_added_end']}");
    }

    if( preg_match(RE_DATETIME, $configuration['date_approved_start']) && preg_match(RE_DATETIME, $configuration['date_approved_end']) )
    {
        $s->AddWhere('date_approved', ST_BETWEEN, "{$configuration['date_approved_start']},{$configuration['date_approved_end']}");
    }

    if( preg_match(RE_DATETIME, $configuration['date_scanned_start']) && preg_match(RE_DATETIME, $configuration['date_scanned_end']) )
    {
        $s->AddWhere('date_scanned', ST_BETWEEN, "{$configuration['date_scanned_start']},{$configuration['date_scanned_end']}");
    }

    // Only galleries submitted by partners
    if( $configuration['only_parter'] )
    {
        $s->AddWhere('partner', ST_NOT_EMPTY);
    }

    // Only galleries that currently have a zero thumbnail count
    if( $configuration['only_zerothumb'] )
    {
        $s->AddWhere('thumbnails', ST_MATCHES, 0);
    }

    // Only galleries that have not yet been scanned
    if( $configuration['only_notscanned'] )
    {
        $s->AddWhere('date_scanned', ST_NULL);
    }

    // Specific categories selected
    if( !IsEmptyString($configuration['categories'][0]) )
    {
        $tags = array();
        foreach( $configuration['categories'] as $category_id )
        {
            $tags[] = $DB->Count('SELECT `tag` FROM `tx_categories` WHERE `category_id`=?', array($category_id));
        }

        if( count($tags) )
        {
            $s->AddFulltextWhere('categories', join(' ', $tags));
        }
    }

    // Specific sponsors selected
    if( !IsEmptyString($configuration['sponsors'][0]) )
    {
        $s->AddWhere('sponsor_id', ST_IN, join(',', array_unique($configuration['sponsors'])));
    }

    // Only galleries that do not currently have a preview thumbnail
    if( $configuration['only_nothumb'] )
    {
        $s->AddWhere('has_preview', ST_MATCHES, 0);
    }

    return $s;
}


?>
