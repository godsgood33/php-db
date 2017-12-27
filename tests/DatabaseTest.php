<?php
require_once 'vendor/autoload.php';
require_once 'src/Database.php';
require_once 'TestClass.php';  // class with _escape method
require_once 'TestClass2.php';  // class without _escape method

use PHPUnit\Framework\TestCase;
use Godsgood33\Php_Db\Database;
use Psr\Log\LogLevel;

/**
 * @coversDefaultClass Database
 */
final class DatabaseTest extends TestCase {

  public function testCanCreateDatabaseInstance() {
    $db = new Database();

    $this->assertInstanceOf("Godsgood33\Php_Db\Database", $db);
  }

  public function testDatabaseConnection() {
    $db = new Database();

    $this->assertTrue($db->is_connected());
  }

  public function testSetSchema() {
    $db = new Database();

    // set the schema and validate that it is what we set it to
    $db->set_schema("os");
    $row = $db->query("SELECT DATABASE()");
    $this->assertEquals("os", $row->fetch_array()[0]);

    $db->sql = "SELECT DATABASE()";
    $row = $db->query();
    $this->assertEquals("os", $row->fetch_array()[0]);
  }

  public function testSetLogLevel() {
    $db = new Database();

    $db->set_log_level(LogLevel::DEBUG);
    $this->assertEquals(LogLevel::DEBUG, $db->log_level);
  }

  /**
   * @expectedException Exception
   */
  public function testSelectWithNullTableName() {
    $db = new Database();

    $db->select(new stdClass());
  }

  public function testSelect() {
    $db = new Database();

    // query table with NO parameters
    $db->select("test");
    $this->assertEquals("SELECT * FROM test", $db->sql);

    // query table with null fields parameter
    $db->select("test", null);
    $this->assertEquals("SELECT * FROM test", $db->sql);

    // query table with one parameter
    $db->select("test", [
        'id'
    ]);
    $this->assertEquals("SELECT `id` FROM test", $db->sql);

    // query table with 2 parameters
    $db->select("test", [
        'id',
        'name'
    ]);
    $this->assertEquals("SELECT `id`,`name` FROM test", $db->sql);

    // query table with string parameter
    $db->select("test", 'id');
    $this->assertEquals("SELECT id FROM test", $db->sql);

    // query table with object parameter
    $db->select("test", new stdClass());
    $this->assertEquals("SELECT  FROM test", $db->sql);

    // query table with null where parameter
    $db->select("test", 'id', null);
    $this->assertEquals("SELECT id FROM test", $db->sql);

    // query table with empty array where paramter
    $db->select("test", 'id', []);
    $this->assertEquals("SELECT id FROM test", $db->sql);

    // query with incomplete WHERE clause
    $db->select("test", 'id', [
        [
            'field' => 'id'
        ]
    ]);
    $this->assertEquals("SELECT id FROM test", $db->sql);

    $db->select("test", 'id', [], [
        'joins' => [
            "JOIN settings"
        ]
    ]);
    $this->assertEquals("SELECT id FROM test JOIN settings", $db->sql);
  }

