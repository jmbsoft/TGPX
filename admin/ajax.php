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

require_once('../includes/common.php');
require_once("{$GLOBALS['BASE_DIR']}/includes/mysql.class.php");
require_once("{$GLOBALS['BASE_DIR']}/includes/template.class.php");
require_once("{$GLOBALS['BASE_DIR']}/includes/http.class.php");
require_once("{$GLOBALS['BASE_DIR']}/admin/includes/json.class.php");
require_once("{$GLOBALS['BASE_DIR']}/admin/includes/functions.php");

// Setup JSON response
$json = new JSON();

set_error_handler('AjaxError');

// Do not allow browsers to cache this script
header("Expires: Mon, 26 Jul 1990 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
header("Content-type: text/html; charset=ISO-8859-1");

SetupRequest();

// Setup database connection
$DB = new DB($C['db_hostname'], $C['db_username'], $C['db_password'], $C['db_name']);
$DB->Connect();

if( ($error = ValidLogin()) === TRUE )
{
    $function = $_REQUEST['r'];

    if( ValidFunction($function) )
    {
        call_user_func($function);
    }
    else
    {
        trigger_error("Function '$function' is not a valid TGPX function", E_USER_ERROR);
    }
}
else
{
    if( !$error )
        $error = 'Control panel login has expired';

    echo $json->encode(array('status' => JSON_FAILURE, 'message' => $error));
}

$DB->Disconnect();

function txAdSearch()
{
    global $DB, $json, $C;

    $out =& GenericSearch('tx_ads', 'ads-tr.php');

    echo $json->encode($out);
}

function txAdDelete()
{
    global $json, $DB;

    VerifyAdministrator(TRUE);

    if( !is_array($_REQUEST['ad_id']) )
    {
        $_REQUEST['ad_id'] = array($_REQUEST['ad_id']);
    }

    foreach($_REQUEST['ad_id'] as $ad_id)
    {
        $DB->Update('DELETE FROM `tx_ads` WHERE `ad_id`=?', array($ad_id));
    }

    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => 'The selected ads have been deleted'));
}

function txDomainSearch()
{
    global $DB, $json, $C;

    $out =& GenericSearch('tx_domains', 'domains-tr.php');

    echo $json->encode($out);
}

function txDomainDelete()
{
    global $json, $DB;

    VerifyAdministrator(TRUE);

    if( !is_array($_REQUEST['domain_id']) )
    {
        $_REQUEST['domain_id'] = array($_REQUEST['domain_id']);
    }

    foreach($_REQUEST['domain_id'] as $domain_id)
    {
        $DB->Update('DELETE FROM `tx_domains` WHERE `domain_id`=?', array($domain_id));
    }

    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => 'The selected domains have been deleted'));
}

function txSearchTermSearch()
{
    global $DB, $json, $C;

    $out =& GenericSearch('tx_search_terms', 'search-terms-tr.php');

    echo $json->encode($out);
}

function txSearchTermDelete()
{
    global $json, $DB;

    VerifyAdministrator(TRUE);

    if( $_REQUEST['term_id'] == 'ALL' )
    {
        $DB->Update('DELETE FROM `tx_search_terms`');

        echo $json->encode(array('status' => JSON_SUCCESS, 'message' => 'All search terms have been deleted'));
    }
    else
    {
        if( !is_array($_REQUEST['term_id']) )
        {
            $_REQUEST['term_id'] = array($_REQUEST['term_id']);
        }

        foreach($_REQUEST['term_id'] as $term_id)
        {
            $DB->Update('DELETE FROM `tx_search_terms` WHERE `term_id`=?', array($term_id));
        }

        echo $json->encode(array('status' => JSON_SUCCESS, 'message' => 'The selected search terms have been deleted'));
    }
}

function txPageBuildHistorySearch()
{
    global $DB, $json, $C;

    $_REQUEST['order'] = 'date_start';
    $_REQUEST['direction'] = 'DESC';

    $out =& GenericSearch('tx_build_history', 'pages-build-history-tr.php');

    echo $json->encode($out);
}

function txPageBuildHistoryClear()
{
    global $json, $DB;

    VerifyAdministrator(TRUE);

    $DB->Update('DELETE FROM `tx_build_history`');

    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => 'The page build history has been cleared'));
}

function txRssFeedImport()
{
    global $DB, $json, $C;

    $out = array('status' => JSON_FAILURE);

    $feed = $DB->Row('SELECT * FROM `tx_rss_feeds` WHERE `feed_id`=?', array($_REQUEST['feed_id']));

    if( $feed )
    {
        $imported = ImportFromRss($feed);
        if( is_numeric($imported) )
        {
            $out['status'] = JSON_SUCCESS;
            $out['message'] = "$imported Galleries have been imported from this RSS feed";
        }
        else
        {
            $out['message'] = $imported;
        }
    }
    else
    {
        $out['message'] = 'Invalid feed ID provided';
    }

    echo $json->encode($out);
}

function txRssFeedSearch()
{
    global $DB, $json, $C;

    $GLOBALS['sponsors'] =& $DB->FetchAll('SELECT * FROM `tx_sponsors`', null, 'sponsor_id');
    $out =& GenericSearch('tx_rss_feeds', 'rss-feeds-tr.php');

    echo $json->encode($out);
}

function txRssFeedDelete()
{
    global $json, $DB;

    VerifyAdministrator(TRUE);

    if( !is_array($_REQUEST['feed_id']) )
    {
        $_REQUEST['feed_id'] = array($_REQUEST['feed_id']);
    }

    foreach($_REQUEST['feed_id'] as $feed_id)
    {
        $DB->Update('DELETE FROM `tx_rss_feeds` WHERE `feed_id`=?', array($feed_id));
    }

    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => 'The selected RSS feeds have been deleted'));
}

function txRssFeedAccess()
{
    global $DB, $json, $C;

    require_once("{$GLOBALS['BASE_DIR']}/includes/rssparser.class.php");

    $out = array('status' => JSON_FAILURE, 'message' => 'Could not access the RSS feed');

    $http = new Http();

    if( $http->Get($_REQUEST['url'], TRUE, $C['install_url']) )
    {
        $parser = new RSSParser();

        if( ($feed = $parser->Parse($http->body)) !== FALSE )
        {
            if( count($feed['items']) > 0 )
            {
                $out['status'] = JSON_SUCCESS;

                $item = $feed['items'][0];

                ob_start();
                eval('?>' . file_get_contents('includes/rss-feeds-item.php'));
                $out['html'] .= ob_get_contents();
                ob_end_clean();
            }
            else
            {
                $out['message'] = 'No &lt;item&gt; tags could be found in the RSS feed';
            }
        }
        else
        {
            $out['message'] = $parser->errstr;
        }
    }
    else
    {
        $out['message'] = 'Accessing RSS feed failed: ' . $http->errstr;
    }

    echo $json->encode($out);
}

function txGallerySearchSave()
{
    global $DB, $json, $C;

    VerifyPrivileges(P_GALLERY, TRUE);

    $search_id = 0;
    $new = 1;
    $search = $DB->Row('SELECT * FROM `tx_saved_searches` WHERE `identifier`=?', array($_REQUEST['identifier']));

    if( $search )
    {
        $DB->Update('UPDATE `tx_saved_searches` SET `fields`=? WHERE `search_id`=?', array($search['search_id']));
        $search_id = $search['search_id'];
        $new = 0;
    }
    else
    {
        $DB->Update('INSERT INTO `tx_saved_searches` VALUES (?,?,?)',
                    array(null,
                          $_REQUEST['identifier'],
                          $_REQUEST['fields']));

        $search_id = $DB->InsertID();
    }

    echo $json->encode(array('status' => JSON_SUCCESS, 'search_id' => $search_id, 'newsearch' => $new, 'identifier' => htmlspecialchars(StringChop($_REQUEST['identifier'], 40))));
}

function txGallerySearchDelete()
{
    global $DB, $json, $C;

    VerifyPrivileges(P_GALLERY, TRUE);

    $DB->Update('DELETE FROM `tx_saved_searches` WHERE `search_id`=?', array($_REQUEST['search_id']));

    echo $json->encode(array('status' => JSON_SUCCESS, 'search_id' => $_REQUEST['search_id']));
}

function txGallerySearchLoad()
{
    global $DB, $json, $C;

    VerifyPrivileges(P_GALLERY, TRUE);

    $search = $DB->Row('SELECT * FROM `tx_saved_searches` WHERE `search_id`=?', array($_REQUEST['search_id']));

    $json_out = $json->encode(array('status' => JSON_SUCCESS));

    echo preg_replace('~\}$~', ', "fields": ' . $search['fields'] . '}', $json_out);
}

function txPreviewCleanup()
{
    global $DB, $json, $C;

    VerifyPrivileges(P_GALLERY_MODIFY, TRUE);

    $cleaned = 0;
    $result = $DB->Query('SELECT * FROM `tx_gallery_previews`');
    while( $preview = $DB->NextRow($result) )
    {
        $preview['preview_url'] = trim($preview['preview_url']);

        // See if thumb is hosted on this server
        if( preg_match("~^{$C['preview_url']}~", $preview['preview_url']) )
        {
            if( !is_file("{$C['preview_dir']}/{$preview['preview_id']}.jpg") )
            {
                $DB->Update('DELETE FROM `tx_gallery_previews` WHERE `preview_id`=?', array($preview['preview_id']));
                $cleaned++;
            }
        }
        else if( empty($preview['preview_url']) )
        {
            $DB->Update('DELETE FROM `tx_gallery_previews` WHERE `preview_id`=?', array($preview['preview_id']));
            $cleaned++;
        }
    }
    $DB->Free($result);

    $DB->Update('UPDATE `tx_galleries` LEFT JOIN `tx_gallery_previews` USING (`gallery_id`) SET `has_preview`=IF(`preview_id` IS NULL, 0, 1)');

    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => "$cleaned galler" . ($cleaned == 1 ? 'y' : 'ies') . " with a missing preview thumbnail file have been cleaned up"));
}

