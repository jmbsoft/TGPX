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


class Template
{

    var $template_dir = '';
    var $compile_dir = '';
    var $cache_dir = '';
    var $force_compile = FALSE;
    var $caching =  FALSE;
    var $cache_lifetime =  3600;
    var $cache_modified_check = FALSE;
    var $compiler_file = 'compiler.class.php';
    var $nocache;
    var $codecache;

    var $vars = array();

    function Template()
    {
        $this->vars['CAPTURES'] = array();
        $this->nocache = array();
        $this->codecache = array();
        $this->vars['t_timestamp'] = time();
        $this->template_dir = realpath(dirname(__FILE__) . '/../templates');
        $this->compile_dir = $this->template_dir . '/compiled';
        $this->cache_dir = $this->template_dir . '/cache';
    }


    function cleanup()
    {
        foreach( $this->vars as $key => $value )
        {
            unset($this->vars[$key]);
        }
        
        unset($this->vars);
        unset($this->nocache);
        unset($this->codecache);
        unset($this->template_dir);
        unset($this->compile_dir);
        unset($this->cache_dir);
    }

    function assign($variable, $value = NULL)
    {
        if( is_array($variable) )
        {
            foreach( $variable as $key => $val )
                if( $key != '' )
                    $this->vars[$key] = $val;
        }
        else
        {
            if( $variable != '' )
                $this->vars[$variable] = $value;
        }
    }

    function assign_by_ref($variable, &$value)
    {
        if( $variable != '' )
            $this->vars[$variable] = &$value;
    }

    function clear_assign($variable)
    {
        if( is_array($variable) )
            foreach ($variable as $var)
                unset($this->vars[$var]);
        else
            unset($this->vars[$variable]);
    }

    function clear_all_assign()
    {
        $this->vars = array();
    }

    function clear_cache($template = NULL, $cache_id = '')
    {       
        if( $template )
        {
            $template = $this->cache_dir . "/$cache_id#$template";            
            if( file_exists($template) )
                unlink($template);
        }
        else
        {
            $files = glob($this->cache_dir . '/*.*');            
            if( $files !== FALSE )
                foreach( $files as $filename )
                    unlink($filename);
        }
    }

    function is_cached($template, $cache_id = '')
    {
        if( !$this->caching )
            return FALSE;

        $template_compiled = $this->compile_dir . "/$template";
        $template_cache = $this->cache_dir . "/$cache_id#$template";
        
        if( file_exists($template_cache) )
            $cache_age = filemtime($template_cache);
        
        if( file_exists($template_compiled) )
            $compiled_age = filemtime($template_compiled);

        if( isset($compiled_age) && isset($cache_age) && $compiled_age >= $cache_age )
        {
            return FALSE;
        }
        else if( isset($cache_age) && $cache_age >= (time() - $this->cache_lifetime) )
        {
            return TRUE;
        }
        else
        {
            return FALSE;
        }
    }

    function clear_compiled_tpl($template = NULL)
    {
        $template = $this->compile_dir . "/$template";

        if( $template )
            if( file_exists($template) )
                unlink($template);
        else
            foreach( glob($this->compile_dir . '/*.*') as $filename )
                unlink($filename);
    }

    function display($template, $cache_id = '')
    {
        // Display cached version if available
        if( $this->caching && $this->is_cached($template, $cache_id) )
        {
            include_once($this->cache_dir . "/$cache_id#$template");
            return;
        }

        // Compile if necessary
        if( !$this->is_compiled($template) )
            $this->compile_template($template);

            
        ob_start();
        include($this->compile_dir . "/$template");
        $contents = ob_get_contents();
        ob_end_clean();
        
        $output = $contents;
        
        if( count($this->nocache) )
        {
            foreach( $this->nocache as $token => $code )
            {
                $output = str_replace($token, $code, $output);
                
                if( $this->caching )
                {
                    $contents = str_replace($token, base64_decode($this->codecache[$token]), $contents);
                }
            }
        }
            
        // Write cache file
        if( $this->caching )
        {
            $this->write_template($this->cache_dir . "/$cache_id#$template", $contents);
        }
        
        echo $output;
    }

    function parse($template)
    {
        if( !class_exists('compiler') )
            require_once(dirname(__FILE__) . '/' . $this->compiler_file);

        $compiled_code = '';
        $compiler = new Compiler();
        $compiler->compile($template, $compiled_code);
                
        ob_start();
        eval('?>' . $compiled_code);
        $contents = ob_get_contents();
        ob_end_clean();

        return $contents;
    }

    function parse_compiled($template)
    {
        ob_start();
        eval("?>" . $template);       
        return ob_get_clean();
    }

    function is_compiled($template)
    {
        $compiled = $this->compile_dir . "/$template";
        $template = $this->template_dir . "/$template";

        if( $this->force_compile )
            return FALSE;

        if( !file_exists($compiled) || filemtime($template) > filemtime($compiled) )
            return FALSE;
        else
            return TRUE;
    }

    function compile_template($template)
    {
        if( !class_exists('compiler') )
            require_once(dirname(__FILE__) . '/' . $this->compiler_file);

        $compiled_code = '';

        $compiler = new Compiler();

        if( $compiler->compile_file($this->template_dir . "/$template", $compiled_code) )
        {
            $this->write_template($this->compile_dir . "/$template", $compiled_code);
        }
        else
        {
            trigger_error($compiler->get_error_string(), E_USER_ERROR);
        }
    }

    function write_template($template, &$code)
    {
        $fd = fopen($template, 'w');
        if( $fd  )
        {
            flock($fd, LOCK_EX);
            fwrite($fd, $code);
            flock($fd, LOCK_UN);
            fclose($fd);
        }
    }
}


function tsearchterm($string)
{
    if( strpos($string, ' ') )
        $string = '"' . $string . '"';
        
    return urlencode($string);
}

function treplace_special($string, $replacement)
{
    return preg_replace('~[^a-z0-9]+~i', $replacement, $string);
}


function tnumber_format($number)
{
    global $C;
    
    return number_format($number, 0, $C['dec_point'], $C['thousands_sep']);
}

function tsprintf()
{
    $args = func_get_args();
    
    $format = $args[1];
    $args[1] = $args[0];
    $args[0] = $format;
    
    return call_user_func_array('sprintf', $args);
}

function tdate($timestamp, $format = null)
{
    global $C;
    
    if( $format == null )
        $format = $C['date_format'];
        
    if( $timestamp == null )
        $timestamp = time();
    else if( preg_match(RE_DATETIME, $timestamp) )
        $timestamp = strtotime($timestamp);
        
    return date($format, $timestamp);
}

function tdatetime($timestamp, $date_format = null, $time_format = null)
{
    global $C;
    
    if( $date_format == null )
        $date_format = $C['date_format'];
        
    if( $time_format == null )
        $time_format = $C['time_format'];
        
    if( $timestamp == null )
        $timestamp = time();
    else if( preg_match(RE_DATETIME, $timestamp) )
        $timestamp = strtotime($timestamp);
        
    return date("$date_format $time_format", $timestamp);
}

?>