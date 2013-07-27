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

if( is_dir('../utilities') )
{
    echo "For security purposes, please remove the utilities directory of your TGPX installation";
    exit;
}


define('TGPX', TRUE);

require_once('../includes/common.php');
require_once("{$GLOBALS['BASE_DIR']}/includes/validator.class.php");
require_once("{$GLOBALS['BASE_DIR']}/includes/mysql.class.php");
require_once("{$GLOBALS['BASE_DIR']}/includes/http.class.php");
require_once("{$GLOBALS['BASE_DIR']}/includes/template.class.php");
require_once("{$GLOBALS['BASE_DIR']}/includes/compiler.class.php");
require_once('includes/functions.php');

SetupRequest();

$DB = new DB($C['db_hostname'], $C['db_username'], $C['db_password'], $C['db_name']);
$DB->Connect();

if( ($error = ValidLogin()) === TRUE )
{
    if( isset($_REQUEST['ref_url']) )
    {
        header("Location: http://{$_SERVER['HTTP_HOST']}{$_REQUEST['ref_url']}");
        exit;
    }

    if( !isset($_REQUEST['r']) )
    {
        ScheduledCleanup();
        include_once('includes/main.php');
    }
    else
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
}
else
{
    if( isset($_REQUEST['ref_url']) )
    {
        $_SERVER['QUERY_STRING'] = TRUE;
        $_SERVER['REQUEST_URI'] = $_REQUEST['ref_url'];
    }

    include_once('includes/login.php');
}

$DB->Disconnect();

function txShAds()
{
    global $DB, $C;

    VerifyAdministrator();

    include_once('includes/ads.php');
}

function txShAdAdd()
{
    global $DB, $C;

    VerifyAdministrator();
    ArrayHSC($_REQUEST);

    include_once('includes/ads-add.php');
}

function txShAdEdit()
{
    global $DB, $C;

    VerifyAdministrator();

    $editing = TRUE;

    // First time, use database information
    if( !$_REQUEST['editing'] || $GLOBALS['added'] )
    {
        $_REQUEST = $DB->Row('SELECT * FROM `tx_ads` WHERE `ad_id`=?', array($_REQUEST['ad_id']));

        // Setup categories
        $categories = array();
        foreach( explode(' ', $_REQUEST['categories']) as $category )
        {
            if( $category != MIXED_CATEGORY )
            {
                $categories[] = $DB->Count('SELECT `category_id` FROM `tx_categories` WHERE `tag`=?', array($category));
            }
        }
        $_REQUEST['categories'] = $categories;
    }

    ArrayHSC($_REQUEST);

    include_once('includes/ads-add.php');
}

function txAdAdd()
{
    global $DB, $C;

    VerifyAdministrator();

    $v = new Validator();
    $v->Register($_REQUEST['weight'], V_NUMERIC, 'The Weight value must be filled in and numeric');
    $v->Register($_REQUEST['raw_clicks'], V_NUMERIC, 'The Raw Clicks value must be filled in and numeric');
    $v->Register($_REQUEST['unique_clicks'], V_NUMERIC, 'The Unique Clicks value must be filled in and numeric');
    $v->Register($_REQUEST['ad_url'], V_URL, 'The Ad URL is not properly formatted');
    $v->Register($_REQUEST['ad_html_raw'], V_EMPTY, 'The Ad HTML value must be filled in');

    if( !$v->Validate() )
    {
        return $v->ValidationError('txShAdAdd');
    }

    $_REQUEST['categories'] = CategoryTagsFromIds($_REQUEST['categories']);

    $DB->Update('INSERT INTO `tx_ads` VALUES (?,?,?,?,?,?,?,?,?,?)',
                array(null,
                      $_REQUEST['ad_url'],
                      $_REQUEST['ad_html_raw'],
                      $_REQUEST['ad_html'],
                      $_REQUEST['weight'],
                      $_REQUEST['raw_clicks'],
                      $_REQUEST['unique_clicks'],
                      0,
                      $_REQUEST['categories'],
                      $_REQUEST['tags']));

    $_REQUEST['ad_id'] = $DB->InsertID();

    $t = new Template();
    $t->assign_by_ref('ad', $_REQUEST);
    $t->assign_by_ref('config', $C);
    $_REQUEST['ad_html'] = $t->parse($_REQUEST['ad_html_raw']);
    $t->cleanup();

    $DB->Update('UPDATE `tx_ads` SET `ad_html`=? WHERE `ad_id`=?', array($_REQUEST['ad_html'], $_REQUEST['ad_id']));

    $GLOBALS['message'] = 'New advertisement successfully added';
    $GLOBALS['added'] = true;
    UnsetArray($_REQUEST);
    txShAdAdd();
}

function txAdEdit()
{
    global $DB, $C;

    VerifyAdministrator();

    $v = new Validator();
    $v->Register($_REQUEST['weight'], V_NUMERIC, 'The Weight value must be filled in and numeric');
    $v->Register($_REQUEST['raw_clicks'], V_NUMERIC, 'The Raw Clicks value must be filled in and numeric');
    $v->Register($_REQUEST['unique_clicks'], V_NUMERIC, 'The Unique Clicks value must be filled in and numeric');
    $v->Register($_REQUEST['ad_url'], V_URL, 'The Ad URL is not properly formatted');
    $v->Register($_REQUEST['ad_html_raw'], V_EMPTY, 'The Ad HTML value must be filled in');

    if( !$v->Validate() )
    {
        return $v->ValidationError('txShAdEdit');
    }

    $_REQUEST['categories'] = CategoryTagsFromIds($_REQUEST['categories']);

    $t = new Template();
    $t->assign_by_ref('ad', $_REQUEST);
    $t->assign_by_ref('config', $C);
    $_REQUEST['ad_html'] = $t->parse($_REQUEST['ad_html_raw']);
    $t->cleanup();

    $DB->Update('UPDATE `tx_ads` SET ' .
                '`ad_url`=?, ' .
                '`ad_html_raw`=?, ' .
                '`ad_html`=?, ' .
                '`weight`=?, ' .
                '`raw_clicks`=?, ' .
                '`unique_clicks`=?, ' .
                '`categories`=?, ' .
                '`tags`=? ' .
                'WHERE `ad_id`=?',
                array($_REQUEST['ad_url'],
                      $_REQUEST['ad_html_raw'],
                      $_REQUEST['ad_html'],
                      $_REQUEST['weight'],
                      $_REQUEST['raw_clicks'],
                      $_REQUEST['unique_clicks'],
                      $_REQUEST['categories'],
                      $_REQUEST['tags'],
                      $_REQUEST['ad_id']));

    $GLOBALS['message'] = 'Advertisement successfully updated';
    $GLOBALS['added'] = true;
    txShAdEdit();
}

function txShDomains()
{
    global $DB, $C;

    VerifyAdministrator();

    include_once('includes/domains.php');
}

function txShDomainAdd()
{
    global $DB, $C;

    VerifyAdministrator();
    ArrayHSC($_REQUEST);

    include_once('includes/domains-add.php');
}

function txShDomainEdit()
{
    global $DB, $C;

    VerifyAdministrator();

    $editing = TRUE;

    // First time, use database information
    if( !$_REQUEST['editing'] || $GLOBALS['added'] )
    {
        $_REQUEST = $DB->Row('SELECT * FROM `tx_domains` WHERE `domain_id`=?', array($_REQUEST['domain_id']));

        if( $_REQUEST['categories'] )
        {
            $_REQUEST['categories'] = unserialize($_REQUEST['categories']);
        }
    }

    ArrayHSC($_REQUEST);

    include_once('includes/domains-add.php');
}

function txDomainAdd()
{
    global $DB, $C;

    VerifyAdministrator();

    $v = new Validator();
    $v->Register($_REQUEST['base_url'], V_URL, 'The Base URL is not properly formatted');
    $v->Register($_REQUEST['domain'], V_EMPTY, 'The Domain value must be filled in');
    $v->Register($_REQUEST['document_root'], V_EMPTY, 'The Document Root value must be filled in');

    if( $DB->Count('SELECT COUNT(*) FROM `tx_domains` WHERE `domain`=?', array($_REQUEST['domain'])) )
    {
        $v->SetError('The domain you have entered already exists');
    }

    if( $_REQUEST['create_templates'] && !is_writeable("{$GLOBALS['BASE_DIR']}/templates") )
    {
        $v->SetError('Change the permissions on the templates directory to 777 to have the templates automatically created');
    }

    if( !$v->Validate() )
    {
        return $v->ValidationError('txShDomainAdd');
    }

    if( $_REQUEST['create_templates'] && !IsEmptyString($_REQUEST['template_prefix']) )
    {
        $files =& DirRead("{$GLOBALS['BASE_DIR']}/templates", '^(submit|report|search|email-gallery|error|global|style|partner|email-partner)');

        foreach( $files as $file )
        {
            $orig_template = "{$GLOBALS['BASE_DIR']}/templates/$file";
            $new_template = SafeFilename("{$GLOBALS['BASE_DIR']}/templates/{$_REQUEST['template_prefix']}$file", FALSE);
            if( !file_exists($new_template) )
            {
                $contents = file_get_contents($orig_template);
                $contents = str_replace(array('global-', 'style.css'), array("{$_REQUEST['template_prefix']}global-", "{$_REQUEST['template_prefix']}style.css"), $contents);
                FileWrite($new_template, $contents);
            }
        }

        RecompileTemplates();
    }

    if( in_array('__ALL__', $_REQUEST['categories']) )
    {
        $_REQUEST['categories'] = null;
    }
    else
    {
        $_REQUEST['categories'] = serialize($_REQUEST['categories']);
    }

    $DB->Update('INSERT INTO `tx_domains` VALUES (?,?,?,?,?,?,?,?)',
                array(null,
                      $_REQUEST['domain'],
                      $_REQUEST['base_url'],
                      $_REQUEST['document_root'],
                      $_REQUEST['categories'],
                      intval($_REQUEST['as_exclude']),
                      $_REQUEST['tags'],
                      $_REQUEST['template_prefix']));

    $GLOBALS['message'] = 'New domain successfully added';
    $GLOBALS['added'] = true;
    UnsetArray($_REQUEST);
    txShDomainAdd();
}

function txDomainEdit()
{
    global $DB, $C;

    VerifyAdministrator();

    $v = new Validator();
    $v->Register($_REQUEST['base_url'], V_URL, 'The Base URL is not properly formatted');
    $v->Register($_REQUEST['domain'], V_EMPTY, 'The Domain value must be filled in');
    $v->Register($_REQUEST['document_root'], V_EMPTY, 'The Document Root value must be filled in');

    if( $DB->Count('SELECT COUNT(*) FROM `tx_domains` WHERE `domain`=? AND `domain_id`!=?', array($_REQUEST['domain'], $_REQUEST['domain_id'])) )
    {
        $v->SetError('The domain you have entered already exists');
    }

    if( $_REQUEST['create_templates'] && !is_writeable("{$GLOBALS['BASE_DIR']}/templates") )
    {
        $v->SetError('Change the permissions on the templates directory to 777 to have the templates automatically created');
    }

    if( !$v->Validate() )
    {
        return $v->ValidationError('txShDomainEdit');
    }


    if( $_REQUEST['create_templates'] && !IsEmptyString($_REQUEST['template_prefix']) )
    {
        $files =& DirRead("{$GLOBALS['BASE_DIR']}/templates", '^(submit|report|search|email-gallery|error-fatal|global|style|partner|email-partner)');

        foreach( $files as $file )
        {
            $orig_template = "{$GLOBALS['BASE_DIR']}/templates/$file";
            $new_template = SafeFilename("{$GLOBALS['BASE_DIR']}/templates/{$_REQUEST['template_prefix']}$file", FALSE);
            if( !file_exists($new_template) )
            {
                $contents = file_get_contents($orig_template);
                $contents = str_replace(array('global-', 'style.css'), array("{$_REQUEST['template_prefix']}global-", "{$_REQUEST['template_prefix']}style.css"), $contents);
                FileWrite($new_template, $contents);
            }
        }

        RecompileTemplates();
    }

    if( in_array('__ALL__', $_REQUEST['categories']) )
    {
        $_REQUEST['categories'] = null;
    }
    else
    {
        $_REQUEST['categories'] = serialize($_REQUEST['categories']);
    }

    // Update blacklist item data
    $DB->Update('UPDATE `tx_domains` SET ' .
                '`domain`=?, ' .
                '`base_url`=?, ' .
                '`document_root`=?, ' .
                '`categories`=?, ' .
                '`as_exclude`=?, ' .
                '`tags`=?, ' .
                '`template_prefix`=? ' .
                'WHERE `domain_id`=?',
                array($_REQUEST['domain'],
                      $_REQUEST['base_url'],
                      $_REQUEST['document_root'],
                      $_REQUEST['categories'],
                      intval($_REQUEST['as_exclude']),
                      $_REQUEST['tags'],
                      $_REQUEST['template_prefix'],
                      $_REQUEST['domain_id']));

    $GLOBALS['message'] = 'Domain successfully updated';
    $GLOBALS['added'] = true;
    txShDomainEdit();
}

function txShSearchTerms()
{
    global $DB, $C;

    VerifyAdministrator();

    include_once('includes/search-terms.php');
}

function txShBuildHistory()
{
    global $DB, $C;

    VerifyAdministrator();

    include_once('includes/pages-build-history.php');
}

function txFetchEmails()
{
    global $DB;

    VerifyAdministrator();
    CheckAccessList();

    header("Expires: Mon, 26 Jul 1990 05:00:00 GMT");
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
    header("Cache-Control: no-cache, must-revalidate");
    header("Pragma: no-cache");
    header("Content-type: text/plain");
    header("Content-Disposition: attachment; filename=email-log.txt");

    $emails = '';
    $result = $DB->Query('SELECT * FROM `tx_email_log`');
    while( $log = $DB->NextRow($result) )
    {
        $emails .= $log['email'] . "\r\n";
    }
    $DB->Free($result);

    echo $emails;
}

function txShBuildAllNew()
{
    global $DB;

    VerifyAdministrator();
    $buffering = @ini_get('output_buffering');

    include_once('includes/header.php');
    include_once('includes/pages-build.php');

    if( $buffering )
    {
        echo '<span style="display: none">'.str_repeat('x', $buffering).'</span>';
    }

    flush();

    BuildNewAll('txBuildCallback');

    echo "</div>\n<div id=\"done\"></div>\n</body>\n</html>";
}

function txShBuildAll()
{
    global $DB;

    VerifyAdministrator();
    $buffering = @ini_get('output_buffering');

    include_once('includes/header.php');
    include_once('includes/pages-build.php');

    if( $buffering )
    {
        echo '<span style="display: none">'.str_repeat('x', $buffering).'</span>';
    }

    flush();

    BuildAll('txBuildCallback');

    echo "</div>\n<div id=\"done\"></div>\n</body>\n</html>";
}

function txBuildCallback(&$page)
{
    $buffering = @ini_get('output_buffering');

    echo "Building " . htmlspecialchars($page['page_url']) . "<br />\n";

    if( $buffering )
    {
        echo '<span style="display: none">'.str_repeat('x', $buffering).'</span>';
    }

    flush();
}

function txShPreviewUpload()
{
    global $DB, $C;

    VerifyPrivileges(P_GALLERY_MODIFY);

    include_once('includes/preview-upload.php');
}

function txPreviewUpload()
{
    global $DB, $C;

    VerifyPrivileges(P_GALLERY_MODIFY);

    require_once("{$GLOBALS['BASE_DIR']}/includes/imager.class.php");

    $imagefile = "{$GLOBALS['BASE_DIR']}/cache/" . md5(uniqid(rand(), true)) . ".jpg";
    $i = GetImager();

    if( is_uploaded_file($_FILES['upload']['tmp_name']) )
    {
        move_uploaded_file($_FILES['upload']['tmp_name'], $imagefile);
        @chmod($imagefile, 0666);
        $image = @getimagesize($imagefile);

        if( $image !== FALSE && $image[2] == IMAGETYPE_JPEG )
        {
            switch( $_REQUEST['action'] )
            {
                case 'auto':
                    $gallery = $DB->Row('SELECT * FROM `tx_galleries` WHERE `gallery_id`=?', array($_REQUEST['gallery_id']));
                    $categories =& CategoriesFromTags($gallery['categories']);
                    $format = GetCategoryFormat($gallery['format'], $categories[0]);
                    $annotation =& LoadAnnotation($format['annotation'], $categories[0]['name']);

                    $i->ResizeAuto($imagefile, $format['preview_size'], $annotation, $C['landscape_bias'], $C['portrait_bias']);

                    $preview = AddPreview($_REQUEST['gallery_id'], $format['preview_size'], $imagefile);

                    $_REQUEST['dimensions'] = $format['preview_size'];

                    include_once('includes/crop-complete.php');

                    break;

                case 'manual':
                    txShCrop($imagefile);
                    break;

                default:
                    $gallery = $DB->Row('SELECT * FROM `tx_galleries` WHERE `gallery_id`=?', array($_REQUEST['gallery_id']));
                    $categories =& CategoriesFromTags($gallery['categories']);
                    $format = GetCategoryFormat($gallery['format'], $categories[0]);
                    $annotation =& LoadAnnotation($format['annotation'], $categories[0]['name']);

                    $i->Annotate($imagefile, $annotation);

                    $preview = AddPreview($_REQUEST['gallery_id'], "{$image[0]}x{$image[1]}", $imagefile);
                    $_REQUEST['dimensions'] = "{$image[0]}x{$image[1]}";

                    include_once('includes/crop-complete.php');

                    break;
            }

            return;
        }
        else
        {
            @unlink($imagefile);
            $GLOBALS['errstr'] = 'Uploaded file is not a valid JPEG image';
        }
    }
    else
    {
        $GLOBALS['errstr'] = 'Image upload failed';
    }

    include_once('includes/preview-upload.php');
}

function txShCrop($image = null)
{
    global $DB, $C;

    VerifyPrivileges(P_GALLERY_MODIFY);

    include_once('includes/crop.php');
}

function txCrop()
{
    global $DB, $C;

    require_once("{$GLOBALS['BASE_DIR']}/includes/imager.class.php");

    $gallery = $DB->Row('SELECT * FROM `tx_galleries` WHERE `gallery_id`=?', array($_REQUEST['gallery_id']));
    $categories =& CategoriesFromTags($gallery['categories']);
    $format = GetCategoryFormat($gallery['format'], $categories[0]);
    $annotation =& LoadAnnotation($format['annotation'], $categories[0]['name']);
    $tempfile = md5(uniqid(rand(), true)) . ".jpg";

    // Make a copy so the original can remain cached
    copy("{$GLOBALS['BASE_DIR']}/cache/{$_REQUEST['imagefile']}", "{$GLOBALS['BASE_DIR']}/cache/$tempfile");

    $i = GetImager();
    $i->ResizeCropper("{$GLOBALS['BASE_DIR']}/cache/$tempfile", $_REQUEST['dimensions'], $_REQUEST, $annotation);

    $preview = AddPreview($gallery['gallery_id'], $_REQUEST['dimensions'], "{$GLOBALS['BASE_DIR']}/cache/$tempfile");
    UpdateThumbSizes($_REQUEST['dimensions']);

    include_once('includes/crop-complete.php');
}

