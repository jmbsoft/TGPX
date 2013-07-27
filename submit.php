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


$functions = array('submit' => 'txShGallerySubmit',
                   'submitgallery' => 'txAddGallery',
                   'crop' => 'txCrop',
                   'confirm' => 'txConfirm');

require_once('includes/common.php');
require_once("{$GLOBALS['BASE_DIR']}/includes/template.class.php");
require_once("{$GLOBALS['BASE_DIR']}/includes/mysql.class.php");
require_once("{$GLOBALS['BASE_DIR']}/includes/imager.class.php");
require_once("{$GLOBALS['BASE_DIR']}/includes/validator.class.php");

SetupRequest();

$DB = new DB($C['db_hostname'], $C['db_username'], $C['db_password'], $C['db_name']);
$DB->Connect();

$domain = $DB->Row('SELECT * FROM `tx_domains` WHERE `domain`=?', array(preg_replace('~^www\.~i', '', strtolower($_SERVER['HTTP_HOST']))));

if( $domain['categories'] )
{
    $domain['categories'] = unserialize($domain['categories']);
}


$t = new Template();
$t->assign_by_ref('config', $C);

if( $C['submit_status'] == 'closed' )
{
    $t->display($domain['template_prefix'].'submit-closed.tpl');
    return;
}


if( isset($functions[$_REQUEST['r']]) && function_exists($functions[$_REQUEST['r']]) )
{
    call_user_func($functions[$_REQUEST['r']]);
}
else
{
    txShGallerySubmit();
}

$DB->Disconnect();

function txShGallerySubmit($errors = null)
{
    global $C, $DB, $L, $t, $domain;

    $category_query = 'SELECT * FROM `tx_categories` WHERE `hidden`=0 ORDER BY `name`';
    $category_binds = array();

    if( $domain['categories'] )
    {
        $category_query = 'SELECT * FROM `tx_categories` WHERE `hidden`=0 AND `category_id` '. ($domain['as_exclude'] ? 'NOT IN' : 'IN') . ' ('. CreateBindList($domain['categories']) .') ORDER BY `name`';
        $category_binds = $domain['categories'];
    }


    $categories =& $DB->FetchAll($category_query, $category_binds);


    if( count($categories) < 1 )
    {
        $t->assign('error', 'There must be at least one category defined before you can submit galleries');
        $t->display($domain['template_prefix'].'error-nice.tpl');
        return;
    }

    $t->assign_by_ref('categories', $categories);

    $fields =& GetUserGalleryFields();

    // See if we are full
    if( $C['max_submissions'] != -1 )
    {
        $todays_submissions = $DB->Count('SELECT COUNT(*) FROM `tx_galleries` WHERE type=? AND (partner=? OR partner IS NULL) AND `date_added` BETWEEN ? AND ?',
                                         array('submitted',
                                               '',
                                               MYSQL_CURDATE . ' 00:00:00',
                                               MYSQL_CURDATE . ' 23:59:59'));

        if( $todays_submissions >= $C['max_submissions'] )
        {
            $t->display($domain['template_prefix'].'submit-full-global.tpl');
            return;
        }
    }

    $t->assign('errors', $errors);
    $t->assign_by_ref('user_fields', $fields);
    $t->assign_by_ref('gallery', $_REQUEST);

    $t->display($domain['template_prefix'].'submit-main.tpl');
}

function txShCrop()
{
    global $DB, $C, $L, $t, $domain;

    // Record the ending URL from the scan for referrer purposes
    $_REQUEST['gallery_url'] = $_REQUEST['scan']['end_url'];

    // Generate the thumb queue
    $thumb_queue = array();
    foreach( $_REQUEST['scan']['thumbs'] as $thumb )
    {
        $thumb_queue[] = "{preview: '{$thumb['preview']}', full: '{$thumb['full']}'}";
    }
    $t->assign('thumb_queue', join(',', $thumb_queue));

    // Set dimensions
    $size = array();
    list($size['width'], $size['height']) = explode('x', $_REQUEST['category_format']['preview_size']);
    $t->assign_by_ref('size', $size);

    $t->assign_by_ref('gallery', $_REQUEST);
    $t->display($domain['template_prefix'].'submit-crop.tpl');
}

