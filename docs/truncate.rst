Truncate
========

Purpose:
--------
The truncate method allows you to truncate a tables data instead of having to
run the delete command.  This will throw an error if there are linked tables.

Definition:
-----------

``$this->truncate($strTableName)``

* strTableName - (string) The table to truncate

Returns:
--------
``mysqli_result``

Returns the ``mysqli_result`` object

Examples:
---------

This will truncate all rows from the members table

    | $this->truncate('members');
    | // TRUNCATE TABLE members