function txCropWithBias()
{
    global $DB, $C;

    require_once("{$GLOBALS['BASE_DIR']}/includes/imager.class.php");

    $gallery = $DB->Row('SELECT * FROM `tx_galleries` WHERE `gallery_id`=?', array($_REQUEST['gallery_id']));
    $categories =& CategoriesFromTags($gallery['categories']);
    $format = GetCategoryFormat($gallery['format'], $categories[0]);
    $annotation =& LoadAnnotation($format['annotation'], $categories[0]['name']);
    $tempfile = md5(uniqid(rand(), true)) . ".jpg";

    $http = new Http();

    if( $http->Get($_REQUEST['imagefile'], TRUE, $gallery['gallery_url']) )
    {
        FileWrite("{$GLOBALS['BASE_DIR']}/cache/$tempfile", $http->body);

        $i = GetImager();
        $i->ResizeAuto("{$GLOBALS['BASE_DIR']}/cache/$tempfile", $_REQUEST['dimensions'], $annotation, $_REQUEST['bias_land'], $_REQUEST['bias_port']);

        $preview = AddPreview($gallery['gallery_id'], $_REQUEST['dimensions'], "{$GLOBALS['BASE_DIR']}/cache/$tempfile");
        UpdateThumbSizes($_REQUEST['dimensions']);

        include_once('includes/crop-complete.php');
    }
    else
    {
        $error = 'Could not download image file: ' . $http->errstr;
        include_once('includes/error.php');
    }
}

function txShCheatReports()
{
    global $DB, $C;

    VerifyPrivileges(P_GALLERY);

    include_once('includes/cheat-reports.php');
}

function txShDatabaseTools()
{
    global $DB, $C;

    VerifyAdministrator();
    CheckAccessList();

    include_once('includes/database.php');
}

function txDatabaseOptimize()
{
    global $DB, $C;

    VerifyAdministrator();
    CheckAccessList();

    $tables = array();
    IniParse("{$GLOBALS['BASE_DIR']}/includes/tables.php", TRUE, $tables);

    include_once('includes/header.php');
    include_once('includes/database-optimize.php');
    flush();

    foreach( array_keys($tables) as $table )
    {
        echo "Repairing " . htmlspecialchars($table) . "<br />"; flush();
        $DB->Update('REPAIR TABLE #', array($table));
        echo "Optimizing " . htmlspecialchars($table) . "<br />"; flush();
        $DB->Update('OPTIMIZE TABLE #', array($table));
    }

    echo "\n<div id=\"done\"></div></div>\n</body>\n</html>";
}

function txDatabaseBackup()
{
    global $DB, $C;

    VerifyAdministrator();
    CheckAccessList();

    $GLOBALS['message'] = DoDatabaseBackup($_REQUEST);

    txShDatabaseTools();
}

function txDatabaseRestore()
{
    global $DB, $C;

    VerifyAdministrator();
    CheckAccessList();

    $GLOBALS['message'] = DoDatabaseRestore($_REQUEST);

    txShDatabaseTools();
}

function txShPages()
{
    global $DB, $C;

    VerifyAdministrator();
    CheckAccessList();

    include_once('includes/pages.php');
}

function txShPageTasks()
{
    global $DB, $C;

    VerifyAdministrator();

    include_once('includes/pages-tasks.php');
}

function txShPageAddBulk()
{
    global $DB, $C;

    VerifyAdministrator();
    CheckAccessList();
    ArrayHSC($_REQUEST);

    include_once('includes/pages-add-bulk.php');
}

function txShPageAdd()
{
    global $DB, $C;

    VerifyAdministrator();
    CheckAccessList();
    ArrayHSC($_REQUEST);

    include_once('includes/pages-add.php');
}

function txShPageEdit()
{
    global $DB, $C;

    VerifyAdministrator();
    CheckAccessList();

    $editing = TRUE;

    // First time, use database information
    if( !$_REQUEST['editing'] || $GLOBALS['added'] )
    {
        $_REQUEST = $DB->Row('SELECT * FROM `tx_pages` WHERE `page_id`=?', array($_REQUEST['page_id']));
    }

    ArrayHSC($_REQUEST);

    include_once('includes/pages-add.php');
}

function txPageAddBulk()
{
    global $DB, $C;

    VerifyAdministrator();
    CheckAccessList();

    $v = new Validator();
    $v->Register($_REQUEST['ext'], V_EMPTY, 'The File Extension field must be filled in');
    $v->Register($_REQUEST['num_pages'], V_REGEX, 'The Number of Pages field must be a numeric value', '~^\d+$~');

    if( empty($_REQUEST['category_id']) )
    {
        $v->Register($_REQUEST['prefix'], V_EMPTY, 'The Filename Prefix field must be filled in');
    }

    // Check tags for proper format
    if( !IsEmptyString($_REQUEST['tags']) )
    {
        $_REQUEST['tags'] = FormatSpaceSeparated($_REQUEST['tags']);
        foreach( explode(' ', $_REQUEST['tags']) as $tag )
        {
            if( strlen($tag) < 4 || !preg_match('~^[a-z0-9_]+$~i', $tag) )
            {
                $v->SetError('All page tags must be at least 4 characters in length and contain only letters, numbers, and underscores');
                break;
            }
        }
    }


    $v->Register($_REQUEST['base_url'], V_URL, 'The Base URL field is not a properly formatted HTTP URL');
    $v->Register($_REQUEST['base_dir'], V_EMPTY, 'The Base Directory field must be filled in');

    $base_dir = $_REQUEST['base_dir'];
    $base_url = $_REQUEST['base_url'];

    if( !is_dir($base_dir) ):
        $v->SetError('The Base Directory value must point to an already existing directory');
    endif;




    if( !$v->Validate() )
    {
        return $v->ValidationError('txShPageAddBulk');
    }

    // Get starting build order
    $build_order = $DB->Count('SELECT MAX(`build_order`) FROM `tx_pages`') + 1;

    // Load default template
    $template = file_get_contents("{$GLOBALS['BASE_DIR']}/templates/default-tgp.tpl");

    NullIfEmpty($_REQUEST['category_id']);

    $pages =& GetBulkAddPages($base_url, $base_dir);
    $c = new Compiler();

    $buffering = @ini_get('output_buffering');

    include_once('includes/header.php');
    include_once('includes/pages-add-bulk-progress.php');

    if( $buffering )
    {
        echo '<span style="display: none">'.str_repeat('x', $buffering).'</span>';
        flush();
    }

    foreach( $pages as $page )
    {
        if( $DB->Count('SELECT COUNT(*) FROM `tx_pages` WHERE `filename`=?', array($page['filename'])) < 1 )
        {
            echo "Adding " . htmlspecialchars($page['page_url']) . "<br />";
            if( $buffering )
            {
                echo '<span style="display: none">'.str_repeat('x', $buffering)."</span>\n";
            }
            flush();

            $compiled = '';
            $c->flags['category_id'] = $page['category_id'];
            $c->compile($template, $compiled);

            // Add page to the database
            $DB->Update('INSERT INTO `tx_pages` VALUES (?,?,?,?,?,?,?,?,?)',
                        array(NULL,
                              $page['filename'],
                              $page['page_url'],
                              $page['category_id'],
                              $build_order++,
                              intval($_REQUEST['locked']),
                              $_REQUEST['tags'],
                              $template,
                              $compiled));
        }
    }

    RenumberBuildOrder();

    echo "</div>\n<div id=\"done\"></div>\n</body>\n</html>";
}

function txPageAdd()
{
    global $DB, $C;

    VerifyAdministrator();
    CheckAccessList();

    $v = new Validator();



    $v->Register($_REQUEST['page_url'], V_URL, 'The Page URL field is not a properly formatted HTTP URL');
    $v->Register($_REQUEST['filename'], V_EMPTY, 'The Path & Filename field must be filled in');

    if( preg_match('~^https?://~i', $_REQUEST['filename']) ):
        $v->SetError('The Path & Filename field cannot be a HTTP URL, it must be a directory path on your server');
    endif;

    $filename = $_REQUEST['filename'];
    $page_url = $_REQUEST['page_url'];

    if( is_dir($filename) ):
        $v->SetError('The Filename value you entered points to a directory');
    endif;




    // See if the filename is the TGPX index.php file
    if( $filename == "{$GLOBALS['BASE_DIR']}/index.php" )
    {
        $v->SetError('The TGP page you are trying to add is the same as the TGPX index.php file');
    }

    // See if the same page already exists
    if( $DB->Count('SELECT COUNT(*) FROM `tx_pages` WHERE `filename`=? OR `page_url`=?', array($filename, $page_url)) )
    {
        $v->SetError('The TGP page you are trying to add already exists');
    }

    // Check tags for proper format
    if( !IsEmptyString($_REQUEST['tags']) )
    {
        $_REQUEST['tags'] = FormatSpaceSeparated($_REQUEST['tags']);
        foreach( explode(' ', $_REQUEST['tags']) as $tag )
        {
            if( strlen($tag) < 4 || !preg_match('~^[a-z0-9_]+$~i', $tag) )
            {
                $v->SetError('All page tags must be at least 4 characters in length and contain only letters, numbers, and underscores');
                break;
            }
        }
    }

    if( !$v->Validate() )
    {
        return $v->ValidationError('txShPageAdd');
    }

    // Generate build order if not supplied
    if( !is_numeric($_REQUEST['build_order']) )
    {
        $_REQUEST['build_order'] = $DB->Count('SELECT MAX(`build_order`) FROM `tx_pages`') + 1;
    }

    // Update build orders greater than or equal to the new page's value
    $DB->Update('UPDATE `tx_pages` SET `build_order`=`build_order`+1 WHERE `build_order`>=?', array($_REQUEST['build_order']));

    // Get template and compile
    $compiled = '';
    $template = file_get_contents("{$GLOBALS['BASE_DIR']}/templates/default-tgp.tpl");
    $c = new Compiler();
    $c->flags['category_id'] = $_REQUEST['category_id'];
    $c->compile($template, $compiled);

    NullIfEmpty($_REQUEST['category_id']);

    // Add page to the database
    $DB->Update('INSERT INTO `tx_pages` VALUES (?,?,?,?,?,?,?,?,?)',
                array(NULL,
                      $filename,
                      $page_url,
                      $_REQUEST['category_id'],
                      $_REQUEST['build_order'],
                      intval($_REQUEST['locked']),
                      $_REQUEST['tags'],
                      $template,
                      $compiled));

    $GLOBALS['message'] = 'New TGP page successfully added';
    $GLOBALS['added'] = true;

    if( file_exists($filename) )
    {
        $GLOBALS['warn'][] = "The file $filename already exists on your server";

        if( !is_writable($filename) )
        {
            $GLOBALS['warn'][] = 'You will not be able to rebuild your pages until you either remove the existing file or change it\'s permissions to 666';
        }
        else
        {
            $GLOBALS['warn'][] = 'If you rebuild your pages, the old file will be overwritten by the software.  It is recommended that you backup or remove the file before rebuilding your pages';
        }
    }

    RenumberBuildOrder();
    UnsetArray($_REQUEST);
    txShPageAdd();
}

function txPageEdit()
{
    global $DB, $C;

    VerifyAdministrator();
    CheckAccessList();

    $v = new Validator();



    $v->Register($_REQUEST['page_url'], V_URL, 'The Page URL field is not a properly formatted HTTP URL');
    $v->Register($_REQUEST['filename'], V_EMPTY, 'The Filename field must be filled in');

    $filename = $_REQUEST['filename'];
    $page_url = $_REQUEST['page_url'];




    // See if the filename is the TGPX index.php file
    if( $filename == "{$GLOBALS['BASE_DIR']}/index.php" )
    {
        $v->SetError('The TGP page you are trying to add is the same as the TGPX index.php file');
    }

    // See if the same page already exists
    if( $DB->Count('SELECT COUNT(*) FROM `tx_pages` WHERE (`filename`=? OR `page_url`=?) AND `page_id`!=?', array($filename, $page_url, $_REQUEST['page_id'])) )
    {
        $v->SetError('You are changing this TGP page to be the same as an already existing page');
    }

    // Check tags for proper format
    if( !IsEmptyString($_REQUEST['tags']) )
    {
        $_REQUEST['tags'] = FormatSpaceSeparated($_REQUEST['tags']);
        foreach( explode(' ', $_REQUEST['tags']) as $tag )
        {
            if( strlen($tag) < 4 || !preg_match('~^[a-z0-9_]+$~i', $tag) )
            {
                $v->SetError('All page tags must be at least 4 characters in length and contain only letters, numbers, and underscores');
                break;
            }
        }
    }

    if( !$v->Validate() )
    {
        return $v->ValidationError('txShPageEdit');
    }

    $page = $DB->Row('SELECT * FROM `tx_pages` WHERE `page_id`=?', array($_REQUEST['page_id']));

    // Use current build order if not supplied
    if( !is_numeric($_REQUEST['build_order']) )
    {
        $_REQUEST['build_order'] = $page['build_order'];
    }

    NullIfEmpty($_REQUEST['category_id']);


    // Update page settings
    $DB->Update('UPDATE `tx_pages` SET ' .
                '`filename`=?, ' .
                '`page_url`=?, ' .
                '`category_id`=?, ' .
                '`build_order`=?, ' .
                '`locked`=?, ' .
                '`tags`=? ' .
                'WHERE `page_id`=?',
                array($filename,
                      $page_url,
                      $_REQUEST['category_id'],
                      $_REQUEST['build_order'],
                      intval($_REQUEST['locked']),
                      $_REQUEST['tags'],
                      $_REQUEST['page_id']));


    // Update build orders greater than or equal to the updated page's value
    if( $_REQUEST['build_order'] < $page['build_order'] )
    {
        $DB->Update('UPDATE `tx_pages` SET `build_order`=`build_order`+1 WHERE `page_id`!=?', array($_REQUEST['page_id']));
    }
    else if( $_REQUEST['build_order'] > $page['build_order'] )
    {
        $DB->Update('UPDATE `tx_pages` SET `build_order`=`build_order`-1 WHERE `page_id`!=?', array($_REQUEST['page_id']));
    }


    $GLOBALS['message'] = 'TGP page successfully updated';
    $GLOBALS['added'] = true;

    RenumberBuildOrder();
    txShPageEdit();
}

function txShPagesRecompile()
{
    global $DB, $C;

    VerifyAdministrator();
    CheckAccessList();

    $buffering = @ini_get('output_buffering');

    include_once('includes/header.php');
    include_once('includes/pages-recompile.php');

    if( $buffering )
    {
        echo '<span style="display: none">'.str_repeat('x', $buffering).'</span>';
    }

    flush();

    $result = $DB->Query('SELECT `page_id`,`category_id`,`page_url` FROM `tx_pages`');
    while( $page = $DB->NextRow($result) )
    {
        echo "Recompiling " . htmlspecialchars($page['page_url']) . "<br />";
        if( $buffering )
        {
            echo '<span style="display: none">'.str_repeat('x', $buffering).'</span>';
        }
        flush();

        $compiled = '';
        $template = $DB->Count('SELECT `template` FROM `tx_pages` WHERE `page_id`=?', array($page['page_id']));
        $c = new Compiler();
        $c->flags['category_id'] = $page['category_id'];
        $c->compile($template, $compiled);

        $DB->Update('UPDATE `tx_pages` SET `compiled`=? WHERE `page_id`=?', array($compiled, $page['page_id']));

        unset($page);
    }
    $DB->Free($result);

    echo "</div>\n<div id=\"done\"></div>\n</body>\n</html>";
}

function txShPagesTest()
{
    global $DB, $C;

    VerifyAdministrator();
    CheckAccessList();

    $buffering = @ini_get('output_buffering');

    include_once('includes/header.php');
    include_once('includes/pages-test.php');

    if( $buffering )
    {
        echo '<span style="display: none">'.str_repeat('x', $buffering).'</span>';
    }

    flush();

    $result = $DB->Query('SELECT `filename`,`page_url` FROM `tx_pages`');
    while( $page = $DB->NextRow($result) )
    {
        $error = null;
        if( is_dir($page['filename']) )
        {
            $error = "{$page['filename']} is a directory";
        }
        else if( is_file($page['filename']) )
        {
            if( !is_writable($page['filename']) )
            {
                $error = "{$page['filename']} is not writeable; change permissions to 666";
            }
        }
        else
        {
            $dir = dirname($page['filename']);

            if( !is_dir($dir) )
            {
                $error = "The directory $dir does not exist; create it and set permissions to 777";
            }
            else if( !is_writable($dir) )
            {
                $error = "The directory $dir is not writeable; change permissions to 777";
            }
        }


        if( $error )
        {
            echo "{$page['page_url']}<br />" .
                 "<div style=\"margin-left: 20px; color: #ff0000; font-size: 8pt;\">{$error}</div>";
        }
        else
        {
            echo '<b style="display: none;"></b>';
        }

        if( $buffering )
        {
            echo '<span style="display: none">'.str_repeat('x', $buffering).'</span>';
        }
        flush();

        unset($page);
    }
    $DB->Free($result);

    echo "</div>\n<div id=\"done\"></div>\n</body>\n</html>";
}

function txShPageTemplateWizard()
{
    global $DB, $C;

    VerifyAdministrator();

    include_once('includes/pages-templates-wizard.php');
}

