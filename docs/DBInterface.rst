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
------------------

This method needs to return an associative array with field name => value pairs
(e.g. if you have the ID as an auto incrementing field, then you can omit that
from the insert method or any other fields you are okay with accepting the
default values you set in the database).

::

    [
        'id' => $this->id, // if this is an auto_incrementing field this can be omitted
        'fname' => $this->fname,
        'lname' => $this->lname,
        'phone' => $this->phone,
        'email' => $this->email
    ]

-------------

``replace`` : array
-------------------

This method needs to return an associative array similar to the ``insert``
method.  For this you should return all possible values.  NOTE: Since a REPLACE
command issues a DELETE before, you may find you have errors when trying to run
these because of constraints that are in place.

::

    [
        'fname' => $this->fname,
        'lname' => $this->lname,
        'phone' => $this->phone,
        'email' => $this->email
    ]

-------------

``update`` : array
------------------

This method needs to return an associative array representing the SET parameter
of the SQL statement.

::

    [
        'fname' => $this->fname,
        'lname' => $this->lname,
        'phone' => $this->phone,
        'email' => $this->email
    ]

-------------

``where`` : DBWhere | array:DBWhere
-----------------------------------

This method can return a DBWhere or array of DBWhere objects.  Most commonly you will want this to return the ID value, but if that is not been assigned yet, you'll want to offer a second way to retrieve the data for that object.

::

    if($this->id) {
        return new DBWhere('id', $this->id);
    } else {
        return [
            new DBWhere('lname', $this->lname),
            new DBWhere('email', $this->email),
        ];
    }