<?php
if( $_GET['a'] == 'phpinfo' )
{
    phpinfo();
    exit;
}

if( !defined('E_STRICT') ) define('E_STRICT', 2048);
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);

$server = GetServerCapabilities();
?>

<html>
<head>
  <title>Server Test Script</title>
  <style>
  body, td {
    font-family: Tahoma;
    font-size: 9pt;
  }

  .h2 {
    font-size: 18pt;
    font-weight: bold;
    margin-bottom: 10px;
  }

  .passed {
    font-weight: bold;
    color: green;
  }

  .failed {
    font-weight: bold;
    color: red;
  }

  .warn {
    font-weight: bold;
    color: orange;
  }

  a.vps {
    font-size: 110%;
    font-weight: bold;
    color: #a25dba;
    text-decoration: none;
  }

  a.vps > span {
    font-size: 110%;
    display: inline-block;
    width: 1000px;
    color: #a25dba;
    margin: 0px auto 15px;
    padding: 6px;
    text-align: center;
    background-color: #f6e7fd;
    border: 1px solid #c38dd4;
    -moz-border-radius: 5px;
    -moz-box-shadow: 0px 0px 6px #aaa;
    -webkit-border-radius: 5px;
    -webkit-box-shadow: 0px 0px 6px #aaa;
  }
  </style>
</head>
<body>

<div style="text-align: center">
<div class="h2">TGPX Server Test Script</div>

<a href="http://manage.aff.biz/z/155/CD3560/" target="_blank" class="vps">
<span>
VPS.net hosting offers a pre-configured setup that has all of the requirements for TGPX, with prices starting under $20/month<br />
You can be setup and running with a fully compatible and scalable system in a matter of minutes!!</a>
</span>
</a>

<br />