function txPageTemplateWizard()
{
    global $DB, $C;

    $intermix = ($_REQUEST['submitted_percent'] > 0 && $_REQUEST['permanent_percent'] > 0);
    $rss_feed = strpos($_REQUEST['display'], 'xml') === 0;
    $sections = array();
    $template = '';

    if( $rss_feed )
        $_REQUEST['inrows'] = 'no';

    if( $_REQUEST['submitted_percent'] > 0 )
        $sections[] =& $_REQUEST['s'];

    if( $_REQUEST['permanent_percent'] > 0 )
        $sections[] =& $_REQUEST['p'];

    $_REQUEST['s']['amount'] = round($_REQUEST['amount'] * ($_REQUEST['submitted_percent']/100));
    $_REQUEST['p']['amount'] = $_REQUEST['amount'] - $_REQUEST['s']['amount'];

    foreach( $sections as $section )
    {
        $options = array('type=' . $section['type'],
                         'format=' . $section['format'],
                         'getnew=' . ($section['getnew'] == 'allowused' ? 'true' : $section['getnew']),
                         'allowused=' . ($section['getnew'] == 'allowused' ? 'true' : 'false'),
                         'description=' . $section['description'],
                         'preview=' . $section['preview'],
                         'amount=' . $section['amount']);

        if( $section['preview'] == 'true' )
        {
            if( (empty($section['previewsize']) && !empty($section['customsize'])) || !empty($section['previewsize']) )
            {
                $options[] = 'previewsize=' . (empty($section['previewsize']) ? $section['customsize'] : $section['previewsize']);
            }
        }

        if( !empty($section['weight']) )
        {
            $options[] = 'weight="' . $section['weight'] . $section['weight_value'] . '"';
        }

        switch($section['agetype'])
        {
            case 'exact':
                $options[] = 'age='.$section['age'];
                break;

            case 'atleast':
                $options[] = 'minage='.$section['age'];
                break;

            case 'atmost':
                $options[] = 'maxage='.$section['age'];
                break;

            case 'between':
                $options[] = 'minage='.$section['age'];
                $options[] = 'maxage='.$section['endage'];
                break;
        }

        if( in_array('MIXED', $section['categories']) )
        {
            $categories = array('MIXED');

            foreach( $section['exclude_categories'] as $exclude )
            {
                if( empty($exclude) )
                    continue;

                $categories[] = "-$exclude";
            }

            $options[] = 'category='.join(',', $categories);
        }
        else
        {
            $categories = array();

            foreach( $section['categories'] as $category )
            {
                $categories[] = $category;
            }

            $options[] = 'category='.join(',', $categories);
        }

        if( in_array('', $section['sponsors']) )
        {
            $sponsors = array('any');

            foreach( $section['exclude_sponsors'] as $exclude )
            {
                if( empty($exclude) )
                    continue;

                $sponsors[] = "-$exclude";
            }

            $options[] = 'sponsor='.join(',', $sponsors);
        }
        else
        {
            $sponsors = array();

            foreach( $section['sponsors'] as $sponsor )
            {
                $sponsors[] = $sponsor;
            }

            $options[] = 'sponsor='.join(',', $sponsors);
        }

        if( $section['getnew'] == 'false' )
        {
            $section['order'] = $section['reorder'];
        }

        $options[] = 'order=' . join(', ', $section['order']);

        if( $section['getnew'] != 'false' )
        {
            $options[] = 'reorder=' . join(', ', $section['reorder']);
        }

        $template .= "{* PULL THE ".strtoupper($section['type'])." GALLERIES FROM THE DATABASE *}\n" .
                     "{galleries\n" .
                     "var=" . ($intermix ? "\${$section['type']}_gals\n" : "\$galleries\n") .
                     join("\n", $options) . "}\n";
    }

    $code = array('text' => '{$gallery.date|tdate} <a href="{$gallery.gallery_url|htmlspecialchars}" target="_blank">{$gallery.thumbnails|htmlspecialchars} {$gallery.category|htmlspecialchars}</a><br />',
                  'thumbs' => '<a href="{$gallery.gallery_url|htmlspecialchars}" target="_blank"><img src="{$gallery.preview_url|htmlspecialchars}" border="0" alt="Thumb"></a>',
                  'xml' => "<item>\n" .
                           "<title>{\$gallery.thumbnails|htmlspecialchars} {\$gallery.category|htmlspecialchars} {\$gallery.format|htmlspecialchars}</title>\n" .
                           "<link>{\$gallery.gallery_url|htmlspecialchars}</link>\n" .
                           "<description>{\$gallery.description|htmlspecialchars}</description>" .
                           "<pubDate>{\$gallery.date|tdate::'D, d M Y H:i:s ".RssTimezone()."'}</pubDate>\n" .
                           "</item>",
                  'xmlthumbs' => "<item>\n" .
                                 "<title>{\$gallery.thumbnails|htmlspecialchars} {\$gallery.category|htmlspecialchars} {\$gallery.format|htmlspecialchars}</title>\n" .
                                 "<link>{\$gallery.gallery_url|htmlspecialchars}</link>\n" .
                                 "<description>\n" .
                                 "&lt;a href=&quot;{\$gallery.gallery_url|htmlspecialchars}&quot; title=&quot;Thumb&quot;&gt;&lt;img src=&quot;{\$gallery.preview_url|htmlspecialchars}&quot; alt=&quot;Thumb&quot; border=&quot;0&quot; /&gt;&lt;/a&gt;\n" .
                                 "&lt;br /&gt;&lt;br /&gt;\n" .
                                 "{\$gallery.description|htmlspecialchars}\n" .
                                 "</description>\n" .
                                 "<pubDate>{\$gallery.date|tdate::'D, d M Y H:i:s ".RssTimezone()."'}</pubDate>\n" .
                                 "</item>");

    if( $intermix )
    {
        $location = 'random';
        switch($_REQUEST['mix_method'])
        {
            case 'interval':
                $_REQUEST['mix_value'] = preg_replace('~[^0-9]~', '', $_REQUEST['mix_value']);
                $location = "+{$_REQUEST['mix_value']}";
                break;

            case 'locations':
                $location = $_REQUEST['mix_value'];
                break;
        }

        $template .= "{* MIX THE PERMANENT AND SUBMITTED GALLERIES TOGETHER *}\n" .
                     "{intermix var=\$galleries from=\$submitted_gals,\$permanent_gals location=$location}\n\n";
    }

    if( $_REQUEST['inrows'] == 'yes' )
    {
        $template .= "<table align=\"center\" cellpadding=\"5\" border=\"0\">\n" .
                     "<tr>\n";

        $code['thumbs'] = '<td>' . $code['thumbs'] . '</td>';
        $code['text'] = '<td>' . $code['text'] . '</td>';
    }

    $template .= "{* DISPLAY ALL OF THE GALLERIES *}\n" .
                 "{foreach var=\$gallery from=\$galleries counter=\$counter}\n" .
                 $code[$_REQUEST['display']] . "\n";

    if( $_REQUEST['inrows'] == 'yes' && $_REQUEST['amount'] > $_REQUEST['perrow'] )
    {
        $template .= "{insert counter=\$counter location=+{$_REQUEST['perrow']} max=".($_REQUEST['amount']-$_REQUEST['perrow'])."}\n" .
                     "</tr><tr>\n" .
                     "{/insert}\n";
    }

    $template .= "{/foreach}\n";

    if( $_REQUEST['inrows'] == 'yes' )
    {
        $template .= "</tr>\n" .
                     "</table>\n";
    }

    if( $rss_feed )
    {
        $template = "{define name=globaldupes value=true}\n" .
                    "{define name=pagedupes value=false}\n" .
                    "{php} echo '<?xml  version=\"1.0\" ?>'; {/php}\n" .
                    "<rss version=\"2.0\">\n" .
                    "  <channel>\n" .
                    "    <title>Your Site Title</title>\n" .
                    "    <description>Your site description</description>\n" .
                    "    <link>http://www.yoursite.com/</link>\n" .
                    $template .
                    "  </channel>\n" .
                    "</rss>";
    }

    include_once('includes/header.php');
    echo "<div style=\"padding: 10px;\">\n";
    echo "Here is your generated template code:<br /><br />\n<textarea rows=\"40\" cols=\"140\" wrap=\"off\">";
    echo htmlspecialchars($template);
    echo "</textarea>\n</div>\n</body>\n</html>";
}

function txShPageTemplateReplace()
{
    global $DB, $C;

    VerifyAdministrator();
    CheckAccessList();

    include_once('includes/pages-templates-replace.php');
}

function txPageTemplatesReplace()
{
    global $DB, $C;

    VerifyAdministrator();
    CheckAccessList();

    $GLOBALS['_counter'] = 0;
    $GLOBALS['_details'] = array();

    if( is_array($_REQUEST['pages']) )
    {
        // Prepare data for the search and replace
        UnixFormat($_REQUEST['search']);
        UnixFormat($_REQUEST['replace']);
        $search = preg_quote($_REQUEST['search'], '~');

        foreach( $_REQUEST['pages'] as $page_id )
        {
            $page = $DB->Row('SELECT * FROM `tx_pages` WHERE `page_id`=?', array($page_id));

            $GLOBALS['_replaced'] = FALSE;
            $GLOBALS['_counter_this_page'] = 0;
            $GLOBALS['_page'] =& $page;

            UnixFormat($page['template']);

            $page['template'] = preg_replace_callback("~$search~i",
                                                      'txPageTemplatesReplaceCallback',
                                                      $page['template']);

            // Update and recompile template if replacements were made
            if( $GLOBALS['_replaced'] )
            {
                $compiled = '';
                $c = new Compiler();
                $c->flags['category_id'] = $page['category_id'];
                $c->compile($page['template'], $compiled);

                $DB->Update('UPDATE `tx_pages` SET `template`=?,`compiled`=? WHERE `page_id`=?',
                            array($page['template'], $compiled, $page_id));

                if( $_REQUEST['detailed'] )
                {
                    $GLOBALS['_details'][] = $page['page_url'] . " (" . $GLOBALS['_counter_this_page'] . ")";
                }
            }
        }
    }

    $GLOBALS['message'] = $GLOBALS['_counter'] == 1 ?
                          "A total of 1 replacement has been made" :
                          "A total of {$GLOBALS['_counter']} replacements have been made";

    txShPageTemplateReplace();
}

function txPageTemplatesReplaceCallback($matches)
{
    $GLOBALS['_counter']++;
    $GLOBALS['_counter_this_page']++;
    $GLOBALS['_replaced'] = TRUE;

    return $_REQUEST['replace'];
}

function txShPageTemplates()
{
    global $DB, $C;

    VerifyAdministrator();
    CheckAccessList();

    ArrayHSC($_REQUEST);

    include_once('includes/pages-templates.php');
}

function txPageTemplateLoad()
{
    global $DB, $C;

    VerifyAdministrator();
    CheckAccessList();

    $page = $DB->Row('SELECT `page_id`,`page_url`,`template` FROM `tx_pages` WHERE `page_id`=?', array($_REQUEST['page_id']));
    $_REQUEST['page'] = $page;
    $_REQUEST['code'] = $page['template'];

    txShPageTemplates();
}

function txPageTemplateSave()
{
    global $DB, $C;

    VerifyAdministrator();
    CheckAccessList();

    // Remove extra whitespace from the template code
    $_REQUEST['code'] = trim($_REQUEST['code']);
    $_REQUEST['page_id'] = explode(',', $_REQUEST['page_id']);

    if( preg_match('~<\?[^x]~', $_REQUEST['code']) )
    {
        $GLOBALS['errstr'] = "Template not saved: Your template currently contains raw PHP code using &lt;? and ?&gt; tags; " .
                             "you should instead use the special {php} or {phpcode} template functions for raw PHP code (see the software manual for details on these template functions)";
    }

    // Attempt to compile the code
    if( !isset($GLOBALS['errstr']) )
    {
        foreach( $_REQUEST['page_id'] as $page_id )
        {
            if( !empty($page_id) )
            {
                $page = $DB->Row('SELECT * FROM `tx_pages` WHERE `page_id`=?', array($page_id));
                $compiled = '';
                $c = new Compiler();
                $c->flags['category_id'] = $page['category_id'];

                if( $c->compile($_REQUEST['code'], $compiled) )
                {
                    $DB->Update('UPDATE `tx_pages` SET `template`=?,`compiled`=? WHERE `page_id`=?',
                                array($_REQUEST['code'],
                                      $compiled,
                                      $page_id));
                }
                else
                {
                    $GLOBALS['errstr'] = "Template for {$page['page_url']} could not be saved:<br />" . nl2br($c->get_error_string());
                }
            }
        }

        // Build pages if the option was selected
        if( $_REQUEST['build'] )
        {
            BuildSelected($_REQUEST['page_id']);
        }
    }

    $_REQUEST['page_id'] = $_REQUEST['page_id'][0];
    $_REQUEST['page'] = $DB->Row('SELECT `page_id`,`page_url` FROM `tx_pages` WHERE `page_id`=?', array($_REQUEST['page_id']));

    if( !isset($GLOBALS['errstr']) )
    {
        $GLOBALS['message'] = 'Template has been successully saved';
        $GLOBALS['warnstr'] = CheckTemplateCode($_REQUEST['code']);
    }

    txShPageTemplates();
}

function txShScannerResults()
{
    global $DB, $C;

    VerifyAdministrator();

    include_once('includes/galleries-scanner-results.php');
}

function txShScannerHistory()
{
    global $DB, $C;

    VerifyAdministrator();

    include_once('includes/galleries-scanner-history.php');
}

function txShGalleryTasks()
{
    global $DB, $C;

    VerifyPrivileges(P_GALLERY);

    include_once('includes/galleries-tasks.php');
}

function txShGalleryStats()
{
    global $DB, $C;

    VerifyPrivileges(P_GALLERY);

    include_once('includes/galleries-statistics.php');
}

function txShGalleryBreakdown()
{
    global $DB, $C;

    VerifyPrivileges(P_GALLERY);

    include_once('includes/galleries-breakdown.php');
}

function txShGalleryBlacklist()
{
    global $DB, $C;

    VerifyPrivileges(P_GALLERY_REMOVE);

    $gallery = $DB->Row('SELECT * FROM `tx_galleries` WHERE `gallery_id`=?', array($_REQUEST['gallery_id']));
    $_REQUEST = array_merge($_REQUEST, $gallery);

    $_REQUEST['dns'] = GetNameServers($_REQUEST['gallery_url']);

    ArrayHSC($_REQUEST);

    include_once('includes/galleries-blacklist.php');
}

function txGalleryBlacklist()
{
    global $DB, $json, $C, $BLIST_TYPES;

    VerifyPrivileges(P_GALLERY_REMOVE, TRUE);

    $gallery = DeleteGallery($_REQUEST['gallery_id']);

    foreach( $BLIST_TYPES as $type => $name )
    {
        if( IsEmptyString($_REQUEST[$type]) )
            continue;

        if( $DB->Count('SELECT COUNT(*) FROM `tx_blacklist` WHERE `type`=? AND `value`=?', array($type, $_REQUEST[$type])) < 1 )
        {
            $DB->Update('INSERT INTO `tx_blacklist` VALUES (?,?,?,?,?)', array(null, $type, 0, $_REQUEST[$type], $_REQUEST['reason']));
        }
    }

    $GLOBALS['added'] = TRUE;
    $message = 'The selected gallery has been blacklisted';
    include_once('includes/message.php');
}

function txShGalleryScan()
{
    global $DB, $C;

    // Get gallery information
    $gallery = $DB->Row('SELECT * FROM `tx_galleries` WHERE `gallery_id`=?', array($_REQUEST['gallery_id']));

    $partner = null;
    if( $gallery['partner'] )
    {
        $partner = $DB->Row('SELECT * FROM `tx_partners` WHERE `username`=?', array($gallery['partner']));
    }

    $categories =& CategoriesFromTags($gallery['categories']);
    $whitelisted = MergeWhitelistOptions(CheckWhitelist($gallery), $partner);

    // Scan the gallery
    $scan =& ScanGallery($gallery, $categories[0], $whitelisted);

    if( $scan['success'] )
    {
        // Check the blacklist
        $gallery['html'] = $scan['html'];
        $gallery['headers'] = $scan['headers'];
        $blacklisted = CheckBlacklistGallery($gallery, TRUE);

        // See if category allows this format
        $scan['bad_format'] = FALSE;
        $format = GetCategoryFormat($scan['format'], $categories[0]);
        if( !$format['allowed'] )
        {
            $scan['bad_format'] = TRUE;
        }

        // Update gallery data
        $DB->Update('UPDATE `tx_galleries` SET ' .
                    '`date_scanned`=?, ' .
                    '`page_hash`=?, ' .
                    '`format`=?, ' .
                    '`gallery_ip`=?, ' .
                    '`thumbnails`=?, ' .
                    '`has_recip`=? ' .
                    'WHERE `gallery_id`=?',
                    array(MYSQL_NOW,
                          $scan['page_hash'],
                          $scan['format'],
                          $scan['gallery_ip'],
                          $scan['thumbnails'],
                          $scan['has_recip'],
                          $gallery['gallery_id']));
    }

    ArrayHSC($scan);

    // Display the results
    include_once('includes/galleries-quickscan.php');
}

function txShGalleryScanner()
{
    global $DB, $C;

    VerifyAdministrator();

    include_once('includes/galleries-scanner.php');
}

function txShScannerConfigAdd()
{
    global $DB, $C;

    VerifyAdministrator();
    ArrayHSC($_REQUEST);

    include_once('includes/galleries-scanner-add.php');
}

function txShScannerConfigEdit()
{
    global $DB, $C;

    VerifyAdministrator();

    $editing = TRUE;

    // First time, use database information
    if( !$_REQUEST['editing'] || $GLOBALS['added'] )
    {
        $_REQUEST = $DB->Row('SELECT * FROM `tx_scanner_configs` WHERE `config_id`=?', array($_REQUEST['config_id']));
        $_REQUEST = array_merge(unserialize($_REQUEST['configuration']), $_REQUEST);
    }

    ArrayHSC($_REQUEST);

    include_once('includes/galleries-scanner-add.php');
}

function txScannerConfigAdd()
{
    global $DB, $C;

    VerifyAdministrator();

    $v = new Validator();
    $v->Register($_REQUEST['identifier'], V_EMPTY, 'The Identifier field must be filled in');

    if( $_REQUEST['pics_preview_size'] == 'custom' )
    {
        $v->Register($_REQUEST['pics_preview_size_custom'], V_REGEX, 'The custom picture preview size must be in WxH format', '~^\d+x\d+$~');
        $_REQUEST['pics_preview_size'] = $_REQUEST['pics_preview_size_custom'];
    }

    if( $_REQUEST['movies_preview_size'] == 'custom' )
    {
        $v->Register($_REQUEST['movies_preview_size_custom'], V_REGEX, 'The custom movies preview size must be in WxH format', '~^\d+x\d+$~');
        $_REQUEST['movies_preview_size'] = $_REQUEST['movies_preview_size_custom'];
    }

    if( !$v->Validate() )
    {
        return $v->ValidationError('txShScannerConfigAdd');
    }

    $_REQUEST['categories'] = array_unique($_REQUEST['categories']);
    $_REQUEST['sponsors'] = array_unique($_REQUEST['sponsors']);
    $_REQUEST['new_size'] = FormatCommaSeparated($_REQUEST['new_size']);

    // Add scanner configuration to the database
    $DB->Update('INSERT INTO `tx_scanner_configs` VALUES (?,?,?,?,?,?,?)',
                array(NULL,
                      $_REQUEST['identifier'],
                      'Not Running',
                      time(),
                      0,
                      null,
                      serialize($_REQUEST)));

    UpdateThumbSizes();

    $GLOBALS['message'] = 'New scanner configuration successfully added';
    $GLOBALS['added'] = true;
    UnsetArray($_REQUEST);
    txShScannerConfigAdd();
}

function txScannerConfigEdit()
{
    global $DB, $C;

    VerifyAdministrator();

    $v = new Validator();
    $v->Register($_REQUEST['identifier'], V_EMPTY, 'The Identifier field must be filled in');

    if( $_REQUEST['pics_preview_size'] == 'custom' )
    {
        $v->Register($_REQUEST['pics_preview_size_custom'], V_REGEX, 'The custom picture preview size must be in WxH format', '~^\d+x\d+$~');
        $_REQUEST['pics_preview_size'] = $_REQUEST['pics_preview_size_custom'];
    }

    if( $_REQUEST['movies_preview_size'] == 'custom' )
    {
        $v->Register($_REQUEST['movies_preview_size_custom'], V_REGEX, 'The custom movies preview size must be in WxH format', '~^\d+x\d+$~');
        $_REQUEST['movies_preview_size'] = $_REQUEST['movies_preview_size_custom'];
    }

    if( !$v->Validate() )
    {
        return $v->ValidationError('txShScannerConfigEdit');
    }

    $_REQUEST['categories'] = array_unique($_REQUEST['categories']);
    $_REQUEST['sponsors'] = array_unique($_REQUEST['sponsors']);
    $_REQUEST['new_size'] = FormatCommaSeparated($_REQUEST['new_size']);

    // Update scanner configuration to the database
    $DB->Update('UPDATE `tx_scanner_configs` SET ' .
                '`identifier`=?, ' .
                '`configuration`=? ' .
                'WHERE `config_id`=?',
                array($_REQUEST['identifier'],
                      serialize($_REQUEST),
                      $_REQUEST['config_id']));

    UpdateThumbSizes();

    $GLOBALS['message'] = 'Scanner configuration successfully updated';
    $GLOBALS['added'] = true;
    txShScannerConfigEdit();
}

function txShPhpInfo()
{
    global $DB, $C;

    CheckAccessList();
    VerifyAdministrator();

    phpinfo();
}

function txShPartnerFields()
{
    global $DB, $C;

    VerifyAdministrator();

    include_once('includes/partners-fields.php');
}

function txShPartnerFieldAdd()
{
    global $DB, $C, $FIELD_TYPES, $VALIDATION_TYPES;

    VerifyAdministrator();
    ArrayHSC($_REQUEST);

    include_once('includes/partners-fields-add.php');
}

function txShPartnerFieldEdit()
{
    global $DB, $C, $FIELD_TYPES, $VALIDATION_TYPES;

    VerifyAdministrator();

    $editing = TRUE;

    // First time or update, use database information
    if( !$_REQUEST['editing'] || $GLOBALS['added'] )
    {
        $_REQUEST = $DB->Row('SELECT * FROM `tx_partner_field_defs` WHERE `field_id`=?', array($_REQUEST['field_id']));
        $_REQUEST['old_name'] = $_REQUEST['name'];
    }

    ArrayHSC($_REQUEST);

    include_once('includes/partners-fields-add.php');
}

