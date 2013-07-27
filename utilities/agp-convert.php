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

if( !is_file('scanner.php') )
{
    echo "This file must be located in the admin directory of your TGPX installation";
    exit;
}

$replace_galleries_html = array('##Gallery_ID##' => '{$gallery.gallery_id|htmlspecialchars}',
                                '##Gallery_URL##' => '{$gallery.gallery_url|htmlspecialchars}',
                                '##Encoded_URL##' => '{$gallery.gallery_url|urlencode}',
                                '##Description##' => '{$gallery.description|htmlspecialchars}',
                                '##Thumbnails##' => '{$gallery.thumbnails|htmlspecialchars}',
                                '##Category##' => '{$gallery.category|htmlspecialchars}',
                                '##Thumbnail_URL##' => '{$gallery.preview_url|htmlspecialchars}',
                                '##Nickname##' => '{$gallery.nickname|htmlspecialchars}',
                                '##Icons##' => '{if $gallery.icons}{foreach var=$icon from=$gallery.icons}{$icon.icon_html}&nbsp;{/foreach}{/if}',                                
                                '##Date##' => '{$gallery.date|tdate::$config.date_format}',
                                '##Today##' => '{date value=\'today\' format=\'m-d-Y\'}',
                                '##Cheat_URL##' => '{$gallery.report_url|htmlspecialchars}');                       


$replace_global = array('##Thumbnails##' => '{$page_galleries}',
                        '##Galleries##' => '{$page_thumbnails}',
                        '##Total_Thumbs##' => '{$total_thumbnails|tnumber_format}',
                        '##Total_Galleries##' => '{$total_galleries|tnumber_format}',
                        '##Script_URL##' => '{$config.install_url}',
                        '##Category##' => '{$category.name|htmlspecialchars}',
                        '##Fixed_Category##' => '{$category.name|treplace_special::\'\'|strtolower|htmlspecialchars}',                        
                        '##Updated##' => '{date value=\'today\' format=\'m-d-Y h:i:a\'}',
                        '##Updated_Date##' => '{date value=\'today\' format=\'m-d-Y\'}',
                        '##Updated_Time##' => '{date value=\'today\' format=\'h:i:a\'}');


define('TGPX', TRUE);

require_once('../includes/common.php');
require_once("{$GLOBALS['BASE_DIR']}/includes/mysql.class.php");
require_once("{$GLOBALS['BASE_DIR']}/admin/includes/functions.php");

SetupRequest();

$DB = new DB($C['db_hostname'], $C['db_username'], $C['db_password'], $C['db_name']);
$DB->Connect();

$DB->Update('SET wait_timeout=86400');

@set_time_limit(0);

$from_shell = FALSE;
if( php_sapi_name() == 'cli' )
{
    $_REQUEST['r'] = 'ConvertData';
    $_REQUEST['directory'] = $_SERVER['argv'][1];
    $from_shell = TRUE;
}

if( isset($_REQUEST['r']) )
{
    call_user_func($_REQUEST['r']);
}
else
{
    DisplayMain();
}

$DB->Disconnect();

