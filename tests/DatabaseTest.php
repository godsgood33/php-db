<?php
use Godsgood33\Php_Db\Database;
use Godsgood33\Php_Db\DBCreateTable;
use Godsgood33\Php_Db\DBWhere;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

require_once 'TestClass.php'; // class with _escape method
require_once 'TestClass2.php'; // class without _escape method
require_once 'TestClass3.php';
require_once 'DBConfig.php';

/**
 * @coversDefaultClass Database
 */
final class DatabaseTest extends TestCase
{
    private $db;

    public function setUp()
    {
        $this->db = new Database(realpath(__DIR__));
        // Database::$autorun = true;
    }

    public function testCanCreateDatabaseInstance()
    {
        $this->assertInstanceOf("Godsgood33\Php_Db\Database", $this->db);
    }

    public function testGetSchema()
    {
        $schema = $this->db->getSchema();
        $this->assertEquals("db", $schema);
    }

    public function testSetVarNullName()
    {
        $ret = $this->db->setVar(null, null);
        $this->assertFalse($ret);
    }

    public function testSetVarFail()
    {
        $ret = $this->db->setVar('5*%#', '');
        $this->assertFalse($ret);
    }

    public function testSetSchemaWithNonExistentSchema()
    {
        $ret = $this->db->setSchema("george");
        $this->assertFalse($ret);
    }

    public function testDatabaseConnection()
    {
        $this->assertTrue($this->db->isConnected());
    }

    public function testPassInMysqliConnection()
    {
        if (defined('PHP_DB_ENCRYPT') && PHP_DB_ENCRYPT) {
            $pwd = Database::decrypt(PHP_DB_PWD);
        } else {
            $pwd = PHP_DB_PWD;
        }

        $conn = new mysqli(PHP_DB_SERVER, PHP_DB_USER, $pwd, PHP_DB_SCHEMA);
        if ($conn->connect_errno) {
            fwrite(STDOUT, $conn->connect_error);
        }

        $this->db = new Database(realpath(__DIR__), $conn);

        $this->assertInstanceOf("Godsgood33\Php_Db\Database", $this->db);
    }

    public function testSetLogLevel()
    {
        $this->db->setLogLevel(Logger::DEBUG);
        $this->assertEquals(Logger::DEBUG, $this->db->getLogLevel());
    }

    public function testSetLogger()
    {
        $l = new Monolog\Logger('test');
        $this->assertTrue($this->db->setLogger($l));
    }

    public function testCreateTableJson()
    {
        $json = json_decode(file_get_contents(dirname(dirname(__FILE__)) . "/examples/create_table_json.json"));

        $this->db->createTableJson($json->tables[0]);
        $this->assertEquals("CREATE TABLE IF NOT EXISTS `settings` (`id` int(11) AUTO_INCREMENT NOT NULL,`meta_key` varchar(100) NOT NULL,`meta_value` mediumtext DEFAULT NULL, UNIQUE(`meta_key`), PRIMARY KEY(`id`))", $this->db->getSql());

        $this->assertTrue($this->db->execute());
    }

    public function testCreateTableJson2()
    {
        $json = json_decode(file_get_contents(dirname(dirname(__FILE__)) . "/examples/create_table_json.json"));

        $this->db->createTableJson($json->tables[1]);
        $this->assertEquals("CREATE TABLE IF NOT EXISTS `test` (`id` int(11) AUTO_INCREMENT NOT NULL,`fk` int(11) NOT NULL,`default` tinyint(1) DEFAULT '0',`enum` enum('1','2') DEFAULT '1', INDEX `default_idx` (`default`), PRIMARY KEY(`id`,`fk`))", $this->db->getSql());

        $this->assertTrue($this->db->execute());
    }

    public function testCreateTableJson3()
    {
        $json = json_decode(file_get_contents(dirname(dirname(__FILE__)) . "/examples/create_table_json.json"));

        $this->db->createTableJson($json->tables[2]);
        $this->assertEquals("CREATE TABLE IF NOT EXISTS `test2` (`id` int(11) AUTO_INCREMENT NOT NULL, PRIMARY KEY(`id`))", $this->db->getSql());

        $this->assertTrue($this->db->execute());
    }

    /**
     * @expectedException Exception
     */
    public function testSelectWithInvalidTableName()
    {
        $this->db->select(new stdClass());
    }

    public function testSelectWithNoParameters()
    {
        // query table with NO parameters
        $this->db->select("test");
        $this->assertEquals("SELECT * FROM test", $this->db->getSql());
    }

