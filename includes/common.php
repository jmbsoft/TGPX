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

// Globals
$GLOBALS['VERSION'] = '1.1.0-SS';
$GLOBALS['RELEASE'] = 'April 15, 2010 09:15';
$GLOBALS['BASE_DIR'] = realpath(dirname(__FILE__) . '/..');
$GLOBALS['ADMIN_DIR'] = "$BASE_DIR/admin";
$GLOBALS['FILE_PERMISSIONS'] = 0666;
$GLOBALS['DEFAULT_PAGINATION'] = array('total' => 0, 'pages' => 0, 'page' => 1, 'limit' => 0, 'start' => 0, 'end' => 0, 'prev' => 0, 'next' => 0);
$GLOBALS['L'] = array();
$GLOBALS['DEBUG'] = TRUE;


// Setup error reporting and other PHP options
if( !defined('E_STRICT') ) define('E_STRICT', 2048);
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);
@set_time_limit(0);
@ini_set('pcre.backtrack_limit', 1000000); // PHP 5 sets limits when running regex on large strings, so increase the default
set_error_handler('Error');
@set_magic_quotes_runtime(0);
if( function_exists('date_default_timezone_set') )
{
    date_default_timezone_set('America/Chicago');
}
register_shutdown_function('Shutdown');


// Load the language file
if( file_exists("{$GLOBALS['BASE_DIR']}/includes/language.php") )
{
    require_once("{$GLOBALS['BASE_DIR']}/includes/language.php");
}


// Load variables
if( file_exists("{$GLOBALS['BASE_DIR']}/includes/config.php") )
{
    require_once("{$GLOBALS['BASE_DIR']}/includes/config.php");
}



// Notifications
define('E_CHEAT_REPORT', 0x00000001);
define('E_SCANNER_COMPLETE', 0x00000002);
define('E_PARTNER_REQUEST', 0x00000004);


// Field types
define('FT_CHECKBOX', 'Checkbox');
define('FT_TEXTAREA', 'Textarea');
define('FT_TEXT', 'Text');
define('FT_SELECT', 'Select');


// Date formats
define('DF_DATETIME', 'Y-m-d H:i:s');
define('DF_DATE', 'Y-m-d');
define('DF_SHORT', 'm-d-Y h:ia');


// Mail types
define('MT_PHP', 0);
define('MT_SENDMAIL', 1);
define('MT_SMTP', 2);


// Search types
define('ST_CONTAINS', 'contains');
define('ST_MATCHES', 'matches');
define('ST_STARTS', 'starts');
define('ST_BETWEEN', 'between');
define('ST_GREATER', 'greater');
define('ST_LESS', 'less');
define('ST_EMPTY', 'empty');
define('ST_ANY', 'any');
define('ST_IN', 'in');
define('ST_NOT_IN', 'not_in');
define('ST_NOT_EMPTY', 'not_empty');
define('ST_NULL', 'null');
define('ST_NOT_MATCHES', 'not_matches');
define('ST_NOT_NULL', 'not_null');


// Gallery formats
define('FMT_PICTURES', 'pictures');
define('FMT_MOVIES', 'movies');


// Build types
define('BT_BUILD', 0);
define('BT_BUILD_WITH_NEW', 1);


// Other
define('MYSQL_EXPIRES', '2000-01-01 00:00:00');
define('MYSQL_NOW', gmdate(DF_DATETIME, TimeWithTz()));
define('MYSQL_CURDATE', gmdate(DF_DATE, TimeWithTz()));
define('TIME_NOW', TimeWithTz());
define('RE_DATETIME', '~^\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d$~');
define('MIXED_CATEGORY', '516a2342953891f249b58ff1c3943723');
define('JSON_SUCCESS', 'Success');
define('JSON_FAILURE', 'Failure');


// Blacklist types
$GLOBALS['BLIST_TYPES'] = array('submit_ip' => 'Submitter IP',
                                'email' => 'E-mail Address',
                                'url' => 'Domain/URL',
                                'domain_ip' => 'Domain IP',
                                'word' => 'Word',
                                'html' => 'HTML',
                                'headers' => 'HTTP Headers',
                                'dns' => 'DNS Server');

// Whitelist types
$GLOBALS['WLIST_TYPES'] = array('submit_ip' => 'Submitter IP',
                                'email' => 'E-mail Address',
                                'url' => 'Domain/URL',
                                'domain_ip' => 'Domain IP',
                                'dns' => 'DNS Server');

function CompareSearches($a, $b)
{
    return strcmp($a['term'], $b['term']);
}

function RssTimezone()
{
    global $C;

    list($hour, $half) = explode('.', $C['timezone']);

    return sprintf('%s%02d%02d', $hour < 0 ? '-' : '+', abs($hour), $half ? 30 : 0);
}

function ProcessClickLog()
{
    global $DB, $C;

    $clicks = array();
    $file = "{$GLOBALS['BASE_DIR']}/data/clicklog";

    if( !is_file($file) || filesize($file) === 0 )
    {
        return;
    }

    //$lines = file($file);
    $fp = fopen($file, 'r');

    while( !feof($fp) )
    {
        $line = trim(fgets($fp));
        list($gallery_id, $ip_address) = explode('|', $line);

        if( !isset($clicks[$gallery_id]) )
        {
            $clicks[$gallery_id] = array('ip' => array(), 'clicks' => 0);
        }

        if( empty($ip_address) || !isset($clicks[$gallery_id]['ip'][$ip_address]) )
        {
            $clicks[$gallery_id]['clicks']++;
            $clicks[$gallery_id]['ip'][$ip_address] = 1;
        }
    }
    fclose($fp);
    FileWrite($file, '');

    foreach( $clicks as $gallery_id => $data )
    {
        if( is_numeric($gallery_id) )
        {
            $DB->Update('UPDATE `tx_galleries` SET `clicks`=`clicks`+? WHERE `gallery_id`=?', array($data['clicks'], $gallery_id));
        }
    }

    unset($clicks);
}

function PrepareCategoriesBuild()
{
    global $DB, $C;

    $GLOBALS['_prep_category_build'] = TRUE;

    $DB->Update('DELETE FROM `tx_categories_build`');

    if( $C['one_category_per_gallery'] )
    {
        $DB->Update('INSERT INTO `tx_categories_build` SELECT ' .
                    '`category_id`,' .
                    '`name`,' .
                    '0, ' .
                    '0, ' .
                    '0, ' .
                    '0, ' .
                    'NULL ' .
                    'FROM `tx_categories`');

        $result = $DB->Query('SELECT ' .
                             '`categories`, ' .
                             'SUM(IF(`status`=? OR `status`=?, 1, 0)) AS `galleries`, ' .
                             'SUM(IF(`status`=?, `clicks`, 0)) AS `clicks`, ' .
                             'SUM(IF(`status`=?, `build_counter`, 0)) AS `build_counter`, ' .
                             'SUM(IF(`status`=?, 1, 0)) AS `used` ' .
                             'FROM `tx_galleries` GROUP BY `categories`',
                             array('used',
                                   'approved',
                                   'used',
                                   'used',
                                   'used',
                                   $category['tag']));

        while( $catstats = $DB->NextRow($result) )
        {
            list($mixed, $category_tag) = explode(' ', $catstats['categories']);
            $category = $GLOBALS['CATEGORY_CACHE_TAG'][$category_tag];
            $page = $DB->Row('SELECT `page_url` FROM `tx_pages` WHERE `category_id`=? ORDER BY `build_order` LIMIT 1', array($category['category_id']));

            $DB->Update('REPLACE INTO `tx_categories_build` VALUES (?,?,?,?,?,?,?)',
                        array($category['category_id'],
                              $category['name'],
                              $catstats['galleries'],
                              $catstats['clicks'],
                              $catstats['build_counter'],
                              $catstats['used'],
                              $page['page_url']));
        }
        $DB->Free($result);
    }
    else
    {
        $result = $DB->Query('SELECT * FROM `tx_categories` WHERE `hidden`=0');
        while( $category = $DB->NextRow($result) )
        {
            $page = $DB->Row('SELECT * FROM `tx_pages` WHERE `category_id`=? ORDER BY `build_order` LIMIT 1', array($category['category_id']));

            $DB->Update('INSERT INTO `tx_categories_build` SELECT ' .
                        '?,' .
                        '?,' .
                        'SUM(IF(`status`=? OR `status`=?, 1, 0)), ' .
                        'SUM(IF(`status`=?, `clicks`, 0)), ' .
                        'SUM(IF(`status`=?, `build_counter`, 0)), ' .
                        'SUM(IF(`status`=?, 1, 0)), ' .
                        '? ' .
                        'FROM `tx_galleries` WHERE ' .
                        'MATCH(`categories`) AGAINST(? IN BOOLEAN MODE)',
                        array($category['category_id'],
                              $category['name'],
                              'used',
                              'approved',
                              'used',
                              'used',
                              'used',
                              $page['page_url'],
                              $category['tag']));
        }
        $DB->Free($result);
    }
}

function ClearUsedGalleries(&$pages, $all)
{
    global $DB;

    $DB->Update('DELETE `tx_gallery_used` FROM `tx_gallery_used` LEFT JOIN `tx_pages` USING (`page_id`) WHERE `tx_pages`.`page_id` IS NULL');
    $DB->Update('DELETE `tx_ads_used` FROM `tx_ads_used` LEFT JOIN `tx_pages` USING (`page_id`) WHERE `tx_pages`.`page_id` IS NULL');

    if( $all )
    {
        $DB->Update('DELETE FROM `tx_gallery_used`');
        $DB->Update('DELETE FROM `tx_ads_used`');
    }
    else
    {
        $page_ids = array_map(create_function('$n', "return \$n['page_id'];"), $pages);
        $bindlist = CreateBindList($page_ids);
        $DB->Update('DELETE FROM `tx_gallery_used` WHERE `page_id` IN (' . $bindlist . ')', $page_ids);
        $DB->Update('DELETE FROM `tx_ads_used` WHERE `page_id` IN (' . $bindlist . ')', $page_ids);
    }
}

function BuildAll($callback = null)
{
    global $DB, $C;

    $GLOBALS['_build_type'] = BT_BUILD;
    $pages = array();

    $pages =& $DB->FetchAll('SELECT `page_id` FROM `tx_pages` ORDER BY `build_order`');

    BuildPages($pages, $callback, TRUE);
}

