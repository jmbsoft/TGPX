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

$replace_galleries_order = array('Display_Date' => 'date_displayed',
                                 'Approve_Stamp' => 'date_approved',
                                 'Approve_Date' => 'date_approved',
                                 'Times_Selected' => 'times_selected',
                                 'Clicks' => 'clicks',
                                 'Build_Counter' => 'build_counter',
                                 'Used_Counter' => 'used_counter',
                                 'Weight' => 'weight',
                                 'RAND()' => 'RAND()',
                                 'Thumbnails' => 'thumbnails');

$replace_galleries_html = array('##Gallery_ID##' => '{$gallery.gallery_id|htmlspecialchars}',
                                '##Gallery_URL##' => '{$gallery.gallery_url|htmlspecialchars}',
                                '##Encoded_URL##' => '{$gallery.gallery_url|urlencode}',
                                '##Rss_URL##' => '{$gallery.gallery_url|htmlspecialchars}',
                                '##Description##' => '{$gallery.description|htmlspecialchars}',
                                '##Trimmed_Description##' => '{$gallery.description|htmlspecialchars}',
                                '##Rss_Description##' => '{$gallery.description|htmlspecialchars}',
                                '##Thumbnails##' => '{$gallery.thumbnails|htmlspecialchars}',
                                '##Category##' => '{$gallery.category|htmlspecialchars}',
                                '##Sponsor##' => '{$gallery.sponsor|htmlspecialchars}',
                                '##Thumbnail_URL##' => '{$gallery.preview_url|htmlspecialchars}',
                                '##Thumb_Width##' => '{$gallery.preview_width|htmlspecialchars}',
                                '##Thumb_Height##' => '{$gallery.preview_height|htmlspecialchars}',
                                '##Weight##' => '{$gallery.weight|tnumber_format}',
                                '##Nickname##' => '{$gallery.nickname|htmlspecialchars}',
                                '##Clicks##' => '{$gallery.clicks|tnumber_format}',
                                '##Productivity##' => '{$gallery.productivity|htmlspecialchars}',
                                '##Format##' => '{$gallery.format|htmlspecialchars}',
                                '##Icons##' => '{if $gallery.icons}{foreach var=$icon from=$gallery.icons}{$icon.icon_html}&nbsp;{/foreach}{/if}',
                                '##Times_Selected##' => '{$gallery.times_selected|tnumber_format}',
                                '##Used_Counter##' => '{$gallery.used_counter|tnumber_format}',
                                '##Build_Counter##' => '{$gallery.build_counter|tnumber_format}',
                                '##Keywords##' => '{$gallery.keywords|htmlspecialchars}',
                                '##Date##' => '{$gallery.date|tdate::$config.date_format}',
                                '##Last_Date##' => '{$gallery.date|tdate::$config.date_format}',
                                '##Today##' => '{date value=\'today\' format=\'m-d-Y\'}',
                                '##Cheat_URL##' => '{$gallery.report_url|htmlspecialchars}');

$replace_categories_order = array('Name' => 'name',
                                  'Used' => 'used',
                                  'Galleries' => 'galleries',
                                  'Clicks' => 'clicks',
                                  'Build_Counter' => 'build_counter');

$replace_categories_html = array('##Name##' => '{$category.name|htmlspecialchars}',
                                 '##Page##' => '{$category.page_url|htmlspecialchars}',
                                 '##Galleries##' => '{$category.galleries|tnumber_format}',
                                 '##Used##' => '{$category.used|tnumber_format}',
                                 '##Clicks##' => '{$category.clicks|tnumber_format}');                            
                                 
