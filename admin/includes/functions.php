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

if( !defined('TGPX') ) die("Access denied");

define('SESSION_LENGTH', 600);
define('ACCOUNT_EDITOR', 'editor');
define('ACCOUNT_ADMINISTRATOR', 'administrator');

// Privileges
define('P_CATEGORY_ADD',    0x00000001);
define('P_CATEGORY_MODIFY', 0x00000002);
define('P_CATEGORY_REMOVE', 0x00000004);
define('P_GALLERY_ADD',     0x00000008);
define('P_GALLERY_MODIFY',  0x00000010);
define('P_GALLERY_REMOVE',  0x00000020);
define('P_PARTNER_ADD',     0x00000040);
define('P_PARTNER_MODIFY',  0x00000080);
define('P_PARTNER_REMOVE',  0x00000100);
define('P_CATEGORY',        P_CATEGORY_ADD|P_CATEGORY_MODIFY|P_CATEGORY_REMOVE);
define('P_GALLERY',         P_GALLERY_ADD|P_GALLERY_MODIFY|P_GALLERY_REMOVE);
define('P_PARTNER',         P_PARTNER_ADD|P_PARTNER_MODIFY|P_PARTNER_REMOVE);


// User defined field types
$FIELD_TYPES = array(FT_TEXT => FT_TEXT,
                     FT_TEXTAREA => FT_TEXTAREA,
                     FT_SELECT => FT_SELECT,
                     FT_CHECKBOX => FT_CHECKBOX);

// Validation types
$VALIDATION_TYPES = array(V_NONE => 'None',
                          V_ALPHANUM => 'Alphanumeric',
                          V_BETWEEN => 'Between',
                          V_EMAIL => 'E-mail Address',
                          V_GREATER => 'Greater Than',
                          V_LESS => 'Less Than',
                          V_LENGTH => 'Length',
                          V_NUMERIC => 'Numeric',
                          V_REGEX => 'Regular Expression',
                          V_URL => 'HTTP URL',
                          V_URL_WORKING_300 => 'Working URL (Redirection OK)',
                          V_URL_WORKING_400 => 'Working URL (No Redirection)');


// Annotation locations
$ANN_LOCATIONS = array('NorthWest' => 'Top Left',
                       'North' => 'Top Center',
                       'NorthEast' => 'Top Right',
                       'SouthWest' => 'Bottom Left',
                       'South' => 'Bottom Center',
                       'SouthEast' => 'Bottom Right');

function ImportFromRss($feed)
{
    global $DB, $C;

    $settings = unserialize($feed['settings']);
    $category = $DB->Row('SELECT * FROM `tx_categories` WHERE `category_id`=?', array($settings['category']));
    $columns = $DB->GetColumns('tx_gallery_fields');
    $imported = 0;

    $defaults = array('gallery_url' => null,
                      'description' => null,
                      'keywords' => null,
                      'thumbnails' => 0,
                      'email' => $C['from_email'],
                      'nickname' => null,
                      'weight' => $C['gallery_weight'],
                      'clicks' => 0,
                      'submit_ip' => GetIpFromUrl($feed['feed_url']),
                      'gallery_ip' => '',
                      'sponsor_id' => !empty($feed['sponsor_id']) ? $feed['sponsor_id'] : null,
                      'type' => $settings['type'],
                      'format' => $settings['format'],
                      'status' => $settings['status'],
                      'previous_status' => null,
                      'date_scanned' => null,
                      'date_added' => MYSQL_NOW,
                      'date_approved' => null,
                      'date_scheduled' => null,
                      'date_displayed' => null,
                      'date_deletion' => null,
                      'partner' => null,
                      'administrator' => $_SERVER['REMOTE_USER'],
                      'admin_comments' => null,
                      'page_hash' => null,
                      'has_recip' => 0,
                      'has_preview' => 0,
                      'allow_scan' => 1,
                      'allow_preview' => 1,
                      'times_selected' => 0,
                      'used_counter' => 0,
                      'build_counter' => 0,
                      'tags' => null,
                      'categories' => MIXED_CATEGORY . " " . $category['tag'],
                      'preview_url' => null,
                      'dimensions' => null);

    require_once("{$GLOBALS['BASE_DIR']}/includes/rssparser.class.php");

    $http = new Http();

    if( $http->Get($feed['feed_url'], TRUE, $C['install_url']) )
    {
        $parser = new RSSParser();

        if( ($rss = $parser->Parse($http->body)) !== FALSE )
        {

            foreach( $rss['items'] as $item )
            {
                $gallery = array();
                $gallery['gallery_url'] = html_entity_decode($item[$settings['gallery_url_from']]);
                $gallery['description'] = html_entity_decode($item[$settings['description_from']]);

                if( !empty($settings['date_added_from']) )
                {
                    if( ($timestamp = strtotime($item[$settings['date_added_from']])) !== FALSE )
                    {
                        $gallery['date_added'] = date(DF_DATETIME, $timestamp);
                    }
                }

                if( !empty($settings['preview_from']) )
                {
                    if( !is_array($item[$settings['preview_from']]) )
                    {
                        $item[$settings['preview_from']] = array($item[$settings['preview_from']]);
                    }

                    foreach( $item[$settings['preview_from']] as $item_value )
                    {
                        if( preg_match('~(http://[^>< ]+\.(jpg|png))~i', $item_value, $matches) )
                        {
                            $gallery['preview_url'] = $matches[1];
                            break;
                        }
                    }
                }

                // Remove HTML tags and trim the description
                $gallery['description'] = trim(strip_tags($gallery['description']));

                // Merge with the defaults
                $gallery = array_merge($defaults, $gallery);

                // Skip over duplicate or empty URLs
                if( $DB->Count('SELECT COUNT(*) FROM `tx_galleries` WHERE `gallery_url`=?', array($gallery['gallery_url'])) || IsEmptyString($gallery['gallery_url']) )
                {
                    continue;
                }

                $imported++;

                // Has a preview thumbnail
                if( !empty($gallery['preview_url']) )
                {
                    $gallery['has_preview'] = 1;
                }

                // Add regular fields
                $DB->Update('INSERT INTO `tx_galleries` VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                            array(null,
                                  $gallery['gallery_url'],
                                  $gallery['description'],
                                  $gallery['keywords'],
                                  $gallery['thumbnails'],
                                  $gallery['email'],
                                  $gallery['nickname'],
                                  $gallery['weight'],
                                  $gallery['clicks'],
                                  $gallery['submit_ip'],
                                  $gallery['gallery_ip'],
                                  $gallery['sponsor_id'],
                                  $gallery['type'],
                                  $gallery['format'],
                                  $gallery['status'],
                                  $gallery['previous_status'],
                                  $gallery['date_scanned'],
                                  $gallery['date_added'],
                                  $gallery['date_approved'],
                                  $gallery['date_scheduled'],
                                  $gallery['date_displayed'],
                                  $gallery['date_deletion'],
                                  $gallery['partner'],
                                  $gallery['administrator'],
                                  $gallery['admin_comments'],
                                  $gallery['page_hash'],
                                  $gallery['has_recip'],
                                  $gallery['has_preview'],
                                  $gallery['allow_scan'],
                                  $gallery['allow_preview'],
                                  $gallery['times_selected'],
                                  $gallery['used_counter'],
                                  $gallery['build_counter'],
                                  $gallery['tags'],
                                  $gallery['categories']));

                $gallery['gallery_id'] = $DB->InsertID();

                // Add user defined fields
                $query_data = CreateUserInsert('tx_gallery_fields', $gallery, $columns);
                $DB->Update('INSERT INTO `tx_gallery_fields` VALUES ('.$query_data['bind_list'].')', $query_data['binds']);


                // Has a preview thumbnail
                if( !empty($gallery['preview_url']) )
                {
                    $DB->Update('INSERT INTO `tx_gallery_previews` VALUES (?,?,?,?)',
                                array(null,
                                      $gallery['gallery_id'],
                                      $gallery['preview_url'],
                                      $gallery['dimensions']));
                }
            }
        }

        $DB->Update('UPDATE `tx_rss_feeds` SET `date_last_import`=? WHERE `feed_id`=?', array(MYSQL_NOW, $feed['feed_id']));
    }
    else
    {
        return "Could not access the RSS feed: " . $http->errstr;
    }

    return $imported;
}

function StringChopTooltip($string, $length, $center = FALSE, $append = null)
{
    if( strlen($string) > $length )
    {
        $string = '<span title="'.$string.'" class="tt">' . StringChop($string, $length, $center, $append) . '</span>';
    }

    return $string;
}

