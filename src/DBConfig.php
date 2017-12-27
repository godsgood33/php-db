<?php
/**
 * Constant with database server to connect to
 *
 * @var string
 */
define('DB_SERVER', '{hostname|IP}');

/**
 * Constant with database user to connect with
 *
 * @var string
 */
define('DB_USER', '{username}');

/**
 * Constant with database password to connect with
 *
 * @var string
 */
define('DB_PWD', '{password}');

/**
 * Constant with database schema to connect to
 *
 * @var string
 */
define('DB_SCHEMA', '{schema}');

/**
 * Constant to decide if the database queries will run automatically after creating them
 *
 * @var boolean
 */
define('AUTORUN', false);

/**
 * Global to represent an IN statement (e.g.
 * WHERE field IN (1,2))
 *
 * @var int
 */
define('IN', 'IN');

/**
 * Global to represent a NOT IN statement (e.g.
 * WHERE field NOT IN (1,2))
 *
 * @var int
 */
define('NOT_IN', 'NOT IN');

/**
 * Global to represent a BETWEEN statement (e.g.
 * WHERE field BETWEEN 1 and 2)
 *
 * @var int
 */
define('BETWEEN', 'BETWEEN');

/**
 * Global to represent a LIKE statement (e.g.
 * WHERE field LIKE '%value%')
 *
 * @var int
 */
define('LIKE', 'LIKE');

/**
 * Global to represent a NOT LIKE statement (e.g.
 * WHERE field NOT LIKE '%value%')
 *
 * @var int
 */
define('NOT_LIKE', 'NOT LIKE');

/**
 * Global to represent an IS statement (e.g.
 * WHERE field IS NULL)
 *
 * @var int
 */
define('IS', 'IS');

/**
 * Global to represent an IS NOT statement (e.g.
 * WHERE field IS NOT NULL)
 *
 * @var int
 */
define('IS_NOT', 'IS NOT');

/**
 * Global to represent a DEBUG error state
 *
 * @var int
 */
define('E_DEBUG', 65535);

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