function txPreviewFilter()
{
    global $DB, $json, $C;

    VerifyPrivileges(P_GALLERY_MODIFY, TRUE);

    require_once("{$GLOBALS['BASE_DIR']}/includes/imager.class.php");

    $out = array('status' => JSON_SUCCESS, 'message' => '', 'close' => 0, 'level' => 0);
    $image = SafeFilename("{$C['preview_dir']}/{$_REQUEST['preview_id']}.jpg", FALSE);
    $i = GetImager();

    if( is_file($image) )
    {
        $out['image'] = "{$C['preview_url']}/{$_REQUEST['preview_id']}.jpg?".rand();

        // See if the starting image is in the database, and if not add it
        if( $DB->Count("SELECT COUNT(*) FROM `tx_undos` WHERE `preview_id`=? AND `undo_level`=0", array($_REQUEST['preview_id'])) < 1 )
        {
            $DB->Update("INSERT INTO `tx_undos` VALUES (?, 0, ?)", array($_REQUEST['preview_id'], file_get_contents($image)));
        }

        switch( $_REQUEST['filter'] )
        {
            case 'reset':
            {
                $undo = $DB->Row("SELECT * FROM `tx_undos` WHERE `preview_id`=? AND `undo_level`=0", array($_REQUEST['preview_id']));
                $DB->Update("DELETE FROM `tx_undos` WHERE `preview_id`=? AND `undo_level` > 0", array($_REQUEST['preview_id']));
                FileWrite($image, $undo['image']);
            }
            break;

            case 'undo':
            {
                $level = $DB->Count("SELECT MAX(`undo_level`) FROM `tx_undos` WHERE `preview_id`=?", array($_REQUEST['preview_id']));
                $undo = $DB->Row("SELECT * FROM `tx_undos` WHERE `preview_id`=? AND `undo_level`=?", array($_REQUEST['preview_id'], --$level));
                $DB->Update("DELETE FROM `tx_undos` WHERE `preview_id`=? AND `undo_level`=?", array($_REQUEST['preview_id'], $level + 1));
                FileWrite($image, $undo['image']);
                $out['level'] = $level;
            }
            break;

            case 'save':
            {
                $i->ApplyFilter($image, 'compress', null);
                $DB->Update("DELETE FROM `tx_undos` WHERE `preview_id`=?", array($_REQUEST['preview_id']));
                $out['close'] = 1;
            }
            break;

            case 'cancel':
            {
                $out['close'] = 1;
                $undo = $DB->Row("SELECT * FROM `tx_undos` WHERE `preview_id`=? AND `undo_level`=0", array($_REQUEST['preview_id']));
                $DB->Update("DELETE FROM `tx_undos` WHERE `preview_id`=?", array($_REQUEST['preview_id']));
                if( $undo )
                {
                    FileWrite($image, $undo['image']);
                }
            }
            break;

            default:
            {
                $fd = fopen("{$GLOBALS['BASE_DIR']}/data/_filter_lock", 'w');
                flock($fd, LOCK_EX);

                $i->ApplyFilter($image, $_REQUEST['filter'], $_REQUEST['input']);
                $level = $DB->Count("SELECT MAX(`undo_level`) FROM `tx_undos` WHERE `preview_id`=?", array($_REQUEST['preview_id']));
                $DB->Update("INSERT INTO `tx_undos` VALUES (?, ?, ?)", array($_REQUEST['preview_id'], ++$level, file_get_contents($image)));

                flock($fd, LOCK_UN);
                fclose($fd);

                $out['level'] = $level;
            }
            break;
        }
    }
    else
    {
        $out['close'] = 1;
        $out['status'] = JSON_FAILURE;
        $out['message'] = 'This thumbnail cannot be modified because it is located on a remote server';
    }

    echo $json->encode($out);
}

function txPreviewDelete()
{
    global $DB, $json, $C;

    VerifyPrivileges(P_GALLERY_MODIFY, TRUE);

    $preview = $DB->Row('SELECT * FROM `tx_gallery_previews` WHERE `preview_id`=?', array($_REQUEST['preview_id']));
    $DB->Update('DELETE FROM `tx_gallery_previews` WHERE `preview_id`=?', array($_REQUEST['preview_id']));

    if( file_exists("{$C['preview_dir']}/{$preview['preview_id']}.jpg") )
    {
        @unlink("{$C['preview_dir']}/{$preview['preview_id']}.jpg");
    }

    $DB->Update('UPDATE `tx_galleries` LEFT JOIN `tx_gallery_previews` ON ' .
                '`tx_galleries`.`gallery_id`=`tx_gallery_previews`.`gallery_id` SET ' .
                '`has_preview`=IF(`preview_id` IS NULL, 0, 1) WHERE `tx_galleries`.`gallery_id`=?', array($preview['gallery_id']));

    echo $json->encode(array('status' => JSON_SUCCESS));
}

function txReviewGalleryNext()
{
    global $DB, $json, $C;

    $out = array('status' => JSON_SUCCESS, 'message' => "Next gallery in line has been loaded", 'html' => '', 'gallery_url' => '', 'done' => 0);

    $s = new SelectBuilder('SQL_CALC_FOUND_ROWS *', 'tx_galleries');
    $s->AddWhere('status', ST_MATCHES, 'pending');
    $s->AddWhere('type', ST_MATCHES, $_REQUEST['s_type'], TRUE);
    $s->AddWhere('format', ST_MATCHES, $_REQUEST['s_format'], TRUE);
    $s->AddWhere('sponsor_id', ST_MATCHES, $_REQUEST['s_sponsor_id'], TRUE);
    $s->AddFulltextWhere('categories', $_REQUEST['s_category'], TRUE);
    $s->SetLimit("{$_REQUEST['limit']},1");

    if( $_REQUEST['sort'] == 'random' )
    {
        $junk = array();
        $s->SetOrderString('RAND('.$_REQUEST['seed'].')', $junk);
    }
    else
    {
        $s->AddOrder($_REQUEST['sort'], $_REQUEST['direction']);
    }

    $gallery = $DB->Row($s->Generate(), $s->binds);
    $remain = $DB->Count('SELECT FOUND_ROWS()');

    if( $gallery )
    {
        $icons =& $DB->FetchAll('SELECT * FROM `tx_icons`');

        if( $gallery['has_preview'] )
        {
            $gallery['previews'] =& $DB->FetchAll('SELECT * FROM `tx_gallery_previews` WHERE `gallery_id`=?', array($gallery['gallery_id']));

            foreach( $gallery['previews'] as $index => $preview )
            {
                list($width, $height) = explode('x', $preview['dimensions']);

                if( $width > 180 || $height > 180 )
                {
                    $gallery['previews'][$index]['attrs'] = $width > $height ? ' width="180"' : ' height="180"';
                }
            }
        }

        $out['gallery_url'] = $gallery['gallery_url'];
        $out['gallery_id'] = $gallery['gallery_id'];

        $fields = $DB->Row('SELECT * FROM `tx_gallery_fields` WHERE `gallery_id`=?', array($gallery['gallery_id']));

        if( $fields )
        {
            $gallery = array_merge($gallery, $fields);
        }

        ArrayHSC($gallery);

        $form_html = file_get_contents("includes/review-galleries-form.php");

        ob_start();
        eval('?>' . $form_html);
        $out['html'] = ob_get_contents();
        ob_end_clean();
    }
    else
    {
        $pending = $DB->Count('SELECT COUNT(*) FROM `tx_galleries` WHERE `status`=?', array('pending'));

        $done_html = file_get_contents("includes/review-galleries-done.php");

        ob_start();
        eval('?>' . $done_html);
        $out['html'] = ob_get_contents();
        ob_end_clean();

        $out['done'] = 1;
    }

    echo $json->encode($out);
}

function txDownloadImage()
{
    global $DB, $json, $C;

    $out = array('status' => JSON_FAILURE);
    $cachefile = SafeFilename("_{$_REQUEST['gallery_id']}_" . md5($_REQUEST['image']) . ".jpg", FALSE);

    if( is_file("{$GLOBALS['BASE_DIR']}/cache/$cachefile") )
    {
        $out['imagefile'] = $cachefile;
        $out['src'] = "{$C['install_url']}/cache/$cachefile";
        $out['status'] = JSON_SUCCESS;

        $size = @getimagesize("{$GLOBALS['BASE_DIR']}/cache/$cachefile");

        $out['width'] = $size[0];
        $out['height'] = $size[1];
    }
    else
    {
        $http = new Http();
        if( $http->Get($_REQUEST['image'], TRUE, $_REQUEST['gallery_url']) )
        {
            FileWrite("{$GLOBALS['BASE_DIR']}/cache/$cachefile", $http->body);

            $size = @getimagesize("{$GLOBALS['BASE_DIR']}/cache/$cachefile");

            if( $size !== FALSE )
            {
                $out['imagefile'] = $cachefile;
                $out['src'] = "{$C['install_url']}/cache/$cachefile";
                $out['width'] = $size[0];
                $out['height'] = $size[1];
                $out['status'] = JSON_SUCCESS;
            }
            else
            {
                $out['message'] = 'Downloaded file is not a valid image';
            }
        }
        else
        {
            $out['message'] = "Could not download image: " . $http->errstr;
        }
    }

    echo $json->encode($out);
}

function txDownloadThumb()
{
    global $DB, $json, $C;

    $out = array('status' => JSON_FAILURE);
    $id = md5($_REQUEST['thumb']);
    $cachefile = SafeFilename("_{$_REQUEST['gallery_id']}_" . $id . ".jpg", FALSE);

    if( !is_file("{$GLOBALS['BASE_DIR']}/cache/$cachefile") )
    {
        $http = new Http();
        if( $http->Get($_REQUEST['thumb'], TRUE, $_REQUEST['gallery_url']) )
        {
            FileWrite("{$GLOBALS['BASE_DIR']}/cache/$cachefile", $http->body);
        }
    }

    $out['size'] = @getimagesize("{$GLOBALS['BASE_DIR']}/cache/$cachefile");

    if( $out['size'] !== FALSE )
    {
        if( $out['size'][0] >= $C['min_thumb_width'] && $out['size'][1] >= $C['min_thumb_height'] && $out['size'][0] <= $C['max_thumb_width'] && $out['size'][1] <= $C['max_thumb_height'] )
        {
            $out['src'] = "{$C['install_url']}/cache/$cachefile";
            $out['status'] = JSON_SUCCESS;
            $out['id'] = $id;
        }
        else
        {
            $out['message'] = "Downloading " . htmlspecialchars($_REQUEST['thumb']) . " failed: image size of {$out['size'][0]}x{$out['size'][1]} is " .
                              "not within the range of {$C['min_thumb_width']}x{$C['min_thumb_height']} to {$C['max_thumb_width']}x{$C['max_thumb_height']}";
        }
    }
    else
    {
        $out['message'] = "Downloading " . htmlspecialchars($_REQUEST['thumb']) . " failed: not a valid image file";
    }

    echo $json->encode($out);
}

function txGallerySearchAndReplace()
{
    global $DB, $json, $C;

    VerifyPrivileges(P_GALLERY_MODIFY, TRUE);

    $user_columns = $DB->GetColumns('tx_gallery_fields');
    $preview_columns = $DB->GetColumns('tx_gallery_previews');
    $update = 'UPDATE `tx_galleries`';

    if( in_array($_REQUEST['field'], $user_columns) )
    {
        $update = 'UPDATE `tx_galleries` JOIN `tx_gallery_fields` USING (`gallery_id`)';
    }

    if( in_array($_REQUEST['field'], $preview_columns) )
    {
        $update = 'UPDATE `tx_galleries` JOIN `tx_gallery_previews` USING (`gallery_id`)';
    }

    if( $_REQUEST['search'] == 'NULL' )
    {
        $replacements = $DB->Update($update.' SET #=? WHERE #=? OR # IS NULL',
                                    array($_REQUEST['field'],
                                          $_REQUEST['replace'],
                                          $_REQUEST['field'],
                                          '',
                                          $_REQUEST['field']));
    }
    else
    {
        $replacements = $DB->Update($update.' SET #=REPLACE(#, ?, ?)', array($_REQUEST['field'], $_REQUEST['field'], $_REQUEST['search'], $_REQUEST['replace']));
    }

    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => "$replacements replacements have been made"));
}