function BuildNewAll($callback = null)
{
    global $DB, $C;

    $GLOBALS['_build_type'] = BT_BUILD_WITH_NEW;
    $pages = array();

    $pages =& $DB->FetchAll('SELECT `page_id` FROM `tx_pages` ORDER BY `build_order`');

    BuildPages($pages, $callback, TRUE);
}

function BuildSelected($ids, $callback = null)
{
    global $DB, $C;

    $GLOBALS['_build_type'] = BT_BUILD;
    $pages = array();

    $pages =& $DB->FetchAll('SELECT `page_id` FROM `tx_pages` WHERE `page_id` IN ('.CreateBindList($ids).') ORDER BY `build_order`', $ids);

    BuildPages($pages, $callback);
}

function BuildNewSelected($ids, $callback = null)
{
    global $DB, $C;

    $GLOBALS['_build_type'] = BT_BUILD_WITH_NEW;
    $pages = array();

    $pages =& $DB->FetchAll('SELECT `page_id` FROM `tx_pages` WHERE `page_id` IN ('.CreateBindList($ids).') ORDER BY `build_order`', $ids);

    BuildPages($pages, $callback);
}

function BuildTagged($tags, $callback = null)
{
    global $DB, $C;

    $GLOBALS['_build_type'] = BT_BUILD;
    $pages = array();

    $pages =& $DB->FetchAll('SELECT `page_id` FROM `tx_pages` WHERE MATCH(`tags`) AGAINST (? IN BOOLEAN MODE) ORDER BY `build_order`', array($tags));

    BuildPages($pages, $callback);
}

function BuildNewTagged($tags, $callback = null)
{
    global $DB, $C;

    $GLOBALS['_build_type'] = BT_BUILD_WITH_NEW;
    $pages = array();

    $pages =& $DB->FetchAll('SELECT `page_id` FROM `tx_pages` WHERE MATCH(`tags`) AGAINST (? IN BOOLEAN MODE) ORDER BY `build_order`', array($tags));

    BuildPages($pages, $callback);
}

function BuildPages(&$pages, $callback = null, $all = FALSE)
{
    global $DB, $C, $L;

    // One at a time please
    $wouldblock = FALSE;
    $fd = fopen("{$GLOBALS['BASE_DIR']}/data/_build_lock", 'w');
    flock($fd, LOCK_EX|LOCK_NB, $wouldblock);
    if( $wouldblock ) return;

    // Clear old build history
    $DB->Update('DELETE FROM `tx_build_history` WHERE `date_start` < DATE_ADD(?, INTERVAL -14 DAY)', array(MYSQL_NOW));

    $DB->Update('INSERT INTO `tx_build_history` VALUES (?,?,?,?,?,?,?)', array(null, MYSQL_NOW, null, null, count($pages), 0, null));
    $GLOBALS['build_history_id'] = $DB->InsertID();

    if( !preg_match('~^\d\d\d$~', $C['page_permissions']) )
    {
        $C['page_permissions'] = $GLOBALS['FILE_PERMISSIONS'];
    }
    else
    {
        $C['page_permissions'] = octdec('0'.$C['page_permissions']);
    }

    // Clear records of currently used galleries on these pages
    ClearUsedGalleries($pages, $all);

    // Process the clicklog
    ProcessClickLog();

    // Cache icons
    if( !isset($GLOBALS['ICON_CACHE']) )
    {
        $GLOBALS['ICON_CACHE'] =& $DB->FetchAll('SELECT * FROM `tx_icons`', null, 'icon_id');
    }

    // Cache categories by tag
    if( !isset($GLOBALS['CATEGORY_CACHE_TAG']) )
    {
        $GLOBALS['CATEGORY_CACHE_TAG'] =& $DB->FetchAll('SELECT * FROM `tx_categories` ORDER BY `name`', null, 'tag');
    }

    // Cache categories by id
    if( !isset($GLOBALS['CATEGORY_CACHE_ID']) )
    {
        $GLOBALS['CATEGORY_CACHE_ID'] =& $DB->FetchAll('SELECT * FROM `tx_categories`', null, 'category_id');
        $GLOBALS['CATEGORY_CACHE_ID'][''] = array('name' => $L['MIXED'], 'category_id' => 0);
    }

    // Cache sponsors by id
    if( !isset($GLOBALS['SPONSOR_CACHE']) )
    {
        $GLOBALS['SPONSOR_CACHE'] =& $DB->FetchAll('SELECT * FROM `tx_sponsors`', null, 'sponsor_id');
    }

    // Clear data on galleries used during the previous build
    $DB->Update('UPDATE `tx_gallery_used` SET `this_build`=0,`new`=0');

    // Remove galleries scheduled for deletion
    $result = $DB->Query('SELECT * FROM `tx_galleries` WHERE `date_deletion` <= ?', array(MYSQL_NOW));
    while( $gallery = $DB->NextRow($result) )
    {
        DeleteGallery($gallery['gallery_id'], $gallery);
    }
    $DB->Free($result);

    // Delete old submitted galleries that are currently holding
    $result = $DB->Query('SELECT * FROM `tx_galleries` WHERE `status`=? AND `type`=? AND `date_displayed` <= SUBDATE(?, INTERVAL ? DAY)',
                         array('holding',
                               'submitted',
                               MYSQL_NOW,
                               $C['submitted_hold']));

    while( $gallery = $DB->NextRow($result) )
    {
        DeleteGallery($gallery['gallery_id'], $gallery);
    }
    $DB->Free($result);

    // Rotate permanent galleries from holding back to approved queue
    $DB->Update('UPDATE `tx_galleries` SET ' .
                "`status`='approved', " .
                "`date_displayed`=NULL, " .
                "`build_counter`=IF(?, 0, `build_counter`), " .
                "`used_counter`=IF(?, 0, `used_counter`), " .
                "`clicks`=IF(?, 0, `clicks`) " .
                "WHERE `status`='holding' AND `type`='permanent' AND `date_displayed` <= SUBDATE(?, INTERVAL ? DAY)",
                array(intval($C['reset_on_rotate']),
                      intval($C['reset_on_rotate']),
                      intval($C['reset_on_rotate']),
                      MYSQL_NOW,
                      $C['permanent_hold']));

    // Count total thumbs and galleries
    $GLOBALS['_totals'] = $DB->Row("SELECT COUNT(*) AS `galleries`,SUM(`thumbnails`) AS `thumbnails` FROM `tx_galleries` WHERE `status` IN ('approved','used')");
    $GLOBALS['_totals']['categories'] = count($GLOBALS['CATEGORY_CACHE_ID']) - 1;

    // Build each page
    foreach( $pages as $page )
    {
        $page_all = $DB->Row('SELECT * FROM `tx_pages` WHERE `page_id`=?', array($page['page_id']));

        if( $page_all['locked'] && !$GLOBALS['override_page_lock'] )
        {
            continue;
        }

        if( $callback )
        {
            call_user_func($callback, $page_all);
        }

        $DB->Update('DELETE FROM `tx_gallery_used_page`');
        $DB->Update('DELETE FROM `tx_ads_used_page`');
        $DB->Update('UPDATE `tx_build_history` SET `current_page_url`=? WHERE `history_id`=?', array($page_all['page_url'], $GLOBALS['build_history_id']));

        BuildPage($page_all);

        $DB->Update('UPDATE `tx_build_history` SET `pages_built`=`pages_built`+1 WHERE `history_id`=?', array($GLOBALS['build_history_id']));

        unset($page_all);
        unset($page);
    }


    // Mark newly selected galleries as used
    // Update counters for galleries used this build
    $DB->Update('UPDATE `tx_galleries` JOIN `tx_gallery_used` USING (`gallery_id`) SET ' .
                '`build_counter`=`build_counter`+1, ' .
                '`used_counter`=`used_counter`+1, ' .
                '`times_selected`=IF(`new`=1, `times_selected`+1, `times_selected`), ' .
                '`status`=?, ' .
                '`date_displayed`=IF(`new`=1, ?, `date_displayed`) ' .
                'WHERE `this_build`=1',
                array('used',
                      MYSQL_NOW));

    // Move no longer used galleries to holding queue (or rotate back to approved if permanent holding period is 0)
    if( $C['permanent_hold'] == 0 )
    {
        $DB->Update('UPDATE `tx_galleries` LEFT JOIN `tx_gallery_used` USING (`gallery_id`) SET ' .
                    "`status`=IF(`type`='permanent', 'approved', 'holding'), " .
                    "`date_displayed`=IF(`type`='permanent', NULL, `date_displayed`), " .
                    "`date_approved`=IF(`type`='permanent', NULL, `date_approved`), " .
                    "`date_scheduled`=IF(`type`='permanent', NULL, `date_scheduled`), " .
                    "`build_counter`=IF(`type`='permanent', IF(?, 0, `build_counter`+1), `build_counter`+1), " .
                    "`used_counter`=IF(`type`='permanent', IF(?, 0, `used_counter`), `used_counter`), " .
                    "`clicks`=IF(`type`='permanent', IF(?, 0, `clicks`), `clicks`) " .
                    "WHERE `status`='used' AND `page_id` IS NULL",
                    array(intval($C['reset_on_rotate']),
                          intval($C['reset_on_rotate']),
                          intval($C['reset_on_rotate']),
                          'used'));
    }
    else
    {
        $DB->Update('UPDATE `tx_galleries` LEFT JOIN `tx_gallery_used` USING (`gallery_id`) SET `status`=?,`build_counter`=`build_counter`+1 WHERE `status`=? AND `page_id` IS NULL', array('holding', 'used'));
    }

    $DB->Update('UPDATE `tx_build_history` SET `date_end`=?,`current_page_url`=NULL WHERE `history_id`=?', array(gmdate(DF_DATETIME, TimeWithTz()), $GLOBALS['build_history_id']));

    flock($fd, LOCK_UN);
    fclose($fd);
    @chmod("{$GLOBALS['BASE_DIR']}/data/_build_lock", 0666);
}

