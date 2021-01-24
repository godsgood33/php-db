<?php
namespace Godsgood33\Php_Db;

use mysqli;
use stdClass;
use Exception;
use InvalidArgumentException;

use Monolog\Logger;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;

use Godsgood33\Php_Db\Exceptions\MissingOrInvalidParam;
use Godsgood33\Php_Db\Exceptions\ConnectError;
use Godsgood33\Php_Db\Exceptions\MissingInterfaceAndMethods;
use Godsgood33\Php_Db\Exceptions\QueryError;

/**
 * Constant to define that we want to return an object
 *
 * @var int
 */
define('MYSQLI_OBJECT', 4);

/**
 * Constant to return consistent date format
 *
 * @var string
 */
define('MYSQL_DATE', 'Y-m-d');

/**
 * Constant to return consistent datetime format
 *
 * @var string
 */
define('MYSQL_DATETIME', 'Y-m-d H:i:s');

/**
 * A generic database class
 *
 * @author Ryan Prather <godsgood33@gmail.com>
 */
class Database
{

    /**
     * Constant defining parameters as a collection of objects
     *
     * @var int
     */
    private const COLLECTION = 1;

    /**
     * Constant defining parameters as an array
     *
     * @var int
     */
    private const ARRAY = 2;

    /**
     * Constant defining parameters as an objects
     *
     * @var int
     */
    private const OBJECT = 3;

    /**
     * Constant defining parameters as a string
     *
     * @var int
     */
    private const STRING = 4;

    /**
     * Constant defining parameters as an array of objects
     */
    private const ARRAY_OBJECT = 5;

    /**
     * Constant defining a SELECT query
     *
     * @var integer
     */
    private const SELECT = 1;

    /**
     * Constant defining a SELECT COUNT query
     *
     * @var integer
     */
    private const SELECT_COUNT = 2;

    /**
     * Constant defining a CREATE TABLE query
     *
     * @var integer
     */
    private const CREATE_TABLE = 3;

    /**
     * Constant defining DROP query
     *
     * @var integer
     */
    private const DROP = 4;

    /**
     * Constant defining DELETE query
     *
     * @var integer
     */
    private const DELETE = 5;

    /**
     * Constant defining INSERT query
     *
     * @var integer
     */
    private const INSERT = 6;

    /**
     * Constant defining REPLACE query
     *
     * @var integer
     */
    private const REPLACE = 7;

    /**
     * Constant defining UPDATE query
     *
     * @var integer
     */
    private const UPDATE = 8;

    /**
     * Constant defining EXTENDED INSERT query
     *
     * @var integer
     */
    private const EXTENDED_INSERT = 9;

    /**
     * Constant defining EXTENDED REPLACE query
     *
     * @var integer
     */
    private const EXTENDED_REPLACE = 10;

    /**
     * Constant defining EXTENDED UPDATE query
     *
     * @var integer
     */
    private const EXTENDED_UPDATE = 11;

    /**
     * Constant defining ALTER TABLE query
     *
     * @var integer
     */
    private const ALTER_TABLE = 12;

    /**
     * Constant defining action for alter table statement
     *
     * @var integer
     */
    private const ADD_COLUMN = 1;

    /**
     * Constant defining action for alter table statement
     *
     * @var integer
     */
    private const DROP_COLUMN = 2;

    /**
     * Constant defining action for alter table statement
     *
     * @var integer
     */
    private const MODIFY_COLUMN = 3;

    /**
     * Constant defining action to add a constraint
     *
     * @var integer
     */
    private const ADD_CONSTRAINT = 4;

    /**
     * Constant defining a TRUNCATE TABLE query
     *
     * @var integer
     */
    private const TRUNCATE = 13;

    /**
     * The mysqli connection
     *
     * @access protected
     * @var mysqli
     */
    protected $_c;

    /**
     * To store the SQL statement
     *
     * @access private
     * @var string
     */
    private $_sql = '';

    /**
     * A variable to store the type of query that is being run
     *
     * @access private
     * @var int
     */
    private $_queryType = null;

    /**
     * The result of the query
     *
     * @access protected
     * @var mixed
     */
    protected $_result = null;

    /**
     * Log level
     *
     * @access private
     * @var int
     */
    private $_logLevel = Logger::ERROR;

    /**
     * Variable to store the logger
     *
     * @access private
     * @var \Monolog\Logger
     */
    private $_logger = null;

    /**
     * Path for the logger to log the file
     *
     * @access private
     * @var string
     */
    private $_logPath = null;

    /**
     * Variable to store the most recent insert ID from an insert query
     *
     * @access protected
     * @var mixed
     */
    protected $_insertId = null;

    /**
     * Constructor
     *
     * @param string $strLogPath
     *            [optional] absolute log path for the log file
     * @param mysqli $dbh
     *            [optional]
     *            [by ref]
     *            mysqli object to perform queries.
     * @param int $intLogLevel
     *            [optional] Level of the log file to output
     *
     * @uses PHP_DB_ENCRYPT bool to define if the password is encrypted
     * @uses PHP_DB_PWD string to store the password
     * @uses PHP_DB_SERVER string to store the database server name or IP
     * @uses PHP_DB_USER string to store the name of the user used to connect to the server
     * @uses PHP_DB_SCHEMA string to store the default schema to connect to
     * @uses PHP_DB_LOG_LEVEL int to store the PSR-4 log level for the library
     * @uses PHP_DB_CLI_LOG bool to store if logs should be echoed to STDOUT
     */
    public function __construct($strLogPath = __DIR__, mysqli &$dbh = null, $intLogLevel = null)
    {
        // set the log file path
        $this->_logPath = $strLogPath;
        if (!file_exists($this->_logPath)) {
            touch($this->_logPath . "/db.log");
        }

        // set the log level
        if (!defined("PHP_DB_LOG_LEVEL") && is_null($intLogLevel)) {
            $this->_logLevel = Logger::ERROR;
        } elseif (!is_null($intLogLevel)) {
            $this->_logLevel = $intLogLevel;
        } elseif (defined('PHP_DB_LOG_LEVEL')) {
            $this->_logLevel = PHP_DB_LOG_LEVEL;
        }

        // open the log handler
        $this->_logger = new Logger('db', [
            new StreamHandler(realpath($this->_logPath . "/db.log"), $this->_logLevel)
        ]);

        // check to see if we are operating in a CLI and if the user wants log data output to the terminal
        if (PHP_SAPI == 'cli' && defined('PHP_DB_CLI_LOG') && PHP_DB_CLI_LOG) {
            $stream = new StreamHandler(STDOUT, $this->_logLevel);
            $stream->setFormatter(new LineFormatter("%datetime% %level_name% %message%" . PHP_EOL, "H:i:s.u"));
            $this->_logger->pushHandler($stream);
        }

        // check to see if a connection was passed and all defined constants are present to establish a connection
        if (! is_null($dbh) && is_a($dbh, 'mysqli')) {
            $this->_logger->debug("Connecting through existing connection");
            $this->_c = $dbh;
        } elseif (!defined('PHP_DB_SERVER') || !defined('PHP_DB_USER') || !defined('PHP_DB_PWD') || !defined('PHP_DB_SCHEMA')) {
            $this->_logger->critical("Missing essential defined constants");
            throw new MissingOrInvalidParam("Please create and include a constant file with the following constants defining your DB connection (PHP_DB_SERVER, PHP_DB_USER, PHP_DB_PWD, PHP_DB_SCHEMA)", E_USER_ERROR);
        } elseif (defined('PHP_DB_ENCRYPT') && (!defined('PHP_DB_ENCRYPT_ALGORITHM') || !defined('PHP_DB_ENCRYPT_SALT'))) {
            $this->_logger->critical("Missing essential encryption constants");
            throw new MissingOrInvalidParam("Missing required PHP_DB_ENCRYPT_ALGORITHM or PHP_DB_ENCRYPT_SALT constants");
        }

        // check to see if the password is encrypted and decrypt if it is
        if (defined('PHP_DB_ENCRYPT') && PHP_DB_ENCRYPT) {
            $this->_logger->debug("Decrypting password");
            $pwd = $this->decrypt(PHP_DB_PWD);
        } else {
            $pwd = PHP_DB_PWD;
        }

        // open the connection
        $this->_c = new mysqli(PHP_DB_SERVER, PHP_DB_USER, $pwd, PHP_DB_SCHEMA);

        // check for a connection error and throw an error if there is one
        if ($this->_c->connect_errno) {
            $this->_logger->error("Error connecting to database {$this->_c->connect_error}");
            throw new ConnectError("Could not create database class due to error {$this->_c->connect_error}", E_ERROR);
        }

        // update the logger with the connection details
        $this->_logger->info("Database connected");
        $this->_logger->debug("Connection details:", [
            'Server' => PHP_DB_SERVER,
            'User'   => PHP_DB_USER,
            'Schema' => PHP_DB_SCHEMA
        ]);

        $this->setVar("time_zone", "+00:00");
        $this->setVar("sql_mode", "");
    }

