<?php

declare(strict_types = 1);

namespace Godsgood33\Php_Db;

/**
 * This class is intended to be nothing more than project constants
 *
 * @author Ryan Prather <godsgood33@gmail.com>
 */
final class DBConst
{
    /**
     * Const to declare a Boolean
     *
     * @var string
     */
    public const Boolean = 'tinyint(1)';

    /**
     * Const to declare a short string (100 characters)
     *
     * @var string
     */
    public const ShortString = 'varchar(100)';

    /**
     * Const to declare a medium text datatype
     *
     * @var string
     */
    public const MediumString = 'mediumtext';

    /**
     * Const to declare a long text datatype
     *
     * @var string
     */
    public const LongString = 'longtext';

    /**
     * Const to declare a 10 digit currency
     *
     * @var string
     */
    public const Currency = 'decimal(10,2)';

    /**
     * Const to declare a standard primary key int
     *
     * @var string
     */
    public const Key = 'int(11)';

    /**
     * Const to declare a standard int (11 digits)
     *
     * @var string
     */
    public const Int = 'int(11)';

    /**
     * Const to declare a date datatype
     *
     * @var string
     */
    public const Date = 'date';

    /**
     * Const to declare a time datatype
     *
     * @var string
     */
    public const Time = 'time';

    /**
     * Const to declare a datetime value
     *
     * @var string
     */
    public const DateTime = 'datetime';

    /**
     * Const to declare a email datatype
     *
     * @var string
     */
    public const Email = 'varchar(100)';

    /**
     * Const to declare a phone number datatype
     *
     * @var string
     */
    public const USPhone = 'varchar(11)';

    /**
     * Constant defining parameters as a collection of objects
     *
     * @var int
     */
    public const COLLECTION = 1;

    /**
     * Constant defining parameters as an array
     *
     * @var int
     */
    public const ARRAY = 2;

    /**
     * Constant defining parameters as an objects
     *
     * @var int
     */
    public const OBJECT = 3;

    /**
     * Constant defining parameters as a string
     *
     * @var int
     */
    public const STRING = 4;

    /**
     * Constant defining parameters as an array of objects
     *
     * @var int
     */
    public const ARRAY_OBJECT = 5;

    /**
     * Constant defining that this is an array of primatives
     *
     * @var int
     */
    public const ARRAY_PRIMATIVE = 6;

    /**
     * Constant defining that this is a string with a select statement
     *
     * @var int
     */
    public const STRING_SELECT = 7;

    /**
     * Constant defining a SELECT query
     *
     * @var integer
     */
    public const SELECT = 1;

    /**
     * Constant defining a SELECT COUNT query
     *
     * @var integer
     */
    public const SELECT_COUNT = 2;

    /**
     * Constant defining a CREATE TABLE query
     *
     * @var integer
     */
    public const CREATE_TABLE = 3;

    /**
     * Constant defining DROP query
     *
     * @var integer
     */
    public const DROP = 4;

    /**
     * Constant defining DELETE query
     *
     * @var integer
     */
    public const DELETE = 5;

    /**
     * Constant defining INSERT query
     *
     * @var integer
     */
    public const INSERT = 6;

    /**
     * Constant defining REPLACE query
     *
     * @var integer
     */
    public const REPLACE = 7;

    /**
     * Constant defining UPDATE query
     *
     * @var integer
     */
    public const UPDATE = 8;

    /**
     * Constant defining EXTENDED INSERT query
     *
     * @var integer
     */
    public const EXTENDED_INSERT = 9;

    /**
     * Constant defining EXTENDED REPLACE query
     *
     * @var integer
     */
    public const EXTENDED_REPLACE = 10;

    /**
     * Constant defining EXTENDED UPDATE query
     *
     * @var integer
     */
    public const EXTENDED_UPDATE = 11;

    /**
     * Constant defining ALTER TABLE query
     *
     * @var integer
     */
    public const ALTER_TABLE = 12;

    /**
     * Constant defining action for alter table statement
     *
     * @var integer
     */
    public const ADD_COLUMN = 1;

    /**
     * Constant defining action for alter table statement
     *
     * @var integer
     */
    public const DROP_COLUMN = 2;

    /**
     * Constant defining action for alter table statement
     *
     * @var integer
     */
    public const MODIFY_COLUMN = 3;

    /**
     * Constant defining action to add a constraint
     *
     * @var integer
     */
    public const ADD_CONSTRAINT = 4;

    /**
     * Constant defining a TRUNCATE TABLE query
     *
     * @var integer
     */
    public const TRUNCATE = 13;
}
