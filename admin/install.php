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
require_once("{$GLOBALS['BASE_DIR']}/includes/compiler.class.php");
require_once("{$GLOBALS['BASE_DIR']}/admin/includes/functions.php");

if( $_SERVER['REQUEST_URI'] == '/admin/install.php' )
{
    echo "<div style=\"color: red; font-family: arial; font-weight: bold; text-align: center;\">" .
         "It appears that you are trying to install TGPX in the base directory of your website; " .
         "this is not recommended and can cause conflicts with other scripts.  Please install TGPX " .
         "in a sub-directory of your website</div>";
    exit;
}

SetupRequest();

$t = new Template();
$errors = array();

function Initialize()
{
    global $errors, $t, $C, $template;

    // Already initialized
    if( !empty($C['db_username']) )
    {
        $t->assign('mode', 'done');
        echo $t->parse($template);
    }
    else
    {
        // Form submitted
        if( $_SERVER['REQUEST_METHOD'] == 'POST' )
        {
            $connection = TestDBConnection();

            if( !$connection )
            {
                $t->assign_by_ref('errors', $errors);
                $t->assign_by_ref('request', $_REQUEST);
                $t->assign('mode', 'getdb');
                echo $t->parse($template);
            }
            else
            {
                // Create database tables and setup initial login
                FileWrite("{$GLOBALS['BASE_DIR']}/data/.htaccess", "deny from all");
                CreateTables();
                WriteConfig($_REQUEST);
                RecompileTemplates();

                // Display initialization finished screen
                $t->assign('control_panel', "http://{$_SERVER['HTTP_HOST']}" . dirname($_SERVER['REQUEST_URI']) . "/index.php");
                $t->assign('mode', 'login');
                echo $t->parse($template);
            }
        }


        // Check that files are installed correctly
        else
        {
            // Run pre-initialization tests
            FilesTest();
            DirectoriesTest();
            TemplatesTest();

            if( is_dir('../utilities') )
            {
                $errors[] = 'For security purposes, the utilities directory must be removed';
            }


            if( count($errors) )
            {
                // Display failed test information
                $t->assign('mode', 'errors');
                $t->assign_by_ref('errors', $errors);
                echo $t->parse($template);
            }
            else
            {
                $_REQUEST['db_hostname'] = 'localhost';
                $t->assign_by_ref('request', $_REQUEST);
                $t->assign_by_ref('errors', $errors);
                $t->assign('mode', 'getdb');
                echo $t->parse($template);
            }
        }
    }
}

function TemplatesTest()
{
    global $errors;

    foreach( glob("{$GLOBALS['BASE_DIR']}/templates/*.*") as $filename )
    {
        if( !is_writeable($filename) )
        {
            $errors[] = "Template file $filename has incorrect permissions; change to 666";
        }
    }
}

function FilesTest()
{
    global $errors;

    $files = array("{$GLOBALS['BASE_DIR']}/includes/language.php",
                   "{$GLOBALS['BASE_DIR']}/includes/config.php");

    foreach( $files as $file )
    {
        if( !is_file($file) )
        {
            $errors[] = "File " . basename($file) . " is missing; please upload this file and set permissions to 666";
        }
        else if( !is_writeable($file) )
        {
            $errors[] = "File " . basename($file) . " has incorrect permissions; change to 666";
        }
    }
}

