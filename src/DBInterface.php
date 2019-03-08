<?php
namespace Godsgood33\Php_Db;

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
    public function insert();

    /**
     * Required extendedInsert method to return an extended insert statement for the class
     * 
     * @return string
     *      String returned is list of all parameters and rows that need to be inserted on the table include parens and comma's
     *      Must be properly escaped, encoded, and/or encrypted
     * 
     * @example
     *      "('1','name','phone'),('2','name2','phone2'),..."
     */
    public function extendedInsert();

    /**
     * Required update method to return an update statement for the class
     * 
     * @return string
     *      String return is all fields and values to be updated
     *      Must be properly escaped, encoded, and/or encrypted
     * 
     * @example
     *      "id='1',name='name',phone='phone'"
     */
    public function update();

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
    public function replace();
    
}