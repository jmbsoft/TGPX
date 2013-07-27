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

define('TGPX', TRUE);

require_once('../includes/common.php');
require_once("{$GLOBALS['BASE_DIR']}/includes/mysql.class.php");
require_once("{$GLOBALS['BASE_DIR']}/includes/template.class.php");
require_once("{$GLOBALS['BASE_DIR']}/includes/compiler.class.php");
require_once("{$GLOBALS['BASE_DIR']}/admin/includes/functions.php");

SetupRequest();

$DB = new DB($C['db_hostname'], $C['db_username'], $C['db_password'], $C['db_name']);
$DB->Connect();

function ResetPassword()
{
    global $template, $DB;

    $t = new Template();

    // Form submitted
    if( $_SERVER['REQUEST_METHOD'] == 'POST' )
    {
        $password = RandomPassword();
        $domain = preg_replace('~^www\.~', '', $_SERVER['HTTP_HOST']);

        $t->assign('password', $password);
        $t->assign('control_panel', "http://{$_SERVER['HTTP_HOST']}" . dirname($_SERVER['REQUEST_URI']) . "/index.php");

        $DB->Update('DELETE FROM `tx_administrators` WHERE `username`=?', array('administrator'));
        $DB->Update('INSERT INTO `tx_administrators` VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                    array('administrator',
                          sha1($password),
                          '',
                          0,
                          'Administrator',
                          "webmaster@$domain",
                          'administrator',
                          null,
                          null,
                          null,
                          null,
                          0,
                          0,
                          0,
                          0,
                          0,
                          null,
                          null));

        $DB->Disconnect();
        $t->assign('mode', 'confirmed');
    }
    else
    {

        $t->assign('mode', 'confirm');
    }

    echo $t->parse($template);
}


$template = <<<TEMPLATE
{php}
require_once("includes/header.php");
{/php}

<div id="main-content">
  <div id="centered-content" style="width: 800px;">
      <div class="heading">
      Reset TGPX Control Panel Access
      </div>

    {if \$mode == 'confirm'}
      <form action="reset-access.php" method="POST">
      <div class="margin-bottom margin-top">
        Pressing the button below will restore the default control panel administrator account.

        <br />
        <br />

        <div style="text-align: center">
        <button type="submit">Reset Access</button>
        </div>
      </div>
      </form>
    {else}
      <div class="notice margin-bottom margin-top">
        The default control panel administrator account has been restored. Use the information below to login to the control panel.
      </div>

      <div class="warn margin-bottom margin-top">
        Be sure to remove this file from your server after you write down your username and password
      </div>

      <b>Control Panel URL:</b> <a href="{\$control_panel}">{\$control_panel}</a><br />
      <b>Username:</b> administrator<br />
      <b>Password:</b> {\$password|htmlspecialchars}
    {/if}
  </div>
</div>


</body>
</html>
TEMPLATE;

ResetPassword();

$DB->Disconnect();

?>
