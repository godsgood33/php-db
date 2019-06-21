truncate
========

Purpose:
--------
The truncate method allows you to truncate a tables data instead
of having to run the delete command

Definition:
-----------

``$this->truncate($strTableName)``

* strTableName - (string) The table to truncate

Examples:
---------

This will truncate all rows from the members table

``$this->truncate('members');
// TRUNCATE TABLE members``
