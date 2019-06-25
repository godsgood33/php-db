Drop
====

Purpose:
--------
The drop method will create a drop query to drop a table or view from the
database.  This will also add a IF EXISTS clause.

Definition:
-----------

``$this->drop($strTableName);``

* strTableName - (string) The table to truncate

Examples:
---------

    | $this->drop('members');
    | // DROP TABLE IF EXISTS members

To delete a view

    | $this->drop("active_members", "view");
    | // DROP VIEW IF EXISTS active_members