  public function testWhere() {
    $db = new Database();

    // send empty where array will return empty string
    $sql = $db->where([]);
    $this->assertEquals("", $sql);

    // query with one parameter and two parenthese, second clause just has the close paren
    $sql = $db->where([
        [
            'field' => 'id',
            'value' => 1,
            'open-paren' => true
        ],
        [
            'close-paren' => true
        ]
    ]);
    $this->assertEquals(" WHERE ( `id` = '1')", $sql);

    // query with 2 clauses, but second clause doesn't have the sql_op parameter so will return with just the first parameter
    $sql = $db->where([
        [
            'field' => 'id',
            'value' => 1
        ],
        [
            'field' => 'name',
            'value' => 'Ed'
        ]
    ]);
    $this->assertEquals(" WHERE `id` = '1'", $sql);

    // query with 2 clauses and parens
    $sql = $db->where([
        [
            'field' => 'id',
            'value' => 1,
            'open-paren' => true
        ],
        [
            'field' => 'name',
            'value' => 'Ed',
            'sql_op' => 'OR',
            'close-paren' => true
        ]
    ]);
    $this->assertEquals(" WHERE ( `id` = '1' OR `name` = 'Ed')", $sql);

    // query with AS statement to keep code from adding backticks ` to the field
    $sql = $db->where([
        [
            'field' => "id AS 'id'",
            'value' => 1,
            'backticks' => false
        ]
    ]);
    $this->assertEquals(" WHERE id AS 'id' = '1'", $sql);

    // query with a LIKE operator
    $sql = $db->where([
        [
            'field' => 'id',
            'op' => LIKE,
            'value' => "'%1'",
            'escape' => false
        ]
    ]);
    $this->assertEquals(" WHERE `id` LIKE '%1'", $sql);

    // query with a NOT LIKE operator
    $sql = $db->where([
        [
            'field' => 'name',
            'op' => NOT_LIKE,
            'value' => "'Ed%'",
            'escape' => false
        ]
    ]);
    $this->assertEquals(" WHERE `name` NOT LIKE 'Ed%'", $sql);

    // query with an IN operator and a single value
    $sql = $db->where([
        [
            'field' => 'name',
            'op' => IN,
            'value' => "'Ed'"
        ]
    ]);
    $this->assertEquals(" WHERE `name` IN ('Ed')", $sql);

    // query with IN operator and array of values
    $sql = $db->where([
        [
            'field' => 'name',
            'op' => IN,
            'value' => [
                'Ed',
                'Frank'
            ]
        ]
    ]);
    $this->assertEquals(" WHERE `name` IN ('Ed','Frank')", $sql);

    // query with BETWEEN operator and missing low and high fields
    $sql = $db->where([
        [
            'field' => 'id',
            'op' => BETWEEN,
            'value' => 1
        ]
    ]);
    $this->assertEquals("", $sql);

    // query with BETWEEN operator
    $sql = $db->where([
        [
            'field' => 'id',
            'op' => BETWEEN,
            'low' => 1,
            'high' => 10
        ]
    ]);
    $this->assertEquals(" WHERE `id` BETWEEN '1' AND '10'", $sql);

    // query with IS operator
    $sql = $db->where([
        [
            'field' => 'id',
            'op' => IS,
            'value' => 'NULL'
        ]
    ]);
    $this->assertEquals(" WHERE `id` IS NULL", $sql);

    // WHERE with case_insensitive statement
    $sql = $db->where([
        [
            'field' => 'name',
            'value' => 'Ed',
            'case_insensitive' => true
        ]
    ]);
    $this->assertEquals(" WHERE LOWER(`name`) = LOWER('Ed')", $sql);

    // query with SELECT statement to prevent escaping
    $sql = $db->where([
        [
            'field' => 'name',
            'value' => '(SELECT name FROM test)'
        ]
    ]);
    $this->assertEquals(" WHERE `name` = (SELECT name FROM test)", $sql);
  }

  /**
   * @expectedException Exception
   */
  public function testWhereInvalidInClauseValueDatatype() {
    $db = new Database();

    $sql = $db->where([
        [
            'field' => 'name',
            'op' => IN,
            'value' => new stdClass()
        ]
    ]);
    $this->assertEquals("", $sql);
  }

  public function testGroup() {
    $db = new Database();

    // query with single group by string
    $sql = $db->groups('name');
    $this->assertEquals(" GROUP BY name", $sql);

    // query with array group by string
    $sql = $db->groups([
        'name',
        'id'
    ]);
    $this->assertEquals(" GROUP BY name, id", $sql);
  }

  /**
   * @expectedException Exception
   */
  public function testGroupWrongUnknownDataType() {
    $db = new Database();

    // query group with invalid datatype (stdClass) should throw Exception
    $db->groups(new stdClass());
  }

