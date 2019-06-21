select
======

Purpose:
--------
The select method creates a basic select statement to
retrieve information from a database table, view, or function.

Definition:
-----------

``$this->select($strTableName, $fields = null, $arrWhere = [],
$arrFlags = []);``

* strTableName - (string) The table to query
* fields - (string|array) Optional defaults to '*'
    * String - fields to retrieve
    * Array - list of fields to retrieve
* arrWhere - (DBWhere|array:DBWhere)
* arrFlags - (array)

Examples:
---------

``$this->select("members");
// SELECT * FROM members``

This will only return the id and name of the people in the members table

``$this->select("members", ['id', 'name']);
// SELECT id, name FROM members``

This will return all the fields for the member with the id = 1

``$this->select("members", null, new DBWhere('id', 1));
// SELECT * FROM members WHERE id = 1``

