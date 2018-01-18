<?php

/**
 *
 */
namespace Godsgood33\Php_Db;

use Katzgrau\KLogger\Logger;
use Psr\Log\LogLevel;
use Exception;
use mysqli;
require_once 'DBConfig.php';

/**
 * A generic database class
 *
 * @author Ryan Prather
 */
class Database
{

    /**
     * Constant defining a SELECT query
     *
     * @var integer
     */
    const SELECT = 1;

    /**
     * Constant defining a SELECT COUNT query
     *
     * @var integer
     */
    const SELECT_COUNT = 2;

    /**
     * Constant defining a CREATE TABLE query
     *
     * @var integer
     */
    const CREATE_TABLE = 3;

    /**
     * Constant defining DROP query
     *
     * @var integer
     */
    const DROP = 4;

    /**
     * Constant defining DELETE query
     *
     * @var integer
     */
    const DELETE = 5;

    /**
     * Constant defining INSERT query
     *
     * @var integer
     */
    const INSERT = 6;

    /**
     * Constant defining REPLACE query
     *
     * @var integer
     */
    const REPLACE = 7;

    /**
     * Constant defining UPDATE query
     *
     * @var integer
     */
    const UPDATE = 8;

    /**
     * Constant defining EXTENDED INSERT query
     *
     * @var integer
     */
    const EXTENDED_INSERT = 9;

    /**
     * Constant defining EXTENDED REPLACE query
     *
     * @var integer
     */
    const EXTENDED_REPLACE = 10;

    /**
     * Constant defining EXTENDED UPDATE query
     *
     * @var integer
     */
    const EXTENDED_UPDATE = 11;

    /**
     * Constant defining ALTER TABLE query
     *
     * @var integer
     */
    const ALTER_TABLE = 12;

    /**
     * Constant defining a TRUNCATE TABLE query
     *
     * @var integer
     */
    const TRUNCATE = 13;

    /**
     * The mysqli connection
     *
     * @var \mysqli
     */
    public $c;

    /**
     * To store the SQL statement
     *
     * @var string
     */
    public $sql = null;

    /**
     * A string to store the type of query that is being run
     *
     * @var int
     */
    public $query_type = null;

    /**
     * The result of the query
     *
     * @var mixed
     */
    public $result = null;

    /**
     * Log level
     *
     * @var LogLevel
     */
    public $log_level = LogLevel::DEBUG;

    /**
     * Variable to store the logger
     *
     * @var \Katzgrau\KLogger\Logger
     */
    public $logger = null;

    /**
     * Variable to decide if we need to automatically run the queries after generating them
     *
     * @var boolean
     */
    public static $autorun = false;

    /**
     * Constructor
     *
     * @param \mysqli $dbh
     *            [optional]
     *            [by ref]
     *            mysqli object to perform queries.
     */
    public function __construct(&$dbh = null)
    {
        if (! is_null($dbh) && is_a($dbh, "mysqli")) {
            $this->c = $dbh;
        } else {
            $this->c = new mysqli(PHP_DB_SERVER, PHP_DB_USER, PHP_DB_PWD, PHP_DB_SCHEMA);
        }

        if ($this->c->connect_errno) {
            throw new Exception("Could not create database class due to error {$this->c->error}", E_ERROR);
        }

        $this->logger = new Logger("./", $this->log_level, [
            'filename' => 'db.log',
            'dateFormat' => 'Y-m-d H:i:s.u',
            'logFormat' => "[{date}] {level}{level-padding} {message} {context}"
        ]);

        $this->set_var("time_zone", "+00:00");
        $this->set_var("sql_mode", "");
    }

    /**
     * Function to make sure that the database is connected
     *
     * @return boolean
     */
    public function is_connected()
    {
        return $this->c->ping();
    }

    /**
     * Function to set the log level just in case there needs to be a change to the default log level
     *
     * @param LogLevel $level
     */
    public function set_log_level($level)
    {
        $this->log_level = $level;
    }

    /**
     * Function to return the currently selected database schema
     *
     * @return string
     */
    public function get_schema()
    {
        if ($res = $this->c->query("SELECT DATABASE()")) {
            $row = $res->fetch_row();
            return $row[0];
        }
        return null;
    }

    /**
     * Function to set schema
     *
     * @param string $schema
     */
    public function set_schema(string $schema)
    {
        if (! $this->c->select_db($schema)) {
            throw new Exception("Failed to change databases to {$schema}", E_ERROR);
        }
        return true;
    }

    /**
     * Method to set a MYSQL variable
     *
     * @param string $name
     * @param string $val
     * @return boolean
     */
    public function set_var(string $name, string $val)
    {
        if (! $name || ! $val) {
            return false;
        }

        return $this->c->real_query("SET $name = {$this->_escape($val)}");
    }

    /**
     * Function to execute the statement
     *
     * @param mixed $return
     *            [optional]
     *            MYSQLI constant to control what is returned from the mysqli_result object
     * @param string $sql
     *            [optional]
     *            Optional SQL query
     *
     * @return mixed
     */
    public function execute($return = MYSQLI_ASSOC, $class = null, $sql = null)
    {
        if (! is_null($sql)) {
            $this->sql = $sql;
        }

        if (is_a($this->c, 'mysqli')) {
            if (! $this->c->ping()) {
                $this->c = null;
                $this->c = new mysqli(PHP_DB_SERVER, PHP_DB_USER, PHP_DB_PWD, PHP_DB_SCHEMA);
            }
        } else {
            throw new Exception('Database was not connected', E_ERROR);
        }

        $this->logger->debug($this->sql);

        try {
            if (in_array($this->query_type, [
                self::SELECT,
                self::SELECT_COUNT
            ])) {
                $this->result = $this->c->query($this->sql);
                if ($this->c->error) {
                    $this->log("There is an error " . $this->c->error, LogLevel::ERROR);
                    throw new Exception("There was an error " . $this->c->error, E_ERROR);
                }
            } else {
                $this->result = $this->c->real_query($this->sql);
                if ($this->c->errno) {
                    $this->log("There was an error " . $this->c->error, LogLevel::ERROR);
                    throw new Exception("There was an error " . $this->c->error, E_ERROR);
                }
            }

            if ($return == MYSQLI_OBJECT && ! is_null($class) && class_exists($class)) {
                $this->result = $this->check_results($return, $class);
            } elseif ($return == MYSQLI_OBJECT && is_null($class)) {
                $this->result = $this->check_results($return, 'stdClass');
            } else {
                $this->result = $this->check_results(MYSQLI_ASSOC);
            }
        } catch (Exception $e) {
            // die($e->getTraceAsString());
        }

        return $this->result;
    }