  public function testOrder() {
    $db = new Database();

    // query with single name order parameter
    $sql = $db->order("name");
    $this->assertEquals(" ORDER BY name", $sql);

    // query with order array
    $sql = $db->order([
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

    // query with invalid datatype (stdClass) will return empty string
    $sql = $db->order(new stdClass());
    $this->assertEquals("", $sql);
  }

  public function testHaving() {
    $db = new Database();

    // query having with empty array, returns empty string
    $sql = $db->having([]);
    $this->assertEquals("", $sql);

    // query having with missing parameters, returns empty string
    $sql = $db->having([
        [
            'field' => 'id'
        ]
    ]);
    $this->assertEquals("", $sql);

    // query having with one clause and parens
    $sql = $db->having([
        [
            'field' => 'id',
            'value' => 1,
            'open-paren' => true
        ],
        [
            'close-paren' => true
        ]
    ]);
    $this->assertEquals(" HAVING ( `id` = '1')", $sql);

    // query having with 2 clauses and missing sql_op in second clause, returns first clause only
    $sql = $db->having([
        [
            'field' => 'id',
            'value' => 1
        ],
        [
            'field' => 'name',
            'value' => 'Ed'
        ]
    ]);
    $this->assertEquals(" HAVING `id` = '1'", $sql);

    // query having with 2 clauses
    $sql = $db->having([
        [
            'field' => 'id',
            'value' => 1,
            'open-paren' => true
        ],
        [
            'field' => 'name',
            'value' => 'Ed',
            'sql_op' => 'OR',
            'close-paren' => true
        ]
    ]);
    $this->assertEquals(" HAVING ( `id` = '1' OR `name` = 'Ed')", $sql);

    // query having clause with AS to avoid field backticks `
    $sql = $db->having([
        [
            'field' => "id AS 'id'",
            'value' => 1,
            'backticks' => false
        ]
    ]);
    $this->assertEquals(" HAVING id AS 'id' = '1'", $sql);

    // query having with LIKE operator
    $sql = $db->having([
        [
            'field' => 'id',
            'op' => LIKE,
            'value' => "'%1'",
            'escape' => false
        ]
    ]);
    $this->assertEquals(" HAVING `id` LIKE '%1'", $sql);

    // query having with NOT LIKE operator
    $sql = $db->having([
        [
            'field' => 'name',
            'op' => NOT_LIKE,
            'value' => "'Ed%'",
            'escape' => false
        ]
    ]);
    $this->assertEquals(" HAVING `name` NOT LIKE 'Ed%'", $sql);

    // query having with IN operator and single string
    $sql = $db->having([
        [
            'field' => 'name',
            'op' => IN,
            'value' => "'Ed'"
        ]
    ]);
    $this->assertEquals(" HAVING `name` IN ('Ed')", $sql);

    // query having with IN operator and array of values
    $sql = $db->having([
        [
            'field' => 'name',
            'op' => IN,
            'value' => [
                'Ed',
                'Frank'
            ]
        ]
    ]);
    $this->assertEquals(" HAVING `name` IN ('Ed','Frank')", $sql);

    // query having with BETWEEN operator and missing low & high parameters
    $sql = $db->having([
        [
            'field' => 'id',
            'op' => BETWEEN,
            'value' => 1
        ]
    ]);
    $this->assertEquals("", $sql);

    // query having with BETWEEN opertor
    $sql = $db->having([
        [
            'field' => 'id',
            'op' => BETWEEN,
            'low' => 1,
            'high' => 10
        ]
    ]);
    $this->assertEquals(" HAVING `id` BETWEEN '1' AND '10'", $sql);

    // query having with IS operator and null value
    $sql = $db->having([
        [
            'field' => 'id',
            'op' => IS,
            'value' => 'NULL'
        ]
    ]);
    $this->assertEquals(" HAVING `id` IS NULL", $sql);

    // query having with case_insensitive parameter
    $sql = $db->having([
        [
            'field' => 'name',
            'value' => 'Ed',
            'case_insensitive' => true
        ]
    ]);
    $this->assertEquals(" HAVING LOWER(`name`) = LOWER('Ed')", $sql);

    // query having with SQL statement for value
    $sql = $db->having([
        [
            'field' => 'name',
            'value' => '(SELECT name FROM test)'
        ]
    ]);
    $this->assertEquals(" HAVING `name` = (SELECT name FROM test)", $sql);
  }

  public function testFlags() {
    $db = new Database();

    // query flags with all parameters
    $sql = $db->flags([
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

  public function testCreateTable() {
    $db = new Database();

    $db->sql = "SELECT * FROM test";
    $db->create_table('test', true);
    $this->assertEquals("CREATE TEMPORARY TABLE IF NOT EXISTS test AS (SELECT * FROM test)", $db->sql);

    $db->create_table('test', false, $db->select("test"));
    $this->assertEquals("CREATE TABLE IF NOT EXISTS test AS (SELECT * FROM test)", $db->sql);

    $db->create_table("test", true, [
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
    $this->assertEquals("CREATE TEMPORARY TABLE IF NOT EXISTS test (`id` int(11) PRIMARY KEY,`name` varchar(100),`email` varchar(100) DEFAULT '')", $db->sql);
  }

  public function testCreateTableJson() {
    $db = new Database();
    $json = json_decode(file_get_contents("examples/create_table_json.json"));

    $db->create_table_json($json->tables[0]);
    $this->assertEquals("CREATE TABLE IF NOT EXISTS `settings` (`id` int(11) AUTO_INCREMENT NOT NULL,`meta_key` varchar(100) NOT NULL,`meta_value` mediumtext DEFAULT NULL, UNIQUE(`meta_key`), PRIMARY KEY(`id`))", $db->sql);

    $db->create_table_json($json->tables[1]);
    $this->assertEquals("CREATE TABLE IF NOT EXISTS `test` (`id` int(11) AUTO_INCREMENT NOT NULL,`fk` int(11) NOT NULL,`default` tinyint(1) DEFAULT '0',`enum` enum('1','2') DEFAULT '1', INDEX `default_idx` (`default`), CONSTRAINT `con_1` FOREIGN KEY (`fk`) REFERENCES `db`.`test` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION, PRIMARY KEY(`id`,`fk`))", $db->sql);

    $db->create_table_json($json->tables[2]);
    $this->assertEquals("CREATE TABLE IF NOT EXISTS `test2` (`id` int(11) AUTO_INCREMENT NOT NULL, PRIMARY KEY(`id`))", $db->sql);
  }

  public function testTableExists() {
    $db = new Database();

    $tbl_count = $db->table_exists('settings');
    $this->assertEquals(1, $tbl_count);

    $tbl_count = $db->table_exists('test%');
    $this->assertEquals(2, $tbl_count);

    $tbl_not_present = $db->table_exists("users");
    $this->assertFalse($tbl_not_present);
  }

  public function testAlterTable() {
    $db = new Database();

    $new = new stdClass();
    $new->name = 'newCol';
    $new->dataType = 'tinyint(1)';
    $new->nn = false;
    $new->default = null;

    $mod = new stdClass();
    $mod->name = 'default';
    $mod->new_name = 'default2';
    $mod->dataType = 'int(1)';
    $mod->nn = true;
    $mod->default = 1;

    $drop = new stdClass();
    $drop->name = 'newCol';

    $db->alter_table('test', 'add-column', $new);
    $this->assertEquals("ALTER TABLE test ADD COLUMN `newCol` tinyint(1) DEFAULT NULL", $db->sql);

    $db->alter_table("test", 'modify-column', $mod);
    $this->assertEquals("ALTER TABLE test MODIFY COLUMN `default` `default2` int(1) NOT NULL DEFAULT '1'", $db->sql);

    $db->alter_table("test", 'drop-column', [$drop]);
    $this->assertEquals("ALTER TABLE test DROP COLUMN `newCol`", $db->sql);
  }

  public function testSelectCount() {
    $db = new Database();

    $db->select_count("test");
    $this->assertEquals("SELECT COUNT(1) AS 'count' FROM test", $db->sql);

    $db->select_count(new stdClass());
    $this->assertEquals(null, $db->sql);

    $db->select_count("test", [
        [
            'field' => 'name',
            'op' => '=',
            'value' => 'Ed'
        ]
    ], [
        'joins' => [
            "JOIN settings s ON s.id = test.id"
        ]
    ]);
  }

  public function testInsert() {
    $db = new Database();

    // query with one parameter
    $db->insert("test", ['id' => 1]);
    $this->assertEquals("INSERT INTO test (`id`) VALUES ('1')", $db->sql);

    // query with 2 parameters
    $db->insert("test", ['id' => 1, 'name' => 'Ed'], true);
    $this->assertEquals("INSERT IGNORE INTO test (`id`,`name`) VALUES ('1','Ed')", $db->sql);

    // insert query using SELECT statement
    $db->insert("test", "SELECT id FROM settings");
    $this->assertEquals("INSERT INTO test SELECT id FROM settings", $db->sql);
  }

  /**
   * @expectedException Exception
   */
  public function testInsertInvalidTableNameDataType() {
    $db = new Database();

    $db->insert(new stdClass());
  }

  /**
   * @expectedException Exception
   */
  public function testInsertInvalidParameterDataType() {
    $db = new Database();

    $db->insert("test", new stdClass());
  }

  public function testEInsert() {
    $db = new Database();

    // extended insert query with fields and 2 items
    $db->extended_insert("test", ['id', 'name'], [[1, 'Ed'], [2, 'Frank']]);
    $this->assertEquals("INSERT INTO test (`id`,`name`) VALUES ('1','Ed'),('2','Frank')", $db->sql);
  }

  /**
   * @expectedException Exception
   */
  public function testEInsertInvalidTableNameDatatype() {
    $db = new Database();

    $db->extended_insert(new stdClass(), [], []);
  }

  /**
   * @expectedException Exception
   */
  public function testEInsertDifferentFieldValuePairs() {
    $db = new Database();

    $db->extended_insert('test', ['id', 'name'], [[1], [2]]);
  }

  /**
   * @expectedException Exception
   */
  public function testEInsertDifferentFieldValuePairs2() {
    $db = new Database();

    $db->extended_insert('test', ['id', 'name'], [[1, 'Ed'], [2]]);
  }

  public function testUpdate() {
    $db = new Database();

    $db->update('test', ['name' => 'Frank']);
    $this->assertEquals("UPDATE test SET `name`='Frank'", $db->sql);

    $db->update('test', ['name' => 'Frank'], [
        [
            'field' => 'id',
            'op' => '=',
            'value' => 1
        ]
    ]);
    $this->assertEquals("UPDATE test SET `name`='Frank' WHERE `id` = '1'", $db->sql);

    $db->update('test t', ['t.name' => 'Frank'], [], [
        'joins' => [
            "JOIN settings s ON s.id=t.id"
        ]
    ]);
    $this->assertEquals("UPDATE test t JOIN settings s ON s.id=t.id SET t.name='Frank'", $db->sql);

    $db->update('test', ['name' => 'Frank'], [], [
        'limit' => 1
    ]);
    $this->assertEquals("UPDATE test SET `name`='Frank' LIMIT 1", $db->sql);
  }

  /**
   * @expectedException Exception
   */
  public function testUpdateInvalidTableNameDatatype() {
    $db = new Database();

    $db->update(new stdClass(), []);
  }

  public function testEUpdate() {
    $db = new Database();

    $db->extended_update("test", "settings", "id", ['name']);
    $this->assertEquals("UPDATE test tbu INNER JOIN settings o USING (id) SET tbu.`name` = o.`name`", $db->sql);

    $db->extended_update("test", "settings", "id", "name");
    $this->assertEquals("UPDATE test tbu INNER JOIN settings o USING (id) SET tbu.`name` = o.`name`", $db->sql);
  }

  /**
   * @expectedException Exception
   */
  public function testEUpdateInvalidParamDatatype() {
    $db = new Database();

    $db->extended_update('test', 'settings', 'id', new stdClass());
  }

  public function testReplace() {
    $db = new Database();

    $db->replace("test", ['id' => 1]);
    $this->assertEquals("REPLACE INTO test (`id`) VALUES ('1')", $db->sql);
  }

  /**
   * @expectedException Exception
   */
  public function testReplaceInvalidTableNameDatatype() {
    $db = new Database();

    $db->replace(new stdClass(), []);
  }

  public function testEReplace() {
    $db = new Database();

    $db->extended_replace("test", ['id', 'name'], [[1, 'Ed'], [2, 'Frank']]);
    $this->assertEquals("REPLACE INTO test (`id`,`name`) VALUES ('1','Ed'),('2','Frank')", $db->sql);
  }

  /**
   * @expectedException Exception
   */
  public function testEReplaceInvalidTableNameDatatype() {
    $db = new Database();

    $db->extended_replace(new stdClass(), [], []);
  }

  public function testFieldExists() {
    $db = new Database();

    $id_exists = $db->field_exists('test', 'id');
    $this->assertTrue($id_exists);

    $phone_not_exists = $db->field_exists('test', 'phone');
    $this->assertFalse($phone_not_exists);
  }

  public function testFieldData() {
    $db = new Database();

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
    $fd = $db->field_data("test2");
    $this->assertEquals(['id' => $id], $fd);

    // query single field in table
    $fd = $db->field_data('test2', 'id');
    $this->assertEquals(['id' => $id], $fd);

    // query array of fields in table
    $fd = $db->field_data('test2', ['id']);
    $this->assertEquals(['id' => $id], $fd);

    // invalid datatype for field name
    $fd = $db->field_data('test2', new stdClass());
    $this->assertEquals(null, $fd);
  }

  public function testEscape() {
    $db = new Database();

    $ret = $db->_escape('NOW()');
    $this->assertEquals("NOW()", $ret);

    $ret = $db->_escape("t.`id`");
    $this->assertEquals("t.`id`", $ret);

    $dt = new DateTime("2017-01-01 00:00:00");
    $ret = $db->_escape($dt);
    $this->assertEquals("'2017-01-01 00:00:00'", $ret);

    $ret = $db->_escape(true);
    $this->assertEquals("'1'", $ret);

    $tc = new TestClass();
    $tc->var = "test";
    $ret = $db->_escape($tc);
    $this->assertEquals("test", $ret);
  }

  /**
   * @expectedException Exception
   */
  public function testEscapeUnknownClassToEscape() {
    $db = new Database();

    $tc2 = new TestClass2();
    $tc2->var = "test";
    $ret = $db->_escape($tc2);
  }

  public function testDelete() {
    $db = new Database();

    $db->delete("test");
    $this->assertEquals("DELETE FROM test", $db->sql);

    $db->delete('test', ['id'], [
        [
            'field' => 'id',
            'op' => '=',
            'value' => 1
        ]
    ]);
    $this->assertEquals("DELETE id FROM test WHERE `id` = '1'", $db->sql);

    $db->delete('test t', null, [], [
        'joins' => "JOIN settings s ON s.id=t.id"
    ]);
    $this->assertEquals("DELETE FROM test t JOIN settings s ON s.id=t.id", $db->sql);
  }

  /**
   * @expectedException Exception
   */
  public function testDeleteInvalidTableNameDatatype() {
    $db = new Database();

    $db->delete(new stdClass());
  }

  public function testTruncate() {
    $db = new Database();

    $db->truncate('test');
    $this->assertEquals("TRUNCATE TABLE test", $db->sql);
  }

  /**
   * @expectedException Exception
   */
  public function testTruncateInvalidTableNameDatatype() {
    $db = new Database();

    $db->truncate(new stdClass());
  }

  public function testDrop() {
    $db = new Database();

    $sql = $db->drop("settings");
    $this->assertEquals("DROP TABLE IF EXISTS `settings`", $sql);

    $sql = $db->drop("test");
    $this->assertEquals("DROP TABLE IF EXISTS `test`", $sql);

    $sql = $db->drop("test", "view");
    $this->assertEquals("DROP VIEW IF EXISTS `test`", $sql);
  }

  /**
   * @expectedException Exception
   */
  public function testDropInvalidTableNameDatatype() {
    $db = new Database();

    $db->drop(new stdClass());
  }

  /**
   * @expectedException Exception
   */
  public function testDropInvalidTypeDatatype() {
    $db = new Database();

    $db->drop('test', new stdClass());
  }
}