    /**
     * Function to make sure that the database is connected
     *
     * @return bool
     *
     * @throws ConnectError
     */
    public function isConnected(): bool
    {
        $this->_logger->info("Checking for live connection");

        if (is_a($this->_c, 'mysqli')) {
            $ret = $this->_c->ping();
        } else {
            throw new ConnectError("Connection lost");
        }

        return $ret;
    }

    /**
     * Setter function for _logger
     *
     * @param Logger $log
     *
     * @return bool
     */
    public function setLogger(Logger $log): bool
    {
        // set the logger
        $this->_logger->debug("Setting logger");
        $this->_logger = $log;
        return true;
    }

    /**
     * Getter function for _logLevel
     *
     * @return int
     */
    public function getLogLevel(): int
    {
        $level = $this->_logLevel;

        $this->_logger->debug("Getting log level ({$level})");
        return $level;
    }

    /**
     * Getter function for _queryType
     *
     * @return int
     */
    public function getQueryType(): int
    {
        $this->_logger->debug("Getting query type");
        return $this->_queryType;
    }

    /**
     * Setter function for _queryType
     *
     * @param int $qt
     *      Class constants that store query type
     *
     * @return Database
     */
    private function setQueryType(int $qt): Database
    {
        $this->_logger->debug("Setting query type");
        $this->_queryType = $qt;

        return $this;
    }

    /**
     * Magic method to convert the class to a string represented by the SQL query
     *
     * @return string|null
     */
    public function __toString(): string
    {
        $this->_logger->notice("__toString");
        $this->_logger->debug($this->_sql);
        return $this->_sql;
    }

    /**
     * Function to return the currently selected database schema
     *
     * @return string|bool
     *      Returns the string name of the selected schema or FALSE if none selected
     */
    public function getSchema()
    {
        if ($res = $this->_c->query("SELECT DATABASE()")) {
            $row = $res->fetch_row();

            $this->_logger->debug("Getting schema {$row[0]}");
            return $row[0];
        }

        return false;
    }

    /**
     * Function to set schema
     *
     * @param string $strSchema
     *
     * @return bool
     *
     * @throws QueryError
     */
    public function setSchema(string $strSchema): bool
    {
        $this->_logger->info("Setting schema to {$strSchema}");
        if (! $this->_c->select_db($strSchema)) {
            $this->_logger->error("Unknown schema {$strSchema}");
            throw new QueryError("Unknown database schema $strSchema");
        }
        return true;
    }

    /**
     * Method to set a MYSQL variable
     *
     * @param string $strName
     *      Name of the SQL variable to set
     * @param string $strVal
     *      Value for the variable
     *
     * @return bool
     */
    public function setVar(string $strName, ?string $strVal): bool
    {
        if (empty($strName)) {
            $this->_logger->debug("name is blank", [
                'name'  => $strName
            ]);
            return false;
        }

        $this->_logger->debug("Setting {$strName} = '{$strVal}'");

        if ($this->_c->real_query("SET $strName = {$this->_escape($strVal)}")) {
            $this->_logger->info("Var set");
            return true;
        } else {
            $this->_logger->error("Failed to set variable {$this->_c->error}");
            return false;
        }
    }

    /**
     * Function to execute the statement
     *
     * @param int $return
     *            [optional]
     *            MYSQLI constant to control what is returned from the mysqli_result object
     * @param string $strSql
     *            [optional]
     *            Optional SQL query
     *
     * @throws QueryError
     * @throws ConnectError
     *
     * @return mixed
     */
    public function execute(int $return = MYSQLI_OBJECT, ?string $strSql = null)
    {
        if (! is_null($strSql)) {
            $this->_sql = $strSql;
        }

        $this->_result = false;
        $this->_insertId = null;

        $this->isConnected();

        $this->_logger->info("Executing {$this->_queryType} query");
        $this->_logger->debug($this->_sql);

        try {
            if (in_array($this->_queryType, [
                self::SELECT,
                self::SELECT_COUNT
            ])) {
                $this->_result = $this->_c->query($this->_sql);
                if ($this->_c->error) {
                    $this->_logger->error("There is an error {$this->_c->error}");
                    $this->_logger->debug("Errored on query", [$this->_sql]);
                    throw new QueryError("There was an error {$this->_c->error}", E_ERROR);
                }
            } else {
                $this->_result = $this->_c->real_query($this->_sql);
                if ($this->_c->errno) {
                    $this->_logger->error("There was an error {$this->_c->error}");
                    $this->_logger->debug("Errored on query", [$this->_sql]);
                    throw new QueryError("There was an error {$this->_c->error}", E_ERROR);
                }
            }

            $this->_logger->debug("Checking for query results");
            $this->_result = $this->checkResults($return);
        } catch (QueryError $e) {
            $this->_logger->error($e);
        }

        return $this->_result;
    }

    /**
     * Function to check the results and return what is expected
     *
     * @param mixed $returnType
     *            [optional]
     *            Optional return mysqli_result return type
     *
     * @return mixed
     */
    protected function checkResults(int $returnType)
    {
        $res = null;

        // check the sql results and process appropriately
        if (in_array($this->_queryType, [Database::CREATE_TABLE, Database::ALTER_TABLE, Database::TRUNCATE, Database::DROP])) {
            $res = $this->_result;
        } elseif (in_array($this->_queryType, [Database::INSERT, Database::EXTENDED_INSERT, Database::DELETE, Database::UPDATE, Database::EXTENDED_UPDATE, Database::REPLACE, Database::EXTENDED_REPLACE, Database::DELETE])) {
            $res = $this->_c->affected_rows;

            if (in_array($this->_queryType, [Database::INSERT, Database::REPLACE, Database::EXTENDED_INSERT])) {
                $this->_insertId = $this->_c->insert_id;
            }
        } elseif ($this->_queryType == Database::SELECT_COUNT) {
            if (! is_a($this->_result, 'mysqli_result')) {
                $this->_logger->error("Error with return on query");
                return null;
            }

            if ($this->_result->num_rows == 1) {
                $row = $this->_result->fetch_assoc();
                if (isset($row['count'])) {
                    $this->_logger->debug("Returning SELECT_COUNT query", [
                        'count' => $row['count']
                    ]);
                    $res = $row['count'];
                }
            } elseif ($this->_result->num_rows > 1) {
                $this->_logger->debug("Returning SELECT_COUNT query", [
                    'count' => $this->_result->num_rows
                ]);
                $res = $this->_result->num_rows;
            }

            mysqli_free_result($this->_result);
        } else {
            $method = "mysqli_fetch_object";
            if ($returnType == MYSQLI_ASSOC) {
                $method = "mysqli_fetch_assoc";
            } elseif ($returnType == MYSQLI_NUM) {
                $method = "mysqli_fetch_array";
            }

            if (is_a($this->_result, 'mysqli_result')) {
                if ($this->_result->num_rows > 1) {
                    $res = [];
                    while ($row = call_user_func($method, $this->_result)) {
                        $res[] = $row;
                    }
                } else {
                    $res = call_user_func($method, $this->_result);
                }
            } else {
                $this->_logger->error("Error with return on query");
                return null;
            }
        }

        if ($this->_c->error) {
            $this->_logger->error("Encountered a SQL error", ['error' => $this->_c->error, 'list' => $this->_c->error_list]);
            $this->_logger->debug("Debug", ['debug' => debug_backtrace()]);
            return null;
        }

        return $res;
    }

