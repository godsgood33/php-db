Setup
=====

Installation
------------

``composer require godsgood33\php_db``

Setup
-----

Open a terminal window and navigate to your project path.

The next thing is to decide if you want to encrypt your MySQL password, if you
do then run ``php vendor/godsgood33/php_db/bin/setup.php --algorithms``.  This
will display a list of all of your OpenSSL support encryption algorithms.
Look at your list and select the one you want to use, then rerun
``php vendor/godsgood33/php_db/bin/setup.php -a={algorithm}``.  It will then
ask you a few questions and print out a list that you will need to copy into a
included file prior to creating your DB instance.  It will look something like

    | define('PHP_DB_SERVER', '1.1.1.1');
    | define('PHP_DB_USER', 'root');
    | define('PHP_DB_PWD', '{this is your encrypted password}');
    | define('PHP_DB_SCHEMA', 'test');
    | define('PHP_DB_ENCRYPT', true);
    | define('PHP_DB_ENCRYPT_ALGORITHM', 'AES-256-CBC-HMAC-SHA256');
    | define('PHP_DB_ENCRYPT_SALT', '{this is your encryption salt}');

- PHP_DB_SERVER can be an IP or hostname as long as it is accessible
- PHP_DB_USER is the user that you want this tool to connect to the server with
- PHP_DB_PWD will be either the encrypted or plaintext password you entered
    in the setup
- PHP_DB_SCHEMA is the default schema that the connection will make
- PHP_DB_ENCRYPT is a boolean to decide if the password should be encrypted
    By entering an algorithm you selected you wanted to encrypt it.
- PHP_DB_ENCRYPT_ALGORITHM is the algorithm you selected
- PHP_DB_ENCRYPT_SALT is a pseudo random base encoded list of characters
    from ``openssl_random_pseudo_bytes``

If you would like to autorun/commit the queries and have the methods return the
result of the query, you can add a ``PHP_DB_AUTORUN`` boolean constant

    define('PHP_DB_AUTORUN', true);

Again, once these are printed out, copy them to a file you include in your
program (one good option is bootstrap.php if you use it).

Basics
------

Once you have it installed and setup these are some bascis for how it works.
The first parameter in almost all the functions is the table name you want to
alter.  ``$this->select('{table_name}')`` The second parameter is the field
list.  It will allow you to edit or retrieve specific fields from the table.
You can add simple aliases to the table or fields and it will create a property
in the returned fields with the name you specify.

    $this->select('users', ['id', "CONCAT('fname', ' ', 'lname') AS 'name'",
    "user_phone_number AS 'phone'"])

So the previous statement will return an array of stdClass objects with ``id``,
``name``, ``phone`` as properties available for retrieval.  The exception to
this rule is the selectCount function with doesn't have a field list because it
just returns an integer with the number of rows that satisfy the where clauses

The third parameter in most is the where clause list.  It can either be an
array or an individual item.  You can also use an object you created a pass
that directly to the list as long as you are implementing the DBInterface
and you have a ``where`` method that returns a DBWhere object (or array of
DBWhere objects).
