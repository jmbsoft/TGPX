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


$functions = array('request' => 'txShPartnerRequest',
                   'submitrequest' => 'txPartnerRequestAdd',
                   'reset' => 'txShPasswordReset',
                   'sendreset' => 'txSendPasswordReset',
                   'doreset' => 'txShPasswordResetConfirm',
                   'overview' => 'txShPartnerOverview',
                   'edit' => 'txShPartnerEdit',
                   'update' => 'txPartnerEdit',
                   'logout' => 'txPartnerLogout',
                   'galleries' => 'txShPartnerGalleries',
                   'disable' => 'txPartnerDisableGallery');

require_once('includes/common.php');
require_once("{$GLOBALS['BASE_DIR']}/includes/template.class.php");
require_once("{$GLOBALS['BASE_DIR']}/includes/mysql.class.php");
require_once("{$GLOBALS['BASE_DIR']}/includes/validator.class.php");

// Do not allow browsers to cache this script
header("Expires: Mon, 26 Jul 1990 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");

SetupRequest();

$t = new Template();
$t->assign_by_ref('config', $C);

$DB = new DB($C['db_hostname'], $C['db_username'], $C['db_password'], $C['db_name']);
$DB->Connect();

$domain = $DB->Row('SELECT * FROM `tx_domains` WHERE `domain`=?', array(preg_replace('~^www\.~i', '', strtolower($_SERVER['HTTP_HOST']))));

if( $domain )
{
    if( $domain['categories'] )
    {
        $domain['categories'] = unserialize($domain['categories']);
    }
    
    $C['cookie_domain'] = $domain['domain'];
}


if( isset($functions[$_REQUEST['r']]) && function_exists($functions[$_REQUEST['r']]) )
{
    call_user_func($functions[$_REQUEST['r']]);
}
else
{
    txShPartnerLogin();
}

$DB->Disconnect();

function txPartnerLogout()
{
    global $C, $DB, $L, $t, $domain;
        
    if( isset($_COOKIE['tgpxpartner']) )
    {
        parse_str($_COOKIE['tgpxpartner'], $cookie);
        
        $DB->Update('UPDATE `tx_partners` SET `session`=?,`session_start`=? WHERE `username`=? AND `session`=?', 
                    array(null,
                          null,
                          $cookie['username'],
                          $cookie['session']));
    }
    
    setcookie('tgpxpartner', '', time() - 3600, '/', $C['cookie_domain']);
    
    $t->assign('logged_out', TRUE);
    
    txShPartnerLogin();
}

function txShPartnerLogin($errors = null)
{
    global $C, $DB, $L, $t, $domain;
      
    $t->assign('errors', $errors);
    $t->assign_by_ref('login', $_REQUEST);
        
    $t->display($domain['template_prefix'].'partner-login.tpl');
}

function txPartnerDisableGallery()
{
    global $C, $DB, $L, $t, $domain;
    
    $partner = ValidPartnerLogin();
    
    if( $partner !== FALSE )
    {
        $gallery = $DB->Row('SELECT * FROM `tx_galleries` WHERE `gallery_id`=? AND `partner`=? AND `status`!=?', array($_REQUEST['disable_id'], $partner['username'], 'disabled'));
        
        if( $gallery )
        {           
            $DB->Update('UPDATE `tx_galleries` SET `previous_status`=`status`,`status`=?,`admin_comments`=? WHERE `gallery_id`=?',
                        array('disabled',
                              "[Partner Request] " . $_REQUEST['reason'],
                              $gallery['gallery_id']));
                              
            $t->assign('disabled', TRUE);
            $t->assign('disabled_id', $gallery['gallery_id']);
        }
        
        txShPartnerGalleries();
    }
}