    /**
     * Function to pass through calling the query function (used for backwards compatibility and for more complex queries that aren't currently supported)
     * Nothing is escaped
     *
     * @param string $strSql
     *            [optional]
     *            Optional query to pass in and execute
     *
     * @return \mysqli_result|bool
     */
    public function query(?string $strSql = null)
    {
        return is_null($strSql) ? $this->_c->query($this->_sql) : $this->_c->query($strSql);
    }

    /**
     * A function to build a select query
     *
     * @param string $strTableName
     *            The table to query
     * @param array|string $fields
     *            [optional]
     *            Optional array of fields to return (defaults to '*')
     * @param array:DBWhere|DBWhere $arrWhere
     *            [optional] Where clause data
     * @param array $arrFlags
     *            [optional]
     *            Optional 2-dimensional array to allow other flags
     *
     * @see Database::flags()
     *
     * @throws Exception
     * @throws InvalidArgumentException
     *
     * @return mixed
     *
     * @uses PHP_DB_AUTORUN bool to decide if the statement should be auto-committed and the results returned instead of the statement
     */
    public function select(string $strTableName, $fields = null, $arrWhere = [], ?array $arrFlags = [])
    {
        $this->_sql = null;
        $this->setQueryType(self::SELECT);

        // starting building the query
        if ($this->checkTableName($strTableName)) {
            $this->_logger->debug("Starting SELECT query of {$strTableName}", [
                'fields' => $this->fields($fields)
            ]);
            $this->_sql = "SELECT " . $this->fields($fields) . " FROM $strTableName";
        }

        // add in any joins
        if (isset($arrFlags['joins']) && is_array($arrFlags['joins']) && count($arrFlags['joins'])) {
            $this->_logger->debug("Adding joins", [
                'joins' => implode(' ', $arrFlags['joins'])
            ]);
            $this->_sql .= " " . implode(" ", $arrFlags['joins']);
        } else {
            $this->_logger->debug("No joins");
        }

        // parse the where clauses
        $where = $this->parseClause($arrWhere);

        if (! is_null($where) && is_array($where) && count($where)) {
            $where_str = " WHERE";
            $this->_logger->debug("Parsing where clause and adding to query");
            foreach ($where as $x => $w) {
                if ($x > 0) {
                    $where_str .= " {$w->sqlOperator}";
                }
                $where_str .= $w;
            }
            if (strlen($where_str) > strlen(" WHERE")) {
                $this->_sql .= $where_str;
            }
        }

        // search for any other flags (order, having, group)
        if (is_array($arrFlags) && count($arrFlags)) {
            $this->_logger->debug("Parsing flags and adding to query", $arrFlags);
            $this->_sql .= $this->flags($arrFlags);
        }

        if (defined("PHP_DB_AUTORUN") && PHP_DB_AUTORUN) {
            return $this->execute();
        }

        return $this->_sql;
    }

    /**
     * Function to build a query to check the number of rows in a table
     *
     * @param string $strTableName
     *            The table to query
     * @param array:DBWhere|DBWhere $arrWhere
     *            [optional]
     *            Optional 2-dimensional array to build where clause
     * @param array $arrFlags
     *            [optional]
     *            Optional 2-dimensional array to add flags
     *
     * @see Database::flags()
     *
     * @return string|int|NULL
     *
     * @throws InvalidArgumentException
     *
     * @uses PHP_DB_AUTORUN bool to decide if the statement should be auto-committed and the results returned instead of the statement
     */
    public function selectCount(string $strTableName, $arrWhere = [], ?array $arrFlags = [])
    {
        $this->_sql = null;
        $this->setQueryType(self::SELECT_COUNT);

        // start building query
        if ($this->checkTableName($strTableName)) {
            $this->_sql = "SELECT COUNT(1) AS 'count' FROM $strTableName";
        }

        // add in any joins
        if (isset($arrFlags['joins']) && is_array($arrFlags['joins'])) {
            $this->_sql .= " " . implode(" ", $arrFlags['joins']);
        }

        // parse where clauses
        $where = $this->parseClause($arrWhere);

        if (! is_null($where) && is_array($where) && count($where)) {
            $where_str = " WHERE";
            $this->_logger->debug("Parsing where clause and adding to query");
            foreach ($where as $x => $w) {
                if ($x > 0) {
                    $where_str .= " {$w->sqlOperator}";
                }
                $where_str .= $w;
            }
            if (strlen($where_str) > strlen(" WHERE")) {
                $this->_sql .= $where_str;
            }
        }

        // add in additional flags (group, having, order)
        if (is_array($arrFlags) && count($arrFlags)) {
            $this->_sql .= $this->flags($arrFlags);
        }

        if (defined("PHP_DB_AUTORUN") && PHP_DB_AUTORUN) {
            return $this->execute();
        }

        return $this->_sql;
    }

    /**
     * Function to build an insert query statement
     *
     * @param string $strTableName
     * @param mixed $arrParams
     * @param bool $blnToIgnore
     *
     * @return string|NULL
     *
     * @throws InvalidArgumentException
     * @throws MissingInterfaceAndMethods
     *
     * @uses PHP_DB_AUTORUN bool to decide if the statement should be auto-committed and the results returned instead of the statement
     */
    public function insert(string $strTableName, $params, bool $blnToIgnore = false)
    {
        $this->_sql = null;
        $this->setQueryType(self::INSERT);

        // start building query
        if ($this->checkTableName($strTableName)) {
            $this->_sql = "INSERT" . ($blnToIgnore ? " IGNORE" : "") . " INTO {$strTableName}";
        }

        $paramType = $this->checkParamType($params);

        // add in field parameters and values
        if ($paramType == self::ARRAY) {
            $keys = array_keys($params);
            $vals = array_values($params);
            $this->_sql .= " (`" . implode("`,`", $keys) . "`)";
            $this->_sql .= " VALUES (" . implode(",", array_map([
                $this,
                '_escape'
            ], $vals)) . ")";
        } elseif ($paramType == self::STRING) {
            $this->_sql .= " {$params}";
        } elseif ($paramType == self::OBJECT) {
            $arr = $params->insert();
            $keys = array_keys($arr);
            $vals = array_values($arr);
            $this->_sql .= " (`" . implode("`,`", $keys) . "`) VALUES ";
            $this->_sql .= "(" . implode(",", array_map([$this, '_escape'], $vals)) . ")";
        } else {
            throw new InvalidArgumentException("Invalid type passed to insert " . gettype($params));
        }

        if (defined("PHP_DB_AUTORUN") && PHP_DB_AUTORUN) {
            return $this->execute();
        }

        return $this->_sql;
    }

    /**
     * Function to create an extended insert query statement
     *
     * @param string $strTableName
     *            The table name that the data is going to be inserted on
     * @param array $arrFields
     *            An array of field names that each value represents
     * @param mixed $params
     *            An array of array of values or a string with a SELECT statement to populate the insert with
     * @param bool $blnToIgnore
     *            [optional]
     *            Boolean to decide if we need to use the INSERT IGNORE INTO syntax
     *
     * @return NULL|string Returns the SQL if self::$autorun is set to false, else it returns the output from running.
     *
     * @throws InvalidArgumentException
     * @throws MissingOrInvalidParam
     * @throws MissingInterfaceAndMethods
     *
     * @uses PHP_DB_AUTORUN bool to decide if the statement should be auto-committed and the results returned instead of the statement
     */
    public function extendedInsert(string $strTableName, array $arrFields, $params, bool $blnToIgnore = false)
    {
        $this->_sql = null;
        $this->setQueryType(self::EXTENDED_INSERT);

        // start building query
        if ($this->checkTableName($strTableName)) {
            $this->_sql = "INSERT ".
                ($blnToIgnore ? "IGNORE " : "").
                "INTO $strTableName ".
                "(`".implode("`,`", $arrFields)."`)";
        }

        $paramType = $this->checkParamType($params);
        $this->_sql .= " VALUES ";

        if ($paramType == self::COLLECTION || $paramType == self::ARRAY_OBJECT) {
            foreach ($params as $p) {
                $key_value = $p->insert();
                $this->_sql .= "(" . implode(",", array_map([$this, '_escape'], array_values($key_value))) . "),";
            }

            $this->_sql = substr($this->_sql, 0, -1);
        } elseif ($paramType == self::OBJECT) {
            $key_value = $params->insert();
            $this->_sql .= "(" . implode(",", array_map([$this, '_escape'], array_values($key_value))) . "),";
        } elseif ($paramType == self::ARRAY) {
            foreach ($params as $p) {
                if (count($p) != count($arrFields)) {
                    $this->_logger->emergency("Inconsistent number of fields to values in extendedInsert", [
                        $p,
                        debug_backtrace()
                    ]);
                    throw new MissingOrInvalidParam("Inconsistent number of fields in fields and values in extendedInsert " . print_r($p, true));
                }
                $this->_sql .= "(" . implode(",", array_map([$this, '_escape'], array_values($p))) . ")";

                if ($p != end($params)) {
                    $this->_sql .= ",";
                }
            }
        } else {
            throw new InvalidArgumentException("Invalid param type ".gettype($params));
        }

        if (defined("PHP_DB_AUTORUN") && PHP_DB_AUTORUN) {
            return $this->execute();
        }

        return $this->_sql;
    }

