<?php
namespace Godsgood33\Php_Db;

/**
 * Interface to allow for easier insert, update, and replacements
 * 
 * @author Ryan Prather <godsgood33@gmail.com>
 */
interface DBInterface
{

    /**
     * Required insert method to return keys to be inserted
     * 
     * @return array
     *      Array return is key/value pairs.  Values must be already SQL escaped, encoded, and/or encrypted
     * 
     * @example
     *      [
     *          'id' => '1',
     *          'name' => 'John Doe',
     *          'phone' => '1234567890'
     *      ]
     */
    public function insert() : array;

    /**
     * Required update method to return an update statement for the class
     * 
     * @return string
     *      String return is all fields and values to be updated
     *      Must be properly escaped, encoded, and/or encrypted
     * 
     * @example
     *      [
     *          id => '1',
     *          name => 'name',
     *          phone => 'phone'
     *      ]
     */
    public function update() : array;

    /**
     * Required replace method to return a replace statement for the class
     * 
     * @return array
     *      Array return is key/value pairs.  Values must be already SQL escaped, encoded, and/or encrypted
     * 
     * @example
     *      [
     *          'id' => '1',
     *          'name' => 'Jane Doe',
     *          'phone' => '9876543210'
     *      ]
     */
    public function replace() : array;

    /**
     * Required where method to return a DBWhere object for the class
     * 
     * @return DBWhere|array:DBWhere
     */
    public function where();

}