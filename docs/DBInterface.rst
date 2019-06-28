DBInterface
===========

Purpose:
--------
The purpose of the DBInterface is to give you a framework to allow you to pass
entire objects to the insert, replace, update, or where clause methods.  Once
you do, the library will retrieve all the necessary data and render the SQL
statements as needed.

Methods:
--------

``insert`` : array

This method needs to return an associative array with field name => value pairs

Example:
--------

    | [
    | 'id' => $this->id
    | 'fname' => $this->fname,
    | 'lname' => $this->lname,
    | 'phone' => $this->phone,
    | 'email' => $this->email
    | ]

``replace`` : array

This method needs to return an associative array similar to the ``insert``
method (e.g. if you have the ID as an auto incrementing field, then you can
omit that from the insert method or any other fields you are okay with
accepting the default values you set in the database.  For this you should
return all possible values.  NOTE: Since a REPLACE command issues a DELETE
before, you may find you have errors when trying to run these because of
constraints that are in place.

``update`` : array

This method needs to return an associative array representing the SET parameter
of the SQL statement.

``where`` : DBWhere | array:DBWhere

This method can return a DBWhere or array of DBWhere objects