    /**
     * Build a statement to update a table
     *
     * @param string $strTableName
     *            The table name to update
     * @param mixed $arrParams
     *            Name/value pairs of the field name and value
     * @param array:DBWhere|DBWhere $arrWhere
     *            [optional]
     *            DBWhere clauses
     * @param array $arrFlags
     *            [optional]
     *            Two-dimensional array to create other flag options (joins, order, and group)
     *
     * @see Database::flags()
     *
     * @return NULL|string
     *
     * @throws InvalidArgumentException
     * @throws MissingOrInvalidParam
     * @throws MissingInterfaceAndMethods
     *
     * @uses PHP_DB_AUTORUN bool to decide if the statement should be auto-committed and the results returned instead of the statement
     */
    public function update(string $strTableName, $params, $arrWhere = [], ?array $arrFlags = [])
    {
        $this->_sql = "UPDATE ";
        $this->setQueryType(self::UPDATE);

        if ($this->checkTableName($strTableName)) {
            $this->_sql .= $strTableName;

            if (isset($arrFlags['joins']) && is_array($arrFlags['joins'])) {
                $this->_sql .= " " . implode(" ", $arrFlags['joins']);
                unset($arrFlags['joins']);
            }

            $this->_sql .= " SET ";
        }

        $paramType = $this->checkParamType($params);

        if ($paramType == self::ARRAY) {
            $keys = array_keys($params);
            foreach ($params as $f => $p) {
                $field = $f;
                if ((strpos($f, "`") === false) &&
                    (strpos($f, ".") === false) &&
                    (strpos($f, "*") === false) &&
                    (stripos($f, " as ") === false)
                ) {
                    $field = "`{$f}`";
                }

                if (! is_null($p)) {
                    $this->_sql .= "$field={$this->_escape($p)}";
                } else {
                    $this->_sql .= "$field=NULL";
                }

                if ($f != end($keys)) {
                    $this->_sql .= ",";
                }
            }
        } elseif ($paramType == self::OBJECT) {
            $key_value = $params->update();
            $fields = array_keys($key_value);
            $values = array_map([$this, '_escape'], array_values($key_value));
            foreach ($fields as $x => $f) {
                if ($x > 0) {
                    $this->_sql .= ",";
                }
                $this->_sql .= "`{$f}`={$values[$x]}";
            }
        } else {
            throw new MissingOrInvalidParam("No fields to update");
        }

        $where = $this->parseClause($arrWhere);

        if (! is_null($where) && is_array($where) && count($where)) {
            $where_str = " WHERE";
            $this->_logger->debug("Parsing where clause and adding to query");
            foreach ($where as $x => $w) {
                if ($x > 0) {
                    $where_str .= " {$w->sqlOperator}";
                }
                $where_str .= $w;
            }
            if (strlen($where_str) > strlen(" WHERE")) {
                $this->_sql .= $where_str;
            }
        }

        if (! is_null($arrFlags) && is_array($arrFlags) && count($arrFlags)) {
            $this->_sql .= $this->flags($arrFlags);
        }

        if (defined("PHP_DB_AUTORUN") && PHP_DB_AUTORUN) {
            return $this->execute();
        }

        return $this->_sql;
    }

    /**
     * Function to offer an extended updated functionality by using two different tables.
     *
     * @param string $strTableToUpdate
     *            The table that you want to update (alias 'tbu' is automatically added)
     * @param string $strOriginalTable
     *            The table with the data you want to overwrite to_be_updated table (alias 'o' is automatically added)
     * @param string $strLinkField
     *            The common index value between them that will join the fields
     * @param array|string $arrParams
     *            If string only a single field is updated (tbu.$params = o.$params)
     *            If array each element in the array is a field to be updated (tbu.$param = o.$param)
     *
     * @return mixed
     *
     * @throws InvalidArgumentException
     *
     * @uses PHP_DB_AUTORUN bool to decide if the statement should be auto-committed and the results returned instead of the statement
     */
    public function extendedUpdate(string $strTableToUpdate, string $strOriginalTable, string $strLinkField, array $arrParams)
    {
        $this->_sql = "UPDATE ";
        $this->setQueryType(self::EXTENDED_UPDATE);

        if (! is_null($strTableToUpdate) && ! is_null($strOriginalTable) && ! is_null($strLinkField)) {
            $this->_sql .= "$strTableToUpdate tbu INNER JOIN $strOriginalTable o USING ($strLinkField) SET ";
        }

        if (is_array($arrParams) && count($arrParams)) {
            foreach ($arrParams as $param) {
                if ($param != $strLinkField) {
                    $this->_sql .= "tbu.`$param` = o.`$param`,";
                }
            }
            $this->_sql = substr($this->_sql, 0, - 1);
        } elseif (is_string($arrParams)) {
            $this->_sql .= "tbu.`$arrParams` = o.`$arrParams`";
        } else {
            throw new InvalidArgumentException("Do not understand datatype " . gettype($arrParams), E_ERROR);
        }

        if (defined("PHP_DB_AUTORUN") && PHP_DB_AUTORUN) {
            return $this->execute();
        }

        return $this->_sql;
    }

    /**
     * Function to build a replace query
     *
     * @param string $strTableName
     *            The table to update
     * @param mixed $arrParams
     *            Name/value pair to insert
     *
     * @return NULL|string
     *
     * @uses PHP_DB_AUTORUN bool to decide if the statement should be auto-committed and the results returned instead of the statement
     */
    public function replace(string $strTableName, $params)
    {
        $this->_sql = null;
        $this->setQueryType(self::REPLACE);

        if ($this->checkTableName($strTableName)) {
            $this->_sql = "REPLACE INTO $strTableName ";
        }

        $paramType = $this->checkParamType($params);

        if ($paramType == self::ARRAY) {
            $keys = array_keys($params);
            $vals = array_values($params);

            $this->_sql .= "(`" . implode("`,`", $keys) . "`)";
            $this->_sql .= " VALUES (" . implode(",", array_map([
                $this,
                '_escape'
            ], array_values($vals))) . ")";
        } elseif ($paramType == self::OBJECT) {
            $key_value = $params->replace();
            $this->_sql .= "(`" . implode("`,`", array_keys($key_value)) . "`) VALUES ";
            $this->_sql .= "(" . implode(",", array_map([$this, '_escape'], array_values($key_value))) . ")";
        } else {
            throw new MissingOrInvalidParam('Arrays or Objects that implement DBInterface are the only valid types for replace');
        }

        if (defined("PHP_DB_AUTORUN") && PHP_DB_AUTORUN) {
            return $this->execute();
        }

        return $this->_sql;
    }