function DirectoriesTest()
{
    global $errors;

    $dirs = array(array('dir' => "{$GLOBALS['BASE_DIR']}/data", 'writeable' => TRUE),
                  array('dir' => "{$GLOBALS['BASE_DIR']}/templates/compiled", 'writeable' => TRUE),
                  array('dir' => "{$GLOBALS['BASE_DIR']}/templates/cache", 'writeable' => TRUE),
                  array('dir' => "{$GLOBALS['BASE_DIR']}/cache", 'writeable' => TRUE),
                  array('dir' => "{$GLOBALS['BASE_DIR']}/thumbs", 'writeable' => TRUE),
                  array('dir' => "{$GLOBALS['BASE_DIR']}/annotations", 'writeable' => FALSE),
                  array('dir' => "{$GLOBALS['BASE_DIR']}/fonts", 'writeable' => FALSE));

    foreach( $dirs as $dir )
    {
        if( !is_dir($dir['dir']) )
        {
            $errors[] = "Directory {$dir['dir']} is missing; please create this directory" . ($dir['writeable'] === TRUE ? " and set permissions to 777" : '');
        }
        else if( $dir['writeable'] === TRUE && !is_writeable($dir['dir']) )
        {
            $errors[] = "Directory {$dir['dir']} has incorrect permissions; change to 777";
        }
    }
}

function CreateTables()
{
    global $t, $DB;

    $DB = new DB($_REQUEST['db_hostname'], $_REQUEST['db_username'], $_REQUEST['db_password'], $_REQUEST['db_name']);
    $DB->Connect();

    $tables = array();
    IniParse("{$GLOBALS['BASE_DIR']}/includes/tables.php", TRUE, $tables);

    foreach( $tables as $name => $create )
    {
        $DB->Update("CREATE TABLE IF NOT EXISTS $name ( $create ) TYPE=MyISAM");
    }

    $password = RandomPassword();
    $domain = preg_replace('~^www\.~', '', $_SERVER['HTTP_HOST']);

    $t->assign('password', $password);

    // Setup default user defined partner account fields
    $columns = $DB->GetColumns('tx_partner_fields');

    if( in_array('sample_url_1', $columns) && $DB->Count('SELECT COUNT(*) FROM `tx_partner_field_defs` WHERE `name`=?', array('sample_url_1')) < 1 )
        $DB->Update("INSERT INTO `tx_partner_field_defs` VALUES (NULL,'sample_url_1','Sample URL 1','Text','size=\"80\"','',2,'','Sample URL 1 is not properly formatted',1,1,1,0,0)");

    if( in_array('sample_url_2', $columns) && $DB->Count('SELECT COUNT(*) FROM `tx_partner_field_defs` WHERE `name`=?', array('sample_url_2')) < 1 )
        $DB->Update("INSERT INTO `tx_partner_field_defs` VALUES (NULL,'sample_url_2','Sample URL 2','Text','size=\"80\"','',2,'','Sample URL 2 is not properly formatted',1,1,1,0,0)");

    if( in_array('sample_url_3', $columns) && $DB->Count('SELECT COUNT(*) FROM `tx_partner_field_defs` WHERE `name`=?', array('sample_url_3')) < 1 )
        $DB->Update("INSERT INTO `tx_partner_field_defs` VALUES (NULL,'sample_url_3','Sample URL 3','Text','size=\"80\"','',0,'','Sample URL 3 is not properly formatted',1,1,1,0,0)");


    // Setup default category and initial thumbnail preview dimensions
    $dimensions = array('180x150', '120x150');
    $category = array('per_day' => '-1',
                      'pics_allowed' => 1,
                      'pics_extensions' => 'jpg,jpeg,bmp,png',
                      'pics_minimum' => 10,
                      'pics_maximum' => 30,
                      'pics_file_size' => 12288,
                      'pics_preview_allowed' => 1,
                      'pics_preview_size' => $dimensions[1],
                      'movies_allowed' => 1,
                      'movies_extensions' => 'avi,mpg,mpeg,rm,wmv,mov,asf',
                      'movies_minimum' => 5,
                      'movies_maximum' => 30,
                      'movies_file_size' => 102400,
                      'movies_preview_allowed' => 1,
                      'movies_preview_size' => $dimensions[0]);

    StoreValue('default_category', serialize($category));
    Storevalue('preview_sizes', serialize($dimensions));


    // Setup administrator account
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
}

