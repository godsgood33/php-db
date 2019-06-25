DBWhere
=======

Purpose:
--------
This class allows you to easily create WHERE or HAVING clauses.

.. _constants:

Constants:
----------

- LIKE - for a LIKE clause (e.g. ``name LIKE '%george%'``)
- NOT_LIKE - for a NOT LIKE clause (e.g. ``name NOT LIKE '%george%'``)
- IS - for an IS clause (e.g. ``phone IS NULL``)
- IS_NOT - for an IS NOT clause (e.g. ``age IS NOT NULL``)
- BETWEEN - for a BETWEEN clause (e.g. ``age BETWEEN 20 AND 29``)
- IN - for an IN clause (e.g. ``state IN ('MD', 'IN', 'CA')``)
- NOT_IN - for a NOT IN clause (e.g. ``state NOT IN ('NY', 'AR', 'MT')``)

Properties:
-----------

- field = null
    - The DB field to query for (e.g. name, id, phone)
- value = null
    - The value to search for
- low = null
    - Only used if doing a BETWEEN statement then this becomes the lowerbounds
- high = null
    - Only used if doing a BETWEEN statement then this becomes the higherbounds
- escape = true
    - To decide if we need to escape the value
    - *Need to be set to false if doing a LIKE statement and you will need to*
        *escape all problematic characters*
- operator = '='
    - The operator to check the field for
    - Can be any standard operator string (e.g. =, >, <, >=, <=)
    - Can use the :ref:`constants` above
- sqlOperator = 'AND'
    - The SQL operator to bridge two or more clauses (e.g. AND, OR, NOR, XOR,
        etc)
- backticks = true
    - To decide if you need to put backticks around a field (e.g. ```id```)
    - Need to set to false if you are attempting to get a field from an aliased
        table (e.g. ``u.name``)
- openParen = false
    - To decide to start the clause with an open paren
- closeParen = false
    - To decide to end the clause with a closing paren
- caseInsensitive = false
    - To decide to make the question case case insensitive
    - ``LOWER(`name`) = LOWER('FRANK')``

``DBWhere($field = null, $value = null, $operator = '=')``

Examples:
---------

1)
    $where = new DBWhere('name', 'Fred Flintstone');
    // WHERE `name` = 'Fred Flintstone'

2)
    $where = new DBWhere('phone', null, DBWhere::IS);
    // WHERE `phone` IS NULL

3)
    | $where = new DBWhere('age', null, DBWhere::BETWEEN);
    | $where->low = 20;
    | $where->high = 30;
    | // WHERE `age` BETWEEN 20 AND 30