function txGallerySearchAndSet()
{
    global $DB, $json, $C;

    VerifyPrivileges(P_GALLERY_MODIFY, TRUE);

    $user_columns = $DB->GetColumns('tx_gallery_fields');
    $preview_columns = $DB->GetColumns('tx_gallery_previews');
    $search_type = ($_REQUEST['search'] == 'NULL' ? ST_EMPTY : ST_CONTAINS);
    $u = new UpdateBuilder('tx_galleries');

    if( in_array($_REQUEST['field'], $user_columns) || in_array($_REQUEST['set_field'], $user_columns) )
    {
        $u->AddJoin('tx_galleries', 'tx_gallery_fields', '', 'gallery_id');
    }

    if( in_array($_REQUEST['field'], $preview_columns) || in_array($_REQUEST['set_field'], $preview_columns) )
    {
        $u->AddJoin('tx_galleries', 'tx_gallery_previews', '', 'gallery_id');
    }

    if( $_REQUEST['replace'] == 'NULL' )
    {
        $_REQUEST['replace'] = null;
    }

    if( $_REQUEST['set_field'] == 'sponsor_id' && $_REQUEST['replace'] != null )
    {
        $sponsor_name = $_REQUEST['replace'];
        $_REQUEST['replace'] = $DB->Count('SELECT `sponsor_id` FROM `tx_sponsors` WHERE `name`=?', array($_REQUEST['replace']));

        if( !$_REQUEST['replace'] )
        {
            echo $json->encode(array('status' => JSON_SUCCESS, 'message' => "Sponsor '".htmlspecialchars($sponsor_name)."' does not exist"));
            return;
        }
    }

    if( $_REQUEST['field'] == 'sponsor_id' && $_REQUEST['search'] != 'NULL' )
    {
        $sponsor_name = $_REQUEST['search'];
        $_REQUEST['search'] = $DB->Count('SELECT `sponsor_id` FROM `tx_sponsors` WHERE `name`=?', array($_REQUEST['search']));

        if( !$_REQUEST['search'] )
        {
            echo $json->encode(array('status' => JSON_SUCCESS, 'message' => "Sponsor '".htmlspecialchars($sponsor_name)."' does not exist"));
            return;
        }

        $search_type = ST_MATCHES;
    }

    $u->AddSet($_REQUEST['set_field'], $_REQUEST['replace']);
    $u->AddWhere($_REQUEST['field'], $search_type, $_REQUEST['search']);

    $replacements = $DB->Update($u->Generate(), $u->binds);

    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => "$replacements changes have been made"));
}

function txGallerySearchAndAppend()
{
    global $DB, $json, $C;

    VerifyPrivileges(P_GALLERY_MODIFY, TRUE);

    $user_columns = $DB->GetColumns('tx_gallery_fields');
    $preview_columns = $DB->GetColumns('tx_gallery_previews');
    $update = 'UPDATE `tx_galleries`';

    if( in_array($_REQUEST['field'], $user_columns) )
    {
        $update = 'UPDATE `tx_galleries` JOIN `tx_gallery_fields` USING (`gallery_id`)';
    }

    if( in_array($_REQUEST['field'], $preview_columns) )
    {
        $update = 'UPDATE `tx_galleries` JOIN `tx_gallery_previews` USING (`gallery_id`)';
    }


    if( $_REQUEST['append'] == 'NULL' || IsEmptyString($_REQUEST['append']) )
    {
        echo $json->encode(array('status' => JSON_FAILURE, 'message' => "Please enter a string to append"));
        return;
    }


    if( $_REQUEST['search'] == 'NULL' )
    {
        $replacements = $DB->Update($update.' SET #=CONCAT(#,?) WHERE #=? OR # IS NULL',
                                    array($_REQUEST['append_field'],
                                          $_REQUEST['append_field'],
                                          $_REQUEST['append'],
                                          $_REQUEST['field'],
                                          $_REQUEST['search'],
                                          '',
                                          $_REQUEST['field']));
    }
    else
    {
        $replacements = $DB->Update($update.' SET #=CONCAT(#, ?) WHERE # LIKE ?',
                                    array($_REQUEST['append_field'],
                                          $_REQUEST['append_field'],
                                          $_REQUEST['append'],
                                          $_REQUEST['field'],
                                          "%{$_REQUEST['search']}%"));
    }

    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => "$replacements replacements have been made"));
}

function txGalleryResetClicks()
{
    global $DB, $json, $C;

    VerifyPrivileges(P_GALLERY_MODIFY, TRUE);

    $DB->Update('UPDATE `tx_galleries` SET `clicks`=0 WHERE `type`=?', array($_REQUEST['type']));

    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => "Click counts have been reset for {$_REQUEST['type']} galleries"));
}

function txGalleryDecrementCounters()
{
    global $DB, $json, $C;

    VerifyPrivileges(P_GALLERY_MODIFY, TRUE);

    $DB->Update('UPDATE `tx_galleries` SET `build_counter`=IF(`build_counter` > 0, `build_counter`-1,`build_counter`),`used_counter`=IF(`used_counter` > 0, `used_counter`-1,`used_counter`)');

    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => "Used and build counter have decremented"));
}

function txGalleryRemoveUnconfirmed()
{
    global $DB, $json, $C;

    VerifyPrivileges(P_GALLERY_MODIFY, TRUE);

    $amount = $DB->Update('DELETE FROM `tx_galleries` WHERE `status`=? AND `date_added` <= DATE_SUB(?, INTERVAL 48 HOUR)', array('unconfirmed', MYSQL_NOW));

    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => "$amount old unconfirmed galleries have been removed"));
}

function txGalleryScanForCrop()
{
    global $DB, $json, $C;

    $out = array('status' => JSON_FAILURE, 'message' => 'Gallery scan could not be completed');

    $gallery = $DB->Row('SELECT * FROM `tx_galleries` WHERE `gallery_id`=?', array($_REQUEST['gallery_id']));

    if( $gallery )
    {
        // Prepare information for the scan
        $categories =& CategoriesFromTags($gallery['categories']);
        $whitelisted = CheckWhitelist($gallery);

        $results =& ScanGallery($gallery, $categories[0], $whitelisted, TRUE);

        if( $results['success'] )
        {
            if( $results['thumbnails'] > 0 )
            {
                $DB->Update('UPDATE `tx_galleries` SET `format`=? WHERE `gallery_id`=?', array($results['format'], $gallery['gallery_id']));
                $format = GetCategoryFormat($results['format'], $categories[0]);
                $out['dimensions'] = $format['preview_size'];
                $out['thumbs'] = $results['thumbs'];
                $out['status'] = JSON_SUCCESS;
                $out['message'] = 'Gallery scan has been completed successfully';
                $out['end_url'] = $results['end_url'];
            }
            else
            {
                $out['message'] = "No thumbnails could be found on this gallery; all thumbnails must link directly to the full sized image or movie file";
            }
        }
        else
        {
            $out['message'] = "The gallery could not be accessed: {$results['errstr']}";
        }
    }
    else
    {
        $out['message'] = "The gallery with ID {$_REQUEST['gallery_id']} no longer exists in the database";
    }

    echo $json->encode($out);
}

function txCheatReportSearch()
{
    global $DB, $json, $C;

    $out =& GenericSearch('tx_reports', 'cheat-reports-tr.php', null, 'txCheatReportItem');

    echo $json->encode($out);
}

function txCheatReportItem(&$item)
{
    global $DB;

    $item['gallery'] = $DB->Row('SELECT * FROM `tx_galleries` WHERE `gallery_id`=?', array($item['gallery_id']));
}

function txCheatReportIgnore()
{
    global $DB, $json, $C;

    VerifyPrivileges(P_GALLERY, TRUE);

    $result = GetWhichReports();
    $amount = $DB->NumRows($result);
    while( $report = $DB->NextRow($result) )
    {
        $DB->Update('DELETE FROM `tx_reports` WHERE `report_id`=?', array($report['report_id']));
    }
    $DB->Free($result);

    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => "$amount cheat report" . ($amount == 1 ? ' has' : 's have') . " been ignored"));
}

function txCheatReportDelete()
{
    global $DB, $json, $C;

    VerifyPrivileges(P_GALLERY_REMOVE, TRUE);

    $result = GetWhichReports();
    $amount = $DB->NumRows($result);
    $removed = 0;
    while( $report = $DB->NextRow($result) )
    {
        $DB->Update('DELETE FROM `tx_reports` WHERE `report_id`=?', array($report['report_id']));
        $gallery = DeleteGallery($report['gallery_id']);

        if( $gallery )
        {
            $removed++;
        }
    }
    $DB->Free($result);

    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => "$amount cheat report" . ($amount == 1 ? ' has' : 's have') . " been processed; $removed galler" . ($removed == 1 ? 'y' : 'ies') . " removed"));
}

function txCheatReportBlacklist()
{
    global $DB, $json, $C;

    VerifyPrivileges(P_GALLERY_REMOVE, TRUE);

    $result = GetWhichReports();
    $amount = $DB->NumRows($result);
    $removed = 0;
    while( $report = $DB->NextRow($result) )
    {
        $DB->Update('DELETE FROM `tx_reports` WHERE `report_id`=?', array($report['report_id']));
        $gallery = DeleteGallery($report['gallery_id']);

        if( $gallery )
        {
            AutoBlacklist($gallery);
            $removed++;
        }
    }
    $DB->Free($result);

    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => "$amount cheat report" . ($amount == 1 ? ' has' : 's have') . " been processed; $removed galler" . ($removed == 1 ? 'y' : 'ies') . " blacklisted"));
}

function txDatabaseRawQuery()
{
    global $json, $DB;

    VerifyAdministrator(TRUE);
    CheckAccessList(TRUE);

    if( preg_match('~^SELECT COUNT~i', $_REQUEST['query']) )
    {
        $affected = $DB->Count($_REQUEST['query']);
    }
    else if( preg_match('~^SELECT~i', $_REQUEST['query']) )
    {
        $result = $DB->Query($_REQUEST['query']);
        $affected = $DB->NumRows($result);
        $DB->Free($result);
    }
    else
    {
        $affected = $DB->Update($_REQUEST['query']);
    }

    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => "SQL query has been executed; a total of $affected rows were affected by this query"));
}

function txPageSearchAndReplace()
{
    global $DB, $json, $C;

    VerifyAdministrator(TRUE);

    if( $_REQUEST['search'] == 'NULL' )
    {
        $replacements = $DB->Update($update.' SET #=? WHERE #=? OR # IS NULL',
                                    array($_REQUEST['field'],
                                          $_REQUEST['replace'],
                                          $_REQUEST['field'],
                                          '',
                                          $_REQUEST['field']));
    }
    else
    {
        $replacements = $DB->Update('UPDATE `tx_pages` SET #=REPLACE(#, ?, ?)', array($_REQUEST['field'], $_REQUEST['field'], $_REQUEST['search'], $_REQUEST['replace']));
    }

    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => "$replacements replacements have been made"));
}

function txPageSearch()
{
    global $DB, $json, $C;

    $GLOBALS['categories'] =& $DB->FetchAll('SELECT `category_id`,`name` FROM `tx_categories`', array(), 'category_id');
    $out =& GenericSearch('tx_pages', 'pages-tr.php', 'txPageSelect');

    echo $json->encode($out);
}