function &GetBulkAddPages($base_url, $base_dir)
{
    global $DB;

    $pages = array();
    $ext = $_REQUEST['ext'];

    $base_url = preg_replace('~/$~', '', $base_url);
    $base_dir = preg_replace('~/$~', '', $base_dir);

    switch($_REQUEST['category_id'])
    {
        case '':
        {
            $prefix = $_REQUEST['prefix'];

            foreach( range(1, $_REQUEST['num_pages']) as $index )
            {
                $page =& $pages[];
                $page['filename'] = "$base_dir/$prefix" . ($index == 1 ? '' : $index) . ".$ext";
                $page['page_url'] = "$base_url/$prefix" . ($index == 1 ? '' : $index) . ".$ext";
                $page['category_id'] = null;
            }

            break;
        }

        case '__all__':
        {
            $categories =& $DB->FetchAll('SELECT * FROM `tx_categories` ORDER BY `name`');

            foreach( $categories as $category )
            {
                $prefix = CategoryToPageName($category['name'], $_REQUEST['characters'], $_REQUEST['case']);

                foreach( range(1, $_REQUEST['num_pages']) as $index )
                {
                    $page =& $pages[];
                    $page['filename'] = "$base_dir/$prefix" . ($index == 1 ? '' : $index) . ".$ext";
                    $page['page_url'] = "$base_url/$prefix" . ($index == 1 ? '' : $index) . ".$ext";
                    $page['category_id'] = $category['category_id'];
                }
            }

            break;
        }

        default:
        {
            $prefix = $DB->Count('SELECT `name` FROM `tx_categories` WHERE `category_id`=?', array($_REQUEST['category_id']));
            $prefix = CategoryToPageName($prefix, $_REQUEST['characters'], $_REQUEST['case']);

            foreach( range(1, $_REQUEST['num_pages']) as $index )
            {
                $page =& $pages[];
                $page['filename'] = "$base_dir/$prefix" . ($index == 1 ? '' : $index) . ".$ext";
                $page['page_url'] = "$base_url/$prefix" . ($index == 1 ? '' : $index) . ".$ext";
                $page['category_id'] = $_REQUEST['category_id'];
            }

            break;
        }
    }

    return $pages;
}

function CategoryToPageName($name, $characters, $case)
{
    $replacement = '';

    switch($characters)
    {
        case 'remove':
            $replacement = '';
            break;

        case 'dash':
            $replacement = '-';
            break;

        case 'underscore':
            $replacement = '_';
            break;
    }

    $name = preg_replace('~[^a-z0-9]+~i', $replacement, $name);

    if( $case == 'lower' )
    {
        $name = strtolower($name);
    }

    $name = preg_replace("~^$replacement|$replacement$~", '', $name);

    return $name;
}

function ScheduledCleanup($checktime = TRUE)
{
    global $DB;

    // Check if last cleanup was less than 24 hours ago
    if( $checktime )
    {
        $last_cleanup = GetValue('last_cleanup');

        if( !empty($last_cleanup) && $last_cleanup > time() - 86400 )
        {
            return;
        }
    }

    // Clear out galleries with submitting status
    $result = $DB->Query('SELECT * FROM `tx_galleries` WHERE `status`=?', array('submitting'));
    while( $gallery = $DB->NextRow($result) )
    {
        DeleteGallery($gallery['gallery_id'], $gallery);
    }
    $DB->Free($result);


    // Clear out the cache directory
    $cache_items = glob("{$GLOBALS['BASE_DIR']}/cache/*", GLOB_NOSORT);
    if( $cache_items !== FALSE )
    {
        foreach( $cache_items as $item )
        {
            $stat = stat($item);

            if( $stat['mtime'] < time() - 3600 )
            {
                @unlink($item);
            }
        }
    }

    StoreValue('last_cleanup', time());
}

function RenumberBuildOrder()
{
    global $DB;

    $DB->Update("SET @build_order=0");
    $result = $DB->Query("SELECT * FROM `tx_pages` ORDER BY `build_order`");
    while( $page = $DB->NextRow($result) )
    {
        $DB->Update('UPDATE `tx_pages` SET `build_order`=@build_order:=@build_order+1 WHERE `page_id`=?', array($page['page_id']));
    }
    $DB->Free($result);
}

function DirectoryFromRoot($root, $url)
{
    $parsed_url = parse_url($url);

    if( !IsEmptyString($parsed_url['path']) )
    {
        $root .= $parsed_url['path'];
    }

    return $root;
}

function PrepareMessage()
{
    UnixFormat($_REQUEST['plain']);
    UnixFormat($_REQUEST['html']);

    return "=>[subject]\n" .
           $_REQUEST['subject'] . "\n" .
           "=>[plain]\n" .
           trim($_REQUEST['plain']) . "\n" .
           "=>[html]\n" .
           trim($_REQUEST['html']);
}

function GetWhichReports()
{
    global $DB;

    $result = null;
    $req = $_REQUEST;

    if( IsEmptyString($_REQUEST['which']) )
    {
        parse_str($_REQUEST['results'], $req);
    }

    switch($req['which'])
    {
    case 'all':
        $result = $DB->Query('SELECT * FROM `tx_reports`');
        break;

    default:
        $bind_list = CreateBindList($req['report_id']);
        $result = $DB->Query('SELECT * FROM `tx_reports` WHERE `report_id` IN (' . $bind_list . ')', $req['report_id']);
        break;
    }

    return $result;
}

function GetWhichPartners()
{
    global $DB;

    $result = null;
    $req = $_REQUEST;

    if( IsEmptyString($_REQUEST['which']) )
    {
        parse_str($_REQUEST['results'], $req);
    }

    switch($req['which'])
    {
    case 'matching':
        // Extract search form information
        $search_form = array();
        parse_str($_REQUEST['search_form'], $search_form);

        // Build select query
        $select = new SelectBuilder('*', 'tx_partners');
        $select->AddWhere($search_form['field'], $search_form['search_type'], $search_form['search'], $search_form['search_type'] != ST_EMPTY);
        $select->AddWhere('status', ST_MATCHES, $search_form['status'], TRUE);

        // Execute the query
        $result = $DB->Query($select->Generate(), $select->binds);
        break;

    case 'all':
        $result = $DB->Query('SELECT * FROM `tx_partners`');
        break;

    default:
        $bind_list = CreateBindList($req['username']);
        $result = $DB->Query('SELECT * FROM `tx_partners` WHERE `username` IN (' . $bind_list . ')', $req['username']);
        break;
    }

    return $result;
}