function txCrop()
{
    global $DB, $C, $L, $t, $domain;

    $gallery = $DB->Row('SELECT * FROM `tx_galleries` WHERE `gallery_id`=?', array($_REQUEST['gallery_id']));

    if( $gallery )
    {
        if( $gallery['has_preview'] )
        {
            $t->assign('error', $L['PREVIEW_EXISTS']);
            $t->display($domain['template_prefix'].'error-nice.tpl');
            return;
        }

        // Defaults
        $gallery['status'] = 'pending';
        $gallery['date_approved'] = null;
        $gallery['administrator'] = null;
        $partner = null;

        // Get category and format information
        $categories =& CategoriesFromTags($gallery['categories']);
        $format = GetCategoryFormat($gallery['format'], $categories[0]);
        $annotation =& LoadAnnotation($format['annotation'], $categories[0]['name']);
        $imagefile = SafeFilename("{$GLOBALS['BASE_DIR']}/cache/{$_REQUEST['imagefile']}");

        $i = GetImager();
        $i->ResizeCropper($imagefile, $format['preview_size'], $_REQUEST, $annotation);

        $preview = AddPreview($gallery['gallery_id'], $format['preview_size'], $imagefile);
        $gallery['preview_url'] = $preview['url'];

        // Load gallery information to determine how to process the gallery
        $whitelisted = CheckWhitelist($gallery);
        if( $gallery['partner'] )
        {
            $partner = $DB->Row('SELECT * FROM `tx_partners` WHERE `username`=?', array($gallery['partner']));
        }
        $whitelisted = MergeWhitelistOptions($whitelisted, $partner);


        // Determine gallery status
        $autoapprove_general = empty($partner) && !$C['require_confirm'] && ($C['allow_autoapprove'] || $whitelisted['allow_autoapprove']);
        $autoapprove_partner = !empty($partner) && $partner['allow_noconfirm'] && $whitelisted['allow_autoapprove'];
        if( $autoapprove_general || $autoapprove_partner )
        {
            $gallery['status'] = 'approved';
            $gallery['date_approved'] = MYSQL_NOW;
            $gallery['administrator'] = 'AUTO';
        }

        // Setup gallery for confirmation
        else if( (empty($partner) && $C['require_confirm']) || (!empty($partner) && !$partner['allow_noconfirm'] && $C['require_confirm']) )
        {
            $gallery['status'] = 'unconfirmed';
            $gallery['confirm_id'] = md5(uniqid(rand(), true));
        }

        // Update gallery data
        $DB->Update('UPDATE `tx_galleries` SET `status`=?,`date_approved`=?,`administrator`=?,`has_preview`=? WHERE `gallery_id`=?',
                    array($gallery['status'], $gallery['date_approved'], $gallery['administrator'], 1, $gallery['gallery_id']));

        // Get category
        $categories =& CategoriesFromTags($gallery['categories']);
        $gallery['category'] = $categories[0]['name'];

        // Assign gallery data to the template
        $fields =& GetUserGalleryFields($gallery);
        $t->assign_by_ref('gallery', $gallery);
        $t->assign_by_ref('user_fields', $fields);

        // Handle confirmation
        if( $gallery['status'] == 'unconfirmed' )
        {
            SendMail($gallery['email'], $domain['template_prefix'].'email-gallery-confirm.tpl', $t);

            $DB->Update('INSERT INTO `tx_gallery_confirms` VALUES (?,?,?)',
                        array($gallery['gallery_id'],
                              $gallery['confirm_id'],
                              MYSQL_NOW));
        }

        // Update number of submitted galleries if partner account
        if( $partner )
        {
            $DB->Update('UPDATE `tx_partners` SET `submitted`=`submitted`+1,`date_last_submit`=? WHERE `username`=?', array(MYSQL_NOW, $partner['username']));
        }

        // Update the date of last submission for this category
        $DB->Update('UPDATE `tx_categories` SET `date_last_submit`=? WHERE `category_id`=?', array(MYSQL_NOW, $categories[0]['category_id']));

        $t->display($domain['template_prefix'].'submit-complete.tpl');
    }
    else
    {
        $t->assign('error', $L['BAD_GALLERY_ID']);
        $t->display($domain['template_prefix'].'error-nice.tpl');
    }
}

