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

define('HAVE_CURL', extension_loaded('curl'));
define('HAVE_ZLIB', extension_loaded('zlib'));


$GLOBALS['http_agents'] = array('Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.1.4322)',
                                'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.0.6) Gecko/20060728 Firefox/1.5.0.6',
                                'Opera/9.00 (Windows NT 5.1; U; en)');

$GLOBALS['http_referers'] = array('http://www.google.com/search?hl=en&q=art&btnG=Google+Search');


// curl errors
if( !HAVE_CURL )
{
    define('CURLE_UNSUPPORTED_PROTOCOL', 1);
    define('CURLE_URL_MALFORMAT', 3);
    define('CURLE_COULDNT_RESOLVE_PROXY', 5);
    define('CURLE_COULDNT_RESOLVE_HOST', 6);
    define('CURLE_COULDNT_CONNECT', 7);
    define('CURLE_HTTP_RETURNED_ERROR', 22);
    define('CURLE_OPERATION_TIMEOUTED', 28);
    define('CURLE_SSL_CONNECT_ERROR', 35);
    define('CURLE_TOO_MANY_REDIRECTS', 47);
    define('CURLE_GOT_NOTHING', 52);
    define('CURLE_SEND_ERROR', 55);
    define('CURLE_RECV_ERROR', 56);
}

$GLOBALS['HTTP_ERROR'] = array();
$GLOBALS['HTTP_ERROR'][CURLE_UNSUPPORTED_PROTOCOL] = $GLOBALS['L']['CURLE_UNSUPPORTED_PROTOCOL'];
$GLOBALS['HTTP_ERROR'][CURLE_URL_MALFORMAT] = $GLOBALS['L']['CURLE_URL_MALFORMAT'];
$GLOBALS['HTTP_ERROR'][CURLE_COULDNT_RESOLVE_PROXY] = $GLOBALS['L']['CURLE_COULDNT_RESOLVE_PROXY'];
$GLOBALS['HTTP_ERROR'][CURLE_COULDNT_RESOLVE_HOST] = $GLOBALS['L']['CURLE_COULDNT_RESOLVE_HOST'];
$GLOBALS['HTTP_ERROR'][CURLE_COULDNT_CONNECT] = $GLOBALS['L']['CURLE_COULDNT_CONNECT'];
$GLOBALS['HTTP_ERROR'][CURLE_HTTP_RETURNED_ERROR] = $GLOBALS['L']['CURLE_HTTP_RETURNED_ERROR'];
$GLOBALS['HTTP_ERROR'][CURLE_OPERATION_TIMEOUTED] = $GLOBALS['L']['CURLE_OPERATION_TIMEOUTED'];
$GLOBALS['HTTP_ERROR'][CURLE_SSL_CONNECT_ERROR] = $GLOBALS['L']['CURLE_SSL_CONNECT_ERROR'];
$GLOBALS['HTTP_ERROR'][CURLE_TOO_MANY_REDIRECTS] = $GLOBALS['L']['CURLE_TOO_MANY_REDIRECTS'];
$GLOBALS['HTTP_ERROR'][CURLE_GOT_NOTHING] = $GLOBALS['L']['CURLE_GOT_NOTHING'];
$GLOBALS['HTTP_ERROR'][CURLE_SEND_ERROR] = $GLOBALS['L']['CURLE_SEND_ERROR'];
$GLOBALS['HTTP_ERROR'][CURLE_RECV_ERROR] = $GLOBALS['L']['CURLE_RECV_ERROR'];

class Http
{
    var $request_headers = array();
    var $response_headers = array();
    var $request_info = array();
    var $proxy;
    var $proxy_username;
    var $proxy_password;
    var $raw_response_headers;
    var $body;
    var $username;
    var $password;
    var $errstr;
    var $connect_timeout = 15;
    var $read_timeout = 30;
    var $redirects = 0;
    var $max_redirects = 5;
    var $end_url;
    var $head_request = FALSE;
    var $ip_addresses = array();

    function Http()
    {
    }

    function Cleanup()
    {
        unset($this->request_headers);
        unset($this->response_headers);
        unset($this->request_info);
        unset($this->proxy);
        unset($this->proxy_username);
        unset($this->proxy_password);
        unset($this->raw_response_headers);
        unset($this->body);
        unset($this->username);
        unset($this->password);
        unset($this->errstr);
        unset($this->connect_timeout);
        unset($this->read_timeout);
        unset($this->redirects);
        unset($this->max_redirects);
        unset($this->end_url);
        unset($this->head_request);
        unset($this->ip_addresses);
    }

