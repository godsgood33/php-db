<?php

/**
 *
 */
namespace Godsgood33\Php_Db;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Psr\Log\LogLevel;
use Exception;
use mysqli;

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
     * Global to represent an IN statement (e.g.
     * WHERE field IN (1,2))
     *
     * @var string
     */
    const IN = 'IN';

    /**
     * Global to represent a NOT IN statement (e.g.
     * WHERE field NOT IN (1,2))
     *
     * @var string
     */
    const NOT_IN = 'NOT IN';

    /**
     * Global to represent a BETWEEN statement (e.g.
     * WHERE field BETWEEN 1 and 2)
     *
     * @var string
     */
    const BETWEEN = 'BETWEEN';

    /**
     * Global to represent a LIKE statement (e.g.
     * WHERE field LIKE '%value%')
     *
     * @var string
     */
    const LIKE = 'LIKE';

    /**
     * Global to represent a NOT LIKE statement (e.g.
     * WHERE field NOT LIKE '%value%')
     *
     * @var string
     */
    const NOT_LIKE = 'NOT LIKE';

    /**
     * Global to represent an IS statement (e.g.
     * WHERE field IS NULL)
     *
     * @var string
     */
    const IS = 'IS';

    /**
     * Global to represent an IS NOT statement (e.g.
     * WHERE field IS NOT NULL)
     *
     * @var string
     */
    const IS_NOT = 'IS NOT';

    /**
     * The mysqli connection
     *
     * @var \mysqli
     */
    private $_c;

    /**
     * To store the SQL statement
     *
     * @var string
     */
    private $_sql = null;

    /**
     * A string to store the type of query that is being run
     *
     * @var int
     */
    private $_queryType = null;

    /**
     * The result of the query
     *
     * @var mixed
     */
    private $_result = null;

    /**
     * Log level
     *
     * @var string
     */
    private $_logLevel = Logger::ERROR;

    /**
     * Variable to store the logger
     *
     * @var \Monolog\Logger
     */
    private $_logger = null;

    /**
     * Path for the logger to log the file
     *
     * @var string
     */
    private $_logPath = null;

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
     * @param string $logPath
     */
    public function __construct(mysqli &$dbh = null, string $logPath = null)
    {
        require_once 'DBConfig.php';
        if (! is_null($dbh) && is_a($dbh, "mysqli")) {
            $this->_c = $dbh;
        } else {
            $this->_c = new mysqli(PHP_DB_SERVER, PHP_DB_USER, PHP_DB_PWD, PHP_DB_SCHEMA);
        }

        if ($this->_c->connect_errno) {
            throw new Exception("Could not create database class due to error {$this->_c->error}", E_ERROR);
        }

        $this->_logPath = $logPath;
        touch($this->_logPath . "/db.log");

        $this->_logger = new Logger('db', [
            new StreamHandler("php://output", Logger::INFO),
            new StreamHandler(realpath($this->_logPath . "/db.log"), $this->_logLevel)
        ]);

        $this->setVar("time_zone", "+00:00");
        $this->setVar("sql_mode", "");
    }

    /**
     * Function to make sure that the database is connected
     *
     * @return boolean
     */
    public function isConnected()
    {
        return $this->_c->ping();
    }

    /**
     * Setter function for _logger
     *
     * @param Logger $log
     */
    public function setLogger(Logger $log)
    {
        $this->_logger = $log;
    }

    /**
     * Getter function for _logLevel
     *
     * @return string
     */
    public function getLogLevel()
    {
        return $this->_logLevel;
    }

    /**
     * Function to set the log level just in case there needs to be a change to the default log level
     *
     * @param string $strLevel
     */
    public function setLogLevel(string $strLevel)
    {
        $this->_logLevel = $strLevel;
        $this->_logger->setHandlers([
            new StreamHandler("php://output", Logger::INFO),
            new StreamHandler(realpath("{$this->_logPath}/db.log"), $this->_logLevel)
        ]);
    }

    /**
     * Getter function for _sql
     *
     * @return string
     */
    public function getSql()
    {
        return $this->_sql;
    }

    /**
     * Function to return the currently selected database schema
     *
     * @return string
     */
    public function getSchema()
    {
        if ($res = $this->_c->query("SELECT DATABASE()")) {
            $row = $res->fetch_row();
            return $row[0];
        }
        return null;
    }

    /**
     * Function to set schema
     *
     * @param string $strSchema
     */
    public function setSchema(string $strSchema)
    {
        if (! $this->_c->select_db($strSchema)) {
            throw new Exception("Failed to change databases to {$strSchema}", E_ERROR);
        }
        return true;
    }

    /**
     * Method to set a MYSQL variable
     *
     * @param string $strName
     * @param string $strVal
     * @return boolean
     */
    public function setVar(string $strName, string $strVal)
    {
        if (! $strName || ! $strVal) {
            return false;
        }

        return $this->_c->real_query("SET $strName = {$this->_escape($strVal)}");
    }

    /**
     * Function to execute the statement
     *
     * @param mixed $return
     *            [optional]
     *            MYSQLI constant to control what is returned from the mysqli_result object
     * @param string $class
     *            [optional]
     *            Class to use when returning object
     * @param string $sql
     *            [optional]
     *            Optional SQL query
     *
     * @return mixed
     */
    public function execute($return = MYSQLI_ASSOC, $class = null, $sql = null)
    {
        if (! is_null($sql)) {
            $this->_sql = $sql;
        }

        if (is_a($this->_c, 'mysqli')) {
            if (! $this->_c->ping()) {
                require_once 'DBConfig.php';
                $this->_c = null;
                $this->_c = new mysqli(PHP_DB_SERVER, PHP_DB_USER, PHP_DB_PWD, PHP_DB_SCHEMA);
            }
        } else {
            throw new Exception('Database was not connected', E_ERROR);
        }

        $this->_logger->debug($this->_sql);

        try {
            if (in_array($this->_queryType, [
                self::SELECT,
                self::SELECT_COUNT
            ])) {
                $this->_result = $this->_c->query($this->_sql);
                if ($this->_c->error) {
                    $this->log("There is an error {$this->_c->error}", Logger::ERROR);
                    throw new Exception("There was an error {$this->_c->error}", E_ERROR);
                }
            } else {
                $this->_result = $this->_c->real_query($this->_sql);
                if ($this->_c->errno) {
                    $this->log("There was an error {$this->_c->error}", Logger::ERROR);
                    throw new Exception("There was an error {$this->_c->error}", E_ERROR);
                }
            }

            if ($return == MYSQLI_OBJECT && ! is_null($class) && class_exists($class)) {
                $this->_result = $this->checkResults($return, $class);
            } elseif ($return == MYSQLI_OBJECT && is_null($class)) {
                $this->_result = $this->checkResults($return, 'stdClass');
            } else {
                $this->_result = $this->checkResults(MYSQLI_ASSOC);
            }
        } catch (Exception $e) {}

        return $this->_result;
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
    public function checkResults($return_type = MYSQLI_ASSOC, $class = null)
    {
        $res = null;

        switch ($this->_queryType) {
            case self::SELECT_COUNT:
                if (! is_a($this->_result, 'mysqli_result')) {
                    $this->log("Error with return on query", Logger::ERROR);
                }

                if ($this->_result->num_rows == 1) {
                    $row = $this->_result->fetch_assoc();
                    if (isset($row['count'])) {
                        $res = $row['count'];
                    }
                } elseif ($this->_result->num_rows > 1) {
                    $res = $this->_result->num_rows;
                }

                mysqli_free_result($this->_result);

                return $res;
            case self::SELECT:
                if (! is_a($this->_result, 'mysqli_result')) {
                    $this->log("Error with return on query", Logger::ERROR);
                }

                if ($return_type == MYSQLI_OBJECT && ! is_null($class) && class_exists($class)) {
                    if ($this->_result->num_rows == 1) {
                        $res = $this->_result->fetch_object($class);
                    } elseif ($this->_result->num_rows > 1) {
                        while ($row = $this->_result->fetch_object($class)) {
                            $res[] = $row;
                        }
                    }
                } else {
                    if ($this->_result->num_rows == 1) {
                        $res = $this->_result->fetch_array($return_type);
                    } elseif ($this->_result->num_rows > 1) {
                        $res = $this->fetch_all($return_type);
                    }
                }

                mysqli_free_result($this->_result);

                return $res;
            case self::INSERT:
                if ($this->_c->error) {
                    $this->log("Database Error {$this->_c->error}", Logger::ERROR);
                    return 0;
                }

                if ($this->_c->insert_id) {
                    return $this->_c->insert_id;
                } elseif ($this->_c->affected_rows) {
                    return $this->_c->affected_rows;
                }

                return 1;
            // intentional fall through
            case self::EXTENDED_INSERT:
            // intentional fall through
            case self::EXTENDED_REPLACE:
            // intentional fall through
            case self::EXTENDED_UPDATE:
            // intentional fall through
            case self::REPLACE:
            // intentional fall through
            case self::UPDATE:
            // intentional fall through
            case self::DELETE:
            // intentional fall through
            case self::ALTER_TABLE:
                if ($this->_c->error && $this->_c->errno == 1060) {
                    return ($this->_c->affected_rows ? $this->_c->affected_rows : true);
                } elseif ($this->_c->error) {
                    $this->log("Database Error {$this->_c->error}", Logger::ERROR);
                    return false;
                } elseif ($this->_c->affected_rows) {
                    return $this->_c->affected_rows;
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
     * @return \mysqli_result|boolean
     */
    public function query($sql = null)
    {
        if (is_null($sql)) {
            return $this->_c->query($this->_sql);
        } else {
            return $this->_c->query($sql);
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
        $this->_sql = null;
        $this->_query_type = self::SELECT;

        if (! is_null($table_name) && is_string($table_name)) {
            $this->_sql = "SELECT " . $this->fields($fields) . " FROM $table_name";
        } else {
            throw new Exception("Table name is invalid", E_ERROR);
        }

        if (isset($flags['joins']) && is_array($flags['joins'])) {
            $this->_sql .= " " . implode(" ", $flags['joins']);
        }

        if (! is_null($where) && is_array($where) && count($where)) {
            $where_str = " WHERE";
            foreach ($where as $x => $w) {
                $where_str .= $this->parseClause($w, $x);
            }
            if (strlen($where_str) > strlen(" WHERE")) {
                $this->_sql .= $where_str;
            }
        }

        if (is_array($flags) && count($flags)) {
            $this->_sql .= $this->flags($flags);
        }

        if (self::$autorun) {
            return $this->execute(MYSQLI_BOTH);
        }

        return $this->_sql;
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
    public function selectCount($table_name, $where = null, $flags = null)
    {
        $this->_sql = null;
        $this->_queryType = self::SELECT_COUNT;

        if (! is_null($table_name) && is_string($table_name)) {
            $this->_sql = "SELECT COUNT(1) AS 'count' FROM $table_name";
        } else {
            return null;
        }

        if (isset($flags['joins']) && is_array($flags['joins'])) {
            $this->_sql .= " " . implode(" ", $flags['joins']);
        }

        if (! is_null($where) && is_array($where) && count($where)) {
            $where_str = " WHERE";
            foreach ($where as $x => $w) {
                $where_str .= $this->parseClause($w, $x);
            }
            if (strlen($where_str) > strlen(" WHERE")) {
                $this->_sql .= $where_str;
            }
        }

        if (is_array($flags) && count($flags)) {
            $this->_sql .= $this->flags($flags);
        }

        if (self::$autorun) {
            return $this->execute(MYSQLI_BOTH);
        }

        return $this->_sql;
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
        $this->_sql = null;
        $this->_queryType = self::INSERT;

        if (! is_null($table_name) && is_string($table_name)) {
            $this->_sql = "INSERT" . ($to_ignore ? " IGNORE" : "") . " INTO $table_name" . (is_array($params) && count($params) ? " (`" . implode("`,`", array_keys($params)) . "`)" : null);
        } else {
            throw (new Exception("Missing table name in insert function", E_ERROR));
        }

        if (is_array($params) && count($params)) {
            $this->_sql .= " VALUES (" . implode(",", array_map([
                $this,
                '_escape'
            ], array_values($params))) . ")";
        } elseif (is_string($params) && stripos($params, 'SELECT') !== false) {
            $this->_sql .= " {$params}";
        } else {
            throw (new Exception("Invalid type passed to insert " . gettype($params), E_ERROR));
        }

        if (self::$autorun) {
            return $this->execute(MYSQLI_BOTH);
        }

        return $this->_sql;
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
    public function extendedInsert($table_name, $fields, $params, $to_ignore = false)
    {
        $this->_sql = null;
        $this->_queryType = self::EXTENDED_INSERT;

        if (! is_null($table_name) && is_string($table_name)) {
            $this->_sql = "INSERT " . ($to_ignore ? "IGNORE " : "") . "INTO $table_name " . "(`" . implode("`,`", $fields) . "`)";
        } else {
            throw (new Exception("Missing table name in extended_insert", E_ERROR));
        }

        if (is_array($params) && count($params)) {
            $this->_sql .= " VALUES ";
            if (isset($params[0]) && is_array($params[0])) {
                foreach ($params as $p) {
                    if (count($p) != count($fields)) {
                        throw (new Exception("Inconsistent number of fields in fields and values in extended_insert " . print_r($p, true), E_ERROR));
                    }
                    $this->_sql .= "(" . implode(",", array_map([
                        $this,
                        '_escape'
                    ], array_values($p))) . ")";

                    if ($p != end($params)) {
                        $this->_sql .= ",";
                    }
                }
            }
        }

        if (self::$autorun) {
            return $this->execute(MYSQLI_BOTH);
        }

        return $this->_sql;
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
        $this->_sql = "UPDATE ";
        $this->_queryType = self::UPDATE;

        if (! is_null($table_name) && is_string($table_name)) {
            $this->_sql .= $table_name;

            if (isset($flags['joins']) && is_array($flags['joins'])) {
                $this->_sql .= " " . implode(" ", $flags['joins']);
                unset($flags['joins']);
            }

            $this->_sql .= " SET ";
        } else {
            throw new Exception("Invalid table name datatype", E_ERROR);
        }

        foreach ($params as $f => $p) {
            if ((strpos($f, "`") === false) && (strpos($f, ".") === false) && (strpos($f, "*") === false) && (stripos($f, " as ") === false)) {
                $f = "`{$f}`";
            }

            if (! is_null($p)) {
                $this->_sql .= "$f={$this->_escape($p)},";
            } else {
                $this->_sql .= "$f=NULL,";
            }
        }

        $this->_sql = substr($this->_sql, 0, - 1);

        if (! is_null($where) && is_array($where) && count($where)) {
            $where_str = " WHERE";
            foreach ($where as $x => $w) {
                $where_str .= $this->parseClause($w, $x);
            }
            if (strlen($where_str) > strlen(" WHERE")) {
                $this->_sql .= $where_str;
            }
        }

        if (! is_null($flags) && is_array($flags) && count($flags)) {
            $this->_sql .= $this->flags($flags);
        }

        if (self::$autorun) {
            return $this->execute(MYSQLI_BOTH);
        }

        return $this->_sql;
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
    public function extendedUpdate($to_be_updated, $original, $using, $params)
    {
        $this->_sql = "UPDATE ";
        $this->_queryType = self::EXTENDED_UPDATE;

        if (! is_null($to_be_updated) && ! is_null($original) && ! is_null($using)) {
            $this->_sql .= "$to_be_updated tbu INNER JOIN $original o USING ($using) SET ";
        }

        if (is_array($params) && count($params)) {
            foreach ($params as $param) {
                if ($param != $using) {
                    $this->_sql .= "tbu.`$param` = o.`$param`,";
                }
            }
            $this->_sql = substr($this->_sql, 0, - 1);
        } elseif (is_string($params)) {
            $this->_sql .= "tbu.`$params` = o.`$params`";
        } else {
            throw new Exception("Do not understand datatype " . gettype($params), E_ERROR);
        }

        if (self::$autorun) {
            return $this->execute(MYSQLI_BOTH);
        }

        return $this->_sql;
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
        $this->_sql = null;
        $this->_queryType = self::REPLACE;

        if (! is_null($table_name) && is_string($table_name)) {
            $this->_sql = "REPLACE INTO $table_name " . "(`" . implode("`,`", array_keys($params)) . "`)";
        } else {
            throw (new Exception("Table name is not valid", E_ERROR));
        }

        $this->_sql .= " VALUES (" . implode(",", array_map([
            $this,
            '_escape'
        ], array_values($params))) . ")";

        if (self::$autorun) {
            return $this->execute(MYSQLI_BOTH);
        }

        return $this->_sql;
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
    public function extendedReplace($table_name, $fields, $params)
    {
        $this->_sql = null;
        $this->_queryType = self::EXTENDED_REPLACE;

        if (! is_null($table_name) && is_string($table_name)) {
            $this->_sql = "REPLACE INTO $table_name " . "(`" . implode("`,`", $fields) . "`)";
        } else {
            throw (new Exception("Table name is not valid", E_ERROR));
        }

        if (is_array($params) && count($params)) {
            $this->_sql .= " VALUES ";
            foreach ($params as $p) {
                $this->_sql .= "(" . implode(",", array_map([
                    $this,
                    '_escape'
                ], array_values($p))) . ")";

                if ($p != end($params)) {
                    $this->_sql .= ",";
                }
            }
        }

        if (self::$autorun) {
            return $this->execute(MYSQLI_BOTH);
        }

        return $this->_sql;
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
        $this->_sql = "DELETE";
        $this->_queryType = self::DELETE;

        if (! is_null($fields) && is_array($fields)) {
            $this->_sql .= " " . implode(",", $fields);
        }

        if (! is_null($table_name) && is_string($table_name)) {
            $this->_sql .= " FROM $table_name";
        } else {
            throw (new Exception("Failed to create delete query, no table name", E_ERROR));
        }

        if (! is_null($joins) && is_array($joins) && count($joins)) {
            $this->_sql .= " " . implode(" ", $joins);
        }

        if (! is_null($where) && is_array($where) && count($where)) {
            $where_str = " WHERE";
            foreach ($where as $x => $w) {
                $where_str .= $this->parseClause($w, $x);
            }
            if (strlen($where_str) > strlen(" WHERE")) {
                $this->_sql .= $where_str;
            }
        }

        if (self::$autorun) {
            return $this->execute(MYSQLI_BOTH);
        }

        return $this->_sql;
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
        $this->_sql = null;
        $this->_queryType = self::DROP;

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
            $this->_sql = "DROP" . ($is_tmp ? " TEMPORARY" : "") . " $type IF EXISTS `$name`";
        } else {
            throw new Exception("Table name is invalid", E_ERROR);
        }

        if (self::$autorun) {
            return $this->execute(MYSQLI_BOTH);
        }

        return $this->_sql;
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
        $this->_sql = null;
        $this->_queryType = self::TRUNCATE;

        if (! is_null($table_name) && is_string($table_name)) {
            $this->_sql = "TRUNCATE TABLE $table_name";
        } else {
            throw new Exception("Table name is invalid", E_ERROR);
        }

        if (self::$autorun) {
            return $this->execute(MYSQLI_BOTH);
        }

        return $this->_sql;
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
    public function createTable($table_name, $is_tmp = false, $select = null)
    {
        $this->_queryType = self::CREATE_TABLE;

        if (is_null($select) && ! is_null($this->_sql) && substr($this->_sql, 0, 6) == 'SELECT') {
            $this->_sql = "CREATE" . ($is_tmp ? " TEMPORARY" : "") . " TABLE IF NOT EXISTS $table_name AS ($this->_sql)";
        }
        if (! is_null($table_name) && is_string($table_name) && is_string($select)) {
            $this->_sql = "CREATE" . ($is_tmp ? " TEMPORARY" : "") . " TABLE IF NOT EXISTS $table_name AS ($select)";
        } elseif (! is_null($table_name) && is_string($table_name) && is_array($select)) {
            $this->_sql = "CREATE" . ($is_tmp ? " TEMPORARY" : "") . " TABLE IF NOT EXISTS $table_name (";

            foreach ($select as $field) {
                $default = null;
                if (isset($field['default'])) {
                    $default = (is_null($field['default']) ? "" : " DEFAULT '{$field['default']}'");
                }
                $this->_sql .= "`{$field['field']}` {$field['datatype']}" . $default . (isset($field['option']) ? " {$field['option']}" : '') . ",";
            }
            $this->_sql = substr($this->_sql, 0, - 1) . ")";
        }

        if (self::$autorun) {
            return $this->execute();
        }

        return $this->_sql;
    }

    /**
     * Function to create a table using a stdClass object derived from JSON
     *
     * @param \stdClass $json
     *
     * @example /examples/create_table_json.json
     *
     */
    public function createTableJson($json)
    {
        $this->_queryType = self::CREATE_TABLE;
        $this->_c->select_db($json->schema);

        $this->_sql = "CREATE TABLE IF NOT EXISTS `{$json->name}` (";
        foreach ($json->fields as $field) {
            $this->_sql .= "`{$field->name}` {$field->dataType}";

            if ($field->dataType == 'enum') {
                $this->_sql .= "('" . implode("','", $field->values) . "')";
            }

            if ($field->ai) {
                $this->_sql .= " AUTO_INCREMENT";
            }

            if ($field->nn) {
                $this->_sql .= " NOT NULL";
            } else {
                if ($field->default === null) {
                    $this->_sql .= " DEFAULT NULL";
                } elseif (strlen($field->default)) {
                    $this->_sql .= " DEFAULT '{$field->default}'";
                }
            }

            if ($field != end($json->fields)) {
                $this->_sql .= ",";
            }
        }

        if (isset($json->index) && count($json->index)) {
            foreach ($json->index as $ind) {
                $this->_sql .= ", " . strtoupper($ind->type) . " `{$ind->id}` (`{$ind->ref}`)";
            }
        }

        if (isset($json->constraints) && count($json->constraints)) {
            foreach ($json->constraints as $con) {
                $this->_sql .= ", CONSTRAINT `{$con->id}` " . "FOREIGN KEY (`{$con->local}`) " . "REFERENCES `{$con->schema}`.`{$con->table}` (`{$con->field}`) " . "ON DELETE " . (is_null($con->delete) ? "NO ACTION" : strtoupper($con->delete)) . " " . "ON UPDATE " . (is_null($con->update) ? "NO ACTION" : strtoupper($con->update));
            }
        }

        if (isset($json->unique) && count($json->unique)) {
            $this->_sql .= ", UNIQUE(`" . implode("`,`", $json->unique) . "`)";
        }

        if (isset($json->primary_key) && count($json->primary_key)) {
            $this->_sql .= ", PRIMARY KEY(`" . implode("`,`", $json->primary_key) . "`))";
        } else {
            if (substr($this->_sql, - 1) == ',') {
                $this->_sql = substr($this->_sql, 0, - 1);
            }

            $this->_sql .= ")";
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
    public function alterTable($table_name, $action, $params)
    {
        $this->_queryType = self::ALTER_TABLE;
        $this->_sql = "ALTER TABLE $table_name";
        if ($action == 'add-column') {
            $nn = ($params->nn ? " NOT NULL" : "");
            $default = null;
            if ($params->default === null) {
                $default = " DEFAULT NULL";
            } elseif (strlen($params->default)) {
                $default = " DEFAULT {$this->_escape($params->default)}";
            }
            $this->_sql .= " ADD COLUMN `{$params->name}` {$params->dataType}" . $nn . $default;
        } elseif ($action == 'drop-column') {
            $this->_sql .= " DROP COLUMN ";
            foreach ($params as $col) {
                $this->_sql .= "`{$col->name}`";

                if ($col != end($params)) {
                    $this->_sql .= ",";
                }
            }
        } elseif ($action == 'modify-column') {
            $this->_sql .= " MODIFY COLUMN";
            $nn = ($params->nn ? " NOT NULL" : "");
            $default = null;
            if ($params->default === null) {
                $default = " DEFAULT NULL";
            } elseif (strlen($params->default)) {
                $default = " DEFAULT {$this->_escape($params->default)}";
            }
            $this->_sql .= " `{$params->name}` `{$params->new_name}` {$params->dataType}" . $nn . $default;
        }

        if (self::$autorun) {
            return $this->execute();
        }

        return $this->_sql;
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
    public function fieldExists($table_name, $field_name)
    {
        $fdata = $this->fieldData($table_name);

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
    public function fieldData($table_name, $field = null)
    {
        if (is_null($field)) {
            $res = $this->_c->query("SELECT * FROM $table_name LIMIT 1");
        } elseif (is_array($field)) {
            $res = $this->_c->query("SELECT `" . implode("`,`", $field) . "` FROM $table_name LIMIT 1");
        } elseif (is_string($field)) {
            $res = $this->_c->query("SELECT $field FROM $table_name LIMIT 1");
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
     * @param array $pks
     * @param object $index
     *
     * @return array|string
     */
    public function fieldCheck($field_data, $check, $pks, $index)
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
            $this->log("{$field_data->name} wrong datatype, changing to {$check->dataType}", Logger::NOTICE);
            $ret = " CHANGE COLUMN `{$field_data->name}` `{$check->name}` {$check->dataType}" . "{$nn}{$default}";
        } elseif (! is_null($check->length) && $field_data->length != $check->length) {
            $this->log("{$field_data->name} incorrect size ({$field_data->length} != {$check->length})", Logger::NOTICE);
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
     * @param string $strSchema
     *            The schema to search in
     * @param string $table_name
     *            Table to search for
     *
     * @return integer|boolean Returns number of tables that match if table is found in that schema, otherwise FALSE
     */
    public function tableExists($strSchema, $table_name)
    {
        if (! $this->_c->select_db($strSchema)) {
            fwrite(STDOUT, $this->_c->error . PHP_EOL);
        }
        $sql = "SHOW TABLES LIKE '{$table_name}'";

        if ($res = $this->_c->query($sql)) {
            if (gettype($res) == 'object' && is_a(/**
             * @scrutinizer ignore-type
             */
            $res, 'mysqli_result') && $res->num_rows) {
                return $res->num_rows;
            }
        } else {
            if ($this->_c->errno) {
                fwrite(STDOUT, $this->_c->error . PHP_EOL);
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
                return "'{$this->_c->real_escape_string($val)}'";
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
            $this->log("Unknown object to escape " . get_class($val) . " in SQL string {$this->_sql}", LogLevel::ERROR);
        }

        throw new Exception("Unknown datatype to escape in SQL string {$this->_sql} " . gettype($val), E_ERROR);
    }

    /**
     * Function to retrieve all results
     *
     * @param string $resulttype
     *
     * @return mixed
     */
    public function fetchAll($resulttype = MYSQLI_ASSOC)
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
            $having = " HAVING";
            foreach ($flags['having'] as $x => $h) {
                $having .= $this->parseClause($h, $x);
            }
            if (strlen($having) > strlen(" HAVING")) {
                $ret .= $having;
            }
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
    public function isConstraint(string $strConstraintId)
    {
        $res = $this->_c->query("SELECT * FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_NAME = '{$strConstraintId}'");

        if ($res->num_rows) {
            return true;
        }

        return false;
    }

    /**
     * Function to call logger and log activity
     *
     * @param string $msg
     * @param string $level
     *            [optional]
     * @param array $context
     *            [optional]
     */
    public function log($msg, $level = LogLevel::ERROR, $context = [])
    {
        if ($level == Logger::INFO) {
            $this->_logger->info($msg, $context);
        } elseif ($level == Logger::WARNING) {
            $this->_logger->warning($msg, $context);
        } elseif ($level == Logger::ERROR) {
            $this->_logger->error($msg, $context);
        } elseif ($level == Logger::NOTICE) {
            $this->_logger->notice($msg, $context);
        } elseif ($level == Logger::DEBUG) {
            $this->_logger->debug($msg, $context);
        }
    }

    /**
     * Function to parse where and having clauses
     *
     * @param array $clause
     * @param int $index
     */
    public function parseClause($clause, $index)
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
            case self::BETWEEN:
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

        if ($op == self::IN || $op == self::NOT_IN) {
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
        } elseif ($op == self::BETWEEN) {
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