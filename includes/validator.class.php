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

define('V_NONE', 0);
define('V_EMAIL', 1);
define('V_URL', 2);
define('V_EMPTY', 3);
define('V_LENGTH', 4);
define('V_EQUALS', 5);
define('V_GREATER', 6);
define('V_LESS', 7);
define('V_ZERO', 8);
define('V_REGEX', 9);
define('V_ALPHANUM', 10);
define('V_NUMERIC', 11);
define('V_TRUE', 12);
define('V_FALSE', 13);
define('V_BETWEEN', 14);
define('V_URL_WORKING_300', 15);
define('V_URL_WORKING_400', 16);
define('V_NOT_FALSE', 17);
define('V_DATETIME', 18);
define('V_LESS_EQ', 19);
define('V_GREATER_EQ', 20);
define('V_CONTAINS', 21);


class Validator
{
    var $errors;
    var $validations;

    function Validator()
    {
        $this->errors = array();
        $this->validations = array();
    }

    function Validate()
    {
        foreach( $this->validations as $validation )
        {
            $result = null;
            $fields = (is_array($validation['field']) ? $validation['field'] : array($validation['field']));

            foreach($fields as $field)
            {
                switch( $validation['type'] )
                {
                case V_EMAIL:
                    $result = $this->to_bool(preg_match('/^[\w\d][\w\d\,\.\-]*\@([\w\d\-]+\.)+([a-zA-Z]+)$/i', $field));
                    break;

                case V_URL:
                    $result = $this->to_bool(preg_match('!^http(s)?://[\w-]+\.[\w-]+(\S+)?$!i', $field));
                    break;

                case V_EMPTY:
                    $result = !$this->to_bool(preg_match('/^\s*$/i', $field));
                    break;

                case V_LENGTH:
                    $length = strlen($field);
                    if( !is_array($validation['extras']) )
                    {
                        $extras = $validation['extras'];
                        $validation['extras'] = array();
                        list($validation['extras']['min'], $validation['extras']['max']) = explode(',', $extras);
                    }

                    $result = ($length >= $validation['extras']['min'] && $length <= $validation['extras']['max']);
                    break;

                case V_EQUALS:
                    $result = ($field == $validation['extras']);
                    break;

                case V_GREATER:
                    $result = ($field > $validation['extras']);
                    break;

                case V_LESS:
                    $result = ($field < $validation['extras']);
                    break;

                case V_GREATER_EQ:
                    $result = ($field >= $validation['extras']);
                    break;

                case V_LESS_EQ:
                    $result = ($field <= $validation['extras']);
                    break;

                case V_ZERO:
                    $result = ($field == 0);
                    break;

                case V_REGEX:
                    $result = $this->to_bool(preg_match($validation['extras'], $field));
                    break;

                case V_CONTAINS:
                    $result = (stristr($field, $validation['extras']) === FALSE);
                    break;

                case V_NUMERIC:
                    $result = is_numeric($field);
                    break;

                case V_ALPHANUM:
                    $result = $this->to_bool(preg_match('/^[a-z0-9]+$/i', $field));
                    break;

                case V_TRUE:
                    $result = ($field === TRUE);
                    break;

                case V_FALSE:
                    $result = ($field === FALSE);
                    break;

                case V_NOT_FALSE:
                    $result = ($field !== FALSE);
                    break;

                case V_DATETIME:
                    // Value cannot be empty
                    if( $validation['extras'] !== TRUE && $field == '' )
                    {
                        $result = TRUE;
                    }
                    else
                    {
                        $result = $this->to_bool(preg_match(RE_DATETIME, $field));
                    }
                    break;

                case V_BETWEEN:
                    if( !is_array($validation['extras']) )
                    {
                        $extras = $validation['extras'];
                        $validation['extras'] = array();
                        list($validation['extras']['min'], $validation['extras']['max']) = explode(',', $extras);
                    }

                    $result = ($field >= $validation['extras']['min'] && $field <= $validation['extras']['max']);
                    break;

                case V_URL_WORKING_300:
                case V_URL_WORKING_400:
                    $result = $this->to_bool(preg_match('!^http(s)?://[\w-]+\.[\w-]+(\S+)?$!i', $field));

                    if( $result !== FALSE )
                    {
                        list($result, $error) = $this->TestUrl($field, ($validation['type'] == V_URL_WORKING_300 ? TRUE : FALSE));

                        if( $result === FALSE )
                            $validation['message'] = "{$validation['extras']}: $error";
                    }

                    break;
                }

                if( $result === FALSE )
                {
                    $this->errors[] = $validation['message'];
                }
            }
        }

        if( count($this->errors) > 0 )
        {
            return FALSE;
        }

        $this->Clear();

        return TRUE;
    }

    function Register($field, $type, $message, $extras = null)
    {
        $this->validations[] = array('field' => $field,
                                     'type' => $type,
                                     'message' => $message,
                                     'extras' => $extras);
    }

    function SetError($error)
    {
        $this->errors[] = $error;
    }

    function GetErrors()
    {
        return $this->errors;
    }

    function Clear()
    {
        $this->errors = array();
        $this->validations = array();
    }

    function TestUrl($url, $redirection)
    {
        if( !class_exists('http') )
        {
            require_once("{$GLOBALS['BASE_DIR']}/includes/http.class.php");
        }

        $http = new Http();
        $result = $http->Get($url, $redirection);

        return array($result, $http->errstr);
    }

    function ValidationError($function, $as_arg = FALSE)
    {
        if( $as_arg )
        {
            call_user_func($function, $this->errors);
        }
        else
        {
            $GLOBALS['errstr'] = join('<br />', $this->errors);
            call_user_func($function);
        }

        return false;
    }

    function to_bool($value)
    {
        if( is_numeric($value) )
        {
            if( $value == 0 )
            {
                return FALSE;
            }
            else
            {
                return TRUE;
            }
        }

        return FALSE;
    }
}

?>