    /**
     * Function to build an extended replace statement
     *
     * @param string $strTableName
     *            Table name to update
     * @param array $arrFields
     *            Array of fields
     * @param mixed $arrParams
     *            Two-dimensional array of values
     *
     * @return NULL|string
     *
     * @throws InvalidArgumentException
     *
     * @uses PHP_DB_AUTORUN bool to decide if the statement should be auto-committed and the results returned instead of the statement
     */
    public function extendedReplace(string $strTableName, array $arrFields, $params)
    {
        $this->_sql = null;
        $this->setQueryType(self::EXTENDED_REPLACE);

        if (! is_array($arrFields) || ! count($arrFields)) {
            throw new InvalidArgumentException("Error with the field type");
        }

        if ($this->checkTableName($strTableName)) {
            $this->_sql = "REPLACE INTO $strTableName " . "(`" . implode("`,`", $arrFields) . "`)";
        }

        $paramType = $this->checkParamType($params);
        $this->_sql .= " VALUES ";

        if ($paramType == self::ARRAY) {
            foreach ($params as $p) {
                $this->_sql .= "(" . implode(",", array_map([
                    $this,
                    '_escape'
                ], array_values($p))) . ")";

                if ($p != end($params)) {
                    $this->_sql .= ",";
                }
            }
        } elseif ($paramType == self::COLLECTION || $paramType == self::ARRAY_OBJECT) {
            foreach ($params as $p) {
                $key_value = $p->replace();
                $this->_sql .= "(" . implode(",", array_map([
                    $this,
                    '_escape'
                ], array_values($key_value))) . ")";

                if ($p != end($params)) {
                    $this->_sql .= ",";
                }
            }
        }

        if (defined("PHP_DB_AUTORUN") && PHP_DB_AUTORUN) {
            return $this->execute();
        }

        return $this->_sql;
    }

    /**
     * Function to build a delete statement
     *
     * @param string $strTableName
     *            Table name to act on
     * @param array $arrFields
     *            [optional]
     *            Optional list of fields to delete (used when including multiple tables)
     * @param array:DBWhere|DBWhere $arrWhere
     *            [optional]
     *            Optional where clauses to use
     * @param array $arrJoins
     *            [optional]
     *            Optional 2-dimensional array to add other flags
     *
     * @see Database::flags()
     *
     * @return string|NULL
     *
     * @throws InvalidArgumentException
     *
     * @uses PHP_DB_AUTORUN bool to decide if the statement should be auto-committed and the results returned instead of the statement
     */
    public function delete(string $strTableName, ?array $arrFields = [], $arrWhere = [], ?array $arrJoins = [])
    {
        $this->_sql = "DELETE";
        $this->setQueryType(self::DELETE);

        $this->_logger->debug("Deleting table data");

        if (! is_null($arrFields) && is_array($arrFields) && count($arrFields)) {
            $this->_sql .= " " . implode(",", $arrFields);
        }

        if ($this->checkTableName($strTableName)) {
            $this->_sql .= " FROM $strTableName";
        }

        if (! is_null($arrJoins) && is_array($arrJoins) && count($arrJoins)) {
            $this->_sql .= " " . implode(" ", $arrJoins);
        }

        $where = $this->parseClause($arrWhere);

        if (! is_null($where) && is_array($where) && count($where)) {
            $where_str = " WHERE";
            $this->_logger->debug("Parsing where clause and adding to query");
            foreach ($where as $x => $w) {
                if ($x > 0) {
                    $where_str .= " {$w->sqlOperator}";
                }
                $where_str .= $w;
            }
            if (strlen($where_str) > strlen(" WHERE")) {
                $this->_sql .= $where_str;
            }
        }

        if (defined("PHP_DB_AUTORUN") && PHP_DB_AUTORUN) {
            return $this->execute();
        }

        return $this->_sql;
    }

    /**
     * Function to build a drop table statement (automatically executes)
     *
     * @param string $strTableName
     *            Table to drop
     * @param string $strType
     *            [optional]
     *            Type of item to drop ('table', 'view') (defaulted to 'table')
     * @param bool $blnIsTemp
     *            [optional]
     *            Optional bool if this is a temporary table
     *
     * @return string|NULL
     *
     * @throws InvalidArgumentException
     *
     * @uses PHP_DB_AUTORUN bool to decide if the statement should be auto-committed and the results returned instead of the statement
     */
    public function drop(string $strTableName, string $strType = 'table', bool $blnIsTemp = false)
    {
        $this->_sql = null;
        $this->setQueryType(self::DROP);

        switch ($strType) {
            case 'table':
                $strType = 'TABLE';
                break;
            case 'view':
                $strType = 'VIEW';
                break;
            default:
                throw new InvalidArgumentException("Invalid type " . gettype($strType), E_ERROR);
        }

        if ($this->checkTableName($strTableName)) {
            $this->_sql = "DROP" . ($blnIsTemp ? " TEMPORARY" : "") . " $strType IF EXISTS `{$strTableName}`";
        }

        if (defined("PHP_DB_AUTORUN") && PHP_DB_AUTORUN) {
            return $this->execute();
        }

        return $this->_sql;
    }

    /**
     * Function to build a truncate table statement (automatically executes)
     *
     * @param string $strTableName
     *            Table to truncate
     *
     * @return string|NULL
     *
     * @throws InvalidArgumentException
     *
     * @uses PHP_DB_AUTORUN bool to decide if the statement should be auto-committed and the results returned instead of the statement
     */
    public function truncate(string $strTableName)
    {
        $this->_sql = null;
        $this->setQueryType(self::TRUNCATE);

        if ($this->checkTableName($strTableName)) {
            $this->_sql = "TRUNCATE TABLE $strTableName";
        }

        if (defined("PHP_DB_AUTORUN") && PHP_DB_AUTORUN) {
            return $this->execute();
        }

        return $this->_sql;
    }

    /**
     * Function to build a create temporary table statement
     *
     * @param string $strTableName
     *            Name to give the table when creating
     * @param bool $blnIsTemp
     *            [optional]
     *            Optional bool to make the table a temporary table
     * @param mixed $strSelect
     *            [optional]
     *            Optional parameter if null uses last built statement
     *            If string, will be made the SQL statement executed to create the table
     *            If array, 2-dimensional array with "field", "datatype" values to build table fields
     *
     * @return NULL|string
     *
     * @uses PHP_DB_AUTORUN bool to decide if the statement should be auto-committed and the results returned instead of the statement
     */
    public function createTable(string $strTableName, bool $blnIsTemp = false, $strSelect = null)
    {
        $this->setQueryType(self::CREATE_TABLE);

        if (is_null($strSelect) && ! is_null($this->_sql) && substr($this->_sql, 0, 6) == 'SELECT') {
            $this->_sql = "CREATE" . ($blnIsTemp ? " TEMPORARY" : "") . " TABLE IF NOT EXISTS $strTableName AS ($this->_sql)";
        } elseif ($this->checkTableName($strTableName) && is_string($strSelect)) {
            $this->_sql = "CREATE" . ($blnIsTemp ? " TEMPORARY" : "") . " TABLE IF NOT EXISTS $strTableName AS ($strSelect)";
        } elseif ($this->checkTableName($strTableName) && is_array($strSelect)) {
            $this->_sql = "CREATE" . ($blnIsTemp ? " TEMPORARY" : "") . " TABLE IF NOT EXISTS $strTableName (";

            foreach ($strSelect as $field) {
                $default = null;
                if (is_a($field, 'Godsgood33\Php_Db\DBCreateTable')) {
                    $this->_sql .= (string) $field . ",";
                } elseif (is_array($field)) {
                    if (isset($field['default'])) {
                        $default = (is_null($field['default']) ? "" : " DEFAULT '{$field['default']}'");
                    }
                    $this->_sql .= "`{$field['field']}` {$field['datatype']}" . $default . (isset($field['option']) ? " {$field['option']}" : '') . ",";
                }
            }
            $this->_sql = substr($this->_sql, 0, - 1) . ")";
        }

        if (defined("PHP_DB_AUTORUN") && PHP_DB_AUTORUN) {
            return $this->execute();
        }

        return $this->_sql;
    }