function txPageSelect(&$select)
{
    global $DB;

    if( $_REQUEST['field'] == 'tags' && $_REQUEST['search_type'] != ST_EMPTY )
    {
        $select->AddFulltextWhere('tags', $_REQUEST['search'], TRUE);
        return TRUE;
    }
    else if( $_REQUEST['field'] == 'category_id' )
    {
        if( strtolower($_REQUEST['search']) == 'mixed' )
        {
            $select->AddWhere('category_id', ST_NULL, null);
        }
        else
        {
            $csb = new SelectBuilder('*', 'tx_categories');
            $csb->AddWhere('name', $_REQUEST['search_type'], $_REQUEST['search'], TRUE);
            $categories =& $DB->FetchAll($csb->Generate(), $csb->binds, 'category_id');

            $select->AddWhere('category_id', ST_IN, join(',', array_keys($categories)));
        }

        return TRUE;
    }
    else
    {
        return FALSE;
    }
}

function txPageBuild()
{
    global $json, $DB;

    VerifyAdministrator(TRUE);

    $GLOBALS['override_page_lock'] = TRUE;

    if( $_REQUEST['new'] )
    {
        BuildNewSelected(array($_REQUEST['page_id']));
    }
    else
    {
        BuildSelected(array($_REQUEST['page_id']));
    }

    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => 'The selected TGP page has been built'));
}

function txPageDelete()
{
    global $json, $DB;

    VerifyAdministrator(TRUE);

    if( !is_array($_REQUEST['page_id']) )
    {
        $_REQUEST['page_id'] = array($_REQUEST['page_id']);
    }

    foreach($_REQUEST['page_id'] as $page_id)
    {
        $DB->Update('DELETE FROM `tx_pages` WHERE `page_id`=?', array($page_id));
        $DB->Update('DELETE FROM `tx_gallery_used` WHERE `page_id`=?', array($page_id));
    }

    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => 'The selected TGP pages have been deleted'));
}

function txScannerHistorySearch()
{
    global $DB, $json, $C;

    $_REQUEST['order'] = 'date_start';
    $_REQUEST['direction'] = 'DESC';

    $out =& GenericSearch('tx_scanner_history', 'galleries-scanner-history-tr.php', 'txScannerHistorySelect');

    echo $json->encode($out);
}

function txScannerHistorySelect(&$select)
{
    $select->AddWhere('config_id', ST_MATCHES, $_REQUEST['config_id']);
    return FALSE;
}

function txScannerHistoryClear()
{
    global $json, $DB;

    VerifyAdministrator(TRUE);

    $DB->Update('DELETE FROM `tx_scanner_history` WHERE `config_id`=?', array($_REQUEST['config_id']));

    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => 'The gallery scanner history for this configuration has been cleared'));
}

function txScannerResultsSearch()
{
    global $DB, $json, $C;

    $out =& GenericSearch('tx_scanner_results', 'galleries-scanner-results-tr.php', 'txScannerResultsSelect');

    echo $json->encode($out);
}

function txScannerResultsSelect(&$select)
{
    $select->AddWhere('config_id', ST_MATCHES, $_REQUEST['config_id']);
    return FALSE;
}

function txScannerConfigSearch()
{
    global $DB, $json, $C;

    $_REQUEST['order'] = 'identifier';
    $_REQUEST['direction'] = 'ASC';

    $out =& GenericSearch('tx_scanner_configs', 'galleries-scanner-tr.php');

    echo $json->encode($out);
}

function txScannerConfigDelete()
{
    global $json, $DB;

    VerifyAdministrator(TRUE);

    if( !is_array($_REQUEST['config_id']) )
    {
        $_REQUEST['config_id'] = array($_REQUEST['config_id']);
    }

    foreach($_REQUEST['config_id'] as $config_id)
    {
        $scanner = $DB->Row('SELECT * FROM `tx_scanner_configs` WHERE `config_id`=?', array($config_id));

        if( $scanner['current_status'] != 'Not Running' )
        {
            // Stop the scanner and wait a few seconds
            $DB->Update('UPDATE `tx_scanner_configs` SET `pid`=0 WHERE `config_id`=?', array($config_id));
        }

        $DB->Update('DELETE FROM `tx_scanner_configs` WHERE `config_id`=?', array($config_id));
    }

    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => 'The selected gallery scanner configurations have been deleted'));
}

function txScannerStart()
{
    global $DB, $json, $C;

    VerifyAdministrator(TRUE);
    CheckAccessList(TRUE);

    shell_exec("{$C['php_cli']} scanner.php " . escapeshellarg($_REQUEST['config_id']) . " >/dev/null 2>&1 &");

    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => 'Your request to start the gallery scanner has been processed'));
}

function txScannerStop()
{
    global $DB, $json, $C;

    VerifyAdministrator(TRUE);

    $DB->Update('UPDATE `tx_scanner_configs` SET `pid`=0,`current_status`=? WHERE `config_id`=?', array('Not Running', $_REQUEST['config_id']));

    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => 'Your request to stop the gallery scanner has been processed'));
}

function txScannerStatus()
{
    global $DB, $json, $C;

    VerifyAdministrator(TRUE);

    $configs = array();
    $result = $DB->Query('SELECT * FROM `tx_scanner_configs`');

    while( $config = $DB->NextRow($result) )
    {
        // Scanner most likely stopped
        if( $config['status_updated'] < time() - 600 )
        {
            $DB->Update('UPDATE `tx_scanner_configs` SET `current_status`=?,`status_updated`=?,`pid`=? WHERE `config_id`=?',
                        array('Not Running',
                              time(),
                              0,
                              $config['config_id']));

            $config['current_status'] = 'Not Running';
        }

        $config['date_last_run'] = ($config['date_last_run'] ? date(DF_SHORT, strtotime($config['date_last_run'])) : '-');
        unset($config['configuration']);
        unset($config['identifier']);
        $configs[] = $config;
    }

    $DB->Free($result);

    echo $json->encode(array('status' => JSON_SUCCESS, 'configs' => $configs));
}

function txPartnerFieldSearch()
{
    global $DB, $json, $C;

    $out =& GenericSearch('tx_partner_field_defs', 'partners-fields-tr.php');

    echo $json->encode($out);
}

function txPartnerFieldDelete()
{
    global $json, $DB;

    VerifyAdministrator(TRUE);

    if( !is_array($_REQUEST['field_id']) )
    {
        $_REQUEST['field_id'] = array($_REQUEST['field_id']);
    }

    foreach($_REQUEST['field_id'] as $field_id)
    {
        $field = $DB->Row('SELECT * FROM `tx_partner_field_defs` WHERE `field_id`=?', array($field_id));
        $DB->Update("ALTER TABLE `tx_partner_fields` DROP COLUMN #", array($field['name']));
        $DB->Update('DELETE FROM `tx_partner_field_defs` WHERE `field_id`=?', array($field_id));
    }

    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => 'The selected user defined partner fields have been deleted'));
}

function txPartnerReviewSearch()
{
    global $DB, $json, $C;

    $GLOBALS['ICON_CACHE'] =& $DB->FetchAll('SELECT * FROM `tx_icons`');
    $GLOBALS['REJECTION_CACHE'] =& $DB->FetchAll('SELECT * FROM `tx_rejections` ORDER BY `identifier`');

    $GLOBALS['categories'] =& $DB->FetchAll('SELECT * FROM `tx_categories` ORDER BY `name`');
    array_unshift($GLOBALS['categories'], array('category_id' => '__ALL__', 'name' => 'ALL CATEGORIES'));

    $out =& GenericSearch('tx_partners', 'partners-review-tr.php', 'txPartnerSelect', 'txPartnerItem');

    echo $json->encode($out);
}

function txPartnerSearch()
{
    global $DB, $json, $C;

    $out =& GenericSearch('tx_partners', 'partners-tr.php', 'txPartnerSelect', 'txPartnerItem');

    echo $json->encode($out);
}

function txPartnerSelect(&$select)
{
    global $DB;

    $select->AddWhere('status', ST_MATCHES, $_REQUEST['status'], TRUE);

    $user = $DB->GetColumns('tx_partner_fields', TRUE);

    if( !empty($user[$_REQUEST['field']]) || !empty($user[$_REQUEST['order']]) )
    {
        $select->AddJoin('tx_partners', 'tx_partner_fields', '', 'username');
    }

    return FALSE;
}

function txPartnerItem(&$item)
{
    global $DB;

    $user_fields = $DB->Row('SELECT * FROM `tx_partner_fields` WHERE `username`=?', array($item['username']));

	if( is_array($user_fields) )
	{
		$item = array_merge($item, $user_fields);
	}

    $item['tx_partners.username'] = $item['username'];
    $item['per_day'] = ($item['per_day'] == -1 ? '&#8734;' : number_format($item['per_day'], 0, $C['dec_point'], $C['thousands_sep']));
    $item['date_start'] = ($item['date_start'] ? date(DF_SHORT, strtotime($item['date_start'])) : '-');
    $item['date_end'] = ($item['date_end'] ? date(DF_SHORT, strtotime($item['date_end'])) : '-');
    $item['date_last_submit'] = ($item['date_last_submit'] ? date(DF_SHORT, strtotime($item['date_last_submit'])) : '-');
    $item['date_added'] = ($item['date_added'] ? date(DF_SHORT, strtotime($item['date_added'])) : '-');
}

function txPartnerApprove()
{
    global $DB, $json, $C;

    VerifyPrivileges(P_PARTNER_ADD, TRUE);

    $t = new Template();
    $t->assign_by_ref('config', $C);

    $result = GetWhichPartners();
    $amount = $DB->NumRows($result);
    while( $partner = $DB->NextRow($result) )
    {
        $data = $_REQUEST['partner'][$partner['username']];
        $password = RandomPassword();

        if( !in_array('__ALL__', $data['categories']) )
        {
            $data['categories'] = serialize($data['categories']);
        }
        else
        {
            $data['categories'] = null;
        }

        NullIfEmpty($data['date_start']);
        NullIfEmpty($data['date_end']);

        $DB->Update('UPDATE `tx_partners` SET ' .
                    '`name`=?, ' .
                    '`password`=?, ' .
                    '`date_start`=?, ' .
                    '`date_end`=?, ' .
                    '`per_day`=?, ' .
                    '`weight`=?, ' .
                    '`categories`=?, ' .
                    '`status`=?, ' .
                    '`allow_redirect`=?, ' .
                    '`allow_norecip`=?, ' .
                    '`allow_autoapprove`=?, ' .
                    '`allow_noconfirm`=?, ' .
                    '`allow_blacklist`=? ' .
                    'WHERE `username`=?',
                    array($data['name'],
                          sha1($password),
                          $data['date_start'],
                          $data['date_end'],
                          $data['per_day'],
                          $data['weight'],
                          $data['categories'],
                          'active',
                          intval($data['allow_redirect']),
                          intval($data['allow_norecip']),
                          intval($data['allow_autoapprove']),
                          intval($data['allow_noconfirm']),
                          intval($data['allow_blacklist']),
                          $partner['username']));

        // Update user defined fields
        UserDefinedUpdate('tx_partner_fields', 'tx_partner_field_defs', 'username', $partner['username'], $data);

        $partner = array_merge($DB->Row('SELECT * FROM `tx_partners` WHERE `username`=?', array($partner['username'])),
                               $DB->Row('SELECT * FROM `tx_partner_fields` WHERE `username`=?', array($partner['username'])));

        $partner['password'] = $password;

        // Send confirmation e-mail
        $t->assign_by_ref('partner', $partner);
        SendMail($partner['email'], 'email-partner-added.tpl', $t);
    }
    $DB->Free($result);

    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => "$amount partner" . ($amount == 1 ? ' has' : 's have') . " been approved"));
}

