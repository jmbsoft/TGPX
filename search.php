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

// If you are using this script on one of the domains you have defined in the
// Manage Domains interface, uncomment the following line and set the directory
// path to your TGPX Server Edition installation
//chdir('/full/path/to/tgpxse/install');


if( !defined('E_STRICT') ) define('E_STRICT', 2048);
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);

require_once('includes/config.php');
require_once('includes/template.class.php');
require_once('includes/mysql.class.php');

@set_magic_quotes_runtime(0);

if( function_exists('date_default_timezone_set') )
{
    date_default_timezone_set('America/Chicago');
}

if( get_magic_quotes_gpc() )
{
    _astripslashes($_GET);
}

$_GET['s'] = trim($_GET['s']);
$page = isset($_GET['p']) ? $_GET['p'] : 1;
$per_page = isset($_GET['pp']) ? $_GET['pp'] : 20;
$too_short = strlen($_GET['s']) < 4;
$search_id = md5("{$_GET['s']}-$page-{$_GET['c']}-{$_GET['f']}");

$t = new Template();
$t->caching = TRUE;
$t->cache_lifetime = 3600;

$DB = new DB($C['db_hostname'], $C['db_username'], $C['db_password'], $C['db_name']);
$DB->Connect();

$domain = $DB->Row('SELECT * FROM `tx_domains` WHERE `domain`=?', array(preg_replace('~^www\.~i', '', strtolower($_SERVER['HTTP_HOST']))));

if( $domain )
{
    $C['cookie_domain'] = $domain['domain'];
}



if( !$too_short && !$t->is_cached($domain['template_prefix'].'search-results.tpl', $search_id) )
{


    $categories =& $DB->FetchAll('SELECT `name`,`tag` FROM `tx_categories` WHERE `hidden`=0 ORDER BY `name`', null, 'tag');

    $galleries = array();

    $search_wheres = array('MATCH(`description`,`keywords`) AGAINST(? IN BOOLEAN MODE)', '`status` IN (?,?)');
    $search_binds = array($_GET['s'], 'used', 'holding');

    // If category was specified, add it to the where clause
    if( $_GET['c'] )
    {
        $search_wheres[] = 'MATCH(`categories`) AGAINST(? IN BOOLEAN MODE)';
        $search_binds[] = $_GET['c'];
    }

    // If format was specified, add it to the where clause
    if( $_GET['f'] )
    {
        $search_wheres[] = '`format`=?';
        $search_binds[] = $_GET['f'];
    }

    $result = $DB->QueryWithPagination('SELECT * FROM `tx_galleries` WHERE ' . join(' AND ', $search_wheres), $search_binds, $page, $per_page);

    if( $result['result'] )
    {
        while( $gallery = $DB->NextRow($result['result']) )
        {
            $fields = $DB->Row('SELECT * FROM `tx_gallery_fields` WHERE `gallery_id`=?', array($gallery['gallery_id']));

            if( $fields )
            {
                $gallery = array_merge($gallery, $fields);
            }

            // Get the gallery preview thumbnail, if it has one
            if( $gallery['has_preview'] )
            {
                $prev_wheres = array('`gallery_id`=?');
                $prev_binds = array($gallery['gallery_id']);

                // User has specified the thumbnail size to get
                if( $_GET['pt'] && $gallery['format'] == 'pictures' )
                {
                    $prev_wheres[] = '`dimensions`=?';
                    $prev_binds[] = $_GET['pt'];
                }
                else if( $_GET['mt'] && $gallery['format'] == 'movies' )
                {
                    $prev_wheres[] = '`dimensions`=?';
                    $prev_binds[] = $_GET['mt'];
                }

                $preview = $DB->Row('SELECT * FROM `tx_gallery_previews` WHERE ' . join(' AND ', $prev_wheres) . ' LIMIT 1', $prev_binds);

                if( $preview )
                {
                    $gallery = array_merge($gallery, $preview);
                }
            }

            $temp_categories = array();
            foreach( explode(' ', $gallery['categories']) as $category_tag )
            {
                if( $categories[$category_tag] )
                {
                    $temp_categories[] = $categories[$category_tag];
                }
            }

            $gallery['categories'] = $temp_categories;
            $gallery['category'] = $gallery['categories'][0]['name'];
            $galleries[] = $gallery;
        }

        $DB->Free($result['result']);
        unset($result['result']);
    }

    $t->assign_by_ref('search_categories', $categories);
    $t->assign_by_ref('pagination', $result);
    $t->assign_by_ref('results', $galleries);

}

if( !$too_short && $page == 1 && $C['log_searches'] )
{
    if( $_COOKIE['txsearch'] != $_GET['s'] )
    {
        logsearch();
    }

    setcookie('txsearch', $_GET['s'], time()+86400, '/', $C['cookie_domain']);
}

$t->assign_by_ref('config', $C);
$t->assign('search_term', $_GET['s']);
$t->assign('search_category', $_GET['c']);
$t->assign('search_format', $_GET['f']);
$t->assign('search_too_short', $too_short);
$t->assign('page', $page);
$t->assign('per_page', $per_page);
$t->assign('picture_thumb', $_GET['pt']);
$t->assign('movie_thumb', $_GET['mt']);
$t->assign('search_formats', array(array('format' => 'pictures', 'name' => 'Pictures'), array('format' => 'movies', 'name' => 'Movies')));

$t->display($domain['template_prefix'].'search-results.tpl', $search_id);

if( isset($DB) )
{
    $DB->Disconnect();
}

function logsearch()
{
    global $DB, $C;

    if( !isset($DB) )
    {
        $DB = new DB($C['db_hostname'], $C['db_username'], $C['db_password'], $C['db_name']);
        $DB->Connect();
    }

    if( preg_match_all('~[\'"]([^\'"]+)[\'"]|(\b\w+\b)~', $_GET['s'], $matches) )
    {
        $date = gmdate('Y-m-d H:i:s', _timewithtz());

        foreach( $matches[0] as $match )
        {
            $match = str_replace(array('"', '\''), '', $match);
            if( $DB->Update('UPDATE `tx_search_terms` SET `searches`=`searches`+1,`date_last_search`=? WHERE `term`=?', array($date, $match)) < 1 )
            {
                $DB->Update('INSERT INTO `tx_search_terms` VALUES (?,?,?,?)', array(null, $match, 1, $date));
            }
        }
    }
}

function _timewithtz($timestamp = null)
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

function thilite($string)
{
    $term = $_GET['s'];

    if( $term )
    {
        if( isset($GLOBALS['re_matches']) || preg_match_all('~("[^"]+"|\b\w+\b)~', $term, $GLOBALS['re_matches']) )
        {
            foreach( $GLOBALS['re_matches'][0] as $match )
            {
                $match = preg_quote(str_replace(array('+', '-', '*', '"', '(', ')'), '', $match));
                $string = preg_replace("/\b($match)\b/i", "<span class=\"hilite\">$1</span>", $string);
            }
        }
    }

    return $string;
}

function _astripslashes(&$array)
{
    foreach($array as $key => $value)
    {
        if( is_array($array[$key]) )
        {
            _astripslashes($array[$key]);
        }
        else
        {
            $array[$key] = stripslashes($value);
        }
    }
}

?>
