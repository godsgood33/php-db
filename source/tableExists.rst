tableExists
===========

Purpose:
--------
The tableExists method requires a schema and table name and will
return the number of types a table matching the string appears (wildcards
allowed)

Definition:
-----------

``$this->tableExists($strSchema, $strTableName)``

* strSchema - (string) The schema to check in
* strTableName - (string) The table to look for

Examples:
---------

``$this->tableExists('db', 'members');``

``// returns 1``

You can also use wildcards to find out how many table that match the wildcard
exist

``$this->tableExists('db', 'mem%');``

``// also retuns 1 in our example, but could return more if something else
matches the query``