function txPartnerFieldAdd()
{
    global $DB, $C;

    VerifyAdministrator();

    $v =& ValidateUserDefined('tx_partner_field_defs', 'tx_partners');

    if( !$v->Validate() )
    {
        return $v->ValidationError('txShPartnerFieldAdd');
    }

    $_REQUEST['options'] = FormatCommaSeparated($_REQUEST['options']);

    $DB->Update("ALTER TABLE `tx_partner_fields` ADD COLUMN # TEXT", array($_REQUEST['name']));
    $DB->Update('INSERT INTO `tx_partner_field_defs` VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                array(NULL,
                      $_REQUEST['name'],
                      $_REQUEST['label'],
                      $_REQUEST['type'],
                      $_REQUEST['tag_attributes'],
                      $_REQUEST['options'],
                      $_REQUEST['validation'],
                      $_REQUEST['validation_extras'],
                      $_REQUEST['validation_message'],
                      intval($_REQUEST['on_request']),
                      intval($_REQUEST['request_only']),
                      intval($_REQUEST['required_request']),
                      intval($_REQUEST['on_edit']),
                      intval($_REQUEST['required_edit'])));

    $GLOBALS['message'] = 'New partner field successfully added';
    $GLOBALS['added'] = true;

    UnsetArray($_REQUEST);
    txShPartnerFieldAdd();
}

function txPartnerFieldEdit()
{
    global $DB, $C;

    VerifyAdministrator();

    $v =& ValidateUserDefined('tx_partner_field_defs', 'tx_partners', TRUE);

    if( !$v->Validate() )
    {
        return $v->ValidationError('txShPartnerFieldEdit');
    }

    $_REQUEST['options'] = FormatCommaSeparated($_REQUEST['options']);

    if( $_REQUEST['name'] != $_REQUEST['old_name'] )
        $DB->Update("ALTER TABLE `tx_partner_fields` CHANGE # # TEXT", array($_REQUEST['old_name'], $_REQUEST['name']));

    $DB->Update('UPDATE `tx_partner_field_defs` SET ' .
                '`name`=?, ' .
                '`label`=?, ' .
                '`type`=?, ' .
                '`tag_attributes`=?, ' .
                '`options`=?, ' .
                '`validation`=?, ' .
                '`validation_extras`=?, ' .
                '`validation_message`=?, ' .
                '`on_request`=?, ' .
                '`request_only`=?, ' .
                '`required_request`=?, ' .
                '`on_edit`=?, ' .
                '`required_edit`=? ' .
                'WHERE `field_id`=?',
                array($_REQUEST['name'],
                      $_REQUEST['label'],
                      $_REQUEST['type'],
                      $_REQUEST['tag_attributes'],
                      $_REQUEST['options'],
                      $_REQUEST['validation'],
                      $_REQUEST['validation_extras'],
                      $_REQUEST['validation_message'],
                      intval($_REQUEST['on_request']),
                      intval($_REQUEST['request_only']),
                      intval($_REQUEST['required_request']),
                      intval($_REQUEST['on_edit']),
                      intval($_REQUEST['required_edit']),
                      $_REQUEST['field_id']));

    $GLOBALS['message'] = 'Partner field has been successfully updated';
    $GLOBALS['added'] = true;

    txShPartnerFieldEdit();
}

function txShPartnerReview()
{
    global $DB, $C;

    VerifyPrivileges(P_PARTNER);

    include_once('includes/partners-review.php');
}

function txShPartnerInactive()
{
    global $DB, $C;

    VerifyPrivileges(P_PARTNER);

    include_once('includes/partners-inactive.php');
}

function txPartnerMailInactive()
{
    global $DB, $C;

    VerifyPrivileges(P_PARTNER);

    $v = new Validator();
    $v->Register($_REQUEST['inactive'], V_NUMERIC, 'Please enter the number of inactive days as a numeric value');

    if( !$v->Validate() )
    {
        return $v->ValidationError('txShPartnerInactive');
    }

    $t = new Template();
    $t->assign_by_ref('config', $C);
    $t->assign('inactive', $_REQUEST['inactive']);

    $result = $DB->Query('SELECT * FROM `tx_partners` WHERE `date_last_submit` <= DATE_SUB(?, INTERVAL ? DAY) OR (`date_last_submit` IS NULL AND `date_added` <= DATE_SUB(?, INTERVAL ? DAY))',
                         array(MYSQL_NOW,
                               $_REQUEST['inactive'],
                               MYSQL_NOW,
                               $_REQUEST['inactive']));
    $removed = $DB->NumRows($result);
    while( $partner = $DB->NextRow($result) )
    {
        $t->assign_by_ref('partner', $partner);
        SendMail($partner['email'], 'email-partner-inactive.tpl', $t);
    }
    $DB->Free($result);

    $GLOBALS['message'] = "$removed inactive partner account".($removed == 1 ? ' has' : 's have')." been e-mailed";
    txShPartnerInactive();
}

function txPartnerDeleteInactive()
{
    global $DB, $C;

    VerifyPrivileges(P_PARTNER_REMOVE);

    $v = new Validator();
    $v->Register($_REQUEST['inactive'], V_NUMERIC, 'Please enter the number of inactive days as a numeric value');

    if( !$v->Validate() )
    {
        return $v->ValidationError('txShPartnerInactive');
    }

    $result = $DB->Query('SELECT * FROM `tx_partners` WHERE `date_last_submit` <= DATE_SUB(?, INTERVAL ? DAY) OR (`date_last_submit` IS NULL AND `date_added` <= DATE_SUB(?, INTERVAL ? DAY))',
                         array(MYSQL_NOW,
                               $_REQUEST['inactive'],
                               MYSQL_NOW,
                               $_REQUEST['inactive']));
    $removed = $DB->NumRows($result);
    while( $partner = $DB->NextRow($result) )
    {
        DeletePartner($partner['username'], $partner);
    }
    $DB->Free($result);

    $GLOBALS['message'] = "$removed inactive partner account".($removed == 1 ? ' has' : 's have')." been deleted";
    txShPartnerInactive();
}

function txShPartnerSearch()
{
    global $DB, $C;

    VerifyPrivileges(P_PARTNER);

    include_once('includes/partners.php');
}

function txShPartnerAdd()
{
    global $DB, $C;

    VerifyPrivileges(P_PARTNER_ADD);
    ArrayHSC($_REQUEST);

    include_once('includes/partners-add.php');
}

function txShPartnerEdit()
{
    global $DB, $C;

    VerifyPrivileges(P_PARTNER_MODIFY);

    $editing = TRUE;

    // First time, use database information
    if( !$_REQUEST['editing'] || $GLOBALS['added'] )
    {
        $_REQUEST = $DB->Row('SELECT * FROM `tx_partners` JOIN `tx_partner_fields` USING (`username`) WHERE `tx_partners`.`username`=?', array($_REQUEST['username']));

        if( !empty($_REQUEST['categories']) )
        {
            $_REQUEST['categories'] = unserialize($_REQUEST['categories']);
        }

        if( !empty($_REQUEST['domains']) )
        {
            $_REQUEST['domains'] = unserialize($_REQUEST['domains']);
        }


        // Load icons
        $_REQUEST['icons'] = array();
        $result = $DB->Query('SELECT * FROM `tx_partner_icons` WHERE `username`=?', array($_REQUEST['username']));
        while( $icon = $DB->NextRow($result) )
        {
            $_REQUEST['icons'][$icon['icon_id']] = $icon['icon_id'];
        }
        $DB->Free($result);
    }

    unset($_REQUEST['password']);
    ArrayHSC($_REQUEST);

    include_once('includes/partners-add.php');
}

function txShPartnerMail()
{
    global $DB, $C;

    VerifyAdministrator();
    ArrayHSC($_REQUEST);

    if( is_array($_REQUEST['username']) )
    {
        $_REQUEST['to'] = join(', ', $_REQUEST['username']);
        $_REQUEST['to_list'] = join(',', $_REQUEST['username']);
    }

    $function = 'txPartnerMail';
    include_once('includes/email-compose.php');
}

function txPartnerMail()
{
    global $DB, $C, $t;

    VerifyAdministrator();

    if( isset($_REQUEST['to']) )
    {
        $result = $DB->Query('SELECT * FROM `tx_partners` WHERE `username`=?', array($_REQUEST['to']));
    }
    else
    {
        $result = GetWhichPartners();
    }

    $message = PrepareMessage();
    $t = new Template();
    $t->assign_by_ref('config', $C);

    while( $partner = $DB->NextRow($result) )
    {
        $t->assign_by_ref('partner', $partner);
        SendMail($partner['email'], $message, $t, FALSE);
    }

    $message = 'The selected partner accounts have been e-mailed';
    include_once('includes/message.php');
}

function txPartnerAdd()
{
    global $DB, $C;

    VerifyPrivileges(P_PARTNER_ADD);

    $start_or_end_empty = ($_REQUEST['date_start'] && !$_REQUEST['date_end']) || (!$_REQUEST['date_start'] && $_REQUEST['date_end']);
    $v = new Validator();
    $v->Register($_REQUEST['username'], V_LENGTH, 'The username must be between 3 and 32 characters in length', array('min'=>3,'max'=>32));
    $v->Register($_REQUEST['username'], V_ALPHANUM, 'The username can only contain letters and numbers');
    $v->Register($_REQUEST['password'], V_LENGTH, 'The password must contain at least 4 characters', array('min'=>4,'max'=>999));
    $v->Register($_REQUEST['email'], V_EMAIL, 'The e-mail address is not properly formatted');
    $v->Register($_REQUEST['weight'], V_NUMERIC, 'The Weight field must be filled in and numeric');
    $v->Register($_REQUEST['per_day'], V_NUMERIC, 'The Galleries Per Day field must be filled in and numeric');
    $v->Register($_REQUEST['date_start'], V_DATETIME, 'The Start Date field is not properly formatted');
    $v->Register($_REQUEST['date_end'], V_DATETIME, 'The End Date field is not properly formatted');
    $v->Register($start_or_end_empty, V_FALSE, 'Start Date must be provided if End Date is provided, and vice versa');

    if( $DB->Count('SELECT COUNT(*) FROM `tx_partners` WHERE `username`=?', array($_REQUEST['username'])) > 0 )
    {
        $v->SetError('A partner account already exists with that username');
    }

    if( !$v->Validate() )
    {
        return $v->ValidationError('txShPartnerAdd');
    }

    if( !in_array('__ALL__', $_REQUEST['categories']) )
    {
        $_REQUEST['categories'] = serialize($_REQUEST['categories']);
    }
    else
    {
        $_REQUEST['categories'] = null;
    }

    if( is_array($_REQUEST['domains']) && !in_array('__ALL__', $_REQUEST['domains']) )
    {
        $_REQUEST['domains'] = serialize($_REQUEST['domains']);
    }
    else
    {
        $_REQUEST['domains'] = null;
    }


    NullIfEmpty($_REQUEST['date_start']);
    NullIfEmpty($_REQUEST['date_end']);

    // Add account data to the database
    $DB->Update('INSERT INTO `tx_partners` VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                array($_REQUEST['username'],
                      sha1($_REQUEST['password']),
                      $_REQUEST['name'],
                      $_REQUEST['email'],
                      $_SERVER['REMOTE_ADDR'],
                      MYSQL_NOW,
                      null,
                      $_REQUEST['date_start'],
                      $_REQUEST['date_end'],
                      $_REQUEST['per_day'],
                      $_REQUEST['weight'],
                      $_REQUEST['categories'],
                      intval($_REQUEST['categories_as_exclude']),
                      $_REQUEST['domains'],
                      intval($_REQUEST['domains_as_exclude']),
                      0,
                      0,
                      $_REQUEST['status'],
                      null,
                      null,
                      intval($_REQUEST['allow_redirect']),
                      intval($_REQUEST['allow_norecip']),
                      intval($_REQUEST['allow_autoapprove']),
                      intval($_REQUEST['allow_noconfirm']),
                      intval($_REQUEST['allow_blacklist'])));

    // Insert user defined fields
    $insert = CreateUserInsert('tx_partner_fields', $_REQUEST);
    $DB->Update('INSERT INTO `tx_partner_fields` VALUES ('.$insert['bind_list'].')', $insert['binds']);

    // Add icons
    if( is_array($_REQUEST['icons']) )
    {
        foreach( $_REQUEST['icons'] as $icon_id )
        {
            $DB->Update('INSERT INTO `tx_partner_icons` VALUES (?,?)', array($_REQUEST['username'], $icon_id));
        }
    }

    // Send e-mail message
    if( isset($_REQUEST['send_email']) )
    {
        $t = new Template();

        $t->assign_by_ref('partner', $_REQUEST);
        $t->assign_by_ref('config', $C);

        SendMail($_REQUEST['email'], 'email-partner-added.tpl', $t);
    }

    $GLOBALS['message'] = 'New partner successfully added';
    $GLOBALS['added'] = true;
    UnsetArray($_REQUEST);
    txShPartnerAdd();
}

function txPartnerEdit()
{
    global $DB, $C;

    VerifyPrivileges(P_PARTNER_MODIFY);

    $partner = $DB->Row('SELECT * FROM `tx_partners` WHERE `username`=?', array($_REQUEST['username']));

    $start_or_end_empty = ($_REQUEST['date_start'] && !$_REQUEST['date_end']) || (!$_REQUEST['date_start'] && $_REQUEST['date_end']);
    $v = new Validator();
    $v->Register($_REQUEST['email'], V_EMAIL, 'The e-mail address is not properly formatted');
    $v->Register($_REQUEST['email'], V_EMAIL, 'The e-mail address is not properly formatted');
    $v->Register($_REQUEST['weight'], V_NUMERIC, 'The Weight field must be filled in and numeric');
    $v->Register($_REQUEST['per_day'], V_NUMERIC, 'The Galleries Per Day field must be filled in and numeric');
    $v->Register($_REQUEST['date_start'], V_DATETIME, 'The Start Date field is not properly formatted');
    $v->Register($_REQUEST['date_end'], V_DATETIME, 'The End Date field is not properly formatted');
    $v->Register($start_or_end_empty, V_FALSE, 'Start Date must be provided if End Date is provided, and vice versa');
    if( $_REQUEST['password'] )
    {
        $v->Register($_REQUEST['password'], V_LENGTH, 'The password must contain at least 4 characters', array('min'=>4,'max'=>999));
    }

    if( !$v->Validate() )
    {
        return $v->ValidationError('txShPartnerEdit');
    }

    if( $_REQUEST['password'] )
    {
        // Password has changed, so invalidate any current session that may be active
        $DB->Update('UPDATE `tx_partners` SET `session`=NULL,`session_start`=NULL WHERE `username`=?', array($_REQUEST['username']));
        $_REQUEST['password'] = sha1($_REQUEST['password']);
    }
    else
    {
        $_REQUEST['password'] = $partner['password'];
    }

    if( !in_array('__ALL__', $_REQUEST['categories']) )
    {
        $_REQUEST['categories'] = serialize($_REQUEST['categories']);
    }
    else
    {
        $_REQUEST['categories'] = null;
    }

    if( !in_array('__ALL__', $_REQUEST['domains']) )
    {
        $_REQUEST['domains'] = serialize($_REQUEST['domains']);
    }
    else
    {
        $_REQUEST['domains'] = null;
    }


    NullIfEmpty($_REQUEST['date_start']);
    NullIfEmpty($_REQUEST['date_end']);

    // Update account information
    $DB->Update('UPDATE `tx_partners` SET ' .
                '`password`=?, ' .
                '`name`=?, ' .
                '`email`=?, ' .
                '`date_start`=?, ' .
                '`date_end`=?, ' .
                '`per_day`=?, ' .
                '`weight`=?, ' .
                '`categories`=?, ' .
                '`categories_as_exclude`=?, ' .
                '`domains`=?, ' .
                '`domains_as_exclude`=?, ' .
                '`status`=?, ' .
                '`allow_redirect`=?, ' .
                '`allow_norecip`=?, ' .
                '`allow_autoapprove`=?, ' .
                '`allow_noconfirm`=?, ' .
                '`allow_blacklist`=? ' .
                'WHERE `username`=?',
                array($_REQUEST['password'],
                      $_REQUEST['name'],
                      $_REQUEST['email'],
                      $_REQUEST['date_start'],
                      $_REQUEST['date_end'],
                      $_REQUEST['per_day'],
                      $_REQUEST['weight'],
                      $_REQUEST['categories'],
                      intval($_REQUEST['categories_as_exclude']),
                      $_REQUEST['domains'],
                      intval($_REQUEST['domains_as_exclude']),
                      $_REQUEST['status'],
                      $_REQUEST['allow_redirect'],
                      $_REQUEST['allow_norecip'],
                      $_REQUEST['allow_autoapprove'],
                      $_REQUEST['allow_noconfirm'],
                      $_REQUEST['allow_blacklist'],
                      $_REQUEST['username']));

    // Update user defined fields
    UserDefinedUpdate('tx_partner_fields', 'tx_partner_field_defs', 'username', $_REQUEST['username'], $_REQUEST);

    // Update icons
    $DB->Update('DELETE FROM `tx_partner_icons` WHERE `username`=?', array($_REQUEST['username']));
    if( is_array($_REQUEST['icons']) )
    {
        foreach( $_REQUEST['icons'] as $icon_id )
        {
            $DB->Update('INSERT INTO `tx_partner_icons` VALUES (?,?)', array($_REQUEST['username'], $icon_id));
        }
    }

    // Reactivate galleries if this account is being reactivated
    if( $_REQUEST['status'] == 'active' && $partner['status'] == 'disabled' )
    {
        $DB->Update('UPDATE `tx_galleries` SET `status`=`previous_status`,`previous_status`=NULL WHERE `status`=? AND `partner`=?', array('disabled',$_REQUEST['username']));
    }

    // Disable galleries if this account is being suspended
    else if( $_REQUEST['status'] == 'suspended' )
    {
        $DB->Update('UPDATE `tx_galleries` SET `previous_status`=`status`,`status`=? WHERE `status`!=? AND `partner`=?', array('disabled','disabled',$_REQUEST['username']));
    }

    $GLOBALS['message'] = 'Partner account successfully updated';
    $GLOBALS['added'] = true;
    txShPartnerEdit();
}

function txShIcons()
{
    global $DB, $C;

    VerifyAdministrator();

    include_once('includes/icons.php');
}

function txShIconAdd()
{
    global $C, $DB;

    VerifyAdministrator();
    ArrayHSC($_REQUEST);

    include_once('includes/icons-add.php');
}

function txShIconEdit()
{
    global $C, $DB;

    VerifyAdministrator();

    // First time or update, use database information
    if( !$_REQUEST['editing'] || $GLOBALS['added'] )
    {
        $_REQUEST = $DB->Row('SELECT * FROM `tx_icons` WHERE `icon_id`=?', array($_REQUEST['icon_id']));
    }

    ArrayHSC($_REQUEST);

    $editing = TRUE;

    include_once('includes/icons-add.php');
}

function txIconAdd()
{
    global $C, $DB;

    VerifyAdministrator();

    $v = new Validator();
    $v->Register($_REQUEST['identifier'], V_EMPTY, 'The Identifier field must be filled in');
    $v->Register($_REQUEST['icon_html'], V_EMPTY, 'The Icon HTML field must be filled in');

    if( !$v->Validate() )
    {
        return $v->ValidationError('txShIconAdd');
    }

    $DB->Update('INSERT INTO `tx_icons` VALUES (?,?,?)',
                array(null,
                      $_REQUEST['identifier'],
                      $_REQUEST['icon_html']));

    $GLOBALS['message'] = 'New icon has been successfully added';
    $GLOBALS['added'] = true;
    UnsetArray($_REQUEST);
    txShIconAdd();
}

function txIconEdit()
{
    global $C, $DB;

    VerifyAdministrator();

    $v = new Validator();
    $v->Register($_REQUEST['identifier'], V_EMPTY, 'The Identifier field must be filled in');
    $v->Register($_REQUEST['icon_html'], V_EMPTY, 'The Icon HTML field must be filled in');

    if( !$v->Validate() )
    {
        return $v->ValidationError('txShIconEdit');
    }

    $DB->Update('UPDATE `tx_icons` SET ' .
                '`identifier`=?, ' .
                '`icon_html`=? ' .
                'WHERE `icon_id`=?',
                array($_REQUEST['identifier'],
                      $_REQUEST['icon_html'],
                      $_REQUEST['icon_id']));

    $GLOBALS['message'] = 'Icon has been successfully updated';
    $GLOBALS['added'] = true;

    txShIconEdit();
}

