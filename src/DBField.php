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
     *
     * @throws InvalidArgumentException
     */
    public function __construct(string $field = '*', string $table = '', string $alias = '')
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
    }

    /**
     * Magic method to turn the class into a string
     *
     * @return string
     */
    public function __toString(): string
    {
        $ret = ($this->data['table'] ? $this->data['table'].'.' : '').
            ($this->data['field'] == '*' ? $this->data['field'] : "`{$this->data['field']}`").
            ($this->data['alias'] ? " AS '{$this->data['alias']}'" : "");

        return $ret;
    }
}
