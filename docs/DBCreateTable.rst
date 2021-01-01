DBCreateTable
=============

Purpose:
--------
This class was created to help in creating a table in the database.

Properties:
-----------

The class properties are as follows::

    String $field - what is the field name
    String $datatype - what is the SQL datatype stored here (can use optional DBConst values, if not present in DBConst, you can just use the string value of that datatype)
    String $default - what is the default value for this field
    String $option - optional SQL flags (AUTO_INCREMENT, UNIQUE, PRIMARY KEY, NOT NULL, etc)

Methods:
--------

There is a magic setter method, so if you need to change any of these properties you can do so by calling it directly::

    $dbc->field = 'george';
    $dbc->datatype = 'yo mama';

There is also a __toString method to auto convert the class to the require syntax appropriate for SQL
