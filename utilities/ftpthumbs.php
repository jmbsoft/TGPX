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

$remote_url = 'http://www.remoteserver.com/thumbs';

$ftp_host = 'ftp.remoteserver.com';
$ftp_user = 'username';
$ftp_pass = 'password';
$ftp_port = '21';
$ftp_dir = 'public_html/thumbs';



####################################################################
#                      DONE EDITING THIS FILE                      #
####################################################################



define('TGPX', TRUE);

require_once('../includes/common.php');
require_once("{$GLOBALS['BASE_DIR']}/includes/mysql.class.php");


// Run from shell only
if( isset($_SERVER['REQUEST_METHOD']) )
{
    echo "This script can only be run from the command line or through cron\n";
    exit;
}

// Make sure CLI API is being used
if( php_sapi_name() != 'cli' )
{
    echo "Invalid access: This script requires the CLI version of PHP\n";
    exit;
}

// Check that the FTP extension is loaded
if( !extension_loaded('ftp') )
{
    echo "The PHP FTP extension is not enabled; ask your server administrator to recompile PHP with FTP support\n";
    exit;
}


$verbose = ($_SERVER['argv'][1] == '-v');
$transferred = array();

$ftp = ftp_connect($ftp_host, $ftp_port);

if( $ftp )
{
    if( ftp_login($ftp, $ftp_user, $ftp_pass) )
    {
        ftp_pasv($ftp, TRUE);
        
        if( ftp_chdir($ftp, $ftp_dir) )
        {
            $DB = new DB($C['db_hostname'], $C['db_username'], $C['db_password'], $C['db_name']);
            $DB->Connect();
            
            
            // Upload thumbnails
            if( $verbose ) echo "Uploading thumbnails...\n";
            $result = $DB->Query('SELECT * FROM `tx_galleries` JOIN `tx_gallery_previews` USING (`gallery_id`)');
            while( $gallery = $DB->NextRow($result) )
            {                
                // File already exists on remote FTP server, so mark as still in use
                if( preg_match("~^".preg_quote($remote_url)."~", $gallery['preview_url']) )
                {
                    $transferred["{$gallery['preview_id']}.jpg"] = 1;
                }
                
                // Thumbnail needs to be transferred and DB updated
                else if( preg_match("~^".preg_quote($C['preview_url'])."~", $gallery['preview_url']) )
                {
                    if( $verbose ) echo "{$gallery['preview_id']}.jpg\n";
                    
                    $success = ftp_put($ftp, "{$gallery['preview_id']}.jpg", "{$C['preview_dir']}/{$gallery['preview_id']}.jpg", FTP_BINARY);
                    
                    if( $success )
                    {
                        $transferred["{$gallery['preview_id']}.jpg"] = 1;
                        $DB->Update('UPDATE `tx_gallery_previews` SET `preview_url`=? WHERE `preview_id`=?', array("$remote_url/{$gallery['preview_id']}.jpg", $gallery['preview_id']));
                        @unlink("{$C['preview_dir']}/{$gallery['preview_id']}.jpg");
                    }
                }
            }            
            $DB->Free($result);
            
            
            // Remove thumbs that are no longer being used
            if( $verbose ) echo "Removing no longer used thumbnails...\n";
            $dir_contents = ftp_nlist($ftp, '.');            
            if( is_array($dir_contents) )
            {
                foreach( $dir_contents as $file )
                {
                    $file = strtolower($file);
                    if( preg_match('~\.jpg$~i', $file) && !isset($transferred[$file]) )
                    {
                        ftp_delete($ftp, $file);
                    }
                }
            }
            
            $DB->Disconnect();
        }
    }
    
    ftp_close($ftp);
    
    if( $verbose ) echo "Transfer complete!\n\n";
}
else
{
    echo "Could not connect to FTP server $ftp_host:$ftp_port\n";
}

?>