<table width="925" align="center" border="1" cellpadding="5" cellspacing="0">
  <tr bgcolor="#ececec">
    <td width="200" valign="top">
      <b>TEST</b>
    </td>
    <td width="200">
      <b>REQUIREMENT</b>
    </td>
    <td>
      <b>YOUR SERVER</b>
    </td>
  </tr>
  <tr>
    <td width="200" valign="top">
      <b>Operating System</b>
    </td>
    <td valign="top" width="200">
      Unix or Linux
    </td>
    <td valign="top">
      <?php
      $uname = php_uname('s');
      $class = strpos($uname, 'Windows') === 0 ? 'failed' : 'passed';

      echo "<span class=\"$class\">" . $uname . " - " .
            ($class == 'failed' ? '' : 'looks ok') .
            "</span>";
      ?>
    </td>
  </tr>

  <tr>
    <td width="200" valign="top">
      <b>PHP Version</b>
    </td>
    <td valign="top" width="200">
      4.3.0+
    </td>
    <td valign="top">
      <?php
      list($a, $b, $c) = explode('.', PHP_VERSION);
      $class = $a > 4 || ($a == 4 && $b >= 3) ? 'passed' : 'failed';

      echo "<span class=\"$class\">" . PHP_VERSION . " - " .
           ($class == 'failed' ? 'PHP Version 4.3.0 or newer is required' : 'ok') .
           "</span>";
      ?>
    </td>
  </tr>
  <tr>
    <td width="200" valign="top">
      <b>MySQL Extension</b>
    </td>
    <td width="200">
      Required
    </td>
    <td>
      <?php
      if( extension_loaded('mysql') )
      {
          echo "<div class=\"passed\">MySQL extension is installed - ok</div>";
      }
      else
      {
          echo "<span class=\"failed\">The MySQL extension is not installed; this software requires the MySQL extension and will not work without it</span>";
      }
      ?>
    </td>
  </tr>
  <tr>
    <td width="200" valign="top">
      <b>MySQL Version</b>
    </td>
    <td valign="top" width="200">
      4.0.4+ with all database privileges enabled
    </td>
    <td valign="top">
      <?php

      if( $_POST['user'] && $_POST['host'] )
      {
          $dbh = @mysql_connect($_POST['host'], $_POST['user'], $_POST['pass']);

          if( $dbh )
          {
              if( @mysql_select_db($_POST['db']) )
              {
                  $result = mysql_query('SELECT VERSION()');
                  $row = mysql_fetch_row($result);
                  $mysql_version = $row[0];

                  preg_match('~^(\d+)\.(\d+)\.(\d+)~', $mysql_version, $matches);
                  list($a, $b, $c) = array($matches[1], $matches[2], $matches[3]);

                  if( $a > 4 || ($a == 4 && ($b > 0 || $c > 3)) )
                  {
                      @mysql_query('CREATE TABLE `tlx_test_script_db` ( `tester` INT )');
                      if( @mysql_query('LOCK TABLES `tlx_test_script_db` WRITE') )
                      {
                          echo "<div class=\"passed\">MySQL version $mysql_version installed - ok</div>";
                      }
                      else
                      {
                          echo "<div class=\"failed\">MySQL LOCK TABLES privilege not enabled: " . mysql_error() . "</div>";
                      }

                      @mysql_query('UNLOCK TABLES');
                      @mysql_query('DROP TABLE `tlx_test_script_db`');
                  }
                  else
                  {
                      echo "<div class=\"failed\">MySQL version $mysql_version installed<br />Version 4.0.4 or newer is required for this software</div>";
                  }
              }
              else
              {
                  echo "<div class=\"failed\">Could not select database '" . htmlspecialchars($_POST['db']) . "': " . mysql_error() . "</div>";
              }
          }
          else
          {
              echo "<div class=\"failed\">Could not connect to MySQL database server: " . mysql_error() . "</div>";
          }

          echo "<br />";
      }

      ?>

      Enter the following information to check the MySQL version:
      <br />
      <br />

      <form style="margin: 0; padding: 0;" method="POST" action="server-test.php">
      Username: <input type="text" name="user" value="<?php echo htmlspecialchars($_POST['user']); ?>" style="margin-left: 1px;"><br />
      Password: <input type="text" name="pass" value="<?php echo htmlspecialchars($_POST['pass']); ?>" style="margin-left: 4px;"><br />
      Database: <input type="text" name="db" value="<?php echo htmlspecialchars($_POST['db']); ?>" style="margin-left: 5px;"><br />
      Hostname: <input type="text" name="host" value="<?php echo (isset($_POST['host']) ? htmlspecialchars($_POST['host']) : 'localhost'); ?>" style="margin-left: 0px;"><br />
      <input type="submit" value="Test MySQL" style="margin-left: 64px;">
      </form>
    </td>
  </tr>
  <tr>
    <td width="200" valign="top">
      <b>GD Extension</b>
    </td>
    <td valign="top" width="200">
      Optional, but needed if you want to use the thumbnail cropping and
      verification code features of the software.
    </td>
    <td valign="top">
      <?php
      if( $server['have_gd'] )
      {
          $gd = gd_info();

          echo "<div class=\"passed\">GD extension version {$gd['GD Version']} is installed - ok</div>" .
               ($gd['FreeType Support'] ? "<div class=\"passed\">Freetype support is installed - ok</div>" : "<div class=\"failed\">Freetype support is not installed; use of TTF fonts will not be available</div>") .
               ($gd['JPG Support'] ? "<div class=\"passed\">JPEG support is installed - ok</div>" : "<div class=\"failed\">JPG support is not installed; will not be able to read/write JPEG images</div>") .
               ($gd['PNG Support'] ? "<div class=\"passed\">PNG support is installed - ok</div>" : "<div class=\"failed\">PNG support is not installed; will not be able to read/write PNG images</div>");
      }
      else
      {
          echo "<span class=\"failed\">The GD extension is not installed</span>";
      }
      ?>
    </td>
  </tr>
  <tr>
    <td width="200" valign="top">
      <b>PHP safe_mode disabled</b>
    </td>
    <td valign="top" width="200">
      Optional, but some features will not be available or will be limited in their use.
    </td>
    <td valign="top">
      <?php
      if( $server['safe_mode'] )
      {
          echo "<span class=\"failed\">PHP safe_mode appears to be enabled, or PHP is running in a restrictive operating mode</span>";
      }
      else
      {
          echo "<span class=\"passed\">PHP safe_mode is disabled - ok</span>";
      }
      ?>
    </td>
  </tr>
  <tr>
    <td width="200" valign="top">
      <b>shell_exec() function available</b>
    </td>
    <td valign="top" width="200">
      Optional, but some features will not be available or will be limited in their use.
    </td>
    <td valign="top">
      <?php
      if( $server['shell_exec'] )
      {
          echo "<span class=\"passed\">shell_exec() function is available - ok</span>";
      }
      else
      {
          echo "<span class=\"failed\">The PHP shell_exec() function " .
               ($server['safe_mode'] ? "cannot be used because of the safe_mode setting" : "is disabled") .
               ".  This will prevent you from using the gallery scanner and may limit some of the other software functions</span>";
      }
      ?>
    </td>
  </tr>
  <tr>
    <td width="200" valign="top">
      <b>cURL Extension</b>
    </td>
    <td valign="top" width="200">
      Optional, but offers some advanced features (https support, proxy support)
    </td>
    <td valign="top">
      <?php
      if( extension_loaded('curl') )
      {
          echo "<div class=\"passed\">cURL extension is installed - ok</div>";
      }
      else
      {
          echo "<span class=\"warn\">The cURL extension is not installed; the software can work without this extension but it does offer some enhanced capabilities</span>";
      }
      ?>
    </td>
  </tr>
  <tr>
    <td width="200" valign="top">
      <b>ImageMagick</b>
    </td>
    <td valign="top" width="200">
      Optional,

      <?php if( $server['have_gd'] ): ?>
      the GD extension can be used instead
      <?php else: ?>
      but you will not be able to use the thumbnail cropping features of the software.
      <?php endif; ?>
    </td>
    <td valign="top">
      <?php
      if( $server['have_magick'] )
      {
          echo "<div class=\"passed\">ImageMagick command line tools have been found - ok<br />{$server['convert']}<br />{$server['composite']}</div>";
      }
      else
      {
          echo "<span class=\"" . ($server['have_gd'] ? 'warn' : 'failed') . "\">The ImageMagick command line tools ";

          if( $server['safe_mode'] ):
              echo "cannot be used because of the safe_mode setting.";
          elseif( !$server['shell_exec'] ):
              echo "cannot be used because the shell_exec() function is not available.";
          else:
              echo "could not be found.";
          endif;

          if( $server['have_gd'] )
          {
              echo "  The GD extension can be used instead, however ImageMagick generally produces better quality images.";
          }

          echo "</span>";
      }
      ?>
    </td>
  </tr>
  <tr>
    <td width="200" valign="top">
      <b>PHP CLI version installed</b>
    </td>
    <td valign="top" width="200">
      Optional, but some features will not be available or will be limited in their use.
    </td>
    <td valign="top">
      <?php
      if( $server['php_cli'] )
      {
          echo "<span class=\"passed\">PHP CLI version is installed - ok<br />{$server['php_cli']}</span>";

          if( $server['php_cli_safe_mode'] )
          {
              echo "<div class=\"failed\">The CLI version of PHP is running with safe_mode enabled</div>";
          }
      }
      else
      {
          echo "<span class=\"failed\">The PHP CLI version ";

          if( $server['safe_mode'] ):
              echo "cannot be used because of the safe_mode setting.";
          elseif( !$server['shell_exec'] ):
              echo "cannot be used because the shell_exec() function is not available.";
          else:
              echo "could not be found.";
          endif;

          echo "  This will prevent you from using the gallery scanner function, cron, and may limit some of the other software functions</span>";
      }
      ?>
    </td>
  </tr>
  <tr>
    <td width="200" valign="top">
      <b>PHP open_basedir</b>
    </td>
    <td valign="top" width="200">
      For TGPX Server Edition this setting will need to be
      configured so that TGPX can write TGP pages in all
      of the website directories on your server.
    </td>
    <td valign="top">
      <?php
        ob_start();
        $open_basedir = ini_get('open_basedir');
        $buffer = ob_get_contents();
        ob_end_clean();

      if( empty($buffer) && empty($open_basedir) )
      {
          echo "<div class=\"passed\">There does not appear to be an open_basedir restriction in place - ok</div>";
      }
      else if( $buffer )
      {
          echo "<div class=\"warn\">The open_basedir setting could not be determined.  Contact your server administrator to get this information.</div>";
      }
      else
      {
          echo "<div class=\"warn\">The following open_basedir restriction is in effect:<br />$open_basedir</div>";
      }
      ?>
    </td>
  </tr>
