drop
====

Purpose:
--------
The drop method will create a drop query to drop a table or view
from the database.  This will also add a IF EXISTS clause.

Use:
To delete a table:

``$this->drop("members");``

``// DROP TABLE IF EXISTS members``

To delete a view

``$this->drop("members", "view");``

``// DROP VIEW IF EXISTS members``
