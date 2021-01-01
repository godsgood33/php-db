<?php

declare(strict_types = 1);

namespace Godsgood33\Php_Db;

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
}
