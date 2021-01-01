Extended Replace
================

Purpose:
--------
The extendedReplace method does the same thing as the extendedInsert
method does except for a REPLACE query (which does a delete first, then an
insert)

Definition:
-----------

::

    $this->extendedReplace($strTableName, $arrFields, $arrParams);

* strTableName - (string) Table name to update
* arrFields - (array) Array of fields
* arrParams - (array) 2-dimensional array of values

Returns:
--------
``Integer``

Returns the number of affected rows

Examples:
---------

::

    $this->extendedReplace("members", ['id', 'name', 'salary'], [
        [1, 'George Foreman', 100], [2, 'Curious George', 1000]
    ]);
    // REPLACE INTO members (`id`,`name`,`salary`) VALUES
        (1, 'George Foreman', 100), (2, 'Curious George', 1000)