function GallerySearchSelect(&$s, $request = null)
{
    global $DB;

    if( $request != null )
    {
        $_REQUEST = array_merge($_REQUEST, $request);
    }

    $fulltext = array('description,keywords','tags');
    $user = $DB->GetColumns('tx_gallery_fields', TRUE);

    // Special handling of date searches (transform MM-DD-YYYY to YYYY-MM-DD format)
    if( preg_match('~^date_~', $_REQUEST['field']) )
    {
        $_REQUEST['search'] = trim($_REQUEST['search']);

        if( preg_match('~^(\d\d)-(\d\d)-(\d\d\d\d)$~', $_REQUEST['search'], $date) )
        {
            $_REQUEST['search_type'] = ST_BETWEEN;
            $_REQUEST['search'] = "{$date[3]}-{$date[1]}-{$date[2]} 00:00:00,{$date[3]}-{$date[1]}-{$date[2]} 23:59:59";
        }
        else if( preg_match('~^\d\d\d\d-\d\d-\d\d$~', $_REQUEST['search']) )
        {
            $_REQUEST['search_type'] = ST_BETWEEN;
            $_REQUEST['search'] = "{$_REQUEST['search']} 00:00:00,{$_REQUEST['search']} 23:59:59";
        }

        $_REQUEST['search'] = preg_replace('~(\d\d)-(\d\d)-(\d\d\d\d)~', '\3-\1-\2', $_REQUEST['search']);
    }

    if( !empty($user[$_REQUEST['field']]) || !empty($user[$_REQUEST['order']]) || !empty($user[$_REQUEST['order_next']]) )
    {
        $s->AddJoin('tx_galleries', 'tx_gallery_fields', '', 'gallery_id');
        $s->AddWhere($_REQUEST['field'], $_REQUEST['search_type'], $_REQUEST['search'], $_REQUEST['search_type'] != ST_EMPTY);
    }
    else if( in_array($_REQUEST['field'], $fulltext) )
    {
        if( $_REQUEST['search_type'] == ST_EMPTY && ($_REQUEST['field'] == 'keywords' || $_REQUEST['field'] == 'tags') )
        {
            $s->AddWhere($_REQUEST['field'], $_REQUEST['search_type'], $_REQUEST['search'], $_REQUEST['search_type'] != ST_EMPTY);
        }
        else
        {
            $s->AddFulltextWhere($_REQUEST['field'], $_REQUEST['search'], $_REQUEST['search_type'] != ST_EMPTY);
        }
    }
    else if( $_REQUEST['field'] == 'dimensions' )
    {
        if( $_REQUEST['search_type'] == ST_EMPTY || !empty($_REQUEST['search']) )
        {
            $s->AddJoin('tx_galleries', 'tx_gallery_previews', $_REQUEST['search_type'] == ST_EMPTY ? 'LEFT' : '', 'gallery_id');
        }
        $s->AddWhere($_REQUEST['field'], $_REQUEST['search_type'], $_REQUEST['search'], $_REQUEST['search_type'] != ST_EMPTY);
    }
    else if( $_REQUEST['field'] == 'sponsor' )
    {
        $s->AddJoin('tx_galleries', 'tx_sponsors', '', 'sponsor_id');
        $s->AddWhere('name', $_REQUEST['search_type'], $_REQUEST['search'], $_REQUEST['search_type'] != ST_EMPTY);
    }
    else
    {
        $s->AddWhere($_REQUEST['field'], $_REQUEST['search_type'], $_REQUEST['search'], $_REQUEST['search_type'] != ST_EMPTY);
    }


    if( count($_REQUEST['type']) == 1 )
    {
        $s->AddWhere('type', ST_MATCHES, $_REQUEST['type'][0]);
    }

    if( count($_REQUEST['format']) == 1 )
    {
        $s->AddWhere('format', ST_MATCHES, $_REQUEST['format'][0]);
    }


    $s_checked = count($_REQUEST['status']);
    if( $s_checked > 0 && $s_checked < 6 )
    {
        $s->AddWhere('status', ST_IN, join(',', $_REQUEST['status']));
    }

    if( isset($_REQUEST['partners']) )
    {
        $s->AddWhere('partner', ST_NOT_EMPTY, null);
    }

    if( isset($_REQUEST['preview']) && count($_REQUEST['preview']) == 1 )
    {
        $preview = $_REQUEST['preview'][0];
        $s->AddWhere('has_preview', ST_MATCHES, $preview);
    }

    if( count($_REQUEST['categories']) > 0 && !in_array(MIXED_CATEGORY, $_REQUEST['categories']) )
    {
        if( isset($_REQUEST['cat_exclude']) )
        {
            array_walk($_REQUEST['categories'], create_function('&$c', '$c = "-" . $c;'));
            $s->AddFulltextWhere('categories', MIXED_CATEGORY . " " . join(' ', $_REQUEST['categories']));
        }
        else
        {
            $s->AddFulltextWhere('categories', join(' ', $_REQUEST['categories']));
        }
    }

    if( is_numeric($_REQUEST['sponsor_id']) )
    {
        $s->AddWhere('sponsor_id', ST_MATCHES, $_REQUEST['sponsor_id']);
    }

    if( $_REQUEST['order'] == 'RAND()' )
    {
        $_REQUEST['order'] = 'RAND('.$_REQUEST['rand'].')';
    }

    if( $_REQUEST['order_next'] == 'RAND()' )
    {
        $_REQUEST['order_next'] = 'RAND('.$_REQUEST['rand'].')';
    }

    return TRUE;
}

function GetWhichGalleries($update = FALSE)
{
    global $DB;

    $result = null;
    $req = $_REQUEST;

    if( IsEmptyString($_REQUEST['which']) )
    {
        parse_str($_REQUEST['results'], $req);
    }

    switch($req['which'])
    {
    case 'specific':
        $result = $DB->Query('SELECT * FROM `tx_galleries` WHERE `gallery_id`=?', array($req['gallery_id']));
        break;

    case 'matching':
        // Extract search form information
        $search_form = array();
        parse_str($_REQUEST['search_form'], $search_form);

        if( $update )
        {
            GallerySearchSelect($update, $search_form);
            $result = $update;
        }
        else
        {
            // Build select query
            $select = new SelectBuilder('*', 'tx_galleries');
            GallerySearchSelect($select, $search_form);

            // Execute the query
            $result = $DB->Query($select->Generate(), $select->binds);
        }
        break;

    case 'all':
        $result = $DB->Query('SELECT * FROM `tx_galleries`');
        break;

    default:
        if( $update )
        {
            $update->AddWhere('gallery_id', ST_IN, join(',', $req['gallery_id']));
            $result = $update;
        }
        else
        {
            $bind_list = CreateBindList($req['gallery_id']);
            $result = $DB->Query('SELECT * FROM `tx_galleries` WHERE `gallery_id` IN (' . $bind_list . ')', $req['gallery_id']);
        }
        break;
    }

    return $result;
}

function HandleUncheckedFields(&$fields)
{
    foreach($fields as $field)
    {
        if( $field['type'] == FT_CHECKBOX && !isset($_REQUEST[$field['name']]) )
        {
            $_REQUEST[$field['name']] = null;
        }
    }
}

function RemoveCategoryTag($tag, $list)
{
    $tags = array();

    foreach( explode(' ', $list) as $current_tag )
    {
        if( $current_tag != $tag )
        {
            $tags[] = $current_tag;
        }
    }

    return trim(join(' ', array_unique($tags)));
}

function CreateCategories($list)
{
    global $DB, $C;

    $default = unserialize(GetValue('default_category'));
    $list = FormatCommaSeparated($list);
    $tags = array(MIXED_CATEGORY);

    foreach( array_unique(explode(',', $list)) as $name )
    {
        if( IsEmptyString($name) )
        {
            continue;
        }

        if( $DB->Count('SELECT COUNT(*) FROM `tx_categories` WHERE `name`=?', array($name)) )
        {
            continue;
        }

        $tag = CreateCategoryTag($name);
        $tags[] = $tag;

        $DB->Update('INSERT INTO `tx_categories` VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                    array(null,
                          $name,
                          $tag,
                          intval($default['pics_allowed']),
                          $default['pics_extensions'],
                          $default['pics_minimum'],
                          $default['pics_maximum'],
                          $default['pics_file_size'],
                          $default['pics_preview_size'],
                          intval($default['pics_preview_allowed']),
                          $default['pics_annotation'],
                          intval($default['movies_allowed']),
                          $default['movies_extensions'],
                          $default['movies_minimum'],
                          $default['movies_maximum'],
                          $default['movies_file_size'],
                          $default['movies_preview_size'],
                          intval($default['movies_preview_allowed']),
                          $default['movies_annotation'],
                          $default['per_day'],
                          intval($default['hidden']),
                          null,
                          $default['meta_description'],
                          $default['meta_keywords']));

    }

    return join(' ', array_unique($tags));
}

function &DeletePartner($username, $partner = null)
{
    global $DB;

    if( $partner == null )
    {
        $partner = $DB->Row('SELECT * FROM `tx_partners` WHERE `username`=?', array($username));
    }

    if( $partner )
    {
        // Remove this partner's galleries
        $result = $DB->Query('SELECT * FROM `tx_galleries` WHERE `partner`=?', array($partner['username']));
        while( $gallery = $DB->NextRow($result) )
        {
            DeleteGallery($gallery['gallery_id'], $gallery);
        }
        $DB->Free($result);

        $DB->Update('DELETE FROM `tx_partners` WHERE `username`=?', array($partner['username']));
        $DB->Update('DELETE FROM `tx_partner_icons` WHERE `username`=?', array($partner['username']));
        $DB->Update('DELETE FROM `tx_partner_fields` WHERE `username`=?', array($partner['username']));
    }

    return $partner;
}

function UpdateThumbSizes($new_size = null)
{
    global $DB;

    $sizes = unserialize(GetValue('preview_sizes'));

    if( !is_array($sizes) )
    {
        $sizes = array();
    }

    if( !empty($_REQUEST['movies_preview_size']) )
    {
        $sizes[] = $_REQUEST['movies_preview_size'];
    }

    if( !empty($_REQUEST['pics_preview_size']) )
    {
        $sizes[] = $_REQUEST['pics_preview_size'];
    }

    if( $new_size )
    {
        $sizes[] = $new_size;
    }

    StoreValue('preview_sizes', serialize(array_unique($sizes)));
}