function txPartnerReject()
{
    global $DB, $json, $C;

    VerifyPrivileges(P_PARTNER_REMOVE, TRUE);

    $t = new Template();
    $t->assign_by_ref('config', $C);

    $result = GetWhichPartners();
    $amount = $DB->NumRows($result);
    while( $partner = $DB->NextRow($result) )
    {
        $user_fields = $DB->Row('SELECT * FROM `tx_partner_fields` WHERE `username`=?', array($partner['username']));

		if( is_array($user_fields) )
		{
			$partner = array_merge($partner, $user_fields);
		}

        DeletePartner($partner['username'], $partner);

        $data = $_REQUEST['partner'][$partner['username']];

        // Send rejection e-mail if selected
        if( is_numeric($data['rejection']) )
        {
            $rejection = $DB->Row('SELECT * FROM `tx_rejections` WHERE `email_id`=?', array($data['rejection']));

            if( $rejection )
            {
                $t->assign_by_ref('partner', $partner);
                SendMail($partner['email'], $rejection['plain'], $t, FALSE);
            }
        }
    }
    $DB->Free($result);

    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => "$amount partner" . ($amount == 1 ? ' has' : 's have') . " been rejected"));
}

function txPartnerDelete()
{
    global $DB, $json, $C;

    VerifyPrivileges(P_PARTNER_REMOVE, TRUE);

    $result = GetWhichPartners();
    $amount = $DB->NumRows($result);
    while( $partner = $DB->NextRow($result) )
    {
        DeletePartner($partner['username'], $partner);
    }
    $DB->Free($result);

    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => "$amount partner" . ($amount == 1 ? ' has' : 's have') . " been deleted"));
}

function txPartnerSuspend()
{
    global $DB, $json, $C;

    VerifyPrivileges(P_PARTNER_MODIFY, TRUE);

    $result = GetWhichPartners();
    $amount = 0;
    while( $partner = $DB->NextRow($result) )
    {
        if( $partner['status'] != 'suspended' )
        {
            $DB->Update('UPDATE `tx_partners` SET `status`=?,`session`=?,`session_start`=? WHERE `username`=?', array('suspended', null, null, $partner['username']));
            $DB->Update('UPDATE `tx_galleries` SET `previous_status`=`status`,`status`=? WHERE `status`!=? AND `partner`=?',
                        array('disabled',
                              'disabled',
                              $partner['username']));
            $amount++;
        }
    }
    $DB->Free($result);

    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => "$amount partner" . ($amount == 1 ? ' has' : 's have') . " been suspended"));
}

function txPartnerActivate()
{
    global $DB, $json, $C;

    VerifyPrivileges(P_PARTNER_MODIFY, TRUE);

    $result = GetWhichPartners();
    $amount = 0;
    while( $partner = $DB->NextRow($result) )
    {
        if( $partner['status'] == 'suspended' )
        {
            $DB->Update('UPDATE `tx_partners` SET `status`=? WHERE `username`=?', array('active', $partner['username']));
            $DB->Update('UPDATE `tx_galleries` SET `status`=`previous_status`,`previous_status`=? WHERE `status`=? AND `partner`=?',
                        array(null,
                              'disabled',
                              $partner['username']));
            $amount++;
        }
    }
    $DB->Free($result);

    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => "$amount partner" . ($amount == 1 ? ' has' : 's have') . " been reactivated"));
}

function txGalleryStats()
{
    global $DB, $json, $C;

    VerifyPrivileges(P_GALLERY, TRUE);

    $out = array('status' => JSON_SUCCESS, 'html' => '');
    $decimals = 0;
    $url = FALSE;
    $is_category = preg_match('~-category$~', $_REQUEST['type']);

    if( $is_category )
    {
        $categories =& $DB->FetchAll('SELECT * FROM `tx_categories`');
    }

    switch($_REQUEST['type'])
    {
        case 'click-category':
            foreach( $categories as $index => $category )
            {
                $categories[$index]['sum'] = $DB->Count('SELECT SUM(`clicks`) FROM `tx_galleries` WHERE MATCH(`categories`) AGAINST (? IN BOOLEAN MODE)', array($category['tag']));
            }
            break;

        case 'prod-used-category':
            foreach( $categories as $index => $category )
            {
                $categories[$index]['sum'] = $DB->Count('SELECT SUM(`clicks`/`used_counter`) FROM `tx_galleries` WHERE MATCH(`categories`) AGAINST (? IN BOOLEAN MODE)', array($category['tag']));
            }
            $decimals = 2;
            break;

        case 'prod-build-category':
            foreach( $categories as $index => $category )
            {
                $categories[$index]['sum'] = $DB->Count('SELECT SUM(`clicks`/`build_counter`) FROM `tx_galleries` WHERE MATCH(`categories`) AGAINST (? IN BOOLEAN MODE)', array($category['tag']));
            }
            $decimals = 2;
            break;

        case 'click-sponsor':
            $result = $DB->Query('SELECT SUM(`clicks`) AS `sum`,`name` AS `grouper` FROM `tx_galleries` JOIN `tx_sponsors` USING (`sponsor_id`) GROUP BY `tx_galleries`.`sponsor_id` ORDER BY `sum` DESC');
            break;

        case 'prod-used-sponsor':
            $result = $DB->Query('SELECT SUM(`clicks`/`used_counter`) AS `sum`,`name` AS `grouper` FROM `tx_galleries` JOIN `tx_sponsors` USING (`sponsor_id`) GROUP BY `tx_galleries`.`sponsor_id` ORDER BY `sum` DESC');
            $decimals = 2;
            break;

        case 'prod-build-sponsor':
            $result = $DB->Query('SELECT SUM(`clicks`/`build_counter`) AS `sum`,`name` AS `grouper` FROM `tx_galleries` JOIN `tx_sponsors` USING (`sponsor_id`) GROUP BY `tx_galleries`.`sponsor_id` ORDER BY `sum` DESC');
            $decimals = 2;
            break;

        case 'click-gallery':
            $result = $DB->Query('SELECT `clicks` AS `sum`,`gallery_url` AS `grouper` FROM `tx_galleries` ORDER BY `sum` DESC LIMIT 30');
            $url = TRUE;
            break;

        case 'prod-used-gallery':
            $result = $DB->Query('SELECT `clicks`/`used_counter` AS `sum`,`gallery_url` AS `grouper` FROM `tx_galleries` ORDER BY `sum` DESC LIMIT 30');
            $decimals = 2;
            $url = TRUE;
            break;

        case 'prod-build-gallery':
            $result = $DB->Query('SELECT `clicks`/`build_counter` AS `sum`,`gallery_url` AS `grouper` FROM `tx_galleries` ORDER BY `sum` DESC LIMIT 30');
            $decimals = 2;
            $url = TRUE;
            break;
    }


    if( $is_category )
    {
        usort($categories, 'CategoryStatCmp');

        foreach( $categories as $category )
        {
            $out['html'] .= "<tr class=\"tbody\"><td>" .
                            StringChopTooltip($category['name'], 100) .
                            "</td><td>" .
                            number_format($category['sum'], $decimals, $C['dec_point'], $C['thousands_sep']) .
                            "</td></tr>";
        }
    }
    else
    {
        while( $row = $DB->NextRow($result) )
        {
            $out['html'] .= "<tr class=\"tbody\"><td>" .
                            ($url ? "<a href=\"" . htmlspecialchars($row['grouper']) . "\" target=\"_blank\">".StringChopTooltip($row['grouper'], 100)."</a>": StringChopTooltip($row['grouper'], 100)) .
                            "</td><td>" .
                            number_format($row['sum'], $decimals, $C['dec_point'], $C['thousands_sep']) .
                            "</td></tr>";
        }
        $DB->Free($result);
    }

    echo $json->encode($out);
}

function CategoryStatCmp($a, $b)
{
    if( $a['sum'] == $b['sum'] )
    {
        return 0;
    }

    return ($a['sum'] > $b['sum']) ? -1 : 1;
}

function txGalleryBreakdown()
{
    global $DB, $json, $C;

    VerifyPrivileges(P_GALLERY, TRUE);


    $out = array('status' => JSON_SUCCESS, 'breakdown' => array());

    switch($_REQUEST['group'])
    {
        case 'category':
        {
            $categories =& $DB->FetchAll('SELECT * FROM `tx_categories` ORDER BY `name`');
            $breakdown = array();

            foreach( $categories as $category )
            {
                $s = new SelectBuilder('COUNT(*) AS `amount`', 'tx_galleries');

                if( $_REQUEST['status'] )
                {
                    $s->AddWhere('status', ST_MATCHES, $_REQUEST['status']);
                }

                if( $_REQUEST['type'] )
                {
                    $s->AddWhere('type', ST_MATCHES, $_REQUEST['type']);
                }

                $s->AddFulltextWhere('categories', $category['tag']);

                $amount = $DB->Count($s->Generate(), $s->binds);
                $breakdown[] = array('grouper' => htmlspecialchars($category['name']), 'amount' => number_format($amount, 0, $C['dec_point'], $C['thousands_sep']), 'sorter' => $amount);
            }

            usort($breakdown, 'txBreakdownCmp');
            $out['breakdown'] =& $breakdown;
        }
        break;

        case 'sponsor':
        {
            $s = new SelectBuilder("`name` AS `grouper`,COUNT(*) AS `amount`", 'tx_galleries');

            $s->AddJoin('tx_galleries', 'tx_sponsors', 'LEFT', 'sponsor_id');

            if( $_REQUEST['type'] )
            {
                $s->AddWhere('type', ST_MATCHES, $_REQUEST['type']);
            }

            if( $_REQUEST['status'] )
            {
                $s->AddWhere('status', ST_MATCHES, $_REQUEST['status']);
            }

            $s->AddGroup('tx_galleries.sponsor_id');
            $s->AddOrder('amount', 'DESC');

            $result = $DB->Query($s->Generate(), $s->binds);
            while( $breakdown = $DB->NextRow($result) )
            {
                $breakdown['amount'] = number_format($breakdown['amount'], 0, $C['dec_point'], $C['thousands_sep']);
                $breakdown['grouper'] = $breakdown['grouper'] ? ucfirst(htmlspecialchars($breakdown['grouper'])) : '-';
                $out['breakdown'][] = $breakdown;
            }
            $DB->Free($result);
        }
        break;

        default:
        {
            $group_field = array('added' => 'DATE_FORMAT(date_added, \'%Y-%m-%d\')', 'displayed' => 'DATE_FORMAT(date_displayed, \'%Y-%m-%d\')', 'format' => 'format');

            $s = new SelectBuilder("{$group_field[$_REQUEST['group']]} AS `grouper`,COUNT(*) AS `amount`", 'tx_galleries');

            if( $_REQUEST['type'] )
            {
                $s->AddWhere('type', ST_MATCHES, $_REQUEST['type']);
            }

            if( $_REQUEST['status'] )
            {
                $s->AddWhere('status', ST_MATCHES, $_REQUEST['status']);
            }

            $result = $DB->Query($s->Generate() . " GROUP BY {$group_field[$_REQUEST['group']]} ORDER BY " . (in_array($_REQUEST['group'], array('added','displayed')) ? '`grouper`' : '`amount`') . " DESC", $s->binds);
            while( $breakdown = $DB->NextRow($result) )
            {
                $breakdown['amount'] = number_format($breakdown['amount'], 0, $C['dec_point'], $C['thousands_sep']);
                $breakdown['grouper'] = $breakdown['grouper'] ? ucfirst(htmlspecialchars($breakdown['grouper'])) : '-';
                $out['breakdown'][] = $breakdown;
            }
            $DB->Free($result);
        }
        break;
    }

    $type = $_REQUEST['type'] ? ucfirst(htmlspecialchars($_REQUEST['type'])) : 'Overall';
    $status = $_REQUEST['status'] ? ucfirst(htmlspecialchars($_REQUEST['status'])) : '';
    $by = ucfirst(htmlspecialchars($_REQUEST['group']));

    $out['type'] = "$type $status Galleries By $by";

    echo $json->encode($out);
}