function txShPartnerEdit($errors = null)
{
    global $C, $DB, $L, $t, $domain;
    
    $partner = ValidPartnerLogin();
    
    if( $partner !== FALSE )
    {
        if( $errors != null )
        {
            $partner = array_merge($partner, $_REQUEST);
        }
        
        $fields =& GetUserPartnerFields($partner);
        
        $t->assign_by_ref('user_fields', $fields);
        $t->assign_by_ref('partner', $partner);
        $t->assign('errors', $errors);
        $t->display($domain['template_prefix'].'partner-edit.tpl');
    }
}

function txPartnerEdit()
{
    global $C, $DB, $L, $t, $domain;
    
    $partner = ValidPartnerLogin();
    
    if( $partner !== FALSE )
    {
        $v = new Validator();
        $v->Register($_REQUEST['email'], V_EMAIL, $L['INVALID_EMAIL']);
        
        // Check that new e-mail address does not conflict with another account
        if( $partner['email'] != $_REQUEST['email'] && $DB->Count('SELECT COUNT(*) FROM `tx_partners` WHERE `email`=?', array($_REQUEST['email'])) )
        {
            $v->SetError($L['EXISTING_EMAIL']);
        }
        
        // Check if new passwords match
        if( !IsEmptyString($_REQUEST['password']) )
        {
            $v->Register($_REQUEST['password'], V_EQUALS, $L['NO_PASSWORD_MATCH'], $_REQUEST['confirm_password']);
            $v->Register($_REQUEST['password'], V_LENGTH, sprintf($L['PASSWORD_LENGTH'], 3, 32), '3,32');
            $partner['password'] = sha1($_REQUEST['password']);
        }
        
        // Validation of user defined fields
        $fields =& GetUserPartnerFields();
        foreach($fields as $field)
        {
            if( $field['on_edit'] )
            {
                // Set values for unchecked checkboxes
                if( $field['type'] == FT_CHECKBOX && !isset($_REQUEST[$field['name']]) )
                {
                    $_REQUEST[$field['name']] = null;
                }
                
                if( $field['required_edit'] )
                {
                    $v->Register($_REQUEST[$field['name']], V_EMPTY, sprintf($L['REQUIRED_FIELD'], $field['label']));
                }
                
                if( !IsEmptyString($_REQUEST[$field['name']]) && $field['validation'] )
                {
                    $v->Register($_REQUEST[$field['name']], $field['validation'], $field['validation_message'], $field['validation_extras']);
                }
            }
        }   
        
        if( !$v->Validate() )
        {
            return $v->ValidationError('txShPartnerEdit', TRUE);
        }
        
        // Update the predefined fields
        $DB->Update('UPDATE `tx_partners` SET ' .
                    '`email`=?, ' .
                    '`name`=?, ' .
                    '`password`=? ' .
                    'WHERE `username`=?', 
                    array($_REQUEST['email'],
                          $_REQUEST['name'],
                          $partner['password'],
                          $partner['username']));
                          
        // Update user defined fields
        $_REQUEST['username'] = $partner['username'];
        UserDefinedUpdate('tx_partner_fields', 'tx_partner_field_defs', 'username', $_REQUEST['username'], $_REQUEST);
        
        $t->assign('updated', TRUE);
        txShPartnerEdit();
    }
}

function txShPartnerOverview()
{
    global $C, $DB, $L, $t, $domain;
    
    $partner = ValidPartnerLogin();
    
    if( $partner !== FALSE )
    {        
        // Default statistics
        $stats = array();
        $stats['galleries'] = 0;
        $stats['clicks'] = 0;
        $stats['unconfirmed'] = 0;
        $stats['pending'] = 0;
        $stats['approved'] = 0;
        $stats['used'] = 0;
        $stats['holding'] = 0;
        $stats['disabled'] = 0;
        
        // Calculate stats
        $result = $DB->Query('SELECT `status`,COUNT(*) AS `galleries`,SUM(`clicks`) AS `clicks` FROM `tx_galleries` WHERE `partner`=? GROUP BY `status`', array($partner['username']));
        while( $group = $DB->NextRow($result) )
        {
            $stats['galleries'] += $group['galleries'];
            $stats['clicks'] += $group['clicks'];
            $stats[$group['status']] = $group['galleries'];
        }
        $DB->Free($result);        
        
        // Setup partner dates
        DatetimeToTime($partner['date_added']);
        DatetimeToTime($partner['date_last_submit']);
        DatetimeToTime($partner['date_start']);
        DatetimeToTime($partner['date_end']);
        
        
        $t->assign_by_ref('partner', $partner);
        $t->assign_by_ref('stats', $stats);
        $t->display($domain['template_prefix'].'partner-overview.tpl');
    }
}