function CleanupThumbSizes()
{
    global $DB;

    $sizes = array();

    $default_category = unserialize(GetValue('default_category'));

    $sizes[] = $default_category['pics_preview_size'];
    $sizes[] = $default_category['movies_preview_size'];

    $result = $DB->Query('SELECT `dimensions` FROM `tx_gallery_previews` GROUP BY `dimensions`');
    while( $preview = $DB->NextRow($result) )
    {
        $preview['dimensions'] = trim($preview['dimensions']);

        if( empty($preview['dimensions']) )
            continue;

        $sizes[] = $preview['dimensions'];
    }
    $DB->Free($result);


    $result = $DB->Query('SELECT `movies_preview_size` FROM `tx_categories` GROUP BY `movies_preview_size`');
    while( $preview = $DB->NextRow($result) )
    {
        $preview['dimensions'] = trim($preview['dimensions']);

        if( empty($preview['dimensions']) )
            continue;

        $sizes[] = $preview['dimensions'];
    }
    $DB->Free($result);


    $result = $DB->Query('SELECT `pics_preview_size` FROM `tx_categories` GROUP BY `pics_preview_size`');
    while( $preview = $DB->NextRow($result) )
    {
        $preview['dimensions'] = trim($preview['dimensions']);

        if( empty($preview['dimensions']) )
            continue;

        $sizes[] = $preview['dimensions'];
    }
    $DB->Free($result);


    $result = $DB->Query('SELECT `configuration` FROM `tx_scanner_configs`');
    while( $scanner = $DB->NextRow($result) )
    {
        $config = unserialize($scanner['configuration']);
        $config['movies_preview_size'] = trim($config['movies_preview_size']);
        $config['pics_preview_size'] = trim($config['pics_preview_size']);

        if( !empty($config['pics_preview_size']) )
            $sizes[] = $config['pics_preview_size'];

        if( !empty($config['movies_preview_size']) )
            $sizes[] = $config['movies_preview_size'];
    }
    $DB->Free($result);

    StoreValue('preview_sizes', serialize(array_unique($sizes)));
}

function &ValidateAnnotationInput()
{
    $validator = new Validator();

    $validator->Register($_REQUEST['identifier'], V_EMPTY, 'The Identifier field must be filled in');

    if( $_REQUEST['type'] == 'text' )
    {
        if( !isset($_REQUEST['use_category']) )
        {
            $validator->Register($_REQUEST['string'], V_EMPTY, 'The String field must be filled in');
        }

        $validator->Register($_REQUEST['font_file'], V_EMPTY, 'The Font File field must be filled in');

        if( !is_file("{$GLOBALS['BASE_DIR']}/annotations/{$_REQUEST['font_file']}") )
        {
            $validator->SetError("The font file '{$_REQUEST['font_file']}' does not exist; upload it to the annotations directory");
        }

        $validator->Register($_REQUEST['text_size'], V_NUMERIC, 'The Text Size field must be numeric');
        $validator->Register($_REQUEST['text_color'], V_REGEX, 'The Text Color field must be in hex format (#RRGGBB)', '~^#[0-9a-f]+$~i');
        $validator->Register($_REQUEST['shadow_color'], V_REGEX, 'The Shadow Color field must be in hex format (#RRGGBB)', '~^#[0-9a-f]+$~i');
    }
    else
    {
        $validator->Register($_REQUEST['image_file'], V_EMPTY, 'The Image File field must be filled in');

        if( !is_file("{$GLOBALS['BASE_DIR']}/annotations/{$_REQUEST['image_file']}") )
        {
            $validator->SetError("The image file '{$_REQUEST['image_file']}' does not exist; upload it to the annotations directory");
        }

    }

    return $validator;
}

function &ValidateCategoryInput($adding = FALSE)
{
    global $DB;

    if( $_REQUEST['movies_preview_size'] == 'custom' )
    {
        $_REQUEST['movies_preview_size'] = $_REQUEST['movies_preview_size_custom'];
    }

    if( $_REQUEST['pics_preview_size'] == 'custom' )
    {
        $_REQUEST['pics_preview_size'] = $_REQUEST['pics_preview_size_custom'];
    }

    $_REQUEST['pics_extensions'] = FormatCommaSeparated($_REQUEST['pics_extensions']);
    $_REQUEST['movies_extensions'] = FormatCommaSeparated($_REQUEST['movies_extensions']);

    $v = new Validator();
    $v->Register($_REQUEST['name'], V_EMPTY, 'The Category Name(s) field must be filled in');
    $v->Register($_REQUEST['per_day'], V_NUMERIC, 'The Submissions Per Day field must be numeric');

    if( strpos($_REQUEST['name'], ',') !== FALSE )
    {
        $v->SetError('Category names may not contain commas');
    }

    $names = array();
    foreach( explode("\n", $_REQUEST['name']) as $name )
    {
        $name = trim($name);

        if( strtoupper($name) == 'MIXED' )
        {
            $v->SetError('The word MIXED is reserved and cannot be used as a category name');
        }

        if( preg_match('~^-~', $name) )
        {
            $v->SetError('Category names cannot start with a dash (-) character');
        }

        if( $adding )
        {
            if( $DB->Count('SELECT COUNT(*) FROM `tx_categories` WHERE `name`=?', array($name)) < 1 )
            {
                $names[] = $name;
            }
        }
        else
        {
            $names[] = $name;
        }
    }
    $_REQUEST['name'] = join("\n", $names);

    if( isset($_REQUEST['pics_allowed']) )
    {
        $v->Register($_REQUEST['pics_extensions'], V_EMPTY, 'The Pictures File Extensions field must be filled in');
        $v->Register($_REQUEST['pics_minimum'], V_NUMERIC, 'The Pictures Minimum Thumbs field must be numeric');
        $v->Register($_REQUEST['pics_maximum'], V_NUMERIC, 'The Pictures Maximum Thumbs field must be numeric');
        $v->Register($_REQUEST['pics_file_size'], V_NUMERIC, 'The Pictures Minimum Filesize field must be numeric');

        if( isset($_REQUEST['pics_preview_allowed']) )
        {
            $v->Register($_REQUEST['pics_preview_size'], V_REGEX, 'The Pictures Preview Dimensions must in WIDTHxHEIGHT format', '~^\d+x\d+$~');
        }
    }

    if( isset($_REQUEST['movies_allowed']) )
    {
        $v->Register($_REQUEST['movies_extensions'], V_EMPTY, 'The Movies File Extensions field must be filled in');
        $v->Register($_REQUEST['movies_minimum'], V_NUMERIC, 'The Movies Minimum Thumbs field must be numeric');
        $v->Register($_REQUEST['movies_maximum'], V_NUMERIC, 'The Movies Maximum Thumbs field must be numeric');
        $v->Register($_REQUEST['movies_file_size'], V_NUMERIC, 'The Movies Minimum Filesize field must be numeric');

        if( isset($_REQUEST['movies_preview_allowed']) )
        {
            $v->Register($_REQUEST['movies_preview_size'], V_REGEX, 'The Movies Preview Dimensions must in WIDTHxHEIGHT format', '~^\d+x\d+$~');
        }
    }

    return $v;
}

function &ValidateUserDefined($defs_table, $predefined_table, $editing = FALSE)
{
    global $DB, $C;

    // See if field name already exists
    $field_count = $DB->Count('SELECT COUNT(*) FROM # WHERE `name`=?', array($defs_table, $_REQUEST['name']));

    // Get pre-defined fields so there are no duplicates
    $predefined = $DB->GetColumns($predefined_table);

    $v = new Validator();
    $v->Register($_REQUEST['name'], V_EMPTY, 'The Field Name must be filled in');
    $v->Register($_REQUEST['name'], V_REGEX, 'The Field Name can contain only letters, numbers, and underscores', '/^[a-z0-9_]+$/i');
    $v->Register($_REQUEST['name'], V_LENGTH, 'The Field Name can be at most 30 characters', '0,30');
    $v->Register($_REQUEST['label'], V_EMPTY, 'The Label field must be filled in');

    if( $_REQUEST['type'] == FT_SELECT )
        $v->Register($_REQUEST['options'], V_EMPTY, 'The Options field must be filled in for this field type');

    if( $_REQUEST['validation'] != V_NONE )
        $v->Register($_REQUEST['validation_message'], V_EMPTY, 'The Validation Error field must be filled in');

    if( !$editing || ($_REQUEST['name'] != $_REQUEST['old_name']) )
    {
        $v->Register(in_array($_REQUEST['name'], $predefined), V_FALSE, 'The field name you have selected conflicts with a pre-defined field name');
        $v->Register($field_count, V_ZERO, 'A field with this name already exists');
    }

    return $v;
}

function RecompileTemplates()
{
    $t = new Template();
    $templates =& DirRead("{$GLOBALS['BASE_DIR']}/templates", '^(?!email)[^\.]+\.tpl$');

    // Compile global templates first
    foreach( glob("{$GLOBALS['BASE_DIR']}/templates/*global-*.tpl") as $global_template )
    {
        $t->compile_template(basename($global_template));
    }

    foreach( $templates as $template )
    {
        if( $template == 'default-tgp.tpl' )
        {
            continue;
        }

        if( !preg_match('~global-~', $template) )
        {
            $t->compile_template($template);
        }
    }
}

function GetValue($name)
{
    global $DB;

    $row = $DB->Row('SELECT * FROM `tx_stored_values` WHERE `name`=?', array($name));

    if( $row )
    {
        return $row['value'];
    }
    else
    {
        return null;
    }
}

