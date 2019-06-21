<?php
use Godsgood33\Php_Db\Database;
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
        if(defined('PHP_DB_ENCRYPT') && PHP_DB_ENCRYPT) {
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

    public function testCreateTemporaryTable()
    {
        $this->db->select("test");
        $this->db->createTable('test2', true);
        $this->assertEquals("CREATE TEMPORARY TABLE IF NOT EXISTS test2 AS (SELECT * FROM test)", $this->db->getSql());
    }

    public function testCreateTable()
    {
        // Database::$autorun = false;
        $this->db->createTable('test', false, $this->db->select("test"));
        $this->assertEquals("CREATE TABLE IF NOT EXISTS test AS (SELECT * FROM test)", $this->db->getSql());
        // Database::$autorun = true;
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

    public function testCreateTableJson()
    {
        $json = json_decode(file_get_contents(dirname(dirname(__FILE__)) . "/examples/create_table_json.json"));

        $this->db->createTableJson($json->tables[0]);
        $this->assertEquals("CREATE TABLE IF NOT EXISTS `settings` (`id` int(11) AUTO_INCREMENT NOT NULL,`meta_key` varchar(100) NOT NULL,`meta_value` mediumtext DEFAULT NULL, UNIQUE(`meta_key`), PRIMARY KEY(`id`))", $this->db->getSql());
    }

    public function testCreateTableJson2()
    {
        $json = json_decode(file_get_contents(dirname(dirname(__FILE__)) . "/examples/create_table_json.json"));

        $this->db->createTableJson($json->tables[1]);
        $this->assertEquals("CREATE TABLE IF NOT EXISTS `test` (`id` int(11) AUTO_INCREMENT NOT NULL,`fk` int(11) NOT NULL,`default` tinyint(1) DEFAULT '0',`enum` enum('1','2') DEFAULT '1', INDEX `default_idx` (`default`), PRIMARY KEY(`id`,`fk`))", $this->db->getSql());
    }

    public function testCreateTableJson3()
    {
        $json = json_decode(file_get_contents(dirname(dirname(__FILE__)) . "/examples/create_table_json.json"));

        $this->db->createTableJson($json->tables[2]);
        $this->assertEquals("CREATE TABLE IF NOT EXISTS `test2` (`id` int(11) AUTO_INCREMENT NOT NULL, PRIMARY KEY(`id`))", $this->db->getSql());
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

    public function testAlterTableAddColumn()
    {
        $new = new stdClass();
        $new->name = 'newCol';
        $new->dataType = 'tinyint(1)';
        $new->nn = false;
        $new->default = null;

        $this->db->alterTable('test', Database::ADD_COLUMN, $new);
        $this->assertEquals("ALTER TABLE test ADD COLUMN `newCol` tinyint(1) DEFAULT NULL", $this->db->getSql());
    }

    public function testAlterTableModifyColumn()
    {
        $mod = new stdClass();
        $mod->name = 'default';
        $mod->new_name = 'default2';
        $mod->dataType = 'int(1)';
        $mod->nn = true;
        $mod->default = 1;

        $this->db->alterTable("test", Database::MODIFY_COLUMN, $mod);
        $this->assertEquals("ALTER TABLE test MODIFY COLUMN `default` `default2` int(1) NOT NULL DEFAULT '1'", $this->db->getSql());
    }

    public function testAlterTableDropColumn()
    {
        $drop = new stdClass();
        $drop->name = 'newCol';

        $this->db->alterTable("test", Database::DROP_COLUMN, [
            $drop
        ]);
        $this->assertEquals("ALTER TABLE test DROP COLUMN `newCol`", $this->db->getSql());
    }

    public function testSelectCountWithNoParameters()
    {
        $this->db->selectCount("test");
        $this->assertEquals("SELECT COUNT(1) AS 'count' FROM test", $this->db->getSql());
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

    public function testInsertWithSelectStatement()
    {
        // insert query using SELECT statement
        $this->db->insert("test", "SELECT id FROM settings");
        $this->assertEquals("INSERT INTO test SELECT id FROM settings", $this->db->getSql());
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

    public function testUpdateWithOneElementAndLimitClause()
    {
        $this->db->update('test', [
            'name' => 'Frank'
        ], [], [
            'limit' => 1
        ]);
        $this->assertEquals("UPDATE test SET `name`='Frank' LIMIT 1", $this->db->getSql());
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
        $this->assertEquals("UPDATE settings SET `meta_value`='george'", $this->db->getSql());
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
        // Database::$autorun = false;
        $sql = $this->db->drop("settings");
        $this->assertEquals("DROP TABLE IF EXISTS `settings`", $sql);
        // Database::$autorun = true;
    }

    public function testDropTestTable()
    {
        // Database::$autorun = false;
        $sql = $this->db->drop("test");
        $this->assertEquals("DROP TABLE IF EXISTS `test`", $sql);
        // Database::$autorun = true;
    }

    public function testDropView()
    {
        // Database::$autorun = false;
        $sql = $this->db->drop("test", "view");
        $this->assertEquals("DROP VIEW IF EXISTS `test`", $sql);
        // Database::$autorun = true;
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
        $ret = $this->db->select('test', null, $where);
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
}