    function Head($url, $redirection = FALSE, $referrer = null)
    {
        $this->head_request = TRUE;
        return $this->Get($url, $redirection, $referrer);
    }

    function Get($url, $redirection = FALSE, $referrer = null)
    {
        global $http_agents, $http_referers;

        // Prepare for new request
        unset($this->request_headers);
        unset($this->response_headers);
        unset($this->body);
        unset($this->errstr);
        unset($this->end_url);
        unset($this->raw_response_headers);
        $this->redirects = 0;

        // Replace spaces in URL
        $url = str_replace(' ', '%20', $url);

        // Setup request data
        $this->request_headers['User-Agent'] = $http_agents[array_rand($http_agents)];
        $this->request_headers['Accept'] = 'image/gif, image/x-xbitmap, image/jpeg, image/pjpeg, application/x-shockwave-flash, */*';
        $this->request_headers['Accept-Language'] = 'en-us';

        if( isset($referrer) )
            $this->request_headers['Referer'] = $referrer;
        else if( count($http_referers) > 0 )
            $this->request_headers['Referer'] = $http_referers[array_rand($http_referers)];

        if( HAVE_ZLIB && !$this->head_request )
            $this->request_headers['Accept-Encoding'] = 'gzip, deflate';


        // Make request, based on available extensions
        if( HAVE_CURL )
            $result = $this->GetCurl($url, $redirection);
        else
            $result = $this->GetStandard($url, $redirection);

        // Handle compressed pages
        if( HAVE_ZLIB )
        {
            if( $this->response_headers['content-encoding'] == 'gzip' && substr($this->body, 0, 8) == "\x1f\x8b\x08\x00\x00\x00\x00\x00" )
                $this->body = gzinflate(substr($this->body, 10));
            else if( $this->response_headers['content-encoding'] == 'deflate' )
                $this->body = gzinflate($this->body);
        }

        if( !$this->head_request && !isset($this->response_headers['content-length']) )
            $this->response_headers['content-length'] = strlen($this->body);

        return $result;
    }

    function GetCurl($url, $redirection)
    {
        global $HTTP_ERROR;

        $result = TRUE;

        $ch = curl_init();

        // Configure curl options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connect_timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->read_timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER , TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->CurlPrepareHeaders());
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, array(&$this, 'ReadResponseHeader'));


        // Allow user to specify the outgoing IP addresses available on the server
        if( count($this->ip_addresses) )
        {
            curl_setopt($ch, CURLOPT_INTERFACE, $this->ip_addresses[array_rand($this->ip_addresses)]);
        }


        // Doing a HEAD request
        if( $this->head_request )
        {
            curl_setopt($ch, CURLOPT_NOBODY, TRUE);
        }

        // Handle basic HTTP authorization
        if( $this->username )
        {
            curl_setopt($ch, CURLOPT_USERPWD, $this->username . ':' . $this->password);
        }