function StoreValue($name, $value)
{
    global $DB;

    // See if it exists
    if( $DB->Count('SELECT COUNT(*) FROM `tx_stored_values` WHERE `name`=?', array($name)) )
    {
        $DB->Update('UPDATE `tx_stored_values` SET `value`=? WHERE `name`=?', array($value, $name));
    }
    else
    {
        $DB->Update('INSERT INTO `tx_stored_values` VALUES (?,?)', array($name, $value));
    }
}

function DoGalleryExport($args, $cli = FALSE)
{
    global $DB, $C;

    $message = 'Gallery export is in progress, please allow a few minutes to complete...';

    // Running from command line interface
    if( $cli )
    {
        $args = unserialize(GetValue('export_settings'));

        switch( $args['file_format'] )
        {
            case 'sql':
                GalleryExportSql($args);
                break;

            case 'delimited':
                GalleryExportDelimited($args);
                break;
        }
    }

    // Running from web browser
    else
    {
        if( $C['shell_exec'] && !empty($C['php_cli']) )
        {
            StoreValue('export_settings', serialize($args));
            shell_exec("{$C['php_cli']} cron.php --export " .
                       "--galleries-file=" . escapeshellarg($args['galleries-file']) . " " .
                       "--thumbs-file=" . escapeshellarg($args['thumbs-file']) . " " .
                       ">/dev/null 2>&1 &");
        }
        else
        {
            switch( $args['file_format'] )
            {
                case 'sql':
                    GalleryExportSql($args);
                    break;

                case 'delimited':
                    GalleryExportDelimited($args);
                    break;
            }

            $message = 'Gallery export has been completed';
        }
    }

    return $message;
}

function GalleryExportDelimited($args)
{
    global $DB, $C;

    $galleries_file = SafeFilename("{$GLOBALS['BASE_DIR']}/data/{$args['galleries-file']}", FALSE);
    $thumbs_file = empty($args['thumbs-file']) ? null : SafeFilename("{$GLOBALS['BASE_DIR']}/data/{$args['thumbs-file']}", FALSE);

    $delimiters = array('tab' => "\t", 'pipe' => '|');
    $delimiter = empty($args['custom_delimiter']) ? $delimiters[$args['delimiter']] : $args['custom_delimiter'];

    $sponsors =& $DB->FetchAll('SELECT * FROM `tx_sponsors`', null, 'sponsor_id');
    $categories =& $DB->FetchAll('SELECT * FROM `tx_categories`', null, 'tag');

    $galleries_fd = fopen($galleries_file, 'w');
    flock($galleries_fd, LOCK_EX);

    if( !empty($args['thumbs-file']) )
    {
        $thumbs_fd = fopen($thumbs_file, 'w');
        flock($thumbs_fd, LOCK_EX);
    }

    $_REQUEST = $args;
    $result = GetWhichGalleries();
    while( $gallery = $DB->NextRow($result) )
    {
        $preview = $DB->Row('SELECT * FROM `tx_gallery_previews` WHERE `gallery_id`=? LIMIT 1', array($gallery['gallery_id']));

        if( $preview )
        {
            $gallery['preview_url'] = $preview['preview_url'];
            $gallery['dimensions'] = $preview['dimensions'];

            if( !empty($args['thumbs-file']) && strpos($preview['preview_url'], $C['preview_url']) === 0 )
            {
                $gallery['preview_file'] = "{$preview['preview_id']}.jpg";
                $thumb_data = file_get_contents("{$C['preview_dir']}/{$preview['preview_id']}.jpg");
                fwrite($thumbs_fd, "{$preview['preview_id']}.jpg|" . base64_encode($thumb_data) . "\n");
            }
        }

        $output = array();
        foreach( $args['fields'] as $field )
        {
            switch($field)
            {
                case 'sponsor_id':
                    $output[] = $sponsors[$gallery[$field]]['name'];
                    break;

                case 'categories':
                    $category_list = array();
                    foreach( explode(' ', $gallery['categories']) as $category )
                    {
                        if( $category != MIXED_CATEGORY )
                        {
                            $category_list[] = $categories[$category]['name'];
                        }
                    }

                    $output[] = join(',', $category_list);
                    break;

                default:
                    $output[] = $gallery[$field];
                    break;
            }
        }

        fwrite($galleries_fd, join($delimiter, $output) . "\n");
    }
    $DB->Free($result);

    if( !empty($args['thumbs-file']) )
    {
        flock($thumbs_fd, LOCK_UN);
        fclose($thumbs_fd);
    }

    flock($galleries_fd, LOCK_UN);
    fclose($galleries_fd);
}

function GalleryExportSql($args)
{
    global $DB, $C;

    $galleries_file = SafeFilename("{$GLOBALS['BASE_DIR']}/data/{$args['galleries-file']}", FALSE);
    $thumbs_file = empty($args['thumbs-file']) ? null : SafeFilename("{$GLOBALS['BASE_DIR']}/data/{$args['thumbs-file']}", FALSE);

    $tables = array('tx_categories', 'tx_annotations', 'tx_icons', 'tx_sponsors', 'tx_partners', 'tx_partner_fields', 'tx_partner_icons', 'tx_gallery_field_defs', 'tx_partner_field_defs');

    $galleries_fd = fopen($galleries_file, 'w');
    flock($galleries_fd, LOCK_EX);

    if( !empty($args['thumbs-file']) )
    {
        $thumbs_fd = fopen($thumbs_file, 'w');
        flock($thumbs_fd, LOCK_EX);
    }

    foreach( $tables as $table )
    {
        $row = $DB->Row('SHOW CREATE TABLE #', array($table));
        $create = str_replace(array("\r", "\n"), '', $row['Create Table']);
        fwrite($galleries_fd, "DROP TABLE IF EXISTS `$table`;\n");
        fwrite($galleries_fd, "$create;\n");
        fwrite($galleries_fd, "DELETE FROM `$table`;\n");
        fwrite($galleries_fd, "LOCK TABLES `$table` WRITE;\n");
        fwrite($galleries_fd, "ALTER TABLE `$table` DISABLE KEYS;\n");

        $result = mysql_unbuffered_query("SELECT * FROM $table", $DB->handle);
        while( $row = mysql_fetch_row($result) )
        {
            $row = array_map('mysql_real_escape_string', $row);
            fwrite($galleries_fd, "INSERT INTO `$table` VALUES ('" . join("','", $row) . "');\n");
        }
        $DB->Free($result);

        fwrite($galleries_fd, "UNLOCK TABLES;\n");
        fwrite($galleries_fd, "ALTER TABLE `$table` ENABLE KEYS;\n");
    }

    // Get CREATE clause for tx_gallery_fields
    $row = $DB->Row('SHOW CREATE TABLE #', array('tx_gallery_fields'));
    $create = str_replace(array("\r", "\n"), '', $row['Create Table']);
    fwrite($galleries_fd, "DROP TABLE IF EXISTS `tx_gallery_fields`;\n");
    fwrite($galleries_fd, "$create;\n");

    $gallery_tables = array('tx_gallery_previews', 'tx_gallery_fields', 'tx_gallery_icons');

    $_REQUEST = $args;
    $result = GetWhichGalleries();
    while( $gallery = mysql_fetch_row($result) )
    {
        $gallery = array_map('mysql_real_escape_string', $gallery);
        fwrite($galleries_fd, "INSERT INTO `tx_galleries` VALUES ('" . join("','", $gallery) . "');\n");

        foreach( $gallery_tables as $table )
        {
            $sub_result = $DB->Query("SELECT * FROM `$table` WHERE `gallery_id`=?", array($gallery[0]));
            while( $row = mysql_fetch_row($sub_result) )
            {
                if( $table == 'tx_gallery_previews' && !empty($args['thumbs-file']) && strpos($row[2], $C['preview_url']) === 0 )
                {
                    $thumb_data = file_get_contents("{$C['preview_dir']}/{$row[0]}.jpg");
                    fwrite($thumbs_fd, "{$row[0]}.jpg|" . base64_encode($thumb_data) . "\n");
                }

                $row = array_map('mysql_real_escape_string', $row);
                fwrite($galleries_fd, "INSERT INTO `$table` VALUES ('" . join("','", $row) . "');\n");
            }
            $DB->Free($sub_result);
        }
    }
    $DB->Free($result);

    if( !empty($args['thumbs-file']) )
    {
        flock($thumbs_fd, LOCK_UN);
        fclose($thumbs_fd);
    }

    flock($galleries_fd, LOCK_UN);
    fclose($galleries_fd);
}