    /**
     * Function to check the results and return what is expected
     *
     * @param mixed $return_type
     *            [optional]
     *            Optional return mysqli_result return type
     *
     * @return mixed
     */
    public function check_results($return_type = MYSQLI_ASSOC, $class = null)
    {
        $res = null;

        switch ($this->query_type) {
            case self::SELECT_COUNT:
                if (! is_a($this->result, 'mysqli_result')) {
                    $this->log("Error with return on query", LogLevel::ERROR);
                }

                if ($this->result->num_rows == 1) {
                    $row = $this->result->fetch_assoc();
                    if (isset($row['count'])) {
                        $res = $row['count'];
                    }
                } elseif ($this->result->num_rows > 1) {
                    $res = $this->result->num_rows;
                }

                mysqli_free_result($this->result);

                return $res;
            case self::SELECT:
                if (! is_a($this->result, 'mysqli_result')) {
                    $this->log("Error with return on query", LogLevel::ERROR);
                }

                if ($return_type == MYSQLI_OBJECT && ! is_null($class) && class_exists($class)) {
                    if ($this->result->num_rows == 1) {
                        $res = $this->result->fetch_object($class);
                    } elseif ($this->result->num_rows > 1) {
                        while ($row = $this->result->fetch_object($class)) {
                            $res[] = $row;
                        }
                    }
                } else {
                    if ($this->result->num_rows == 1) {
                        $res = $this->result->fetch_array($return_type);
                    } elseif ($this->result->num_rows > 1) {
                        $res = $this->fetch_all($return_type);
                    }
                }

                mysqli_free_result($this->result);

                return $res;
            case self::INSERT:
                if ($this->c->error) {
                    $this->log("Database Error " . $this->c->error, LogLevel::ERROR);
                    return 0;
                }

                if ($this->c->insert_id) {
                    return $this->c->insert_id;
                } elseif ($this->c->affected_rows) {
                    return $this->c->affected_rows;
                }

                return 1;
            case self::EXTENDED_INSERT:
            case self::EXTENDED_REPLACE:
            case self::EXTENDED_UPDATE:
            case self::REPLACE:
            case self::UPDATE:
            case self::DELETE:
            case self::ALTER_TABLE:
                if ($this->c->error && $this->c->errno == 1060) {
                    return ($this->c->affected_rows ? $this->c->affected_rows : true);
                } elseif ($this->c->error) {
                    $this->log("Database Error " . $this->c->error, LogLevel::ERROR);
                    return false;
                } elseif ($this->c->affected_rows) {
                    return $this->c->affected_rows;
                } else {
                    return true;
                }
            case self::CREATE_TABLE:
            case self::DROP:
            case self::TRUNCATE:
                return true;
        }
    }

    /**
     * Function to pass through calling the query function (used for backwards compatibility and for more complex queries that aren't currently supported)
     * Nothing is escaped
     *
     * @param string $sql
     *            [optional]
     *            Optional query to pass in and execute
     *
     * @return \mysqli_result
     */
    public function query($sql = null)
    {
        if (is_null($sql)) {
            return $this->c->query($this->sql);
        } else {
            return $this->c->query($sql);
        }
    }

    /**
     * A function to build a select query
     *
     * @param string $table_name
     *            The table to query
     * @param array $fields
     *            [optional]
     *            Optional array of fields to return (defaults to '*')
     * @param array $where
     *            [optional]
     *            Optional 2-dimensional array to build where clause from
     * @param array $flags
     *            [optional]
     *            Optional 2-dimensional array to allow other flags
     *
     * @see Database::where()
     * @see Database::flags()
     *
     * @return mixed
     */
    public function select($table_name, $fields = null, $where = null, $flags = null)
    {
        $this->sql = null;
        $this->query_type = self::SELECT;

        if (! is_null($table_name) && is_string($table_name)) {
            $this->sql = "SELECT " . $this->fields($fields) . " FROM $table_name";
        } else {
            throw new Exception("Table name is invalid", E_ERROR);
        }

        if (isset($flags['joins']) && is_array($flags['joins'])) {
            $this->sql .= " " . implode(" ", $flags['joins']);
        }

        if (! is_null($where) && is_array($where) && count($where)) {
            $this->sql .= $this->where($where);
        }

        if (count($flags)) {
            $this->sql .= $this->flags($flags);
        }

        if (self::$autorun) {
            return $this->execute(MYSQLI_BOTH);
        }

        return $this->sql;
    }

