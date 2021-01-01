Delete
======

Purpose:
--------
The delete method creates a delete query that will delete the
requested rows from the database

Definition:
-----------

``$this->delete($strTableName, $arrFields = [], $arrWhere = [], $arrJoins = []);``

* strTableName - (string) Table name to act on
* arrFields - (array) optional list of fields to delete (used when including multiple tables)
* arrWhere - (DBWhere|array:DBWhere) optional where clauses
* arrJoins - (array) Optional 2-dimensional array to add other flags

Returns:
--------
``Integer``

Returns the number of affected rows

Examples:
---------

This will delete the row from the members table where the id = 1::

    $this->delete("members", new DBWhere('field', 1));
    // DELETE FROM members WHERE id = 1

You can also use join commands to delete rows from one table that are
dependent upon another table::

    $this->delete("members m", ['m.*'], [], [
        'joins' => ["JOIN department d ON d.member_id = m.id]
    ]);
    // DELETE m.* FROM members m JOIN department d ON d.member_id = m.id