function BuildPage($page)
{
    global $DB, $C, $L;

    $GLOBALS['_counters']['thumbnails'] = 0;
    $GLOBALS['_counters']['galleries'] = 0;


    $t = new Template();
    $t->assign_by_ref('this_page', $page);
    $t->assign_by_ref('config', $C);
    $t->assign_by_ref('page_category', $GLOBALS['CATEGORY_CACHE_ID'][$page['category_id']]);
    $t->assign_by_ref('search_categories', $GLOBALS['CATEGORY_CACHE_TAG']);
    $t->assign('category', array());
    $t->assign('total_galleries', $GLOBALS['_totals']['galleries']);
    $t->assign('total_thumbnails', $GLOBALS['_totals']['thumbnails']);
    $t->assign('total_categories', $GLOBALS['_totals']['categories']);
    $t->assign('page_galleries', '{$galleries}');
    $t->assign('page_thumbnails', '{$thumbnails}');

    $mode = is_file($page['filename']) ? 'r+' : 'w';

    $fd = fopen($page['filename'], $mode);
    flock($fd, LOCK_EX);

    // Parse the template
    $generated = $t->parse_compiled($page['compiled']);
    $generated = str_replace('{$galleries}', number_format($GLOBALS['_counters']['galleries'], 0, $C['dec_point'], $C['thousands_sep']), $generated);
    $generated = str_replace('{$thumbnails}', number_format($GLOBALS['_counters']['thumbnails'], 0, $C['dec_point'], $C['thousands_sep']), $generated);
    fwrite($fd, trim($generated));
    fflush($fd);
    ftruncate($fd, ftell($fd));
    flock($fd, LOCK_UN);
    fclose($fd);
    @chmod($page['filename'], $C['page_permissions']);

    $t->cleanup();
    unset($fd);
    unset($generated);
    unset($t);
    unset($page);
}

function ArrayIntermix(&$a, &$b, $locations)
{
    $result = array();
    $a_count = count($a);
    $b_count = count($b);
    $locations = strtolower($locations);

    if( $b_count == 0 )
        return $a;

    if( $a_count == 0 )
        return $b;

    if( $locations == 'end' )
    {
        $result = array_merge($a, $b);
    }
    else if( $locations == 'random' )
    {
        for( $i = 0; $i < $a_count + $b_count; $i++ )
        {
            if( (rand(0,1) || !current($b)) && current($a) )
            {
                $result[$i] =& current($a);
                next($a);
            }
            else
            {
                $result[$i] =& current($b);
                next($b);
            }
        }
    }
    else if( preg_match('~\+(\d+)~', $locations, $matches) )
    {
        $position = $matches[1];

        $j = 0;
        for( $i = 0; $i < $a_count; $i++ )
        {
            $result[] =& $a[$i];

            if( $j < $b_count && ($i + 1) % $position == 0 )
            {
                $result[] =& $b[$j];
                $j++;
            }
        }

        if( $j < $b_count )
        {
            $result = array_merge($result, array_slice($b, $j));
        }
    }
    else
    {
        $locations = explode(',', $locations);

        $j = 0;
        for( $i = 0; $i < $a_count; $i++ )
        {
            $result[] =& $a[$i];

            if( $j < $b_count && in_array($i+1, $locations) )
            {
                $result[] =& $b[$j];
                $j++;
            }
        }

        if( $j < $b_count )
        {
            $result = array_merge($result, array_slice($b, $j));
        }
    }

    reset($a);
    reset($b);

    return $result;
}

function &LoadGalleries($query, $page_id, $category_id, $fetch_preview = FALSE)
{
    global $DB, $L, $C;

    $galleries = array();
    //$fetch_preview = strstr($query, '`has_preview`') !== FALSE;
    $categories = null;

    if( preg_match('~MATCH\(`categories`\) AGAINST\s*\(\'(.*?)\'~i', $query, $matches) )
    {
        $categories = array();

        foreach( explode(' ', $matches[1]) as $tag )
        {
            if( strpos($tag, '-') !== 0 && $tag != MIXED_CATEGORY )
            {
                $categories[] = $tag;
            }
        }

        if( count($categories) )
        {
            $categories = trim(join(' ', $categories));
        }
        else
        {
            $categories = null;
        }
    }

    $result = $DB->Query($query);
    while( $gallery = $DB->NextRow($result) )
    {
        $fields = $DB->Row('SELECT * FROM `tx_gallery_fields` WHERE `gallery_id`=?', array($gallery['gallery_id']));
        if( is_array($fields) )
        {
            $gallery = array_merge($gallery, $fields);
        }

        if( !empty($gallery['sponsor_id']) )
        {
            $gallery['sponsor'] = $GLOBALS['SPONSOR_CACHE'][$gallery['sponsor_id']]['name'];
            $gallery['sponsor_url'] = $GLOBALS['SPONSOR_CACHE'][$gallery['sponsor_id']]['url'];
        }

        if( $fetch_preview )
        {
            $preview = $DB->Row('SELECT * FROM `tx_gallery_previews` WHERE `gallery_id`=? ORDER BY RAND(?) LIMIT 1', array($gallery['gallery_id'], rand()));
            if( is_array($preview) )
            {
                $gallery = array_merge($gallery, $preview);
            }
        }

        list($gallery['preview_width'], $gallery['preview_height']) = explode('x', $gallery['dimensions']);

        if( $gallery['status'] == 'approved' )
        {
            $gallery['date_displayed'] = MYSQL_NOW;
        }

        $gallery['date'] = strtotime($gallery['date_displayed']);
        $gallery['format'] = $L[strtoupper($gallery['format'])];
        $gallery['now'] = TIME_NOW;
        $gallery['report_url'] = "{$C['install_url']}/report.php?id=" . urlencode($gallery['gallery_id']);
        $gallery['productivity'] = $gallery['used_counter'] > 0 ? sprintf('.2f', ($gallery['clicks']/$gallery['used_counter'])) : 0;

        $GLOBALS['_counters']['thumbnails'] += $gallery['thumbnails'];
        $GLOBALS['_counters']['galleries']++;

        if( $category_id )
        {
            $gallery['category'] = $GLOBALS['CATEGORY_CACHE_ID'][$category_id]['name'];
        }
        else
        {
            $all_categories = array();
            foreach( explode(' ', $gallery['categories']) as $category_tag )
            {
                $category_tag = trim($category_tag);
                $in_allowed_category = ($categories != null && !empty($category_tag) && stristr($categories, $category_tag));
                $any_category_but_mixed = ($categories == null && !empty($category_tag) && $category_tag != MIXED_CATEGORY);

                if( $in_allowed_category || $any_category_but_mixed )
                {
                    if( !isset($gallery['category']) )
                    {
                        $gallery['category'] = $GLOBALS['CATEGORY_CACHE_TAG'][$category_tag]['name'];
                    }
                    $all_categories[] = $GLOBALS['CATEGORY_CACHE_TAG'][$category_tag];
                }
            }
            $gallery['categories'] = $all_categories;
        }

        $gallery['icons'] = array();
        $icons =& $DB->FetchAll('SELECT * FROM `tx_gallery_icons` WHERE `gallery_id`=?', array($gallery['gallery_id']));
        foreach( $icons as $icon )
        {
            $gallery['icons'][] = $GLOBALS['ICON_CACHE'][$icon['icon_id']]['icon_html'];
        }

        $galleries[] = $gallery;

        $DB->Update('REPLACE INTO `tx_gallery_used_page` VALUES (?)', array($gallery['gallery_id']));
        $DB->Update('REPLACE INTO `tx_gallery_used` VALUES (?,?,?,?)',
                    array($gallery['gallery_id'],
                          $page_id,
                          1,
                          $gallery['status'] == 'approved' ? 1 : 0));
    }
    $DB->Free($result);

    return $galleries;
}

function MarkGalleriesUsed(&$galleries, $page_id)
{
    global $DB;

    if( !is_array($galleries) )
        return;

    foreach( $galleries as $gallery )
    {
        if( $gallery['gallery_id'] )
        {
            $DB->Update('REPLACE INTO `tx_gallery_used_page` VALUES (?)', array($gallery['gallery_id']));
            $DB->Update('REPLACE INTO `tx_gallery_used` VALUES (?,?,?,?)',
                        array($gallery['gallery_id'],
                              $page_id,
                              1,
                              $gallery['status'] == 'approved' ? 1 : 0));
        }
    }
}

function &DeleteGallery($gallery_id, $gallery = null)
{
    global $DB, $C;

    if( $gallery == null )
    {
        $gallery = $DB->Row('SELECT * FROM `tx_galleries` WHERE `gallery_id`=?', array($gallery_id));
    }

    if( $gallery )
    {
        // Remove gallery data
        $DB->Update('DELETE FROM `tx_galleries` WHERE `gallery_id`=?', array($gallery_id));
        $DB->Update('DELETE FROM `tx_gallery_fields` WHERE `gallery_id`=?', array($gallery_id));
        $DB->Update('DELETE FROM `tx_gallery_used` WHERE `gallery_id`=?', array($gallery_id));
        $DB->Update('DELETE FROM `tx_gallery_icons` WHERE `gallery_id`=?', array($gallery_id));
        $DB->Update('DELETE FROM `tx_gallery_confirms` WHERE `gallery_id`=?', array($gallery_id));

        // Remove any cheat reports
        $DB->Update('DELETE FROM `tx_reports` WHERE `gallery_id`=?', array($gallery_id));

        // Remove preview thumbs
        $result = $DB->Query('SELECT * FROM `tx_gallery_previews` WHERE `gallery_id`=?', array($gallery_id));
        while( $thumb = $DB->NextRow($result) )
        {
            if( is_file("{$C['preview_dir']}/{$thumb['preview_id']}.jpg") )
            {
                @unlink("{$C['preview_dir']}/{$thumb['preview_id']}.jpg");
            }
        }
        $DB->Free($result);
        $DB->Update('DELETE FROM `tx_gallery_previews` WHERE `gallery_id`=?', array($gallery_id));

        // Update partner account
        if( !IsEmptyString($gallery['partner']) )
        {
            $DB->Update('UPDATE `tx_partners` SET `removed`=`removed`+1 WHERE `username`=?', array($gallery['partner']));
        }
    }

    return $gallery;
}

function &LoadAnnotation($id, $category_name)
{
    global $DB;

    if( empty($id) )
        return null;

    $annotation = $DB->Row('SELECT * FROM `tx_annotations` WHERE `annotation_id`=?', array($id));

    if( $annotation && $annotation['use_category'] )
        $annotation['string'] = $category_name;

    return $annotation;
}

