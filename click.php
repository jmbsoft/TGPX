<?PHP
## http://www.jmbsoft.com/license.php

// These settings must match the database settings used for TGPX

$USERNAME = 'username';          // The username to access your MySQL database
$PASSWORD = 'password';          // The password to access your MySQL database
$DATABASE = 'database';          // The name of your MySQL database
$HOSTNAME = 'localhost';         // The hostname of your MySQL database server



// Would you like to use the IP log to track unique clicks?
// Change this to TRUE if you want to use the IP log
$USE_IPLOG = FALSE;


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


if( $_GET['id'] )
{
    $value = null;
    $cookie_set = FALSE;
    $ip_logged = FALSE;

    if( $USE_COOKIES && isset($_COOKIE['tgpx_click']) )
    {
        $ids = explode(',', $_COOKIE['tgpx_click']);

        if( in_array($_GET['id'], $ids) )
        {
            $cookie_set = TRUE;
        }
        else
        {
            $ids[] = $_GET['id'];
            $value = join(',', $ids);
        }
    }
    else
    {
        $value = $_GET['id'];
    }


    if( !$cookie_set )
    {
        mysql_connect($HOSTNAME, $USERNAME, $PASSWORD);
        mysql_select_db($DATABASE);
        $safe_id = mysql_real_escape_string($_GET['id']);
        $safe_ip = mysql_real_escape_string($_SERVER['REMOTE_ADDR']);

        if( $USE_IPLOG )
        {
            $result = mysql_query("SELECT COUNT(*) FROM `tx_addresses` WHERE `gallery_id`='$safe_id' AND `ip_address`='$safe_ip'");
            $row = mysql_fetch_row($result);

            if( $row[0] > 0 )
            {
                $ip_logged = TRUE;
            }
        }

        if( !$ip_logged )
        {
            mysql_query("UPDATE `tx_galleries` SET `clicks`=`clicks`+1 WHERE `gallery_id`='$safe_id'");

            if( $USE_IPLOG )
            {
                mysql_query("INSERT INTO `tx_addresses` VALUES ('$safe_id', '$safe_ip', " . time() . ")");
            }
        }

        if( $USE_COOKIES )
        {
            setcookie('tgpx_click', $value, time()+$EXPIRE, '/');
        }

        mysql_close();
    }
}


header("Location: $TEMPLATE");

?>