function txShPartnerGalleries()
{
    global $C, $DB, $L, $t, $domain;
    
    $partner = ValidPartnerLogin();
    
    if( $partner !== FALSE )
    {
        $sorters = array('added' => 'date_added', 'approved' => 'date_approved', 'clicks' => 'clicks', 'status' => 'status');
        $directions = array('asc' => 'ASC', 'desc' => 'DESC');
        
        // Filter user input
        $_REQUEST['p'] = is_numeric($_REQUEST['p']) ? $_REQUEST['p'] : 1;
        $_REQUEST['s'] = isset($sorters[$_REQUEST['s']]) ? $_REQUEST['s'] : 'added';
        $_REQUEST['d'] = isset($directions[$_REQUEST['d']]) ? $_REQUEST['d'] : 'asc';
        
        // Setup data for the query
        $galleries = array();
        $per_page = 10;
        $page = $_REQUEST['p'];
        $sort = isset($sorters[$_REQUEST['s']]) ? $sorters[$_REQUEST['s']] : 'date_added';
        $direction = isset($directions[$_REQUEST['d']]) ? $directions[$_REQUEST['d']] : 'ASC';
        
        // Load this partner's galleries
        $result = $DB->QueryWithPagination('SELECT * FROM `tx_galleries` WHERE `partner`=? AND `status`!=? ORDER BY # ' . $direction, 
                                           array($partner['username'], 'submitting', $sort),
                                           $page,
                                           $per_page);
        
        if( $result['result'] )
        {
            while( $gallery = $DB->NextRow($result['result']) )
            {
                DatetimeToTime($gallery['date_added']);
                DatetimeToTime($gallery['date_approved']);
                DatetimeToTime($gallery['date_scheduled']);
                DatetimeToTime($gallery['date_displayed']);
                                
                // Load a thumbnail for this gallery
                $preview = $DB->Row('SELECT * FROM `tx_gallery_previews` WHERE `gallery_id`=? LIMIT 1', array($gallery['gallery_id']));
                if( $preview )
                {
                    list($preview['preview_width'], $preview['preview_height']) = explode('x', $preview['dimensions']);
                    $gallery = array_merge($preview, $gallery);
                }
                
                // Get the categories for this gallery
                $gallery['categories'] =& CategoriesFromTags($gallery['tags']);
                
                // Get user defined fields
                $fields = $DB->Row('SELECT * FROM `tx_gallery_fields` WHERE `gallery_id`=?', array($gallery['gallery_id']));
                if( $fields )
                {
                    $fields = array_merge($fields, $gallery);
                }
                
                $galleries[] = $gallery;
            }
            $DB->Free($result['result']);
        }
        
        $t->assign('sort', $_REQUEST['s']);
        $t->assign('direction', $_REQUEST['d']);
        $t->assign_by_ref('pagination', $result);
        $t->assign_by_ref('partner', $partner);
        $t->assign_by_ref('galleries', $galleries);
        $t->display($domain['template_prefix'].'partner-galleries.tpl');
    }
}

function txShPartnerRequest($errors = null)
{
    global $C, $DB, $L, $t, $domain;
        
    $fields =& GetUserPartnerFields();    
    $t->assign('errors', $errors);
    $t->assign_by_ref('user_fields', $fields);
    $t->assign_by_ref('request', $_REQUEST);
        
    $t->display($domain['template_prefix'].'partner-request.tpl');
}

