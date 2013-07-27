<?PHP
// http://www.jmbsoft.com/license.php


// Enter the full path to the data directory of your TGPX installation
$DDIR = '/home/username/public_html/tgpx/data';


// Would you like to use the IP log to track unique clicks?
// Change this to FALSE if you do not want to use the IP log
$USE_IPLOG = TRUE;


// Would you like to use cookies to track unique clicks?
// Change this to FALSE if you do not want to use cookies
$USE_COOKIES = TRUE;


// The length of time (in seconds) before this script's cookie expires
// Cookies are used to track unique clicks
$EXPIRE = 86400;


// The template for your traffic trading script URL
// If you are not using a traffic trading script, do not change this value
$TEMPLATE = '{$gallery_url}';


// If your traffic trading script supports encoded URLs set this value to TRUE.
// This will allow you to send traffic to URLs that contain query strings without a problem.
// If you are not using a traffic trading script, do not change this value
$ENCODE = FALSE;


###########################################################################################################
##              DONE EDITING THIS FILE.  YOU DO NOT NEED TO EDIT THIS FILE ANY FURTHER                   ##
###########################################################################################################

if( $ENCODE )
{
    $_GET['u'] = urlencode($_GET['u']);
}

$TEMPLATE = str_replace('{$skim}', $_GET['s'], $TEMPLATE);
$TEMPLATE = str_replace('{$gallery_url}', $_GET['u'], $TEMPLATE);


foreach( $_GET as $key => $value )
{
    $TEMPLATE = str_replace("{\$$key}", $value, $TEMPLATE);
}


if( is_numeric($_GET['id']) )
{
    $value = $_GET['id'];
    $cookie_set = FALSE;    

    if( $USE_COOKIES && isset($_COOKIE['tgpx_click']) )
    {
        if( strstr(",{$_COOKIE['tgpx_click']},", ",{$_GET['id']},") )
        {
            $cookie_set = TRUE;
        }
        else
        {
            $value = "{$_COOKIE['tgpx_click']},{$_GET['id']}";
        }
    }

    if( !$USE_IPLOG )
    {
        $_SERVER['REMOTE_ADDR'] = '';
    }

    if( !$cookie_set )
    {
        $fd = fopen("$DDIR/clicklog", 'a');
        if( $fd )
        {
            flock($fd, LOCK_EX);
            fwrite($fd, "{$_GET['id']}|{$_SERVER['REMOTE_ADDR']}\n");
            flock($fd, LOCK_UN);
            fclose($fd);
        }

        if( $USE_COOKIES )
        {
            setcookie('tgpx_click', $value, time() + $EXPIRE, '/');
        }
    }    
}


header("Location: $TEMPLATE");

?>