function ConvertData()
{
    global $C, $DB, $from_shell;
    
    $errors = array();
    if( !is_dir($_REQUEST['directory']) )
    {
        $errors[] = "The directory " . htmlspecialchars($_REQUEST['directory']) . " does not exist on your server";
        return DisplayMain($errors);
    }
    
    if( !is_file("{$_REQUEST['directory']}/agp.pl") )
    {
        $errors[] = "The agp.pl file could not be found in the " . htmlspecialchars($_REQUEST['directory']) . " directory; make sure you have version 3.0.0 or newer installed";
        return DisplayMain($errors);
    }
    
    if( !is_readable("{$_REQUEST['directory']}/agp.pl") )
    {
        $errors[] = "The agp.pl file in the " . htmlspecialchars($_REQUEST['directory']) . " directory could not be opened for reading";
        return DisplayMain($errors);
    }
    
    
    // Check version
    $version_file_contents = file_get_contents("{$_REQUEST['directory']}/agp.pl");    
    if( preg_match('~\$VERSION\s+=\s+\'(.*?)\'~', $version_file_contents, $matches) )
    {
        list($a, $b, $c) = explode('.', $matches[1]);
        
        $c = str_replace('-SS', '', $c);
        
        if( $a < 3 )
        {
            $errors[] = "Your AutoGallery Pro installation is outdated ({$matches[1]}); please upgrade to version 3.0.0+";
            return DisplayMain($errors);
        }
    }
    else
    {
        $errors[] = "Unable to extract version information from agp.pl; your version of AutoGallery Pro is likely too old";
        return DisplayMain($errors);
    }
    
    
    // Extract variables
    $var_file_contents = file_get_contents("{$_REQUEST['directory']}/data/variables");
    
    if( $var_file_contents === FALSE )
    {
        $errors[] = "Unable to read contents of the variables file";
        return DisplayMain($errors);
    }
    
    $vars = array();
                      
    if( preg_match_all('~^\$([a-z0-9_]+)\s+=\s+\'(.*?)\';$~msi', $var_file_contents, $matches, PREG_SET_ORDER) )
    {
        foreach( $matches as $match )
        {
            $vars[$match[1]] = $match[2];
        }
    }
    
    if( !isset($vars['ADMIN_EMAIL']) )
    {
        $errors[] = "Unable to extract variable data from the AutoGallery Pro variables file";
        return DisplayMain($errors);
    }
    
    if( !is_writable($C['font_dir']) )
    {
        $errors[] = "Change the permissions on the TGPX fonts directory to 777";
        return DisplayMain($errors);
    }
    
    if( $C['preview_dir'] == $vars['THUMB_DIR'] )
    {
        $errors[] = "The TGPX Thumbnail URL cannot be the same as the AutoGallery Pro Thumbnail URL";
        return DisplayMain($errors);
    }
    
    
    if( !$from_shell )
        echo "<pre>";
    
    // Copy fonts for validation codes
    FileAppend("{$GLOBALS['BASE_DIR']}/data/convert.log", "Copying font files for verification codes...\n");
    echo "Copying font files for verification codes...\n"; flush();
    $fonts =& DirRead($vars['FONT_DIR'], '^[^.]');
    foreach( $fonts as $font )
    {
        @copy("{$vars['FONT_DIR']}/$font", "{$C['font_dir']}/$font");
    }
    
    
    // Copy thumbnail previews
    FileAppend("{$GLOBALS['BASE_DIR']}/data/convert.log", "Copying thumbnail preview images...\n");
    echo "Copying thumbnail preview images...\n"; flush();
    $thumbs =& DirRead($vars['THUMB_DIR'], '\.jpg$');
    foreach( $thumbs as $thumb )
    {
        @copy("{$vars['THUMB_DIR']}/$thumb", "{$C['preview_dir']}/t_$thumb");
        @chmod("{$C['preview_dir']}/t_$thumb", 0666);
    }
    
    
    //
    // Dump e-mail log
    FileAppend("{$GLOBALS['BASE_DIR']}/data/convert.log", "Converting e-mail log...\n");
    echo "Converting e-mail log...\n"; flush();
    $emails = file("{$_REQUEST['directory']}/data/emails");
    $DB->Update('DELETE FROM `tx_email_log`');
    foreach( $emails as $email )
    {
        $email = trim($email);
        
        if( empty($email) )
            continue;
            
        $DB->Update('REPLACE INTO `tx_email_log` VALUES (?)', array($email));
    }
    
    
    //
    // Dump blacklist
    FileAppend("{$GLOBALS['BASE_DIR']}/data/convert.log", "Converting blacklist...\n");
    echo "Converting blacklist...\n"; flush();
    $DB->Update('DELETE FROM `tx_blacklist`');
    $types = array('submit_ip' => 'submitip',
                    'email' => 'email',
                    'url' => 'domain',
                    'domain_ip' => 'domainip',
                    'word' => 'word',
                    'html' => 'html',
                    'dns' => 'dns');
    foreach( $types as $new_type => $old_type )
    {
        $blist_items = file("{$_REQUEST['directory']}/data/blacklist/$old_type");
        foreach( $blist_items as $html )
        {
            $html = trim($html);
            
            if( empty($html) )
                continue;
                
            $regex = 0;
            if( strpos($html, '*') !== FALSE )
            {
                $regex = 1;
                $html = preg_quote($html);
                $html = str_replace('\*', '.*?', $html);
            }
            
            $DB->Update('INSERT INTO `tx_blacklist` VALUES (?,?,?,?,?)',
                        array(null,
                              $new_type,
                              $regex,
                              $html,
                              ''));     
        }
    }
    
    
    //
    // Dump whitelist
    FileAppend("{$GLOBALS['BASE_DIR']}/data/convert.log", "Converting whitelist...\n");
    echo "Converting whitelist...\n"; flush();
    $DB->Update('DELETE FROM `tx_whitelist`');
    $wlist_items = file("{$_REQUEST['directory']}/data/blacklist/whitelist");
    foreach( $wlist_items as $html )
    {
        $html = trim($html);
            
        if( empty($html) )
            continue;
                
        $regex = 0;
        if( strpos($html, '*') !== FALSE )
        {
            $regex = 1;
            $html = preg_quote($html);
            $html = str_replace('\*', '.*?', $html);
        }
        
        $DB->Update('INSERT INTO `tx_whitelist` VALUES (?,?,?,?,?,?,?,?,?,?)',
                    array(null,
                          'url',
                          $regex,
                          $html,
                          '',
                          1,
                          0,
                          0,
                          0,
                          0));                        
    }
    
    
    
    //
    // Dump reciprocal links
    FileAppend("{$GLOBALS['BASE_DIR']}/data/convert.log", "Converting reciprocal link settings...\n");
    echo "Converting reciprocal link settings...\n"; flush();
    $DB->Update('DELETE FROM `tx_reciprocals`');
    IniParse("{$_REQUEST['directory']}/data/generalrecips", TRUE, $recips);
    IniParse("{$_REQUEST['directory']}/data/trustedrecips", TRUE, $recips);
    foreach( $recips as $identifier => $html )
    {
        $regex = 0;
        if( strpos($html, '*') !== FALSE )
        {
            $regex = 1;
            $html = preg_quote($html);
            $html = str_replace('\*', '.*?', $html);
        }
        
        $DB->Update('INSERT INTO `tx_reciprocals` VALUES (?,?,?,?)',
                    array(null,
                          $identifier,
                          trim($html),
                          $regex));
    }

    
    
    //
    // Dump icons
    FileAppend("{$GLOBALS['BASE_DIR']}/data/convert.log", "Converting icons...\n");
    echo "Converting icons...\n"; flush();
    $icons = array();
    $DB->Update('DELETE FROM `tx_icons`');
    IniParse("{$_REQUEST['directory']}/data/icons", TRUE, $icons_ini);
    foreach( $icons_ini as $identifier => $html )
    {
        $identifier = trim($identifier);
        $html = trim($html);
        
        if( empty($identifier) || empty($html) )
        {
            continue;
        }
        
        $DB->Update('INSERT INTO `tx_icons` VALUES (?,?,?)',
                    array(null,
                          $identifier,
                          trim($html)));
        
        $icons[$identifier] = $DB->InsertID();
    }


    
    //
    // Dump categories
    FileAppend("{$GLOBALS['BASE_DIR']}/data/convert.log", "Converting categories...\n");
    echo "Converting categories...\n"; flush();
    $cat_format = array('Name','Type','Ext_Pictures','Ext_Movies','Min_Pictures','Min_Movies','Max_Pictures','Max_Movies','Size_Pictures','Size_Movies');
    $categories = array();
    $category_ids = array();
    $DB->Update('DELETE FROM `tx_categories`');
    $lines = file("{$_REQUEST['directory']}/data/dbs/categories");
    foreach( $lines as $line )
    {
        $line = trim($line);
        
        if( empty($line) )
        {
            continue;
        }
        
        $category = explode('|', $line);
        foreach( $cat_format as $index => $key )
        {
            $category[$key] = $category[$index];
        }
        
        $tag = CreateCategoryTag($category['Name']);
        $categories[$category['Name']] = $tag;
        
        $DB->Update('INSERT INTO `tx_categories` VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                    array(null,
                          $category['Name'],
                          $tag,
                          $category['Type'] != 'Movies' ? 1 : 0,
                          $category['Ext_Pictures'],
                          $category['Min_Pictures'],
                          $category['Max_Pictures'],
                          $category['Size_Pictures'],
                          "{$vars['THUMB_WIDTH']}x{$vars['THUMB_HEIGHT']}",
                          1,
                          null,
                          $category['Type'] != 'Pictures' ? 1 : 0,
                          $category['Ext_Movies'],
                          $category['Min_Movies'],
                          $category['Max_Movies'],
                          $category['Size_Movies'],
                          "{$vars['THUMB_WIDTH']}x{$vars['THUMB_HEIGHT']}",
                          1,
                          null,
                          -1,
                          0,
                          null,
                          null,
                          null));
                          
        $category_ids[$category['Name']] = $DB->InsertID();
    }

    
    
    
    //
    // Dump gallery data
    FileAppend("{$GLOBALS['BASE_DIR']}/data/convert.log", "Converting gallery data...\n");
    echo "Converting gallery data...\n"; flush();
    $DB->Update('DELETE FROM `tx_galleries`');
    $DB->Update('DELETE FROM `tx_gallery_fields`');
    $DB->Update('DELETE FROM `tx_gallery_icons`');
    $DB->Update('DELETE FROM `tx_gallery_previews`');
    $DB->Update('ALTER TABLE `tx_galleries` AUTO_INCREMENT=0');
    $DB->Update('ALTER TABLE `tx_gallery_previews` AUTO_INCREMENT=0');
    $gal_format = array('Gallery_ID','Email','Gallery_URL','Description','Thumbnails','Category','Nickname','Submit_Date','Approve_Date','Display_Date','Display_Stamp','Confirm_ID','Account_ID','CPanel_ID','Submit_IP','Gallery_IP','Scanned','Links','Has_Recip','Page_Bytes','Icons');
    $gal_dbs = array('unconfirmed' => 'unconfirmed', 'pending' => 'pending', 'approved' => 'used', 'archived' => 'used');
    foreach( array_keys($categories) as $cat_name )
    {
        $gal_dbs[preg_replace('~[^a-z0-9]~i', '', strtolower($cat_name))] = 'used';
    }
    
    foreach( $gal_dbs as $db => $status )
    {
        $db_file = "{$_REQUEST['directory']}/data/dbs/$db";
        
        if( is_file($db_file) )
        {
            $lines = file($db_file);
            
            foreach( $lines as $line )
            {
                $line = trim($line);
                
                if( empty($line) )
                {
                    continue;
                }
                
                $gallery = explode('|', $line);
                foreach( $gal_format as $index => $key )
                {
                    $gallery[$key] = $gallery[$index];
                }
                
                if( !preg_match('!^http(s)?://[\w-]+\.[\w-]+(\S+)?$!i', $gallery['Gallery_URL']) )
                {
                    continue;
                }
                
                $has_thumb = is_file("{$vars['THUMB_DIR']}/{$gallery['Gallery_ID']}.jpg");
                
                $DB->Update("INSERT INTO `tx_galleries` VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
                            array(null,
                                  $gallery['Gallery_URL'],
                                  $gallery['Description'],
                                  null,
                                  $gallery['Thumbnails'],
                                  $gallery['Email'],
                                  $gallery['Nickname'],
                                  $C['gallery_weight'],
                                  0,
                                  $gallery['Submit_IP'],
                                  $gallery['Gallery_IP'],
                                  null,
                                  'submitted',
                                  FMT_PICTURES,
                                  $status,
                                  null,
                                  "{$gallery['Submit_Date']} 12:00:00",
                                  "{$gallery['Submit_Date']} 12:00:00",
                                  empty($gallery['Approve_Date']) ? null : "{$gallery['Approve_Date']} 12:00:00",
                                  null,
                                  empty($gallery['Display_Date']) ? null : "{$gallery['Display_Date']} 12:00:00",
                                  null,
                                  $gallery['Account_ID'],
                                  $gallery['CPanel_ID'],
                                  null,
                                  null,
                                  $gallery['Has_Recip'],
                                  $has_thumb ? 1 : 0,
                                  1,
                                  1,
                                  0,
                                  0,
                                  0,
                                  null,
                                  MIXED_CATEGORY . " " . $categories[$gallery['Category']]));
                
                $gallery_id = $DB->InsertID();
                
                $gallery_info = array('gallery_id' => $gallery_id);
                $insert = CreateUserInsert('tx_gallery_fields', $gallery_info);
                $DB->Update('INSERT INTO `tx_gallery_fields` VALUES ('.$insert['bind_list'].')', $insert['binds']);
                
                foreach( explode(',', $gallery['Icons']) as $icon_id )
                {
                    if( isset($icons[$icon_id]) )
                    {
                        $DB->Update('INSERT INTO `tx_gallery_icons` VALUES (?,?)',
                                    array($gallery_id,
                                          $icons[$icon_id]));
                    }
                }
                
                if( !empty($has_thumb) )
                {
                    $dimensions = $vars['THUMB_WIDTH'].'x'.$vars['THUMB_HEIGHT'];
                    $DB->Update('INSERT INTO `tx_gallery_previews` VALUES (?,?,?,?)',
                                array(null,
                                      $gallery_id,
                                      '',
                                      $dimensions));
                                      
                    $preview_id = $DB->InsertID();
                    $gallery['Thumbnail_URL'] = "{$C['preview_url']}/$preview_id.jpg";
                    $DB->Update('UPDATE `tx_gallery_previews` SET `preview_url`=? WHERE `preview_id`=?', array($gallery['Thumbnail_URL'], $preview_id));
                    @rename("{$C['preview_dir']}/t_{$gallery['Gallery_ID']}.jpg", "{$C['preview_dir']}/$preview_id.jpg");
                }
            }
        }
    }
    
    
    //
    // Convert permanent gallery data
    $perm_format = array('Permanent_ID','Gallery_URL','Category','Thumbnails','Description','Nickname','Location','Thumbnail_URL','Start_Date','Expire_Date');
    $lines = file("{$_REQUEST['directory']}/data/dbs/permanent");
    foreach( $lines as $line )
    {
        $line = trim($line);
        
        if( empty($line) )
        {
            continue;
        }
        
        $gallery = explode('|', $line);
        foreach( $perm_format as $index => $key )
        {
            $gallery[$key] = $gallery[$index];
        }
        
        if( !preg_match('!^http(s)?://[\w-]+\.[\w-]+(\S+)?$!i', $gallery['Gallery_URL']) )
        {
            continue;
        }
        
        $has_thumb = is_file("{$vars['THUMB_DIR']}/p{$gallery['Permanent_ID']}.jpg");
        
        $DB->Update("INSERT INTO `tx_galleries` VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
                    array(null,
                          $gallery['Gallery_URL'],
                          $gallery['Description'],
                          null,
                          $gallery['Thumbnails'],
                          $C['from_email'],
                          $gallery['Nickname'],
                          $C['gallery_weight'],
                          0,
                          $_SERVER['REMOTE_ADDR'],
                          null,
                          null,
                          'permanent',
                          FMT_PICTURES,
                          'approved',
                          null,
                          MYSQL_NOW,
                          MYSQL_NOW,
                          MYSQL_NOW,
                          null,
                          null,
                          null,
                          null,
                          'AGP Import',
                          null,
                          null,
                          0,
                          $has_thumb ? 1 : 0,
                          1,
                          1,
                          0,
                          0,
                          0,
                          null,
                          MIXED_CATEGORY . " " . $categories[$gallery['Category']]));
        
        $gallery_id = $DB->InsertID();
        
        $gallery_info = array('gallery_id' => $gallery_id);
        $insert = CreateUserInsert('tx_gallery_fields', $gallery_info);
        $DB->Update('INSERT INTO `tx_gallery_fields` VALUES ('.$insert['bind_list'].')', $insert['binds']);
        
        foreach( explode(',', $gallery['Icons']) as $icon_id )
        {
            if( isset($icons[$icon_id]) )
            {
                $DB->Update('INSERT INTO `tx_gallery_icons` VALUES (?,?)',
                            array($gallery_id,
                                  $icons[$icon_id]));
            }
        }
        
        if( !empty($has_thumb) )
        {
            $DB->Update('INSERT INTO `tx_gallery_previews` VALUES (?,?,?,?)',
                        array(null,
                              $gallery_id,
                              '',
                              $vars['THUMB_WIDTH'].'x'.$vars['THUMB_HEIGHT']));
                              
            $preview_id = $DB->InsertID();
            $gallery['Thumbnail_URL'] = "{$C['preview_url']}/$preview_id.jpg";
            $DB->Update('UPDATE `tx_gallery_previews` SET `preview_url`=? WHERE `preview_id`=?', array($gallery['Thumbnail_URL'], $preview_id));
            @rename("{$C['preview_dir']}/t_p{$gallery['Permanent_ID']}.jpg", "{$C['preview_dir']}/$preview_id.jpg");
        }
    }
    
            
    //
    // Dump partner data
    FileAppend("{$GLOBALS['BASE_DIR']}/data/convert.log", "Converting partner accounts...\n");
    echo "Converting partner accounts...\n"; flush();
    $DB->Update('DELETE FROM `tx_partners`');
    $DB->Update('DELETE FROM `tx_partner_fields`');
    $DB->Update('DELETE FROM `tx_partner_icons`');
    $DB->Update('DELETE FROM `tx_partner_confirms`');
    $acct_format = array('Account_ID','Password','Email','Allowed','Auto_Approve','Recip','Blacklist','HTML','Icons');
    $lines = file("{$_REQUEST['directory']}/data/dbs/accounts");
    foreach( $lines as $line )
    {
        $line = trim($line);
        
        if( empty($line) )
        {
            continue;
        }
        
        $partner = explode('|', $line);
        foreach( $acct_format as $index => $key )
        {
            $partner[$key] = $partner[$index];
        }
        
        $DB->Update('INSERT INTO `tx_partners` VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                    array($partner['Account_ID'],
                          sha1($partner['Password']),
                          '',
                          $partner['Email'],
                          null,
                          MYSQL_NOW,
                          null,
                          null,
                          null,
                          $partner['Allowed'],
                          $C['gallery_weight'],
                          null,
                          0,
                          null,
                          0,
                          0,
                          0,
                          'active',
                          null,
                          null,
                          0,
                          $partner['Recip'] ? 0 : 1,
                          $partner['Auto_Approve'],
                          1,
                          $partner['Blacklist'] ? 0 : 1));
                          
        $partner_info = array('username' => $partner['Account_ID']);
        $insert = CreateUserInsert('tx_partner_fields', $partner_info);
        $DB->Update('INSERT INTO `tx_partner_fields` VALUES ('.$insert['bind_list'].')', $insert['binds']);
        
        foreach( explode(',', $partner['Icons']) as $icon_id )
        {
            if( isset($icons[$icon_id]) )
            {
                $DB->Update('INSERT INTO `tx_partner_icons` VALUES (?,?)',
                            array($partner['Account_ID'],
                                  $icons[$icon_id]));
            }
        }
    }


    
    // Update the stored thumbnail preview sizes
    UpdateThumbSizes($vars['THUMB_WIDTH'].'x'.$vars['THUMB_HEIGHT']);
    
    
    //
    // Dump TGP page data
    FileAppend("{$GLOBALS['BASE_DIR']}/data/convert.log", "Converting TGP pages...\n");
    echo "Converting TGP pages...\n"; flush();
    $build_order = 1;
    $DB->Update('DELETE FROM `tx_pages`');
    $DB->Update('ALTER TABLE `tx_pages` AUTO_INCREMENT=0');
    $pages = GetPageList($vars, $categories);
    foreach( $pages as $page )
    {
        $template = file_get_contents($page['template']);
        $template = trim(ConvertTemplate($template, $page['arch']));
        $compiled = '';
        
        $DB->Update('INSERT INTO `tx_pages` VALUES (?,?,?,?,?,?,?,?,?)',
                    array(null,
                          $page['file'],
                          $page['url'],
                          $page['category'] == 'Mixed' ? null : $category_ids[$page['category']],
                          $build_order++,
                          0,
                          null,
                          $template,
                          $compiled));
    }

    FileAppend("{$GLOBALS['BASE_DIR']}/data/convert.log", "\nData conversion complete!");
    echo "\nData conversion complete!\n";
    
    if( !$from_shell )
        echo "</pre>";
}