    public function testSelectWithNullFieldParameter()
    {
        // query table with null fields parameter
        $this->db->select("test", null);
        $this->assertEquals("SELECT * FROM test", $this->db->getSql());
    }

    public function testSelectWithOneArrayParameter()
    {
        // query table with one parameter
        $this->db->select("test", [
            'id'
        ]);
        $this->assertEquals("SELECT `id` FROM test", $this->db->getSql());
    }

    public function testSelectWithTwoArrayParameters()
    {
        // query table with 2 parameters
        $this->db->select("test", [
            'id',
            'name'
        ]);
        $this->assertEquals("SELECT `id`,`name` FROM test", $this->db->getSql());
    }

    public function testSelectWithOneStringParameter()
    {
        // query table with string parameter
        $this->db->select("test", 'id');
        $this->assertEquals("SELECT id FROM test", $this->db->getSql());
    }

    /**
     * @expectedException Exception
     */
    public function testSelectWithStdClassParameter()
    {
        // query table with object parameter
        $this->db->select("test", new stdClass());
        $this->assertEquals("SELECT  FROM test", $this->db->getSql());
    }

    public function testSelectWithNullWhereParameter()
    {
        // query table with null where parameter
        $this->db->select("test", 'id', null);
        $this->assertEquals("SELECT id FROM test", $this->db->getSql());
    }

    public function testSelectWithEmptyArrayWhereParameter()
    {
        // query table with empty array where paramter
        $this->db->select("test", 'id', []);
        $this->assertEquals("SELECT id FROM test", $this->db->getSql());
    }

    public function testSelectWithImcompleteWhereArrayParameter()
    {
        $where = new DBWhere('id');
        // query with incomplete WHERE clause
        $this->db->select("test", 'id', $where);
        $this->assertEquals("SELECT id FROM test WHERE `id` = NULL", $this->db->getSql());
    }

    public function testSelectWithMultipleWhereClauses()
    {
        $where1 = new DBWhere("name", "Frank");
        $where2 = new DBWhere("state", "IN");
        $this->db->select("test", null, [$where1, $where2]);
        $this->assertEquals("SELECT * FROM test WHERE `name` = 'Frank' AND `state` = 'IN'", $this->db->getSql());
    }

    public function testSelectWithGroupByFlag()
    {
        $this->db->select('test', null, [], [
            'group' => 'state'
        ]);
        $this->assertEquals("SELECT * FROM test GROUP BY state", $this->db->getSql());
    }

    public function testSelectWithGroupByArrayFlag()
    {
        $this->db->select("test", null, [], [
            'group' => ['lname', 'state']
        ]);
        $this->assertEquals("SELECT * FROM test GROUP BY lname, state", $this->db->getSql());
    }

    /**
     * @expectedException Exception
     */
    public function testSelectWithGroupByObject()
    {
        $this->db->select("test", null, [], [
            'group' => new stdClass()
        ]);
    }

    public function testSelectWithOrderByFlag()
    {
        $this->db->select("test", null, [], [
            'order' => 'lname'
        ]);
        $this->assertEquals("SELECT * FROM test ORDER BY lname", $this->db->getSql());
    }

    public function testSelectWithOrderByArrayFlag()
    {
        $this->db->select('test', null, [], [
            'order' => [['field' => 'lname', 'sort' => 'ASC'], ['field' => 'state', 'sort' => 'DESC']]
        ]);
        $this->assertEquals("SELECT * FROM test ORDER BY lname ASC, state DESC", $this->db->getSql());
    }

    public function testSelectWithHavingFlag()
    {
        $where1 = new DBWhere('foo', 10);
        $where2 = new DBWhere('bar', 100, '>=');
        $where2->sqlOperator = 'OR';
        $this->db->select('test', null, [], [
            'having' => [$where1, $where2]
        ]);
        $this->assertEquals("SELECT * FROM test HAVING `foo` = 10 OR `bar` >= 100", $this->db->getSql());
    }

    public function testSelectWithLimitAndStartFlag()
    {
        $this->db->select("test", null, [], [
            'limit' => 10,
            'start' => 5
        ]);
        $this->assertEquals("SELECT * FROM test LIMIT 5,10", $this->db->getSql());
    }

    public function testCreateTemporaryTable()
    {
        $this->db->select("test");
        $this->db->createTable('test2', true);
        $this->assertEquals("CREATE TEMPORARY TABLE IF NOT EXISTS test2 AS (SELECT * FROM test)", $this->db->getSql());
    }

    public function testCreateTable()
    {
        $this->db->createTable('test', false, $this->db->select("test"));
        $this->assertEquals("CREATE TABLE IF NOT EXISTS test AS (SELECT * FROM test)", $this->db->getSql());
    }