function txShSubmitterMail()
{
    global $DB, $C;

    VerifyAdministrator(P_GALLERY);

    if( is_array($_REQUEST['gallery_id']) )
    {
        $_REQUEST['to'] = $DB->Count('SELECT `email` FROM `tx_galleries` WHERE `gallery_id`=?', array($_REQUEST['gallery_id'][0]));
        $_REQUEST['to_list'] = $_REQUEST['gallery_id'][0];
    }

    ArrayHSC($_REQUEST);

    $function = 'txSubmitterMail';
    include_once('includes/email-compose.php');
}

function txSubmitterMail()
{
    global $DB, $C, $t;

    VerifyAdministrator(P_GALLERY);

    if( isset($_REQUEST['to']) )
    {
        $result = $DB->Query('SELECT * FROM `tx_galleries` WHERE `gallery_id`=?', array($_REQUEST['to']));
    }
    else
    {
        $result = GetWhichGalleries();
    }

    $message = PrepareMessage();
    $t = new Template();
    $t->assign_by_ref('config', $C);

    while( $gallery = $DB->NextRow($result) )
    {
        $t->assign_by_ref('gallery', $gallery);
        SendMail($gallery['email'], $message, $t, FALSE);
    }

    $DB->Free($result);

    $message = 'The selected gallery submitters have been e-mailed';
    include_once('includes/message.php');
}

function txShGalleryExport()
{
    global $C, $DB;

    VerifyPrivileges(P_GALLERY);

    include_once('includes/galleries-export.php');
}

function txGalleryExport()
{
    global $DB, $C;

    VerifyAdministrator(P_GALLERY);

    $message = DoGalleryExport($_REQUEST);

    include_once('includes/message.php');
}

function txShGalleryEditBulk()
{
    global $C, $DB;

    VerifyPrivileges(P_GALLERY_MODIFY);

    include_once('includes/galleries-edit-bulk.php');
}

function txShGallerySearch()
{
    global $C, $DB;

    VerifyPrivileges(P_GALLERY);

    include_once('includes/galleries-search.php');
}

function txShRssFeeds()
{
    global $C, $DB;

    VerifyPrivileges(P_GALLERY_ADD);

    include_once('includes/rss-feeds.php');
}

function txShRssFeedEdit()
{
    global $DB, $C;

    VerifyAdministrator();

    $editing = TRUE;

    // First time, use database information
    if( !$_REQUEST['editing'] || $_REQUEST['added'] )
    {
        $_REQUEST = $DB->Row('SELECT * FROM `tx_rss_feeds` WHERE `feed_id`=?', array($_REQUEST['feed_id']));
        $_REQUEST['settings'] = unserialize($_REQUEST['settings']);
    }

    ArrayHSC($_REQUEST);

    include_once('includes/rss-feeds-add.php');
}

function txShRssFeedAdd()
{
    global $DB, $C;

    VerifyAdministrator();
    ArrayHSC($_REQUEST);

    include_once('includes/rss-feeds-add.php');
}

function txRssFeedAdd()
{
    global $C, $DB;

    VerifyAdministrator();

    $v = new Validator();

    $v->Register($_REQUEST['feed_url'], V_URL, 'The RSS Feed URL is missing or not properly formatted');
    $v->Register($_REQUEST['settings']['gallery_url_from'], V_EMPTY, 'You must select the XML tag from which the gallery URL will be extracted');

    if( $_REQUEST['sponsor_id'] == '__NEW__' )
    {
        $v->Register($_REQUEST['sponsor_name'], V_EMPTY, 'Please enter a name for the new sponsor, or select NONE');

        if( $DB->Count('SELECT COUNT(*) FROM `tx_sponsors` WHERE `name`=?', array($_REQUEST['sponsor_name'])) )
        {
            $v->SetError("A sponsor with the name '{$_REQUEST['sponsor_name']}' already exists");
        }
    }

    if( !$v->Validate() )
    {
        return $v->ValidationError('txShRssFeedAdd');
    }

    if( $_REQUEST['sponsor_id'] == '__NEW__' )
    {
        list($name, $url) = explode('|', $_REQUEST['sponsor_name']);
        $DB->Update('INSERT INTO `tx_sponsors` VALUES (?,?,?)',
                    array(null,
                          $name,
                          $url));

        $_REQUEST['sponsor_id'] = $DB->InsertID();
    }

    NullIfEmpty($_REQUEST['sponsor_id']);

    $DB->Update('INSERT INTO `tx_rss_feeds` VALUES (?,?,?,?,?)',
                array(null,
                      $_REQUEST['feed_url'],
                      null,
                      $_REQUEST['sponsor_id'],
                      serialize($_REQUEST['settings'])));

    $GLOBALS['message'] = 'New RSS feed successfully added';
    $GLOBALS['added'] = true;
    UnsetArray($_REQUEST);
    txShRssFeedAdd();
}

function txRssFeedEdit()
{
    global $C, $DB;

    VerifyAdministrator();

    $v = new Validator();

    $v->Register($_REQUEST['feed_url'], V_URL, 'The RSS Feed URL is missing or not properly formatted');

    if( $_REQUEST['sponsor_id'] == '__NEW__' )
    {
        $v->Register($_REQUEST['sponsor_name'], V_EMPTY, 'Please enter a name for the new sponsor, or select NONE');

        if( $DB->Count('SELECT COUNT(*) FROM `tx_sponsors` WHERE `name`=?', array($_REQUEST['sponsor_name'])) )
        {
            $v->SetError("A sponsor with the name '{$_REQUEST['sponsor_name']}' already exists");
        }
    }

    if( !$v->Validate() )
    {
        return $v->ValidationError('txShRssFeedEdit');
    }

    if( $_REQUEST['sponsor_id'] == '__NEW__' )
    {
        list($name, $url) = explode('|', $_REQUEST['sponsor_name']);
        $DB->Update('INSERT INTO `tx_sponsors` VALUES (?,?,?)',
                    array(null,
                          $name,
                          $url));

        $_REQUEST['sponsor_id'] = $DB->InsertID();
    }

    NullIfEmpty($_REQUEST['sponsor_id']);

    $DB->Update('UPDATE `tx_rss_feeds` SET ' .
                '`feed_url`=?, ' .
                '`sponsor_id`=?, ' .
                '`settings`=? ' .
                'WHERE `feed_id`=?',
                array($_REQUEST['feed_url'],
                      $_REQUEST['sponsor_id'],
                      serialize($_REQUEST['settings']),
                      $_REQUEST['feed_id']));

    $GLOBALS['message'] = 'The RSS feed has been successfully updated';
    $GLOBALS['added'] = true;
    txShRssFeedEdit();
}

function txShGalleryImport()
{
    global $C, $DB;

    VerifyPrivileges(P_GALLERY_ADD);

    include_once('includes/galleries-import.php');
}

function txGalleryImport()
{
    global $DB, $C;

    VerifyPrivileges(P_GALLERY_ADD);

    $defaults = array('gallery_url' => null,
                      'description' => null,
                      'keywords' => null,
                      'thumbnails' => 0,
                      'email' => $C['from_email'],
                      'nickname' => null,
                      'weight' => $C['gallery_weight'],
                      'clicks' => 0,
                      'submit_ip' => $_SERVER['REMOTE_ADDR'],
                      'gallery_ip' => '',
                      'sponsor_id' => !empty($_REQUEST['sponsor']) ? $_REQUEST['sponsor'] : null,
                      'type' => $_REQUEST['type'],
                      'format' => $_REQUEST['format'],
                      'status' => $_REQUEST['status'],
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
                      'categories' => null,
                      'preview_url' => null,
                      'dimensions' => null);

    $v = new Validator();

    if( empty($_REQUEST['type']) && !in_array('type', $_REQUEST['fields']) )
    {
        $v->SetError('You indicated that the gallery type should come from the import data, but that field has not been defined');
    }

    if( empty($_REQUEST['format']) && !in_array('format', $_REQUEST['fields']) )
    {
        $v->SetError('You indicated that the gallery format should come from the import data, but that field has not been defined');
    }

    // Make sure only one of each field is submitted
    $field_counts = array_count_values($_REQUEST['fields']);
    foreach( $field_counts as $field_name => $field_count )
    {
        if( $field_name != 'IGNORE' && $field_count > 1 )
        {
            $v->SetError("The $field_name field has been specified more than once");
        }
    }

    if( !$v->Validate() )
    {
        return $v->ValidationError('txShGalleryImportAnalyze');
    }


    // Create/empty log files for skipped galleries
    FileWrite("{$GLOBALS['BASE_DIR']}/data/skipped-cat.txt", '');
    FileWrite("{$GLOBALS['BASE_DIR']}/data/skipped-dupe.txt", '');

    // Initialize variables
    $imported = 0;
    $duplicates = 0;
    $no_matching_cat = 0;
    $lines = file(SafeFilename("{$GLOBALS['BASE_DIR']}/data/{$_REQUEST['filename']}"));
    $sponsors =& $DB->FetchAll('SELECT * FROM `tx_sponsors`', null, 'name');
    $partners = array();
    $columns = $DB->GetColumns('tx_gallery_fields');

    foreach( $lines as $line_number => $line )
    {
        $line_number++;
        $line = trim($line);

        if( IsEmptyString($line) )
        {
            continue;
        }

        $data = explode('|', $line);
        $gallery = array();

        foreach( $_REQUEST['fields'] as $index => $field )
        {
            $gallery[$field] = trim($data[$index]);
        }

        $gallery = array_merge($defaults, $gallery);

        // Check for and handle duplicates
        $dupes =& $DB->FetchAll('SELECT `gallery_id` FROM `tx_galleries` WHERE `gallery_url`=?', array($gallery['gallery_url']));
        if( count($dupes) > 0 )
        {
            switch($_REQUEST['duplicates'])
            {
                case 'replace':
                    // Remove existing so it can be replaced with new
                    foreach( $dupes as $dupe )
                    {
                        DeleteGallery($dupe['gallery_id']);
                    }
                    break;

                case 'allow':
                    // Allow duplicate galleries, so do nothing here
                    break;

                default:
                    // Go to next line if this is a duplicate
                    FileAppend("{$GLOBALS['BASE_DIR']}/data/skipped-dupe.txt", sprintf("%-6d %s", $line_number, $line));
                    $duplicates++;
                    continue 2;
                    break;
            }
        }


        // Check for valid categories
        $skipped = array();
        $category_tags = CategoryTagsFromList($gallery['categories'], $skipped);
        if( $category_tags == MIXED_CATEGORY )
        {
            switch( $_REQUEST['bad_category'] )
            {
                case 'create':
                {
                    if( IsEmptyString($gallery['categories']) )
                    {
                        FileAppend("{$GLOBALS['BASE_DIR']}/data/skipped-cat.txt", sprintf("%-6d %s", $line_number, $line));
                        $no_matching_cat++;
                        continue 2;
                    }

                    $category_tags = CreateCategories($gallery['categories']);
                }
                break;

                case 'force':
                {
                    $category_tags = MIXED_CATEGORY . " " . $_REQUEST['forced_category'];
                }
                break;

                default:
                {
                    FileAppend("{$GLOBALS['BASE_DIR']}/data/skipped-cat.txt", sprintf("%-6d %s", $line_number, $line));
                    $no_matching_cat++;
                    continue 2;
                }
                break;
            }
        }

        if( count($skipped) && $_REQUEST['bad_category'] == 'create' )
        {
            $category_tags = join(' ', array_unique(array($category_tags, CreateCategories($gallery['categories']))));
        }

        // Setup the sponsor
        if( empty($_REQUEST['sponsor']) && $_REQUEST['add_sponsor'] && $gallery['sponsor_id'] != null && !isset($sponsors[$gallery['sponsor_id']]) )
        {
            $DB->Update('INSERT INTO `tx_sponsors` VALUES (?,?,?)', array(null, $gallery['sponsor_id'], null));
            $sponsors[$gallery['sponsor_id']]['sponsor_id'] = $DB->InsertID();
            $gallery['sponsor_id'] = $sponsors[$gallery['sponsor_id']]['sponsor_id'];
        }
        else if( empty($_REQUEST['sponsor']) && $gallery['sponsor_id'] != null && isset($sponsors[$gallery['sponsor_id']]) )
        {
            $gallery['sponsor_id'] = $sponsors[$gallery['sponsor_id']]['sponsor_id'];
        }


        // Check for valid format
        $gallery['format'] = strtolower($gallery['format']);
        if( !in_array($gallery['format'], array('pictures','movies')) )
        {
            $gallery['format'] = 'pictures';
        }

        // Check for valid type
        $gallery['type'] = strtolower($gallery['type']);
        if( !in_array($gallery['type'], array('submitted','permanent')) )
        {
            $gallery['type'] = 'submitted';
        }

        // Check date scheduled for errors
        if( $gallery['date_scheduled'] != null && !preg_match(RE_DATETIME, $gallery['date_scheduled']) )
        {
            $gallery['date_scheduled'] = null;
        }

        // Check date of deletion for errors
        if( $gallery['date_deletion'] != null && !preg_match(RE_DATETIME, $gallery['date_deletion']) )
        {
            $gallery['date_deletion'] = null;
        }

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
                          FormatSpaceSeparated($gallery['keywords']),
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
                          FormatSpaceSeparated($gallery['tags']),
                          $category_tags));

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


        // Add icons
        if( !IsEmptyString($gallery['icons']) )
        {
            foreach( explode(',', $gallery['icons']) as $icon )
            {
                $icon = trim($icon);

                if( !empty($icon) )
                {
                    $icon_id = $DB->Count('SELECT `icon_id` FROM `tx_icons` WHERE `identifier`=?', array($icon));

                    if( $icon_id )
                    {
                        $DB->Update('INSERT INTO `tx_gallery_icons` VALUES (?,?)', array($gallery['gallery_id'], $icon_id));
                    }
                }
            }
        }

        if( !empty($gallery['partner']) )
        {
            $partners[$gallery['partner']]++;

            // Add partner icons
            $partner_icons =& $DB->FetchAll('SELECT * FROM `tx_partner_icons` WHERE `username`=?', array($gallery['partner']));
            foreach( $partner_icons as $icon )
            {
                $DB->Update('REPLACE INTO `tx_gallery_icons` VALUES (?,?)', array($gallery['gallery_id'], $icon['icon_id']));
            }
        }

        $imported++;
    }

    StoreValue('last_import', serialize($_REQUEST['fields']));


    // Update partner submit counts
    foreach( $partners as $username => $amount )
    {
        $DB->Update('UPDATE `tx_partners` SET `submitted`=`submitted`+? WHERE `username`=?', array($amount, $username));
    }

    $GLOBALS['message'] = "A total of $imported galleries have been imported<br />" .
                          "<a href=\"index.php?r=txShSkippedImport&type=dupe\" class=\"window {title: 'Skipped Galleries'}\">$duplicates galleries</a> were skipped because they were duplicates<br />" .
                          "<a href=\"index.php?r=txShSkippedImport&type=cat\" class=\"window {title: 'Skipped Galleries'}\">$no_matching_cat galleries</a> were skipped because they did not fit into an existing category";

    txShGalleryImport();
}

function txShSkippedImport()
{
    global $DB, $C;

    VerifyPrivileges(P_GALLERY_ADD);

    $files = array('cat' => "{$GLOBALS['BASE_DIR']}/data/skipped-cat.txt",
                   'dupe' => "{$GLOBALS['BASE_DIR']}/data/skipped-dupe.txt");

    if( isset($files[$_REQUEST['type']]) )
    {
        include_once('includes/galleries-import-skipped.php');
    }
    else
    {
        $error = 'Invalid view type';
        include_once('includes/error.php');
    }
}

function txShGalleryImportAnalyze()
{
    global $DB, $C;

    VerifyPrivileges(P_GALLERY_ADD);

    if( !isset($_REQUEST['analyzed']) )
    {
        $v = new Validator();

        if( $_REQUEST['type'] == 'file' )
        {
            $v->Register(is_file("{$GLOBALS['BASE_DIR']}/data/import.txt"), V_TRUE, 'The file import.txt has not been uploaded to the data directory');
        }
        else
        {
            $v->Register($_REQUEST['input'], V_EMPTY, 'You must supply some import data in the text input box');
        }

        if( !$v->Validate() )
        {
            return $v->ValidationError('txShGalleryImport');
        }

        // Setup file for analysis
        $filename = 'import.txt';
        if( $_REQUEST['type'] == 'input' )
        {
            FileWrite("{$GLOBALS['BASE_DIR']}/data/temp-import.txt", $_REQUEST['input']);
            $filename = 'temp-import.txt';
        }
    }
    else
    {
        $filename = $_REQUEST['filename'];
    }

    include_once('includes/galleries-import-analyze.php');
}

function txShGalleryAdd()
{
    global $C, $DB;

    VerifyPrivileges(P_GALLERY_ADD);
    ArrayHSC($_REQUEST);

    include_once('includes/galleries-add.php');
}

function txShGalleryEdit()
{
    global $C, $DB;

    VerifyAdministrator();

    // First time or update, use database information
    if( !$_REQUEST['editing'] || $GLOBALS['added'] )
    {
        // Get gallery data
        $_REQUEST = $DB->Row('SELECT * FROM `tx_galleries` JOIN `tx_gallery_fields` USING (`gallery_id`) WHERE `tx_galleries`.`gallery_id`=?', array($_REQUEST['gallery_id']));

        // Load categories
        $categories = array();
        foreach( explode(' ', $_REQUEST['categories']) as $category )
        {
            if( $category != MIXED_CATEGORY )
            {
                $categories[] = $DB->Count('SELECT `category_id` FROM `tx_categories` WHERE `tag`=?', array($category));
            }
        }
        $_REQUEST['categories'] = $categories;

        // Load icons
        $_REQUEST['icons'] = array();
        $result = $DB->Query('SELECT * FROM `tx_gallery_icons` WHERE `gallery_id`=?', array($_REQUEST['gallery_id']));
        while( $icon = $DB->NextRow($result) )
        {
            $_REQUEST['icons'][$icon['icon_id']] = $icon['icon_id'];
        }
        $DB->Free($result);
    }

    ArrayHSC($_REQUEST);

    $editing = TRUE;

    include_once('includes/galleries-add.php');
}

