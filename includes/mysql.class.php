<?PHP
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

class DB
{
    var $handle;
    var $hostname;
    var $username;
    var $password;
    var $connected;
    var $database;

    function DB($hostname, $username, $password, $database)
    {
        $this->handle = 0;
        $this->connected = FALSE;
        $this->hostname = $hostname;
        $this->password = $password;
        $this->username = $username;
        $this->database = $database;
    }

    function Connect()
    {
        if( !$this->connected )
        {
            $this->handle = mysql_connect($this->hostname, $this->username, $this->password, TRUE);
            $this->SelectDB($this->database);
            $this->connected = TRUE;
        }
    }

    function IsConnected()
    {
        return $this->connected;
    }

    function Disconnect()
    {
        if( $this->connected )
        {
            mysql_close($this->handle);
            $this->handle    = 0;
            $this->connected = FALSE;
        }
    }

    function SelectDB($database)
    {
        $this->database = $database;

        if( !mysql_select_db($this->database, $this->handle) )
        {
            trigger_error(mysql_error($this->handle), E_USER_ERROR);
        }
    }

    function Row($query, $binds = array())
    {
        $result = mysql_query($this->Prepare($query, $binds), $this->handle);

        if( !$result )
        {
            trigger_error(mysql_error($this->handle) . "<br />$query", E_USER_ERROR);
        }

        $row = mysql_fetch_assoc($result);

        mysql_free_result($result);

        return $row;
    }

    function Count($query, $binds = array())
    {
        $query = $this->Prepare($query, $binds);
        $result = mysql_query($query, $this->handle);

        if( !$result )
        {
            trigger_error(mysql_error($this->handle) . "<br />$query", E_USER_ERROR);
        }

        $row = mysql_fetch_row($result);

        mysql_free_result($result);

        return $row[0];
    }

    function Query($query, $binds = array())
    {
        $query = $this->Prepare($query, $binds);
        $result = mysql_query($query, $this->handle);

        if( !$result )
        {
            trigger_error(mysql_error($this->handle) . "<br />$query", E_USER_ERROR);
        }

        return $result;
    }

    function QueryWithPagination($query, $binds = array(), $page = 1, $per_page = 10, $nolimit = FALSE)
    {
        global $C;

        $result = array();

        // Get total number of results
        $count_query = preg_replace('~SELECT .* FROM~', 'SELECT COUNT(*) FROM', $query);
        $result['total'] = $this->Count($count_query, $binds);

        // Calculate pagination
        $result['pages'] = ceil($result['total']/$per_page);
        $result['page'] = min(max($page, 1), $result['pages']);
        $result['limit'] = max(($result['page'] - 1) * $per_page, 0);
        $result['start'] = max(($result['page'] - 1) * $per_page + 1, 0);
        $result['end'] = min($result['start'] - 1 + $per_page, $result['total']);
        $result['prev'] = ($result['page'] > 1);
        $result['next'] = ($result['end'] < $result['total']);

        if( $result['next'] )
            $result['next_page'] = $result['page'] + 1;

        if( $result['prev'] )
            $result['prev_page'] = $result['page'] - 1;

        if( $result['total'] > 0 )
            $result['result'] = $this->Query($query . ($nolimit ? '' : " LIMIT {$result['limit']},{$per_page}"), $binds);
        else
            $result['result'] = FALSE;

        // Format
        $result['fpages'] = number_format($result['pages'], 0, $C['dec_point'], $C['thousands_sep']);
        $result['start'] = number_format($result['start'], 0, $C['dec_point'], $C['thousands_sep']);
        $result['end'] = number_format($result['end'], 0, $C['dec_point'], $C['thousands_sep']);
        $result['ftotal'] = number_format($result['total'], 0, $C['dec_point'], $C['thousands_sep']);

        return $result;
    }

    function &FetchAll($query, $binds = array(), $key = null)
    {
        $all = array();
        $query = $this->Prepare($query, $binds);
        $result = mysql_query($query, $this->handle);

        if( !$result )
        {
            trigger_error(mysql_error($this->handle) . "<br />$query", E_USER_ERROR);
        }

        while( $row = mysql_fetch_assoc($result) )
        {
            if( $key )
            {
                $all[$row[$key]] = $row;
            }
            else
            {
                $all[] = $row;
            }
        }

        return $all;
    }

    function Update($query, $binds = array())
    {
        $query = $this->Prepare($query, $binds);
        $result = mysql_query($query, $this->handle);

        if( !$result )
        {
            trigger_error(mysql_error($this->handle) . "<br />$query", E_USER_ERROR);
        }

        return mysql_affected_rows($this->handle);
    }

    function NextRow($result)
    {
        return mysql_fetch_assoc($result);
    }

    function Free($result)
    {
        mysql_free_result($result);
    }

    function InsertID()
    {
        return mysql_insert_id($this->handle);
    }

    function NumRows($result)
    {
        return mysql_num_rows($result);
    }

    function FetchArray($result)
    {
        return mysql_fetch_array($result);
    }

    function Seek($result, $where)
    {
        mysql_data_seek($result, $where);
    }

    function BindList($count)
    {
        $list = "''";

        if( $count > 0 )
        {
            $list = join(',', array_fill(0, $count, '?'));
        }

        return $list;
    }

    function Prepare($query, &$binds)
    {
        $query_result = '';
        $index = 0;

        $pieces = preg_split('/(\?|#)/', $query, -1, PREG_SPLIT_DELIM_CAPTURE);
        foreach( $pieces as $piece )
        {
            if( $piece == '?' )
            {
                if( $binds[$index] === NULL )
                    $query_result .= 'NULL';
                else if( is_numeric($binds[$index]) )
                    $query_result .= $binds[$index];
                else
                    $query_result .= "'" . mysql_real_escape_string($binds[$index], $this->handle) . "'";

                $index++;
            }
            else if( $piece == '#' )
            {
                $binds[$index] = str_replace('`', '\`', $binds[$index]);
                $query_result .= "`" . $binds[$index] . "`";
                $index++;
            }
            else
            {
                $query_result .= $piece;
            }
        }

        return $query_result;
    }

    function GetTables()
    {
        $tables = array();
        $result = $this->Query('SHOW TABLES');
        $field = mysql_field_name($result, 0);

        while( $row = $this->NextRow($result) )
        {
            $tables[$row[$field]] = $row[$field];
        }

        $this->Free($result);

        return $tables;
    }

    function GetColumns($table, $as_hash = FALSE)
    {
        $columns = array();
        $result = $this->Query('DESCRIBE #', array($table));
        $field = mysql_field_name($result, 0);

        while( $column = $this->NextRow($result) )
        {
            if( $as_hash )
            {
                $columns[$column[$field]] = $column[$field];
            }
            else
            {
                $columns[] = $column[$field];
            }
        }

        $this->Free($result);

        return $columns;
    }
}

?>