function DoDatabaseBackup($args, $cli = FALSE)
{
    global $DB, $C;

    IniParse("{$GLOBALS['BASE_DIR']}/includes/tables.php", TRUE, $tables);

    $sql_file = SafeFilename("{$GLOBALS['BASE_DIR']}/data/{$args['sql-file']}", FALSE);
    $thumbs_file = empty($args['thumbs-file']) ? null : SafeFilename("{$GLOBALS['BASE_DIR']}/data/{$args['thumbs-file']}", FALSE);
    $archive_file = empty($args['archive-file']) ? null : SafeFilename("{$GLOBALS['BASE_DIR']}/data/{$args['archive-file']}", FALSE);
    $message = 'The database backup function has been started, please allow a few minutes for it to complete...';

    // Running from the command line
    if( $cli )
    {
        $to_archive = array($sql_file);

        if( !empty($C['mysqldump']) )
        {
            $command = "{$C['mysqldump']} " .
                       "-u" . escapeshellarg($C['db_username']) . " " .
                       "-p" . escapeshellarg($C['db_password']) . " " .
                       "-h" . escapeshellarg($C['db_hostname']) . " " .
                       "--opt -Q " .
                       escapeshellarg($C['db_name']) . " " .
                       join(' ', array_keys($tables)) .
                       " >" . escapeshellarg($sql_file);

            shell_exec($command);
        }
        else
        {
            DumpSQLTables($sql_file, $tables);
        }

        if( !empty($args['thumbs-file']) )
        {
            $to_archive[] = $thumbs_file;
            DumpThumbnails($thumbs_file);
        }

        if( !empty($args['archive-file']) && !empty($C['tar']) && !empty($C['gzip']) )
        {
            ArchiveFiles($archive_file, $to_archive);

            foreach( $to_archive as $archived_file )
            {
                @unlink($archived_file);
            }
        }
    }

    // Running through the web server
    else
    {
        if( $C['shell_exec'] && !empty($C['php_cli']) )
        {
            $command = "{$C['php_cli']} cron.php --backup " .
                       "--sql-file=" . escapeshellarg($args['sql-file']) . " " .
                       "--thumbs-file=" . escapeshellarg($args['thumbs-file']) . " " .
                       "--archive-file=" . escapeshellarg($args['archive-file']) . " " .
                       ">/dev/null 2>&1 &";

            shell_exec($command);
        }
        else
        {
            DumpSQLTables($sql_file, $tables);

            if( !empty($args['thumbs-file']) )
            {
                DumpThumbnails($thumbs_file);
            }

            $message = 'The database backup has been completed';
        }
    }

    StoreValue('last_backup', MYSQL_NOW);

    return $message;
}

function DoDatabaseRestore($args, $cli = FALSE)
{
    global $DB, $C;

    $message = 'The database restore function has been started, please allow a few minutes for it to complete...';
    $sql_file = SafeFilename("{$GLOBALS['BASE_DIR']}/data/{$args['sql-file']}", FALSE);
    $thumbs_file = empty($args['thumbs-file']) ? null : SafeFilename("{$GLOBALS['BASE_DIR']}/data/{$args['thumbs-file']}", FALSE);

    // Running from the command line
    if( $cli )
    {
        $to_archive = array($sql_file);

        if( !empty($C['mysql']) )
        {
            $command = "{$C['mysql']} " .
                       "-u" . escapeshellarg($C['db_username']) . " " .
                       "-p" . escapeshellarg($C['db_password']) . " " .
                       "-h" . escapeshellarg($C['db_hostname']) . " " .
                       escapeshellarg($C['db_name']) . " " .
                       "<" . escapeshellarg($sql_file);

            shell_exec($command);
        }
        else
        {
            RestoreSQLTables($sql_file);
        }

        if( !empty($args['thumbs-file']) )
        {
            RestoreThumbnails($thumbs_file);
        }
    }

    // Running through the web server
    else
    {
        if( $C['shell_exec'] && !empty($C['php_cli']) )
        {
            $command = "{$C['php_cli']} cron.php --restore " .
                       "--sql-file=" . escapeshellarg($args['sql-file']) . " " .
                       "--thumbs-file=" . escapeshellarg($args['thumbs-file']) . " " .
                       ">/dev/null 2>&1 &";

            shell_exec($command);
        }
        else
        {
            RestoreSQLTables($sql_file);

            if( !empty($args['thumbs-file']) )
            {
                RestoreThumbnails($thumbs_file);
            }

            $message = 'The database restore has been completed';
        }
    }

    return $message;
}

function ArchiveFiles($filename, $files)
{
    global $DB, $C;

    array_map('escapeshellarg', $files);

    $command = "tar czf " . escapeshellarg($filename) . " " . join(' ', $files) . " >/dev/null 2>&1";

    shell_exec($command);

    @chmod($filename, 0666);
}

function RestoreThumbnails($filename)
{
    global $DB, $C;

    $fd = fopen($filename, 'r');

    if( $fd )
    {
        while( !feof($fd) )
        {
            list($file, $thumb_data) = explode('|', trim(fgets($fd)));

            if( IsEmptyString($file) )
            {
                continue;
            }

            FileWrite("{$C['preview_dir']}/$file", base64_decode($thumb_data));
        }

        fclose($fd);

        @chmod($filename, 0666);
    }
}

function DumpThumbnails($filename)
{
    global $DB, $C;

    $thumbnails = glob("{$C['preview_dir']}/*.jpg");

    $fd = fopen($filename, 'w');

    if( $fd )
    {
        foreach( $thumbnails as $thumbnail )
        {
            $thumb_data = file_get_contents($thumbnail);
            $file = basename($thumbnail);

            fwrite($fd, "$file|" . base64_encode($thumb_data) . "\n");
        }

        fclose($fd);

        @chmod($filename, 0666);
    }
}

function DumpSQLTables($filename, &$tables, $use_keys = TRUE)
{
    global $DB;

    $fd = fopen($filename, 'w');

    if( $fd )
    {
        if( $use_keys )
        {
            $tables = array_keys($tables);
        }

        foreach( $tables as $table )
        {
            $row = $DB->Row('SHOW CREATE TABLE #', array($table));
            $create = str_replace(array("\r", "\n"), '', $row['Create Table']);

            fwrite($fd, "DROP TABLE IF EXISTS `$table`;\n");
            fwrite($fd, "$create;\n");

            fwrite($fd, "DELETE FROM `$table`;\n");
            fwrite($fd, "LOCK TABLES `$table` WRITE;\n");
            fwrite($fd, "ALTER TABLE `$table` DISABLE KEYS;\n");

            $result = mysql_unbuffered_query("SELECT * FROM $table", $DB->handle);
            while( $row = mysql_fetch_row($result) )
            {
                $row = array_map('PrepareRow', $row);
                fwrite($fd, "INSERT INTO `$table` VALUES (" . join(",", $row) . ");\n");
            }
            $DB->Free($result);

            fwrite($fd, "UNLOCK TABLES;\n");
            fwrite($fd, "ALTER TABLE `$table` ENABLE KEYS;\n");
        }

        fclose($fd);

        @chmod($filename, 0666);
    }
}

function RestoreSQLTables($filename)
{
    global $DB;

    $buffer = '';
    $fd = fopen($filename, 'r');

    if( $fd )
    {
        while( !feof($fd) )
        {
            $line = trim(fgets($fd));

            // Skip comments and empty lines
            if( empty($line) || preg_match('~^(/\*|--)~', $line) )
            {
                continue;
            }

            if( !preg_match('~;$~', $line) )
            {
                $buffer .= $line;
                continue;
            }

            // Remove trailing ; character
            $line = preg_replace('~;$~', '', $line);

            $buffer .= $line;

            mysql_query($buffer, $DB->handle);

            $buffer = '';
        }

        fclose($fd);
    }
}

function PrepareRow($field)
{
    global $DB;

    if( $field == NULL )
    {
        return 'NULL';
    }
    else
    {
        return "'" . mysql_real_escape_string($field, $DB->handle) . "'";
    }
}

