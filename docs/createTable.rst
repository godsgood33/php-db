Create Table
============

Purpose:
--------
This function will allow you to create permanent or temporary tables using a
defined structure.  There is also a createTableJson method that will allow you
to create a table using a defined JSON structure (an example of which can be
found in the /examples directory).

Definition:
-----------

``$this->createTable($strTableName, $blnIsTemp = false, $strSelect = null)``

* strTableName - (string) What to name the new table
* blnIsTemp - (boolean) Is this supposed to be a temporary table
* strSelect - (string|array) The definition of the table
    * String - Usually a SELECT SQL statement to populate the table with
    * Array - Array of arrays that store the definition of the table

``[
'field' => 'field_name',
'datatype' => '{standard SQL datatypes}',
'default' => null,
'option' => '{optional values, e.g. AUTO_INCREMENT, PRIMARY KEY, UNIQUE, etc)}'
]``

Returns:
--------
``mysqli_result``

Returns a ``mysqli_result`` object

Examples:
---------

``$this->createTable('test', false, [['field' => 'id','datatype' => 'int(11)',
'option' => 'AUTO_INCREMENT PRIMARY KEY'],['field' => 'name','datatype' =>
'varchar(100)'],['field' => 'active','datatype' => 'tinyint(1)','default' =>
'0']]);``

``CREATE TABLE IF NOT EXISTS (id int(11) AUTO_INCREMENT PRIMARY KEY, name
varchar(100), active tinyint(1) DEFAULT '0')``

Create Table JSON
=================

Definition:
-----------

``$this->createTableJson($json)``

* json - (object) a JSON object storing the definition of the table

Returns:
--------
``Void``

Examples:
---------
There are example formats in the /examples directory, but to pass it to the
method use something similar to the following:

    | $txt = file_get_contents('/examples/create_table_json.json');
    | $json = json_decode($txt);
    | foreach($json->tables as $t) {
    |     $this->createTableJson($t);
    | }