    public function testCreateTableWithArrayParameter()
    {
        $this->db->createTable("test", true, [
            [
                'field' => 'id',
                'datatype' => 'int(11)',
                'option' => 'PRIMARY KEY'
            ],
            [
                'field' => 'name',
                'datatype' => 'varchar(100)',
                'default' => null
            ],
            [
                'field' => 'email',
                'datatype' => 'varchar(100)',
                'default' => ''
            ]
        ]);
        $this->assertEquals("CREATE TEMPORARY TABLE IF NOT EXISTS test (`id` int(11) PRIMARY KEY,`name` varchar(100),`email` varchar(100) DEFAULT '')", $this->db->getSql());
    }

    public function testTableExists()
    {
        $tbl_count = $this->db->tableExists('db', 'settings');
        $this->assertEquals(1, $tbl_count);
    }

    public function testMultipleTableExists()
    {
        $tbl_count = $this->db->tableExists('db', 'test%');
        $this->assertEquals(2, $tbl_count);
    }

    public function testTableNotPresent()
    {
        $tbl_not_present = $this->db->tableExists('db', "users");
        $this->assertFalse($tbl_not_present);
    }

    public function testAlterTableAddColumnNotNullDefault()
    {
        $new = new stdClass();
        $new->name = 'newCol';
        $new->dataType = 'tinyint(1)';
        $new->nn = false;
        $new->default = '1';

        $this->db->addColumn('test', $new);
        $this->assertEquals("ALTER TABLE test ADD COLUMN `newCol` tinyint(1) DEFAULT '1'", $this->db->getSql());
    }

    public function testAlterTableAddColumnNullDefault()
    {
        $new = new stdClass();
        $new->name = 'newCol';
        $new->dataType = 'varchar(50)';
        $new->nn = true;
        $new->default = null;

        $this->db->addColumn('test', $new);
        $this->assertEquals("ALTER TABLE test ADD COLUMN `newCol` varchar(50) NOT NULL DEFAULT NULL", $this->db->getSql());
    }

    /**
     * @expectedException Exception
     */
    public function testAlterTableAddColumnMissingRequiredFields()
    {
        $new = new stdClass();
        $this->db->addColumn('test', $new);
    }

    public function testAlterTableModifyColumnNotNullDefault()
    {
        $mod = new stdClass();
        $mod->name = 'default';
        $mod->new_name = 'default2';
        $mod->dataType = 'int(1)';
        $mod->nn = true;
        $mod->default = 1;

        $this->db->modifyColumn("test", $mod);
        $this->assertEquals("ALTER TABLE test MODIFY COLUMN `default` `default2` int(1) NOT NULL DEFAULT '1'", $this->db->getSql());
    }

    public function testAlterTableModifyColumnNullDefault()
    {
        $mod = new stdClass();
        $mod->name = 'default';
        $mod->dataType = 'int(2)';
        $mod->nn = false;
        $mod->default = null;

        $this->db->modifyColumn('test', $mod);
        $this->assertEquals("ALTER TABLE test MODIFY COLUMN `default` `default` int(2) DEFAULT NULL", $this->db->getSql());
    }

    /**
     * @expectedException Exception
     */
    public function testAlterTableModifyColumnMissingRequiredParameters()
    {
        $mod = new stdClass();
        $this->db->modifyColumn('test', $mod);
    }

    public function testAlterTableDropColumn()
    {
        $drop = new stdClass();
        $drop->name = 'newCol';

        $this->db->dropColumn("test", [$drop]);
        $this->assertEquals("ALTER TABLE test DROP COLUMN `newCol`", $this->db->getSql());
    }

    public function testAlterTableDropMultipleColumns()
    {
        $drop1 = new stdClass();
        $drop1->name = 'newCol1';
        $drop2 = new stdClass();
        $drop2->name = 'newCol2';

        $this->db->dropColumn('test', [$drop1, $drop2]);
        $this->assertEquals("ALTER TABLE test DROP COLUMN `newCol1`, `newCol2`", $this->db->getSql());
    }

    public function testAlterTableDropColumnString()
    {
        $this->db->dropColumn('test', 'newCol');
        $this->assertEquals("ALTER TABLE test DROP COLUMN `newCol`", $this->db->getSql());
    }

    public function testAlterTableAddConstraint()
    {
        $con = new stdClass();
        $con->field = 'field';
        $con->local = 'local_field';
        $con->id = 'unique_id';
        $con->schema = PHP_DB_SCHEMA;
        $con->table = 'test';
        $con->delete = 'CASCADE';
        $con->update = 'NO ACTION';

        $this->db->addConstraint('test', $con);
        $this->assertEquals("ALTER TABLE test ADD CONSTRAINT `unique_id` FOREIGN KEY (`local_field`) REFERENCES `db`.`test` (`field`) ON DELETE CASCADE ON UPDATE NO ACTION", $this->db->getSql());
    }

