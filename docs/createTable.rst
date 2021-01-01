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

    $this->createTable($strTableName, $blnIsTemp = false, $strSelect = null)

* strTableName - (string) What to name the new table
* blnIsTemp - (boolean) Is this supposed to be a temporary table
* strSelect - (string|array|DBCreateTable|array:DBCreateTable) The definition of the table
    * String - Usually a SELECT SQL statement to populate the table with
    * DBCreateTable - An individual object for creating a table
    * array:DBCreateTable - An array of objects for creating the table
    * Array - Array of arrays that store the definition of the table::

        [
            'field' => 'field_name',
            'datatype' => '{standard SQL datatypes}',
            'default' => null,
            'option' => '{optional values, e.g. AUTO_INCREMENT, PRIMARY KEY, UNIQUE, NOT NULL, etc)}'
        ]

Returns:
--------
``mysqli_result``

Returns a ``mysqli_result`` object

Examples:
---------

Create a table with the ``DBCreateTable`` class::

    $idField = new DBCreateTable('id', DBConst::Key, null, 'AUTO_INCREMENT PRIMARY KEY');
    $nameField = new DBCreateTable('name', DBConst::ShortString);
    $emailField = new DBCreateTable('email', DBConst::Email);
    $this->createTable('test', false, [
        $idField,
        $nameField,
        $emailField
    ]);

**SQL Statement**::

    CREATE TABLE IF NOT EXISTS test (`id` int(11) AUTO_INCREMENT PRIMARY KEY, `name` varchar(100), `email` varchar(100))

Create a table given an array definition::

    $this->createTable('test', false, [
        [
            'field' => 'id',
            'datatype' => 'int(11)',
            'option' => 'AUTO_INCREMENT PRIMARY KEY'
        ],
        [
            'field' => 'name',
            'datatype' => 'varchar(100)'
        ],
        [
            'field' => 'active',
            'datatype' => 'tinyint(1)',
            'default' => '0'
        ]
    ]);

**SQL Statement**::

    CREATE TABLE IF NOT EXISTS test (`id` int(11) AUTO_INCREMENT PRIMARY KEY, `name` varchar(100), `active` tinyint(1) DEFAULT '0')

Create a table given a SQL SELECT statement::

    $this->createTable('test', true, "SELECT * FROM users WHERE active = '1'")``;

**SQL Statement**::

    CREATE TABLE IF NOT EXISTS test SELECT * FROM users WHERE active = '1'``

Create Table JSON
=================

Definition:
-----------

``$this->createTableJson($json)``

* json - (object) a JSON object storing the definition of the table.  We recommend a format like the following
    * tables : (array:objects) the table definitions
        * schema : (string) schema you want to put the table in
        * name : (string) name of the table
        * primary_key : (array) array of primary key fields
        * unique : (array) optional array of fields to make "unique"
        * fields : (array:objects) definition of the fields for the table
            * name : (string) name of the field
            * dataType : (string) SQL datatype of the field
            * ai : (optional boolean) is the field an auto incrementing field
            * nn : (optional boolean) is the field a NOT NULL field
            * default : (optional string) what should be the default of the field if not specified in query
            * values : (optional array) array of possible values for enum fields
        * index : (array:objects) specific index fields you want to specify
            * id : (string) the unique ID of this index
            * type : (string) what type of index is this (index, unique, fulltext, or primary)
            * ref : (string) what field does this index reference
    * table_constraints : (array:objects) any table constraints you want to apply
        * schema : (string) the schema
        * table : (string) the table
        * constraints : (array:objects) the constraint definitions
            * id : (string) the unique ID of the constraint
            * local : (string|array:string) local field in the table (FK)
            * schema : (string) the schema of the referenced table
            * table : (string) the table of the referenced field
            * field : (string|array:string) the referenced field (PK)
            * update : (string) action to take when updating the PK
            * delete : (string) action to take when deleting the PK

Returns:
--------
``Void``

Examples:
---------
There are example formats in the /examples directory, but to pass it to the
method use something similar to the following::

    $txt = file_get_contents('/examples/create_table_json.json');
    $json = json_decode($txt);
    foreach($json->tables as $t) {
        $this->createTableJson($t);
    }

If you have any constraints you want to apply, we recommend looping over the
constraints as suggest above after you create the tables::

    foreach($json->table_constraints as $tc) {
        $this->addConstraint($tc);
    }