function AddPreview($gallery_id, $dimensions, $filename)
{
    global $C, $DB;

    if( !$filename  )
        return null;

    // Replace existing size
    $thumb = $DB->Row('SELECT * FROM `tx_gallery_previews` WHERE `gallery_id`=? AND `dimensions`=?', array($gallery_id, $dimensions));
    if( $thumb )
    {
        $preview_id = $thumb['preview_id'];
        @rename($filename, "{$C['preview_dir']}/$preview_id.jpg");
        @chmod("{$C['preview_dir']}/$preview_id.jpg", $GLOBALS['FILE_PERMISSIONS']);

        $DB->Update('UPDATE `tx_gallery_previews` SET `preview_url`=? WHERE `preview_id`=?', array("{$C['preview_url']}/{$thumb['preview_id']}.jpg", $preview_id));
    }

    // Add new
    else
    {
        $DB->Update('INSERT INTO `tx_gallery_previews` VALUES(?,?,?,?)',
                array(null,
                      $gallery_id,
                      null,
                      $dimensions));

        $preview_id = $DB->InsertID();
        $DB->Update('UPDATE `tx_gallery_previews` SET `preview_url`=? WHERE `preview_id`=?', array("{$C['preview_url']}/$preview_id.jpg", $preview_id));
        @rename($filename, "{$C['preview_dir']}/$preview_id.jpg");
        @chmod("{$C['preview_dir']}/$preview_id.jpg", $GLOBALS['FILE_PERMISSIONS']);
    }

    $DB->Update('UPDATE `tx_galleries` LEFT JOIN `tx_gallery_previews` ON ' .
                '`tx_galleries`.`gallery_id`=`tx_gallery_previews`.`gallery_id` SET ' .
                '`has_preview`=IF(`preview_id` IS NULL, 0, 1) WHERE `tx_galleries`.`gallery_id`=?', array($gallery_id));

    return array('url' => "{$C['preview_url']}/$preview_id.jpg", 'id' => $preview_id);
}

function &CategoriesFromTags($tags)
{
    global $DB;

    if( !isset($GLOBALS['_CAT_CACHE']) )
    {
        $GLOBALS['_CAT_CACHE'] =& $DB->FetchAll('SELECT * FROM `tx_categories`', null, 'tag');
    }
    $categories = array();

    foreach( explode(' ', $tags) as $tag )
    {
        $tag = trim($tag);
        if( isset($GLOBALS['_CAT_CACHE'][$tag]) )
        {
            $categories[] = $GLOBALS['_CAT_CACHE'][$tag];
        }
    }

    return $categories;
}

function CategoryTagsFromIds($ids)
{
    global $DB;

    $tags = array(MIXED_CATEGORY);

    foreach( array_unique($ids) as $id )
    {
        $category = $DB->Row('SELECT * FROM `tx_categories` WHERE `category_id`=?', array($id));

        if( $category)
        {
            $tags[] = $category['tag'];
        }
    }

    return join(' ', array_unique($tags));
}

function CategoryTagsFromList($list, &$skipped)
{
    global $DB;

    $tags = array(MIXED_CATEGORY);
    $list = FormatCommaSeparated($list);

    foreach( explode(',', $list) as $name )
    {
        $name = trim($name);

        if( empty($name) )
        {
            continue;
        }

        $category = $DB->Row('SELECT * FROM `tx_categories` WHERE `name`=?', array($name));

        if( $category )
        {
            $tags[] = $category['tag'];
        }
        else
        {
            $skipped[] = $name;
        }
    }

    return join(' ', array_unique($tags));
}

function CreateCategoryTag($name, $is_name = TRUE)
{
    global $DB;

    if( !$is_name )
    {
        $name = $DB->Count('SELECT `name` FROM `tx_categories` WHERE `category_id`=?', array($name));
    }

    $tag = md5($name);

    if( $DB->Count('SELECT COUNT(*) FROM `tx_categories` WHERE `tag`=?', array($tag)) )
    {
        $tag = md5(uniqid(rand(), true));
    }

    return $tag;
}

function strtogmtime($string)
{
    global $C;

    $timezone = $C['timezone'];

    if( date('I', $timestamp) )
    {
        $timezone++;
    }

    $time = strtotime($string);

    if( $time != -1 && $time !== FALSE )
    {
        $zone = intval(date('O')) / 100;
        $time += $zone * 60 * 60;
        return $time - (3600 * $timezone);
    }
    else
    {
        return -1;
    }
}

function CheckReciprocal($html)
{
    global $DB, $C, $RECIP_CACHE;

    $has_recip = FALSE;

    // Prepare HTML code for scanning
    $html = preg_replace(array('/[\r\n]/', '/\s+/'), ' ', $html);

    // Load reciprocal links, if not previously cached
    if( !is_array($RECIP_CACHE) )
    {
        $RECIP_CACHE = array();
        $result = $DB->Query('SELECT * FROM `tx_reciprocals`');
        while( $recip = $DB->NextRow($result) )
        {
            $RECIP_CACHE[] = preg_replace(array('/[\r\n]/', '/\s+/'), ' ', $recip);
        }
        $DB->Free($result);
    }

    foreach( $RECIP_CACHE as $recip )
    {
        if( !$recip['regex'] )
        {
            $recip['code'] = preg_quote($recip['code'], '~');
        }
        else
        {
            $recip['code'] = preg_replace("%(?<!\\\)~%", '\\~', $recip['code']);
        }

        if( preg_match("~{$recip['code']}~i", $html) )
        {
            $has_recip = TRUE;
            break;
        }
    }

    return $has_recip;
}

function Check2257($html)
{
    global $DB, $C, $RECORD_CACHE;

    $has_2257 = FALSE;

    // Prepare HTML code for scanning
    $html = preg_replace('/[\r\n]/', ' ', $html);
    $html = preg_replace('/\s+/', ' ', $html);

    // Load reciprocal links, if not previously cached
    if( !is_array($RECORD_CACHE) )
    {
        $RECORD_CACHE = array();
        $result = $DB->Query('SELECT * FROM `tx_2257`');
        while( $record = $DB->NextRow($result) )
        {
            $RECORD_CACHE[] = $record;
        }
        $DB->Free($result);
    }

    foreach( $RECORD_CACHE as $record )
    {
        if( !$record['regex'] )
        {
            $record['code'] = preg_quote($record['code'], '~');
        }
        else
        {
            $record['code'] = preg_replace("%(?<!\\\)~%", '\\~', $record['code']);
        }

        if( preg_match("~{$record['code']}~i", $html) )
        {
            $has_2257 = TRUE;
            break;
        }
    }

    $html = '';
    unset($html);

    return $has_2257;
}

function RandomPassword()
{
    $chars = array_merge(range('a', 'z'), range('A', 'Z'));
    $numbers = range(0, 9);
    $number_locations = array(rand(0, 7), rand(0, 7));
    $password = '';

    for( $i = 0; $i < 8; $i++ )
    {
        if( in_array($i, $number_locations) )
        {
            $password .= $numbers[array_rand($numbers)];
        }
        else
        {
            $password .= $chars[array_rand($chars)];
        }
    }

    return $password;
}

function LevelUpUrl($url)
{
    $slash = strrpos($url, '/');

    if( $slash <= 7 )
    {
        return $url;
    }

    return substr($url, 0, $slash);
}

function SendMail($to, $template, &$t, $is_file = TRUE)
{
    global $C;

    if( !class_exists('mailer') )
    {
        require_once("{$GLOBALS['BASE_DIR']}/includes/mailer.class.php");
    }

    $m = new Mailer();
    $m->mailer = $C['email_type'];
    $m->from = $C['from_email'];
    $m->from_name = $C['from_email_name'];
    $m->to = $to;

    switch($C['email_type'])
    {
        case MT_PHP:
            break;

        case MT_SENDMAIL:
            $m->sendmail = $C['mailer'];
            break;

        case MT_SMTP:
            $m->host = $C['mailer'];
            break;
    }

    if( $is_file )
    {
        $template = file_get_contents("{$GLOBALS['BASE_DIR']}/templates/$template");
    }

    $message_parts = array();
    $parsed_template = $t->parse($template);
    IniParse($parsed_template, FALSE, $message_parts);

    $m->subject = $message_parts['subject'];
    $m->text_body = $message_parts['plain'];
    $m->html_body = $message_parts['html'];

    return $m->Send();
}

function IsEmptyString(&$string)
{
    if( preg_match("/^\s*$/s", $string) )
    {
        return TRUE;
    }

    return FALSE;
}

function UserDefinedUpdate($table, $defs_table, $key_name, $key_value, &$data)
{
    global $DB;

    $bind_list = array();
    $binds = array($table);
    $fields =& $DB->FetchAll('SELECT * FROM #', array($defs_table));

    foreach( $fields as $field )
    {
        // Handle unchecked checkboxes
        if( $field['type'] == FT_CHECKBOX && !isset($data[$field['name']]) )
        {
            $data[$field['name']] = null;
        }

        // See if new data was supplied
        if( array_key_exists($field['name'], $data) )
        {
            $binds[] = $field['name'];
            $binds[] = $data[$field['name']];
            $bind_list[] = '#=?';
        }
    }

    if( count($binds) > 1 )
    {
        $binds[] = $key_name;
        $binds[] = $key_value;
        $DB->Update('UPDATE # SET '.join(',', $bind_list).' WHERE #=?', $binds);
    }
}

function &GetUserPartnerFields($partner_data = null)
{
    global $DB;

    if( $partner_data == null )
    {
        $partner_data = $_REQUEST;
    }

    $fields = array();
    $result = $DB->Query('SELECT * FROM `tx_partner_field_defs`');
    while( $field = $DB->NextRow($result) )
    {
        if( isset($partner_data[$field['name']]) )
        {
            $field['value'] = $partner_data[$field['name']];
        }
        $fields[] = $field;
    }
    $DB->Free($result);

    return $fields;
}

function &GetUserGalleryFields($gallery_data = null)
{
    global $DB;

    if( $gallery_data == null )
    {
        $gallery_data = $_REQUEST;
    }

    $fields = array();
    $result = $DB->Query('SELECT * FROM `tx_gallery_field_defs`');
    while( $field = $DB->NextRow($result) )
    {
        if( isset($gallery_data[$field['name']]) )
        {
            $field['value'] = $gallery_data[$field['name']];
        }
        $fields[] = $field;
    }
    $DB->Free($result);

    return $fields;
}

function TimeWithTz($timestamp = null)
{
    global $C;

    $timezone = $C['timezone'];

    if( $timestamp == null )
    {
        $timestamp = time();
    }

    if( date('I', $timestamp) )
    {
        $timezone++;
    }

    return $timestamp + 3600 * $timezone;
}

function UnsetArray(&$array)
{
    $array = array();
}