    /**
     * Function to create a table using a stdClass object derived from JSON
     *
     * @param stdClass $json
     * @param bool $blnDropFirst bool to decide if you want to drop the table first
     *
     * @example /examples/create_table_json.json
     *
     * @uses PHP_DB_AUTORUN bool to decide if the statement should be auto-committed and the results returned instead of the statement
     */
    public function createTableJson($json, bool $blnDropFirst = false)
    {
        $this->setQueryType(self::CREATE_TABLE);
        $this->_c->select_db($json->schema);

        if ($blnDropFirst) {
            $this->drop($json->name);
            if (!defined('PHP_DB_AUTORUN') || !PHP_DB_AUTORUN) {
                $this->execute();
            }
        }

        $this->_sql = "CREATE TABLE IF NOT EXISTS `{$json->name}` (";
        foreach ($json->fields as $field) {
            $this->_sql .= "`{$field->name}` {$field->dataType}";

            if ($field->dataType == 'enum' && isset($field->values)) {
                $this->_sql .= "('" . implode("','", $field->values) . "')";
            }

            if (isset($field->ai) && $field->ai) {
                $this->_sql .= " AUTO_INCREMENT";
            }

            if (isset($field->nn) && $field->nn) {
                $this->_sql .= " NOT NULL";
            } elseif (isset($field->default)) {
                if (strtolower($field->default) == 'null') {
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
                $ref = null;
                if (is_array($ind->ref)) {
                    $ref = "";
                    foreach ($ind->ref as $r) {
                        $ref .= "`{$r}` ASC,";
                    }
                    $ref = substr($ref, 0, -1);
                } elseif (is_string($ind->ref)) {
                    $ref = $ind->ref;
                }
                if (!is_null($ref)) {
                    $this->_sql .= ", " . strtoupper($ind->type) . " `{$ind->id}` (`{$ref}`)";
                }
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

        if (defined("PHP_DB_AUTORUN") && PHP_DB_AUTORUN) {
            return $this->execute();
        }

        return $this->_sql;
    }

    /**
     * Method to add a column to the database (only one at a time!)
     *
     * @param string $strTableName
     * @param stdClass $params
     *
     * @return string|mixed
     *
     * @throws InvalidArgumentException
     *
     * @uses PHP_DB_AUTORUN bool to decide if the statement should be auto-committed and the results returned instead of the statement
     */
    public function addColumn(string $strTableName, stdClass $params)
    {
        $this->setQueryType(self::ALTER_TABLE);
        $this->checkTableName($strTableName);

        $this->_sql = "ALTER TABLE {$strTableName} ADD COLUMN";

        if (!self::checkObject($params, ['name', 'dataType'])) {
            $this->_logger->error("Missing elements for the addColumn method (need 'name', 'dataType')", [$params]);
            throw new InvalidArgumentException("Missing elements for the addColumn method");
        }

        $nn = (isset($params->nn) && $params->nn ? " NOT NULL" : "");
        $default = null;
        if ($params->default === null) {
            $default = " DEFAULT NULL";
        } elseif (strlen($params->default)) {
            $default = " DEFAULT {$this->_escape($params->default)}";
        }
        $this->_sql .= " `{$params->name}` {$params->dataType}" . $nn . $default;

        if (defined("PHP_DB_AUTORUN") && PHP_DB_AUTORUN) {
            return $this->execute();
        }

        return $this->_sql;
    }

    /**
     * Method to drop a fields from a table
     *
     * @param string $strTableName
     * @param string|array:string $params
     *
     * @return string|mixed
     *
     * @uses PHP_DB_AUTORUN bool to decide if the statement should be auto-committed and the results returned instead of the statement
     */
    public function dropColumn(string $strTableName, $params)
    {
        $this->setQueryType(self::ALTER_TABLE);
        $this->_sql = "ALTER TABLE {$strTableName} DROP COLUMN";

        if (is_array($params) && count($params)) {
            foreach ($params as $col) {
                $this->_sql .= " `{$col->name}`";

                if ($col != end($params)) {
                    $this->_sql .= ",";
                }
            }
        } elseif (is_string($params)) {
            $this->_sql .= " `{$params}`";
        }

        if (defined("PHP_DB_AUTORUN") && PHP_DB_AUTORUN) {
            return $this->execute();
        }

        return $this->_sql;
    }

    /**
     * Method to modify a field to change it's datatype, name, or other parameter
     *
     * @param string $strTableName
     * @param stdClass $params
     *
     * @return string|mixed
     *
     * @throws InvalidArgumentException
     *
     * @uses PHP_DB_AUTORUN bool to decide if the statement should be auto-committed and the results returned instead of the statement
     */
    public function modifyColumn($strTableName, $params)
    {
        $this->setQueryType(self::ALTER_TABLE);
        $this->_sql = "ALTER TABLE {$strTableName} MODIFY COLUMN";

        if (!self::checkObject($params, ['name', 'dataType'])) {
            $this->_logger->error("Missing elements to the modifyColumn method (need 'name' and 'dataType')", [$params]);
            throw new InvalidArgumentException("Missing elements to the modifyColumn method");
        }

        if (!isset($params->new_name)) {
            $params->new_name = $params->name;
        }

        $nn = (isset($params->nn) && $params->nn ? " NOT NULL" : "");
        $default = null;
        if ($params->default === null) {
            $default = " DEFAULT NULL";
        } elseif (strlen($params->default)) {
            $default = " DEFAULT {$this->_escape($params->default)}";
        }
        $this->_sql .= " `{$params->name}` `{$params->new_name}` {$params->dataType}" . $nn . $default;

        if (defined("PHP_DB_AUTORUN") && PHP_DB_AUTORUN) {
            return $this->execute();
        }

        return $this->_sql;
    }

    /**
     * Method to add a constraint to a table
     *
     * @param string $strTableName
     * @param stdClass $params
     *
     * @return string|mixed
     *
     * @throws InvalidArgumentException
     *
     * @uses PHP_DB_AUTORUN bool to decide if the statement should be auto-committed and the results returned instead of the statement
     */
    public function addConstraint($strTableName, $params)
    {
        $this->setQueryType(self::ALTER_TABLE);
        $this->_sql = "ALTER TABLE {$strTableName} ADD CONSTRAINT";

        if (!is_a($params, 'stdClass')) {
            $this->_logger->critical("Error in reading constraint field");
            throw new InvalidArgumentException("Error in reading constraint field");
        }

        if (!self::checkObject($params, ['id', 'local', 'schema', 'table', 'field', 'delete', 'update'])) {
            $this->_logger->error("Missing elements in the addConstraint method (need 'id', 'local', 'schema', 'table', 'field', 'delete', 'update')", [$params]);
            throw new InvalidArgumentException("There are some missing elements for the addConstraint action");
        }

        if (!in_array(strtoupper($params->delete), ['CASCADE', 'SET NULL', 'RESTRICT', 'NO ACTION'])) {
            $this->_logger->error("Invalid action for deletion on addConstraint");
            throw new InvalidArgumentException("Invalid action for deletion on addConstraint");
        }

        if (!in_array(strtoupper($params->update), ['CASCADE', 'SET NULL', 'RESTRICT', 'NO ACTION'])) {
            $this->_logger->error("Invalid action for update on addConstraint");
            throw new InvalidArgumentException("Invalid action for update on addConstraint");
        }

        if (is_array($params->field) && is_array($params->local)) {
            $field = "`" . implode("`,`", $params->field) . "`";
            $local = "`" . implode("`,`", $params->local) . "`";
        } elseif (is_string($params->field) && is_string($params->local)) {
            $field = "`{$params->field}`";
            $local = "`{$params->local}`";
        } else {
            throw new InvalidArgumentException("Invalid type for the field and local values both must be an array or string");
        }
        $this->_sql .= " `{$params->id}` FOREIGN KEY ({$local}) REFERENCES `{$params->schema}`.`{$params->table}` ({$field}) ON DELETE {$params->delete} ON UPDATE {$params->update}";

        if (defined("PHP_DB_AUTORUN") && PHP_DB_AUTORUN) {
            return $this->execute();
        }

        return $this->_sql;
    }

    /**
     * Check to see if a field in a table exists
     *
     * @param string $strTableName
     *            Table to check
     * @param string $strFieldName
     *            Field name to find
     *
     * @return bool Returns TRUE if field is found in that schema and table, otherwise FALSE
     */
    public function fieldExists(string $strTableName, string $strFieldName): bool
    {
        $fdata = $this->fieldData($strTableName);

        if (is_array($fdata) && count($fdata)) {
            foreach ($fdata as $field) {
                if ($field->name == $strFieldName) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Function to get the column data (datatype, flags, defaults, etc)
     *
     * @param string $strTableName
     *            Table to query
     * @param mixed $field
     *            [optional]
     *            Optional field to retrieve data (if null, returns data from all fields)
     *
     * @return mixed
     */
    public function fieldData(string $strTableName, $field = null)
    {
        if (is_null($field)) {
            $res = $this->_c->query("SELECT * FROM $strTableName LIMIT 1");
        } elseif (is_array($field)) {
            $res = $this->_c->query("SELECT `" . implode("`,`", $field) . "` FROM $strTableName LIMIT 1");
        } elseif (is_string($field)) {
            $res = $this->_c->query("SELECT $field FROM $strTableName LIMIT 1");
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
     * @param stdClass $field_data
     * @param stdClass $check
     * @param array $pks
     * @param stdClass $index
     *
     * @return array|string
     */
    public function fieldCheck(stdClass $field_data, stdClass $check, array $pks, ?array $index)
    {
        $default = null;
        $ret = null;

        $nn = (isset($check->nn) && $check->nn ? " NOT NULL" : null);

        if ($check->default === null) {
            $default = " DEFAULT NULL";
        } elseif (strlen($check->default)) {
            $default = " DEFAULT '{$check->default}'";
        }

        if ($field_data->type != $check->type && $check->type != MYSQLI_TYPE_ENUM) {
            $this->_logger->notice("Wrong datatype", [
                'name' => $field_data->name,
                'datatype' => $check->dataType
            ]);
            $ret = " CHANGE COLUMN `{$field_data->name}` `{$check->name}` {$check->dataType}" . "{$nn}{$default}";
        } elseif (! is_null($check->length) && $field_data->length != $check->length) {
            $this->_logger->notice("Incorrect size", [
                'name' => $field_data->name,
                'current' => $field_data->length,
                'new_size' => $check->length
            ]);
            $ret = " CHANGE COLUMN `{$field_data->name}` `{$check->name}` {$check->dataType}" . "{$nn}{$default}";
        } elseif ($check->type == MYSQLI_TYPE_ENUM && ! ($field_data->flags & MYSQLI_ENUM_FLAG)) {
            $this->_logger->notice("Setting ENUM type", [
                'name' => $field_data->name,
                'values' => implode(",", $check->values)
            ]);
            $ret = " CHANGE COLUMN `{$field_data->name}` `{$check->name}` {$check->dataType}('" . implode("','", $check->values) . "')" . "{$nn}{$default}";
        }

        if (! is_null($index) && count($index)) {
            foreach ($index as $ind) {
                if ($check->name == $ind->ref && ! ($field_data->flags & MYSQLI_MULTIPLE_KEY_FLAG)) {
                    $this->_logger->debug("Missing index", [
                        'name' => $field_data->name
                    ]);
                    $ret .= ($ret ? "," : "") . " ADD INDEX `{$ind->id}` (`{$ind->ref}` ASC)";
                }
            }
        }

        if (in_array($check->name, $pks) && ! ($field_data->flags & MYSQLI_PRI_KEY_FLAG)) {
            $this->_logger->debug("Setting PKs", [
                'keys' => implode(',', $pks)
            ]);
            $ret .= ($ret ? "," : "") . " DROP PRIMARY KEY, ADD PRIMARY KEY(`" . implode("`,`", $pks) . "`)";
        }

        return $ret;
    }

    /**
     * Function to check for the existence of a table within a schema
     *
     * @param string $strSchema
     *            The schema to search in
     * @param string $strTableName
     *            Table to search for
     *
     * @return int|bool Returns number of tables that match if table is found in that schema, otherwise FALSE
     *
     * @throws InvalidArgumentException
     */
    public function tableExists($strSchema, $strTableName)
    {
        if (! $this->_c->select_db($strSchema)) {
            $this->_logger->error("Schema {$strSchema} not found", [$this->_c->error]);
            throw new InvalidArgumentException("Error connecting to schema {$strSchema}");
        }

        if (preg_match("/[^0-9a-zA-Z\%\?\_]/", $strTableName)) {
            $this->_logger->warning("Invalid table name {$strTableName}");
            return false;
        }

        $sql = "SHOW TABLES LIKE '{$strTableName}'";

        if ($res = $this->_c->query($sql)) {
            if (gettype($res) == 'object' && is_a($res, 'mysqli_result') && $res->num_rows) {
                return $res->num_rows;
            }
        } elseif ($this->_c->errno) {
            $this->_logger->error($this->_c->error);
        }

        return false;
    }

    /**
     * Function to detect if string is a JSON object or not
     *
     * @param string $strVal
     *
     * @return bool
     */
    public function isJson($strVal): bool
    {
        json_decode($strVal);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    /**
     * Function to escape SQL characters to prevent SQL injection
     *
     * @param mixed $val
     *            Value to escape
     * @param bool $blnEscape
     *            Decide if we should escape or not
     *
     * @throws Exception
     * @throws InvalidArgumentException
     *
     * @return string Escaped value
     */
    public function _escape($val, bool $blnEscape = true): string
    {
        if (is_null($val) || (is_string($val) && strtolower($val) == 'null')) {
            return 'NULL';
        } elseif (is_numeric($val) || is_string($val)) {
            if ($blnEscape) {
                return "'{$this->_c->real_escape_string($val)}'";
            }
            return $val;
        } elseif (is_a($val, 'DateTime')) {
            return "'{$val->format(MYSQL_DATETIME)}'";
        } elseif (is_bool($val)) {
            return $val ? "'1'" : "'0'";
        } elseif (is_array($val)) {
            $ret = [];
            foreach ($val as $v) {
                $ret[] = $this->_escape($v);
            }
            return "(" . implode(",", $ret) . ")";
        } elseif (is_object($val) && method_exists($val, '_escape')) {
            $ret = call_user_func([
                $val,
                '_escape'
            ]);
            if ($ret !== false && is_string($ret)) {
                return $ret;
            } else {
                throw new Exception("Error in return from _escape method in " . get_class($val), E_ERROR);
            }
        }

        throw new InvalidArgumentException("Unknown datatype to escape in SQL string {$this->_sql} " . gettype($val), E_ERROR);
    }

    /**
     * Function to populate the fields for the SQL
     *
     * @param array|string $fields
     *            [optional]
     *            Optional array of fields to string together to create a field list
     *
     * @return string|null
     *
     * @throws InvalidArgumentException
     */
    protected function fields($fields = null): string
    {
        $ret = null;

        if (is_array($fields) && count($fields) && isset($fields[0]) && is_string($fields[0])) {
            foreach ($fields as $field) {
                if ((strpos($field, '`') === false) && (strpos($field, '.') === false) && (strpos($field, '*') === false) && (strpos($field, 'JSON_') === false) && (stripos($field, ' as ') === false) && stripos($field, 'IF(') === false) {
                    $ret .= "`$field`,";
                } else {
                    $ret .= "$field,";
                }
            }
            $ret = substr($ret, -1) == ',' ? substr($ret, 0, -1) : $ret;
        } elseif (is_string($fields)) {
            $ret = $fields;
        } elseif (is_null($fields)) {
            $ret = "*";
        } else {
            throw new InvalidArgumentException("Invalid field type");
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
    protected function flags(array $arrFlags)
    {
        $ret = '';

        if (isset($arrFlags['group'])) {
            $ret .= $this->groups($arrFlags['group']);
        }

        if (isset($arrFlags['having']) && is_array($arrFlags['having'])) {
            $having = " HAVING";
            $this->_logger->debug("Parsing where clause and adding to query");
            foreach ($arrFlags['having'] as $x => $h) {
                if ($x > 0) {
                    $having .= " {$h->sqlOperator}";
                }
                $having .= $h;
            }
            if (strlen($having) > strlen(" HAVING")) {
                $ret .= $having;
            }
        }

        if (isset($arrFlags['order'])) {
            $ret .= $this->order($arrFlags['order']);
        }

        if (isset($arrFlags['limit']) && (is_string($arrFlags['limit']) || is_numeric($arrFlags['limit']))) {
            $ret .= " LIMIT ";
            if (isset($arrFlags['start']) && (is_string($arrFlags['start']) || is_numeric($arrFlags['start']))) {
                $ret .= "{$arrFlags['start']},";
            }
            $ret .= "{$arrFlags['limit']}";
        }

        return $ret;
    }

    /**
     * Function to parse SQL GROUP BY statements
     *
     * @param mixed $groups
     *
     * @return string
     *
     * @throws InvalidArgumentException
     */
    protected function groups($groups): string
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
            throw new InvalidArgumentException("Error in datatype for groups " . gettype($groups), E_ERROR);
        }

        return $ret;
    }

    /**
     * Function to parse SQL ORDER BY statements
     *
     * @param mixed $order
     *
     * @return string
     *
     * @throws InvalidArgumentException
     */
    protected function order($order): string
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
        } else {
            throw new InvalidArgumentException("Error in datatype for order method ".gettype($order), E_ERROR);
        }

        return $ret;
    }

    /**
     * Method to check if there are any invalid characters in the table name
     *
     * @param string $strTableName
     *      Table name passed in
     *
     * @return bool
     *      Returns FALSE if table name contains any characters that will be problematic (0-9, a-z, A-Z, $, _), TRUE otherwise
     */
    private function checkTableName(string $strTableName): bool
    {
        return !strlen($strTableName) || preg_match("/[^0-9a-zA-Z\$\_\ ]/", $strTableName) ? false : true;
    }

    /**
     * Function to see if a constraint exists
     *
     *
     *
     * @param string $strConstraintId
     *
     * @return bool
     */
    public function isConstraint($strConstraintId): bool
    {
        $res = $this->_c->query("SELECT * FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_NAME = '{$strConstraintId}'");

        if ($res->num_rows) {
            return true;
        }

        return false;
    }

    /**
     * Method to add a where clause
     *
     * @param DBWhere|array:DBWhere $where
     *
     * @return bool|array:DBWhere
     */
    public function parseClause($where)
    {
        $ret = [];
        $interfaces = [];
        if (is_object($where)) {
            $interfaces = class_implements($where);
        }
        if (is_array($where)) {
            foreach ($where as $k => $w) {
                if (!is_a($w, 'Godsgood33\Php_Db\DBWhere')) {
                    return false;
                }
                $v = $this->_escape($w->value, $w->escape);
                $where[$k]->value = $v;

                $ret[] = $where[$k];
            }
        } elseif (is_a($where, 'Godsgood33\Php_Db\DBWhere')) {
            $v = $this->_escape($where->value, $where->escape);
            $where->value = $v;
            $ret[] = $where;
        } elseif (in_array("Godsgood33\Php_Db\DBInterface", $interfaces) && is_callable([$where, 'where'])) {
            $ret = $this->parseClause($where->where());
        } else {
            $this->_logger->warning("Failed to get where", [$where]);
        }

        return $ret;
    }

    /**
     * Encryption algorithm
     *
     * @param string $data
     * @param string $salt
     *
     * @return string
     *
     * @throws Exception
     *
     * @uses PHP_DB_ENCRYPT_SALT string the salt used in the encryption algorithm
     * @uses PHP_DB_ENCRYPT_ALGORITHM string the encryption algorithm used
     */
    public static function encrypt(string $data, ?string $salt = null)
    {
        if (!defined('PHP_DB_ENCRYPT_SALT') || !defined('PHP_DB_ENCRYPT_ALGORITHM')) {
            throw new Exception("Need to declare and populate PHP_DB_ENCRYPT_SALT and PHP_DB_ENCRYPT_ALGORITHM");
        }

        // Remove the base64 encoding from our key
        if (is_null($salt)) {
            $encryption_key = base64_decode(PHP_DB_ENCRYPT_SALT);
        } else {
            $encryption_key = base64_decode($salt);
        }
        // Generate an initialization vector
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(PHP_DB_ENCRYPT_ALGORITHM));
        // Encrypt the data using AES 256 encryption in CBC mode using our encryption key and initialization vector.
        $encrypted = openssl_encrypt($data, PHP_DB_ENCRYPT_ALGORITHM, $encryption_key, 0, $iv);
        // The $iv is just as important as the key for decrypting, so save it with our encrypted data using a unique separator (::)
        return base64_encode($encrypted . '::' . $iv);
    }

    /**
     * Decryption algorithm
     *
     * @param string $data
     *
     * @return string
     *
     * @throws Exception
     *
     * @uses PHP_DB_ENCRYPT_SALT string the salt used in the encryption algorithm
     * @uses PHP_DB_ENCRYPT_ALGORITHM string the encryption algorithm used
     */
    public static function decrypt(string $data)
    {
        if (!defined('PHP_DB_ENCRYPT_SALT') || !defined('PHP_DB_ENCRYPT_ALGORITHM')) {
            throw new Exception("Need to declare and populate PHP_DB_ENCRYPT_SALT and PHP_DB_ENCRYPT_ALGORITHM");
        }

        // Remove the base64 encoding from our key
        $encryption_key = base64_decode(PHP_DB_ENCRYPT_SALT);

        // To decrypt, split the encrypted data from our IV - our unique separator used was "::"
        list($encrypted_data, $iv) = explode('::', base64_decode($data), 2);
        $plaintext = openssl_decrypt($encrypted_data, PHP_DB_ENCRYPT_ALGORITHM, $encryption_key, 0, $iv);
        return $plaintext;
    }

    /**
     * Method to check if all required fields are available in the object
     *
     * @param object $object
     * @param array:string $requiredFields
     *
     * @return bool
     */
    public static function checkObject($object, $requiredFields): bool
    {
        $haystack = array_keys(json_decode(json_encode($object), true));
        foreach ($requiredFields as $r) {
            if (!in_array($r, $haystack)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Method to retrieve the error data
     *
     * @return string
     */
    public function error(): string
    {
        return $this->_c->error;
    }

    /**
     * Method to check the parameter types
     *
     * @param mixed $param
     *
     * @return int
     *
     * @throws MissingInterfaceAndMethods
     * @throws Exception
     */
    private function checkParamType($param): int
    {
        // check for implented object interfaces
        $interfaces = is_object($param) ? class_implements($param) : [];

        // numeric is the only datatype we can't have
        if (is_numeric($param)) {
            throw new MissingOrInvalidParam('Numeric parameters are not valid');
        }

        // check for a SELECT statement within an insert
        if (is_string($param) && stripos($param, 'select') !== false) {
            return self::STRING;
        }
        // param is an object, check to see if it includes the required interface
        elseif (is_object($param) && !is_iterable($param)) {
            if (!in_array('Godsgood33\Php_Db\DBInterface', $interfaces)) {
                throw new MissingInterfaceAndMethods('Object does not implement DBInterface interface');
            }

            // check to see if all required methods are callable
            if (!is_callable([$param, 'insert']) ||
                !is_callable([$param, 'update']) ||
                !is_callable([$param, 'replace']) ||
                !is_callable([$param, 'where'])
            ) {
                throw new MissingInterfaceAndMethods("Required DBInterface methods are not present in class ".get_class($param));
            }

            return self::OBJECT;
        }
        // param is an array
        elseif (is_array($param)) {
            // check that there is actual data in the array
            if (!count($param)) {
                throw new Exception('Array param is empty');
            }

            // check the first element of the array and see if it's an object then recurse through to check it
            $first = array_shift($param);
            if (is_object($first) && $this->checkParamType($first) == self::OBJECT) {
                return self::ARRAY_OBJECT;
            }

            return self::ARRAY;
        }
        // object is potentially a collection
        elseif (is_object($param)) {
            // check that collection has required interface
            if (!in_array('IteratorAggregate', $interfaces)) {
                throw new MissingInterfaceAndMethods('Object does not implement IteratorAggregate interface');
            }

            // get the first element in the collection
            $it = $param->getIterator();
            $first = $it->current();

            // check that the first element of the collection is a valid object as defined above
            if ($this->checkParamType($first) == self::OBJECT) {
                return self::COLLECTION;
            }
        }

        return 0;
    }
}
