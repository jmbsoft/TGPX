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

$t = new Template();
$errors = array();

function ChangeMysql()
{
    global $errors, $t, $C, $template;

    // Form submitted
    if( $_SERVER['REQUEST_METHOD'] == 'POST' )
    {
        $connection = TestDBConnection();

        if( !$connection )
        {
            $t->assign_by_ref('errors', $errors);
            $t->assign_by_ref('request', $_REQUEST);
            $t->assign('mode', 'getdb');
        }
        else
        {
            // Write data to configuration file
            WriteConfig($_REQUEST);
            $t->assign('mode', 'done');
        }
    }


    // Show interface to get new MySQL information
    else
    {
        $_REQUEST['db_hostname'] = 'localhost';
        $t->assign_by_ref('request', $_REQUEST);
        $t->assign('mode', 'getdb');
    }

    echo $t->parse($template);
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
        <a href="docs/install.html" target="_blank"><img src="images/help.png" border="0" alt="Help" title="Help"></a>
      </div>
      Change MySQL Database Information
    </div>

      {if \$mode == 'getdb'}
      <form action="mysql-change.php" method="POST">
      <div class="margin-bottom margin-top">
        Please enter your new MySQL database information in the fields below.  If you are changing the database name, make sure that the tables
        from the old database have already been copied to the new database.
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
    {else}
      <div class="notice margin-bottom margin-top">
      The updated MySQL database information has been saved successfully
      </div>
    {/if}
  </div>
</div>


</body>
</html>
TEMPLATE;

ChangeMysql();

?>