function txGalleryAdd()
{
    global $DB, $C;

    VerifyPrivileges(P_GALLERY_ADD);

    $v = new Validator();
    $v->Register($_REQUEST['email'], V_EMAIL, 'The E-mail Address is not properly formatted');
    $v->Register($_REQUEST['gallery_url'], V_URL, 'The Gallery URL is not properly formatted');
    $v->Register($_REQUEST['date_scheduled'], V_DATETIME, 'The Scheduled Date is not properly formatted');
    $v->Register($_REQUEST['date_deletion'], V_DATETIME, 'The Delete Date is not properly formatted');

    if( $_REQUEST['status'] == 'used' || $_REQUEST['status'] == 'holding' )
    {
        $v->Register($_REQUEST['date_displayed'], V_EMPTY, 'The Displayed Date must be filled in');
        $v->Register($_REQUEST['date_displayed'], V_DATETIME, 'The Displayed Date is not properly formatted');
    }

    if( !IsEmptyString($_REQUEST['partner']) )
    {
        $partner = $DB->Row('SELECT * FROM `tx_partners` WHERE `username`=?', array($_REQUEST['partner']));

        if( !$partner )
        {
            $v->SetError('The Partner username you entered does not match an existing partner account');
        }
    }

    // Check tags for proper format
    if( !IsEmptyString($_REQUEST['tags']) )
    {
        $_REQUEST['tags'] = FormatSpaceSeparated($_REQUEST['tags']);
        foreach( explode(' ', $_REQUEST['tags']) as $tag )
        {
            if( strlen($tag) < 4 || !preg_match('~^[a-z0-9_]+$~i', $tag) )
            {
                $v->SetError('All tags must be at least 4 characters in length and contain only letters, numbers, and underscores');
                break;
            }
        }
    }

    if( !$v->Validate() )
    {
        return $v->ValidationError('txShGalleryAdd');
    }


    // Get the primary category (first one selected)
    $category = $DB->Row('SELECT * FROM `tx_categories` WHERE `category_id`=?', array($_REQUEST['categories'][0]));

    // Check if whitelisted
    $whitelisted = MergeWhitelistOptions(CheckWhitelist($_REQUEST), $partner);

    // Scan gallery
    $scan =& ScanGallery($_REQUEST, $category, $whitelisted);

    // If approved, set date approved
    $date_approved = null;
    if( $_REQUEST['status'] == 'approved' )
    {
        $date_approved = MYSQL_NOW;
        $DB->Update('UPDATE `tx_administrators` SET `approved`=`approved`+1 WHERE `username`=?', array($_SERVER['REMOTE_USER']));
    }

    NullIfEmpty($_REQUEST['date_scheduled']);
    NullIfEmpty($_REQUEST['date_displayed']);
    NullIfEmpty($_REQUEST['date_deletion']);

    // Add gallery data to the database
    $DB->Update('INSERT INTO `tx_galleries` VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                array(null,
                      $_REQUEST['gallery_url'],
                      $_REQUEST['description'],
                      FormatSpaceSeparated($_REQUEST['keywords']),
                      (IsEmptyString($_REQUEST['thumbnails']) ? $scan['thumbnails'] : $_REQUEST['thumbnails']),
                      $_REQUEST['email'],
                      $_REQUEST['nickname'],
                      $_REQUEST['weight'],
                      $_REQUEST['clicks'],
                      $_REQUEST['submit_ip'],
                      $scan['gallery_ip'],
                      $_REQUEST['sponsor_id'],
                      $_REQUEST['type'],
                      (IsEmptyString($_REQUEST['format']) ? $scan['format'] : $_REQUEST['format']),
                      $_REQUEST['status'],
                      null,
                      MYSQL_NOW,
                      MYSQL_NOW,
                      $date_approved,
                      $_REQUEST['date_scheduled'],
                      $_REQUEST['date_displayed'],
                      $_REQUEST['date_deletion'],
                      $_REQUEST['partner'],
                      $_SERVER['REMOTE_USER'],
                      $_REQUEST['admin_comments'],
                      $scan['page_hash'],
                      0,
                      intval($scan['has_recip']),
                      intval($_REQUEST['allow_scan']),
                      intval($_REQUEST['allow_preview']),
                      0,
                      1,
                      1,
                      FormatSpaceSeparated($_REQUEST['tags']),
                      CategoryTagsFromIds($_REQUEST['categories'])));

    // Add user defined fields
    $_REQUEST['gallery_id'] = $DB->InsertID();
    $query_data = CreateUserInsert('tx_gallery_fields', $_REQUEST);
    $DB->Update('INSERT INTO `tx_gallery_fields` VALUES ('.$query_data['bind_list'].')', $query_data['binds']);

    // Add icons
    if( is_array($_REQUEST['icons']) )
    {
        foreach( $_REQUEST['icons'] as $icon_id )
        {
            $DB->Update('INSERT INTO `tx_gallery_icons` VALUES (?,?)', array($_REQUEST['gallery_id'], $icon_id));
        }
    }

    // Update partner submit count, if applicable
    if( isset($_REQUEST['partner']) )
    {
        $DB->Update('UPDATE `tx_partners` SET `submitted`=`submitted`+1 WHERE `username`=?', array($_REQUEST['partner']));
    }


    // Warn that the gallery URL is not working
    if( !$scan['success'] )
    {
        $GLOBALS['warn'][] = 'The gallery URL does not seem to be working: ' . $scan['errstr'];
    }

    // Warn that the gallery has no thumbs
    if( $scan['thumbnails'] < 1 )
    {
        $GLOBALS['warn'][] = 'No thumbnails could be found on the gallery';
    }

    $GLOBALS['message'] = 'New gallery successfully added';
    $GLOBALS['added'] = true;
    UnsetArray($_REQUEST);
    txShGalleryAdd();
}

function txGalleryEdit()
{
    global $DB, $C;

    VerifyPrivileges(P_GALLERY_MODIFY);

    $v = new Validator();
    $v->Register($_REQUEST['email'], V_EMAIL, 'The E-mail Address is not properly formatted');
    $v->Register($_REQUEST['gallery_url'], V_URL, 'The Gallery URL is not properly formatted');
    $v->Register($_REQUEST['date_scheduled'], V_DATETIME, 'The Scheduled Date is not properly formatted');
    $v->Register($_REQUEST['date_deletion'], V_DATETIME, 'The Delete Date is not properly formatted');

    if( $_REQUEST['status'] == 'used' || $_REQUEST['status'] == 'holding' )
    {
        $v->Register($_REQUEST['date_displayed'], V_EMPTY, 'The Displayed Date must be filled in');
        $v->Register($_REQUEST['date_displayed'], V_DATETIME, 'The Displayed Date is not properly formatted');
    }

    if( !IsEmptyString($_REQUEST['partner']) )
    {
        $partner = $DB->Row('SELECT * FROM `tx_partners` WHERE `username`=?', array($_REQUEST['partner']));

        if( !$partner )
        {
            $v->SetError('The Partner username you entered does not match an existing partner account');
        }
    }

    // Check tags for proper format
    if( !IsEmptyString($_REQUEST['tags']) )
    {
        $_REQUEST['tags'] = FormatSpaceSeparated($_REQUEST['tags']);
        foreach( explode(' ', $_REQUEST['tags']) as $tag )
        {
            if( strlen($tag) < 4 || !preg_match('~^[a-z0-9_]+$~i', $tag) )
            {
                $v->SetError('All tags must be at least 4 characters in length and contain only letters, numbers, and underscores');
                break;
            }
        }
    }

    if( !$v->Validate() )
    {
        return $v->ValidationError('txShGalleryEdit');
    }

    NullIfEmpty($_REQUEST['date_scheduled']);
    NullIfEmpty($_REQUEST['date_displayed']);
    NullIfEmpty($_REQUEST['date_deletion']);

    // Update gallery data
    $DB->Update('UPDATE `tx_galleries` SET ' .
                '`gallery_url`=?, ' .
                '`description`=?, ' .
                '`keywords`=?, ' .
                '`thumbnails`=?, ' .
                '`email`=?, ' .
                '`nickname`=?, ' .
                '`weight`=?, ' .
                '`clicks`=?, ' .
                '`submit_ip`=?, ' .
                '`sponsor_id`=?, ' .
                '`type`=?, ' .
                '`format`=?, ' .
                '`status`=?, ' .
                '`date_scheduled`=?, ' .
                '`date_displayed`=?, ' .
                '`date_deletion`=?, ' .
                '`partner`=?, ' .
                '`allow_scan`=?, ' .
                '`allow_preview`=?, ' .
                '`tags`=?, ' .
                '`categories`=? ' .
                'WHERE `gallery_id`=?',
                array($_REQUEST['gallery_url'],
                      $_REQUEST['description'],
                      FormatSpaceSeparated($_REQUEST['keywords']),
                      $_REQUEST['thumbnails'],
                      $_REQUEST['email'],
                      $_REQUEST['nickname'],
                      $_REQUEST['weight'],
                      $_REQUEST['clicks'],
                      $_REQUEST['submit_ip'],
                      $_REQUEST['sponsor_id'],
                      $_REQUEST['type'],
                      $_REQUEST['format'],
                      $_REQUEST['status'],
                      $_REQUEST['date_scheduled'],
                      $_REQUEST['date_displayed'],
                      $_REQUEST['date_deletion'],
                      $_REQUEST['partner'],
                      intval($_REQUEST['allow_scan']),
                      intval($_REQUEST['allow_preview']),
                      FormatSpaceSeparated($_REQUEST['tags']),
                      CategoryTagsFromIds($_REQUEST['categories']),
                      $_REQUEST['gallery_id']));

    // Update user defined fields
    UserDefinedUpdate('tx_gallery_fields', 'tx_gallery_field_defs', 'gallery_id', $_REQUEST['gallery_id'], $_REQUEST);

    // Update icons
    $DB->Update('DELETE FROM `tx_gallery_icons` WHERE `gallery_id`=?', array($_REQUEST['gallery_id']));
    if( is_array($_REQUEST['icons']) )
    {
        foreach( $_REQUEST['icons'] as $icon_id )
        {
            $DB->Update('INSERT INTO `tx_gallery_icons` VALUES (?,?)', array($_REQUEST['gallery_id'], $icon_id));
        }
    }


    $GLOBALS['message'] = 'Gallery successfully updated';
    $GLOBALS['added'] = true;
    txShGalleryEdit();
}

function txShSponsors()
{
    global $DB, $C;

    VerifyAdministrator();

    include_once('includes/sponsors.php');
}

function txShSponsorAdd()
{
    global $C, $DB;

    VerifyAdministrator();
    ArrayHSC($_REQUEST);

    include_once('includes/sponsors-add.php');
}

function txShSponsorEdit()
{
    global $C, $DB;

    VerifyAdministrator();

    // First time or update, use database information
    if( !$_REQUEST['editing'] || $GLOBALS['added'] )
    {
        $_REQUEST = $DB->Row('SELECT * FROM `tx_sponsors` WHERE `sponsor_id`=?', array($_REQUEST['sponsor_id']));
    }

    ArrayHSC($_REQUEST);

    $editing = TRUE;

    include_once('includes/sponsors-add.php');
}

function txSponsorAdd()
{
    global $DB, $C;

    VerifyAdministrator();

    $v = new Validator();
    $v->Register($_REQUEST['name'], V_EMPTY, 'The Name(s) field must be filled in');

    if( strpos($_REQUEST['name'], ',') !== FALSE )
    {
        $v->SetError('Sponsor names may not contain commas');
    }

    foreach( explode("\n", $_REQUEST['name']) as $name )
    {
        $name = trim($name);

        if( preg_match('~^-~', $name) )
        {
            $v->SetError('Sponsor names cannot start with a dash (-) character');
        }
    }

    if( !$v->Validate() )
    {
        return $v->ValidationError('txShSponsorAdd');
    }

    UnixFormat($_REQUEST['name']);
    $added = 0;

    foreach( explode("\n", $_REQUEST['name']) as $name )
    {
        list($name, $url) = explode('|', $name);

        if( IsEmptyString($name) )
        {
            continue;
        }

        if( !$url )
        {
            $url = $_REQUEST['url'];
        }

        // Add blacklist item data to the database
        $DB->Update('INSERT INTO `tx_sponsors` VALUES (?,?,?)',
                    array(NULL,
                          trim($name),
                          trim($url)));

        $added++;
    }

    $GLOBALS['message'] = 'New sponsor' . ($added == 1 ? '' : 's') . ' successfully added';
    $GLOBALS['added'] = true;
    UnsetArray($_REQUEST);
    txShSponsorAdd();
}

function txSponsorEdit()
{
    global $DB, $C;

    VerifyAdministrator();

    $v = new Validator();
    $v->Register($_REQUEST['name'], V_EMPTY, 'The Name field must be filled in');

    if( strpos($_REQUEST['name'], ',') !== FALSE )
    {
        $v->SetError('Sponsor names may not contain commas');
    }

    if( preg_match('~^-~', trim($_REQUEST['name'])) )
    {
        $v->SetError('Sponsor names cannot start with a dash (-) character');
    }

    if( !$v->Validate() )
    {
        return $v->ValidationError('txShSponsorEdit');
    }

    // Update blacklist item data
    $DB->Update('UPDATE `tx_sponsors` SET ' .
                '`name`=?, ' .
                '`url`=? ' .
                'WHERE `sponsor_id`=?',
                array(trim($_REQUEST['name']),
                      trim($_REQUEST['url']),
                      $_REQUEST['sponsor_id']));

    $GLOBALS['message'] = 'Sponsor successfully updated';
    $GLOBALS['added'] = true;
    txShSponsorEdit();
}

function txShAnnotations()
{
    global $DB, $C;

    VerifyAdministrator();

    include_once('includes/annotations.php');
}

function txShAnnotationAdd()
{
    global $C, $DB, $ANN_LOCATIONS;

    VerifyAdministrator();
    ArrayHSC($_REQUEST);

    include_once('includes/annotations-add.php');
}

function txShAnnotationEdit()
{
    global $C, $DB, $ANN_LOCATIONS;

    VerifyAdministrator();

    // First time or update, use database information
    if( !$_REQUEST['editing'] || $GLOBALS['added'] )
    {
        $_REQUEST = $DB->Row('SELECT * FROM `tx_annotations` WHERE `annotation_id`=?', array($_REQUEST['annotation_id']));
    }

    ArrayHSC($_REQUEST);

    $editing = TRUE;

    include_once('includes/annotations-add.php');
}

function txAnnotationAdd()
{
    global $C, $DB;

    VerifyAdministrator();
    $v =& ValidateAnnotationInput();

    if( !$v->Validate() )
    {
        return $v->ValidationError('txShAnnotationAdd');
    }

    $DB->Update('INSERT INTO `tx_annotations` VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
                array(null,
                      $_REQUEST['identifier'],
                      $_REQUEST['type'],
                      $_REQUEST['string'],
                      intval($_REQUEST['use_category']),
                      $_REQUEST['font_file'],
                      $_REQUEST['text_size'],
                      $_REQUEST['text_color'],
                      $_REQUEST['shadow_color'],
                      $_REQUEST['image_file'],
                      $_REQUEST['transparency'],
                      $_REQUEST['location']));

    $GLOBALS['message'] = 'New annotation has been successfully added';
    $GLOBALS['added'] = true;
    UnsetArray($_REQUEST);
    txShAnnotationAdd();
}

function txAnnotationEdit()
{
    global $C, $DB;

    VerifyAdministrator();
    $v =& ValidateAnnotationInput();

    if( !$v->Validate() )
    {
        return $v->ValidationError('txShAnnotationEdit');
    }

    $DB->Update('UPDATE `tx_annotations` SET ' .
                '`identifier`=?, ' .
                '`type`=?, ' .
                '`string`=?, ' .
                '`use_category`=?, ' .
                '`font_file`=?, ' .
                '`text_size`=?, ' .
                '`text_color`=?, ' .
                '`shadow_color`=?, ' .
                '`image_file`=?, ' .
                '`transparency`=?, ' .
                '`location`=? ' .
                'WHERE `annotation_id`=?',
                array($_REQUEST['identifier'],
                      $_REQUEST['type'],
                      $_REQUEST['string'],
                      intval($_REQUEST['use_category']),
                      $_REQUEST['font_file'],
                      $_REQUEST['text_size'],
                      $_REQUEST['text_color'],
                      $_REQUEST['shadow_color'],
                      $_REQUEST['image_file'],
                      $_REQUEST['transparency'],
                      $_REQUEST['location'],
                      $_REQUEST['annotation_id']));

    $GLOBALS['message'] = 'Annotation has been successfully updated';
    $GLOBALS['added'] = true;

    txShAnnotationEdit();
}

function txShCategories()
{
    global $DB, $C;

    VerifyPrivileges(P_CATEGORY);

    include_once('includes/categories.php');
}

function txShCategoryEditDefault()
{
    global $C, $DB;

    // Get default category values & predefined thumb sizes
    $default = unserialize(GetValue('default_category'));
    $sizes = unserialize(GetValue('preview_sizes'));

    $_REQUEST = array_merge($default, $_REQUEST);

    // Load available annotations
    $annotations =& $DB->FetchAll('SELECT * FROM tx_annotations');

    VerifyPrivileges(P_CATEGORY_MODIFY);
    ArrayHSC($_REQUEST);

    $editing_default = TRUE;

    include_once('includes/categories-add.php');
}

function txShCategoryEdit()
{
    global $C, $DB;

    VerifyPrivileges(P_CATEGORY_MODIFY);

    // Load available annotations and predefined thumb sizes
    $annotations =& $DB->FetchAll('SELECT * FROM tx_annotations');
    $sizes = unserialize(GetValue('preview_sizes'));

    // First time or update, use database information
    if( !$_REQUEST['editing'] || $GLOBALS['added'] )
    {
        $_REQUEST = $DB->Row('SELECT * FROM `tx_categories` WHERE `category_id`=?', array($_REQUEST['category_id']));
    }

    ArrayHSC($_REQUEST);

    $editing = TRUE;

    include_once('includes/categories-add.php');
}

function txShCategoryAdd()
{
    global $C, $DB;

    VerifyPrivileges(P_CATEGORY_ADD);

    // Get default category values and pre-defined thumb sizes
    $default = unserialize(GetValue('default_category'));
    $sizes = unserialize(GetValue('preview_sizes'));

    $_REQUEST = array_merge($default, $_REQUEST);

    // Load available annotations
    $annotations =& $DB->FetchAll('SELECT * FROM tx_annotations');

    ArrayHSC($_REQUEST);

    include_once('includes/categories-add.php');
}

function txCategoryEditDefault()
{
    global $C, $DB;

    VerifyPrivileges(P_CATEGORY_MODIFY);

    $v =& ValidateCategoryInput();

    if( !$v->Validate() )
    {
        return $v->ValidationError('txShCategoryEditDefault');
    }

    UpdateThumbSizes();

    unset($_REQUEST['r']);
    unset($_REQUEST['name']);
    unset($_REQUEST['pics_preview_size_custom']);
    unset($_REQUEST['movies_preview_size_custom']);
    StoreValue('default_category', serialize($_REQUEST));

    $GLOBALS['message'] = 'Default category settings have been saved';
    $GLOBALS['added'] = true;

    txShCategoryEditDefault();
}

function txCategoryAdd()
{
    global $C, $DB;

    VerifyPrivileges(P_CATEGORY_ADD);
    UnixFormat($_REQUEST['name']);
    $v =& ValidateCategoryInput(TRUE);

    if( !$v->Validate() )
    {
        return $v->ValidationError('txShCategoryAdd');
    }

    UpdateThumbSizes();
    $added = 0;

    foreach( explode("\n", $_REQUEST['name']) as $name )
    {
        $name = trim($name);

        if( IsEmptyString($name) )
        {
            continue;
        }

        $tag = CreateCategoryTag($name);

        $DB->Update('INSERT INTO `tx_categories` VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                    array(null,
                          $name,
                          $tag,
                          intval($_REQUEST['pics_allowed']),
                          $_REQUEST['pics_extensions'],
                          $_REQUEST['pics_minimum'],
                          $_REQUEST['pics_maximum'],
                          $_REQUEST['pics_file_size'],
                          $_REQUEST['pics_preview_size'],
                          intval($_REQUEST['pics_preview_allowed']),
                          $_REQUEST['pics_annotation'],
                          intval($_REQUEST['movies_allowed']),
                          $_REQUEST['movies_extensions'],
                          $_REQUEST['movies_minimum'],
                          $_REQUEST['movies_maximum'],
                          $_REQUEST['movies_file_size'],
                          $_REQUEST['movies_preview_size'],
                          intval($_REQUEST['movies_preview_allowed']),
                          $_REQUEST['movies_annotation'],
                          $_REQUEST['per_day'],
                          intval($_REQUEST['hidden']),
                          null,
                          $_REQUEST['meta_description'],
                          $_REQUEST['meta_keywords']));

        $added++;
    }

    $GLOBALS['message'] = 'New ' . ($added == 1 ? 'category has' : 'categories have') . ' been successfully added';
    $GLOBALS['added'] = true;
    UnsetArray($_REQUEST);
    txShCategoryAdd();
}

