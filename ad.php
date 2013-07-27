<?PHP
## http://www.jmbsoft.com/license.php


// These settings must match the database settings used for TGPX

$USERNAME = 'username';          // The username to access your MySQL database
$PASSWORD = 'password';          // The password to access your MySQL database
$DATABASE = 'database';          // The name of your MySQL database
$HOSTNAME = 'localhost';         // The hostname of your MySQL database server


###########################################################################################################
##              DONE EDITING THIS FILE.  YOU DO NOT NEED TO EDIT THIS FILE ANY FURTHER                   ##
###########################################################################################################

if( $_GET['id'] )
{
    $ids = array();
    $raw_click = FALSE;
    
    if( isset($_COOKIE['tgpx_ad_click']) )
    {
        $ids = unserialize($_COOKIE['tgpx_ad_click']);

        if( isset($ids[$_GET['id']]) )
        {
            $raw_click = TRUE;
        }
    }
    
    $ids[$_GET['id']] = 1;
    
    mysql_connect($HOSTNAME, $USERNAME, $PASSWORD);
    mysql_select_db($DATABASE);
    $safe_id = mysql_real_escape_string($_GET['id']);
    $safe_ip = mysql_real_escape_string(sprintf('%u', ip2long($_SERVER['REMOTE_ADDR'])));
    $now = time();

    mysql_query("UPDATE `tx_iplog_ads` SET `raw_clicks`=`raw_clicks`+1,`last_click`=$now WHERE `ad_id`='$safe_id' AND `ip_address`='$safe_ip'");
    if( mysql_affected_rows() == 0 )
    {
        mysql_query("INSERT INTO `tx_iplog_ads` VALUES ('$safe_id', '$safe_ip', 0, $now)");
    }
    else
    {
        $raw_click = TRUE;
    }

    if( $raw_click )
    {
        mysql_query("UPDATE `tx_ads` SET `raw_clicks`=`raw_clicks`+1 WHERE `ad_id`='$safe_id'");
    }
    else
    {
        mysql_query("UPDATE `tx_ads` SET `unique_clicks`=`unique_clicks`+1,`raw_clicks`=`raw_clicks`+1 WHERE `ad_id`='$safe_id'");
    }

    setcookie('tgpx_ad_click', serialize($ids), time()+86400, '/');

    mysql_close();
}


if( $_GET['u'] )
{
    header("Location: {$_GET['u']}");
}
else
{
    echo "ERROR: The u= value was not passed to the script";
}

?>