function txConfirm()
{
    global $DB, $L, $C, $t, $domain;

    // Delete old confirmations
    $DB->Update('DELETE FROM `tx_gallery_confirms` WHERE `date_sent` < ?', array(gmdate(DF_DATETIME, TIME_NOW - 86400)));

    $confirmation = $DB->Row('SELECT * FROM `tx_gallery_confirms` WHERE `confirm_id`=?', array($_REQUEST['id']));

    if( $confirmation )
    {
        $gallery = $DB->Row('SELECT * FROM `tx_galleries` WHERE `gallery_id`=?', array($confirmation['gallery_id']));

        if( $gallery )
        {
            // Defaults
            $gallery['status'] = 'pending';
            $gallery['date_approved'] = null;
            $gallery['administrator'] = null;

            // Load gallery information to determine how to process the gallery
            $whitelisted = CheckWhitelist($gallery);
            if( $gallery['partner'] )
            {
                $partner = $DB->Row('SELECT * FROM `tx_partners` WHERE `username`=?', array($gallery['partner']));
            }
            $whitelisted = MergeWhitelistOptions($whitelisted, $partner);


            // Determine if gallery should be auto-approved
            $autoapprove_general = empty($partner) && ($C['allow_autoapprove'] || $whitelisted['allow_autoapprove']);
            $autoapprove_partner = !empty($partner) && $whitelisted['allow_autoapprove'];
            if( $autoapprove_general || $autoapprove_partner )
            {
                $gallery['status'] = 'approved';
                $gallery['date_approved'] = MYSQL_NOW;
                $gallery['administrator'] = 'AUTO';
            }

            // Update gallery data
            $DB->Update('UPDATE `tx_galleries` SET `status`=?,`date_approved`=?,`administrator`=? WHERE `gallery_id`=?',
                        array($gallery['status'], $gallery['date_approved'], $gallery['administrator'], $gallery['gallery_id']));

            // Remove the confirmation code
            $DB->Update('DELETE FROM `tx_gallery_confirms` WHERE `confirm_id`=?', array($confirmation['confirm_id']));

            // Get category
            $categories = CategoriesFromTags($gallery['categories']);
            $gallery['category'] = $categories[0]['name'];

            // Get preview URL (if any)
            $gallery['preview_url'] = $DB->Count('SELECT `preview_url` FROM `tx_gallery_previews` WHERE `gallery_id`=?', array($gallery['gallery_id']));

            $fields =& GetUserGalleryFields($gallery);
            $t->assign('confirmed', true);
            $t->assign_by_ref('gallery', $gallery);
            $t->assign_by_ref('user_fields', $fields);

            $t->display($domain['template_prefix'].'submit-complete.tpl');
        }
        else
        {
            $t->assign('error', $L['BAD_GALLERY_ID']);
            $t->display($domain['template_prefix'].'error-nice.tpl');
        }
    }
    else
    {
        $t->assign('error', $L['INVALID_CONFIRMATION']);
        $t->display($domain['template_prefix'].'error-nice.tpl');
    }
}

