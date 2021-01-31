<?php

namespace Godsgood33\Php_Db;

use InvalidArgumentException;

/**
 * Class to store field values
 *
 * @property string $field
 *      The database field name
 * @property string $table
 *      The database table link
 * @property string $alias
 *      The field alias if desired
 *
 * @author Ryan Prather <godsgood33@gmail.com>
 */
class DBField
{
    /**
     * Class properties
     *
     * @var array
     */
    private array $data = [
        'field' => '',
        'table' => '',
        'alias' => '',
        'func' => ''
    ];

    /**
     * Constructor
     *
     * @param string $field
     *      The parameter field name to return
     * @param string $table
     *      The parameter representing the table (can be an alias)
     * @param string $alias
     *      The parameter representing the alias the field should be known as (e.g. " `name` AS 'full_name'")
     * @param string $function
     *      The parameter saying the field should be passed to a SQL function (e.g. SUM, COUNT, etc)
     *
     * @throws InvalidArgumentException
     */
    public function __construct(string $field = '*', string $table = '', string $alias = '', string $function = '')
    {
        if (preg_match("/[^A-Za-z0-9_\*]/", $field)) {
            throw new InvalidArgumentException("Invalid field name $field");
        } elseif (preg_match("/[^A-Za-z0-9_]/", $table)) {
            throw new InvalidArgumentException("Invalid table name $table");
        } elseif (preg_match("/[^A-Za-z0-9_]/", $alias)) {
            throw new InvalidArgumentException("Invalid alias $alias");
        }

        $this->data['field'] = $field;
        $this->data['table'] = $table;
        $this->data['alias'] = $alias;
        $this->data['func'] = $function;
    }

    /**
     * Magic method to turn the class into a string
     *
     * @return string
     */
    public function __toString(): string
    {
        $ret = '';
        if ($this->data['func']) {
            $ret .= $this->data['func'].'(';
        }
        if ($this->data['table']) {
            $ret .= $this->data['table'].'.';
        }
        if ($this->data['field'] == '*') {
            $ret .= $this->data['field'];
        } elseif ($this->data['func']) {
            $ret .= $this->data['field'];
        } else {
            $ret .= "`{$this->data['field']}`";
        }
        if ($this->data['func']) {
            $ret .= ')';
        }
        if ($this->data['alias']) {
            $ret .= " AS '{$this->data['alias']}'";
        }

        return $ret;
    }
}