function ArrayHSC(&$array)
{
    if( !is_array($array) )
        return;

    foreach($array as $key => $value)
    {
        if( is_array($array[$key]) )
        {
            ArrayHSC($array[$key]);
        }
        else
        {
            $array[$key] = htmlspecialchars($array[$key], ENT_QUOTES);
        }
    }
}

function IniWrite($filename, &$hash, $keys = null)
{
    if( $keys == null )
        $keys = array_keys($hash);

    $data = '';

    foreach( $keys as $key )
    {
        UnixFormat($hash[$key]);

        $data .= "=>[$key]\n" .
                 trim($hash[$key]) . "\n";
    }

    if( $filename != null )
        FileWrite($filename, $data);
    else
        return $data;
}

function IniParse($string, $isfile = TRUE, &$hash)
{
    if( $hash == null )
        $hash = array();

    if( $isfile )
        $string = file_get_contents($string);

    UnixFormat($string);

    foreach(explode("\n", $string) as $line)
    {
        if( preg_match("/^=>\[(.*?)\]$/", $line, $submatch) )
        {
            if( isset($key) )
            {
                $hash[$key] = trim($hash[$key]);
            }

            $key = $submatch[1];
            $hash[$key] = '';
        }
        else
        {


            $hash[$key] .= "$line\n";
        }
    }

    if( isset($key) )
    {
        $hash[$key] = rtrim($hash[$key]);
    }
}

function StringChop($string, $length, $center = false, $append = null)
{
    // Set the default append string
    if ($append === null) {
        $append = ($center === true) ? ' ... ' : '...';
    }

    // Get some measurements
    $len_string = strlen($string);
    $len_append = strlen($append);

    // If the string is longer than the maximum length, we need to chop it
    if ($len_string > $length) {
        // Check if we want to chop it in half
        if ($center === true) {
            // Get the lengths of each segment
            $len_start = $length / 2;
            $len_end = $len_string - $len_start;

            // Get each segment
            $seg_start = substr($string, 0, $len_start);
            $seg_end = substr($string, $len_end);

            // Stick them together
            $string = trim($seg_start) . $append . trim($seg_end);
        } else {
            // Otherwise, just chop the end off
            $string = trim(substr($string, 0, $length - $len_append)) . $append;
        }
    }

    return $string;
}

function FormatCommaSeparated($string)
{
    if( strlen($string) < 1 || strstr($string, ',') === FALSE )
        return $string;

    $items = array();
    $string = trim($string);

    foreach( explode(',', $string) as $item )
    {
        $items[] = trim($item);
    }

    return join(',', $items);
}

function FormField($options, $value)
{
    $html = '';
    $select_options = explode(',', $options['options']);

    $options['tag_attributes'] = str_replace(array('&quot;', '&#039;'), array('"', "'"), $options['tag_attributes']);

    switch($options['type'])
    {
    case FT_CHECKBOX:
        $tag_value = null;

        if( preg_match('/value\s*=\s*["\']?([^\'"]+)\s?/i', $options['tag_attributes'], $matches) )
        {
            $tag_value = $matches[1];
        }
        else
        {
            $tag_value = 1;
            $options['tag_attributes'] .= ' value="1"';
        }

        $html = "<input " .
                "type=\"checkbox\" " .
                "name=\"{$options['name']}\" " .
                "id=\"{$options['name']}\" " .
                ($value == $tag_value ? "checked=\"checked\" " : '') .
                "{$options['tag_attributes']} />\n";
        break;

    case FT_SELECT:
        $html = "<select " .
                "name=\"{$options['name']}\" " .
                "id=\"{$options['name']}\" " .
                "{$options['tag_attributes']}>\n" .
                OptionTags($select_options, $value, TRUE) .
                "</select>\n";
        break;

    case FT_TEXT:
        $html = "<input " .
                "type=\"text\" " .
                "name=\"{$options['name']}\" " .
                "id=\"{$options['name']}\" " .
                "value=\"$value\" " .
                "{$options['tag_attributes']} />\n";
        break;

    case FT_TEXTAREA:
        $html = "<textarea " .
                "name=\"{$options['name']}\" " .
                "id=\"{$options['name']}\" " .
                "{$options['tag_attributes']}>" .
                $value .
                "</textarea>\n";
        break;
    }

    return $html;
}

function OptionTags($options, $selected = null, $use_values = FALSE, $max_length = 9999)
{
    $html = '';

    if( is_array($options) )
    {
        foreach($options as $key => $value)
        {
            if( $use_values )
                $key = $value;

            $html .= "<option value=\"" . htmlspecialchars($key) . "\"" .
                     ($key == $selected ? ' selected="selected"' : '') .
                     ">" . htmlspecialchars(StringChop($value, $max_length)) . "</option>\n";
        }
    }

    return $html;
}

function OptionTagsAdv($options, $selected, $value, $name, $max_length = 9999)
{
    $html = '';

    if( is_array($options) )
    {
        foreach($options as $option)
        {
            $html .= "<option value=\"" . htmlspecialchars($option[$value]) . "\"" .
                     ((is_array($selected) && in_array($option[$value], $selected) || $option[$value] == $selected) ? ' selected="selected"' : '') .
                     ">" . htmlspecialchars(StringChop($option[$name], $max_length)) . "</option>\n";
        }
    }

    return $html;
}

function UnixFormat(&$string)
{
    $string = str_replace(array("\r\n", "\r"), "\n", $string);
}

function WindowsFormat(&$string)
{
    $string = str_replace(array("\r\n", "\r"), "\n", $string);
    $string = str_replace("\n", "\r\n", $string);
}

function ToBool($value)
{
    if( is_numeric($value) )
    {
        if( $value == 0 )
        {
            return FALSE;
        }
        else
        {
            return TRUE;
        }
    }
    else if( preg_match('~^true$~i', $value) )
    {
        return TRUE;
    }
    else if( preg_match('~^false$~i', $value) )
    {
        return FALSE;
    }

    return FALSE;
}

function IsBool($value)
{
    return is_bool($value) || preg_match('/^(true|false)$/i', $value);
}

function SafeAddSlashes(&$string)
{
    $string = preg_replace("/(?<!\\\)'/", "\'", $string);
}

function ArrayCombine($keys, $values)
{
    $combined = array();

    for( $i = 0; $i < count($keys); $i++ )
    {
        $combined[$keys[$i]] = $values[$i];
    }

    return $combined;
}

function ArrayAddSlashes(&$array)
{
    foreach($array as $key => $value)
    {
        if( is_array($array[$key]) )
        {
            ArrayAddSlashes($array[$key]);
        }
        else
        {
            $array[$key] = preg_replace("/(?<!\\\)'/", "\'", $value);
        }
    }
}

function ArrayStripSlashes(&$array)
{
    foreach($array as $key => $value)
    {
        if( is_array($array[$key]) )
        {
            ArrayStripSlashes($array[$key]);
        }
        else
        {
            $array[$key] = stripslashes($value);
        }
    }
}

function SafeFilename($filename, $must_exist = TRUE)
{
    global $L;

    $unsafe_exts = array('php', 'php3', 'php4', 'php5', 'cgi', 'pl', 'exe', 'js');
    $path_info = pathinfo($filename);

    if( $must_exist && !file_exists($filename) )
        trigger_error("{$L['NOT_A_FILE']}: $filename", E_USER_ERROR);

    if( is_dir($filename) )
        trigger_error("{$L['NOT_A_FILE']}: $filename", E_USER_ERROR);

    if( strstr($filename, '..') != FALSE || strstr($filename, '|') != FALSE || strstr($filename, ';') != FALSE)
        trigger_error("{$L['UNSAFE_FILENAME']}: $file", E_USER_ERROR);

    if( in_array($path_info['extension'], $unsafe_exts) )
        trigger_error("{$L['UNSAFE_FILE_EXTENSION']}: $filename", E_USER_ERROR);

    return $filename;
}

function FileReadLine($file)
{
    $line = '';
    $fh = fopen($file, 'r');

    if( $fh )
    {
        $line = trim(fgets($fh));
        fclose($fh);
    }

    return $line;
}

function FileWrite($file, $data)
{
    $file_mode = file_exists($file) ? 'r+' : 'w';

    $fh = fopen($file, $file_mode);
    flock($fh, LOCK_EX);
    fseek($fh, 0);
    fwrite($fh, $data);
    ftruncate($fh, ftell($fh));
    flock($fh, LOCK_UN);
    fclose($fh);

    @chmod($file, $GLOBALS['FILE_PERMISSIONS']);
}

function FileWriteNew($file, $data)
{
    if( !file_exists($file) )
    {
        FileWrite($file, $data);
    }
}

function FileAppend($file, $data)
{
    $fh = fopen($file, 'a');
    flock($fh, LOCK_EX);
    fwrite($fh, $data);
    flock($fh, LOCK_UN);
    fclose($fh);

    @chmod($file, $GLOBALS['FILE_PERMISSIONS']);
}

function FileRemove($file)
{
    unlink($file);
}

function FileCreate($file)
{
    if( !file_exists($file) )
    {
        FileWrite($file, '');
    }
}

function &DirRead($dir, $pattern)
{
    $contents = array();

    DirTaint($dir);

    $dh = opendir($dir);

    while( false !== ($file = readdir($dh)) )
    {
        $contents[] = $file;
    }

    closedir($dh);

    $contents = preg_grep("/$pattern/i", $contents);

    return $contents;
}

function DirTaint($dir)
{
    if( is_file($dir) )
        trigger_error("Not A Directory: $dir", E_USER_ERROR);

    if( stristr($dir, '..') != FALSE )
        trigger_error("Security Violation: $dir", E_USER_ERROR);
}

function SetupRequest()
{
    if( get_magic_quotes_gpc() == 1 )
    {
        ArrayStripSlashes($_POST);
        ArrayStripSlashes($_GET);
        ArrayStripSlashes($_COOKIE);
    }

    $_REQUEST = array_merge($_POST, $_GET);
}

function Shutdown()
{
    global $DB;

    if( @get_class($DB) == 'db' )
    {
        $DB->Disconnect();
    }
}

