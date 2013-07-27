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
                                '##Description##' => '{$gallery.description|htmlspecialchars}',
                                '##Trimmed_Description##' => '{$gallery.description|htmlspecialchars}',
                                '##Thumbnails##' => '{$gallery.thumbnails|htmlspecialchars}',
                                '##Category##' => '{$gallery.category|htmlspecialchars}',
                                '##Sponsor##' => '{$gallery.sponsor|htmlspecialchars}',
                                '##Thumbnail_URL##' => '{$gallery.preview_url|htmlspecialchars}',
                                '##Thumb_Width##' => '{$gallery.preview_width|htmlspecialchars}',
                                '##Thumb_Height##' => '{$gallery.preview_height|htmlspecialchars}',
                                '##Weight##' => '{$gallery.weight|tnumber_format}',                                
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
                
                                 
$replace_global = array('##Thumbnails##' => '{$page_galleries}',
                        '##Galleries##' => '{$page_thumbnails}',                        
                        '##Category##' => '{$page_category.name|htmlspecialchars}',
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
    
    if( !is_file("{$_REQUEST['directory']}/tgpr.pl") )
    {
        $errors[] = "The tgpr.pl file could not be found in the " . htmlspecialchars($_REQUEST['directory']) . " directory";
        return DisplayMain($errors);
    }
    
    if( !is_readable("{$_REQUEST['directory']}/tgpr.pl") )
    {
        $errors[] = "The tgpr.pl file in the " . htmlspecialchars($_REQUEST['directory']) . " directory could not be opened for reading";
        return DisplayMain($errors);
    }
    
    
    // Check version
    $version_file_contents = file_get_contents("{$_REQUEST['directory']}/tgpr.pl");    
    if( preg_match('~\$VERSION\s+=\s+\'(.*?)\'~', $version_file_contents, $matches) )
    {
        list($a, $b, $c) = explode('.', $matches[1]);
        
        if( $b < 2 || strpos($c, '-SS') === FALSE )
        {
            $errors[] = "Your TGP Rotator installation is outdated; please upgrade to the very latest snapshot release (1.2.1-SS)";
            return DisplayMain($errors);
        }
    }
    else
    {
        $errors[] = "Unable to extract version information from tgpr.pl; your version of TGP Rotator is likely too old";
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
    
    if( $C['preview_dir'] == $vars['THUMB_DIR'] )
    {
        $errors[] = "The TGPX Thumbnail URL cannot be the same as the TGP Rotator Thumbnail URL";
        return DisplayMain($errors);
    }
    
    
    
    $CONVERTDB = new DB($vars['HOSTNAME'], $vars['USERNAME'], $vars['PASSWORD'], $vars['DATABASE']);
    $CONVERTDB->Connect();    
    $CONVERTDB->Update('SET wait_timeout=86400');
    
    $columns = $CONVERTDB->GetColumns('tr_Galleries');
    if( !in_array('Thumbnail_URL', $columns) )
    {
        $errors[] = "Your TGP Rotator installation is outdated; please upgrade to the latest snapshot release";
        return DisplayMain($errors);
    }
    
    if( !$from_shell )
        echo "<pre>";
       
    
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
    // Dump annotations
    FileAppend("{$GLOBALS['BASE_DIR']}/data/convert.log", "Converting annotation settings...\n");
    echo "Converting annotation settings...\n"; flush();
    $annotations = array();
    $DB->Update('DELETE FROM `tx_annotations`');
    $result = $CONVERTDB->Query('SELECT * FROM `tr_Annotations`');
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
    $result = $CONVERTDB->Query('SELECT * FROM `tr_Categories`');
    while( $category = $CONVERTDB->NextRow($result) )
    {
        $tag = CreateCategoryTag($category['Name']);
        $categories[$category['Name']] = $tag;
        
        $DB->Update('INSERT INTO `tx_categories` VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                    array(null,
                          $category['Name'],
                          $tag,
                          empty($category['Pictures']) ? 0 : 1,
                          $category['Pictures'],
                          10,
                          30,
                          12288,
                          "{$vars['THUMB_WIDTH']}x{$vars['THUMB_HEIGHT']}",
                          1,
                          $annotations[$category['Ann_Pictures']],
                          empty($category['Movies']) ? 0 : 1,
                          $category['Movies'],
                          5,
                          30,
                          102400,
                          "{$vars['THUMB_WIDTH']}x{$vars['THUMB_HEIGHT']}",
                          1,
                          $annotations[$category['Ann_Movies']],
                          -1,
                          0,
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
    $result = $CONVERTDB->Query('SELECT DISTINCT `Sponsor` FROM `tr_Galleries` WHERE `Sponsor`!=?', array(''));
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
    $result = $CONVERTDB->Query('SELECT * FROM `tr_Galleries` ORDER BY `Gallery_ID`');
    $preview_sizes = array();
    while( $gallery = $CONVERTDB->NextRow($result) )
    {
        $DB->Update("INSERT INTO `tx_galleries` VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
                    array(null,
                          $gallery['Gallery_URL'],
                          $gallery['Description'],
                          $gallery['Keywords'],
                          $gallery['Thumbnails'],
                          $C['from_email'],
                          null,
                          $gallery['Weight'],
                          $gallery['Clicks'],
                          $_SERVER['REMOTE_ADDR'],
                          null,
                          !empty($gallery['Sponsor']) ? $sponsors[$gallery['Sponsor']] : null,
                          'permanent',
                          strtolower($gallery['Type']),
                          $gallery['Status'] == 'Pending' ? 'approved' : strtolower($gallery['Status']),
                          $gallery['Status'] == 'Disabled' ? 'approved' : null,
                          date(DF_DATETIME, TimeWithTz($gallery['Added'])),
                          date(DF_DATETIME, TimeWithTz($gallery['Added'])),
                          date(DF_DATETIME, TimeWithTz($gallery['Added'])),
                          empty($gallery['Scheduled_Date']) ? null : "{$gallery['Scheduled_Date']} 00:00:00",
                          empty($gallery['Display_Date']) ? null : "{$gallery['Display_Date']} 12:00:00",
                          null,
                          null,
                          'TGPR Convert',
                          '',
                          null,
                          0,
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
    $DB->Update('DELETE FROM `tx_pages`');
    $DB->Update('ALTER TABLE `tx_pages` AUTO_INCREMENT=0');
    $result = $CONVERTDB->Query('SELECT * FROM `tr_Pages` ORDER BY `Build_Order`');
    while( $page = $CONVERTDB->NextRow($result) )
    {
        $template = file_get_contents("{$_REQUEST['directory']}/data/pages/{$page['Page_ID']}");
        $template = ConvertTemplate($template);
        $compiled = '';
        
        $page['Directory'] = preg_replace('~/$~', '', $page['Directory']);
        
        $DB->Update('INSERT INTO `tx_pages` VALUES (?,?,?,?,?,?,?,?,?)',
                    array(null,
                          "{$page['Directory']}/{$page['Filename']}",
                          $page['Page_URL'],
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
    }
    
    return $output;
}



function ConvertGalleriesOptions($options, $parent_amount)
{
    global $replace_galleries_order;
    
    $newopts = array('type=permanent');

    switch($options['TYPE'])
    {
        case 'Text':
            $newopts[] = 'preview=any';
            break;
            
        case 'Thumb':
            $newopts[] = 'preview=true';
            break;
    }
    
    switch($options['FORMAT'])
    {
        case 'Pictures':                    
        case 'Movies':
            $newopts[] = 'format=' . strtolower($options['FORMAT']);
            break;               
    }
    
    switch($options['STATUS'])
    {
        case 'Pending':
            $newopts[] = 'getnew=true';
            $newopts[] = 'allowused=true';
            break;  
                         
        case 'Used':
            $newopts[] = 'getnew=false';
            break;    
    }
    
    switch($options['DESCRIPTION'])
    {
        case 'True':
        case 'False':
            $newopts[] = 'description=' . strtolower($options['DESCRIPTION']);
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
    
    $newopts[] = 'amount=' . $options['AMOUNT'];
    
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
  <title>Convert TGP Rotator Data</title>
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
<form method="POST" action="tgpr-convert.php" style="margin-top: 20px;" onsubmit="return confirm('Are you sure you want to convert this data to TGPX format?')">
<div style="margin-bottom: 5px; font-weight: bold;">Enter the full directory path to the TGP Rotator installation:</div>
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