function txAddGallery()
{
    global $DB, $C, $L, $t, $domain;

    // Set some default values
    $defaults = array('weight' => $C['gallery_weight'],
                      'clicks' => 0,
                      'submit_ip' => $_SERVER['REMOTE_ADDR'],
                      'sponsor_id' => null,
                      'type' => 'submitted',
                      'format' => $C['allow_format'] ? $_REQUEST['format'] : FMT_PICTURES,
                      'status' => 'pending',
                      'previous_status' => null,
                      'date_scanned' => MYSQL_NOW,
                      'date_added' => MYSQL_NOW,
                      'date_approved' => null,
                      'date_scheduled' => null,
                      'date_displayed' => null,
                      'date_deletion' => null,
                      'allow_scan' => 1,
                      'allow_preview' => 1,
                      'has_preview' => 0,
                      'times_selected' => 0,
                      'used_counter' => 0,
                      'build_counter' => 0,
                      'tags' => $domain['tags']);

    $_REQUEST = array_merge($_REQUEST, $defaults);

    $v = new Validator();

    // Verify and grab partner account
    $partner = null;
    if( !IsEmptyString($_REQUEST['username']) || !IsEmptyString($_REQUEST['password']) )
    {
        $partner = $DB->Row('SELECT * FROM `tx_partners` WHERE `username`=? AND `password`=?', array($_REQUEST['username'], sha1($_REQUEST['password'])));

        if( !$partner )
        {
            $v->SetError($L['INVALID_LOGIN']);
        }
        else
        {
            // Setup the correct weight value for this account
            $_REQUEST['weight'] = $partner['weight'];
            $_REQUEST['partner'] = $partner['username'];
            $_REQUEST['email'] = $partner['email'];
            $_REQUEST['nickname'] = $partner['name'];

            if( !empty($partner['categories']) )
            {
                $partner['categories'] = unserialize($partner['categories']);
            }

            // Nickname not required for partner accounts
            if( $C['require_nickname'] )
            {
                $v->Register($_REQUEST['nickname'], V_EMPTY, $L['NO_PARTNER_NICKNAME']);
            }

            // Check if the partner account is active and valid to submit
            if( $partner['status'] == 'suspended' )
            {
                $v->SetError($L['ACCOUNT_SUSPENDED']);
            }
            else if( $partner['status'] != 'active' )
            {
                $v->SetError($L['ACCOUNT_PENDING']);
            }

            // Check active dates
            if( !IsEmptyString($partner['date_end']) && !IsEmptyString($partner['date_start']) )
            {
                $now = strtotime(MYSQL_NOW);
                $end = strtotime($partner['date_end']);
                $start = strtotime($partner['date_start']);

                if( $now < $start || $now > $end )
                {
                    $start_time = date("{$C['date_format']} {$C['time_format']}", $start);
                    $end_time = date("{$C['date_format']} {$C['time_format']}", $end);
                    $v->SetError(sprintf($L['ACCOUNT_EXPIRED'], $start_time, $end_time));
                }
            }

            if( $partner['domains'] )
            {
                $partner['domains'] = unserialize($partner['domains']);

                if( $domain )
                {
                    if( (!$partner['domains_as_exclude'] && !in_array($domain['domain_id'], $partner['domains'])) || ($partner['domains_as_exclude'] && in_array($domain['domain_id'], $partner['domains'])) )
                    {
                        $v->SetError($L['BAD_PARTNER_DOMAIN']);
                    }
                }
            }

        }
    }

    // See if only accepting submissions from partners
    if( !$partner && $C['submit_status'] == 'partner' )
    {
        $v->SetError($L['PARTNERS_ONLY']);
    }

    // Do partner account validation
    if( !$v->Validate() )
    {
        return $v->ValidationError('txShGallerySubmit', TRUE);
    }

    $v->Register($_REQUEST['email'], V_EMAIL, $L['INVALID_EMAIL']);
    $v->Register($_REQUEST['gallery_url'], V_URL, sprintf($L['INVALID_URL'], $L['GALLERY_URL']));

    if( $C['require_keywords'] )
    {
        $v->Register($_REQUEST['keywords'], V_EMPTY, sprintf($L['REQUIRED_FIELD'], $L['KEYWORDS']));
    }

    if( $C['require_nickname'] )
    {
        $v->Register($_REQUEST['nickname'], V_EMPTY, sprintf($L['REQUIRED_FIELD'], $L['NAME']));
    }

    if( $C['require_description'] )
    {
        $v->Register($_REQUEST['description'], V_EMPTY, sprintf($L['REQUIRED_FIELD'], $L['DESCRIPTION']));
    }

    // Check description length if required or provided
    if( $C['require_description'] || !IsEmptyString($_REQUEST['description']) )
    {
        $v->Register($_REQUEST['description'], V_LENGTH, sprintf($L['DESCRIPTION_LENGTH'], $C['min_desc_length'], $C['max_desc_length']), "{$C['min_desc_length']},{$C['max_desc_length']}");
    }

    // Format keywords and check number
    $_REQUEST['keywords'] = FormatSpaceSeparated($_REQUEST['keywords']);
    $keywords = explode(' ', $_REQUEST['keywords']);
    $v->Register(count($keywords), V_LESS_EQ, sprintf($L['MAXIMUM_KEYWORDS'], $C['max_keywords']), $C['max_keywords']);

    // Validation of user defined fields
    $fields =& GetUserGalleryFields();
    foreach($fields as $field)
    {
        if( $field['on_submit'] )
        {
            if( $field['required'] )
            {
                $v->Register($_REQUEST[$field['name']], V_EMPTY, sprintf($L['REQUIRED_FIELD'], $field['label']));
            }

            if( !IsEmptyString($_REQUEST[$field['name']]) && $field['validation'] )
            {
                $v->Register($_REQUEST[$field['name']], $field['validation'], $field['validation_message'], $field['validation_extras']);
            }
        }
    }

    // Check the global number of submissions
    if( !$partner && $C['max_submissions'] != -1 )
    {
        $todays_submissions = $DB->Count('SELECT COUNT(*) FROM `tx_galleries` WHERE type=? AND (partner=? OR partner IS NULL) AND `date_added` BETWEEN ? AND ?',
                                         array('submitted',
                                               '',
                                               MYSQL_CURDATE . ' 00:00:00',
                                               MYSQL_CURDATE . ' 23:59:59'));

        if( $todays_submissions >= $C['max_submissions'] )
        {
            $t->display($domain['template_prefix'].'submit-full-global.tpl');
            return;
        }
    }

    // Check the number of submitted galleries
    if( $partner )
    {
        if( $partner['per_day'] != -1 )
        {
            $amount = $DB->Count('SELECT COUNT(*) FROM `tx_galleries` WHERE `partner`=? AND `type`=? AND `date_added` BETWEEN ? AND ?', array($partner['username'], 'submitted', MYSQL_CURDATE . ' 00:00:00', MYSQL_CURDATE . ' 23:59:59'));

            if( $amount >= $partner['per_day'] )
            {
                $v->SetError($L['SUBMIT_LIMIT_REACHED']);
            }
        }
    }
    else
    {
        if( $C['submissions_per_person'] != -1 )
        {
            $amount = $DB->Count('SELECT COUNT(*) FROM `tx_galleries` WHERE (`submit_ip`=? OR `email`=? OR `gallery_url`=?) AND `type`=? AND `date_added` BETWEEN ? AND ?',
                                 array($_SERVER['REMOTE_ADDR'],
                                       $_REQUEST['email'],
                                       LevelUpUrl($_REQUEST['gallery_url']),
                                       'submitted',
                                       MYSQL_CURDATE . ' 00:00:00',
                                       MYSQL_CURDATE . ' 23:59:59'));

            if( $amount >= $C['submissions_per_person'] )
            {
                $v->SetError($L['SUBMIT_LIMIT_REACHED']);
            }
        }
    }

    // Check for valid category if allowing multiple categories to be selected
    $category = null;
    if( $C['allow_multiple_cats'] )
    {
        if( is_array($_REQUEST['category_id']) )
        {
            $_REQUEST['category_id'] = array_unique($_REQUEST['category_id']);

            if( count($_REQUEST['category_id']) > $C['max_categories'] )
            {
                $v->SetError(sprintf($L['EXCESSIVE_CATEGORIES'], $C['max_categories']));
            }
            else
            {
                $category_names = array();
                $category_tags = array();
                $_REQUEST['submitted_categories'] = join(',', $_REQUEST['category_id']);

                // Check that all categories are valid
                foreach( $_REQUEST['category_id'] as $category_id )
                {
                    $temp_category = $DB->Row('SELECT * FROM `tx_categories` WHERE `category_id`=? AND `hidden`=0', array($category_id));

                    if( !$temp_category )
                    {
                        $v->SetError($L['INVALID_CATEGORY']);
                    }
                    else
                    {
                        // Set primary category
                        if( $category == null )
                        {
                            $category = $temp_category;
                        }

                        // Check category submission limit
                        if( $temp_category['per_day'] != -1 )
                        {
                            $category_submissions = $DB->Count('SELECT COUNT(*) FROM `tx_galleries` WHERE type=? AND MATCH(`categories`) AGAINST(? IN BOOLEAN MODE) AND `date_added` BETWEEN ? AND ?',
                                                               array('submitted', $temp_category['tag'], MYSQL_CURDATE . ' 00:00:00', MYSQL_CURDATE . ' 23:59:59'));

                            if( $category_submissions >= $temp_category['per_day'] )
                            {
                                $v->SetError(sprintf($L['CATEGORY_FULL'], htmlspecialchars($temp_category['name'])));
                            }
                        }

                        // Check if partner is allowed to submit to this category
                        if( $partner['categories'] )
                        {
                            if( (!$partner['categories_as_exclude'] && !in_array($temp_category['category_id'], $partner['categories'])) || ($partner['categories_as_exclude'] && in_array($temp_category['category_id'], $partner['categories'])) )
                            {
                                $v->SetError(sprintf($L['BAD_PARTNER_CATEGORY'], $category['name']));
                            }
                        }

                        $category_names[] = $temp_category['name'];
                        $category_tags[] = $temp_category['tag'];
                    }
                }

                $_REQUEST['category'] = join(', ', $category_names);
                $category['tag'] = join(' ', $category_tags);
            }
        }
        else
        {
            $v->SetError($L['INVALID_CATEGORY']);
        }
    }

    // Check for valid category if NOT allowing multiple categories to be submitted
    else
    {
        if( is_array($_REQUEST['category_id']) )
        {
            $_REQUEST['category_id'] = $_REQUEST['category_id'][0];
        }

        $category = $DB->Row('SELECT * FROM `tx_categories` WHERE `category_id`=? AND `hidden`=0', array($_REQUEST['category_id']));
        if( !$category )
        {
            $v->SetError($L['INVALID_CATEGORY']);
        }
        else
        {
            // Check category submission limit
            if( $category['per_day'] != -1 )
            {
                $category_submissions = $DB->Count('SELECT COUNT(*) FROM `tx_galleries` WHERE type=? AND MATCH(`categories`) AGAINST(? IN BOOLEAN MODE) AND `date_added` BETWEEN ? AND ?',
                                                   array('submitted', $category['tag'], MYSQL_CURDATE . ' 00:00:00', MYSQL_CURDATE . ' 23:59:59'));

                if( $category_submissions >= $category['per_day'] )
                {
                    $v->SetError(sprintf($L['CATEGORY_FULL'], htmlspecialchars($category['name'])));
                }
            }

            // Check if partner is allowed to submit to this category
            if( $partner['categories'] )
            {
                if( (!$partner['categories_as_exclude'] && !in_array($_REQUEST['category_id'], $partner['categories'])) || ($partner['categories_as_exclude'] && in_array($_REQUEST['category_id'], $partner['categories'])) )
                {
                    $v->SetError(sprintf($L['BAD_PARTNER_CATEGORY'], $category['name']));
                }
            }

            $_REQUEST['category'] = $category['name'];
        }
    }

    // Verify captcha code
    if( (!$partner && $C['gallery_captcha']) || ($partner && $C['gallery_captcha_partner']) )
    {
        VerifyCaptcha($v);
    }

    // Check for duplicate gallery URL
    if( !$C['allow_duplicates'] && $DB->Count('SELECT COUNT(*) FROM `tx_galleries` WHERE `gallery_url`=?', array($_REQUEST['gallery_url'])) )
    {
        $v->SetError($L['DUPLICATE_URL']);
    }

    // Do preliminary validation before gallery scan
    if( !$v->Validate() )
    {
        return $v->ValidationError('txShGallerySubmit', TRUE);
    }

    // Check if whitelisted
    $whitelisted = MergeWhitelistOptions(CheckWhitelist($_REQUEST), $partner);

    // Scan gallery
    $scan =& ScanGallery($_REQUEST, $category, $whitelisted);
    $_REQUEST['scan'] = $scan;

    // Make sure the gallery URL is working
    if( !$scan['success'] )
    {
        $v->SetError(sprintf($L['BROKEN_URL'], $L['GALLERY_URL'], $scan['errstr']));
        return $v->ValidationError('txShGallerySubmit', TRUE);
    }

    // Check if gallery content is hosted on same server
    if( $C['require_content_on_server'] && !$scan['server_match'] )
    {
        $v->SetError($L['CONTENT_NOT_ON_SERVER']);
    }

    // Check for a reciprocal link
    if( $C['require_recip'] && !$whitelisted['allow_norecip'] && !$scan['has_recip'] )
    {
        $v->SetError($L['NO_RECIP_FOUND']);
    }

    // Give weight boost to galleries with a reciprocal link
    if( $scan['has_recip'] && $C['give_recip_boost'] )
    {
        $_REQUEST['weight']++;
    }

    // Check for 2257 code
    if( $C['require_2257'] && !$scan['has_2257'] )
    {
        $v->SetError($L['NO_2257_FOUND']);
    }

    // Check for existing gallery with the same hash
    if( !$C['allow_same_hash'] )
    {
        $amount = $DB->Count('SELECT COUNT(*) FROM `tx_galleries` WHERE `page_hash`=?', array($scan['page_hash']));
    }

    // Override the number of thumbnails
    if( !$C['allow_num_thumbs'] )
    {
        $_REQUEST['thumbnails'] = $scan['thumbnails'];
    }

    // Check blacklist
    $blacklisted = FALSE;
    if( !$whitelisted['allow_blacklist'] )
    {
        $_REQUEST['html'] = $scan['html'];
        $_REQUEST['headers'] = $scan['headers'];
        $blacklisted = CheckBlacklistGallery($_REQUEST);
        if( $blacklisted !== FALSE )
        {
            // Handle blacklist transparently
            if( $C['use_transparent_blacklist'] )
            {
                $_REQUEST['gallery_id'] = $DB->Count('SELECT MAX(gallery_id) FROM `tx_galleries`') + 1;

                $t->assign_by_ref('gallery', $_REQUEST);
                $t->display($domain['template_prefix'].'submit-complete.tpl');
                return;
            }
            else
            {
                $v->SetError(sprintf(($blacklisted[0]['reason'] ? $L['BLACKLISTED_REASON'] : $L['BLACKLISTED']), $blacklisted[0]['match'], $blacklisted[0]['reason']));
            }
        }
    }

    // Check number of links on the gallery
    if( $C['max_links'] != -1 && $scan['links'] > $C['max_links'] )
    {
        $v->SetError(sprintf($L['EXCESSIVE_LINKS'], $C['max_links']));
    }

    // Get information about what is allowed for this category and format
    if( $C['allow_format'] )
    {
        $scan['format'] = $_REQUEST['format'];
    }
    $format = GetCategoryFormat($scan['format'], $category);
    $_REQUEST['category_format'] = $format;

    // See if category allows this format
    if( !$format['allowed'] )
    {
        $v->SetError(sprintf($L['INVALID_FORMAT'], $format['format_lang']));
    }

    // Check number of thumbnails
    if( $_REQUEST['thumbnails'] < $format['minimum'] || $_REQUEST['thumbnails'] > $format['maximum'] )
    {
        $v->SetError(sprintf($L['BAD_THUMB_COUNT'], $format['minimum'], $format['maximum']));
    }

    // Clear keywords if not allowed
    if( !$C['allow_keywords'] )
    {
        $_REQUEST['keywords'] = null;
    }

    // Clear preview thumbnail if only allowing partners to submit
    // OR
    // if this category and format does not allow preview thumbs
    if( ($C['allow_preview_partner'] && !$partner) || !$format['preview_allowed'] )
    {
        $_REQUEST['preview'] = null;
    }

    // Handle the preview thumbnail if it was uploaded or to be automatically selected
    $preview = HandlePreviewThumb($v, $format, LoadAnnotation($format['annotation'], $category['name']));

    // Check size of gallery content
    if( $C['check_content_size'] )
    {
        foreach( $scan['thumbs'] as $thumb )
        {
            $head = new Http();

            if( $head->Head($thumb['content'], FALSE, $scan['end_url']) )
            {
                if( !empty($head->response_headers['content-length']) &&  $head->response_headers['content-length'] < $format['file_size'] )
                {
                    $v->SetError(sprintf($L['SMALL_CONTENT'], $format['file_size']/1024));
                    break;
                }
            }
        }
    }

    // Check download speed
    if( $C['check_download_speed'] && $scan['speed_download'] < $C['min_download_speed'] )
    {
        $v->SetError(sprintf($L['SLOW_DOWNLOAD'], $scan['speed_download'], $C['min_download_speed']));
    }


    // Do final validation after gallery scan
    if( !$v->Validate() )
    {
        return $v->ValidationError('txShGallerySubmit', TRUE);
    }


    // Determine gallery status
    $autoapprove_general = empty($partner) && !$C['require_confirm'] && ($C['allow_autoapprove'] || $whitelisted['allow_autoapprove']);
    $autoapprove_partner = !empty($partner) && ($partner['allow_noconfirm'] || !$C['require_confirm']) && $whitelisted['allow_autoapprove'];
    if( $_REQUEST['preview'] == 'crop' )
    {
        $_REQUEST['status'] = 'submitting';
    }

    // Setup for automatic approval
    else if( $autoapprove_general || $autoapprove_partner )
    {
        $_REQUEST['status'] = 'approved';
        $_REQUEST['date_approved'] = MYSQL_NOW;
        $_REQUEST['administrator'] = 'AUTO';
    }

    // Setup gallery for confirmation
    else if( (empty($partner) && $C['require_confirm']) || (!empty($partner) && !$partner['allow_noconfirm'] && $C['require_confirm']) )
    {
        $_REQUEST['status'] = 'unconfirmed';
        $_REQUEST['confirm_id'] = md5(uniqid(rand(), true));
    }

    // Add gallery data to the database
    $DB->Update('INSERT INTO `tx_galleries` VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                array(null,
                      $_REQUEST['gallery_url'],
                      $_REQUEST['description'],
                      $_REQUEST['keywords'],
                      $_REQUEST['thumbnails'],
                      $_REQUEST['email'],
                      $_REQUEST['nickname'],
                      $_REQUEST['weight'],
                      $_REQUEST['clicks'],
                      $_REQUEST['submit_ip'],
                      $_REQUEST['gallery_ip'],
                      $_REQUEST['sponsor_id'],
                      $_REQUEST['type'],
                      $scan['format'],
                      $_REQUEST['status'],
                      $_REQUEST['previous_status'],
                      $_REQUEST['date_scanned'],
                      $_REQUEST['date_added'],
                      $_REQUEST['date_approved'],
                      $_REQUEST['date_scheduled'],
                      $_REQUEST['date_displayed'],
                      $_REQUEST['date_deletion'],
                      $_REQUEST['partner'],
                      $_REQUEST['administrator'],
                      $_REQUEST['admin_comments'],
                      $scan['page_hash'],
                      $scan['has_recip'],
                      $_REQUEST['has_preview'],
                      $_REQUEST['allow_scan'],
                      $_REQUEST['allow_preview'],
                      $_REQUEST['times_selected'],
                      $_REQUEST['used_counter'],
                      $_REQUEST['build_counter'],
                      $_REQUEST['tags'],
                      MIXED_CATEGORY . " " . $category['tag']));

    $_REQUEST['gallery_id'] = $DB->InsertID();

    // Insert user defined database fields
    $query_data = CreateUserInsert('tx_gallery_fields', $_REQUEST);
    $DB->Update('INSERT INTO `tx_gallery_fields` VALUES ('.$query_data['bind_list'].')', $query_data['binds']);


    // If partner account has icons, assign those to this gallery
    if( $partner )
    {
        $icons =& $DB->FetchAll('SELECT * FROM `tx_partner_icons` WHERE `username`=?', array($partner['username']));

        foreach( $icons as $icon )
        {
            $DB->Update('INSERT INTO `tx_gallery_icons` VALUES (?,?)', array($_REQUEST['gallery_id'], $icon['icon_id']));
        }
    }

    // Log e-mail address
    if( $C['log_emails'] )
    {
        $DB->Update('REPLACE INTO `tx_email_log` VALUES (?)', array($_REQUEST['email']));
    }

    // Show thumbnail cropping interface
    if( $_REQUEST['preview'] == 'crop' && $_REQUEST['thumbnails'] > 0 )
    {
        txShCrop();
    }

    // Display gallery submission complete confirmation page
    else
    {
        // Add preview thumbnail to database and rename
        $preview = AddPreview($_REQUEST['gallery_id'], $format['preview_size'], $preview);
        $_REQUEST['preview_url'] = $preview['url'];

        // Assign gallery data to the template
        $t->assign_by_ref('gallery', $_REQUEST);
        $t->assign_by_ref('user_fields', $fields);

        // Handle confirmation
        if( $_REQUEST['status'] == 'unconfirmed' )
        {
            SendMail($_REQUEST['email'], $domain['template_prefix'].'email-gallery-confirm.tpl', $t);

            $DB->Update('INSERT INTO `tx_gallery_confirms` VALUES (?,?,?)',
                        array($_REQUEST['gallery_id'],
                              $_REQUEST['confirm_id'],
                              MYSQL_NOW));
        }

        // Update number of submitted galleries if partner account
        if( $partner )
        {
            $DB->Update('UPDATE `tx_partners` SET `submitted`=`submitted`+1,`date_last_submit`=? WHERE `username`=?', array(MYSQL_NOW, $partner['username']));
        }

        // Update the date of last submission for this category
        $DB->Update('UPDATE `tx_categories` SET `date_last_submit`=? WHERE `category_id`=?', array(MYSQL_NOW, $category['category_id']));

        $t->display($domain['template_prefix'].'submit-complete.tpl');
    }
}