function Error($code, $string, $file, $line)
{
    global $C, $DB;

    $reporting = error_reporting();

    if( $reporting == 0 || !($code & $reporting) )
    {
        return;
    }

    $sapi = php_sapi_name();

    if( $sapi != 'cli' )
    {
        require_once("{$GLOBALS['BASE_DIR']}/includes/template.class.php");
        $t = new Template();
    }

    $file = basename($file);

    // Generate stack trace
    $backtrace = debug_backtrace();
    for( $i = 1; $i < count($backtrace); $i++ )
    {
        $tracefile = $backtrace[$i];

        if( !$tracefile['line'] )
            continue;

        $trace .= "{$tracefile['function']} in " . basename($tracefile['file']) . " on line {$tracefile['line']}<br />";
    }

    if( $sapi != 'cli' )
    {
        $t->assign('trace', $trace);
        $t->assign('error', $string);
        $t->assign('file', $file);
        $t->assign('line', $line);
        $t->assign_by_ref('config', $C);

        if( defined('TGPX') )
        {
            $t->assign('levelup', '../');
        }

        $t->display('error-fatal.tpl');
    }
    else
    {
        echo "Error on line $line of file $file\n" .
             "$string\n" .
             "Stack Trace:\n$trace\n";
    }

    $error = "Error on line $line of file $file\n" .
             strip_tags(str_replace('<br />', "\n", $string)) . "\n\n" .
             "Stack Trace:\n" . str_replace('<br />', "\n", $trace) . "\n";

    if( @get_class($DB) == 'DB' && isset($GLOBALS['build_history_id']) )
    {
        $DB->Update('UPDATE `tx_build_history` SET `error_message`=? WHERE `history_id`=?', array($error, $GLOBALS['build_history_id']));
    }

    if( $GLOBALS['ERROR_LOG'] )
    {
        FileAppend("{$GLOBALS['BASE_DIR']}/data/error_log", "[".date('r')."] $error\n");
    }

    exit;
}

function VerifyCaptcha(&$v, $cookie = 'tgpxcaptcha')
{
    global $DB, $L, $C;

    if( !isset($_COOKIE[$cookie]) )
    {
        $v->SetError($L['COOKIES_REQUIRED']);
    }
    else
    {
        $captcha = $DB->Row('SELECT * FROM `tx_captcha` WHERE `session`=?', array($_COOKIE[$cookie]));

        if( strtoupper($captcha['code']) != strtoupper($_REQUEST['captcha']) )
        {
            $v->SetError($L['INVALID_CODE']);
        }
        else
        {
            $DB->Update('DELETE FROM `tx_captcha` WHERE `session`=?', array($_COOKIE[$cookie]));
            setcookie($cookie, '', time() - 3600, '/', $C['cookie_domain']);
        }
    }
}

function CheckDsbl($ip_address)
{
    list($a, $b, $c, $d) = explode('.', $ip_address);

    $hostname = "$d.$c.$b.$a.list.dsbl.org";
    $ip_address = gethostbyname("$d.$c.$b.$a.list.dsbl.org");

    if( $hostname != $ip_address )
    {
        return TRUE;
    }

    return FALSE;
}

function NullIfEmpty(&$string)
{
    if( IsEmptyString($string) )
    {
        $string = null;
    }
}

function DatetimeToTime(&$datetime)
{
    if( !empty($datetime) )
    {
        $datetime = strtotime($datetime);
    }
}

function ValidPartnerLogin()
{
    global $DB, $C, $L;

    $error = $L['INVALID_LOGIN'];

    if( isset($_POST['login_username']) && isset($_POST['login_password']) )
    {
        $partner = $DB->Row('SELECT * FROM `tx_partners` WHERE `username`=? AND `password`=?', array($_POST['login_username'], sha1($_POST['login_password'])));

        if( $partner )
        {
            // Only allow active partners to login
            if( $partner['status'] == 'active' )
            {
                // Setup the session
                $session = sha1(uniqid(rand(), true) . $_POST['login_password']);
                setcookie('tgpxpartner', 'username=' . urlencode($_POST['login_username']) . '&session=' . $session, time() + 86400, '/', $C['cookie_domain']);
                $DB->Update('UPDATE `tx_partners` SET `session`=?,`session_start`=? WHERE `username`=?', array($session, time(), $partner['username']));

                // Get user defined fields and merge with default partner data
                $user_fields = $DB->Row('SELECT * FROM `tx_partner_fields` WHERE `username`=?', array($partner['username']));
                $partner = array_merge($partner, $user_fields);

                return $partner;
            }
            else
            {
                $error = $partner['status'] == 'suspended' ? $L['ACCOUNT_SUSPENDED'] : $L['ACCOUNT_PENDING'];
            }
        }
    }
    else if( isset($_COOKIE['tgpxpartner']) )
    {
        parse_str($_COOKIE['tgpxpartner'], $cookie);

        $partner = $DB->Row('SELECT * FROM `tx_partners` WHERE `username`=? AND `session`=?', array($cookie['username'], $cookie['session']));

        if( $partner )
        {
            if( $partner['status'] == 'active' )
            {
                // Get user defined fields and merge with default partner data
                $user_fields = $DB->Row('SELECT * FROM `tx_partner_fields` WHERE `username`=?', array($partner['username']));
                $partner = array_merge($partner, $user_fields);

                return $partner;
            }
            else
            {
                $error = $partner['status'] == 'suspended' ? $L['ACCOUNT_SUSPENDED'] : $L['ACCOUNT_PENDING'];
            }
        }
        else
        {
            $error = $L['EXPIRED_LOGIN'];
        }
    }

    txShPartnerLogin(array($error));
    return FALSE;
}

##REPLACE

function FormatSpaceSeparated($words)
{
    $words = str_replace(array('.', ',', '?', ';', ':', '(', ')', '{', '}', '*', '&', '%', '$', '#', '@', '!', '-'), ' ', $words);
    $words = preg_replace('/\s+/', ' ', $words);
    $words = trim($words);

    return join(' ', array_unique(explode(' ', $words)));
}

function GetNameServers($url)
{
    global $C;

    $nameservers = array();

    if( $C['dig'] )
    {
        $parsed_url = @parse_url($url);

        if( $parsed_url !== FALSE )
        {
            $domain = str_replace('www.', '', $parsed_url['host']);
            $found = FALSE;

            while( substr_count($domain, '.') >= 1 )
            {
                $output = shell_exec("{$C['dig']} $domain NS +nocmd +nostats +noquestion +nocomment");

                foreach( explode("\n", $output) as $line )
                {
                    if( preg_match('~NS\s+([^\s]+)$~i', $line, $matches) )
                    {
                        $nameservers[] = preg_replace('~\.$~', '', $matches[1]);
                    }
                }

                if( $found )
                {
                    break;
                }

                $domain = substr($domain, strpos($domain, '.') + 1);
            }
        }
    }

    return array_unique($nameservers);
}

function CheckBlacklistGallery(&$gallery, $full_check = FALSE)
{
    $checks = array('email' => array($gallery['email']),
                    'url' => array($gallery['gallery_url']),
                    'domain_ip' => array(GetIpFromUrl($gallery['gallery_url'])),
                    'submit_ip' => array($_SERVER['REMOTE_ADDR']),
                    'word' => array($gallery['description'], $gallery['keywords']),
                    'html' => array($gallery['html']),
                    'headers' => array($gallery['headers']),
                    'dns' => GetNameServers($gallery['gallery_url']));

    return CheckBlacklist($checks, $full_check);
}

function CheckBlacklistPartner(&$partner, $full_check = FALSE)
{
    $checks = array('email' => array($partner['email']),
                    'url' => null,
                    'domain_ip' => null,
                    'submit_ip' => array($_SERVER['REMOTE_ADDR']),
                    'word' => array($partner['name']),
                    'html' => null);

    foreach( $partner as $key => $value )
    {
        if( stristr($key, 'url') )
        {
            if( !is_array($checks['url']) )
            {
                $checks['url'] = array();
            }

            $checks['url'][] = $value;
        }
    }

    return CheckBlacklist($checks, $full_check);
}

function &CheckBlacklist(&$checks, $full_check)
{
    global $DB, $BL_CACHE;

    $found = array();

    if( !is_array($BL_CACHE) )
    {
        $BL_CACHE = array();

        $result = $DB->Query('SELECT * FROM `tx_blacklist`');
        while( $item = $DB->NextRow($result) )
        {
            $BL_CACHE[] = $item;
        }
        $DB->Free($result);
    }

    foreach( $BL_CACHE as $item )
    {
        $to_check = $checks[$item['type']];

        if( !$item['regex'] )
        {
            $item['value'] = preg_quote($item['value'], '~');
        }
        else
        {
            $item['value'] = preg_replace("%(?<!\\\)~%", '\\~', $item['value']);
        }

        if( is_array($to_check) )
        {
            foreach( $to_check as $check_item )
            {
                if( empty($check_item) )
                {
                    continue;
                }

                if( preg_match("~({$item['value']})~i", $check_item, $matches) )
                {
                    $item['match'] = $matches[1];
                    $found[] = $item;

                    if( !$full_check )
                    {
                        break;
                    }
                }
            }
        }

        if( !$full_check && count($found) )
        {
            break;
        }
    }

    if( count($found) )
    {
        return $found;
    }
    else
    {
        return FALSE;
    }
}

function CheckWhitelist(&$gallery)
{
    global $DB, $WL_CACHE;

    $found = FALSE;
    $options = array('allow_redirect' => 0,
                     'allow_norecip' => 0,
                     'allow_autoapprove' => 0,
                     'allow_noconfirm' => 0,
                     'allow_blacklist' => 0);

    $checks = array('email' => array($gallery['email']),
                    'url' => array($gallery['gallery_url']),
                    'domain_ip' => array(GetIpFromUrl($gallery['gallery_url'])),
                    'submit_ip' => array($_SERVER['REMOTE_ADDR']),
                    'dns' => GetNameServers($gallery['gallery_url']));

    if( !is_array($WL_CACHE) )
    {
        $WL_CACHE = array();

        $result = $DB->Query('SELECT * FROM `tx_whitelist`');
        while( $item = $DB->NextRow($result) )
        {
            $WL_CACHE[] = $item;
        }
        $DB->Free($result);
    }

    foreach( $WL_CACHE as $item )
    {
        $to_check = $checks[$item['type']];

        if( !$item['regex'] )
        {
            $item['value'] = preg_quote($item['value'], '~');
        }
        else
        {
            $item['value'] = preg_replace("%(?<!\\\)~%", '\\~', $item['value']);
        }

        if( is_array($to_check) )
        {
            foreach( $to_check as $check_item )
            {
                if( empty($check_item) )
                {
                    continue;
                }

                if( preg_match("~{$item['value']}~i", $check_item, $matches) )
                {
                    $found = TRUE;
                    $options['allow_redirect'] |= $item['allow_redirect'];
                    $options['allow_norecip'] |= $item['allow_norecip'];
                    $options['allow_autoapprove'] |= $item['allow_autoapprove'];
                    $options['allow_noconfirm'] |= $item['allow_noconfirm'];
                    $options['allow_blacklist'] |= $item['allow_blacklist'];
                }
            }
        }
    }

    if( $found )
    {
        return $options;
    }
    else
    {
        return FALSE;
    }
}