    /**
     * Function to build a query to check the number of rows in a table
     *
     * @param string $table_name
     *            The table to query
     * @param array $where
     *            [optional]
     *            Optional 2-dimensional array to build where clause
     * @param array $flags
     *            [optional]
     *            Optional 2-dimensional array to add flags
     *
     * @see Database::where()
     * @see Database::flags()
     *
     * @return string|NULL
     */
    public function select_count($table_name, $where = null, $flags = null)
    {
        $this->sql = null;
        $this->query_type = self::SELECT_COUNT;

        if (! is_null($table_name) && is_string($table_name)) {
            $this->sql = "SELECT COUNT(1) AS 'count' FROM $table_name";
        } else {
            return null;
        }

        if (isset($flags['joins']) && is_array($flags['joins'])) {
            $this->sql .= " " . implode(" ", $flags['joins']);
        }

        if (! is_null($where) && is_array($where) && count($where)) {
            $this->sql .= $this->where($where);
        }

        if (count($flags)) {
            $this->sql .= $this->flags($flags);
        }

        if (self::$autorun) {
            return $this->execute(MYSQLI_BOTH);
        }

        return $this->sql;
    }

    /**
     * Function to build an insert query statement
     *
     * @param string $table_name
     * @param array $params
     * @param boolean $to_ignore
     *
     * @return string|NULL
     */
    public function insert($table_name, $params = null, $to_ignore = false)
    {
        $this->sql = null;
        $this->query_type = self::INSERT;

        if (! is_null($table_name) && is_string($table_name)) {
            $this->sql = "INSERT" . ($to_ignore ? " IGNORE" : "") . " INTO $table_name" . (is_array($params) && count($params) ? " (`" . implode("`,`", array_keys($params)) . "`)" : null);
        } else {
            throw (new Exception("Missing table name in insert function", E_ERROR));
        }

        if (is_array($params) && count($params)) {
            $this->sql .= " VALUES (" . implode(",", array_map([
                $this,
                '_escape'
            ], array_values($params))) . ")";
        } elseif (is_string($params) && stripos($params, 'SELECT') !== false) {
            $this->sql .= " {$params}";
        } else {
            throw (new Exception("Invalid type passed to insert " . gettype($params), E_ERROR));
        }

        if (self::$autorun) {
            return $this->execute(MYSQLI_BOTH);
        }

        return $this->sql;
    }

    /**
     * Function to create an extended insert query statement
     *
     * @param string $table_name
     *            The table name that the data is going to be inserted on
     * @param array $fields
     *            An array of field names that each value represents
     * @param array|string $params
     *            An array of array of values or a string with a SELECT statement to populate the insert with
     * @param boolean $to_ignore
     *            [optional]
     *            Boolean to decide if we need to use the INSERT IGNORE INTO syntax
     *
     * @return NULL|string Returns the SQL if self::$autorun is set to false, else it returns the output from running.
     */
    public function extended_insert($table_name, $fields, $params, $to_ignore = false)
    {
        $this->sql = null;
        $this->query_type = self::EXTENDED_INSERT;

        if (! is_null($table_name) && is_string($table_name)) {
            $this->sql = "INSERT " . ($to_ignore ? "IGNORE " : "") . "INTO $table_name " . "(`" . implode("`,`", $fields) . "`)";
        } else {
            throw (new Exception("Missing table name in extended_insert", E_ERROR));
        }

        if (is_array($params) && count($params)) {
            $this->sql .= " VALUES ";
            if (isset($params[0]) && is_array($params[0])) {
                foreach ($params as $p) {
                    if (count($p) != count($fields)) {
                        throw (new Exception("Inconsistent number of fields in fields and values in extended_insert " . print_r($p, true), E_ERROR));
                    }
                    $this->sql .= "(" . implode(",", array_map([
                        $this,
                        '_escape'
                    ], array_values($p))) . ")";

                    if ($p != end($params)) {
                        $this->sql .= ",";
                    }
                }
            }
        }

        if (self::$autorun) {
            return $this->execute(MYSQLI_BOTH);
        }

        return $this->sql;
    }

    /**
     * Build a statement to update a table
     *
     * @param string $table_name
     *            The table name to update
     * @param array $params
     *            Name/value pairs of the field name and value
     * @param array $where
     *            [optional]
     *            Two-dimensional array to create where clause
     * @param array $flags
     *            [optional]
     *            Two-dimensional array to create other flag options (joins, order, and group)
     *
     * @see Database::where()
     * @see Database::flags()
     *
     * @return NULL|string
     */
    public function update($table_name, $params, $where = null, $flags = null)
    {
        $this->sql = "UPDATE ";
        $this->query_type = self::UPDATE;

        if (! is_null($table_name) && is_string($table_name)) {
            $this->sql .= $table_name;

            if (isset($flags['joins'])) {
                $this->sql .= " " . implode(" ", $flags['joins']);
                unset($flags['joins']);
            }

            $this->sql .= " SET ";
        } else {
            throw new Exception("Invalid table name datatype", E_ERROR);
        }

        foreach ($params as $f => $p) {
            if ((strpos($f, "`") === false) && (strpos($f, ".") === false) && (strpos($f, "*") === false) && (stripos($f, " as ") === false)) {
                $f = "`{$f}`";
            }

            if (! is_null($p)) {
                $this->sql .= "$f={$this->_escape($p)},";
            } else {
                $this->sql .= "$f=NULL,";
            }
        }

        $this->sql = substr($this->sql, 0, - 1);

        if (! is_null($where) && is_array($where) && count($where)) {
            $this->sql .= $this->where($where);
        }

        if (! is_null($flags) && is_array($flags) && count($flags)) {
            $this->sql .= $this->flags($flags);
        }

        if (self::$autorun) {
            return $this->execute(MYSQLI_BOTH);
        }

        return $this->sql;
    }