function GetPageList(&$vars, &$categories)
{
    $pages = array();

    foreach( explode(',', $vars['MAIN_PAGES']) as $page )
    {
        $page = trim($page);
        
        if( empty($page) )
        {
            continue;
        }
        
        $pages[$page] = array('arch' => FALSE,
                              'category' => 'Mixed',
                              'template' => "{$_REQUEST['directory']}/data/html/$page",
                              'file' => "{$vars['MAIN_DIR']}/$page",
                              'url' => "{$vars['MAIN_URL']}/$page");
    }

    if( $vars['ARCH_TYPE'] == 'Mixed' )
    {
        foreach( range(1, $vars['ARCH_PAGES']) as $count )
        {
            $fname = "archive".($count > 1 ? $count : '').".{$vars['ARCH_EXTENSION']}";
            
            $pages[$fname] = array('arch' => TRUE,
                                   'category' => 'Mixed',
                                   'template' => "{$_REQUEST['directory']}/data/html/$fname",
                                   'file' => "{$vars['ARCHIVE_DIR']}/$fname",
                                   'url' => "{$vars['ARCHIVE_URL']}/$fname");
        }
    }
    else if( $vars['ARCH_TYPE'] == 'Category' )
    {
        foreach( $categories as $name => $junk )
        {
            $db_name = preg_replace('~[^a-z0-9]~i', '', strtolower($name));
            
            foreach( range(1, $vars['ARCH_PAGES']) as $count )
            {
                $fname = "$db_name".($count > 1 ? $count : '').".{$vars['ARCH_EXTENSION']}";
            
                $pages[$fname] = array('arch' => TRUE,
                                       'category' => $name,
                                       'template' => "{$_REQUEST['directory']}/data/html/$fname",
                                       'file' => "{$vars['ARCHIVE_DIR']}/$fname",
                                       'url' => "{$vars['ARCHIVE_URL']}/$fname");
            }
        }
    }

    return $pages;
}

