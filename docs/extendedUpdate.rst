Extended Update
===============

Purpose:
--------
The extendedUpdate method allows you to link two tables together
and update one with the data from another.  I commonly use it where I've
got to update a large number of fields and rows and I don't want to call
the update method hundreds or thousands of times.

Definition:
-----------

``$this->extendedUpdate($strTableToUpdate, $strOriginalTable,
$strLinkField, $arrParams);``

* strTableToUpdate - (string) The table that you want to update (alias 'tbu'
    is automatically added)
* strOriginalTable - (string) The table with the data you want to overwrite to
    be_updated table (alias 'o' is automatically added)
* strLinkField - (string) The common index value between them that will join
    the fields
* arrParams - (array|string)
    * String - only a single field is updated (tbu.name = o.name)
    * Array - each element of the array corresponds to a field to be updated
        (tbu.name = o.name, tbu.phone = o.phone)

Returns:
--------
``Integer``

Returns the number of affected rows

Examples:
---------

In order to use the extendedUpdate method you have to insert the records you
want to update into a separate table (a temp table works great).  That table
needs to have identical field names for it to work.

NOTE: Yes, in the example below, running a single update query would be plenty
 sufficient, but this allows you to run hundreds of updates in one (so that you
  don't have to put in CASE statements in the query).

Let's assume the table data below (members table)

==== ====== ========
 id   name   salary
---- ------ --------
 1   George  1
 2   Frank   2
==== ====== ========

The tmp_members_to_update table

==== ======== ========
 id   name     salary
---- -------- --------
 1    Horace    10
==== ======== ========

    | $this->extendedUpdate("members", "tmp_members_to_update", "id",
    | ['name', 'salary']);
    | // UPDATE members tbu INNER JOIN tmp_members_to_update o USING (id)
    | SET tbu.name = o.name, tbu.salary = o.salary

The members table would look like this after running the command

==== ======== ========
 id   name     salary
---- -------- --------
 1    Horace    10
 2    Frank     2
==== ======== ========

You can also specify a single string column in the last parameter.
If we assume the same previous table

==== ====== ========
 id   name   salary
---- ------ --------
 1   George  1
 2   Frank   2
==== ====== ========

The tmp_members_to_update table

==== ======== ========
 id   name     salary
---- -------- --------
 1    Horace    10
==== ======== ========

    | $this->extendedUpdate("membes", "tmp_members_to_update", "id", "name");
    | // UPDATE members tbu INNER JOIN tmp_members_to_update o USING (id)
    | SET tbu.name = o.name

==== ======== ========
 id   name     salary
---- -------- --------
 1    Horace    1
 2    Frank     2
==== ======== ========