$replace_global = array('##Thumbnails##' => '{$page_galleries}',
                        '##Galleries##' => '{$page_thumbnails}',
                        '##Total_Thumbs##' => '{$total_thumbnails|tnumber_format}',
                        '##Total_Galleries##' => '{$total_galleries|tnumber_format}',
                        '##Script_URL##' => '{$config.install_url}',
                        '##Category##' => '{$page_category.name|htmlspecialchars}',
                        '##CATEGORY##' => '{$page_category.name|strtoupper|htmlspecialchars}',
                        '##category##' => '{$page_category.name|strtolower|htmlspecialchars}',
                        '##FixedCategory##' => '{$page_category.name|treplace_special::\'\'|htmlspecialchars}',
                        '##Fixed-Category##' => '{$page_category.name|treplace_special::\'-\'|htmlspecialchars}',
                        '##Fixed_Category##' => '{$page_category.name|treplace_special::\'_\'|htmlspecialchars}',
                        '##fixedcategory##' => '{$page_category.name|strtolower|treplace_special::\'\'|htmlspecialchars}',
                        '##fixed-category##' => '{$page_category.name|strtolower|treplace_special::\'-\'|htmlspecialchars}',
                        '##fixed_category##' => '{$page_category.name|strtolower|treplace_special::\'_\'|htmlspecialchars}',
                        '##Updated##' => '{date value=\'today\' format=\'m-d-Y h:i:a\'}',
                        '##Updated_Date##' => '{date value=\'today\' format=\'m-d-Y\'}',
                        '##Updated_Time##' => '{date value=\'today\' format=\'h:i:a\'}',
                        '##Weekday##' => '{datelocale value="today" format="%A"}',
                        '##Weekday-1##' => '{datelocale value="-1 day" format="%A"}',
                        '##Weekday-2##' => '{datelocale value="-2 day" format="%A"}',
                        '##Weekday-3##' => '{datelocale value="-3 day" format="%A"}',
                        '##Weekday-4##' => '{datelocale value="-4 day" format="%A"}',
                        '##Weekday-4##' => '{datelocale value="-5 day" format="%A"}',
                        '##Weekday-6##' => '{datelocale value="-6 day" format="%A"}',
                        '##Weekday-7##' => '{datelocale value="-7 day" format="%A"}',
                        '##Weekday-8##' => '{datelocale value="-8 day" format="%A"}',
                        '##Weekday-9##' => '{datelocale value="-9 day" format="%A"}',
                        '##Today##' => '{date value=\'today\' format=\'m-d-Y\'}',
                        '##Today-1##' => '{date value=\'-1 day\' format=\'m-d-Y\'}',
                        '##Today-2##' => '{date value=\'-2 day\' format=\'m-d-Y\'}',
                        '##Today-3##' => '{date value=\'-3 day\' format=\'m-d-Y\'}',
                        '##Today-4##' => '{date value=\'-4 day\' format=\'m-d-Y\'}',
                        '##Today-5##' => '{date value=\'-5 day\' format=\'m-d-Y\'}',
                        '##Today-6##' => '{date value=\'-6 day\' format=\'m-d-Y\'}',
                        '##Today-7##' => '{date value=\'-7 day\' format=\'m-d-Y\'}',
                        '##Today-8##' => '{date value=\'-8 day\' format=\'m-d-Y\'}',
                        '##Today-9##' => '{date value=\'-9 day\' format=\'m-d-Y\'}');


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
    
    if( !is_file("{$_REQUEST['directory']}/ags.pl") )
    {
        $errors[] = "The ags.pl file could not be found in the " . htmlspecialchars($_REQUEST['directory']) . " directory";
        return DisplayMain($errors);
    }
    
    if( !is_readable("{$_REQUEST['directory']}/ags.pl") )
    {
        $errors[] = "The ags.pl file in the " . htmlspecialchars($_REQUEST['directory']) . " directory could not be opened for reading";
        return DisplayMain($errors);
    }
    
    
    // Check version
    $version_file_contents = file_get_contents("{$_REQUEST['directory']}/ags.pl");    
    if( preg_match('~\$VERSION\s+=\s+\'(.*?)\'~', $version_file_contents, $matches) )
    {        
        if( $matches[1] != '3.6.2-SS' )
        {
            $errors[] = "Your AutoGallery SQL installation is outdated ({$matches[1]}); please upgrade to version 3.6.2-SS";
            return DisplayMain($errors);
        }
    }
    else
    {
        $errors[] = "Unable to extract version information from ags.pl; your version of AutoGallery SQL is likely too old";
        return DisplayMain($errors);
    }
    
    
    // Extract MySQL information
    $mysql_file_contents = file_get_contents("{$_REQUEST['directory']}/data/variables");
    
    if( $mysql_file_contents === FALSE )
    {
        $errors[] = "Unable to read contents of the variables file";
        return DisplayMain($errors);
    }
    
    $vars = array();
                      
    if( preg_match_all('~^\$([a-z0-9_]+)\s+=\s+\'(.*?)\';$~msi', $mysql_file_contents, $matches, PREG_SET_ORDER) )
    {
        foreach( $matches as $match )
        {
            $vars[$match[1]] = $match[2];
        }
    }
    
    if( !isset($vars['USERNAME']) || !isset($vars['DATABASE']) || !isset($vars['HOSTNAME']) )
    {
        $errors[] = "Unable to extract MySQL database information from the variables file";
        return DisplayMain($errors);
    }
    
    
    if( !is_writable("{$GLOBALS['BASE_DIR']}/annotations") )
    {
        $errors[] = "Change the permissions on the TGPX annotations directory to 777";
        return DisplayMain($errors);
    }
    
    if( !is_writable($C['font_dir']) )
    {
        $errors[] = "Change the permissions on the TGPX fonts directory to 777";
        return DisplayMain($errors);
    }
    
    if( $C['preview_dir'] == $vars['THUMB_DIR'] )
    {
        $errors[] = "The TGPX Thumbnail URL cannot be the same as the AutoGallery SQL Thumbnail URL";
        return DisplayMain($errors);
    }
    
    
    $CONVERTDB = new DB($vars['HOSTNAME'], $vars['USERNAME'], $vars['PASSWORD'], $vars['DATABASE']);
    $CONVERTDB->Connect();
    
    $CONVERTDB->Update('SET wait_timeout=86400');
    
    
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
    
    
    // Copy annotations
    FileAppend("{$GLOBALS['BASE_DIR']}/data/convert.log", "Copying annotation font files and images...\n");
    echo "Copying annotation font files and images...\n"; flush();
    $annotations =& DirRead($vars['ANNOTATION_DIR'], '^[^.]');
    foreach( $annotations as $annotation )
    {
        @copy("{$vars['ANNOTATION_DIR']}/$annotation", "{$GLOBALS['BASE_DIR']}/annotations/$annotation");
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
                    'headers' => 'headers',
                    'dns' => 'dns');
    foreach( $types as $new_type => $old_type )
    {
        if( is_file("{$_REQUEST['directory']}/data/blacklist/$old_type") )
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
                          $html,
                          $regex));
    }
    
    
    //
    // Dump 2257 code
    FileAppend("{$GLOBALS['BASE_DIR']}/data/convert.log", "Converting 2257 search code...\n");
    echo "Converting 2257 search code...\n"; flush();
    $counter = 1;
    $c2257s = file("{$_REQUEST['directory']}/data/2257");
    $DB->Update('DELETE FROM `tx_2257`');
    foreach( $c2257s as $html )
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
        
        $DB->Update('INSERT INTO `tx_2257` VALUES (?,?,?,?)',
                    array(null,
                          "AGS Converted #$counter",
                          $html,
                          $regex));
                          
        $counter++;
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
                          $html));
        
        $icons[$identifier] = $DB->InsertID();
    }
    
    
    //
    // Dump annotations
    FileAppend("{$GLOBALS['BASE_DIR']}/data/convert.log", "Converting annotation settings...\n");
    echo "Converting annotation settings...\n"; flush();
    $annotations = array();
    $DB->Update('DELETE FROM `tx_annotations`');
    $result = $CONVERTDB->Query('SELECT * FROM `ags_Annotations`');
    while( $annotation = $CONVERTDB->NextRow($result) )
    {
        $DB->Update('INSERT INTO `tx_annotations` VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
                    array(null,
                          $annotation['Identifier'],
                          strtolower($annotation['Type']),
                          $annotation['String'],
                          0,
                          $annotation['Font_File'],
                          $annotation['Size'],
                          $annotation['Color'],
                          $annotation['Shadow'],
                          $annotation['Image_File'],
                          $annotation['Transparency'],
                          $annotation['Location']));
        
        $annotations[$annotation['Unique_ID']] = $DB->InsertID();
    }
    $CONVERTDB->Free($result);

    
    //
    // Dump categories
    FileAppend("{$GLOBALS['BASE_DIR']}/data/convert.log", "Converting categories...\n");
    echo "Converting categories...\n"; flush();
    $categories = array();
    $category_ids = array();
    $DB->Update('DELETE FROM `tx_categories`');
    $result = $CONVERTDB->Query('SELECT * FROM `ags_Categories`');
    while( $category = $CONVERTDB->NextRow($result) )
    {
        $tag = CreateCategoryTag($category['Name']);
        $categories[$category['Name']] = $tag;
        
        $DB->Update('INSERT INTO `tx_categories` VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                    array(null,
                          $category['Name'],
                          $tag,
                          empty($category['Ext_Pictures']) ? 0 : 1,
                          $category['Ext_Pictures'],
                          $category['Min_Pictures'],
                          $category['Max_Pictures'],
                          $category['Size_Pictures'],
                          "{$vars['THUMB_WIDTH']}x{$vars['THUMB_HEIGHT']}",
                          1,
                          $annotations[$category['Ann_Pictures']],
                          empty($category['Ext_Movies']) ? 0 : 1,
                          $category['Ext_Movies'],
                          $category['Min_Movies'],
                          $category['Max_Movies'],
                          $category['Size_Movies'],
                          "{$vars['THUMB_WIDTH']}x{$vars['THUMB_HEIGHT']}",
                          1,
                          $annotations[$category['Ann_Movies']],
                          $category['Per_Day'],
                          $category['Hidden'],
                          null,
                          null,
                          null));
                          
        $category_ids[$category['Name']] = $DB->InsertID();
    }
    $CONVERTDB->Free($result);


    //
    // Dump sponsors
    FileAppend("{$GLOBALS['BASE_DIR']}/data/convert.log", "Converting sponsors...\n");
    echo "Converting sponsors...\n"; flush();
    $counter = 1;
    $sponsors = array();
    $DB->Update('DELETE FROM `tx_sponsors`');
    $result = $CONVERTDB->Query('SELECT DISTINCT `Sponsor` FROM `ags_Galleries` WHERE `Sponsor`!=?', array(''));
    while( $sponsor = $CONVERTDB->NextRow($result) )
    {
        $sponsors[$sponsor['Sponsor']] = $counter;
        $DB->Update("INSERT INTO `tx_sponsors` VALUES (?,?,?)",
                    array($counter++,
                          $sponsor['Sponsor'],
                          null));
    }
    $CONVERTDB->Free($result);
    
    
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
    $result = $CONVERTDB->Query('SELECT * FROM `ags_Galleries` ORDER BY `Gallery_ID`');
    $preview_sizes = array();
    while( $gallery = $CONVERTDB->NextRow($result) )
    {
        $DB->Update("INSERT INTO `tx_galleries` VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
                    array(null,
                          $gallery['Gallery_URL'],
                          $gallery['Description'],
                          $gallery['Keywords'],
                          $gallery['Thumbnails'],
                          $gallery['Email'],
                          $gallery['Nickname'],
                          $gallery['Weight'],
                          $gallery['Clicks'],
                          $gallery['Submit_IP'],
                          $gallery['Gallery_IP'],
                          !empty($gallery['Sponsor']) ? $sponsors[$gallery['Sponsor']] : null,
                          strtolower($gallery['Type']),
                          strtolower($gallery['Format']),
                          strtolower($gallery['Status']),
                          $gallery['Status'] == 'Disabled' ? 'approved' : null,
                          date(DF_DATETIME, TimeWithTz($gallery['Added_Stamp'])),
                          date(DF_DATETIME, TimeWithTz($gallery['Added_Stamp'])),
                          empty($gallery['Approve_Stamp']) ? null : date(DF_DATETIME, TimeWithTz($gallery['Approve_Stamp'])),
                          empty($gallery['Scheduled_Date']) ? null : "{$gallery['Scheduled_Date']} 00:00:00",
                          empty($gallery['Display_Date']) ? null : "{$gallery['Display_Date']} 12:00:00",
                          empty($gallery['Delete_Date']) ? null : "{$gallery['Delete_Date']} 00:00:00",
                          $gallery['Account_ID'],
                          $gallery['Moderator'],
                          $gallery['Comments'],
                          null,
                          $gallery['Has_Recip'],
                          empty($gallery['Thumbnail_URL']) ? 0 : 1,
                          $gallery['Allow_Scan'],
                          $gallery['Allow_Thumb'],
                          $gallery['Times_Selected'],
                          $gallery['Used_Counter'],
                          $gallery['Build_Counter'],
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
        
        if( !empty($gallery['Thumbnail_URL']) )
        {
            $dimensions = '';
            if( !empty($gallery['Thumb_Width']) && !empty($gallery['Thumb_Height']) )
            {
                $dimensions = "{$gallery['Thumb_Width']}x{$gallery['Thumb_Height']}";
                $preview_sizes[$dimensions] = TRUE;
            }
            
            $DB->Update('INSERT INTO `tx_gallery_previews` VALUES (?,?,?,?)',
                        array(null,
                              $gallery_id,
                              '',
                              $dimensions));
                              
            $preview_id = $DB->InsertID();
            
            if( preg_match('~^'.preg_quote($vars['THUMB_URL']).'~i', $gallery['Thumbnail_URL']) )
            {
                $gallery['Thumbnail_URL'] = "{$C['preview_url']}/$preview_id.jpg";
                $DB->Update('UPDATE `tx_gallery_previews` SET `preview_url`=? WHERE `preview_id`=?', array($gallery['Thumbnail_URL'], $preview_id));
                @rename("{$C['preview_dir']}/t_{$gallery['Gallery_ID']}.jpg", "{$C['preview_dir']}/$preview_id.jpg");
            }
        }
    }
    $CONVERTDB->Free($result);
    
    
    //
    // Dump partner data
    FileAppend("{$GLOBALS['BASE_DIR']}/data/convert.log", "Converting partner accounts...\n");
    echo "Converting partner accounts...\n"; flush();
    $DB->Update('DELETE FROM `tx_partners`');
    $DB->Update('DELETE FROM `tx_partner_fields`');
    $DB->Update('DELETE FROM `tx_partner_icons`');
    $DB->Update('DELETE FROM `tx_partner_confirms`');
    $result = $CONVERTDB->Query('SELECT * FROM `ags_Accounts`');
    while( $partner = $CONVERTDB->NextRow($result) )
    {
        $DB->Update('INSERT INTO `tx_partners` VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                    array($partner['Account_ID'],
                          sha1($partner['Password']),
                          '',
                          $partner['Email'],
                          null,
                          MYSQL_NOW,
                          $partner['Submitted'] > 0 ? MYSQL_NOW : null,
                          empty($partner['Start_Date']) ? null : "{$partner['Start_Date']} 00:00:00",
                          empty($partner['End_Date']) ? null : "{$partner['End_Date']} 23:59:59",
                          $partner['Allowed'],
                          round($partner['Weight']),
                          null,
                          0,
                          null,
                          0,
                          $partner['Submitted'],
                          $partner['Removed'],
                          'active',
                          null,
                          null,
                          0,
                          $partner['Check_Recip'] ? 0 : 1,
                          $partner['Auto_Approve'],
                          $partner['Confirm'] ? 0 : 1,
                          $partner['Check_Black'] ? 0 : 1));
                          
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
    $CONVERTDB->Free($result);

    
    // Update the stored thumbnail preview sizes
    $sizes = unserialize(GetValue('preview_sizes'));    
    if( !is_array($sizes) )
    {
        $sizes = array();
    }    
    $sizes = array_merge($sizes, array_keys($preview_sizes));    
    StoreValue('preview_sizes', serialize(array_unique($sizes)));
    
    
    
    //
    // Dump TGP page data
    FileAppend("{$GLOBALS['BASE_DIR']}/data/convert.log", "Converting TGP pages...\n");
    echo "Converting TGP pages...\n"; flush();
    $build_order = 1;
    $docroot_url = parse_url($vars['CGI_URL']);
    $DB->Update('DELETE FROM `tx_pages`');
    $DB->Update('ALTER TABLE `tx_pages` AUTO_INCREMENT=0');
    $result = $CONVERTDB->Query('SELECT * FROM `ags_Pages` ORDER BY `Build_Order`');
    while( $page = $CONVERTDB->NextRow($result) )
    {
        $template = file_get_contents("{$_REQUEST['directory']}/data/html/{$page['Page_ID']}");
        $template = ConvertTemplate($template);
        $compiled = '';
        
        $DB->Update('INSERT INTO `tx_pages` VALUES (?,?,?,?,?,?,?,?,?)',
                    array(null,
                          "{$vars['DOCUMENT_ROOT']}/{$page['Filename']}",
                          "http://{$docroot_url['host']}/{$page['Filename']}",
                          $page['Category'] == 'Mixed' ? null : $category_ids[$page['Category']],
                          $build_order++,
                          0,
                          null,
                          $template,
                          $compiled));
    }
    $CONVERTDB->Free($result);
    
    FileAppend("{$GLOBALS['BASE_DIR']}/data/convert.log", "\nData conversion complete!");
    echo "\nData conversion complete!\n";
    
    if( !$from_shell )
        echo "</pre>";
}

function ConvertTemplate($template)
{
    global $replace_global;

    $template = preg_replace_callback('~<%([A-Z]+)$(.*?)%>~msi', 'ProcessDirectives', $template);
    $template = str_replace(array_keys($replace_global), array_values($replace_global), $template);
    $template = str_replace('submit.cgi', 'submit.php', $template);
    $template = str_replace('go.php', 'click.php', $template);
    
    return $template;
}

function ProcessDirectives($matches)
{
    global $replace_categories_order, $replace_galleries_order, $replace_categories_html, $replace_galleries_html;
    
    $directive = $matches[1];
    $options = $matches[2];
    $sub_inserts = ExtractSubs('INSERT', $options);
    $sub_galleries = ExtractSubs('GALLERIES', $options);
    $options = ExtractOptions($options);
    $output = '';
    
    switch($matches[1])
    {
        case 'DEFINE':
            if( isset($options['GLOBALDUPES']) )
                $output .= "{define name=globaldupes value=".strtolower($options['GLOBALDUPES'])."}\n";
                
            if( isset($options['PAGEDUPES']) )
                $output .= "{define name=pagedupes value=".strtolower($options['PAGEDUPES'])."}\n";
            
            break;
        
        case 'GALLERIES':
            if( $options['WHERE'] || $options['REWHERE'] )
            {
                $output = "{* SORRY, GALLERIES DIRECTIVES THAT USE THE WHERE OPTION CANNOT BE AUTOMATICALLY CONVERTED\n" .
                          $matches[0] .
                          "\n*}";
            }
            else
            {
                $main_opts = ConvertGalleriesOptions($options, null);
                
                $html = isset($GLOBALS['HTML'][$options['HTML']]) ? $GLOBALS['HTML'][$options['HTML']] : $options['HTML'];
                $html = str_replace(array_keys($replace_galleries_html), array_values($replace_galleries_html), $html);
                
                $output = "{galleries\nvar=\$galleries\n" . join("\n", $main_opts) . "}\n";
                
                if( count($sub_galleries) )
                {
                    foreach( array('HASTHUMB', 'TYPE', 'FORMAT', 'CATEGORY', 'HTML', 'GETNEW', 'DATEFORMAT') as $opt )
                    {
                        if( !isset($sub_galleries[0][$opt]) )
                        {
                            $sub_galleries[0][$opt] = $options[$opt];
                        }
                    }
                    
                    $sub_opts = ConvertGalleriesOptions($sub_galleries[0], $options['AMOUNT']);
                    
                    $output .= "{galleries\nvar=\$sub_galleries\n" . join("\n", $sub_opts) . "}\n" .
                               "{intermix var=\$galleries from=\$galleries,\$sub_galleries location=".$sub_galleries[0]['LOCATION']."}\n";
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
            }
            
            break;
            
        case 'CATEGORIES':
            if( $options['WHERE'] || $options['REWHERE'] )
            {
                $output = "{* SORRY, CATEGORIES DIRECTIVES THAT USE THE WHERE OPTION CANNOT BE AUTOMATICALLY CONVERTED\n" .
                          $matches[0] .
                          "\n*}";
            }
            else
            {
                $newopts = array();
                
                switch($options['AMOUNT'])
                {
                    case 'All':
                        $newopts[] = 'amount=all';
                        break;
                    
                    default:
                        $newopts[] = 'amount=' . $options['AMOUNT'];
                        break;
                }
                
                if( isset($options['ORDER']) )
                {
                    $newopts[] = 'order=' . str_replace(array_keys($replace_categories_order), array_values($replace_categories_order), $options['ORDER']);
                }
                
                if( isset($options['EXCLUDE']) )
                {
                    $newopts[] = 'exclude=' . $options['EXCLUDE'];
                }
                
                
                $html = isset($GLOBALS['HTML'][$options['HTML']]) ? $GLOBALS['HTML'][$options['HTML']] : $options['HTML'];
                $html = str_replace(array_keys($replace_categories_html), array_values($replace_categories_html), $html);
                
                $output = "{categories\nvar=\$categories\n" . join("\n", $newopts) . "}\n\n" .
                          "{foreach var=\$category from=\$categories counter=\$counter}\n";
                
                if( isset($options['LETTERHTML']) )
                {
                    $output .= "{if \$_temp_cat_starts != substr(\$category.name, 0, 1)}\n" .
                               str_replace('##First_Letter##', '{$category.name|substr::0::1}', $options['LETTERHTML']) . "\n" .
                               "{assign var=\$_temp_cat_starts value=substr(\$category.name, 0, 1)}\n" .
                               "{/if}\n";
                }
                
                $output .= $html . "\n";                          
                          
                foreach( $sub_inserts as $insert )
                {
                    $output .= "{insert counter=\$counter location=".$insert['LOCATION']."}\n" .
                               $insert['HTML'] . "\n" .
                               "{/insert}\n";
                }
                
                $output .= "{/foreach}";
            }
            break;
                        
        case 'INCLUDE':
            $output = "{php}\ninclude('".$options['FILE']."');\n{/php}";
            break;
            
        case 'PERL':
            $output = "{* SORRY, PERL CODE IS NOT SUPPORTED IN TGPX TEMPLATES\n" .
                      $matches[0] .
                      "\n*}";
            break;
            
        case 'TEMPLATE':
            $GLOBALS['HTML'][$options['NAME']] = $options['HTML'];
            break;
            
        case 'RANDOM':
            $output = "{* SORRY, RANDOM DIRECTIVES CANNOT BE CONVERTED\n" .
                      $matches[0] .
                      "\n*}";
            break;
    }
    
    return $output;
}



function ConvertGalleriesOptions($options, $parent_amount)
{
    global $replace_galleries_order;
    
    $newopts = array();
                
    switch($options['HASTHUMB'])
    {
        case '0':
            $newopts[] = 'preview=false';
            break;
        
        case '1':
            $newopts[] = 'preview=true';
            break;
        
        default:
            $newopts[] = 'preview=any';
            break;
    }
    
    switch($options['TYPE'])
    {
        case 'Submitted':
        case 'Permanent':
            $newopts[] = 'type=' . strtolower($options['TYPE']);
            break;
            
        default:
            $newopts[] = 'type=any';
            break;
    }
    
    switch($options['FORMAT'])
    {
        case 'Pictures':                    
        case 'Movies':
            $newopts[] = 'format=' . strtolower($options['FORMAT']);
            break;               
    }
    
    switch($options['GETNEW'])
    {
        case 'True':                    
        case 'False':
            $newopts[] = 'getnew=' . strtolower($options['GETNEW']);
            break;    
    }
    
    switch($options['ALLOWUSED'])
    {
        case 'True':                    
        case 'False':
            $newopts[] = 'allowused=' . strtolower($options['ALLOWUSED']);
            break;   
    }
    
    switch($options['DESCREQ'])
    {
        case 'True':
        case 'False':
            $newopts[] = 'description=' . strtolower($options['DESCREQ']);
            break;   
    }
    
    if( isset($options['WEIGHT']) )
    {
        $newopts[] = 'weight="' . $options['WEIGHT'] . '"';
    }
    
    if( isset($options['GLOBALDUPES']) )
    {
        $newopts[] = 'globaldupes=' . strtolower($options['GLOBALDUPES']);
    }
    
    if( isset($options['PAGEDUPES']) )
    {
        $newopts[] = 'pagedupes=' . strtolower($options['PAGEDUPES']);
    }
    
    if( isset($options['MINAGE']) )
    {
        $newopts[] = 'minage=' . $options['MINAGE'];
    }
    
    if( isset($options['MAXAGE']) )
    {
        $newopts[] = 'maxage=' . $options['MAXAGE'];
    }
    
    if( isset($options['AGE']) )
    {
        $newopts[] = 'age=' . $options['AGE'];
    }
    
    switch($options['CATEGORY'])
    {
        case 'Mixed':
        case '':
            $catoption = 'category=MIXED';
            
            if( isset($options['EXCLUDE']) )
            {
                $exclusions = explode(',', $options['EXCLUDE']);
                
                foreach( $exclusions as $index => $value )
                {
                    $exclusions[$index] = "-" . trim($value);
                }
                
                $catoption .= ',' . join(',', $exclusions); 
            }
            
            $newopts[] = $catoption;
            break;
            
        default:
            $newopts[] = 'category=' . $options['CATEGORY'];
    }
    
    if( isset($options['SPONSOR']) )
    {
        $newopts[] = 'sponsor=' . $options['SPONSOR'];
    }
    
    
    if( isset($options['KEYWORDS']) )
    {                    
        $newopts[] = 'keywords=' . str_replace(',', ' ', $options['KEYWORDS']);
    }
    
    
    if( isset($options['ORDER']) )
    {
        $newopts[] = 'order=' . str_replace(array_keys($replace_galleries_order), array_values($replace_galleries_order), $options['ORDER']);
    }
    
    if( isset($options['REORDER']) )
    {
        $newopts[] = 'reorder=' . str_replace(array_keys($replace_galleries_order), array_values($replace_galleries_order), $options['REORDER']);
    }
    
    if( isset($options['HEIGHT']) && isset($options['WIDTH']) )
    {
        $options['HEIGHT'] = trim($options['HEIGHT']);
        $options['WIDTH'] = trim($options['WIDTH']);
        
        if( strpos($options['HEIGHT'], '=') === 0 && strpos($options['WIDTH'], '=') === 0 )
        {
            $newopts[] = 'previewsize=' . substr($options['WIDTH'], 1) . 'x' . substr($options['HEIGHT'], 1);
        }
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
  <title>Convert AutoGallery SQL Data</title>
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
}


echo <<<OUT
<center>
<form method="POST" action="ags-convert.php" style="margin-top: 20px;" onsubmit="return confirm('Are you sure you want to convert this data to TGPX format?')">
<div style="margin-bottom: 5px; font-weight: bold;">Enter the full directory path to the AutoGallery SQL installation:</div>
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