        // Handle HTTP connection through a proxy
        if( $this->proxy )
        {
            curl_setopt($ch, CURLOPT_PROXY, $this->proxy);

            if( $this->proxy_username )
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, $this->proxy_username . ':' . $this->proxy_password);
        }

        // Execute the curl request
        $this->body = curl_exec($ch);

        // Record error, if any
        $errno = curl_errno($ch);
        $error = curl_error($ch);

        // Record request information
        $this->request_info = curl_getinfo($ch);
        $this->end_url = $this->request_info['url'];
        $this->request_info['speed_download'] = sprintf('%.2f', $this->request_info['speed_download'] / 1024);

        curl_close($ch);

        // Handle errors
        if( $errno != CURLE_OK )
        {
            $this->errstr = ($HTTP_ERROR[$errno] ? sprintf($HTTP_ERROR[$errno], $error) : $error);
            $result = FALSE;
        }
        else
        {
            if( $redirection && $this->response_headers['location'] )
            {
                // Get the new URL to access
                $new_url = RelativeToAbsolute($url, $this->response_headers['location']);

                // Clear the previous response headers
                unset($this->response_headers);
                unset($this->raw_response_headers);

                $this->redirects++;
                $this->end_url = $new_url;

                if( $this->redirects > $this->max_redirects )
                {
                    $this->errstr = $HTTP_ERROR[CURLE_TOO_MANY_REDIRECTS];
                    return FALSE;
                }

                return $this->GetCurl($new_url, $redirection);
            }
            else if( $this->response_headers['status_code'] >= 300 )
            {
                $result = FALSE;
                $this->errstr = sprintf($HTTP_ERROR[CURLE_HTTP_RETURNED_ERROR],  $this->response_headers['status']);
            }
        }

        return $result;
    }

    function ReadResponseHeader($ch, $header)
    {
        $this->raw_response_headers .= $header;

        $working = trim($header);

        if( preg_match('~HTTP/\d\.\d ((\d+).*)~', $working, $matches) )
        {
            $this->response_headers['full_status'] = $matches[0];
            $this->response_headers['status'] = $matches[1];
            $this->response_headers['status_code'] = $matches[2];
        }

        else if( preg_match('/^([^:]+):\s*(.*)$/i', $working, $matches) )
        {
            $this->response_headers[strtolower($matches[1])] = $matches[2];
        }

        return strlen($header);
    }

    function CurlPrepareHeaders()
    {
        $headers = array();

        foreach( $this->request_headers as $name => $value )
        {
            $headers[] = "$name: $value";
        }

        return $headers;
    }

    function GetStandard($url, $redirection)
    {
        global $HTTP_ERROR;

        if( $this->username )
            $this->request_headers['Authorization'] = "Basic " . base64_encode($this->username . ":" . $this->password);

        if( ($parsed_url = parse_url($url)) !== FALSE )
        {
            $this->end_url = $url;
            $this->body = '';
            $this->request_headers['Host'] = $parsed_url['host'];

            $ipaddr = gethostbyname($parsed_url['host']);

            if( $ipaddr == $parsed_url['host'] )
            {
                $this->errstr = $HTTP_ERROR[CURLE_COULDNT_RESOLVE_HOST];
                return FALSE;
            }

            if( empty($parsed_url['port']) )
            {
                $parsed_url['port'] = 80;
            }

            if( ($socket = @fsockopen($ipaddr, $parsed_url['port'], $errno, $errstr, $this->connect_timeout)) !== FALSE )
            {
                stream_set_timeout($socket, $this->read_timeout);
                fputs($socket, $this->PrepareHeaders($parsed_url));

                $headers_done = FALSE;
                $start_time = $this->microtime_float();

                while( $line = fgets($socket, 8192) )
                {
                    if( $line === FALSE )
                    {
                        $this->errstr = $HTTP_ERROR[CURLE_RECV_ERROR];
                        fclose($socket);
                        return FALSE;
                    }

                    if( !$headers_done && preg_match('|^\s+$|', $line) )
                    {
                        $headers_done = TRUE;
                        continue;
                    }

                    if( $headers_done )
                        $this->body .= $line;
                    else
                        $this->ReadResponseHeader(null, $line);
                }

                $end_time = $this->microtime_float();
                fclose($socket);

                $this->request_info['size_download'] = strlen($this->body);
                $this->request_info['speed_download'] = sprintf('%.2f', $this->request_info['size_download'] / ($end_time - $start_time) / 1024);

                if( $redirection && $this->response_headers['location'] )
                {
                    // Get the new URL to access
                    $new_url = RelativeToAbsolute($url, $this->response_headers['location']);

                    // Clear the previous response headers
                    unset($this->response_headers);
                    unset($this->raw_response_headers);

                    $this->redirects++;
                    $this->end_url = $new_url;

                    if( $this->redirects > $this->max_redirects )
                    {
                        $this->errstr = $HTTP_ERROR[CURLE_TOO_MANY_REDIRECTS];
                        return FALSE;
                    }

                    return $this->GetStandard($new_url, $redirection);
                }
                else if( $this->response_headers['status_code'] >= 300 )
                {
                    $this->errstr = sprintf($HTTP_ERROR[CURLE_HTTP_RETURNED_ERROR], $this->response_headers['status']);
                    return FALSE;
                }

                return TRUE;
            }
            else
            {
                $this->errstr = sprintf($HTTP_ERROR[CURLE_COULDNT_CONNECT], $errstr);
                return FALSE;
            }
        }
        else
        {
            $this->errstr = $HTTP_ERROR[CURLE_URL_MALFORMAT];
            return FALSE;
        }
    }

    function PrepareHeaders($parsed_url)
    {
        $crlf = "\r\n";
        $get_path = $parsed_url['path'] . ($parsed_url['query'] ? "?{$parsed_url['query']}" : '');
        $headers = ($this->head_request ? "HEAD" : "GET") . " $get_path HTTP/1.0$crlf";

        foreach( $this->request_headers as $name => $value )
        {
            $headers .= "$name: $value$crlf";
        }

        return "$headers$crlf";
    }

    function SetAuth($username, $password)
    {
        $this->username = $username;
        $this->password = $password;
    }

    function SetProxy($proxy, $username = null, $password = null)
    {
        $this->proxy = $proxy;
        $this->proxy_username = $username;
        $this->proxy_password = $password;
    }

    function microtime_float()
    {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }
}


?>