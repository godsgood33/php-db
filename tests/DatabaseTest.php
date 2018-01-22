<?php
use Godsgood33\Php_Db\Database;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;

require_once 'TestClass.php'; // class with _escape method
require_once 'TestClass2.php';

// class without _escape method

/**
 * @coversDefaultClass Database
 */
final class DatabaseTest extends TestCase
{

    private $db;

    public function setUp()
    {
        $this->db = new Database();
        Database::$autorun = true;
    }

    public function testCanCreateDatabaseInstance()
    {
        $this->assertInstanceOf("Godsgood33\Php_Db\Database", $this->db);
    }

    public function testGetSchema()
    {
        $schema = $this->db->get_schema();
        $this->assertEquals("db", $schema);
    }

    /**
     * @expectedException Exception
     */
    public function testSetSchemaWithNonExistentSchema()
    {
        $this->db->set_schema("george");
    }

    public function testDatabaseConnection()
    {
        $this->assertTrue($this->db->is_connected());
    }

    public function testPassInMysqliConnection()
    {
        $conn = new mysqli(PHP_DB_SERVER, PHP_DB_USER, PHP_DB_PWD, PHP_DB_SCHEMA);
        if ($conn->connect_errno) {
            fwrite(STDOUT, $conn->connect_error);
        }

        $this->db = new Database($conn);

        $this->assertInstanceOf("Godsgood33\Php_Db\Database", $this->db);
    }

    public function testSetLogLevel()
    {
        $this->db->set_log_level(LogLevel::DEBUG);
        $this->assertEquals(LogLevel::DEBUG, $this->db->log_level);
    }

    /**
     * @expectedException Exception
     */
    public function testSelectWithNullTableName()
    {
        $this->db->select(new stdClass());
    }

    public function testSelectWithNoParameters()
    {
        // query table with NO parameters
        $this->db->select("test");
        $this->assertEquals("SELECT * FROM test", $this->db->sql);
    }

    public function testSelectWithNullFieldParameter()
    {
        // query table with null fields parameter
        $this->db->select("test", null);
        $this->assertEquals("SELECT * FROM test", $this->db->sql);
    }

    public function testSelectWithOneArrayParameter()
    {
        // query table with one parameter
        $this->db->select("test", [
            'id'
        ]);
        $this->assertEquals("SELECT `id` FROM test", $this->db->sql);
    }

    public function testSelectWithTwoArrayParameters()
    {
        // query table with 2 parameters
        $this->db->select("test", [
            'id',
            'name'
        ]);
        $this->assertEquals("SELECT `id`,`name` FROM test", $this->db->sql);
    }

    public function testSelectWithOneStringParameter()
    {
        // query table with string parameter
        $this->db->select("test", 'id');
        $this->assertEquals("SELECT id FROM test", $this->db->sql);
    }

    public function testSelectWithStdClassParameter()
    {
        // query table with object parameter
        $this->db->select("test", new stdClass());
        $this->assertEquals("SELECT  FROM test", $this->db->sql);
    }

    public function testSelectWithNullWhereParameter()
    {
        // query table with null where parameter
        $this->db->select("test", 'id', null);
        $this->assertEquals("SELECT id FROM test", $this->db->sql);
    }

    public function testSelectWithEmptyArrayWhereParameter()
    {
        // query table with empty array where paramter
        $this->db->select("test", 'id', []);
        $this->assertEquals("SELECT id FROM test", $this->db->sql);
    }

    public function testSelectWithImcompleteWhereArrayParameter()
    {
        // query with incomplete WHERE clause
        $this->db->select("test", 'id', [
            [
                'field' => 'id'
            ]
        ]);
        $this->assertEquals("SELECT id FROM test", $this->db->sql);
    }

    public function testGroupWithString()
    {
        // $this->markTestIncomplete();

        // query with single group by string
        $sql = $this->db->groups('name');
        $this->assertEquals(" GROUP BY name", $sql);
    }

    public function testGroupWithArray()
    {
        // query with array group by string
        $sql = $this->db->groups([
            'name',
            'id'
        ]);
        $this->assertEquals(" GROUP BY name, id", $sql);
    }

    /**
     * @expectedException Exception
     */
    public function testGroupWrongUnknownDataType()
    {
        // $this->markTestIncomplete();

        // query group with invalid datatype (stdClass) should throw Exception
        $this->db->groups(new stdClass());
    }

    public function testOrderWithString()
    {
        // $this->markTestIncomplete();

        // query with single name order parameter
        $sql = $this->db->order("name");
        $this->assertEquals(" ORDER BY name", $sql);
    }