function MergeWhitelistOptions($whitelisted, $partner)
{
    $merged = array('allow_redirect' => 0,
                    'allow_norecip' => 0,
                    'allow_autoapprove' => 0,
                    'allow_noconfirm' => 0,
                    'allow_blacklist' => 0);

    if( is_array($whitelisted) && is_array($partner) )
    {
        $merged['allow_redirect'] = $whitelisted['allow_redirect'] | $partner['allow_redirect'];
        $merged['allow_norecip'] = $whitelisted['allow_norecip'] | $partner['allow_norecip'];
        $merged['allow_autoapprove'] = $whitelisted['allow_autoapprove'] | $partner['allow_autoapprove'];
        $merged['allow_noconfirm'] = $whitelisted['allow_noconfirm'] | $partner['allow_noconfirm'];
        $merged['allow_blacklist'] = $whitelisted['allow_blacklist'] | $partner['allow_blacklist'];
    }
    else if( is_array($whitelisted) )
    {
        return $whitelisted;
    }
    else if( is_array($partner) )
    {
        return $partner;
    }

    return $merged;
}

function CreateBindList(&$items)
{
    $bind_list = array();

    if( !is_array($items) )
    {
        $items = array($items);
    }

    for($i = 0; $i < count($items); $i++)
    {
        $bind_list[] = '?';
    }

    return join(',', $bind_list);
}

function CreateUserInsert($table, &$values, $columns = null)
{
    global $DB;

    $query = array('bind_list' => array(), 'binds' => array());

    if( $columns == null )
    {
        $columns = $DB->GetColumns($table);
    }

    foreach( $columns as $column )
    {
        $query['binds'][] = $values[$column];
        $query['bind_list'][] = '?';
    }

    $query['bind_list'] = join(',', $query['bind_list']);

    return $query;
}

function GetCategoryFormat($format, &$category)
{
    global $L;

    $settings = array();

    $fmt = ($format == FMT_PICTURES) ? 'pics' : 'movies';

    $settings['allowed'] = $category[$fmt.'_allowed'];
    $settings['minimum'] = $category[$fmt.'_minimum'];
    $settings['maximum'] = $category[$fmt.'_maximum'];
    $settings['preview_size'] = $category[$fmt.'_preview_size'];
    $settings['preview_allowed'] = $category[$fmt.'_preview_allowed'];
    $settings['file_size'] = $category[$fmt.'_file_size'];
    $settings['annotation'] = $category[$fmt.'_annotation'];
    $settings['format_lang'] = ($format == FMT_PICTURES) ? $L['PICTURE'] : $L['MOVIE'];

    if( !$category['pics_allowed'] && $format == FMT_PICTURES )
    {
        $settings['format_lang'] = $L['MOVIE'];
    }
    else if( !$category['movies_allowed'] && $format == FMT_MOVIES )
    {
        $settings['format_lang'] = $L['PICTURE'];
    }

    return $settings;
}

function GetIpFromUrl($url)
{
    $parsed = parse_url($url);
    return gethostbyname($parsed['host']);
}

function &ScanGallery(&$gallery, &$category, &$whitelisted, $all_images = FALSE)
{
    require_once("{$GLOBALS['BASE_DIR']}/includes/http.class.php");
    require_once("{$GLOBALS['BASE_DIR']}/includes/htmlparser.class.php");

    // Setup default values
    $results = array('thumbnails' => 0,
                     'links' => 0,
                     'format' => FMT_PICTURES,
                     'has_recip' => FALSE,
                     'has_2257' => FALSE,
                     'thumbs' => array(),
                     'server_match' => TRUE);


    // Download the gallery page
    $http = new Http();
    $http_result = $http->Get($gallery['gallery_url'], $whitelisted['allow_redirect']);

    // Record the request results
    $results = array_merge($results, $http->request_info);
    $results['page_hash'] = md5($http->body);
    $results['gallery_ip'] = GetIpFromUrl($http->end_url);
    $results['bytes'] = intval($results['size_download']);
    $results['html'] = $http->body;
    $results['headers'] = trim($http->raw_response_headers);
    $results['status'] = $http->response_headers['status'];
    $results['success'] = $http_result;
    $results['errstr'] = $http->errstr;
    $results['end_url'] = $http->end_url;

    if( !$http_result )
    {
        $http_result = null;
        return $results;
    }

    // Check if reciprocal link and 2257 code are present
    $results['has_recip'] = CheckReciprocal($http->body);
    $results['has_2257'] = Check2257($http->body);

    // Extract information from the gallery HTML
    $parser = new PageParser($http->end_url, $category['pics_extensions'], $category['movies_extensions']);
    $parser->parse($http->body);

    $results['links'] = $parser->num_links;

    if( $parser->num_content_links > 0 )
    {
        if( $parser->num_picture_links > $parser->num_movie_links )
        {
            $results['format'] = FMT_PICTURES;
            $results['thumbnails'] = $parser->num_picture_links;
            $results['preview'] = $parser->thumbs['pictures'][array_rand($parser->thumbs['pictures'])]['full'];
            $results['thumbs'] = array_values($parser->thumbs['pictures']);
        }
        else
        {
            $results['format'] = FMT_MOVIES;
            $results['thumbnails'] = $parser->num_movie_links;
            $results['preview'] = $parser->thumbs['movies'][array_rand($parser->thumbs['movies'])]['full'];
            $results['thumbs'] = array_values($parser->thumbs['movies']);
        }
    }
    else if( $all_images )
    {
        $results['thumbnails'] = count($parser->images);
        $results['preview'] = $parser->images[array_rand($parser->images)]['full'];
        $results['thumbs'] = array_values($parser->images);
    }

    // Check that gallery content is hosted on same server as the gallery itself
    $parsed_gallery_url = parse_url($results['end_url']);
    $parsed_gallery_url['host'] = preg_quote(preg_replace('~^www\.~', '', $parsed_gallery_url['host']));
    foreach( $results['thumbs'] as $thumb )
    {
        $parsed_content_url = parse_url($thumb['content']);

        if( !preg_match("~{$parsed_gallery_url['host']}~", $parsed_content_url['host']) )
        {
            $results['server_match'] = FALSE;
            break;
        }
    }

    $parser->Cleanup();
    unset($parser);
    $http->Cleanup();
    unset($http);

    return $results;
}

function ResolvePath($path)
{
    $path = explode('/', str_replace('//', '/', $path));

    for( $i = 0; $i < count($path); $i++ )
    {
        if( $path[$i] == '.' )
        {
            unset($path[$i]);
            $path = array_values($path);
            $i--;
        }
        elseif( $path[$i] == '..' AND ($i > 1 OR ($i == 1 AND $path[0] != '')) )
        {
            unset($path[$i]);
            unset($path[$i-1]);
            $path = array_values($path);
            $i -= 2;
        }
        elseif( $path[$i] == '..' AND $i == 1 AND $path[0] == '' )
        {
            unset($path[$i]);
            $path = array_values($path);
            $i--;
        }
        else
        {
            continue;
        }
    }

    return implode('/', $path);
}

function RelativeToAbsolute($start_url, $relative_url)
{
    if( preg_match('~^https?://~', $relative_url) )
    {
        return $relative_url;
    }

    $parsed = parse_url($start_url);
    $base_url = "{$parsed['scheme']}://{$parsed['host']}" . ($parsed['port'] ? ":{$parsed['port']}" : "");
    $path = $parsed['path'];

    if( $relative_url{0} == '/' )
    {
        return $base_url . ResolvePath($relative_url);
    }

    $path = preg_replace('~[^/]+$~', '', $path);

    return $base_url . ResolvePath($path . $relative_url);
}

class PageParser
{
    var $parser;
    var $base_url;
    var $start_url;
    var $movie_exts;
    var $picture_exts;
    var $link_exts;
    var $num_links = 0;
    var $num_movie_links = 0;
    var $num_picture_links = 0;
    var $num_content_links = 0;
    var $in_links = array();
    var $thumbs = array();
    var $images = array();
    var $scripts = array();

    function PageParser($url, $picture_exts, $movie_exts)
    {
        global $C;

        if( !isset($C['min_thumb_height']) )
        {
            $C['min_thumb_height'] = 70;
        }

        if( !isset($C['min_thumb_width']) )
        {
            $C['min_thumb_width'] = 70;
        }

        if( !isset($C['max_thumb_height']) )
        {
            $C['max_thumb_height'] = 400;
        }

        if( !isset($C['max_thumb_width']) )
        {
            $C['max_thumb_width'] = 400;
        }

        $this->start_url = $url;
        $this->base_url = $url;
        $this->picture_exts = str_replace(',', '|', $picture_exts);
        $this->movie_exts = str_replace(',', '|', $movie_exts);
        $this->link_exts = 'jpeg|jpg|png';
    }

    function Cleanup()
    {
        $this->parser->Cleanup();

        unset($this->parser);
        unset($this->base_url);
        unset($this->start_url);
        unset($this->movie_exts);
        unset($this->picture_exts);
        unset($this->link_exts);
        unset($this->num_links);
        unset($this->num_movie_links);
        unset($this->num_picture_links);
        unset($this->num_content_links);
        unset($this->in_links);
        unset($this->thumbs);
        unset($this->scripts);
        unset($this->images);
    }

    function parse($data)
    {
        $this->parser = new XML_HTMLSax();
        $this->parser->set_object($this);
        $this->parser->set_option('XML_OPTION_ENTIES_UNPARSED');
        $this->parser->set_option('XML_OPTION_FULL_ESCAPES');
        $this->parser->set_element_handler('tagOpen', 'tagClose');
        $this->parser->parse($data);
    }

