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


class Mailer
{
    var $to;
    var $from;
    var $from_name;
    var $subject;
    var $text_body;
    var $html_body;
    var $boundary;
    var $headers = array();
    var $type;
    var $mailer = MT_PHP;
    var $host;
    var $sendmail;

    function Send()
    {
        $this->SetMessageType();
        $body = $this->GenerateBody();
        $headers = $this->GenerateHeaders();

        switch($this->mailer)
        {
            case MT_PHP:
                $result = @mail($this->to, $this->subject, $body, $headers);
                break;

            case MT_SMTP:
                $result = $this->SendSmtp($body, $headers);
                break;

            case MT_SENDMAIL:
                $result = $this->SendSendmail($body, $headers);
                break;
        }

        return $result;
    }

    function SendSendmail(&$body, &$headers)
    {
        if( ($pipe = popen("{$this->sendmail} -t", 'w')) == FALSE )
        {
            return FALSE;
        }

        fputs($pipe, $headers);
        fputs($pipe, "\r\n\r\n");
        fputs($pipe, $body);

        $result = pclose($pipe);

        return TRUE;
    }

    function SendSmtp(&$body, &$headers)
    {
        $crlf = "\r\n";

        if( ($socket = @fsockopen($this->host, 25, $errno, $errstr, 10)) !== FALSE )
        {
            stream_set_timeout($socket, 30);
            $this->SmtpGetLines($socket);
            fwrite($socket, "HELO localhost$crlf");
            $this->SmtpGetLines($socket);
            fwrite($socket, "RSET$crlf");
            $this->SmtpGetLines($socket);
            fwrite($socket, "MAIL FROM: <{$this->from}>$crlf");
            $this->SmtpGetLines($socket);
            fwrite($socket, "RCPT TO: <{$this->to}>$crlf");
            $this->SmtpGetLines($socket);
            fwrite($socket, "DATA$crlf");
            $this->SmtpGetLines($socket);
            fwrite($socket, "$headers$crlf$crlf$body$crlf.$crlf");
            $this->SmtpGetLines($socket);
            fwrite($socket, "QUIT$crlf");
            $this->SmtpGetLines($socket);
            fclose($socket);
        }
        else
        {
            return FALSE;
        }
    }

    function SmtpGetLines(&$socket)
    {
        $data = "";
        while($str = fgets($socket,515))
        {
            $data .= $str;
            if(substr($str,3,1) == " ") { break; }
        }
        return $data;
    }

    function GenerateBody()
    {
        $crlf = "\r\n";
        $lf = "\n";
        $content_type = 'text/plain';
        $this->boundary = md5(uniqid(rand(), TRUE));

        switch($this->type)
        {
            case 'alt':
            {
                $this->headers[] = "Mime-Version: 1.0";
                $this->headers[] = "Content-Type: multipart/alternative; boundary=\"{$this->boundary}\"";
                $this->headers[] = "Content-Transfer-Encoding: 7bit";

                $body = "--{$this->boundary}$lf" .
                        "Content-Type: text/plain; charset=\"iso-8859-1\"$lf" .
                        "Content-Transfer-Encoding: 7bit$lf$lf" .
                        trim($this->text_body) . "$lf$lf" .
                        "--{$this->boundary}$lf" .
                        "Content-Type: text/html; charset=\"iso-8859-1\"$lf" .
                        "Content-Transfer-Encoding: 7bit$lf$lf" .
                        trim($this->html_body) . "$lf$lf" .
                        "--{$this->boundary}--$lf$lf";

                return $body;

            }
            break;

            case 'plain':
            {
                return $this->text_body;
            }
            break;
        }
    }

    function GenerateHeaders()
    {
        $newline = "\n";

        switch($this->mailer)
        {
            case MT_SMTP:
                $newline = "\r\n";
            case MT_SENDMAIL:
            {
                array_unshift($this->headers, "Subject: {$this->subject}");
                array_unshift($this->headers, "To: {$this->to}");
            }
            break;
        }

        if( !IsEmptyString($this->from_name) )
        {
            $this->from_name = str_replace('"', '', $this->from_name);
            array_unshift($this->headers, "From: \"{$this->from_name}\" <{$this->from}>");
        }
        else
        {
            array_unshift($this->headers, "From: {$this->from}");
        }

        return join($newline, $this->headers);
    }

    function SetMessageType()
    {
        if( !IsEmptyString($this->html_body) )
        {
            $this->type = 'alt';
        }
        else
        {
            $this->type = 'plain';
        }
    }
}

?>