function txBreakdownCmp($a, $b)
{
    if( $a['sorter'] == $b['sorter'] )
    {
        if( $a['grouper'] == $b['grouper'] )
        {
            return 0;
        }

        return ($a['grouper'] < $b['grouper']) ? -1 : 1;
    }

    return ($a['sorter'] < $b['sorter']) ? 1 : -1;
}

function txGallerySearch()
{
    global $DB, $json, $C;

    VerifyPrivileges(P_GALLERY, TRUE);

    $out =& GenericSearch('tx_galleries', 'galleries-search-tr.php', 'GallerySearchSelect', 'GalleryItemCallback', '`tx_galleries`.`gallery_id` AS `gallery_id`');

    if( extension_loaded('zlib') && !ini_get('zlib.output_compression') )
    {
        header('Content-Encoding: gzip');
        echo gzencode($json->encode($out), 9);
    }
    else
    {
        echo $json->encode($out);
    }
}

function GalleryItemCallback(&$item)
{
    global $C, $DB;

    if( !empty($item['sponsor_id']) )
        $item['sponsor'] = $DB->Count('SELECT `name` FROM `tx_sponsors` WHERE `sponsor_id`=?', array($item['sponsor_id']));

    if( $item['has_preview'] )
    {
        $item['previews'] =& $DB->FetchAll('SELECT * FROM `tx_gallery_previews` WHERE `gallery_id`=? ORDER BY IF(dimensions=?, 0, 1)', array($item['gallery_id'], $_REQUEST['search']));

        foreach( $item['previews'] as $index => $preview )
        {
            list($width, $height) = explode('x', $preview['dimensions']);

            if( $width > 200 || $height > 200 )
            {
                $item['previews'][$index]['attrs'] = $width > $height ? ' width="200"' : ' height="200"';
            }
        }
    }

    $item['tx_galleries.gallery_id'] = $item['gallery_id'];
    $item['type'] = ucfirst($item['type']);
    $item['format'] = ucfirst($item['format']);
    $item['status'] = ucfirst($item['status']);
    $item['clicks'] = number_format($item['clicks'], 0, $C['dec_point'], $C['thousands_sep']);
    $item['weight'] = number_format($item['weight'], 0, $C['dec_point'], $C['thousands_sep']);
    $item['date_scanned'] = ($item['date_scanned'] ? date(DF_SHORT, strtotime($item['date_scanned'])) : '-');
    $item['date_added'] = ($item['date_added'] ? date(DF_SHORT, strtotime($item['date_added'])) : '-');
    $item['date_approved'] = ($item['date_approved'] ? date(DF_SHORT, strtotime($item['date_approved'])) : '-');
    $item['date_scheduled'] = ($item['date_scheduled'] ? date(DF_SHORT, strtotime($item['date_scheduled'])) : '-');
    $item['date_displayed'] = ($item['date_displayed'] ? date(DF_SHORT, strtotime($item['date_displayed'])) : '-');
    $item['date_deletion'] = ($item['date_deletion'] ? date(DF_SHORT, strtotime($item['date_deletion'])) : '-');
    $item['times_selected'] = number_format($item['times_selected'], 0, $C['dec_point'], $C['thousands_sep']);
    $item['used_counter'] = number_format($item['used_counter'], 0, $C['dec_point'], $C['thousands_sep']);
    $item['build_counter'] = number_format($item['build_counter'], 0, $C['dec_point'], $C['thousands_sep']);
    $item['categories'] =& CategoriesFromTags($item['categories']);

    $categories = array();
    $category_ids = array();
    foreach($item['categories'] as $category)
    {
        $categories[] = $category['name'];
        $category_ids[] = $category['category_id'];
    }

    $item['categories'] = join(', ', $categories);
    $item['category_ids'] = join(', ', $category_ids);

    $item['icons'] =& $DB->FetchAll('SELECT * FROM `tx_gallery_icons` WHERE `gallery_id`=?', array($item['gallery_id']));

    $icons = array();
    foreach( $item['icons'] as $icon )
    {
        $icons[] = $icon['icon_id'];
    }

    $item['icons'] = join(', ', $icons);
}

function txGalleryIPE()
{
    global $DB, $C;

    VerifyPrivileges(P_GALLERY_MODIFY, TRUE);

    // Format output
    $update = TRUE;
    $output = $_REQUEST['value'];
    switch($_REQUEST['update'])
    {
        case 'date_scheduled':
        case 'date_deletion':
        case 'date_displayed':
            NullIfEmpty($_REQUEST['value']);
            $update = preg_match(RE_DATETIME, $_REQUEST['value']) || empty($_REQUEST['value']);
            $output = empty($_REQUEST['value']) ? '-' : date(DF_SHORT, strtotime($_REQUEST['value']));
            break;

        case 'status':
            $output = ucfirst($_REQUEST['value']);

            if( $_REQUEST['value'] == 'approved' )
            {
                $_REQUEST['update'] = array('status', 'date_approved');
                $_REQUEST['value'] = array($_REQUEST['value'], MYSQL_NOW);
            }
            break;

        case 'type':
        case 'format':
            $output = ucfirst($_REQUEST['value']);
            break;

        case 'weight':
        case 'clicks':
        case 'thumbnails':
            $update = is_numeric($_REQUEST['value']);
            $output = number_format($_REQUEST['value'], 0, $C['dec_point'], $C['thousands_sep']);
            break;

        case 'description':
        case 'keywords':
        case 'tags':
            $output = StringChopTooltip(htmlspecialchars($_REQUEST['value']), 90);
            break;

        case 'gallery_url':
            $output = StringChopTooltip(htmlspecialchars($_REQUEST['value']), 100, true);
            break;

        case 'nickname':
        case 'email':
            $output = StringChopTooltip(htmlspecialchars($_REQUEST['value']), 40);
            break;

        case 'sponsor_id':
            NullIfEmpty($_REQUEST['value']);

            if( $_REQUEST['value'] == null )
                $output = '';
            else
                $output = $DB->Count('SELECT `name` FROM `tx_sponsors` WHERE `sponsor_id`=?', array($_REQUEST['value']));

            break;

        case 'categories':
            $_REQUEST['value'] = CategoryTagsFromIds(explode(',', $_REQUEST['value']));
            $categories =& CategoriesFromTags($_REQUEST['value']);
            $names = array();

            foreach( $categories as $category )
            {
                $names[] = $category['name'];
            }

            $output = StringChopTooltip(htmlspecialchars(join(', ', $names)), 90);
            break;

        case 'icons':
        {
            if( isset($_REQUEST['multi']) )
            {
                $result = GetWhichGalleries();
                while( $gallery = $DB->NextRow($result) )
                {
                    $DB->Update('DELETE FROM `tx_gallery_icons` WHERE `gallery_id`=?', array($gallery['gallery_id']));

                    foreach( explode(',', $_REQUEST['value']) as $icon_id )
                    {
                        $icon_id = trim($icon_id);

                        if( is_numeric($icon_id) )
                        {
                            $DB->Update('INSERT INTO `tx_gallery_icons` VALUES (?,?)', array($gallery['gallery_id'], $icon_id));
                        }
                    }
                }
                $DB->Free($result);
            }
            else
            {
                $DB->Update('DELETE FROM `tx_gallery_icons` WHERE `gallery_id`=?', array($_REQUEST['gallery_id']));

                foreach( explode(',', $_REQUEST['value']) as $icon_id )
                {
                    $icon_id = trim($icon_id);

                    if( is_numeric($icon_id) )
                    {
                        $DB->Update('INSERT INTO `tx_gallery_icons` VALUES (?,?)', array($_REQUEST['gallery_id'], $icon_id));
                    }
                }
            }

            echo '<img src="images/icons.png" alt="Icons" title="Icons" class="click-image function">';
            return;
        }
        break;
    }

    if( $update )
    {
        $update = new UpdateBuilder('tx_galleries');

        if( is_array($_REQUEST['update']) )
        {
            foreach( $_REQUEST['update'] as $index => $field )
            {
                $update->AddSet($_REQUEST['update'][$index], $_REQUEST['value'][$index]);
            }
        }
        else
        {
            $update->AddSet($_REQUEST['update'], $_REQUEST['value']);
        }

        if( isset($_REQUEST['multi']) )
        {
            $update = GetWhichGalleries($update);
        }
        else
        {
            $update->AddWhere('gallery_id', ST_MATCHES, $_REQUEST['gallery_id']);
        }

        $DB->Update($update->Generate(), $update->binds);
    }

    echo $update ? $output : JSON_FAILURE;
}