function ConvertTemplate($template, $archive)
{
    global $replace_global;
    
    if( $archive )
    {
        $template = "{define name=globaldupes value=false}\n" .
                    "{define name=pagedupes value=false}\n\n" .
                    $template;
    }
    else
    {
        $template = "{define name=globaldupes value=true}\n" .
                    "{define name=pagedupes value=false}\n\n" .
                    $template;
    }

    $template = preg_replace_callback('~<%([A-Z]+)$(.*?)%>~msi', 'ProcessDirectives', $template);
    $template = str_replace(array_keys($replace_global), array_values($replace_global), $template);
    $template = str_replace('submit.cgi', 'submit.php', $template);
    
    return $template;
}

function ProcessDirectives($matches)
{
    global $replace_categories_order, $replace_galleries_order, $replace_categories_html, $replace_galleries_html;
    
    $directive = $matches[1];
    $options = $matches[2];
    $sub_inserts = ExtractSubs('INSERT', $options);
    $sub_permanent = ExtractSubs('PERMANENT', $options);
    $options = ExtractOptions($options);
    $output = '';
    
    switch($matches[1])
    {
        case 'PERMANENT':
        case 'GALLERIES':
            $main_opts = ConvertGalleriesOptions($options, null, $directive);
            
            $html = isset($GLOBALS['HTML'][$options['HTML']]) ? $GLOBALS['HTML'][$options['HTML']] : $options['HTML'];
            $html = str_replace(array_keys($replace_galleries_html), array_values($replace_galleries_html), $html);
            
            $output = "{galleries\nvar=\$galleries\n" . join("\n", $main_opts) . "}\n";
            
            if( count($sub_permanent) )
            {                   
                $sub_opts = ConvertGalleriesOptions($sub_permanent[0], $options['AMOUNT'], 'PERMANENT');
                
                $output .= "{galleries\nvar=\$sub_permanent\n" . join("\n", $sub_opts) . "}\n" .
                           "{intermix var=\$galleries from=\$galleries,\$sub_permanent location=".$sub_permanent[0]['LOCATION']."}\n";
            }
            
            $output .= "\n{foreach var=\$gallery from=\$galleries counter=\$counter}\n" .
                       $html . "\n";
                       
            foreach( $sub_inserts as $insert )
            {
                $output .= "{insert counter=\$counter location=".$insert['LOCATION']."}\n" .
                           $insert['HTML'] . "\n" .
                           "{/insert}\n";
            }
            
            $output .= "{/foreach}";
            
            break;                  

        case 'TEMPLATE':
            $GLOBALS['HTML'][$options['NAME']] = $options['HTML'];
            break;
    }
    
    return $output;
}