</table>

<br />
<br />

<?php
if( count($server['message']) > 0 ):
?>
<table width="925" align="center" border="0" cellpadding="5" cellspacing="0">
<tr>
<td>
<b>Messages Generated from the Server Compatibility Test:</b>
<ul>
<?php
    foreach( $server['message'] as $message ):
?>
<li> <?php echo nl2br(htmlspecialchars($message)); ?>
<?php
  endforeach;
?>
</ul>
</td>
</tr>
</table>
<?php
endif;
?>

<a href="server-test.php?a=phpinfo" target="iframe" style="color: blue; font-weight: bold">View output of phpinfo()</a>

<br />
<br />

<iframe src="" id="iframe" name="iframe" width="925" height="400" frameborder="0"></iframe>

<br />
<br />

</div>

</body>
</html>

<?php

function GetServerCapabilities()
{
    // Handle recursion issues with CGI version of PHP
    if( getenv('PHP_REPEAT') ) return;
    putenv('PHP_REPEAT=TRUE');

    $GLOBALS['LAST_ERROR'] = null;

    $server = array('safe_mode' => TRUE,
                    'shell_exec' => FALSE,
                    'have_gd' => extension_loaded('gd'),
                    'have_magick' => FALSE,
                    'magick6' => FALSE,
                    'have_imager' => FALSE,
                    'php_cli' => null,
                    'mysql' => null,
                    'mysqldump' => null,
                    'convert' => null,
                    'composite' => null,
                    'dig' => null,
                    'tar' => null,
                    'gzip' => null,
                    'message' => array(),
                    'php_cli_safe_mode' => FALSE,
                    'php_cli_zend_optimizer' => TRUE);

    set_error_handler('GetServerCapabilitiesError');
    error_reporting(E_ALL);

    $server['safe_mode'] = @ini_get('safe_mode');

    if( $server['safe_mode'] === null || isset($GLOBALS['LAST_ERROR']) )
    {
        $server['safe_mode'] = TRUE;
        $server['message'][] = "The ini_get() PHP function appears to be disabled on your server\nPHP says: " . $GLOBALS['LAST_ERROR'];
    }
    else if( $server['safe_mode'] )
    {
        $server['message'][] = "Your server is running PHP with safe_mode enabled";

        // Do tests on safe_mode_exec_dir
    }
    else
    {
        $server['safe_mode'] = FALSE;

        $GLOBALS['LAST_ERROR'] = null;

        $open_basedir = ini_get('open_basedir');

        // See if shell_exec is available on the server
        @shell_exec('ls -l');
        if( isset($GLOBALS['LAST_ERROR']) )
        {
            $server['shell_exec'] = FALSE;
            $server['message'][] = "The shell_exec() PHP function appears to be disabled on your server\nPHP says: " . $GLOBALS['LAST_ERROR'];
        }
        else
        {
            $server['shell_exec'] = TRUE;
        }

        if( $server['shell_exec'] )
        {
            // Check for cli version of PHP
            $server['php_cli'] = LocateExecutable('php', '-v', '(cli)', $open_basedir);

            if( !$server['php_cli'] )
            {
                $server['php_cli'] = LocateExecutable('php-cli', '-v', '(cli)', $open_basedir);
            }

            // Check for Zend Optimizer and safe_mode
            if( $server['php_cli'] )
            {
                $cli_settings = shell_exec("{$server['php_cli']} -r \"echo serialize(array('safe_mode' => ini_get('safe_mode'), 'zend_optimizer' => extension_loaded('Zend Optimizer')));\" 2>/dev/null");
                $cli_settings = unserialize($cli_settings);

                if( $cli_settings !== FALSE )
                {
                    if( $cli_settings['safe_mode'] )
                    {
                        $server['php_cli_safe_mode'] = TRUE;
                    }

                    if( !$cli_settings['zend_optimizer'] )
                    {
                        $server['php_cli_zend_optimizer'] = FALSE;
                    }
                }
            }

            // Check for mysql executables
            $server['mysql'] = LocateExecutable('mysql', null, null, $open_basedir);
            $server['mysqldump'] = LocateExecutable('mysqldump', null, null, $open_basedir);

            // Check for imagemagick executables
            $server['convert'] = LocateExecutable('convert', null, null, $open_basedir);
            $server['composite'] = LocateExecutable('composite', null, null, $open_basedir);

            // Check for dig
            $server['dig'] = LocateExecutable('dig', null, null, $open_basedir);

            // Check for archiving executables
            $server['tar'] = LocateExecutable('tar', null, null, $open_basedir);
            $server['gzip'] = LocateExecutable('gzip', null, null, $open_basedir);

            if( $server['convert'] && $server['composite'] )
            {
                $server['have_magick'] = TRUE;
                $server['magick6'] = FALSE;

                // Get version
                $output = shell_exec("{$server['convert']} -version");

                if( preg_match('~ImageMagick 6\.~i', $output) )
                {
                    $server['magick6'] = TRUE;
                }
            }
        }
    }

    $server['have_imager'] = $server['have_magick'] || $server['have_gd'];

    error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT);
    restore_error_handler();

    return $server;
}

