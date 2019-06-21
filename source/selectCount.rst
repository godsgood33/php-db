selectCount
===========

Purpose:
--------
The selectCount method returns the resonse to a SELECT COUNT(1) query.

Definition:
-----------

``$this->selectCount($strTableName, $arrWhere = [], $arrFlags = []);``

* strTableName - (string) The table to query
* arrWhere - (DBWhere|array:DBWhere)
* arrFlags - (array)

Examples:
---------

This will return the total number of rows in the members table

``$this->selectCount('members');
// SELECT COUNT(1) AS 'count' FROM members``

This query will find the number of all the people in the members table that
have a name like "%George%".

``$where = new DBWhere('name', '%George%', DBWhere::LIKE);``

``$where->escape = false;``

``$this->selectCount('members', $where);``

``// SELECT COUNT(1) AS 'count' FROM members WHERE `name` LIKE '%George%'``