function ConvertGalleriesOptions($options, $parent_amount, $directive)
{
    global $replace_galleries_order;
    
    $newopts = array('getnew=true', 'allowused=true', 'order=date_approved', 'reorder=date_displayed DESC, date_approved');                
 
    switch($directive)
    {
        case 'PERMANENT':
            $newopts[] = 'type=permanent';
            break;
            
        default:
            $newopts[] = 'type=submitted';
            break;
    }
    
    switch($options['TYPE'])
    {
        case 'Text':
            $newopts[] = 'preview=any';
            break;
            
        case 'Thumb':
            $newopts[] = 'preview=true';
            break;
    }
    
    switch($options['CATEGORY'])
    {
        case 'Mixed':
        case '':            
            $newopts[] = 'category=MIXED';
            break;
            
        default:
            $newopts[] = 'category=' . $options['CATEGORY'];
            break;
    }    
    
    if( isset($options['LOCATION']) )
    {
        $options['LOCATION'] = trim($options['LOCATION']);
        
        // Format: +5        
        if( preg_match('~\+(\d+)~', $options['LOCATION'], $matches) )
        {
            $newopts[] = 'amount=' . round($parent_amount / $matches[1]);
        }
        
        // Format: 5
        else if( is_numeric($options['LOCATION']) )
        {
            $newopts[] = 'amount=1';
        }
        
        // Format: 5,10,15
        else if( strpos($options['LOCATION'], ',') !== FALSE )
        {
            $locations = explode(',', $options['LOCATION']);
            $newopts[] = 'amount=' . count($locations);
        }
    }
    else
    {
        $newopts[] = 'amount=' . $options['AMOUNT'];
    }
    
    return $newopts;
}

