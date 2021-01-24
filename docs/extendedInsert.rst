Extended Insert
===============

Purpose:
--------
The extendedInsert method allows you to create a insert query that you
can insert multiple entries at once.  Just like the insert method it allows for
a optional IGNORE boolean

Definition:
-----------

::

    $this->extendedInsert($strTableName, $arrFields, $params, $blnIgnore = false);

* strTableName - (string) The table to query
* arrFields - (array) The field names
* params - (mixed)
    * Array - 2-dimensional array of values (must have same number of values as fields)
    * String - just like insert can be INSERT...SELECT statement
    * Object - an object that implements DBInterface interface
    * Collection - an object that implements IteratorAggregate interface
    * Object Array - just like a collection, but a primative array
* blnToIgnore - (boolean) used to decide if it needs to be a INSERT IGNORE
    statement

Returns:
--------
``Integer``

Returns the number of affected rows and sets the "insertID" of the first
element

Examples:
---------

To insert multiple rows, you'll need to have one array with the fields that
correspond to the values you're inserting (the positions of the elements is
important)::

    $this->extendedInsert("members", ['id', 'name', 'salary'], [
        [1, 'George Foreman', 100], [2, 'Curious George', 1000]
    ]);
    // INSERT INTO members (`id`,`name`,`salary`) VALUES
        (1, 'George Foreman', 100), (2, 'Curious George', 1000)

Problems:
---------

If you run into problems it is likely because you don't have an identical
number of values as the number of fields.::

    $this->insert('foo', ['id', 'name', 'phone'], [
        [1, 'Fred Flintstone', '1'],
        [2, 'George Jetson']
    ]);

**Because the second array only has 2 elements the statement will throw**
**an Exception.**