function txCategoryEdit()
{
    global $C, $DB;

    VerifyPrivileges(P_CATEGORY_MODIFY);
    $v =& ValidateCategoryInput();

    if( !$v->Validate() )
    {
        return $v->ValidationError('txShCategoryEdit');
    }

    UpdateThumbSizes();

    // Bulk update
    if( isset($_REQUEST['apply_all']) || isset($_REQUEST['apply_matched']) )
    {
        $GLOBALS['message'] = 'All categories have been successfully updated';

        $select = new SelectBuilder('*', 'tx_categories');

        if( isset($_REQUEST['apply_matched']) )
        {
            $search = array();
            parse_str($_REQUEST['apply_matched'], $search);
            $select->AddWhere($search['field'], $search['search_type'], $search['search'], $search['search_type'] != ST_EMPTY);
            $GLOBALS['message'] = 'Matched categories have been successfully updated';
        }

        $result = $DB->Query($select->Generate(), $select->binds);

        while( $category = $DB->NextRow($result) )
        {
            $DB->Update('UPDATE `tx_categories` SET ' .
                        '`pics_allowed`=?, ' .
                        '`pics_extensions`=?, ' .
                        '`pics_minimum`=?, ' .
                        '`pics_maximum`=?, ' .
                        '`pics_file_size`=?, ' .
                        '`pics_preview_size`=?, ' .
                        '`pics_preview_allowed`=?, ' .
                        '`pics_annotation`=?, ' .
                        '`movies_allowed`=?, ' .
                        '`movies_extensions`=?, ' .
                        '`movies_minimum`=?, ' .
                        '`movies_maximum`=?, ' .
                        '`movies_file_size`=?, ' .
                        '`movies_preview_size`=?, ' .
                        '`movies_preview_allowed`=?, ' .
                        '`movies_annotation`=?, ' .
                        '`per_day`=?, ' .
                        '`hidden`=?, ' .
                        '`meta_description`=?, ' .
                        '`meta_keywords`=? ' .
                        'WHERE `category_id`=?',
                        array(intval($_REQUEST['pics_allowed']),
                              $_REQUEST['pics_extensions'],
                              $_REQUEST['pics_minimum'],
                              $_REQUEST['pics_maximum'],
                              $_REQUEST['pics_file_size'],
                              $_REQUEST['pics_preview_size'],
                              intval($_REQUEST['pics_preview_allowed']),
                              $_REQUEST['pics_annotation'],
                              intval($_REQUEST['movies_allowed']),
                              $_REQUEST['movies_extensions'],
                              $_REQUEST['movies_minimum'],
                              $_REQUEST['movies_maximum'],
                              $_REQUEST['movies_file_size'],
                              $_REQUEST['movies_preview_size'],
                              intval($_REQUEST['movies_preview_allowed']),
                              $_REQUEST['movies_annotation'],
                              $_REQUEST['per_day'],
                              intval($_REQUEST['hidden']),
                              $_REQUEST['meta_description'],
                              $_REQUEST['meta_keywords'],
                              $category['category_id']));
        }

        $DB->Free($result);
    }

    // Single category update
    else
    {
        $_REQUEST['name'] = trim($_REQUEST['name']);

        $DB->Update('UPDATE `tx_categories` SET ' .
                    '`name`=?, ' .
                    '`pics_allowed`=?, ' .
                    '`pics_extensions`=?, ' .
                    '`pics_minimum`=?, ' .
                    '`pics_maximum`=?, ' .
                    '`pics_file_size`=?, ' .
                    '`pics_preview_size`=?, ' .
                    '`pics_preview_allowed`=?, ' .
                    '`pics_annotation`=?, ' .
                    '`movies_allowed`=?, ' .
                    '`movies_extensions`=?, ' .
                    '`movies_minimum`=?, ' .
                    '`movies_maximum`=?, ' .
                    '`movies_file_size`=?, ' .
                    '`movies_preview_size`=?, ' .
                    '`movies_preview_allowed`=?, ' .
                    '`movies_annotation`=?, ' .
                    '`per_day`=?, ' .
                    '`hidden`=?, ' .
                    '`meta_description`=?, ' .
                    '`meta_keywords`=? ' .
                    'WHERE `category_id`=?',
                    array($_REQUEST['name'],
                          intval($_REQUEST['pics_allowed']),
                          $_REQUEST['pics_extensions'],
                          $_REQUEST['pics_minimum'],
                          $_REQUEST['pics_maximum'],
                          $_REQUEST['pics_file_size'],
                          $_REQUEST['pics_preview_size'],
                          intval($_REQUEST['pics_preview_allowed']),
                          $_REQUEST['pics_annotation'],
                          intval($_REQUEST['movies_allowed']),
                          $_REQUEST['movies_extensions'],
                          $_REQUEST['movies_minimum'],
                          $_REQUEST['movies_maximum'],
                          $_REQUEST['movies_file_size'],
                          $_REQUEST['movies_preview_size'],
                          intval($_REQUEST['movies_preview_allowed']),
                          $_REQUEST['movies_annotation'],
                          $_REQUEST['per_day'],
                          intval($_REQUEST['hidden']),
                          $_REQUEST['meta_description'],
                          $_REQUEST['meta_keywords'],
                          $_REQUEST['category_id']));

        $GLOBALS['message'] = 'Category has been successfully updated';
    }

    $GLOBALS['added'] = true;

    txShCategoryEdit();
}

function txShGalleryFields()
{
    global $DB, $C;

    VerifyAdministrator();

    include_once('includes/gallery-fields.php');
}

function txShGalleryFieldAdd()
{
    global $DB, $C, $FIELD_TYPES, $VALIDATION_TYPES;

    VerifyAdministrator();
    ArrayHSC($_REQUEST);

    include_once('includes/gallery-fields-add.php');
}

function txShGalleryFieldEdit()
{
    global $DB, $C, $FIELD_TYPES, $VALIDATION_TYPES;

    VerifyAdministrator();

    $editing = TRUE;

    // First time or update, use database information
    if( !$_REQUEST['editing'] || $GLOBALS['added'] )
    {
        $_REQUEST = $DB->Row('SELECT * FROM `tx_gallery_field_defs` WHERE `field_id`=?', array($_REQUEST['field_id']));
        $_REQUEST['old_name'] = $_REQUEST['name'];
    }

    ArrayHSC($_REQUEST);

    include_once('includes/gallery-fields-add.php');
}

function txGalleryFieldAdd()
{
    global $DB, $C;

    VerifyAdministrator();

    $v =& ValidateUserDefined('tx_gallery_field_defs', 'tx_galleries');

    if( !$v->Validate() )
    {
        return $v->ValidationError('txShGalleryFieldAdd');
    }

    $_REQUEST['options'] = FormatCommaSeparated($_REQUEST['options']);

    $DB->Update("ALTER TABLE `tx_gallery_fields` ADD COLUMN # TEXT", array($_REQUEST['name']));
    $DB->Update('INSERT INTO `tx_gallery_field_defs` VALUES (?,?,?,?,?,?,?,?,?,?,?)',
                array(NULL,
                      $_REQUEST['name'],
                      $_REQUEST['label'],
                      $_REQUEST['type'],
                      $_REQUEST['tag_attributes'],
                      $_REQUEST['options'],
                      $_REQUEST['validation'],
                      $_REQUEST['validation_extras'],
                      $_REQUEST['validation_message'],
                      intval($_REQUEST['on_submit']),
                      intval($_REQUEST['required'])));

    $GLOBALS['message'] = 'New gallery field successfully added';
    $GLOBALS['added'] = true;

    UnsetArray($_REQUEST);
    txShGalleryFieldAdd();
}

function txGalleryFieldEdit()
{
    global $DB, $C;

    VerifyAdministrator();

    $v =& ValidateUserDefined('tx_gallery_field_defs', 'tx_galleries', TRUE);

    if( !$v->Validate() )
    {
        return $v->ValidationError('txShGalleryFieldEdit');
    }

    $_REQUEST['options'] = FormatCommaSeparated($_REQUEST['options']);

    if( $_REQUEST['name'] != $_REQUEST['old_name'] )
        $DB->Update("ALTER TABLE `tx_gallery_fields` CHANGE # # TEXT", array($_REQUEST['old_name'], $_REQUEST['name']));

    $DB->Update('UPDATE `tx_gallery_field_defs` SET ' .
                '`name`=?, ' .
                '`label`=?, ' .
                '`type`=?, ' .
                '`tag_attributes`=?, ' .
                '`options`=?, ' .
                '`validation`=?, ' .
                '`validation_extras`=?, ' .
                '`validation_message`=?, ' .
                '`on_submit`=?, ' .
                '`required`=? ' .
                'WHERE `field_id`=?',
                array($_REQUEST['name'],
                      $_REQUEST['label'],
                      $_REQUEST['type'],
                      $_REQUEST['tag_attributes'],
                      $_REQUEST['options'],
                      $_REQUEST['validation'],
                      $_REQUEST['validation_extras'],
                      $_REQUEST['validation_message'],
                      intval($_REQUEST['on_submit']),
                      intval($_REQUEST['required']),
                      $_REQUEST['field_id']));

    $GLOBALS['message'] = 'Gallery field has been successfully updated';
    $GLOBALS['added'] = true;

    txShGalleryFieldEdit();
}

function txShLanguageFile()
{
    global $DB, $C, $L;

    VerifyAdministrator();

    include_once('includes/language.php');
}

function txLanguageFileSave()
{
    global $DB, $C, $L;

    VerifyAdministrator();

    if( is_writable("{$GLOBALS['BASE_DIR']}/includes/language.php") )
    {
        $language = "<?PHP\n";

        foreach( $L as $key => $value )
        {
            $L[$key] = $_REQUEST[$key];
            $value = str_replace("'", "\'", $_REQUEST[$key]);
            $language .= "\$L['$key'] = '$value';\n";
        }

        $language .= "?>";

        FileWrite("{$GLOBALS['BASE_DIR']}/includes/language.php", $language);

        $GLOBALS['message'] = 'The language file has been successfully updated';
    }

    txShLanguageFile();
}

function txShRejectionTemplates()
{
    global $DB, $C;

    VerifyAdministrator();

    include_once('includes/rejections.php');
}

function txShRejectionTemplateAdd()
{
    global $C, $DB;

    VerifyAdministrator();
    ArrayHSC($_REQUEST);

    include_once('includes/rejections-add.php');
}

function txShRejectionTemplateEdit()
{
    global $DB, $C;

    VerifyAdministrator();

    $editing = TRUE;

    // First time or update, use database information
    if( !$_REQUEST['editing'] || $GLOBALS['added'] )
    {
        $_REQUEST = $DB->Row('SELECT * FROM `tx_rejections` WHERE `email_id`=?', array($_REQUEST['email_id']));
        IniParse($_REQUEST['plain'], FALSE, $_REQUEST);
    }

    ArrayHSC($_REQUEST);

    include_once('includes/rejections-add.php');
}

function txRejectionTemplateAdd()
{
    global $DB, $C;

    VerifyAdministrator();

    $v = new Validator();
    $v->Register($_REQUEST['identifier'], V_EMPTY, 'The Identifier field must be filled in');
    $v->Register($_REQUEST['subject'], V_EMPTY,  'The Subject field must be filled in');

    if( !$v->Validate() )
    {
        return $v->ValidationError('txShRejectionTemplateAdd');
    }

    $_REQUEST['plain'] = trim($_REQUEST['plain']);
    $_REQUEST['html'] = trim($_REQUEST['html']);
    $ini_data = IniWrite(null, $_REQUEST, array('subject', 'plain', 'html'));

    $compiled_code = '';
    $compiler = new Compiler();
    if( $compiler->compile($ini_data, $compiled_code) )
    {
        $DB->Update('INSERT INTO `tx_rejections` VALUES (?,?,?,?)',
                    array(NULL,
                          $_REQUEST['identifier'],
                          $ini_data,
                          $compiled_code));

        $GLOBALS['message'] = 'New rejection e-mail successfully added';
        $GLOBALS['added'] = true;

        UnsetArray($_REQUEST);
    }
    else
    {
        $GLOBALS['errstr'] = "Rejection e-mail could not be saved:<br />" . nl2br($compiler->get_error_string());
    }

    txShRejectionTemplateAdd();
}

function txRejectionTemplateEdit()
{
    global $DB, $C;

    VerifyAdministrator();

    $v = new Validator();
    $v->Register($_REQUEST['identifier'], V_EMPTY, 'The Identifier field must be filled in');
    $v->Register($_REQUEST['subject'], V_EMPTY,  'The Subject field must be filled in');

    if( !$v->Validate() )
    {
        return $v->ValidationError('txShRejectionTemplateEdit');
    }

    $_REQUEST['plain'] = trim($_REQUEST['plain']);
    $_REQUEST['html'] = trim($_REQUEST['html']);
    $ini_data = IniWrite(null, $_REQUEST, array('subject', 'plain', 'html'));

    $compiled_code = '';
    $compiler = new Compiler();
    if( $compiler->compile($ini_data, $compiled_code) )
    {
        $DB->Update('UPDATE `tx_rejections` SET ' .
            '`identifier`=?, ' .
            '`plain`=?, ' .
            '`compiled`=? ' .
            'WHERE `email_id`=?',
            array($_REQUEST['identifier'],
                  $ini_data,
                  $compiled_code,
                  $_REQUEST['email_id']));

        $GLOBALS['message'] = 'Rejection e-mail has been successfully updated';
        $GLOBALS['added'] = true;
    }
    else
    {
        $GLOBALS['errstr'] = "Rejection e-mail could not be saved:<br />" . nl2br($compiler->get_error_string());
    }

    txShRejectionTemplateEdit();
}

function txShEmailTemplates()
{
    global $DB, $C;

    VerifyAdministrator();
    CheckAccessList();

    ArrayHSC($_REQUEST);

    include_once('includes/templates-email.php');
}

function txEmailTemplateLoad()
{
    global $DB, $C;

    VerifyAdministrator();

    $template_file = SafeFilename("{$GLOBALS['BASE_DIR']}/templates/{$_REQUEST['template']}");
    IniParse($template_file, TRUE, $_REQUEST);
    $_REQUEST['loaded_template'] = $_REQUEST['template'];

    txShEmailTemplates();
}

function txEmailTemplateSave()
{
    global $DB, $C;

    VerifyAdministrator();
    CheckAccessList();

    $_REQUEST['plain'] = trim($_REQUEST['plain']);
    $_REQUEST['html'] = trim($_REQUEST['html']);
    $ini_data = IniWrite(null, $_REQUEST, array('subject', 'plain', 'html'));

    $compiled_code = '';
    $compiler = new Compiler();
    if( $compiler->compile($ini_data, $compiled_code) )
    {
        $template_file = SafeFilename("{$GLOBALS['BASE_DIR']}/templates/{$_REQUEST['loaded_template']}");
        FileWrite($template_file, $ini_data);
        $GLOBALS['message'] = 'Template has been successully saved';
    }
    else
    {
        $GLOBALS['errstr'] = "Template could not be saved:<br />" . nl2br($compiler->get_error_string());
    }

    txShEmailTemplates();
}

function txShScriptTemplates()
{
    global $DB, $C;

    VerifyAdministrator();
    CheckAccessList();

    ArrayHSC($_REQUEST);

    include_once('includes/templates-script.php');
}

function txScriptTemplateLoad()
{
    global $DB, $C;

    VerifyAdministrator();

    $template_file = SafeFilename("{$GLOBALS['BASE_DIR']}/templates/{$_REQUEST['template']}");
    $_REQUEST['code'] = file_get_contents($template_file);
    $_REQUEST['loaded_template'] = $_REQUEST['template'];

    txShScriptTemplates();
}

function txScriptTemplateSave()
{
    global $DB, $C;

    VerifyAdministrator();
    CheckAccessList();

    $_REQUEST['code'] = trim($_REQUEST['code']);

    // Compile global templates first, if this is not one
    if( !preg_match('~global-~', $_REQUEST['loaded_template']) )
    {
        $t = new Template();
        foreach( glob("{$GLOBALS['BASE_DIR']}/templates/*global-*.tpl") as $global_template )
        {
            $t->compile_template(basename($global_template));
        }
    }

    $compiled_code = '';
    $compiler = new Compiler();
    if( $compiler->compile($_REQUEST['code'], $compiled_code) )
    {
        $template_file = SafeFilename("{$GLOBALS['BASE_DIR']}/templates/{$_REQUEST['loaded_template']}");
        FileWrite($template_file, $_REQUEST['code']);

        $compiled_file = SafeFilename("{$GLOBALS['BASE_DIR']}/templates/compiled/{$_REQUEST['loaded_template']}", FALSE);
        FileWrite($compiled_file, $compiled_code);

        $GLOBALS['message'] = 'Template has been successully saved';
    }
    else
    {
        $GLOBALS['errstr'] = "Template could not be saved:<br />" . nl2br($compiler->get_error_string());
    }

    $GLOBALS['warnstr'] = CheckTemplateCode($_REQUEST['code']);

    // Recompile all templates if a global template was updated
    if( preg_match('~global-~', $_REQUEST['loaded_template']) )
    {
        RecompileTemplates();
    }

    txShScriptTemplates();
}

function txSh2257()
{
    global $DB, $C;

    VerifyAdministrator();

    include_once('includes/2257.php');
}

function txSh2257Add()
{
    global $DB, $C;

    VerifyAdministrator();
    ArrayHSC($_REQUEST);

    include_once('includes/2257-add.php');
}

function txSh2257Edit()
{
    global $DB, $C;

    VerifyAdministrator();

    $editing = TRUE;

    // First time, use database information
    if( !$_REQUEST['editing'] )
    {
        $_REQUEST = $DB->Row('SELECT * FROM `tx_2257` WHERE `code_id`=?', array($_REQUEST['code_id']));
    }

    ArrayHSC($_REQUEST);

    include_once('includes/2257-add.php');
}

function tx2257Add()
{
    global $DB, $C;

    VerifyAdministrator();

    $v = new Validator();
    $v->Register($_REQUEST['code'], V_EMPTY, 'The Link Code field must be filled in');
    $v->Register($_REQUEST['identifier'], V_EMPTY, 'The Identifier field must be filled in');

    if( !$v->Validate() )
    {
        return $v->ValidationError('txSh2257Add');
    }

    UnixFormat($_REQUEST['code']);

    // Add reciprocal link to the database
    $DB->Update('INSERT INTO `tx_2257` VALUES (?,?,?,?)',
                array(NULL,
                      $_REQUEST['identifier'],
                      $_REQUEST['code'],
                      intval($_REQUEST['regex'])));


    $GLOBALS['message'] = 'New 2257 code successfully added';
    $GLOBALS['added'] = true;
    UnsetArray($_REQUEST);
    txSh2257Add();
}

function tx2257Edit()
{
    global $DB, $C;

    VerifyAdministrator();

    $v = new Validator();
    $v->Register($_REQUEST['code'], V_EMPTY, 'The Link Code field must be filled in');
    $v->Register($_REQUEST['identifier'], V_EMPTY, 'The Identifier field must be filled in');

    if( !$v->Validate() )
    {
        return $v->ValidationError('txSh2257Edit');
    }

    // Update blacklist item data
    $DB->Update('UPDATE `tx_2257` SET ' .
                '`identifier`=?, ' .
                '`code`=?, ' .
                '`regex`=? ' .
                'WHERE `code_id`=?',
                array($_REQUEST['identifier'],
                      $_REQUEST['code'],
                      intval($_REQUEST['regex']),
                      $_REQUEST['code_id']));

    $GLOBALS['message'] = '2257 code successfully updated';
    $GLOBALS['added'] = true;
    txSh2257Edit();
}

function txShReciprocals()
{
    global $DB, $C;

    VerifyAdministrator();

    include_once('includes/reciprocals.php');
}

function txShReciprocalAdd()
{
    global $DB, $C;

    VerifyAdministrator();
    ArrayHSC($_REQUEST);

    include_once('includes/reciprocals-add.php');
}

function txShReciprocalEdit()
{
    global $DB, $C;

    VerifyAdministrator();

    $editing = TRUE;

    // First time, use database information
    if( !$_REQUEST['editing'] )
    {
        $_REQUEST = $DB->Row('SELECT * FROM `tx_reciprocals` WHERE `recip_id`=?', array($_REQUEST['recip_id']));
    }

    ArrayHSC($_REQUEST);

    include_once('includes/reciprocals-add.php');
}

