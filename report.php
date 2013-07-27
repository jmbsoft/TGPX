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

require_once('includes/common.php');
require_once("{$GLOBALS['BASE_DIR']}/includes/template.class.php");
require_once("{$GLOBALS['BASE_DIR']}/includes/mysql.class.php");
require_once("{$GLOBALS['BASE_DIR']}/includes/validator.class.php");

SetupRequest();

$t = new Template();
$t->assign_by_ref('config', $C);

$DB = new DB($C['db_hostname'], $C['db_username'], $C['db_password'], $C['db_name']);
$DB->Connect();
$domain = $DB->Row('SELECT * FROM `tx_domains` WHERE `domain`=?', array(preg_replace('~^www\.~i', '', strtolower($_SERVER['HTTP_HOST']))));

if( $_SERVER['REQUEST_METHOD'] == 'POST' )
{
    txReportAdd();
}
else
{
    txShReportAdd();
}

$DB->Disconnect();

function txShReportAdd($errors = null)
{
    global $DB, $C, $L, $t, $domain;

    $gallery = $DB->Row('SELECT * FROM `tx_galleries` WHERE `gallery_id`=?', array($_REQUEST['id']));

    if( !$gallery )
    {
        $t->assign('error', $L['BAD_GALLERY_ID']);
        $t->display($domain['template_prefix'].'error-nice.tpl');
        return;
    }

    $t->assign_by_ref('report', $_REQUEST);
    $t->assign('errors', $errors);
    $t->assign('referrer', (isset($_REQUEST['referrer']) ? $_REQUEST['referrer'] : $_SERVER['HTTP_REFERER']));
    $t->assign_by_ref('gallery', $gallery);
    $t->display($domain['template_prefix'].'report-main.tpl');
}

function txReportAdd()
{
    global $DB, $C, $L, $t, $domain;

    $gallery = $DB->Row('SELECT * FROM `tx_galleries` WHERE `gallery_id`=?', array($_REQUEST['id']));

    $v = new Validator();
    $v->Register($_REQUEST['reason'], V_EMPTY, sprintf($L['REQUIRED_FIELD'], $L['REPORT']));

    if( !$gallery )
    {
        $v->SetError($L['BAD_GALLERY_ID']);
    }

    if( $C['report_captcha'] )
    {
        VerifyCaptcha($v);
    }

    if( !$v->Validate() )
    {
        return $v->ValidationError('txShReportAdd', TRUE);
    }


    $DB->Update('INSERT INTO `tx_reports` VALUES (?,?,?,?,?)',
                array(null,
                      $gallery['gallery_id'],
                      $_SERVER['REMOTE_ADDR'],
                      MYSQL_NOW,
                      $_REQUEST['reason']));

    $_REQUEST['report_id'] = $DB->InsertID();

    $t->assign_by_ref('report', $_REQUEST);
    $t->display($domain['template_prefix'].'report-complete.tpl');
    flush();

    // See if we need to e-mail any administrators
    $reports_waiting = $DB->Count('SELECT COUNT(*) FROM `tx_reports`');
    $t->assign('reports_waiting', $reports_waiting);

    $administrators =& $DB->FetchAll('SELECT * FROM `tx_administrators`');
    foreach($administrators as $administrator)
    {
        if( $administrator['reports_waiting'] > 0 )
        {
            if( $administrator['notifications'] & E_CHEAT_REPORT && $reports_waiting % $administrator['reports_waiting'] == 0 )
            {
                SendMail($administrator['email'], 'email-admin-reports.tpl', $t);
            }
        }
    }
}

?>