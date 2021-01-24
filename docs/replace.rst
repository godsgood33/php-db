Replace
=======

Purpose:
--------
To show the documentation for the replace method

*This method pretty much identical to the insert method*

Definition:
-----------

::

    $this->replace($strTableName, $params);

* strTableName - (string) The table to update
* params - (mixed)
    * Array - A key/value pair.  Keys are used as field names, values are used as field values
    * Object - An object that implements the DBInterface interface and require `replace` method

Returns:
--------
``Integer``

Returns the number of affected rows and sets the "insertID" of the first element

Examples:
---------

The replace method is very similar to the insert method except for no ignore
boolean::

    $this->replace("member",
        ['id' => 1, 'name' => 'George Foreman', 'salary' => 1]);
    // REPLACE INTO member (`id`, `name`, `salary`) VALUES
        (1, 'George Foreman', 1)