    /**
     * Function to offer an extended updated functionality by using two different tables.
     *
     * @param string $to_be_updated
     *            The table that you want to update (alias 'tbu' is automatically added)
     * @param string $original
     *            The table with the data you want to overwrite to_be_updated table (alias 'o' is automatically added)
     * @param string $using
     *            The common index value between them that will join the fields
     * @param array|string $params
     *            If string only a single field is updated (tbu.$params = o.$params)
     *            If array each element in the array is a field to be updated (tbu.$param = o.$param)
     *
     * @return mixed
     */
    public function extended_update($to_be_updated, $original, $using, $params)
    {
        $this->sql = "UPDATE ";
        $this->query_type = self::EXTENDED_UPDATE;

        if (! is_null($to_be_updated) && ! is_null($original) && ! is_null($using)) {
            $this->sql .= "$to_be_updated tbu INNER JOIN $original o USING ($using) SET ";
        }

        if (is_array($params) && count($params)) {
            foreach ($params as $param) {
                if ($param != $using) {
                    $this->sql .= "tbu.`$param` = o.`$param`,";
                }
            }
            $this->sql = substr($this->sql, 0, - 1);
        } elseif (is_string($params)) {
            $this->sql .= "tbu.`$params` = o.`$params`";
        } else {
            throw new Exception("Do not understand datatype " . gettype($params), E_ERROR);
        }

        if (self::$autorun) {
            return $this->execute(MYSQL_BOTH);
        }

        return $this->sql;
    }

    /**
     * Function to build a replace query
     *
     * @param string $table_name
     *            The table to update
     * @param array $params
     *            Name/value pair to insert
     *
     * @return NULL|string
     */
    public function replace($table_name, $params)
    {
        $this->sql = null;
        $this->query_type = self::REPLACE;

        if (! is_null($table_name) && is_string($table_name)) {
            $this->sql = "REPLACE INTO $table_name " . "(`" . implode("`,`", array_keys($params)) . "`)";
        } else {
            throw (new Exception("Table name is not valid", E_ERROR));
        }

        $this->sql .= " VALUES (" . implode(",", array_map([
            $this,
            '_escape'
        ], array_values($params))) . ")";

        if (self::$autorun) {
            return $this->execute(MYSQLI_BOTH);
        }

        return $this->sql;
    }

    /**
     * Function to build an extended replace statement
     *
     * @param string $table_name
     *            Table name to update
     * @param array $fields
     *            Array of fields
     * @param array $params
     *            Two-dimensional array of values
     *
     * @return NULL|string
     */
    public function extended_replace($table_name, $fields, $params)
    {
        $this->sql = null;
        $this->query_type = self::EXTENDED_REPLACE;

        if (! is_null($table_name) && is_string($table_name)) {
            $this->sql = "REPLACE INTO $table_name " . "(`" . implode("`,`", $fields) . "`)";
        } else {
            throw (new Exception("Table name is not valid", E_ERROR));
        }

        if (is_array($params) && count($params)) {
            $this->sql .= " VALUES ";
            foreach ($params as $p) {
                $this->sql .= "(" . implode(",", array_map([
                    $this,
                    '_escape'
                ], array_values($p))) . ")";

                if ($p != end($params)) {
                    $this->sql .= ",";
                }
            }
        }

        if (self::$autorun) {
            return $this->execute(MYSQLI_BOTH);
        }

        return $this->sql;
    }

    /**
     * Function to build a delete statement
     *
     * @param string $table_name
     *            Table name to act on
     * @param array $fields
     *            [optional]
     *            Optional list of fields to delete (used when including multiple tables)
     * @param array $where
     *            [optional]
     *            Optional 2-dimensional array to build where clause from
     * @param array $joins
     *            [optional]
     *            Optional 2-dimensional array to add other flags
     *
     * @see Database::where()
     * @see Database::flags()
     *
     * @return string|NULL
     */
    public function delete($table_name, $fields = null, $where = null, $joins = null)
    {
        $this->sql = "DELETE";
        $this->query_type = self::DELETE;

        if (! is_null($fields) && is_array($fields)) {
            $this->sql .= " " . implode(",", $fields);
        }

        if (! is_null($table_name) && is_string($table_name)) {
            $this->sql .= " FROM $table_name";
        } else {
            throw (new Exception("Failed to create delete query, no table name", E_ERROR));
        }

        if (! is_null($joins) && is_array($joins) && count($joins)) {
            $this->sql .= " " . implode(" ", $joins);
        }

        if (! is_null($where) && is_array($where) && count($where)) {
            $this->sql .= $this->where($where);
        }

        if (self::$autorun) {
            return $this->execute(MYSQLI_BOTH);
        }

        return $this->sql;
    }

    /**
     * Function to build a drop table statement (automatically executes)
     *
     * @param string $name
     *            Table to drop
     * @param string $type
     *            [optional]
     *            Type of item to drop ('table', 'view') (defaulted to 'table')
     * @param boolean $is_tmp
     *            [optional]
     *            Optional boolean if this is a temporary table
     *
     * @return string|NULL
     */
    public function drop($name, $type = 'table', $is_tmp = false)
    {
        $this->sql = null;
        $this->query_type = self::DROP;

        switch ($type) {
            case 'table':
                $type = 'TABLE';
                break;
            case 'view':
                $type = 'VIEW';
                break;
            default:
                throw new Exception("Invalid type " . gettype($type), E_ERROR);
        }

        if (! is_null($name) && is_string($name)) {
            $this->sql = "DROP" . ($is_tmp ? " TEMPORARY" : "") . " $type IF EXISTS `$name`";
        } else {
            throw new Exception("Table name is invalid", E_ERROR);
        }

        if (self::$autorun) {
            return $this->execute(MYSQLI_BOTH);
        }

        return $this->sql;
    }

    /**
     * Function to build a truncate table statement (automatically executes)
     *
     * @param string $table_name
     *            Table to truncate
     *
     * @return string|NULL
     */
    public function truncate($table_name)
    {
        $this->sql = null;
        $this->query_type = self::TRUNCATE;

        if (! is_null($table_name) && is_string($table_name)) {
            $this->sql = "TRUNCATE TABLE $table_name";
        } else {
            throw new Exception("Table name is invalid", E_ERROR);
        }

        if (self::$autorun) {
            return $this->execute(MYSQLI_BOTH);
        }

        return $this->sql;
    }

