# PHP DB

This is a library that I have been building to act similar to the WPDB class used for database interactions on Wordpress.  I have expanded that library and added things like extended insert, replace, and update syntax building.

## Setup
- After loading open "src/DBConfig.php"
- Update top 4 defined constants with your system configuration (default server, user, password, and schema)
- After including autoload, you can create an object as follows

    $db = new Godsgood33\Php_Db\Database();

*OR*

    $conn = new mysqli("server", "user", "pwd", "schema");
    $db = new Godsgood33\Php_Db\Database($conn);

Using the second allows you to connect to ANY server that is not the default (however, if the connection DROPs out for any reason, it will be restored with the default server info)

## Query Type List
1. select
    - builds select query
2. select_count
    - builds select count(1) query
3. insert
    - builds insert query for one (1) row
4. extended_insert
    - builds insert query with more than one row
5. update
    - builds update query for one row
6. extended_update
    - builds update query for more than one row (requires table to pull from and to update)
7. replace
    - builds replace into query for one row
8. extended_replace
    - builds replace query for more than one row
9. delete
    - builds delete query (allows for joins and targeted deletion)
10. drop
    - builds drop query (allows for dropping multiple tables)
11. truncate
    - builds truncate query
12. create_table
    - builds create table query (allows for temporary, DDL syntax only, or create from select statement)
13. create\_table\_json
    - builds a DDL create table query from json (examples/create\_table\_json.json)
14. alter_table
    - builds alter table query (allows for add, modify, and drop column syntax)
15. field_exists
    - queryies table to check for presents of a specific field
16. field_data
    - queryies table to get field data
17. field_check
    - not implemented
18. table_exists
    - checks for presence of a table
19. fields
    - helper method to build field list
20. where
    - helper method to build where clause list
21. flags
    - helper method to parse option flag array
22. groups
    - helper method to build group by syntax
23. having
    - helper method to build having syntax
24. order
    - helper method to build order by syntax
25. fetch_all
    - helper method to return all rows from a query
26. is_constraint
    - helper method to check for presence of a constraint
27. log (uses Katzgrau/KLogger)
    - helper method for logging