    public function testAlterTableAddConstraintArrayFields()
    {
        $con = new stdClass();
        $con->field = ['field1', 'field2'];
        $con->local = ['local1', 'local2'];
        $con->id = 'unique_id';
        $con->schema = PHP_DB_SCHEMA;
        $con->table = 'test';
        $con->delete = 'CASCADE';
        $con->update = 'NO ACTION';

        $this->db->addConstraint('test', $con);
        $this->assertEquals("ALTER TABLE test ADD CONSTRAINT `unique_id` FOREIGN KEY (`local1`,`local2`) REFERENCES `db`.`test` (`field1`,`field2`) ON DELETE CASCADE ON UPDATE NO ACTION", $this->db->getSql());
    }

    /**
     * @expectedException Exception
     */
    public function testAlterTableAddConstraintInvalidType()
    {
        $tc = new TestClass();

        $this->db->addConstraint('test', $tc);
    }

    /**
     * @expectedException Exception
     */
    public function testAlterTableAddConstraintWithInconsistentLocalAndFieldParameters()
    {
        $con = new stdClass();
        $con->field = ['field1', 'field2'];
        $con->local = 'local1';
        $con->id = 'unique_id';
        $con->schema = PHP_DB_SCHEMA;
        $con->table = 'test';
        $con->delete = 'CASCADE';
        $con->update = 'NO ACTION';

        $this->db->addConstraint('test', $con);
    }

    public function testSelectCountWithNoParameters()
    {
        $this->db->selectCount("test");
        $this->assertEquals("SELECT COUNT(1) AS 'count' FROM test", $this->db->getSql());
    }