    public function testOrderWithArray()
    {
        // query with order array
        $sql = $this->db->order([
            [
                'field' => 'id',
                'sort' => 'ASC'
            ],
            [
                'field' => 'name',
                'sort' => 'DESC'
            ]
        ]);
        $this->assertEquals(" ORDER BY id ASC, name DESC", $sql);
    }

    public function testOrderWithObject()
    {
        // query with invalid datatype (stdClass) will return empty string
        $sql = $this->db->order(new stdClass());
        $this->assertEquals("", $sql);
    }

    public function testFlags()
    {
        // $this->markTestIncomplete();

        // query flags with all parameters
        $sql = $this->db->flags([
            'group' => 'name',
            'order' => 'name',
            'having' => [
                [
                    'field' => 'id',
                    'op' => '=',
                    'value' => 1
                ]
            ],
            'limit' => '10',
            'start' => '5'
        ]);
        $this->assertEquals(" GROUP BY name HAVING `id` = '1' ORDER BY name LIMIT 5,10", $sql);
    }

    public function testCreateTemporaryTable()
    {
        $this->db->sql = "SELECT * FROM test";
        $this->db->create_table('test', true);
        $this->assertEquals("CREATE TEMPORARY TABLE IF NOT EXISTS test AS (SELECT * FROM test)", $this->db->sql);
    }

    public function testCreateTable()
    {
        Database::$autorun = false;
        $this->db->create_table('test', false, $this->db->select("test"));
        $this->assertEquals("CREATE TABLE IF NOT EXISTS test AS (SELECT * FROM test)", $this->db->sql);
        Database::$autorun = true;
    }

