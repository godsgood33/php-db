Replace
=======

Purpose:
--------
To show the documentation for the replace method

*This method pretty much identical to the insert method*

Definition:
-----------

``$this->replace($strTableName, $arrParams);``

* strTableName - (string) The table to update
* arrParams - (array) Name/value pair to insert

Returns:
--------
``Integer``

Returns the number of affected rows and sets the "insertID" of the first
element

Examples:
---------

The replace method is very similar to the insert method except for no ignore
boolean.

    | $this->replace("member",
    | ['id' => 1, 'name' => 'George Foreman', 'salary' => 1]);
    | // REPLACE INTO member (`id`, `name`, `salary`) VALUES
    | (1, 'George Foreman', 1)