function txGalleryApprove()
{
    global $DB, $json, $C;
    VerifyPrivileges(P_GALLERY_MODIFY, TRUE);

    $t = new Template();
    $t->assign_by_ref('config', $C);

    $result = GetWhichGalleries();
    $amount = 0;
    while( $gallery = $DB->NextRow($result) )
    {
        if( $gallery['status'] == 'pending' || $gallery['status'] == 'unconfirmed' )
        {
            $gallery['status'] = 'approved';
            $gallery['date_approved'] = MYSQL_NOW;
            $gallery['administrator'] = $_SERVER['REMOTE_USER'];

            // Mark the gallery as approved
            if( $_REQUEST['framed'] )
            {
                $gallery = array_merge($gallery, $_REQUEST);
                $gallery['categories'] = CategoryTagsFromIds($gallery['categories']);

                if( !preg_match(RE_DATETIME, $gallery['date_scheduled']) )
                    $gallery['date_scheduled'] = '';

                if( !preg_match(RE_DATETIME, $gallery['date_deletion']) )
                    $gallery['date_deletion'] = '';

                NullIfEmpty($gallery['date_scheduled']);
                NullIfEmpty($gallery['date_deletion']);

                $DB->Update('UPDATE `tx_galleries` SET ' .
                            '`gallery_url`=?, ' .
                            '`description`=?, ' .
                            '`keywords`=?, ' .
                            '`thumbnails`=?, ' .
                            '`nickname`=?, ' .
                            '`weight`=?, ' .
                            '`sponsor_id`=?, ' .
                            '`type`=?, ' .
                            '`format`=?, ' .
                            '`status`=?, ' .
                            '`date_approved`=?, ' .
                            '`date_scheduled`=?, ' .
                            '`date_deletion`=?, ' .
                            '`administrator`=?, ' .
                            '`allow_scan`=?, ' .
                            '`allow_preview`=?, ' .
                            '`tags`=?, ' .
                            '`categories`=? ' .
                            'WHERE `gallery_id`=?',
                            array($gallery['gallery_url'],
                                  $gallery['description'],
                                  $gallery['keywords'],
                                  $gallery['thumbnails'],
                                  $gallery['nickname'],
                                  $gallery['weight'],
                                  $gallery['sponsor_id'],
                                  $gallery['type'],
                                  $gallery['format'],
                                  $gallery['status'],
                                  $gallery['date_approved'],
                                  $gallery['date_scheduled'],
                                  $gallery['date_deletion'],
                                  $gallery['administrator'],
                                  intval($gallery['allow_scan']),
                                  intval($gallery['allow_preview']),
                                  $gallery['tags'],
                                  $gallery['categories'],
                                  $gallery['gallery_id']));

                // Update user defined fields
                UserDefinedUpdate('tx_gallery_fields', 'tx_gallery_field_defs', 'gallery_id', $gallery['gallery_id'], $gallery);

                // Update icons
                $DB->Update('DELETE FROM `tx_gallery_icons` WHERE `gallery_id`=?', array($gallery['gallery_id']));
                if( is_array($_REQUEST['icons']) )
                {
                    foreach( $_REQUEST['icons'] as $icon )
                    {
                        $DB->Update('INSERT INTO `tx_gallery_icons` VALUES (?,?)', array($gallery['gallery_id'], $icon));
                    }
                }
            }
            else
            {
                $DB->Update('UPDATE `tx_galleries` SET `status`=?,`date_approved`=?,`administrator`=? WHERE `gallery_id`=?',
                            array($gallery['status'],
                                  $gallery['date_approved'],
                                  $gallery['administrator'],
                                  $gallery['gallery_id']));
            }

            // Send approval e-mail if option is enabled
            if( $C['email_on_approval'] && $gallery['email'] != $C['from_email'] )
            {
                $t->assign_by_ref('gallery', $gallery);
                SendMail($gallery['email'], 'email-gallery-approved.tpl', $t);
            }

            $amount++;
        }
    }
    $DB->Free($result);

    // Update administrator count of galleries approved
    $DB->Update('UPDATE `tx_administrators` SET `approved`=`approved`+? WHERE `username`=?', array($amount, $_SERVER['REMOTE_USER']));

    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => "$amount galler" . ($amount == 1 ? 'y has' : 'ies have') . " been approved"));
}

function txGalleryReject()
{
    global $DB, $json, $C;

    VerifyPrivileges(P_GALLERY_MODIFY, TRUE);

    $reject_cache = array();
    $t = new Template();
    $t->assign_by_ref('config', $C);

    $result = GetWhichGalleries();
    $amount = 0;
    while( $gallery = $DB->NextRow($result) )
    {
        if( $gallery['status'] == 'pending' || $gallery['status'] == 'unconfirmed' )
        {
            DeleteGallery($gallery['gallery_id'], $gallery);

            // Send rejection e-mail if selected
            if( is_numeric($_REQUEST['multi_email']) )
            {
                if( !isset($reject_cache[$_REQUEST['multi_email']]) )
                {
                    $rejection = $DB->Row('SELECT * FROM `tx_rejections` WHERE `email_id`=?', array($_REQUEST['multi_email']));
                    $reject_cache[$_REQUEST['multi_email']] = $rejection;
                }

                $t->assign_by_ref('gallery', $gallery);
                SendMail($gallery['email'], $reject_cache[$_REQUEST['multi_email']]['plain'], $t, FALSE);
            }

            $amount++;
        }
    }
    $DB->Free($result);

    // Update administrator count of galleries rejected
    $DB->Update('UPDATE `tx_administrators` SET `rejected`=`rejected`+? WHERE `username`=?', array($amount, $_SERVER['REMOTE_USER']));

    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => "$amount galler" . ($amount == 1 ? 'y has' : 'ies have') . " been rejected"));
}

function txGalleryDelete()
{
    global $DB, $json, $C;

    VerifyPrivileges(P_GALLERY_REMOVE, TRUE);

    $result = GetWhichGalleries();
    $amount = $DB->NumRows($result);
    while( $gallery = $DB->NextRow($result) )
    {
        DeleteGallery($gallery['gallery_id'], $gallery);
    }
    $DB->Free($result);

    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => "$amount galler" . ($amount == 1 ? 'y has' : 'ies have') . " been deleted"));
}

function txGalleryBlacklistAuto()
{
    global $DB, $json, $C;

    VerifyPrivileges(P_GALLERY_REMOVE, TRUE);

    $result = GetWhichGalleries();
    $amount = $DB->NumRows($result);
    while( $gallery = $DB->NextRow($result) )
    {
        AutoBlacklist($gallery, $_REQUEST['ban_reason']);
        DeleteGallery($gallery['gallery_id'], $gallery);
    }
    $DB->Free($result);

    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => "$amount galler" . ($amount == 1 ? 'y has' : 'ies have') . " been blacklisted and deleted"));
}

function txGalleryBlacklist()
{
    global $DB, $json, $C;

    VerifyPrivileges(P_GALLERY_REMOVE, TRUE);

    $result = GetWhichGalleries();
    $amount = $DB->NumRows($result);
    while( $gallery = $DB->NextRow($result) )
    {
        DeleteGallery($gallery['gallery_id'], $gallery);
    }
    $DB->Free($result);

    $values = array('bl_domainip' => 'domain_ip',
                    'bl_submitip' => 'submit_ip',
                    'bl_dns' => 'dns',
                    'bl_url' => 'url',
                    'bl_email' => 'email');

    foreach( $values as $field => $type )
    {
        if( IsEmptyString($_REQUEST[$field]) )
            continue;

        if( $DB->Count('SELECT COUNT(*) FROM `tx_blacklist` WHERE `type`=? AND `value`=?', array($type, $_REQUEST[$field])) < 1 )
        {
            $DB->Update('INSERT INTO `tx_blacklist` VALUES (?,?,?,?,?)', array(null, $type, 0, $_REQUEST[$field], $_REQUEST['bl_reason']));
        }
    }

    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => "$amount galler" . ($amount == 1 ? 'y has' : 'ies have') . " been blacklisted and deleted"));
}

function txGalleryDisable()
{
    global $DB, $json, $C;

    VerifyPrivileges(P_GALLERY_MODIFY, TRUE);

    $result = GetWhichGalleries();
    $amount = 0;
    while( $gallery = $DB->NextRow($result) )
    {
        if( $gallery['status'] != 'disabled' )
        {
            $DB->Update('UPDATE `tx_galleries` SET `status`=?,`previous_status`=? WHERE `gallery_id`=?', array('disabled', $gallery['status'], $gallery['gallery_id']));
            $amount++;
        }
    }
    $DB->Free($result);

    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => "$amount galler" . ($amount == 1 ? 'y has' : 'ies have') . " been disabled"));
}

function txGalleryEnable()
{
    global $DB, $json, $C;

    VerifyPrivileges(P_GALLERY_MODIFY, TRUE);

    $result = GetWhichGalleries();
    $amount = 0;
    while( $gallery = $DB->NextRow($result) )
    {
        if( $gallery['status'] == 'disabled' )
        {
            $DB->Update('UPDATE `tx_galleries` SET `status`=?,`previous_status`=? WHERE `gallery_id`=?', array($gallery['previous_status'], null, $gallery['gallery_id']));
            $amount++;
        }
    }
    $DB->Free($result);

    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => "$amount galler" . ($amount == 1 ? 'y has' : 'ies have') . " been enabled"));
}

function txIconSearch()
{
    global $DB, $json, $C;

    $out =& GenericSearch('tx_icons', 'icons-tr.php');

    echo $json->encode($out);
}

function txIconDelete()
{
    global $DB, $json, $C;

    VerifyAdministrator(TRUE);

    if( !is_array($_REQUEST['icon_id']) )
    {
        $_REQUEST['icon_id'] = array($_REQUEST['icon_id']);
    }

    foreach($_REQUEST['icon_id'] as $icon_id)
    {
        $DB->Update('DELETE FROM `tx_icons` WHERE `icon_id`=?', array($icon_id));
        $DB->Update('DELETE FROM `tx_gallery_icons` WHERE `icon_id`=?', array($icon_id));
        $DB->Update('DELETE FROM `tx_partner_icons` WHERE `icon_id`=?', array($icon_id));
    }

    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => 'The selected icons have been deleted'));
}

function txPartnerSearchQuick()
{
    global $json, $DB, $C;

    $out = array('status' => JSON_SUCCESS, 'html' => '', 'matches' => 0);

    $result = $DB->Query('SELECT `username`,`email` FROM `tx_partners` WHERE (`username` LIKE ? OR `email` LIKE ?) ORDER BY `username`',
                         array("%{$_REQUEST['s']}%",
                               "%{$_REQUEST['s']}%"));

    $out['matches'] = $DB->NumRows($result);

    while( $partner = $DB->NextRow($result) )
    {
        ArrayHSC($partner);
        $out['html'] .= "<option value=\"{$partner['username']}\">{$partner['username']} ({$partner['email']})</option>\n";
    }
    $DB->Free($result);

    echo $json->encode($out);
}

function txSponsorSearch()
{
    global $DB, $json, $C;

    $out =& GenericSearch('tx_sponsors', 'sponsors-tr.php');

    echo $json->encode($out);
}

function txSponsorDelete()
{
    global $DB, $json, $C;

    VerifyAdministrator(TRUE);

    if( !is_array($_REQUEST['sponsor_id']) )
    {
        $_REQUEST['sponsor_id'] = array($_REQUEST['sponsor_id']);
    }

    foreach($_REQUEST['sponsor_id'] as $sponsor_id)
    {
        $DB->Update('DELETE FROM `tx_sponsors` WHERE `sponsor_id`=?', array($sponsor_id));

        // Remove galleries from this sponsor
        $result = $DB->Query('SELECT * FROM `tx_galleries` WHERE `sponsor_id`=?', array($sponsor_id));
        while( $gallery = $DB->NextRow($result) )
        {
            DeleteGallery($gallery['gallery_id'], $gallery);
        }
        $DB->Free($result);
    }

    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => 'The selected sponsors have been deleted'));
}

function txAnnotationSearch()
{
    global $DB, $json, $C;

    $out =& GenericSearch('tx_annotations', 'annotations-tr.php');

    echo $json->encode($out);
}

function txAnnotationDelete()
{
    global $DB, $json, $C;

    VerifyAdministrator(TRUE);

    if( !is_array($_REQUEST['annotation_id']) )
    {
        $_REQUEST['annotation_id'] = array($_REQUEST['annotation_id']);
    }

    foreach($_REQUEST['annotation_id'] as $annotation_id)
    {
        $DB->Update('DELETE FROM `tx_annotations` WHERE `annotation_id`=?', array($annotation_id));
        $DB->Update('UPDATE `tx_categories` SET `pics_annotation`=NULL WHERE `pics_annotation`=?', array($annotation_id));
        $DB->Update('UPDATE `tx_categories` SET `movies_annotation`=NULL WHERE `movies_annotation`=?', array($annotation_id));
    }

    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => 'The selected annotations have been deleted'));
}

