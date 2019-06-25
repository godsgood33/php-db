Update
======

Purpose:
--------
The update method allows you to update a table.
There is no limit to the number of rows that are updated

Definition:
-----------

``$this->update($strTableName, $arrParams, $arrWhere = [], $arrFlags = []);``

* strTableName - (string) The table name to update
* arrParams - (array) Name/value pairs of the field name and value
* arrWhere - (DBWhere|array:DBWhare)
* arrFlags - (array) Two-dimensional array to create other flag options
    (joins, order, and group)

Examples:
---------

The following call will update the member table where the ID = 1
and change the name to 'Curious George' and the salary to '100'

    | $where = new DBWhere('id', 1);
    | $this->update('member', [
    | 'name' => 'Curious George', 'salary' => 100
    | ], $where);
    | // UPDATE member SET `name` = 'Curious George', `salary` = 100
    | WHERE id = 1

In the update method, you can also join other tables to expand or limit your
update in addition to or replacing the where clause.  This will update the
name and salary to anybody in the Sales department to 'Curious George'
and '100' respectfully.

    | $where = new DBWhere('d.dept', 'Sales');
    | $where->backticks = false; // required because of aliased table
    | $this->update('member m', [
    | 'm.name' => 'Curious George',
    | 'm.salary' => 100
    | ], $where, [
    | 'joins' => [
    | "JOIN department d ON d.member_id = m.id"
    | ]
    | ]);
    | // UPDATE member m SET m.name = 'Curious George', m.salary = 100
    | JOIN department d ON d.member_id = m.id WHERE d.dept = 'Sales'