function TestDBConnection()
{
    global $errors;

    restore_error_handler();

    $handle = @mysql_connect($_REQUEST['db_hostname'], $_REQUEST['db_username'], $_REQUEST['db_password']);

    if( !$handle )
    {
        $errors[] = mysql_error();
    }
    else
    {
        if( !mysql_select_db($_REQUEST['db_name'], $handle) )
        {
            $errors[] = mysql_error($handle);
        }

        $result = mysql_query("SELECT VERSION()", $handle);
        $row = mysql_fetch_row($result);
        mysql_free_result($result);

        $version = explode('.', $row[0]);

        if( $version[0] < 4 )
        {
            $errors[] = "This software requires MySQL version 4.0.0 or newer<br />Your server has version {$row[0]} installed.";
        }

        mysql_close($handle);
    }

    set_error_handler('Error');

    if( count($errors) )
    {
        return FALSE;
    }

    return TRUE;
}


$template = <<<TEMPLATE
{php}
require_once("includes/header.php");
{/php}

<div id="main-content">
  <div id="centered-content" style="width: 800px;">
      <div class="heading">
      <div class="heading-icon">
        <a href="docs/install-script.html" target="_blank"><img src="images/help.png" border="0" alt="Help" title="Help"></a>
      </div>
      TGPX Installation
    </div>

      {if \$mode == 'getdb'}
      <form action="install.php" method="POST">
      <div class="margin-bottom margin-top">
        Please enter your MySQL database information in the fields below
      </div>

      {if count(\$errors)}
      <div class="alert margin-bottom">
        {foreach var=\$error from=\$errors}
          {\$error|htmlspecialchars}<br />
        {/foreach}
        Please double check your MySQL information and try again.
      </div>
      {/if}

      <div class="fieldgroup">
        <label for="db_username" style="width: 300px;">MySQL Username:</label>
        <input type="text" name="db_username" id="db_username" size="20" value="{\$request.db_username|htmlspecialchars}" />
      </div>

      <div class="fieldgroup">
        <label for="db_password" style="width: 300px;">MySQL Password:</label>
        <input type="text" name="db_password" id="db_password" size="20" value="{\$request.db_password|htmlspecialchars}" />
      </div>

      <div class="fieldgroup">
        <label for="db_name" style="width: 300px;">MySQL Database Name:</label>
        <input type="text" name="db_name" id="db_name" size="20" value="{\$request.db_name|htmlspecialchars}" />
      </div>

      <div class="fieldgroup">
        <label for="db_hostname" style="width: 300px;">MySQL Hostname:</label>
        <input type="text" name="db_hostname" id="db_hostname" size="20" value="{\$request.db_hostname|htmlspecialchars}" />
      </div>

      <div class="fieldgroup">
        <label for="" style="width: 300px;"></label>
        <button type="submit">Submit</button>
      </div>
    </form>
    {elseif \$mode == 'errors'}
      <div class="margin-bottom margin-top">
        Some of the pre-installation tests have failed.  Please see the error messages listed below and correct these issues.
        Once they have been corrected, you can reload this script to continue the installation process.
      </div>

      <div class="alert margin-bottom">
        {foreach var=\$error from=\$errors}
          {\$error|htmlspecialchars}<br />
        {/foreach}
      </div>
    {elseif \$mode == 'login'}
    <div class="notice margin-bottom margin-top">
      The software initialization has been completed; use the information below to login to the control panel
    </div>

    <b>Control Panel URL:</b> <a href="{\$control_panel}" onclick="return confirm('Have you written down your username and password?')">{\$control_panel}</a><br />
    <b>Username:</b> administrator<br />
    <b>Password:</b> {\$password|htmlspecialchars}
    {else}
      <div class="notice margin-bottom margin-top">
      The software has already been installed, please remove this file from your server
      </div>
    {/if}
  </div>
</div>


</body>
</html>
TEMPLATE;

Initialize();

?>