    /**
     * Function to build a create temporary table statement
     *
     * @param string $table_name
     *            Name to give the table when creating
     * @param boolean $is_tmp
     *            [optional]
     *            Optional boolean to make the table a temporary table
     * @param mixed $select
     *            [optional]
     *            Optional parameter if null uses last built statement
     *            If string, will be made the SQL statement executed to create the table
     *            If array, 2-dimensional array with "field", "datatype" values to build table fields
     *
     * @return NULL|string
     */
    public function create_table($table_name, $is_tmp = false, $select = null)
    {
        $this->query_type = self::CREATE_TABLE;

        if (is_null($select) && ! is_null($this->sql) && substr($this->sql, 0, 6) == 'SELECT') {
            $this->sql = "CREATE" . ($is_tmp ? " TEMPORARY" : "") . " TABLE IF NOT EXISTS $table_name AS ($this->sql)";
        }
        if (! is_null($table_name) && is_string($table_name) && is_string($select)) {
            $this->sql = "CREATE" . ($is_tmp ? " TEMPORARY" : "") . " TABLE IF NOT EXISTS $table_name AS ($select)";
        } elseif (! is_null($table_name) && is_string($table_name) && is_array($select)) {
            $this->sql = "CREATE" . ($is_tmp ? " TEMPORARY" : "") . " TABLE IF NOT EXISTS $table_name (";

            foreach ($select as $field) {
                $default = null;
                if (isset($field['default'])) {
                    $default = (is_null($field['default']) ? "" : " DEFAULT '{$field['default']}'");
                }
                $this->sql .= "`{$field['field']}` {$field['datatype']}" . $default . (isset($field['option']) ? " {$field['option']}" : '') . ",";
            }
            $this->sql = substr($this->sql, 0, - 1) . ")";
        }

        if (self::$autorun) {
            return $this->execute();
        }

        return $this->sql;
    }

    /**
     * Function to create a table using a stdClass object derived from JSON
     *
     * @param \stdClass $json
     *
     * @example /examples/create_table_json.json
     *
     */
    public function create_table_json($json)
    {
        $this->query_type = self::CREATE_TABLE;
        $this->c->select_db($json->schema);

        $this->sql = "CREATE TABLE IF NOT EXISTS `{$json->name}` (";
        foreach ($json->fields as $field) {
            $this->sql .= "`{$field->name}` {$field->dataType}";

            if ($field->dataType == 'enum') {
                $this->sql .= "('" . implode("','", $field->values) . "')";
            }

            if ($field->ai) {
                $this->sql .= " AUTO_INCREMENT";
            }

            if ($field->nn) {
                $this->sql .= " NOT NULL";
            } else {
                if ($field->default === null) {
                    $this->sql .= " DEFAULT NULL";
                } elseif (strlen($field->default)) {
                    $this->sql .= " DEFAULT '{$field->default}'";
                }
            }

            if ($field != end($json->fields)) {
                $this->sql .= ",";
            }
        }

        if (isset($json->index) && count($json->index)) {
            foreach ($json->index as $ind) {
                $this->sql .= ", " . strtoupper($ind->type) . " `{$ind->id}` (`{$ind->ref}`)";
            }
        }

        if (isset($json->constraints) && count($json->constraints)) {
            foreach ($json->constraints as $con) {
                $this->sql .= ", CONSTRAINT `{$con->id}` " . "FOREIGN KEY (`{$con->local}`) " . "REFERENCES `{$con->schema}`.`{$con->table}` (`{$con->field}`) " . "ON DELETE " . (is_null($con->delete) ? "NO ACTION" : strtoupper($con->delete)) . " " . "ON UPDATE " . (is_null($con->update) ? "NO ACTION" : strtoupper($con->update));
            }
        }

        if (isset($json->unique) && count($json->unique)) {
            $this->sql .= ", UNIQUE(`" . implode("`,`", $json->unique) . "`)";
        }

        if (isset($json->primary_key) && count($json->primary_key)) {
            $this->sql .= ", PRIMARY KEY(`" . implode("`,`", $json->primary_key) . "`))";
        } else {
            if (substr($this->sql, - 1) == ',') {
                $this->sql = substr($this->sql, 0, - 1);
            }

            $this->sql .= ")";
        }

        $this->execute(MYSQLI_BOTH);
    }

    /**
     * Function to alter a existing table
     *
     * @param string $table_name
     *            Table to alter
     * @param string $action
     *            What action should be taken ('add-column', 'drop-column', 'modify-column')
     * @param mixed $params
     *            For add column this is a stdClass object that has the same elements as the example json
     *
     * @return mixed
     */
    public function alter_table($table_name, $action, $params)
    {
        $this->query_type = self::ALTER_TABLE;
        $this->sql = "ALTER TABLE $table_name";
        if ($action == 'add-column') {
            $nn = ($params->nn ? " NOT NULL" : "");
            $default = null;
            if ($params->default === null) {
                $default = " DEFAULT NULL";
            } elseif (strlen($params->default)) {
                $default = " DEFAULT {$this->_escape($params->default)}";
            }
            $this->sql .= " ADD COLUMN `{$params->name}` {$params->dataType}" . $nn . $default;
        } elseif ($action == 'drop-column') {
            $this->sql .= " DROP COLUMN ";
            foreach ($params as $col) {
                $this->sql .= "`{$col->name}`";

                if ($col != end($params)) {
                    $this->sql .= ",";
                }
            }
        } elseif ($action == 'modify-column') {
            $this->sql .= " MODIFY COLUMN";
            $nn = ($params->nn ? " NOT NULL" : "");
            $default = null;
            if ($params->default === null) {
                $default = " DEFAULT NULL";
            } elseif (strlen($params->default)) {
                $default = " DEFAULT {$this->_escape($params->default)}";
            }
            $this->sql .= " `{$params->name}` `{$params->new_name}` {$params->dataType}" . $nn . $default;
        }

        if (self::$autorun) {
            return $this->execute();
        }

        return $this->sql;
    }