function GetServerCapabilities()
{
    // Handle recursion issues with CGI version of PHP
    if( getenv('PHP_REPEAT') ) return;
    putenv('PHP_REPEAT=TRUE');

    $GLOBALS['LAST_ERROR'] = null;

    $server = array('safe_mode' => TRUE,
                    'shell_exec' => FALSE,
                    'have_gd' => extension_loaded('gd'),
                    'have_magick' => FALSE,
                    'magick6' => FALSE,
                    'have_imager' => FALSE,
                    'php_cli' => null,
                    'mysql' => null,
                    'mysqldump' => null,
                    'convert' => null,
                    'composite' => null,
                    'dig' => null,
                    'tar' => null,
                    'gzip' => null,
                    'message' => array());

    set_error_handler('GetServerCapabilitiesError');
    error_reporting(E_ALL);

    $server['safe_mode'] = @ini_get('safe_mode');

    if( $server['safe_mode'] === null || isset($GLOBALS['LAST_ERROR']) )
    {
        $server['safe_mode'] = TRUE;
        $server['message'][] = "The ini_get() PHP function appears to be disabled on your server\nPHP says: " . $GLOBALS['LAST_ERROR'];
    }
    else if( $server['safe_mode'] )
    {
        $server['message'][] = "Your server is running PHP with safe_mode enabled";

        // Do tests on safe_mode_exec_dir
    }
    else
    {
        $server['safe_mode'] = FALSE;
        $GLOBALS['LAST_ERROR'] = null;

        $open_basedir = ini_get('open_basedir');

        // See if shell_exec is available on the server
        @shell_exec('ls -l');
        if( isset($GLOBALS['LAST_ERROR']) )
        {
            $server['shell_exec'] = FALSE;
            $server['message'][] = "The shell_exec() PHP function appears to be disabled on your server\nPHP says: " . $GLOBALS['LAST_ERROR'];
        }
        else
        {
            $server['shell_exec'] = TRUE;
        }

        if( $server['shell_exec'] )
        {
            // Check for cli version of PHP
            $server['php_cli'] = LocateExecutable('php', '-v', '(cli)', $open_basedir);

            if( !$server['php_cli'] )
            {
                $server['php_cli'] = LocateExecutable('php-cli', '-v', '(cli)', $open_basedir);
            }

            // Check for safe_mode
            if( $server['php_cli'] )
            {
                $cli_settings = shell_exec("{$server['php_cli']} -r \"echo serialize(array('safe_mode' => ini_get('safe_mode')));\" 2>/dev/null");
                $cli_settings = unserialize($cli_settings);

                if( $cli_settings !== FALSE )
                {
                    if( $cli_settings['safe_mode'] )
                    {
                        $server['message'][] = 'The CLI version of PHP is running with safe_mode enabled';
                    }
                }
            }

            // Check for mysql executables
            $server['mysql'] = LocateExecutable('mysql', null, null, $open_basedir);
            $server['mysqldump'] = LocateExecutable('mysqldump', null, null, $open_basedir);

            // Check for imagemagick executables
            $server['convert'] = LocateExecutable('convert', null, null, $open_basedir);
            $server['composite'] = LocateExecutable('composite', null, null, $open_basedir);

            // Check for dig
            $server['dig'] = LocateExecutable('dig', null, null, $open_basedir);

            // Check for archiving executables
            $server['tar'] = LocateExecutable('tar', null, null, $open_basedir);
            $server['gzip'] = LocateExecutable('gzip', null, null, $open_basedir);

            if( $server['convert'] && $server['composite'] )
            {
                $server['have_magick'] = TRUE;
                $server['magick6'] = FALSE;

                // Get version
                $output = shell_exec("{$server['convert']} -version");

                if( preg_match('~ImageMagick 6\.~i', $output) )
                {
                    $server['magick6'] = TRUE;
                }
            }
        }
    }

    $server['have_imager'] = $server['have_magick'] || $server['have_gd'];

    error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);
    restore_error_handler();

    return $server;
}

function LocateExecutable($executable, $output_arg = null, $output_search = null, $open_basedir = FALSE)
{

    $executable_dirs = array('/bin',
                             '/usr/bin',
                             '/usr/local/bin',
                             '/usr/local/mysql/bin',
                             '/sbin',
                             '/usr/sbin',
                             '/usr/lib',
                             '/usr/local/ImageMagick/bin',
                             '/usr/X11R6/bin');

    if( isset($GLOBALS['BASE_DIR']) )
    {
        $executable_dirs[] = "{$GLOBALS['BASE_DIR']}/bin";
    }

    if( isset($_SERVER['DOCUMENT_ROOT']) )
    {
        $executable_dirs[] = realpath($_SERVER['DOCUMENT_ROOT'] . '/../bin/');
    }

    // No open_basedir restriction
    if( !$open_basedir )
    {
        foreach( $executable_dirs as $dir )
        {
            if( @is_file("$dir/$executable") && @is_executable("$dir/$executable") )
            {
                if( $output_arg )
                {
                    $output = shell_exec("$dir/$executable $output_arg");

                    if( stristr($output, $output_search) !== FALSE )
                    {
                        return "$dir/$executable";
                    }
                }
                else
                {
                    return "$dir/$executable";
                }
            }
        }
    }

    $which = trim(shell_exec("which $executable"));

    if( !empty($which) )
    {
        if( $output_arg )
        {
            $output = shell_exec("$which $output_arg");

            if( stristr($output, $output_search) !== FALSE )
            {
                return $which;
            }
        }
        else
        {
            return $which;
        }
    }


    $whereis = trim(shell_exec("whereis -B ".join(' ', $executable_dirs)." -f $executable"));
    preg_match("~$executable: (.*)~", $whereis, $matches);
    $whereis = explode(' ', trim($matches[1]));

    if( count($whereis) )
    {
        if( $output_arg )
        {
            foreach( $whereis as $executable )
            {
                $output = shell_exec("$executable $output_arg");

                if( stristr($output, $output_search) !== FALSE )
                {
                    return $executable;
                }
            }
        }
        else
        {
            return $whereis[0];
        }
    }

    return null;
}

function GetServerCapabilitiesError($code, $string, $file, $line)
{
    $GLOBALS['LAST_ERROR'] = $string;
}

function CheckAccessList($ajax = FALSE)
{
    global $C, $allowed_ips;

    $ip = $_SERVER['REMOTE_ADDR'];
    $hostname = gethostbyaddr($ip);
    $found = FALSE;

    require_once("{$GLOBALS['BASE_DIR']}/includes/access-list.php");

    if( is_array($allowed_ips) )
    {
        if( count($allowed_ips) < 1 )
        {
            return;
        }

        foreach( $allowed_ips as $check_ip )
        {
            $check_ip = trim($check_ip);
            $check_ip = preg_quote($check_ip);

            // Setup the wildcard items
            $check_ip = preg_replace('/\\\\\*/', '.*?', $check_ip);
            $check_ip = preg_replace('/\\\\\*/', '\\*', $check_ip);

            if( preg_match("/^$check_ip$/", $ip) || preg_match("/^$check_ip$/", $hostname)  )
            {
                $found = TRUE;
                break;
            }
        }

        if( !$found )
        {
            if( $ajax )
            {
                $json = new JSON();
                echo $json->encode(array('status' => JSON_FAILURE,
                                         'message' => "The IP address you are connecting from ({$_SERVER['REMOTE_ADDR']}) is not allowed to access this function."));
            }
            else
            {
                include_once('no-access.php');
            }
            exit;
        }
    }
    else
    {
        $GLOBALS['no_access_list'] = TRUE;
    }
}

function CheckTemplateCode(&$code)
{
    $warnings = array();

    if( preg_match_all('~(\{\$.*?\})~', $code, $matches) )
    {
        foreach( $matches[1] as $match )
        {
            if( strpos($match, '$config.') )
            {
                continue;
            }

            if( !preg_match('~\|.*?\}~', $match) )
            {
                $warnings[] = "The template value $match is not escaped with htmlspecialchars and may pose a security risk";
            }
        }
    }

    return join('<br />', $warnings);
}

function AutoBlacklist(&$gallery, $reason = '')
{
    global $DB;

    // Ban URL
    if( !$DB->Count('SELECT COUNT(*) FROM `tx_blacklist` WHERE `type`=? AND `value`=?', array('url', $gallery['gallery_url'])) )
    {
        $parsed_url = parse_url($gallery['gallery_url']);
        $DB->Update('INSERT INTO `tx_blacklist` VALUES (?,?,?,?,?)', array(null, 'url', 0, $parsed_url['host'], $reason));
    }

    // Ban IP
    if( !$DB->Count('SELECT COUNT(*) FROM `tx_blacklist` WHERE `type`=? AND `value`=?', array('submit_ip', $gallery['submit_ip'])) )
    {
        $DB->Update('INSERT INTO `tx_blacklist` VALUES (?,?,?,?,?)', array(null, 'submit_ip', 0, $gallery['submit_ip'], $reason));
    }

    // Ban e-mail
    if( !$DB->Count('SELECT COUNT(*) FROM `tx_blacklist` WHERE `type`=? AND `value`=?', array('email', $gallery['email'])) )
    {
        $DB->Update('INSERT INTO `tx_blacklist` VALUES (?,?,?,?,?)', array(null, 'email', 0, $gallery['email'], $reason));
    }
}

function GetCategoryIdList($gallery_id)
{
    global $DB;

    $categories = array();
    $result = $DB->Query('SELECT * FROM `tx_gallery_cats` WHERE `gallery_id`=?', array($gallery_id));

    while( $category = $DB->NextRow($result) )
    {
        $categories[] = $category['category_id'];
    }

    return join(',', $categories);
}

