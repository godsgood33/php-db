Insert
======

Purpose:
--------
The insert method builds an insert query to insert a *single* row
into the table.  The method uses the second parameter array keys as the
table columns so they have to be exactly as they are defined in the table

Definition:
-----------

::

    $this->insert($strTableName, $params, $blnToIgnore = false);

* strTableName - (string) name of the table to insert the data in
* params - (mixed)
    * String - Acts like a INSERT...SELECT statement
    * Array - Use associative array ``field_name => value`` pairs
    * Object - Implements the DBInterface and has required `insert` method
* blnToIgnore - (boolean) used to decide if it needs to be a INSERT IGNORE
    statement

Returns:
--------
``Integer``

Returns the number of affected rows and sets the "insertID" of the first
element

Examples:
---------

This will create an insert query for a single entry to the database::

    $this->insert('member', [
        'id' => 1, 'name' => 'George Foreman', 'salary' => 1
    ]);
    // INSERT INTO member (`id`, `name`, `salary`) VALUES
        (1, 'George Foreman', 1)

A 3rd boolean parameters allows you to add a "IGNORE" in the insert just in
case the row already exists::

    $this->insert('member', [
        'id' => 1, 'name' => 'George Foreman', 'salary' => 1
    ], true);
    // INSERT IGNORE INTO member (`id`, `name`, `salary`) VALUES
        (1, 'George Foreman', 1)