function ExtractOptions(&$options)
{
    $opts = array();
    
    if( preg_match_all('~([A-Z]+)\s+(.*?)$~ms', $options, $matches, PREG_SET_ORDER) )
    {
        foreach( $matches as $match )
        {
            $opts[trim($match[1])] = trim($match[2]);
        }
    }
    
    return $opts;
}

function ExtractSubs($directive, &$options)
{
    $sub_options = array();
    
    if( preg_match_all("~$directive\s+\{(.*?)\}~msi", $options, $matches, PREG_SET_ORDER) )
    {
        foreach( $matches as $match )
        {
            $sub_options[] = ExtractOptions($match[1]);
        }
    }
    
    $options = preg_replace("~$directive\s+\{(.*?)\}~msi", '', $options);
    
    return $sub_options;
}

function DisplayMain($errors = null)
{
    global $from_shell;
    
    if( $from_shell )
    {
        if( !empty($errors) )
        {
            echo "The following errors were encountered:\n";
            foreach( $errors as $error )
            {
                echo "- $error\n";
            }
            echo "\n";
        }
    }
    else
    {
    $_REQUEST['directory'] = htmlspecialchars($_REQUEST['directory']);
    
echo <<<OUT
<html>
<head>
  <title>Convert AutoGallery Pro Data</title>
  <style>
  body, form, input { font-family: Tahoma; font-size: 9pt; }
  </style>
</head>
<body>
OUT;


if( !empty($errors) )
{
    echo '<div style="font-weight: bold; color: #d52727; padding: 4px 10px 4px 10px; background-color: #FEE7E8;">' .
         'The following errors were encountered:<ol>';
    foreach( $errors as $error )
    {
        echo "<li> $error<br />";
    }
    echo "</ol></div>";
OUT;
}


echo <<<OUT
<center>
<form method="POST" action="agp-convert.php" style="margin-top: 20px;" onsubmit="return confirm('Are you sure you want to convert this data to TGPX format?')">
<div style="margin-bottom: 5px; font-weight: bold;">Enter the full directory path to the AutoGallery Pro installation:</div>
<input type="text" name="directory" size="80" value="{$_REQUEST['directory']}"><br />
<input type="submit" value="Convert Data" style="margin-top: 10px;">
<input type="hidden" name="r" value="ConvertData">
</form>
</center>

</body>
</html>
OUT;
    }
}

?>
