Class Properties
================

Constants:
----------
The class constants available for use within the class::

    SELECT              = 1
    SELECT_COUNT        = 2
    CREATE_TABLE        = 3
    DROP                = 4
    DELETE              = 5
    INSERT              = 6
    REPLACE             = 7
    UPDATE              = 8
    EXTENDED_INSERT     = 9
    EXTENDED_REPLACE    = 10
    EXTENDED_UPDATE     = 11
    ALTER_TABLE         = 12
    TRUNCATE            = 13

Alter Table Actions:
^^^^^^^^^^^^^^^^^^^^

::

    ADD_COLUMN          = 1
    DROP_COLUMN         = 2
    MODIFY_COLUMN       = 3
    ADD_CONSTRAINT      = 4

Properties:
-----------
The protected and private variables used within the class

Protected:
^^^^^^^^^^
+---------------+---------------+---------------------------------------------+
| Variable      |  Data Type    | Purpose                                     |
+===============+===============+=============================================+
| ``_c``        |  mysqli       | The connection to the database              |
+---------------+---------------+---------------------------------------------+
| ``_result``   | mysqli_result | The result of the query                     |
+---------------+---------------+---------------------------------------------+
| ``_insertId`` | mixed         | The id of the last insert                   |
+---------------+---------------+---------------------------------------------+

Private:
^^^^^^^^
+---------------+---------------+---------------------------------------------+
| Variable      |  Data Type    | Purpose                                     |
+===============+===============+=============================================+
| ``_sql``      | string        | The query that is being constructed         |
+---------------+---------------+---------------------------------------------+
| ``_queryType``| int           | The action for the database to take using   |
|               |               |  the Class Actions                          |
+---------------+---------------+---------------------------------------------+
| ``_logLevel`` | string        | The log level of the logger                 |
+---------------+---------------+---------------------------------------------+
| ``_logger``   | Logger        | The Monolog\Logger to log the class actions |
+---------------+---------------+---------------------------------------------+
| ``_logPath``  | string        | The absolute path of the log file           |
+---------------+---------------+---------------------------------------------+
