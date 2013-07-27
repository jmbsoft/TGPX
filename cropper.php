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

define('TGPX', TRUE);

$functions = array('preview' => 'txDownloadThumb',
                   'full' => 'txDownloadImage');

require_once('includes/common.php');
require_once("{$GLOBALS['BASE_DIR']}/includes/http.class.php");
require_once("{$GLOBALS['BASE_DIR']}/admin/includes/json.class.php");

// Setup JSON response
$json = new JSON();

set_error_handler('AjaxError');

// Do not allow browsers to cache this script
header("Expires: Mon, 26 Jul 1990 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");

SetupRequest();

if( isset($functions[$_REQUEST['r']]) && function_exists($functions[$_REQUEST['r']]) )
{
    call_user_func($functions[$_REQUEST['r']]);
}

function txDownloadImage()
{
    global $L, $json, $C;

    $out = array('status' => JSON_FAILURE);
    $cachefile = SafeFilename("_{$_REQUEST['gallery_id']}_" . md5($_REQUEST['image']) . ".jpg", FALSE);

    if( is_file("{$GLOBALS['BASE_DIR']}/cache/$cachefile") )
    {
        $out['imagefile'] = $cachefile;
        $out['src'] = "{$C['install_url']}/cache/$cachefile";
        $out['status'] = JSON_SUCCESS;

        $size = @getimagesize("{$GLOBALS['BASE_DIR']}/cache/$cachefile");

        $out['width'] = $size[0];
        $out['height'] = $size[1];
    }
    else
    {
        $http = new Http();
        if( $http->Get($_REQUEST['image'], TRUE, $_REQUEST['gallery_url']) )
        {
            FileWrite("{$GLOBALS['BASE_DIR']}/cache/$cachefile", $http->body);

            $size = @getimagesize("{$GLOBALS['BASE_DIR']}/cache/$cachefile");

            if( $size !== FALSE )
            {
                $out['imagefile'] = $cachefile;
                $out['src'] = "{$C['install_url']}/cache/$cachefile";
                $out['width'] = $size[0];
                $out['height'] = $size[1];
                $out['status'] = JSON_SUCCESS;
            }
            else
            {
                unlink("{$GLOBALS['BASE_DIR']}/cache/$cachefile");
                $out['message'] = $L['IMAGE_DOWNLOAD_INVALID'];
            }
        }
        else
        {
            $out['message'] = sprintf($L['IMAGE_DOWNLOAD_FAILED'], $http->errstr); "Could not download image: " . $http->errstr;
        }
    }

    echo $json->encode($out);
}

function txDownloadThumb()
{
    global $json, $C;

    $out = array('status' => JSON_FAILURE);
    $id = md5($_REQUEST['thumb']);
    $cachefile = SafeFilename("_{$_REQUEST['gallery_id']}_" . $id . ".jpg", FALSE);

    if( !is_file("{$GLOBALS['BASE_DIR']}/cache/$cachefile") )
    {
        $http = new Http();
        if( $http->Get($_REQUEST['thumb'], TRUE, $_REQUEST['gallery_url']) )
        {
            FileWrite("{$GLOBALS['BASE_DIR']}/cache/$cachefile", $http->body);
        }
    }

    $out['size'] = @getimagesize("{$GLOBALS['BASE_DIR']}/cache/$cachefile");

    if( $out['size'] !== FALSE )
    {
        $out['src'] = "{$C['install_url']}/cache/$cachefile";
        $out['status'] = JSON_SUCCESS;
    }
    else
    {
        unlink("{$GLOBALS['BASE_DIR']}/cache/$cachefile");
    }

    echo $json->encode($out);
}

function AjaxError($code, $string, $file, $line)
{
    global $json;

    if( $code == E_NOTICE || $code == E_STRICT )
    {
        return;
    }

    $error = array();

    $error['message'] = "$string on line $line of " . basename($file);
    $error['status'] = JSON_FAILURE;

    echo $json->encode($error);

    exit;
}
?>