    /**
     * @expectedException Exception
     */
    public function testAddConstraintInvalidUpdateAction()
    {
        $field = json_decode(
            '{
                "id":"unique_id",
                "local":"col1",
                "schema":"schema",
                "table":"table",
                "field":"field1",
                "delete":"CASCADE",
                "update":"WHAT"
            }'
        );
        $this->db->addConstraint('test', $field);
    }

    /**
     * @expectedException Exception
     */
    public function testAddConstraintInvalidDeleteAction()
    {
        $field = json_decode(
            '{
                "id":"unique_id",
                "local":"col1",
                "schema":"schema",
                "table":"table",
                "field":"field1",
                "delete":"WHAT",
                "update":"CASCADE"
            }'
        );
        $this->db->addConstraint('test', $field);
    }

    /**
     * @expectedException Exception
     */
    public function testAddConstraintMissingElement()
    {
        $field = json_decode(
            '{
                "id":"unique_id",
                "local":"col1",
                "schema":"schema",
                "table":"table",
                "field":"field1",
                "delete":"WHAT"
            }'
        );
        $this->db->addConstraint('test', $field);
    }

    /**
     * @expectedException Exception
     */
    public function testSelectCountWithStdClassParameterForTable()
    {
        $this->db->selectCount(new stdClass());
    }

    public function testSelectCountWithArrayWhereParameter()
    {
        $where = new DBWhere('name', 'Ed');
        $this->db->selectCount("test", [$where], [
            'joins' => [
                "JOIN settings s ON s.id = test.id"
            ]
        ]);
        $this->assertEquals("SELECT COUNT(1) AS 'count' FROM test JOIN settings s ON s.id = test.id WHERE `name` = 'Ed'", $this->db->getSql());
    }

    public function testSelectCountWithMultipleWhereClauses()
    {
        $where1 = new DBWhere('name', '%george%', DBWhere::LIKE);
        $where1->escape = false;
        $where2 = new DBWhere('state', 'IN');

        $this->db->selectCount('test', [$where1, $where2]);
        $this->assertEquals("SELECT COUNT(1) AS 'count' FROM test WHERE `name` LIKE '%george%' AND `state` = 'IN'", $this->db->getSql());
    }

    public function testInsertWithOneElementArrayParameter()
    {
        // query with one parameter
        $this->db->insert("test", [
            'id' => 1
        ]);
        $this->assertEquals("INSERT INTO test (`id`) VALUES ('1')", $this->db->getSql());
    }

    public function testInsertWithTwoElementArrayParameter()
    {
        // query with 2 parameters
        $this->db->insert("test", [
            'id' => 1,
            'name' => 'Ed'
        ], true);
        $this->assertEquals("INSERT IGNORE INTO test (`id`,`name`) VALUES ('1','Ed')", $this->db->getSql());
    }

    public function testInsertWithDBInterfaceClass()
    {
        $tc = new TestClass3();
        $this->db->insert("settings", $tc);
        $this->assertEquals("INSERT INTO settings (`meta_key`,`meta_value`) VALUES ('test3','test3')", $this->db->getSql());
    }

    public function testExtendedInsertWithDBInterfaceClass()
    {
        $tc1 = new TestClass3();
        $tc2 = new TestClass3();
        $this->db->extendedInsert('settings', ['meta_key', 'meta_value'], [$tc1, $tc2]);
        $this->assertEquals("INSERT INTO settings (`meta_key`,`meta_value`) VALUES ('test3','test3'),('test3','test3')", $this->db->getSql());
    }

    public function testInsertWithSelectStatement()
    {
        // insert query using SELECT statement
        $this->db->insert("test", "SELECT id FROM settings");
        $this->assertEquals("INSERT INTO test SELECT id FROM settings", $this->db->getSql());
    }

    public function testInsertWithExecute()
    {
        $this->db->insert('test', [
            'fk' => '1',
            'default' => '1',
            'enum' => '1'
        ]);
        $result = $this->db->execute();
        $this->assertEquals(1, $result);
    }

    public function testEInsertWithExecute()
    {
        $this->db->extendedInsert('settings', ['meta_key', 'meta_value'], [
            ['test1', 'test1'],
            ['test2', 'test2']
        ], true);
        $rows = $this->db->execute();
        $this->assertEquals(2, $rows);
    }

    public function testSelectCountWithExecute()
    {
        $this->db->selectCount('test');
        $count = $this->db->execute();
        $this->assertEquals(1, $count);
    }

    /**
     * @expectedException Exception
     */
    public function testInsertWithObject()
    {
        $fh = fopen(__DIR__ . '/test', 'r');
        $this->db->insert('settings', $fh);
    }

    public function testSelectRetrieveObject()
    {
        $this->db->select("settings");
        $row = $this->db->execute();

        $this->assertTrue(is_array($row));
        $this->assertTrue(is_object($row[0]));
    }

    public function testSelectRetrieveNumericArray()
    {
        $this->db->select("settings");
        $row = $this->db->execute(MYSQLI_NUM);

        $this->assertTrue(is_array($row));
        $this->assertTrue(isset($row[0][0]));
    }

    public function testSelectRetrieveAssciativeArray()
    {
        $this->db->select("settings");
        $row = $this->db->execute(MYSQLI_ASSOC);

        $this->assertTrue(is_array($row));
        $this->assertTrue(isset($row[0]['id']));
    }

    public function testSelectRetrieveSingleRowObject()
    {
        $where = new DBWhere('id', 1);
        $this->db->select('settings', null, [$where]);
        $row = $this->db->execute();

        $this->assertTrue(is_object($row));
    }

    public function testSelectWithJoin()
    {
        $this->db->select("test t", null, [], [
            'joins' => ["JOIN test2 t2 ON t2.id = t.fk"]
        ]);
        $this->assertEquals("SELECT * FROM test t JOIN test2 t2 ON t2.id = t.fk", $this->db->getSql());
    }

    public function testSelectWithExecute()
    {
        $this->db->select("test");
        $rows = $this->db->execute();
        $this->assertInstanceOf('stdClass', $rows);
    }

    /**
     * @expectedException Exception
     */
    public function testInsertInvalidTableNameDataType()
    {
        $this->db->insert(new stdClass());
    }

    /**
     * @expectedException Exception
     */
    public function testInsertInvalidParameterDataType()
    {
        $this->db->insert("test", new stdClass());
    }

    public function testEInsert()
    {
        // extended insert query with fields and 2 items
        $this->db->extendedInsert("test", [
            'id',
            'name'
        ], [
            [
                1,
                'Ed'
            ],
            [
                2,
                'Frank'
            ]
        ]);
        $this->assertEquals("INSERT INTO test (`id`,`name`) VALUES ('1','Ed'),('2','Frank')", $this->db->getSql());
    }

    /**
     * @expectedException Exception
     */
    public function testEInsertInvalidTableNameDatatype()
    {
        $this->db->extendedInsert(new stdClass(), [], []);
    }

    /**
     * @expectedException Exception
     */
    public function testEInsertDifferentFieldValuePairs()
    {
        $this->db->extendedInsert('test', [
            'id',
            'name'
        ], [
            [
                1
            ],
            [
                2
            ]
        ]);
    }

    /**
     * @expectedException Exception
     */
    public function testEInsertDifferentFieldValuePairs2()
    {
        $this->db->extendedInsert('test', [
            'id',
            'name'
        ], [
            [
                1,
                'Ed'
            ],
            [
                2
            ]
        ]);
    }

    /**
     * @expectedException Exception
     */
    public function testEInsertInvalidParamType()
    {
        $tc = new TestClass();
        $this->db->extendedInsert('test', ['id'], $tc);
    }

    /**
     * @expectedException Exception
     */
    public function testEInsertObjectNotUsingInterface()
    {
        $tc1 = new TestClass();
        $tc2 = new TestClass();
        $this->db->extendedInsert('test', ['test'], [$tc1, $tc2]);
    }

    public function testUpdateWithOneElementArrayParameter()
    {
        $this->db->update('test', [
            'name' => 'Frank'
        ]);
        $this->assertEquals("UPDATE test SET `name`='Frank'", $this->db->getSql());
    }

    public function testUpdateWithOneElementAndWhereArray()
    {
        $where = new DBWhere('id', 1);
        $this->db->update('test', [
            'name' => 'Frank'
        ], [$where]);
        $this->assertEquals("UPDATE test SET `name`='Frank' WHERE `id` = '1'", $this->db->getSql());
    }

    public function testUpdateWithMultipleWhereObjects()
    {
        $where1 = new DBWhere('fname', 'Fred');
        $where2 = new DBWhere('lname', 'Flintstone');
        $this->db->update('test', ['phone' => 1], [$where1, $where2]);
        $this->assertEquals("UPDATE test SET `phone`='1' WHERE `fname` = 'Fred' AND `lname` = 'Flintstone'", $this->db->getSql());
    }

    public function testUpdateWithNullValue()
    {
        $this->db->update('test', ['phone' => null, 'email' => null], new DBWhere('id', 1));
        $this->assertEquals("UPDATE test SET `phone`=NULL,`email`=NULL WHERE `id` = '1'", $this->db->getSql());
    }

    public function testUpdateWithOneElementAndJoinClause()
    {
        $this->db->update('test t', [
            't.name' => 'Frank'
        ], [], [
            'joins' => [
                "JOIN settings s ON s.id=t.id"
            ]
        ]);
        $this->assertEquals("UPDATE test t JOIN settings s ON s.id=t.id SET t.name='Frank'", $this->db->getSql());
    }

    public function testUpdateWithOneElement()
    {
        $this->db->update('test', [
            'name' => 'Frank'
        ]);
        $this->assertEquals("UPDATE test SET `name`='Frank'", $this->db->getSql());
    }

    /**
     * @expectedException Exception
     */
    public function testUpdateWithInvalidTableName()
    {
        $this->db->update(new stdClass(), []);
    }

    /**
     * @expectedException Exception
     */
    public function testUpdateWithNonDBInterfaceParamClass()
    {
        $this->db->update("settings", new stdClass());
    }

    /**
     * @expectedException Exception
     */
    public function testUpdateWithInvalidType()
    {
        $this->db->update("settings", 1);
    }

    public function testUpdateWithDBInterfaceClass()
    {
        $tc = new TestClass3();
        $this->db->update("settings", $tc);
        $this->assertEquals("UPDATE settings SET `field1`='george',`field2`='frank'", $this->db->getSql());
    }

    public function testEUpdateWithArrayList()
    {
        $this->db->extendedUpdate("test", "settings", "id", [
            'name'
        ]);
        $this->assertEquals("UPDATE test tbu INNER JOIN settings o USING (id) SET tbu.`name` = o.`name`", $this->db->getSql());
    }

    public function testEUpdateWithStringList()
    {
        $this->db->extendedUpdate("test", "settings", "id", "name");
        $this->assertEquals("UPDATE test tbu INNER JOIN settings o USING (id) SET tbu.`name` = o.`name`", $this->db->getSql());
    }

    /**
     * @expectedException Exception
     */
    public function testEUpdateWithNullParameter()
    {
        $this->db->extendedUpdate('test', null, null, null);
    }

    /**
     * @expectedException Exception
     */
    public function testEUpdateInvalidParamDatatype()
    {
        $this->db->extendedUpdate('test', 'settings', 'id', new stdClass());
    }

    public function testReplace()
    {
        $this->db->replace("test", [
            'id' => 1
        ]);
        $this->assertEquals("REPLACE INTO test (`id`) VALUES ('1')", $this->db->getSql());
    }

    /**
     * @expectedException Exception
     */
    public function testReplaceInvalidTableNameDatatype()
    {
        $this->db->replace(new stdClass(), []);
    }

    public function testReplaceInterfaceClass()
    {
        $ob = new \TestClass3();
        $this->db->replace('test', $ob);
        $this->assertEquals("REPLACE INTO test (`id`,`meta_key`,`meta_value`) VALUES ('3','test3','test25')", $this->db->getSql());
    }

    public function testEReplace()
    {
        $this->db->extendedReplace("test", [
            'id',
            'name'
        ], [
            [
                1,
                'Ed'
            ],
            [
                2,
                'Frank'
            ]
        ]);
        $this->assertEquals("REPLACE INTO test (`id`,`name`) VALUES ('1','Ed'),('2','Frank')", $this->db->getSql());
    }

    /**
     * @expectedException Exception
     */
    public function testEReplaceInvalidTableNameDatatype()
    {
        $this->db->extendedReplace(new stdClass(), [], []);
    }

    public function testFieldExists()
    {
        $id_exists = $this->db->fieldExists('test', 'id');
        $this->assertTrue($id_exists);
    }

    public function testFieldDoesNotExist()
    {
        $phone_not_exists = $this->db->fieldExists('test', 'phone');
        $this->assertFalse($phone_not_exists);
    }

    public function testFieldData()
    {
        $id = new stdClass();
        $id->name = 'id';
        $id->orgname = 'id';
        $id->table = 'test2';
        $id->orgtable = 'test2';
        $id->def = null;
        $id->db = 'db';
        $id->catalog = 'def';
        $id->max_length = 0;
        $id->length = 11;
        $id->charsetnr = 63;
        $id->flags = 49667;
        $id->type = 3;
        $id->decimals = 0;

        // query all fields in table
        $fd = $this->db->fieldData("test2");
        $this->assertEquals([
            'id' => $id
        ], $fd);

        // query single field in table
        $fd = $this->db->fieldData('test2', 'id');
        $this->assertEquals([
            'id' => $id
        ], $fd);

        // query array of fields in table
        $fd = $this->db->fieldData('test2', [
            'id'
        ]);
        $this->assertEquals([
            'id' => $id
        ], $fd);

        // invalid datatype for field name
        $fd = $this->db->fieldData('test2', new stdClass());
        $this->assertEquals(null, $fd);
    }

    public function testFieldCheck()
    {
        $fd = $this->db->fieldData('settings', ['meta_key']);
        $mk_data = (object) [
            'name' => 'meta_key',
            'length' => 100,
            'flags' => MYSQLI_UNIQUE_KEY_FLAG,
            'type' => MYSQLI_TYPE_VAR_STRING,
            'dataType' => 'varchar(100)',
            'nn' => true,
            'default' => ''
        ];

        $result = $this->db->fieldCheck($fd['meta_key'], $mk_data, [], null);
        $this->assertNull($result);
    }

    public function testDeleteBasic()
    {
        $this->db->delete("test");
        $this->assertEquals("DELETE FROM test", $this->db->getSql());
    }

    public function testDeleteWithWhereClause()
    {
        $where = new DBWhere('id', 1);
        $this->db->delete('test', [
            'id'
        ], [$where]);
        $this->assertEquals("DELETE id FROM test WHERE `id` = '1'", $this->db->getSql());
    }

    public function testDeleteWithMultipleWhereClauses()
    {
        $where1 = new DBWhere('name', '%george%', DBWhere::LIKE);
        $where1->escape = false;
        $where2 = new DBWhere('state', 'IN');

        $this->db->delete('test', null, [$where1, $where2]);
        $this->assertEquals("DELETE FROM test WHERE `name` LIKE '%george%' AND `state` = 'IN'", $this->db->getSql());
    }

    public function testDeleteWithJoin()
    {
        $this->db->delete('test t', [], [], [
            'joins' => "JOIN settings s ON s.id=t.id"
        ]);
        $this->assertEquals("DELETE FROM test t JOIN settings s ON s.id=t.id", $this->db->getSql());
    }

    /**
     * @expectedException Exception
     */
    public function testDeleteInvalidTableNameDatatype()
    {
        $this->db->delete(new stdClass());
    }

    public function testTruncate()
    {
        $this->db->truncate('test');
        $this->assertEquals("TRUNCATE TABLE test", $this->db->getSql());
    }

    /**
     * @expectedException Exception
     */
    public function testTruncateInvalidTableNameDatatype()
    {
        $this->db->truncate(new stdClass());
    }

    public function testDropSettingsTable()
    {
        $sql = $this->db->drop("settings");
        $this->assertEquals("DROP TABLE IF EXISTS `settings`", $sql);
    }

    public function testDropTestTable()
    {
        $sql = $this->db->drop("test");
        $this->assertEquals("DROP TABLE IF EXISTS `test`", $sql);
    }

    public function testDropWithExecute()
    {
        $this->db->drop('test');
        $result = $this->db->execute();
        $this->assertTrue($result);
    }

    public function testDropView()
    {
        $sql = $this->db->drop("test", "view");
        $this->assertEquals("DROP VIEW IF EXISTS `test`", $sql);
    }

    /**
     * @expectedException Exception
     */
    public function testDropInvalidTableNameDatatype()
    {
        $this->db->drop(new stdClass());
    }

    /**
     * @expectedException Exception
     */
    public function testDropInvalidTypeDatatype()
    {
        $this->db->drop('test', new stdClass());
    }

    public function testSetSchema()
    {
        // set the schema and validate that it is what we set it to
        $this->db->setSchema("test");
        $row = $this->db->query("SELECT DATABASE()");
        $this->assertEquals("test", $row->fetch_array()[0]);
    }

    public function testQuery()
    {
        $this->db->select("settings");
        $ret = $this->db->query();
        $this->assertInstanceOf('mysqli_result', $ret);
    }

    /**
     *
     */
    public function testGetQueryType()
    {
        $this->db->insert("settings", ['meta_key' => 'hello', 'meta_value' => 'world']);
        $this->assertEquals(Database::INSERT, $this->db->getQueryType());
    }

    public function testSetQueryType()
    {
        $this->db->insert("settings", ['meta_key' => 'hello', 'meta_value' => 'world']);
        $this->db->setQueryType(Database::REPLACE);
        $this->assertEquals(Database::REPLACE, $this->db->getQueryType());
    }

    public function testEscapeDateTime()
    {
        $dt = new DateTime('2019-01-01 00:00:00');
        $where = new DBWhere('date', $dt, ">=");
        $this->db->select("test", null, $where);
        $this->assertEquals("SELECT * FROM test WHERE `date` >= '2019-01-01 00:00:00'", $this->db->getSql());
    }

    public function testEscapeBoolean()
    {
        $bool = true;
        $where = new DBWhere('active', $bool);
        $this->db->select('test', null, $where);
        $this->assertEquals("SELECT * FROM test WHERE `active` = '1'", $this->db->getSql());
    }

    public function testEscapeArray()
    {
        $arr = ['Fred', 'Barney'];
        $where = new DBWhere('name', $arr, DBWhere::IN);
        $this->db->select('test', null, $where);
        $this->assertEquals("SELECT * FROM test WHERE `name` IN ('Fred','Barney')", $this->db->getSql());
    }

    public function testEscapeObject()
    {
        $ob = new \TestClass();
        $ob->var = "This is Frank's";
        $where = new DBWhere('comment', $ob);
        $this->db->select('test', null, $where);
        $this->assertEquals("SELECT * FROM test WHERE `comment` = This is Frank\'s", $this->db->getSql());
    }

    /**
     * @expectedException Exception
     */
    public function testEscapeObjectNoMethod()
    {
        $ob = new \TestClass2();
        $where = new DBWhere('test', $ob);
        $this->db->select('test', null, $where);
    }

    /**
     * @expectedException Exception
     */
    public function testEscapeInvalidMethodReturn()
    {
        $ob = new \TestClass4();
        $where = new DBWhere('test', $ob);
        $this->db->select('test', null, $where);
    }

    public function testClassWhere()
    {
        $ob = new \TestClass3();
        $this->db->select('test', null, $ob);
        $this->assertEquals("SELECT * FROM test WHERE `foo` = 'bar'", $this->db->getSql());
    }

    public function testClassWhereError()
    {
        $ob = new \TestClass4();
        $ret = $this->db->select('test', null, [$ob]);
        $this->assertEquals("SELECT * FROM test", $ret);
    }

    public function testisJson()
    {
        $json = json_encode("{'test':'test'}");
        $this->assertTrue($this->db->isJson($json));
    }

    public function testDropAllTables()
    {
        $this->db->drop('settings');
        $this->assertTrue($this->db->execute());
        $this->db->drop('test2');
        $this->assertTrue($this->db->execute());
    }

    public function testCreateTableWithClass()
    {
        $field1 = new DBCreateTable('id', 'int(11)');
        $field1->option = 'PRIMARY KEY AUTO_INCREMENT';
        $field2 = new DBCreateTable('name', 'varchar(255)');

        $this->db->createTable('test3', false, [$field1, $field2]);

        $this->assertEquals("CREATE TABLE IF NOT EXISTS test3 (`id` int(11) PRIMARY KEY AUTO_INCREMENT,`name` varchar(255))", $this->db->getSql());
    }
}
