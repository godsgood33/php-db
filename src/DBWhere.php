<?php
namespace Godsgood33\Php_Db;

/**
 * Class to create a where clause
 * 
 * @author Ryan Prather <godsgood33@gmail.com>
 */
class DBWhere
{

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
     * Array to store the necessary class variables
     * 
     * @var array
     */
    protected $data = [];

    /**
     * Constructor
     * 
     * @param string $field
     * @param mixed $value
     * @param string $operator
     */
    public function __construct($field = null, $value = null, $operator = '=')
    {
        $this->data = [
            'index' => 0,
            'field' => $field,
            'value' => $value,
            'low' => null,
            'high' => null,
            'escape' => true,
            'operator' => $operator,
            'sqlOperator' => 'AND',
            'backticks' => true,
            'openParen' => false,
            'closeParen' => false,
            'caseInsensitive' => false
        ];
    }

    /**
     * Method to return the variables
     * 
     * @param string $var
     * 
     * @return mixed
     * 
     * @throws InvalidArgumentException
     */
    public function __get($var)
    {
        if(!in_array($var, [
            'index', 'field', 'value', 'low', 'high', 'operator', 'backticks',
            'sqlOperator', 'escape', 'openParen', 'closeParen', 'caseInsensitive'
        ])) {
            $trace = \debug_backtrace();
            throw new \InvalidArgumentException("Property not allowed via __get():  $var in {$trace[0]['file']} on line {$trace[0]['line']}", E_USER_WARNING);
        }

        return $this->data[$var];
    }

    /**
     * Method to set a variable
     * 
     * @param string $name
     * @param mixed $value
     * 
     * @throws InvalidArgumentException
     */
    public function __set($name, $value)
    {
        if(!in_array($name, [
            'index', 'field', 'value', 'low', 'high', 'operator', 'backticks',
            'sqlOperator', 'escape', 'openParen', 'closeParen', 'caseInsensitive'
        ])) {
            $trace = \debug_backtrace();
            throw new \InvalidArgumentException("Property not allowed via __set():  $name in {$trace[0]['file']} on line {$trace[0]['line']}", E_USER_WARNING);
        }

        $this->data[$name] = $value;
    }

    /**
     * Method to parse where clauses
     */
    public function __toString()
    {
        $ret = '';

        if (is_null($this->data['field']) && isset($this->data['closeParen']) && $this->data['closeParen']) {
            $ret .= ")";
            return $ret;
        }

        switch ($this->data['operator']) {
            case self::BETWEEN:
                if (! isset($this->data['field']) || ! isset($this->data['low']) || ! isset($this->data['high'])) {
                    return '';
                }
                break;
            default:
                if (! $this->data['field']) {
                    return '';
                }
        }

        if ($this->data['openParen']) {
            $ret .= " (";
        }

        if (! $this->data['backticks']) {
            $field = $this->data['field'];
        } else {
            $field = "`{$this->data['field']}`";
        }

        if ($this->data['operator'] == self::IN || $this->data['operator'] == self::NOT_IN) {
            if (is_string($this->data['value'])) {
                $ret .= " {$field} {$this->data['operator']} " . (strpos($this->data['value'], '(') !== false ? $this->data['value'] : "({$this->data['value']})");
            } elseif (is_array($this->data['value'])) {
                $ret .= " {$field} {$this->data['operator']} (" . implode(",", $this->data['value']) . ")";
            } else {
                return '';
            }
        } elseif ($this->data['operator'] == self::BETWEEN) {
            $ret .= " {$field} BETWEEN {$this->data['low']} AND {$this->data['high']}";
        } else {
            $value = (is_null($this->data['value']) ? "NULL" : $this->data['value']);

            if ($this->data['caseInsensitive']) {
                $ret .= " LOWER({$field}) {$this->data['operator']} LOWER({$value})";
            } else {
                $ret .= " {$field} {$this->data['operator']} {$value}";
            }
        }

        if (isset($this->data['closeParen']) && $this->data['closeParen']) {
            $ret .= ")";
        }

        return $ret;
    }
}