    public function testCreateTableWithArrayParameter()
    {
        $this->db->create_table("test", true, [
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
        $this->assertEquals("CREATE TEMPORARY TABLE IF NOT EXISTS test (`id` int(11) PRIMARY KEY,`name` varchar(100),`email` varchar(100) DEFAULT '')", $this->db->sql);
    }

    public function testCreateTableJson()
    {
        $json = json_decode(file_get_contents(dirname(dirname(__FILE__)) . "/examples/create_table_json.json"));

        $this->db->create_table_json($json->tables[0]);
        $this->assertEquals("CREATE TABLE IF NOT EXISTS `settings` (`id` int(11) AUTO_INCREMENT NOT NULL,`meta_key` varchar(100) NOT NULL,`meta_value` mediumtext DEFAULT NULL, UNIQUE(`meta_key`), PRIMARY KEY(`id`))", $this->db->sql);
    }

    public function testCreateTableJson2()
    {
        $json = json_decode(file_get_contents(dirname(dirname(__FILE__)) . "/examples/create_table_json.json"));

        $this->db->create_table_json($json->tables[1]);
        $this->assertEquals("CREATE TABLE IF NOT EXISTS `test` (`id` int(11) AUTO_INCREMENT NOT NULL,`fk` int(11) NOT NULL,`default` tinyint(1) DEFAULT '0',`enum` enum('1','2') DEFAULT '1', INDEX `default_idx` (`default`), CONSTRAINT `con_1` FOREIGN KEY (`fk`) REFERENCES `db`.`test` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION, PRIMARY KEY(`id`,`fk`))", $this->db->sql);
    }

    public function testCreateTableJson3()
    {
        $json = json_decode(file_get_contents(dirname(dirname(__FILE__)) . "/examples/create_table_json.json"));

        $this->db->create_table_json($json->tables[2]);
        $this->assertEquals("CREATE TABLE IF NOT EXISTS `test2` (`id` int(11) AUTO_INCREMENT NOT NULL, PRIMARY KEY(`id`))", $this->db->sql);
    }

    public function testTableExists()
    {
        $tbl_count = $this->db->table_exists('db', 'settings');
        $this->assertEquals(1, $tbl_count);
    }

    public function testMultipleTableExists()
    {
        $tbl_count = $this->db->table_exists('db', 'test%');
        $this->assertEquals(2, $tbl_count);
    }

    public function testTableNotPresent()
    {
        $tbl_not_present = $this->db->table_exists('db', "users");
        $this->assertFalse($tbl_not_present);
    }

    public function testAlterTableAddColumn()
    {
        $new = new stdClass();
        $new->name = 'newCol';
        $new->dataType = 'tinyint(1)';
        $new->nn = false;
        $new->default = null;

        $this->db->alter_table('test', 'add-column', $new);
        $this->assertEquals("ALTER TABLE test ADD COLUMN `newCol` tinyint(1) DEFAULT NULL", $this->db->sql);
    }

    public function testAlterTableModifyColumn()
    {
        $mod = new stdClass();
        $mod->name = 'default';
        $mod->new_name = 'default2';
        $mod->dataType = 'int(1)';
        $mod->nn = true;
        $mod->default = 1;

        $this->db->alter_table("test", 'modify-column', $mod);
        $this->assertEquals("ALTER TABLE test MODIFY COLUMN `default` `default2` int(1) NOT NULL DEFAULT '1'", $this->db->sql);
    }

    public function testAlterTableDropColumn()
    {
        $drop = new stdClass();
        $drop->name = 'newCol';

        $this->db->alter_table("test", 'drop-column', [
            $drop
        ]);
        $this->assertEquals("ALTER TABLE test DROP COLUMN `newCol`", $this->db->sql);
    }

    public function testSelectCountWithNoParameters()
    {
        $this->db->select_count("test");
        $this->assertEquals("SELECT COUNT(1) AS 'count' FROM test", $this->db->sql);
    }

    public function testSelectCountWithStdClassParameterForTable()
    {
        $this->db->select_count(new stdClass());
        $this->assertEquals(null, $this->db->sql);
    }

    public function testSelectCountWithArrayWhereParameter()
    {
        $this->db->select_count("test", [
            [
                'field' => 'name',
                'value' => 'Ed'
            ]
        ], [
            'joins' => [
                "JOIN settings s ON s.id = test.id"
            ]
        ]);
        $this->assertEquals("SELECT COUNT(1) AS 'count' FROM test JOIN settings s ON s.id = test.id WHERE `name` = 'Ed'", $this->db->sql);
    }

    public function testInsertWithOneElementArrayParameter()
    {
        // query with one parameter
        $this->db->insert("test", [
            'id' => 1
        ]);
        $this->assertEquals("INSERT INTO test (`id`) VALUES ('1')", $this->db->sql);
    }

    public function testInsertWithTwoElementArrayParameter()
    {
        // query with 2 parameters
        $this->db->insert("test", [
            'id' => 1,
            'name' => 'Ed'
        ], true);
        $this->assertEquals("INSERT IGNORE INTO test (`id`,`name`) VALUES ('1','Ed')", $this->db->sql);
    }

    public function testInsertWithSelectStatement()
    {
        // insert query using SELECT statement
        $this->db->insert("test", "SELECT id FROM settings");
        $this->assertEquals("INSERT INTO test SELECT id FROM settings", $this->db->sql);
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
        $this->db->extended_insert("test", [
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
        $this->assertEquals("INSERT INTO test (`id`,`name`) VALUES ('1','Ed'),('2','Frank')", $this->db->sql);
    }

    /**
     * @expectedException Exception
     */
    public function testEInsertInvalidTableNameDatatype()
    {
        $this->db->extended_insert(new stdClass(), [], []);
    }

    /**
     * @expectedException Exception
     */
    public function testEInsertDifferentFieldValuePairs()
    {
        $this->db->extended_insert('test', [
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
        $this->db->extended_insert('test', [
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
        $this->assertEquals("UPDATE test SET `name`='Frank'", $this->db->sql);
    }

    public function testUpdateWithOneElementAndWhereArray()
    {
        $this->db->update('test', [
            'name' => 'Frank'
        ], [
            [
                'field' => 'id',
                'value' => 1
            ]
        ]);
        $this->assertEquals("UPDATE test SET `name`='Frank' WHERE `id` = '1'", $this->db->sql);
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
        $this->assertEquals("UPDATE test t JOIN settings s ON s.id=t.id SET t.name='Frank'", $this->db->sql);
    }

    public function testUpdateWithOneElementAndLimitClause()
    {
        $this->db->update('test', [
            'name' => 'Frank'
        ], [], [
            'limit' => 1
        ]);
        $this->assertEquals("UPDATE test SET `name`='Frank' LIMIT 1", $this->db->sql);
    }

    /**
     * @expectedException Exception
     */
    public function testUpdateInvalidTableNameDatatype()
    {
        $this->db->update(new stdClass(), []);
    }

    public function testEUpdateWithArrayList()
    {
        $this->db->extended_update("test", "settings", "id", [
            'name'
        ]);
        $this->assertEquals("UPDATE test tbu INNER JOIN settings o USING (id) SET tbu.`name` = o.`name`", $this->db->sql);
    }

    public function testEUpdateWithStringList()
    {
        $this->db->extended_update("test", "settings", "id", "name");
        $this->assertEquals("UPDATE test tbu INNER JOIN settings o USING (id) SET tbu.`name` = o.`name`", $this->db->sql);
    }

    /**
     * @expectedException Exception
     */
    public function testEUpdateInvalidParamDatatype()
    {
        $this->db->extended_update('test', 'settings', 'id', new stdClass());
    }

    public function testReplace()
    {
        $this->db->replace("test", [
            'id' => 1
        ]);
        $this->assertEquals("REPLACE INTO test (`id`) VALUES ('1')", $this->db->sql);
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
        $this->db->extended_replace("test", [
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
        $this->assertEquals("REPLACE INTO test (`id`,`name`) VALUES ('1','Ed'),('2','Frank')", $this->db->sql);
    }

    /**
     * @expectedException Exception
     */
    public function testEReplaceInvalidTableNameDatatype()
    {
        $this->db->extended_replace(new stdClass(), [], []);
    }

    public function testFieldExists()
    {
        $id_exists = $this->db->field_exists('test', 'id');
        $this->assertTrue($id_exists);
    }

    public function testFieldDoesNotExist()
    {
        $phone_not_exists = $this->db->field_exists('test', 'phone');
        $this->assertFalse($phone_not_exists);
    }

    public function testFieldData()
    {
        $id = new stdClass();
        $id->{'name'} = 'id';
        $id->{'orgname'} = 'id';
        $id->{'table'} = 'test2';
        $id->{'orgtable'} = 'test2';
        $id->{'def'} = null;
        $id->{'db'} = 'db';
        $id->{'catalog'} = 'def';
        $id->{'max_length'} = 0;
        $id->{'length'} = 11;
        $id->{'charsetnr'} = 63;
        $id->{'flags'} = 49667;
        $id->{'type'} = 3;
        $id->{'decimals'} = 0;

        // query all fields in table
        $fd = $this->db->field_data("test2");
        $this->assertEquals([
            'id' => $id
        ], $fd);

        // query single field in table
        $fd = $this->db->field_data('test2', 'id');
        $this->assertEquals([
            'id' => $id
        ], $fd);

        // query array of fields in table
        $fd = $this->db->field_data('test2', [
            'id'
        ]);
        $this->assertEquals([
            'id' => $id
        ], $fd);

        // invalid datatype for field name
        $fd = $this->db->field_data('test2', new stdClass());
        $this->assertEquals(null, $fd);
    }

    public function testEscapeDontEscapeNow()
    {
        // $this->markTestIncomplete();
        $ret = $this->db->_escape('NOW()', false);
        $this->assertEquals("NOW()", $ret);
    }

    public function testEscapeDontEscapeBackticks()
    {
        $ret = $this->db->_escape("t.`id`", false);
        $this->assertEquals("t.`id`", $ret);
    }

    public function testEscapeEscapeDateTime()
    {
        $dt = new DateTime("2017-01-01 00:00:00");
        $ret = $this->db->_escape($dt);
        $this->assertEquals("'2017-01-01 00:00:00'", $ret);
    }

    public function testEscapeBoolean()
    {
        $ret = $this->db->_escape(true);
        $this->assertEquals("'1'", $ret);
    }

    public function testEscapeClassWithEscapeMethod()
    {
        $tc = new TestClass();
        $tc->var = "test's";
        $ret = $this->db->_escape($tc);
        $this->assertEquals("test\'s", $ret);
    }

    /**
     * @expectedException Exception
     */
    public function testEscapeUnknownClassToEscape()
    {
        // $this->markTestIncomplete();
        $tc2 = new TestClass2();
        $tc2->var = "test";
        $ret = $this->db->_escape($tc2);
    }

    public function testDeleteBasic()
    {
        $this->db->delete("test");
        $this->assertEquals("DELETE FROM test", $this->db->sql);
    }

    public function testDeleteWithWhereClause()
    {
        $this->db->delete('test', [
            'id'
        ], [
            [
                'field' => 'id',
                'op' => '=',
                'value' => 1
            ]
        ]);
        $this->assertEquals("DELETE id FROM test WHERE `id` = '1'", $this->db->sql);
    }

    public function testDeleteWithJoin()
    {
        $this->db->delete('test t', null, [], [
            'joins' => "JOIN settings s ON s.id=t.id"
        ]);
        $this->assertEquals("DELETE FROM test t JOIN settings s ON s.id=t.id", $this->db->sql);
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
        $this->assertEquals("TRUNCATE TABLE test", $this->db->sql);
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
        Database::$autorun = false;
        $sql = $this->db->drop("settings");
        $this->assertEquals("DROP TABLE IF EXISTS `settings`", $sql);
        Database::$autorun = true;
    }

    public function testDropTestTable()
    {
        Database::$autorun = false;
        $sql = $this->db->drop("test");
        $this->assertEquals("DROP TABLE IF EXISTS `test`", $sql);
        Database::$autorun = true;
    }

    public function testDropView()
    {
        Database::$autorun = false;
        $sql = $this->db->drop("test", "view");
        $this->assertEquals("DROP VIEW IF EXISTS `test`", $sql);
        Database::$autorun = true;
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
        $this->db->set_schema("test");
        $row = $this->db->query("SELECT DATABASE()");
        $this->assertEquals("test", $row->fetch_array()[0]);
    }
}