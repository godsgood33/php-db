Alter Table
===========

Add Column:
-----------

Purpose:
^^^^^^^^
The purpose to give you the ability to add a field to a table programatically.
NOTE: One field at a time!!!!

Definition:
^^^^^^^^^^^

``$this->addColumn($strTableName, $param)``

- strTableName - (string) the table name you want to add the field to
- param - (stdClass) the definition of the field you want to add
    - name - (string) the name of the field
    - dataType - (string) the datatype of the field (any accepted SQL datatype)
    - nn - (boolean) to set not null *(optional)*
    - default - (string|null) the default value you want the field to have
        Can be NULL *(optional)*

Returns:
^^^^^^^^
String|mysqli_result - returns the SQL string or the msqli_result object

Examples:
^^^^^^^^^

    | $field = json_decode(
    |   '{
    |       "name":"field1",
    |       "dataType":"varchar(64)",
    |       "nn":false,
    |       "default":null
    |   }'
    | );
    | $this->addColumn('test', $field);

``// ALTER TABLE test ADD COLUMN `field1` varchar(64) DEFAULT NULL``

Drop Column:
------------

Purpose:
^^^^^^^^
To give you the ability to drop a column (or multiple) programatically.

Definition:
^^^^^^^^^^^

``$this->dropColumn($strTableName, $params)``

- strTableName - (string) the table to drop the fields from
- params - (array:stdClass|string)
    - String - can be a single field name to drop
    - Array:stdClass - can be an array of stdClass objects that have a name
        property to retrieve

Returns:
^^^^^^^^
String|mysqli_result

Examples:
^^^^^^^^^

Single item using string

    | $field = "col1";
    | $this->dropColumn('test', $field);

``// ALTER TABLE test DROP COLUMN `col1```

Single item using stdClass

    | $field = new stdClass();
    | $field->name = 'col1';
    | $this->dropColumn('test', $field);

``// ALTER TABLE test DROP COLUMN `col1```

Multiple items

    | $fields = json_decode(
    |   '[
    |       {
    |           "name":"col1"
    |       },
    |       {
    |           "name":"col2"
    |       }
    |   ]'
    | );
    | $this->dropColumn('test', $fields);

``// ALTER TABLE test DROP COLUMN `col1`, `col2```

Modify Column:
--------------

Purpose:
^^^^^^^^
To give you the ability to modify a field programatically

Definition:
^^^^^^^^^^^

``$this->modifyColumn($strTableName, $params)``

- strTableName - (string) the table name that contains the fields you want to
    edit
- param - (stdClass) the definition of the field you want to modify
    - name - (string) the name of the field
    - dataType - (string) the datatype of the field (any accepted SQL datatype)
    - nn - (boolean) to set not null *(optional)*
    - default - (string|null) the default value you want the field to have
        Can be NULL *(optional)*

Returns:
^^^^^^^^
String|mysqli_result

Examples:
^^^^^^^^^

    | $field = json_decode(
    |   '{
    |       "name":"col1",
    |       "new_name":"col1",
    |       "dataType":"varchar(10)",
    |       "nn":false,
    |       "default":null
    |   }'
    | );
    | $this->modifyColumn('test', $field);

``// ALTER TABLE test MODIFY COLUMN `col1` `col` varchar(10) DEFAULT NULL``

Add Constraint:
---------------

Purpose:
^^^^^^^^
The purpose of this method to allow you to add a constraint to your tables
after creation.  This simplifies table creation.

Definition:
^^^^^^^^^^^

``$this->addConstraint($strTableName, $param)``

- strTableName - (string) the table name to add the constraint to
- param - (stdClass) the definition of the constraint
    - id - the unique id of the constraint
    - local - (array|string) the field in the local table (fk)
    - schema - the schema the table is in
    - table - the table the field is in
    - field - (array|string) the field in the linked table (key)
    - delete - the action to take when the key is deleted
        can be CASCADE, SET NULL, RESTRICT, or NO ACTION
    - update - the action to take when the key is updated
        can be CASCADE, SET NULL, RESTRICT, or NO ACTION

Returns:
^^^^^^^^
String|mysqli_result

Examples:
^^^^^^^^^

    | $field = json_decode(
    |   '{
    |       "id":"unique_id",
    |       "local":"col1",
    |       "schema":"schema",
    |       "table":"table",
    |       "field":"field1",
    |       "delete":"CASCADE",
    |       "update":"CASCADE"
    |   }'
    | );
    | $this->addConstraint('test', $field);

``ALTER TABLE test ADD CONSTRAINT `unique_id` FOREIGN KEY (`col1`)``
``REFERENCES `schema`.`table` (`field1`) ON DELETE CASCADE ON UPDATE``
``CASCADE``