function txCategorySearch()
{
    global $DB, $json, $C;

    $out =& GenericSearch('tx_categories', 'categories-tr.php', null, 'CategoryItemCallback');

    echo $json->encode($out);
}

function CategoryItemCallback(&$item)
{
    $item['date_last_submit'] = ($item['date_last_submit'] ? date(DF_SHORT, strtotime($item['date_last_submit'])) : '-');
}

function txCategoryDelete()
{
    global $DB, $json, $C;

    VerifyPrivileges(P_CATEGORY_REMOVE, TRUE);

    if( !is_array($_REQUEST['category_id']) )
    {
        $_REQUEST['category_id'] = array($_REQUEST['category_id']);
    }

    foreach($_REQUEST['category_id'] as $category_id)
    {
        $category = $DB->Row('SELECT * FROM `tx_categories` WHERE `category_id`=?', array($category_id));

        // Find all of the galleries in this category
        $result = $DB->Query('SELECT * FROM `tx_galleries` WHERE MATCH(`categories`) AGAINST (? IN BOOLEAN MODE)', array($category['tag']));
        while( $gallery = $DB->NextRow($result) )
        {
            $gallery['categories'] = RemoveCategoryTag($category['tag'], $gallery['categories']);

            // Delete the gallery if it is not also located in another category
            if( $gallery['categories'] == MIXED_CATEGORY )
            {
                DeleteGallery($gallery['gallery_id'], $gallery);
            }
            // Otherwise, simply remove the category affiliation
            else
            {
                $DB->Update('UPDATE `tx_galleries` SET `categories`=? WHERE `gallery_id`=?', array($gallery['categories'], $gallery['gallery_id']));
            }
        }
        $DB->Free($result);


        // Remove the category
        $DB->Update('DELETE FROM `tx_categories` WHERE `category_id`=?', array($category_id));


        // Remove pages associated with this category
        $DB->Update('DELETE FROM `tx_pages` WHERE `category_id`=?', array($category['category_id']));
    }

    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => 'The selected categories have been deleted'));
}

function txGalleryFieldSearch()
{
    global $DB, $json, $C;

    $out =& GenericSearch('tx_gallery_field_defs', 'gallery-fields-tr.php');

    echo $json->encode($out);
}

function txGalleryFieldDelete()
{
    global $json, $DB;

    VerifyAdministrator(TRUE);

    if( !is_array($_REQUEST['field_id']) )
    {
        $_REQUEST['field_id'] = array($_REQUEST['field_id']);
    }

    foreach($_REQUEST['field_id'] as $field_id)
    {
        $field = $DB->Row('SELECT * FROM `tx_gallery_field_defs` WHERE `field_id`=?', array($field_id));
        $DB->Update("ALTER TABLE `tx_gallery_fields` DROP COLUMN #", array($field['name']));
        $DB->Update('DELETE FROM `tx_gallery_field_defs` WHERE `field_id`=?', array($field_id));
    }

    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => 'The selected user defined gallery fields have been deleted'));
}

function txRejectionTemplateDelete()
{
    global $json, $DB;

    VerifyAdministrator(TRUE);

    if( !is_array($_REQUEST['email_id']) )
    {
        $_REQUEST['email_id'] = array($_REQUEST['email_id']);
    }

    foreach($_REQUEST['email_id'] as $email_id)
    {
        $DB->Update('DELETE FROM `tx_rejections` WHERE `email_id`=?', array($email_id));
    }

    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => 'The selected rejection e-mails have been deleted'));
}

function txRejectionTemplateSearch()
{
    global $DB, $json, $C;

    $out =& GenericSearch('tx_rejections', 'rejections-tr.php', null, 'txRejectionTemplateItem');

    echo $json->encode($out);
}

function txRejectionTemplateItem(&$item)
{
    $item['message'] = array();
    IniParse(html_entity_decode($item['plain']), FALSE, $item['message']);
}

function tx2257Search()
{
    global $DB, $json, $C;

    $out =& GenericSearch('tx_2257', '2257-tr.php');

    echo $json->encode($out);
}

function tx2257Delete()
{
    global $json, $DB;

    VerifyAdministrator(TRUE);

    if( !is_array($_REQUEST['code_id']) )
    {
        $_REQUEST['code_id'] = array($_REQUEST['code_id']);
    }

    foreach($_REQUEST['code_id'] as $code_id)
    {
        $DB->Update('DELETE FROM `tx_2257` WHERE `code_id`=?', array($code_id));
    }

    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => 'The selected 2257 codes have been deleted'));
}

function txReciprocalSearch()
{
    global $DB, $json, $C;

    $out =& GenericSearch('tx_reciprocals', 'reciprocals-tr.php');

    echo $json->encode($out);
}

function txReciprocalDelete()
{
    global $json, $DB;

    VerifyAdministrator(TRUE);

    if( !is_array($_REQUEST['recip_id']) )
    {
        $_REQUEST['recip_id'] = array($_REQUEST['recip_id']);
    }

    foreach($_REQUEST['recip_id'] as $recip_id)
    {
        $DB->Update('DELETE FROM `tx_reciprocals` WHERE `recip_id`=?', array($recip_id));
    }

    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => 'The selected reciprocal links have been deleted'));
}

function txRegexTest()
{
    global $json;

    $out = array('status' => JSON_SUCCESS, 'matches' => 'No', 'matched' => '');

    if( preg_match("~({$_REQUEST['regex']})~i", $_REQUEST['string'], $matches) )
    {
        $out['matches'] = 'Yes';
        $out['matched'] = $matches[0];
    }

    ArrayHSC($out);

    echo $json->encode($out);
}

function txWhitelistSearch()
{
    global $DB, $json, $C, $WLIST_TYPES;

    // Yes, the call to txBlacklistSelect is correct since creating
    // a txWhitelistSelect function would be identical
    $out =& GenericSearch('tx_whitelist', 'whitelist-tr.php', 'txBlacklistSelect');

    echo $json->encode($out);
}

function txWhitelistDelete()
{
    global $json, $DB;

    VerifyAdministrator(TRUE);

    if( !is_array($_REQUEST['whitelist_id']) )
    {
        $_REQUEST['whitelist_id'] = array($_REQUEST['whitelist_id']);
    }

    foreach($_REQUEST['whitelist_id'] as $whitelist_id)
    {
        $DB->Update('DELETE FROM `tx_whitelist` WHERE `whitelist_id`=?', array($whitelist_id));
    }

    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => 'The selected whitelist items have been deleted'));
}

function txBlacklistSearch()
{
    global $DB, $json, $C;

    $out =& GenericSearch('tx_blacklist', 'blacklist-tr.php', 'txBlacklistSelect');

    echo $json->encode($out);
}

function txBlacklistSelect(&$select)
{
    $select->AddWhere('type', ST_MATCHES, $_REQUEST['type'], TRUE);
    return FALSE;
}

function txBlacklistDelete()
{
    global $json, $DB;

    VerifyAdministrator(TRUE);

    if( !is_array($_REQUEST['blacklist_id']) )
    {
        $_REQUEST['blacklist_id'] = array($_REQUEST['blacklist_id']);
    }

    foreach($_REQUEST['blacklist_id'] as $blacklist_id)
    {
        $DB->Update('DELETE FROM `tx_blacklist` WHERE `blacklist_id`=?', array($blacklist_id));
    }

    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => 'The selected blacklist items have been deleted'));
}

function txAdministratorSearch()
{
    global $DB, $json, $C;

    $out =& GenericSearch('tx_administrators', 'administrators-tr.php');

    echo $json->encode($out);
}

function txAdministratorDelete()
{
    global $json, $DB;

    VerifyAdministrator(TRUE);

    if( !is_array($_REQUEST['username']) )
    {
        $_REQUEST['username'] = array($_REQUEST['username']);
    }

    // No deleting your own account
    if( in_array($_SERVER['REMOTE_USER'], $_REQUEST['username']) )
    {
        echo $json->encode(array('status' => JSON_FAILURE, 'message' => 'You cannot delete your own account'));
        exit;
    }

    foreach($_REQUEST['username'] as $username)
    {
        $DB->Update('DELETE FROM `tx_administrators` WHERE `username`=?', array($username));
    }

    echo $json->encode(array('status' => JSON_SUCCESS, 'message' => 'The selected administrator accounts have been deleted'));
}

function &GenericSearch($table, $files, $select_callback = null, $item_callback = null, $fields = null)
{
    global $C, $DB, $BLIST_TYPES, $WLIST_TYPES, $ANN_LOCATIONS;

    $out = array('status' => JSON_SUCCESS, 'html' => '', 'pagination' => $GLOBALS['DEFAULT_PAGINATION'], 'pagelinks' => '');

    $per_page = isset($_REQUEST['per_page']) && $_REQUEST['per_page'] > 0 ? $_REQUEST['per_page'] : 20;
    $page = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1;
    $select = new SelectBuilder('*' .(empty($fields) ? '' : ', ' . $fields), $table);
    $override = FALSE;

    if( function_exists($select_callback) )
    {
        $override = $select_callback($select);
    }

    if( !$override )
    {
        $select->AddWhere($_REQUEST['field'], $_REQUEST['search_type'], $_REQUEST['search'], $_REQUEST['search_type'] != ST_EMPTY);
    }

    $select->AddOrder($_REQUEST['order'], $_REQUEST['direction']);

    if( !empty($_REQUEST['order_next']) )
    {
        $select->AddOrder($_REQUEST['order_next'], $_REQUEST['direction_next']);
    }

    $result = $DB->QueryWithPagination($select->Generate(), $select->binds, $page, $per_page);

    $out['pagination'] = $result;
    $out['pagelinks'] = PageLinks($result);

    if( $result['result'] )
    {
        if( !is_array($files) )
        {
            $files = array($files);
        }

        $row_html = '';
        foreach( $files as $file )
        {
            $row_html .= file_get_contents("includes/$file");
        }

        while( $item = $DB->NextRow($result['result']) )
        {
            ArrayHSC($item);

            if( function_exists($item_callback) )
            {
                $item_callback($item);
            }

            ob_start();
            eval('?>' . $row_html);
            $out['html'] .= ob_get_contents();
            ob_end_clean();
        }

        $DB->Free($result['result']);
    }

    return $out;
}

function AjaxError($code, $string, $file, $line)
{
    global $DB, $json;

    $reporting = error_reporting();

    if( $reporting == 0 || !($code & $reporting) )
    {
        return;
    }

    $error = array();

    $error['message'] = "$string on line $line of " . basename($file);
    $error['status'] = JSON_FAILURE;

    if( @get_class($DB) == 'DB' && isset($GLOBALS['build_history_id']) )
    {
        $DB->Update('UPDATE `tx_build_history` SET `error_message`=? WHERE `history_id`=?', array($error['message'], $GLOBALS['build_history_id']));
    }

    echo $json->encode($error);

    exit;
}

?>