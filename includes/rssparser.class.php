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

// TODO
// Extract all image URLs for each item

class RSSParser
{
    var $channel_tags = array ('title', 'link', 'description', 'language', 'copyright', 'managingEditor', 'webMaster', 'lastBuildDate', 'rating', 'docs');
    var $item_tags = array('title', 'link', 'description', 'author', 'category', 'comments', 'enclosure', 'guid', 'pubDate', 'source', 'content:encoded');
    var $errstr;

    function RSSParser()
    {
    }

    function Parse(&$rss)
    {
        $parsed = array('items' => array());

        if( preg_match('~<channel.*?>(.*?)(</channel>|$)~msi', $rss, $channel) )
        {
            foreach( $this->channel_tags as $channel_tag )
            {
                if( preg_match("~<$channel_tag.*?>(.*?)</$channel_tag>~si", $channel[1], $tag_match) )
                {
                    $parsed[$channel_tag] = trim($tag_match[1]);
                }
            }

            if( preg_match_all('~<item[^>]*>(.*?)</item>~si', $rss, $items) )
            {
                foreach( $items[1] as $item )
                {
                    $next_item =& $parsed['items'][];
                    foreach( $this->item_tags as $item_tag )
                    {
                        if( preg_match("~<$item_tag.*?>(.*?)</$item_tag>~si", $item, $tag_match) )
                        {
                            $next_item[$item_tag] = trim(str_replace(array('<![CDATA[', ']]>'), '', $tag_match[1]));
                        }

                        else if( preg_match("~<$item_tag(.*?)/>~si", $item, $tag_match) )
                        {
                            $next_item[$item_tag] = $this->_extract_attrs(trim($tag_match[1]));
                        }
                    }
                }
            }
        }
        else
        {
            $this->errstr = 'This does not appear to be a valid RSS feed: the &lt;channel&gt; tag could not be found';
            return FALSE;
        }

        return $parsed;
    }

    function _extract_attrs($attributes)
    {
        $parsed = array();

        if( preg_match_all('~([a-z_ ]+=.*?)(?=(?:\s+[a-z_]+\s*=)|$)~i', $attributes, $matches) )
        {
            foreach( $matches[1] as $match )
            {
                $equals = strpos($match, '=');
                $attr_name = $this->_dequote(trim(substr($match, 0, $equals)));
                $attr_value = $this->_dequote(trim(substr($match, $equals + 1)));

                $parsed[strtolower($attr_name)] = $attr_value;
            }
        }

        return $parsed;
    }

    function _dequote($string)
    {
        if( (substr($string, 0, 1) == "'" || substr($string, 0, 1) == '"') && substr($string, -1) == substr($string, 0, 1) )
        {
            return substr($string, 1, -1);
        }
        else
        {
            return $string;
        }
    }
}

?>