function LocateExecutable($executable, $output_arg = null, $output_search = null, $open_basedir = FALSE)
{

    $executable_dirs = array('/bin',
                             '/usr/bin',
                             '/usr/local/bin',
                             '/usr/local/mysql/bin',
                             '/sbin',
                             '/usr/sbin',
                             '/usr/lib',
                             '/usr/local/ImageMagick/bin',
                             '/usr/X11R6/bin');

    if( isset($GLOBALS['BASE_DIR']) )
    {
        $executable_dirs[] = "{$GLOBALS['BASE_DIR']}/bin";
    }

    if( isset($_SERVER['DOCUMENT_ROOT']) )
    {
        $executable_dirs[] = realpath($_SERVER['DOCUMENT_ROOT'] . '/../bin/');
    }

    // No open_basedir restriction
    if( !$open_basedir )
    {
        foreach( $executable_dirs as $dir )
        {
            if( @is_file("$dir/$executable") && @is_executable("$dir/$executable") )
            {
                if( $output_arg )
                {
                    $output = shell_exec("$dir/$executable $output_arg");

                    if( stristr($output, $output_search) !== FALSE )
                    {
                        return "$dir/$executable";
                    }
                }
                else
                {
                    return "$dir/$executable";
                }
            }
        }
    }

    $which = trim(shell_exec("which $executable"));

    if( !empty($which) )
    {
        if( $output_arg )
        {
            $output = shell_exec("$which $output_arg");

            if( stristr($output, $output_search) !== FALSE )
            {
                return $which;
            }
        }
        else
        {
            return $which;
        }
    }


    $whereis = trim(shell_exec("whereis -B ".join(' ', $executable_dirs)." -f $executable"));
    preg_match("~$executable: (.*)~", $whereis, $matches);
    $whereis = explode(' ', trim($matches[1]));

    if( count($whereis) )
    {
        if( $output_arg )
        {
            foreach( $whereis as $executable )
            {
                $output = shell_exec("$executable $output_arg");

                if( stristr($output, $output_search) !== FALSE )
                {
                    return $executable;
                }
            }
        }
        else
        {
            return $whereis[0];
        }
    }

    return null;
}

function GetServerCapabilitiesError($code, $string, $file, $line)
{
    $GLOBALS['LAST_ERROR'] = $string;
}


?>
