# PHP DB
[![Build Status](https://scrutinizer-ci.com/g/godsgood33/php-db/badges/build.png?b=master)](https://scrutinizer-ci.com/g/godsgood33/php-db/build-status/master)
[![Code Coverage](https://scrutinizer-ci.com/g/godsgood33/php-db/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/godsgood33/php-db/?branch=master)

This is a library that I have been building to act similar to the WPDB class used for database interactions on Wordpress.  I have expanded that library and added things like extended insert, replace, and update syntax building.

## Full Documentation
https://php-db.readthedocs.io/en/stable/

## Installation
- \``composer require godsgood33/php_db`\`

## Setup
- After installation, run `php vendor/godsgood33/php_db/bin/setup.php` to create the required constants to put a required config file:
    - PHP\_DB\_SERVER (IP or hostname of the server you want to connect to)
    - PHP\_DB\_USER (username for the connection)
    - PHP\_DB\_PWD (password for the user)
    - PHP\_DB\_SCHEMA (default schema to connect to, you can use {schema}.{table} syntax if you need to query other tables)
    - PHP\_DB\_LOG\_LEVEL (using PSR/Logger levels, if absent will default to Logger::Error)
    - PHP\_DB\_CLI\_LOG (boolean deciding if you want to show logs on the command line, if absent, will not display)
    - PHP\_DB\_ENCRYPT (boolean to decide if you want to encrypt the password)
    - PHP\_DB\_ENCRYPT\_ALGORITHM (OpenSSL algorithm used to encrypt and decrypt the password)
    - PHP\_DB\_ENCRYPT\_SALT (salt used to encrypt the password)
    - PHP\_DB\_AUTORUN (boolean to decide if you want the SQL command to auto commit and return the results of the query instead of the query itself, FALSE by default).

If you would like to encrypt the password in the configuration file you can run `php vendor/godsgood33/php_db/bin/setup.php --algorithms` to see what encryption algorithms you currently have installed.  Once you decide which one you want to use, run `php vendor/godsgood33/php_db/bin/setup.php -a={chosen algorithm}` and it will encrypt the password.

After including autoload, you can create an object as follows

    $db = new Godsgood33\Php_Db\Database();

*OR*

    $conn = new mysqli("server", "user", "pwd", "schema");
    $db = new Godsgood33\Php_Db\Database($conn);

Using the second allows you to connect to ANY server that is not the default, however, if the connection DROPs out for any reason, it will **NOT** be reconnected.

We recommend using this class to extend your existing database connection class.  Because instantiating will automatically call the parent class constructor and connect to the database using the default values (as long as you don't create your own class constructor, in which case add `parent::__construct()`).  Then within your DB class you build the function calls that will perform the queries that you need.

    class MyDB extends Godsgood33\Php_Db\Database
    {
        public function __construct()
        {
            parent::__construct();
        }

        public function getUsers()
        {
            $this->select("users");

            return $this->execute();
        }
    }
    $db = new MyDB();

If you have PHP_DB_AUTORUN set to true the above method could be written like the following:

    public function getUsers()
    {
        return $this->select('users');
    }

## Query Type List
1. select
    - builds select query
1. selectCount
    - builds select count(1) query
1. insert
    - builds insert query for one (1) row
1. extendedInsert
    - builds insert query with more than one row
1. update
    - builds update query for one row
1. extendedUpdate
    - builds update query for more than one row (requires table to pull from and to update)
1. replace
    - builds replace into query for one row
1. extendedReplace
    - builds replace query for more than one row
1. delete
    - builds delete query (allows for joins and targeted deletion)
1. drop
    - builds drop query (allows for dropping multiple tables)
1. truncate
    - builds truncate query
1. createTable
    - builds create table query (allows for temporary, DDL syntax only, or create from select statement)
1. createTableJson
    - builds a DDL create table query from json (examples/create\_table\_json.json)
1. alterTable
    - builds alter table query (allows for add, modify, and drop column syntax)
1. setVar
    - set a server, system, or connection variable
1. fieldExists
    - queries table to check for presents of a specific field
1. fieldData
    - queries table to get field data
1. tableExists
    - checks for presence of a table
1. fields
    - helper method to build field list
1. parseClause
    - helper method to build where and having clauses
1. flags
    - helper method to parse option flag array
1. groups
    - helper method to build group by syntax
1. order
    - helper method to build order by syntax
1. isConstraint
    - helper method to check for presence of a constraint
1. encrypt / decrypt
    - helper methods to decrypt and encrypt content