    function tagOpen(&$parser, $name, $attrs)
    {
        global $C;

        foreach( $attrs as $key => $val )
        {
            $attrs[$key] = trim($val);
        }

        switch($name)
        {
            case 'a':
            {
                $href_no_query = preg_replace('~\?.*$~', '', $attrs['href']);
                $is_picture_link = preg_match("~\.({$this->picture_exts})$~i", $href_no_query);
                $is_movie_link = preg_match("~\.({$this->movie_exts})$~i", $href_no_query);

                if( $is_picture_link || $is_movie_link )
                {
                    $this->in_links[] = RelativeToAbsolute($this->base_url, $attrs['href']);
                }
                else
                {
                    $this->num_links++;
                }
            }
            break;

            case 'img':
            {
                // Images with a small width or height generally aren't the thumbnail
                if( isset($attrs['height']) && isset($attrs['width']) && ($attrs['height'] < $C['min_thumb_height'] || $attrs['width'] < $C['min_thumb_width'] || $attrs['height'] > $C['max_thumb_height'] || $attrs['width'] > $C['max_thumb_width']) )
                {
                    break;
                }

                $src_no_query = preg_replace('~\?.*$~', '', $attrs['src']);
                $is_thumbnail = preg_match("~\.({$this->link_exts})$~i", $src_no_query);

                if( $is_thumbnail )
                {
                    $imgsrc = RelativeToAbsolute($this->base_url, $attrs['src']);
                    $this->images[] = array('preview' => $imgsrc, 'full' => $imgsrc, 'content' => $imgsrc);
                }

                if( count($this->in_links) )
                {
                    if( $is_thumbnail )
                    {
                        $link = array_pop($this->in_links);
                        $link_no_query = preg_replace('~\?.*$~', '', $link);

                        $is_picture_link = preg_match("~\.({$this->picture_exts})$~i", $link_no_query);
                        $is_movie_link = preg_match("~\.({$this->movie_exts})$~i", $link_no_query);
                        $format = $is_picture_link ? FMT_PICTURES : FMT_MOVIES;

                        if( !isset($this->thumbs[$format][$link_no_query]) )
                        {
                            ($is_picture_link ? $this->num_picture_links++ : $this->num_movie_links++);
                            $this->num_content_links++;
                        }

                        $attrs['src'] = RelativeToAbsolute($this->base_url, $attrs['src']);

                        $this->thumbs[$format][$link_no_query] = array('preview' => $attrs['src'], 'full' => ($is_movie_link ? $attrs['src'] : $link), 'content' => $link);
                    }
                    else
                    {
                        $this->num_links++;
                    }
                }
            }
            break;

            case 'base':
            {
                if( isset($attrs['href']) && preg_match('~^https?://~i', $attrs['href']) )
                {
                    $this->base_url = $attrs['href'];
                }
            }
            break;

            case 'script':
            {
                if( isset($attrs['src']) )
                {
                    $this->scripts[] = RelativeToAbsolute($this->base_url, $attrs['src']);
                }
            }
            break;
        }
    }

    function tagClose(&$parser, $name)
    {
        switch($name)
        {
            case 'a':
            {
                array_pop($this->in_links);
            }
            break;
        }
    }
}

class SelectBuilder
{
    var $query;
    var $binds = array();
    var $wheres = array();
    var $orders = array();
    var $joins = array();
    var $error = FALSE;
    var $limit = null;
    var $group = null;
    var $order_string = null;
    var $errstr;

    function SelectBuilder($items, $table)
    {
        $this->query = "SELECT $items FROM `$table`";
    }

    function ProcessFieldName($field)
    {
        $field_parts = explode('.', $field);
        $placeholders = array();
        $parts = array('placeholders' => '', 'binds' => array());

        foreach( $field_parts as $part )
        {
            $placeholders[] = '#';
            $parts['binds'][] = $part;
        }

        $parts['placeholders'] = join('.', $placeholders);

        return $parts;
    }

    function GeneratePiece($field, $operator, $value)
    {
        $piece = '';

        $field = $this->ProcessFieldName($field);

        switch($operator)
        {
        case ST_STARTS:
            $piece = "{$field['placeholders']} LIKE ?";
            $this->binds = array_merge($this->binds, $field['binds']);
            $this->binds[] = "$value%";
            break;

        case ST_MATCHES:
            $piece = "{$field['placeholders']}=?";
            $this->binds = array_merge($this->binds, $field['binds']);
            $this->binds[] = $value;
            break;

        case ST_NOT_MATCHES:
            $piece = "{$field['placeholders']}!=?";
            $this->binds = array_merge($this->binds, $field['binds']);
            $this->binds[] = $value;
            break;

        case ST_BETWEEN:
            list($min, $max) = explode(',', $value);

            $piece = "{$field['placeholders']} BETWEEN ? AND ?";
            $this->binds = array_merge($this->binds, $field['binds']);
            $this->binds[] = $min;
            $this->binds[] = $max;
            break;

        case ST_GREATER:
            $piece = "{$field['placeholders']} > ?";
            $this->binds = array_merge($this->binds, $field['binds']);
            $this->binds[] = $value;

            break;

        case ST_LESS:
            $piece = "{$field['placeholders']} < ?";
            $this->binds = array_merge($this->binds, $field['binds']);
            $this->binds[] = $value;

            break;

        case ST_EMPTY:
            $piece = "({$field['placeholders']}='' OR {$field['placeholders']} IS NULL)";
            $this->binds = array_merge($this->binds, $field['binds'], $field['binds']);
            break;

        case ST_NOT_EMPTY:
            $piece = "({$field['placeholders']}!='' AND {$field['placeholders']} IS NOT NULL)";
            $this->binds = array_merge($this->binds, $field['binds'], $field['binds']);
            break;

        case ST_NULL:
            $piece = "{$field['placeholders']} IS NULL";
            $this->binds = array_merge($this->binds, $field['binds']);
            break;

        case ST_NOT_NULL:
            $piece = "{$field['placeholders']} IS NOT NULL";
            $this->binds = array_merge($this->binds, $field['binds']);
            break;

        case ST_IN:
            $items = array_unique(explode(',', $value));

            $piece = "{$field['placeholders']} IN (".CreateBindList($items).")";
            $this->binds = array_merge($this->binds, $field['binds'], $items);
            break;

        case ST_NOT_IN:
            $items = array_unique(explode(',', $value));

            $piece = "{$field['placeholders']} NOT IN (".CreateBindList($items).")";
            $this->binds = array_merge($this->binds, $field['binds'], $items);
            break;

        case ST_ANY:
            break;

        // 'contains' is the default
        default:
            $piece = "{$field['placeholders']} LIKE ?";
            $this->binds = array_merge($this->binds, $field['binds']);
            $this->binds[] = "%$value%";
            break;
        }

        return $piece;
    }

    function AddWhereString($clause)
    {
        $this->wheres[] = $clause;
    }

    function AddWhere($field, $operator, $value = '', $no_value = FALSE)
    {
        if( $no_value && $value == '' )
            return;

        $newpiece = $this->GeneratePiece($field, $operator, $value);

        if( !empty($newpiece) )
        {
            $this->wheres[] = $newpiece;
        }
    }

    function AddMultiWhere($fields, $operators, $values, $no_value = FALSE)
    {
        if( $no_value && count($value) < 1 )
            return;

        $ors = array();

        for( $i = 0; $i < count($fields); $i++ )
        {
            $newpiece = $this->GeneratePiece($fields[$i], $operators[$i], $values[$i]);

            if( !empty($newpiece) )
            {
                $ors[] = $newpiece;
            }
        }

        $this->wheres[] = "(" . join(' OR ', $ors) . ")";
    }

    function AddFulltextWhere($field, $value, $no_value = FALSE)
    {
        if( $no_value && $value == '' )
            return;

        $field_parts = explode(',', $field);
        $parts = array();

        foreach( $field_parts as $part )
        {
            $parts[] = '#';
            $this->binds[] = $part;
        }

        $this->wheres[] = 'MATCH('. join(',', $parts) .') AGAINST (? IN BOOLEAN MODE)';
        $this->binds[] = $value;
    }

    function AddOrder($field, $direction = 'ASC')
    {
        if( preg_match('~^RAND\(~', $field) )
        {
            $this->orders[] = $field;
        }
        else
        {
            $field = $this->ProcessFieldName($field);

            if( $direction != 'ASC' && $direction != 'DESC' )
            {
                $direction = 'ASC';
            }

            $this->binds = array_merge($this->binds, $field['binds']);
            $this->orders[] = "{$field['placeholders']} $direction";
        }
    }

    function SetOrderString($string, &$fields)
    {
        foreach( $fields as $field )
        {
            $string = str_replace($field, "`$field`", $string);
        }

        $this->order_string = $string;
    }

    function AddJoin($left_table, $right_table, $join, $field)
    {
        $this->joins[] = "$join JOIN `$right_table` ON `$right_table`.`$field`=`$left_table`.`$field`";
    }

    function AddGroup($field)
    {
        $field = $this->ProcessFieldName($field);
        $this->group = '`' . join('`.`', $field['binds']) . '`';
    }

    function SetLimit($limit)
    {
        $this->limit = $limit;
    }

    function Generate()
    {
        $select = $this->query;

        if( count($this->joins) )
        {
            $select .= " " . join(' ', $this->joins);
        }

        if( count($this->wheres) )
        {
            $select .= " WHERE " . join(' AND ', $this->wheres);
        }

        if( isset($this->group) )
        {
            $select .= " GROUP BY " . $this->group;
        }

        if( isset($this->order_string) )
        {
            $select .= " ORDER BY " . $this->order_string;
        }
        else if( count($this->orders) )
        {
            $select .= " ORDER BY " . join(',', $this->orders);
        }

        if( isset($this->limit) )
        {
            $select .= " LIMIT {$this->limit}";
        }

        return $select;
    }
}


class UpdateBuilder extends SelectBuilder
{
    var $table;
    var $updates = array();

    function UpdateBuilder($table)
    {
        $this->table = $table;
    }

    function AddSet($field, $value)
    {
        $field = $this->ProcessFieldName($field);

        $this->updates[] = "{$field['placeholders']}=?";
        $this->binds = array_merge($this->binds, $field['binds']);
        $this->binds[] = $value;
    }

    function Generate()
    {
        $select = "UPDATE {$this->table} ";

        if( count($this->joins) )
        {
            $select .= " " . join(' ', $this->joins);
        }

        $select .= " SET " . join(', ', $this->updates);

        if( count($this->wheres) )
        {
            $select .= " WHERE " . join(' AND ', $this->wheres);
        }

        return $select;
    }
}
?>