    /**
     * Check to see if a field in a table exists
     *
     * @param string $table_name
     *            Table to check
     * @param string $field_name
     *            Field name to find
     *
     * @return boolean Returns TRUE if field is found in that schema and table, otherwise FALSE
     */
    public function field_exists($table_name, $field_name)
    {
        $fdata = $this->field_data($table_name);

        if (is_array($fdata) && count($fdata)) {
            foreach ($fdata as $field) {
                if ($field->name == $field_name) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Function to get the column data (datatype, flags, defaults, etc)
     *
     * @param string $table_name
     *            Table to query
     * @param mixed $field
     *            [optional]
     *            Optional field to retrieve data (if null, returns data from all fields)
     *
     * @return array
     */
    public function field_data($table_name, $field = null)
    {
        if (is_null($field)) {
            $res = $this->c->query("SELECT * FROM $table_name LIMIT 1");
        } elseif (is_array($field)) {
            $res = $this->c->query("SELECT `" . implode("`,`", $field) . "` FROM $table_name LIMIT 1");
        } elseif (is_string($field)) {
            $res = $this->c->query("SELECT $field FROM $table_name LIMIT 1");
        } else {
            return null;
        }

        $fields = null;
        if (is_a($res, 'mysqli_result')) {
            $fields = $res->fetch_fields();
            foreach ($fields as $i => $f) {
                $fields["{$f->name}"] = $f;
                unset($fields[$i]);
            }
        }

        return $fields;
    }

    /**
     * Function to check that all field parameters are set correctly
     *
     * @param object $field_data
     * @param object $check
     * @param object $pks
     * @param object $index
     *
     * @return array
     */
    public function field_check($field_data, $check, $pks, $index)
    {
        $default = null;
        $ret = null;

        $nn = ($check->nn ? " NOT NULL" : null);
        if ($check->default === null) {
            $default = " DEFAULT NULL";
        } elseif (strlen($check->default)) {
            $default = " DEFAULT '{$check->default}'";
        }

        if ($field_data->type != $check->type && $check->type != MYSQLI_TYPE_ENUM) {
            $this->log("{$field_data->name} wrong datatype, changing to {$check->dataType}", LogLevel::NOTICE);
            $ret = " CHANGE COLUMN `{$field_data->name}` `{$check->name}` {$check->dataType}" . "{$nn}{$default}";
        } elseif (! is_null($check->length) && $field_data->length != $check->length) {
            $this->log("{$field_data->name} incorrect size ({$field_data->length} != {$check->length})", LogLevel::NOTICE);
            $ret = " CHANGE COLUMN `{$field_data->name}` `{$check->name}` {$check->dataType}" . "{$nn}{$default}";
        } elseif ($check->type == MYSQLI_TYPE_ENUM && ! ($field_data->flags & MYSQLI_ENUM_FLAG)) {
            $ret = " CHANGE COLUMN `{$field_data->name}` `{$check->name}` {$check->dataType}('" . implode("','", $check->values) . "')" . "{$nn}{$default}";
        }

        if (! is_null($index) && count($index)) {
            foreach ($index as $ind) {
                if ($check->name == $ind->ref && ! ($field_data->flags & MYSQLI_MULTIPLE_KEY_FLAG)) {
                    $this->log("{$field_data->name} is not an index", LogLevel::NOTICE);
                    $ret .= ($ret ? "," : "") . " ADD INDEX `{$ind->id}` (`{$ind->ref}` ASC)";
                }
            }
        }

        if (in_array($check->name, $pks) && ! ($field_data->flags & MYSQLI_PRI_KEY_FLAG)) {
            $ret .= ($ret ? "," : "") . " DROP PRIMARY KEY, ADD PRIMARY KEY(`" . implode("`,`", $pks) . "`)";
        }

        return $ret;
    }

    /**
     * Function to check for the existence of a table within a schema
     *
     * @param string $schema
     *            The schema to search in
     * @param string $table_name
     *            Table to search for
     *
     * @return boolean Returns number of tables that match if table is found in that schema, otherwise FALSE
     */
    public function table_exists(string $schema, string $table_name)
    {
        if (! $this->c->select_db($schema)) {
            fwrite(STDOUT, $this->c->error . PHP_EOL);
        }
        $sql = "SHOW TABLES LIKE '{$table_name}'";

        if ($res = $this->c->query($sql)) {
            if (is_a($res, 'mysqli_result') && $res->num_rows) {
                return $res->num_rows;
            }
        } else {
            if ($this->c->errno) {
                fwrite(STDOUT, $this->c->error . PHP_EOL);
            }
        }

        return false;
    }

    /**
     * Function to detect if string is a JSON object or not
     *
     * @param string $val
     *
     * @return boolean
     */
    public function isJson($val)
    {
        json_decode($val);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    /**
     * Function to escape SQL characters to prevent SQL injection
     *
     * @param mixed $val
     *            Value to escape
     * @param boolean $escape
     *            Decide if we should escape or not
     *
     * @return string Escaped value
     */
    public function _escape($val, $escape = true)
    {
        if (is_null($val) || (is_string($val) && strtolower($val) == 'null')) {
            return 'NULL';
        } elseif (is_numeric($val) || is_string($val)) {
            if ($escape) {
                return "'{$this->c->real_escape_string($val)}'";
            }
            return $val;
        } elseif (is_a($val, 'DateTime')) {
            return "'{$val->format(MYSQL_DATETIME)}'";
        } elseif (is_bool($val)) {
            return $val ? "'1'" : "'0'";
        } elseif (gettype($val) == 'object' && method_exists($val, '_escape')) {
            $ret = call_user_func([
                $val,
                '_escape'
            ]);
            if ($ret !== false) {
                return $ret;
            } else {
                throw new Exception("Error in return from _escape method in " . get_class($val), E_ERROR);
            }
        } elseif (gettype($val) == 'object') {
            $this->log("Unknown object to escape " . get_class($val) . " in SQL string {$this->sql}", LogLevel::ERROR);
        }

        throw new Exception("Unknown datatype to escape in SQL string {$this->sql} " . gettype($val), E_ERROR);
    }

    /**
     * Function to retrieve all results
     *
     * @param string $resulttype
     *
     * @return mixed
     */
    public function fetch_all($resulttype = MYSQLI_ASSOC)
    {
        $res = [];
        if (method_exists('mysqli_result', 'fetch_all')) { // Compatibility layer with PHP < 5.3
            $res = $this->result->fetch_all($resulttype);
        } else {
            while ($tmp = $this->result->fetch_array($resulttype)) {
                $res[] = $tmp;
            }
        }

        return $res;
    }

    /**
     * Function to populate the fields for the SQL
     *
     * @param array $fields
     *            [optional]
     *            Optional array of fields to string together to create a field list
     *
     * @return string
     */
    public function fields($fields = null)
    {
        $str_fields = null;

        if (is_array($fields) && count($fields)) {
            foreach ($fields as $field) {
                if ((strpos($field, '`') === false) && (strpos($field, '.') === false) && (strpos($field, '*') === false) && (strpos($field, 'JSON_') === false) && (stripos($field, ' as ') === false)) {
                    $str_fields .= "`$field`,";
                } else {
                    $str_fields .= "$field,";
                }
            }
            $str_fields = substr($str_fields, 0, - 1);
        } elseif (is_string($fields)) {
            $str_fields = $fields;
        } elseif (is_null($fields)) {
            $str_fields = "*";
        }

        return $str_fields;
    }

    /**
     * Function to create the where statement for the SQL
     *
     * @param array $where
     *            Two-dimensional array to use to build the where clause
     *
     *            <code>
     *            [<br />
     *            &nbsp;&nbsp;[<br />
     *            &nbsp;&nbsp;&nbsp;&nbsp;'field' => 'field_name',<br />
     *            &nbsp;&nbsp;&nbsp;&nbsp;'op' => '=', // (defaults to '=', common operations or IN, NOT_IN, BETWEEN, LIKE, NOT_LIKE, IS, & IS_NOT constants)<br />
     *            &nbsp;&nbsp;&nbsp;&nbsp;'value' => 'field_value',<br />
     *            &nbsp;&nbsp;&nbsp;&nbsp;'sql_op' => 'AND', // NOT required for first element (common SQL operators AND, OR, NOR)<br />
     *            &nbsp;&nbsp;&nbsp;&nbsp;'open-paren' => true, // optional to add a paren '(' BEFORE clause<br />
     *            &nbsp;&nbsp;&nbsp;&nbsp;'close-paren' => true, // optional to add a paren ')' AFTER clause<br />
     *            &nbsp;&nbsp;&nbsp;&nbsp;'low' => '1', // LOW value only used in BETWEEN clause<br />
     *            &nbsp;&nbsp;&nbsp;&nbsp;'high' => '100', // HIGH value only used in BETWEEN clause<br />
     *            &nbsp;&nbsp;&nbsp;&nbsp;'case_insensitive' => true // optional boolean to set the parameters to LOWER to do case insenstive comparison
     *            &nbsp;&nbsp;],<br />
     *            &nbsp;&nbsp;[<br />
     *            &nbsp;&nbsp;&nbsp;&nbsp;...<br />
     *            &nbsp;&nbsp;],<br />
     *            &nbsp;&nbsp;...<br />
     *            ]
     *            </code>
     *
     * @return string
     */
    public function where($where)
    {
        $ret = " WHERE";
        if (! is_array($where) || ! count($where) || ! isset($where[0])) {
            $this->log("Invalid where array clause", LogLevel::WARNING);
            return;
        }

        foreach ($where as $x => $w) {
            $ret .= $this->parse_clause($w, $x);
        }

        if ($ret == " WHERE") {
            $ret = '';
        }

        return $ret;
    }

    /**
     * Function to parse the flags
     *
     * @param array $flags
     *            Two-dimensional array to added flags
     *
     *            <code>
     *            [
     *            &nbsp;&nbsp;'joins' => [
     *            &nbsp;&nbsp;&nbsp;&nbsp;"JOIN table2 t2 ON t2.id=t1.id"
     *            &nbsp;&nbsp;],
     *            &nbsp;&nbsp;'group' => 'field',
     *            &nbsp;&nbsp;'having' => 'field',
     *            &nbsp;&nbsp;'order' => 'field',
     *            &nbsp;&nbsp;'start' => 0,
     *            &nbsp;&nbsp;'limit' => 0
     *            ]
     *            </code>
     *
     * @see Database::groups()
     * @see Database::having()
     * @see Database::order()
     *
     * @return string
     */
    public function flags($flags)
    {
        $ret = '';

        if (isset($flags['group'])) {
            $ret .= $this->groups($flags['group']);
        }

        if (isset($flags['having']) && is_array($flags['having'])) {
            $ret .= $this->having($flags['having']);
        }

        if (isset($flags['order'])) {
            $ret .= $this->order($flags['order']);
        }

        if (isset($flags['limit']) && (is_string($flags['limit']) || is_numeric($flags['limit']))) {
            $ret .= " LIMIT ";
            if (isset($flags['start']) && (is_string($flags['start']) || is_numeric($flags['start']))) {
                $ret .= "{$flags['start']},";
            }
            $ret .= "{$flags['limit']}";
        }

        return $ret;
    }

    /**
     * Function to parse SQL GROUP BY statements
     *
     * @param mixed $groups
     *
     * @return string
     */
    public function groups($groups)
    {
        $ret = '';
        if (is_array($groups) && count($groups)) {
            $ret .= " GROUP BY";

            foreach ($groups as $grp) {
                $ret .= " $grp";

                if ($grp != end($groups)) {
                    $ret .= ",";
                }
            }
        } elseif (is_string($groups)) {
            $ret .= " GROUP BY {$groups}";
        } else {
            throw (new Exception("Error in datatype for groups " . gettype($groups), E_ERROR));
        }

        return $ret;
    }

    /**
     * Function to parse SQL HAVING statements (same format as WHERE)
     *
     * @param mixed $having
     *
     * @return string
     *
     * @see Database::where()
     */
    public function having($having)
    {
        if (! is_array($having) || ! count($having) || ! isset($having[0]) || ! is_array($having[0])) {
            $this->log("Invalid having parameter", LogLevel::WARNING, $having);
            return;
        }

        $ret = " HAVING";
        foreach ($having as $x => $h) {
            $ret .= $this->parse_clause($h, $x);
        }

        if ($ret == " HAVING") {
            $ret = '';
        }

        return $ret;
    }

    /**
     * Function to parse SQL ORDER BY statements
     *
     * @param mixed $order
     *
     * @return string
     */
    public function order($order)
    {
        $ret = '';
        if (is_array($order)) {
            $ret .= " ORDER BY";

            foreach ($order as $ord) {
                $ret .= " {$ord['field']} {$ord['sort']}";

                if ($ord != end($order)) {
                    $ret .= ",";
                }
            }
        } elseif (is_string($order)) {
            $ret .= " ORDER BY {$order}";
        }

        return $ret;
    }

    /**
     * Function to see if a constraint exists
     *
     * @param string $con_id
     *
     * @return boolean
     */
    public function is_constraint($con_id)
    {
        $res = $this->c->query("SELECT * FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_NAME = '$con_id'");

        if ($res->num_rows) {
            return true;
        }

        return false;
    }

    /**
     * Function to call logger and log activity
     *
     * @param string $msg
     * @param LogLevel $level
     *            [optional]
     * @param array $context
     *            [optional]
     */
    public function log($msg, $level = LogLevel::ERROR, $context = [])
    {
        if ($level == LogLevel::INFO) {
            $this->logger->info($msg, $context);
        } elseif ($level == LogLevel::WARNING) {
            $this->logger->warning($msg, $context);
        } elseif ($level == LogLevel::ERROR) {
            $this->logger->error($msg, $context);
        } elseif ($level == LogLevel::NOTICE) {
            $this->logger->notice($msg, $context);
        } elseif ($level == LogLevel::DEBUG) {
            $this->logger->debug($msg, $context);
        }
    }

    /**
     * Function to parse where and having clauses
     *
     * @param mixed $clause
     * @param int $index
     */
    public function parse_clause($clause, $index)
    {
        $ret = null;

        if (! isset($clause['field']) && isset($clause['close-paren']) && $clause['close-paren']) {
            $ret .= ")";
            return $ret;
        } elseif ($index > 0 && ! isset($clause['sql_op'])) {
            $this->log("Missing sql_op field to identify how current and previous WHERE clause statements should be linked ('AND', 'OR', 'XOR', etc), skipped", LogLevel::WARNING, $clause);
            return;
        }

        $op = '=';
        if (isset($clause['op'])) {
            $op = $clause['op'];
        }

        switch ($op) {
            case BETWEEN:
                if (! isset($clause['field']) || ! isset($clause['low']) || ! isset($clause['high'])) {
                    $this->log("Missing field, low, or high for BETWEEN where clause, skipping", LogLevel::WARNING, $clause);
                    return;
                }
                break;
            default:
                if (! isset($clause['field']) || ! isset($clause['value'])) {
                    $this->log("Missing field or value for WHERE clause, skipping", LogLevel::WARNING, $clause);
                    return;
                }
        }

        if ($index > 0) {
            $ret .= " {$clause['sql_op']}";
        }

        if (isset($clause['open-paren']) && $clause['open-paren']) {
            $ret .= " (";
        }

        if (isset($clause['backticks']) && ! $clause['backticks']) {
            $field = $clause['field'];
        } else {
            $field = "`{$clause['field']}`";
        }

        if ($op == IN || $op == NOT_IN) {
            if (is_string($clause['value'])) {
                $ret .= " {$field} {$op} " . (strpos($clause['value'], '(') !== false ? $clause['value'] : "({$clause['value']})");
            } elseif (is_array($clause['value'])) {
                $ret .= " {$field} {$op} (" . implode(",", array_map([
                    $this,
                    '_escape'
                ], $clause['value'])) . ")";
            } else {
                $this->log("Invalid datatype for IN WHERE clause, only string and array allowed " . gettype($clause['value']), LogLevel::ERROR, $clause);
                throw new Exception("Invalid datatype for IN WHERE clause", E_ERROR);
            }
        } elseif ($op == BETWEEN) {
            $ret .= " {$field} BETWEEN {$this->_escape($clause['low'])} AND {$this->_escape($clause['high'])}";
        } else {
            if (isset($clause['escape']) && ! $clause['escape']) {
                $value = $clause['value'];
            } else {
                $value = $this->_escape($clause['value']);
            }

            if (isset($clause['case_insensitive']) && $clause['case_insensitive']) {
                $ret .= " LOWER({$field}) {$op} LOWER({$this->_escape($clause['value'])})";
            } elseif (preg_match("/\(SELECT/", $clause['value'])) {
                $ret .= " {$field} {$op} {$clause['value']}";
            } else {
                $ret .= " {$field} {$op} {$value}";
            }
        }

        if (isset($clause['close-paren']) && $clause['close-paren']) {
            $ret .= ")";
        }

        return $ret;
    }
}