function HandlePreviewThumb(&$v, &$format, &$annotation)
{
    global $L, $C, $domain;

    list($width, $height) = explode('x', $format['preview_size']);

    $imagefile = "{$GLOBALS['BASE_DIR']}/cache/" . md5(uniqid(rand(), true)) . ".jpg";
    $i = GetImager();

    switch($_REQUEST['preview'])
    {
        // Automatically crop and resize
        case 'automatic':
        {
            $referrer_url = $_REQUEST['scan']['end_url'];
            $preview_url = $_REQUEST['scan']['preview'];

            if( !IsEmptyString($preview_url) )
            {
                $http = new Http();
                if( $http->Get($preview_url, TRUE, $referrer_url) )
                {
                    FileWrite($imagefile, $http->body);
                    $i->ResizeAuto($imagefile, $format['preview_size'], $annotation, $C['landscape_bias'], $C['portrait_bias']);
                }
                else
                {
                    $v->SetError(sprintf($L['PREVIEW_DOWNLOAD_FAILED'], $http->errstr));
                }
            }
            else
            {
                $v->SetError($L['NO_THUMBS_FOR_PREVIEW']);
            }
        }
        break;

        // Handle uploaded image
        case 'upload':
        {
            if( is_uploaded_file($_FILES['upload']['tmp_name']) )
            {
                move_uploaded_file($_FILES['upload']['tmp_name'], $imagefile);
                @chmod($imagefile, 0666);
                $image = @getimagesize($imagefile);

                if( $image !== FALSE && $image[2] == IMAGETYPE_JPEG )
                {
                    // Image is properly sized
                    if( $image[0] == $width && $image[1] == $height )
                    {
                        if( $C['have_imager'] )
                        {
                            $i->Annotate($imagefile, $annotation);
                        }
                    }
                    else
                    {
                        if( $C['have_imager'] && $C['handle_bad_size'] == 'crop' )
                        {
                            $i->ResizeAuto($imagefile, $format['preview_size'], $annotation, $C['landscape_bias'], $C['portrait_bias']);
                        }
                        else
                        {
                            @unlink($imagefile);
                            $v->SetError(sprintf($L['INVALID_IMAGE_SIZE'], $width, $height));
                        }
                    }
                }
                else
                {
                    @unlink($imagefile);
                    $v->SetError($L['INVALID_IMAGE']);
                }
            }
            else
            {
                $v->SetError($L['INVALID_UPLOAD']);
            }
        }
        break;

        // Cropping an image
        case 'crop':
        {
            if( IsEmptyString($_REQUEST['scan']['preview']) )
            {
                $v->SetError($L['NO_THUMBS_FOR_PREVIEW']);
            }

            $imagefile = null;
        }
        break;

        // Cropping or no image provided
        default:
        {
            $imagefile = null;
        }
        break;
    }

    return $imagefile;
}


?>