function txPartnerRequestAdd()
{
    global $C, $DB, $L, $t, $domain;
    
    $v = new Validator();
    $v->Register($_REQUEST['email'], V_EMAIL, $L['INVALID_EMAIL']);
    $v->Register($_REQUEST['name'], V_EMPTY, sprintf($L['REQUIRED_FIELD'], $L['YOUR_NAME']));
    $v->Register($_REQUEST['username'], V_REGEX, $L['INVALID_USERNAME'], '~^[a-z0-9_]+~i');
    $v->Register($_REQUEST['username'], V_LENGTH, sprintf($L['USERNAME_LENGTH'], 3, 32), '3,32');

    
    // Validation of user defined fields
    $fields =& GetUserPartnerFields();
    foreach($fields as $field)
    {
        if( $field['on_request'] )
        {
            if( $field['required_request'] )
            {
                $v->Register($_REQUEST[$field['name']], V_EMPTY, sprintf($L['REQUIRED_FIELD'], $field['label']));
            }
            
            if( !IsEmptyString($_REQUEST[$field['name']]) && $field['validation'] )
            {
                $v->Register($_REQUEST[$field['name']], $field['validation'], $field['validation_message'], $field['validation_extras']);
            }
        }
    }    
    
    // Check captcha code
    if( $C['request_captcha'] )
    {
        VerifyCaptcha($v);
    }
        
    // Check if this username exists
    if( $DB->Count('SELECT COUNT(*) FROM `tx_partners` WHERE `username`=?', array($_REQUEST['username'])) )
    {
        $v->SetError($L['USERNAME_TAKEN']);
    }    
    
    // Check if this e-mail address already exists
    if( $DB->Count('SELECT COUNT(*) FROM `tx_partners` WHERE `email`=?', array($_REQUEST['email'])) )
    {
        $v->SetError($L['EXISTING_REQUEST']);
    }
    
    // Check blacklist
    $blacklisted = CheckBlacklistPartner($_REQUEST);
    if( $blacklisted !== FALSE )
    {
        $v->SetError(sprintf(($blacklisted[0]['reason'] ? $L['BLACKLISTED_REASON'] : $L['BLACKLISTED']), $blacklisted[0]['match'], $blacklisted[0]['reason']));
    }
    
    if( !$v->Validate() )
    {
        return $v->ValidationError('txShPartnerRequest', TRUE);
    }
    
    
    // Insert partner data
    $DB->Update('INSERT INTO `tx_partners` VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                array($_REQUEST['username'],
                      sha1(RandomPassword()),
                      $_REQUEST['name'],
                      $_REQUEST['email'],
                      $_SERVER['REMOTE_ADDR'],
                      MYSQL_NOW,
                      null,
                      null,
                      null,
                      $C['submissions_per_person'],
                      $C['gallery_weight'],
                      null,
                      0,
                      null,
                      0,
                      0,
                      0,
                      'pending',
                      null,
                      null,
                      0,
                      0,
                      0,
                      1,
                      0));
                      
    // Insert user-defined fields
    $query_data = CreateUserInsert('tx_partner_fields', $_REQUEST);    
    $DB->Update('INSERT INTO `tx_partner_fields` VALUES ('.$query_data['bind_list'].')', $query_data['binds']);
    
    $t->assign_by_ref('request', $_REQUEST);
    $t->assign_by_ref('user_fields', $fields);
    $t->display($domain['template_prefix'].'partner-request-complete.tpl');
    
    // See if we need to e-mail any administrators
    $requests_waiting = $DB->Count('SELECT COUNT(*) FROM `tx_partners` WHERE `status`=?', array('pending'));
    $t->assign('requests_waiting', $requests_waiting);
    
    $administrators =& $DB->FetchAll('SELECT * FROM `tx_administrators`');
    foreach($administrators as $administrator)
    {
        if( $administrator['requests_waiting'] > 0 )
        {
            if( $administrator['notifications'] & E_PARTNER_REQUEST && $requests_waiting % $administrator['requests_waiting'] == 0 )
            {
                SendMail($administrator['email'], 'email-admin-requests.tpl', $t);
            }
        }
    }          
}

