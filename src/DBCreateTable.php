<?php

declare(strict_types = 1);

namespace Godsgood33\Php_Db;

use InvalidArgumentException;

/**
 * Class to help creating new tables
 *
 * @author Ryan Prather <godsgood33@gmail.com>
 *
 * @property string $field
 * @property string $datatype
 * @property string $default
 * @property string $option
 */
class DBCreateTable
{

    /**
     * Private variable to store the name of the field to create
     *
     * @var array
     */
    private array $data;

    /**
     * Constructor
     *
     * @param string $field
     * @param string $datatype
     * @param string $default
     * @param string $option
     */
    public function __construct(string $field, string $datatype, ?string $default = null, ?string $option = null)
    {
        $this->data = [
            'field' => $field,
            'datatype' => $datatype,
            'default' => $default,
            'option' => $option
        ];
    }

    /**
     * Magic setter method
     *
     * @param string $name
     * @param string|int|null $value
     *
     * @throws InvalidArgumentException
     *
     * @return DBCreateTable
     */
    public function __set(string $name, $value): DBCreateTable
    {
        if (in_array($name, ['field', 'datatype', 'default', 'option'])) {
            $this->data[$name] = $value;
        } else {
            throw new InvalidArgumentException("Invalid property in CreateTable");
        }

        return $this;
    }

    /**
     * Magic method to convert the class to a string
     *
     * @return string
     */
    public function __toString(): string
    {
        $default = (isset($this->data['default']) ? $this->data['default'] : null);
        $option = (isset($this->data['option']) ? $this->data['option'] : null);

        return "`{$this->data['field']}` {$this->data['datatype']}" . ($default !== null ? " DEFAULT '{$default}'" : null) . (!is_null($option) ? " {$option}" : null);
    }
}