function AdminFormField(&$options)
{
    $options['tag_attributes'] = str_replace(array('&quot;', '&#039;'), array('"', "'"), $options['tag_attributes']);

    switch($options['type'])
    {
    case FT_CHECKBOX:
        if( strlen($options['label']) > 70 )
        {
            $options['label'] = '<span title="'.$options['label'].'">' . StringChop($options['label'], 70, true) . "</span>";
        }
        if( preg_match('/value\s*=\s*["\']?([^\'"]+)\s?/i', $options['tag_attributes'], $matches) )
            $options['tag_attributes'] = 'class="checkbox" value="'.$matches[1].'"';
        else
            $options['tag_attributes'] = 'class="checkbox"';
        break;

    case FT_SELECT:
        if( strlen($options['label']) > 20 )
        {
            $options['label'] = '<span title="'.$options['label'].'">' . StringChop($options['label'], 20) . "</span>";
        }
        $options['tag_attributes'] = '';
        break;

    case FT_TEXT:
        if( strlen($options['label']) > 20 )
        {
            $options['label'] = '<span title="'.$options['label'].'">' . StringChop($options['label'], 20) . "</span>";
        }
        $options['tag_attributes'] = 'size="70"';
        break;

    case FT_TEXTAREA:
        if( strlen($options['label']) > 20 )
        {
            $options['label'] = '<span title="'.$options['label'].'">' . StringChop($options['label'], 20) . "</span>";
        }
        $options['tag_attributes'] = 'rows="5" cols="80"';
        break;
    }
}

##REPLACE

function PageLinks($data)
{
    $html = '';

    if( $data['prev'] )
    {
        $html .= ' <a href="javascript:void(0);" onclick="return Search.jump(1)"><img src="images/page-first.png" border="0" alt="First" title="First"></a> ' .
                 ' <a href="javascript:void(0);" onclick="return Search.go(-1)"><img src="images/page-prev.png" border="0" alt="Previous" title="Previous"></a> ';
    }
    else
    {
        $html .= ' <img src="images/page-first-disabled.png" border="0" alt="First" title="First"> ' .
                 ' <img src="images/page-prev-disabled.png" border="0" alt="Previous" title="Previous"> ';
    }

    if( $data['pages'] > 2 )
    {
        $html .= ' &nbsp; <input type="text" id="_pagenum_" value="' . $data['page'] . '" size="2" class="centered pagenum" onkeypress="return event.keyCode!=13" onkeyup="Search.jump(null, event)" /> of ' . $data['fpages'] . ' &nbsp; ';
    }

    if( $data['next'] )
    {
        $html .= ' <a href="javascript:void(0);" onclick="return Search.go(1)"><img src="images/page-next.png" border="0" alt="Next" title="Next"></a> ' .
                 ' <a href="javascript:void(0);" onclick="return Search.jump('. $data['pages'] .')">' .
                 '<img src="images/page-last.png" border="0" alt="Last" title="Last"></a> ';
    }
    else
    {
        $html .= ' <img src="images/page-next-disabled.png" border="0" alt="Next" title="Next"> ' .
                 ' <img src="images/page-last-disabled.png" border="0" alt="Last" title="Last"> ';
    }

    return $html;
}

function CheckBox($name, $class, $value, $checked, $flag = 0)
{
    $checked_code = '';

    if( ($value == $checked) || ($flag & $value) )
        $checked_code = ' checked="checked"';

    return "<input type=\"checkbox\" name=\"$name\" id=\"$name\" class=\"$class\" value=\"$value\"$checked_code />";
}

function ValidFunction($function)
{
    return (preg_match('/^tx[a-zA-Z0-9_]+/', $function) > 0 && function_exists($function));
}

function ValidLogin()
{
    global $DB;

    $error = 'Invalid username/password combination';

    if( isset($_POST['login_username']) && isset($_POST['login_password']) )
    {
        $_POST['login_username'] = trim($_POST['login_username']);
        $_POST['login_password'] = trim($_POST['login_password']);

        $administrator = $DB->Row('SELECT * FROM `tx_administrators` WHERE `username`=?', array($_POST['login_username']));
        if( $administrator && $administrator['password'] == sha1($_POST['login_password']) )
        {
            $session = sha1(uniqid(rand(), true) . $_POST['login_password']);
            setcookie('tgpx', 'username=' . urlencode($_POST['login_username']) . '&session=' . $session, time() + 86400);
            $DB->Update('UPDATE `tx_administrators` SET ' .
                        '`session`=?,' .
                        '`session_start`=?, ' .
                        '`date_login`=?, ' .
                        '`date_last_login`=?, ' .
                        '`login_ip`=?, ' .
                        '`last_login_ip`=? ' .
                        'WHERE `username`=?',
                        array($session,
                              time(),
                              MYSQL_NOW,
                              $administrator['date_login'],
                              $_SERVER['REMOTE_ADDR'],
                              $administrator['login_ip'],
                              $administrator['username']));

            $_SERVER['REMOTE_USER'] = $administrator['username'];

            return TRUE;
        }
    }
    else if( isset($_COOKIE['tgpx']) )
    {
        parse_str($_COOKIE['tgpx'], $cookie);

        $administrator = $DB->Row('SELECT * FROM `tx_administrators` WHERE `username`=?', array($cookie['username']));

        if( $administrator && $cookie['session'] == $administrator['session'] )
        {
            if( $administrator['session_start'] < time() - SESSION_LENGTH )
            {
                $session = sha1(uniqid(rand(), true) . $administrator['password']);
                setcookie('tgpx', 'username=' . urlencode($administrator['username']) . '&session=' . $session, time() + 86400);
                $DB->Update('UPDATE `tx_administrators` SET ' .
                            '`session`=?,' .
                            '`session_start`=? ' .
                            'WHERE `username`=?',
                            array($session,
                                  time(),
                                  $cookie['username']));
            }

            $_SERVER['REMOTE_USER'] = $administrator['username'];

            return TRUE;
        }
        else
        {
            $error = 'Session expired or invalid username/password';
        }
    }
    else
    {
        $error = '';
    }

    return $error;
}

function VerifyPrivileges($privilege, $ajax = FALSE)
{
    global $DB;

    $administrator = $DB->Row('SELECT * FROM `tx_administrators` WHERE `username`=?', array($_SERVER['REMOTE_USER']));

    if( $administrator['type'] == ACCOUNT_ADMINISTRATOR )
    {
        return;
    }

    if( !($administrator['rights'] & $privilege) )
    {
        if( $ajax )
        {
            $json = new JSON();
            echo $json->encode(array('status' => JSON_FAILURE, 'message' => 'You do not have the necessary privileges to access this function'));
        }
        else
        {
            $error = 'You do not have the necessary privileges to access this function';
            include_once('includes/error.php');
        }
        exit;
    }
}

function VerifyAdministrator($ajax = FALSE)
{
    global $DB;

    $administrator = $DB->Row('SELECT * FROM `tx_administrators` WHERE `username`=?', array($_SERVER['REMOTE_USER']));

    if( $administrator['type'] != ACCOUNT_ADMINISTRATOR )
    {
        if( $ajax )
        {
            $json = new JSON();
            echo $json->encode(array('status' => JSON_FAILURE, 'message' => 'This function is only available to administrator level accounts'));
        }
        else
        {
            $error = 'This function is only available to administrator level accounts';
            include_once('includes/error.php');
        }
        exit;
    }
}

function GenerateFlags(&$array, $pattern)
{
    $flags = 0x00000000;

    foreach($array as $name => $value)
    {
        if( preg_match("/$pattern/", $name) )
        {
            $flags = $flags | intval($value);
        }
    }

    return $flags;
}

function WriteConfig(&$settings)
{
    global $C;

    unset($settings['r']);
    unset($settings['message']);

    $C = array_merge($C, $settings);

    $fd = fopen("{$GLOBALS['BASE_DIR']}/includes/config.php", "r+");

    fwrite($fd, "<?PHP\n\$C = array();\n");

    foreach($C as $setting => $value)
    {
        if( is_numeric($value) && $setting != 'db_password' )
        {
            fwrite($fd, "\$C['$setting'] = $value;\n");
        }
        else if( IsBool($value) )
        {
            $value = $value ? 'TRUE' : 'FALSE';
            fwrite($fd, "\$C['$setting'] = $value;\n");
        }
        else
        {
            fwrite($fd, "\$C['$setting'] = '" . addslashes($value) . "';\n");
        }
    }

    fwrite($fd, "?>");
    ftruncate($fd, ftell($fd));
    fclose($fd);
}
?>