function txShPasswordReset($errors = null)
{
    global $DB, $C, $L, $t, $domain;
    
    $t->assign_by_ref('partner', $_REQUEST);
    $t->assign('errors', $errors);
    $t->display($domain['template_prefix'].'partner-reset.tpl');
}

function txSendPasswordReset()
{
    global $DB, $C, $t, $L, $domain;
    
    $v = new Validator();
    
    $v->Register($_REQUEST['email'], V_EMPTY, sprintf($L['REQUIRED_FIELD'], $L['EMAIL']));
    
    if( !IsEmptyString($_REQUEST['email']) )
    {
        $partner = $DB->Row('SELECT * FROM `tx_partners` WHERE `email`=?', array($_REQUEST['email']));   
        
        if( !$partner )
        {
            $v->SetError($L['NO_MATCHING_EMAIL']);
        }
        else
        {
            if( $partner['status'] == 'suspended' )
            {
                $v->SetError($L['ACCOUNT_SUSPENDED']);
            }
            else if( $partner['status'] != 'active' )
            {
                $v->SetError($L['ACCOUNT_PENDING']);
            }
        }
    }
    
    if( !$v->Validate() )
    {
        return $v->ValidationError('txShPasswordReset', TRUE);
    }
    
    $confirm_id = md5(uniqid(rand(), TRUE));
    
    $DB->Update('DELETE FROM `tx_partner_confirms` WHERE `username`=?', array($partner['username']));
    $DB->Update('INSERT INTO `tx_partner_confirms` VALUES (?,?,?)',
                array($partner['username'],
                      $confirm_id,
                      MYSQL_NOW));

    $t->assign_by_ref('partner', $partner);
    $t->assign('confirm_id', $confirm_id);
    
    SendMail($partner['email'], $domain['template_prefix'].'email-partner-reset-confirm.tpl', $t);
    
    $t->display($domain['template_prefix'].'partner-reset-confirm.tpl');
}

function txShPasswordResetConfirm()
{
    global $DB, $C, $t, $L, $domain;
    
    // Delete old confirmations
    $DB->Update('DELETE FROM `tx_partner_confirms` WHERE `date_sent` < ?', array(gmdate(DF_DATETIME, TIME_NOW - 86400)));
    
    $confirmation = $DB->Row('SELECT * FROM `tx_partner_confirms` WHERE `confirm_id`=?', array($_REQUEST['id']));
    
    if( $confirmation )
    {
        $DB->Update('DELETE FROM `tx_partner_confirms` WHERE `confirm_id`=?', array($_REQUEST['id']));
        $partner = $DB->Row('SELECT * FROM `tx_partners` WHERE `username`=?', array($confirmation['username']));
        
        if( !$partner )
        {
            $t->assign('error', $L['INVALID_CONFIRMATION']);
        }
        else
        {
            $partner['password'] = RandomPassword();
            
            $DB->Update('UPDATE `tx_partners` SET `password`=?,`session`=?,`session_start`=? WHERE `username`=?', 
                        array(sha1($partner['password']),
                              null,
                              0,
                              $partner['username']));
                              
            $t->assign_by_ref('partner', $partner);
            
            SendMail($partner['email'], $domain['template_prefix'].'email-partner-reset-confirmed.tpl', $t);
        }
    }
    else
    {
        $t->assign('error', $L['INVALID_CONFIRMATION']);
    }
    
    $t->display($domain['template_prefix'].'partner-reset-confirmed.tpl');
}
?>