function txReciprocalAdd()
{
    global $DB, $C;

    VerifyAdministrator();

    $v = new Validator();
    $v->Register($_REQUEST['code'], V_EMPTY, 'The Link Code field must be filled in');
    $v->Register($_REQUEST['identifier'], V_EMPTY, 'The Identifier field must be filled in');

    if( !$v->Validate() )
    {
        return $v->ValidationError('txShReciprocalAdd');
    }

    UnixFormat($_REQUEST['code']);

    // Add reciprocal link to the database
    $DB->Update('INSERT INTO `tx_reciprocals` VALUES (?,?,?,?)',
                array(NULL,
                      $_REQUEST['identifier'],
                      $_REQUEST['code'],
                      intval($_REQUEST['regex'])));


    $GLOBALS['message'] = 'New reciprocal link  successfully added';
    $GLOBALS['added'] = true;
    UnsetArray($_REQUEST);
    txShReciprocalAdd();
}

function txReciprocalEdit()
{
    global $DB, $C;

    VerifyAdministrator();

    $v = new Validator();
    $v->Register($_REQUEST['code'], V_EMPTY, 'The Link Code field must be filled in');
    $v->Register($_REQUEST['identifier'], V_EMPTY, 'The Identifier field must be filled in');

    if( !$v->Validate() )
    {
        return $v->ValidationError('txShReciprocalEdit');
    }

    // Update blacklist item data
    $DB->Update('UPDATE `tx_reciprocals` SET ' .
                '`identifier`=?, ' .
                '`code`=?, ' .
                '`regex`=? ' .
                'WHERE `recip_id`=?',
                array($_REQUEST['identifier'],
                      $_REQUEST['code'],
                      intval($_REQUEST['regex']),
                      $_REQUEST['recip_id']));

    $GLOBALS['message'] = 'Reciprocal link successfully updated';
    $GLOBALS['added'] = true;
    txShReciprocalEdit();
}

function txShRegexTest()
{
    global $DB, $C;

    include_once('includes/regex-test.php');
}

function txShWhitelist()
{
    global $DB, $C, $WLIST_TYPES;

    VerifyAdministrator();

    include_once('includes/whitelist.php');
}

function txShWhitelistAdd()
{
    global $DB, $C, $WLIST_TYPES;

    VerifyAdministrator();
    ArrayHSC($_REQUEST);

    include_once('includes/whitelist-add.php');
}

function txShWhitelistEdit()
{
    global $DB, $C, $WLIST_TYPES;

    VerifyAdministrator();

    $editing = TRUE;

    // First time, use database information
    if( !$_REQUEST['editing'] )
    {
        $_REQUEST = $DB->Row('SELECT * FROM `tx_whitelist` WHERE `whitelist_id`=?', array($_REQUEST['whitelist_id']));
    }

    ArrayHSC($_REQUEST);

    include_once('includes/whitelist-add.php');
}

function txWhitelistAdd()
{
    global $DB, $C;

    VerifyAdministrator();

    $v = new Validator();
    $v->Register($_REQUEST['value'], V_EMPTY, 'The Value(s) field must be filled in');

    if( !$v->Validate() )
    {
        return $v->ValidationError('txShWhitelist');
    }

    UnixFormat($_REQUEST['value']);
    $added = 0;

    foreach( explode("\n", $_REQUEST['value']) as $value )
    {
        list($value, $reason) = explode('|', $value);

        if( IsEmptyString($value) )
        {
            continue;
        }

        if( !$reason )
        {
            $reason = $_REQUEST['reason'];
        }

        if( $DB->Count('SELECT COUNT(*) FROM `tx_whitelist` WHERE `type`=? AND `value`=?', array($_REQUEST['type'], $value)) < 1 )
        {
            // Add whitelist item data to the database
            $DB->Update('INSERT INTO `tx_whitelist` VALUES (?,?,?,?,?,?,?,?,?,?)',
                        array(NULL,
                              $_REQUEST['type'],
                              intval($_REQUEST['regex']),
                              $value,
                              $reason,
                              $_REQUEST['allow_redirect'],
                              $_REQUEST['allow_norecip'],
                              $_REQUEST['allow_autoapprove'],
                              $_REQUEST['allow_noconfirm'],
                              $_REQUEST['allow_blacklist']));

            $added++;
        }
    }

    $GLOBALS['message'] = 'New whitelist item' . ($added == 1 ? '' : 's') . ' successfully added';
    $GLOBALS['added'] = true;
    $GLOBALS['from_search'] = $_REQUEST['from_search'];
    UnsetArray($_REQUEST);
    txShWhitelistAdd();
}

function txWhitelistEdit()
{
    global $DB, $C;

    VerifyAdministrator();

    $v = new Validator();
    $v->Register($_REQUEST['value'], V_EMPTY, 'The Value(s) field must be filled in');

    if( !$v->Validate() )
    {
        return $v->ValidationError('txShWhitelistEdit');
    }

    // Update blacklist item data
    $DB->Update('UPDATE `tx_whitelist` SET ' .
                '`value`=?, ' .
                '`type`=?, ' .
                '`regex`=?, ' .
                '`reason`=?, ' .
                '`allow_redirect`=?, ' .
                '`allow_norecip`=?, ' .
                '`allow_autoapprove`=?, ' .
                '`allow_noconfirm`=?, ' .
                '`allow_blacklist`=? ' .
                'WHERE `whitelist_id`=?',
                array($_REQUEST['value'],
                      $_REQUEST['type'],
                      intval($_REQUEST['regex']),
                      $_REQUEST['reason'],
                      $_REQUEST['allow_redirect'],
                      $_REQUEST['allow_norecip'],
                      $_REQUEST['allow_autoapprove'],
                      $_REQUEST['allow_noconfirm'],
                      $_REQUEST['allow_blacklist'],
                      $_REQUEST['whitelist_id']));

    $GLOBALS['message'] = 'Whitelist item successfully updated';
    $GLOBALS['added'] = true;
    txShWhitelistEdit();
}

function txShBlacklist()
{
    global $DB, $C, $BLIST_TYPES;

    VerifyAdministrator();

    include_once('includes/blacklist.php');
}

function txShBlacklistAdd()
{
    global $DB, $C, $BLIST_TYPES;

    VerifyAdministrator();
    ArrayHSC($_REQUEST);

    include_once('includes/blacklist-add.php');
}

function txShBlacklistEdit()
{
    global $DB, $C, $BLIST_TYPES;

    VerifyAdministrator();

    $editing = TRUE;

    // First time, use database information
    if( !$_REQUEST['editing'] )
    {
        $_REQUEST = $DB->Row('SELECT * FROM `tx_blacklist` WHERE `blacklist_id`=?', array($_REQUEST['blacklist_id']));
    }

    ArrayHSC($_REQUEST);

    include_once('includes/blacklist-add.php');
}

function txBlacklistAdd()
{
    global $DB, $C;

    VerifyAdministrator();

    $v = new Validator();
    $v->Register($_REQUEST['value'], V_EMPTY, 'The Value(s) field must be filled in');

    if( !$v->Validate() )
    {
        return $v->ValidationError('txShBlacklistAdd');
    }

    UnixFormat($_REQUEST['value']);
    $added = 0;

    foreach( explode("\n", $_REQUEST['value']) as $value )
    {
        list($value, $reason) = explode('|', $value);

        if( IsEmptyString($value) )
        {
            continue;
        }

        if( !$reason )
        {
            $reason = $_REQUEST['reason'];
        }

        // Add blacklist item data to the database
        $DB->Update('INSERT INTO `tx_blacklist` VALUES (?,?,?,?,?)',
                    array(NULL,
                          $_REQUEST['type'],
                          intval($_REQUEST['regex']),
                          $value,
                          $reason));

        $added++;
    }

    $GLOBALS['message'] = 'New blacklist item' . ($added == 1 ? '' : 's') . ' successfully added';
    $GLOBALS['added'] = true;
    UnsetArray($_REQUEST);
    txShBlacklistAdd();
}

function txBlacklistEdit()
{
    global $DB, $C;

    VerifyAdministrator();

    $v = new Validator();
    $v->Register($_REQUEST['value'], V_EMPTY, 'The Value(s) field must be filled in');

    if( !$v->Validate() )
    {
        return $v->ValidationError('txShBlacklistEdit');
    }

    // Update blacklist item data
    $DB->Update('UPDATE `tx_blacklist` SET ' .
                '`value`=?, ' .
                '`type`=?, ' .
                '`regex`=?, ' .
                '`reason`=? ' .
                'WHERE `blacklist_id`=?',
                array($_REQUEST['value'],
                      $_REQUEST['type'],
                      intval($_REQUEST['regex']),
                      $_REQUEST['reason'],
                      $_REQUEST['blacklist_id']));

    $GLOBALS['message'] = 'Blacklist item successfully updated';
    $GLOBALS['added'] = true;
    txShBlacklistEdit();
}

function txShGeneralSettings()
{
    global $C;

    VerifyAdministrator();
    CheckAccessList();
    ArrayHSC($C);

    $C = array_merge($C, ($GLOBALS['_server_'] == null ? GetServerCapabilities() : $GLOBALS['_server_']));

    include_once('includes/settings-general.php');
}

function txGeneralSettingsSave()
{
    global $C;

    VerifyAdministrator();
    CheckAccessList();

    $server = GetServerCapabilities();
    $GLOBALS['_server_'] = $server;

    $v = new Validator();

    $required = array('document_root' => 'Document Root',
                      'install_url' => 'TGPX URL',
                      'cookie_domain' => 'Cookie Domain',
                      'from_email' => 'E-mail Address',
                      'from_email_name' => 'E-mail Name',
                      'date_format' => 'Date Format',
                      'time_format' => 'Time Format',
                      'dec_point' => 'Decimal Point',
                      'thousands_sep' => 'Thousands Separator',
                      'max_submissions' => 'Global Submissions Per Day',
                      'submissions_per_person' => 'Submissions Per Person',
                      'max_links' => 'Maximum Links Allowed',
                      'min_desc_length' => 'Minimum Description Length',
                      'max_desc_length' => 'Maximum Description Length',
                      'min_thumb_size' => 'Minimum Gallery Thumb Size',
                      'max_thumb_size' => 'Maximum Gallery Thumb Size',
                      'max_keywords' => 'Maximum Keywords',
                      'gallery_weight' => 'Default Gallery Weight',
                      'font_dir' => 'Font Directory',
                      'min_code_length' => 'Minimum Code Length',
                      'max_code_length' => 'Maximum Code Length',
                      'permanent_hold' => 'Permanent Holding Period',
                      'submitted_hold' => 'Submitted Holding Period',
                      'page_permissions' => 'Page Permissions');

    if( $_REQUEST['allow_multiple_cats'] )
        $required['max_categories'] = 'Maximum Categories';

    foreach($required as $field => $name)
    {
        $v->Register($_REQUEST[$field], V_EMPTY, "The $name field is required");
    }

    $v->Register($_REQUEST['min_thumb_size'], V_REGEX, "The 'Minimum Gallery Thumb Size' value must be in WxH format", '~\d+x\d+~');
    $v->Register($_REQUEST['max_thumb_size'], V_REGEX, "The 'Maximum Gallery Thumb Size' value must be in WxH format", '~\d+x\d+~');

    if( !$v->Validate() )
    {
        $C = array_merge($C, $_REQUEST);
        return $v->ValidationError('txShGeneralSettings');
    }

    if( !isset($_REQUEST['compression']) )
    {
        $_REQUEST['compression'] = 80;
    }

    list($_REQUEST['min_thumb_width'], $_REQUEST['min_thumb_height']) = explode('x', trim($_REQUEST['min_thumb_size']));
    list($_REQUEST['max_thumb_width'], $_REQUEST['max_thumb_height']) = explode('x', trim($_REQUEST['max_thumb_size']));
    $_REQUEST['document_root'] = preg_replace('~/$~', '', $_REQUEST['document_root']);
    $_REQUEST['install_url'] = preg_replace('~/$~', '', $_REQUEST['install_url']);
    $_REQUEST['preview_url'] = preg_replace('~/$~', '', $_REQUEST['preview_url']);
    $_REQUEST['domain'] = preg_replace('~^www\.~', '', $_SERVER['HTTP_HOST']);
    $_REQUEST['preview_dir'] = DirectoryFromRoot($_REQUEST['document_root'], $_REQUEST['preview_url']);
    $_REQUEST = array_merge($server, $_REQUEST);

    WriteConfig($_REQUEST);
    CleanupThumbSizes();

    $GLOBALS['message'] = 'Your settings have been successfully updated';

    txShGeneralSettings();
}

function txShAdministrators()
{
    global $DB, $C;

    VerifyAdministrator();

    include_once('includes/administrators.php');
}

function txAdministratorAdd()
{
    global $DB, $C;

    VerifyAdministrator();

    $user_count = $DB->Count('SELECT COUNT(*) FROM `tx_administrators` WHERE `username`=?', array($_REQUEST['username']));

    $v = new Validator();
    $v->Register($_REQUEST['username'], V_LENGTH, 'The username must be between 3 and 32 characters in length', array('min'=>3,'max'=>32));
    $v->Register($_REQUEST['username'], V_ALPHANUM, 'The username can only contain letters and numbers');
    $v->Register($_REQUEST['password'], V_LENGTH, 'The password must contain at least 4 characters', array('min'=>4,'max'=>999));
    $v->Register($_REQUEST['email'], V_EMAIL, 'The e-mail address is not properly formatted');

    if( $user_count > 0 )
    {
        $v->SetError('An administrator account already exists with that username');
    }

    if( isset($_REQUEST['e_cheat_report']) && !is_numeric($_REQUEST['reports_waiting']) )
    {
        $v->SetError('The number of reports waiting must be filled in and numeric');
    }

    if( isset($_REQUEST['e_partner_request']) && !is_numeric($_REQUEST['requests_waiting']) )
    {
        $v->SetError('The number of requests waiting must be filled in and numeric');
    }

    if( !$v->Validate() )
    {
        return $v->ValidationError('txShAdministratorAdd');
    }

    // Determine the privileges and notifications for this account
    $privileges = GenerateFlags($_REQUEST, '^p_');
    $notifications = GenerateFlags($_REQUEST, '^e_');

    // Add account data to the database
    $DB->Update('INSERT INTO `tx_administrators` VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                array($_REQUEST['username'],
                      sha1($_REQUEST['password']),
                      NULL,
                      NULL,
                      $_REQUEST['name'],
                      $_REQUEST['email'],
                      $_REQUEST['type'],
                      NULL,
                      NULL,
                      NULL,
                      NULL,
                      0,
                      0,
                      0,
                      $notifications,
                      $privileges,
                      $_REQUEST['reports_waiting'],
                      $_REQUEST['requests_waiting']));

    $GLOBALS['message'] = 'New administrator successfully added';
    $GLOBALS['added'] = true;
    UnsetArray($_REQUEST);
    txShAdministratorAdd();
}

function txAdministratorEdit()
{
    global $DB, $C;

    VerifyAdministrator();

    $administrator = $DB->Row('SELECT * FROM `tx_administrators` WHERE `username`=?', array($_REQUEST['username']));

    $v = new Validator();
    $v->Register($_REQUEST['email'], V_EMAIL, 'The e-mail address is not properly formatted');
    if( $_REQUEST['password'] )
    {
        $v->Register($_REQUEST['password'], V_LENGTH, 'The password must contain at least 4 characters', array('min'=>4,'max'=>999));
    }

    if( isset($_REQUEST['e_cheat_report']) && !is_numeric($_REQUEST['reports_waiting']) )
    {
        $v->SetError('The number of reports waiting must be filled in and numeric');
    }

    if( isset($_REQUEST['e_partner_request']) && !is_numeric($_REQUEST['requests_waiting']) )
    {
        $v->SetError('The number of requests waiting must be filled in and numeric');
    }

    if( !$v->Validate() )
    {
        return $v->ValidationError('txShAdministratorEdit');
    }

    if( $_REQUEST['password'] )
    {
        // Password has changed, so invalidate any current session that may be active
        if( $_REQUEST['username'] != $_SERVER['REMOTE_USER'] )
        {
            $DB->Update('UPDATE `tx_administrators` SET `session`=NULL,`session_start`=NULL WHERE `username`=?', array($_REQUEST['username']));
        }

        $_REQUEST['password'] = sha1($_REQUEST['password']);
    }
    else
    {
        $_REQUEST['password'] = $administrator['password'];
    }

    // Determine the privileges and notifications for this account
    $privileges = GenerateFlags($_REQUEST, '^p_');
    $notifications = GenerateFlags($_REQUEST, '^e_');

    // Update account information
    $DB->Update('UPDATE `tx_administrators` SET ' .
                '`password`=?, ' .
                '`name`=?, ' .
                '`email`=?, ' .
                '`type`=?, ' .
                '`notifications`=?, ' .
                '`rights`=?, ' .
                '`reports_waiting`=?, ' .
                '`requests_waiting`=? ' .
                'WHERE `username`=?',
                array($_REQUEST['password'],
                      $_REQUEST['name'],
                      $_REQUEST['email'],
                      $_REQUEST['type'],
                      $notifications,
                      $privileges,
                      $_REQUEST['reports_waiting'],
                      $_REQUEST['requests_waiting'],
                      $_REQUEST['username']));

    $GLOBALS['message'] = 'Administrator account successfully updated';
    $GLOBALS['added'] = true;
    txShAdministratorEdit();
}

function txShAdministratorEdit()
{
    global $DB, $C;

    VerifyAdministrator();

    $editing = TRUE;

    // First time, use database information
    if( !$_REQUEST['editing'] || $GLOBALS['added'] )
    {
        $_REQUEST = $DB->Row('SELECT * FROM `tx_administrators` WHERE `username`=?', array($_REQUEST['username']));

        if( empty($_REQUEST['requests_waiting']) )
            $_REQUEST['requests_waiting'] = '';

        if( empty($_REQUEST['reports_waiting']) )
            $_REQUEST['reports_waiting'] = '';
    }

    unset($_REQUEST['password']);
    ArrayHSC($_REQUEST);

    include_once('includes/administrators-add.php');
}

function txShAdministratorAdd()
{
    global $DB, $C;

    VerifyAdministrator();
    ArrayHSC($_REQUEST);

    include_once('includes/administrators-add.php');
}

function txShAdministratorMail()
{
    global $DB, $C;

    VerifyAdministrator();

    ArrayHSC($_REQUEST);

    if( is_array($_REQUEST['username']) )
    {
        $_REQUEST['to'] = join(', ', $_REQUEST['username']);
        $_REQUEST['to_list'] = join(',', $_REQUEST['username']);
    }
    else
    {
        $_REQUEST['to'] = $_REQUEST['to_list'] = $_REQUEST['username'];
    }

    $function = 'txAdministratorMail';
    include_once('includes/email-compose.php');
}

function txAdministratorMail()
{
    global $DB, $C, $t;

    VerifyAdministrator();

    $message = PrepareMessage();
    $t = new Template();
    $t->assign_by_ref('config', $C);

    foreach( explode(',', $_REQUEST['to']) as $to_account )
    {
        $account = $DB->Row('SELECT * FROM `tx_administrators` WHERE `username`=?', array($to_account));

        if( $account )
        {
            $t->assign_by_ref('account', $account);
            SendMail($account['email'], $message, $t, FALSE);
        }
    }

    $message = 'The selected administrator accounts have been e-mailed';
    include_once('includes/message.php');
}

function txLogOut()
{
    global $DB;

    $DB->Update('UPDATE `tx_administrators` SET `session`=NULL,`session_start`=NULL WHERE `username`=?', array($_SERVER['REMOTE_USER']));

    setcookie('tgpx', '', time()-3600);
    header('Expires: Mon, 26 Jul 1990 05:00:00 GMT');
    header('Last-Modified: ' . gmdate("D, d M Y H:i:s") . ' GMT');
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Location: index.php');
}

?>
