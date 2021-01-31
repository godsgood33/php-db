<?php

use Godsgood33\Php_Db\DBField;
use Godsgood33\Php_Db\DBWhere;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass DBWhere
 */
final class DBWhereTest extends TestCase
{
    public function testEmptyObject()
    {
        $where = new DBWhere();
        $this->assertEquals('', $where);
    }

    public function testEmptyValue()
    {
        $where = new DBWhere(new DBField('id'));
        $this->assertEquals(' `id` = NULL', (string) $where);
    }

    public function testValidFieldAndNumericValue()
    {
        $where = new DBWhere(new DBField('id'), 1);
        $this->assertEquals(' `id` = 1', (string) $where);
    }

    public function testValidFieldAndStringValue()
    {
        $where = new DBWhere(new DBField('id'), 'Frank');
        $this->assertEquals(" `id` = Frank", (string) $where);
    }

    public function testInClauseStringValue()
    {
        $where = new DBWhere(new DBField('id'), '1', DBWhere::IN);
        $this->assertEquals(" `id` IN (1)", (string) $where);
    }

    public function testInClauseArrayValue()
    {
        $where = new DBWhere(new DBField('id'), [1, 2], DBWhere::IN);
        $this->assertEquals(" `id` IN (1,2)", (string) $where);
    }

    public function testNotInClauseNullValue()
    {
        $where = new DBWhere(new DBField('id'), [1, 2], DBWhere::NOT_IN);
        $this->assertEquals(" `id` NOT IN (1,2)", (string) $where);
    }

    public function testBetweenClause()
    {
        $where = new DBWhere(new DBField('id'), null, DBWhere::BETWEEN);
        $where->low = 1;
        $where->high = 10;
        $this->assertEquals(" `id` BETWEEN 1 AND 10", (string) $where);
    }

    public function testLikeClause()
    {
        $where = new DBWhere(new DBField('id'), '%frank%', DBWhere::LIKE);
        $this->assertEquals(" `id` LIKE %frank%", (string) $where);
    }

    public function testNotLikeClause()
    {
        $where = new DBWhere(new DBField('id'), '%frank%', DBWhere::NOT_LIKE);
        $this->assertEquals(" `id` NOT LIKE %frank%", (string) $where);
    }

    public function testIsClause()
    {
        $where = new DBWhere(new DBField('id'), null, DBWhere::IS);
        $this->assertEquals(" `id` IS NULL", (string) $where);
    }

    public function testNotIsClause()
    {
        $where = new DBWhere(new DBField('id'), null, DBWhere::IS_NOT);
        $this->assertEquals(" `id` IS NOT NULL", (string) $where);
    }

    public function testCloseParenWhere()
    {
        $where = new DBWhere();
        $where->closeParen = true;
        $this->assertEquals(")", (string) $where);
    }

    public function testInvalidBetweenClause()
    {
        $where = new DBWhere(new DBField('id'));
        $where->operator = DBWhere::BETWEEN;
        $this->assertEquals("", (string) $where);
    }

    public function testParens()
    {
        $where = new DBWhere(new DBField('id'), 1);
        $where->openParen = true;
        $where->closeParen = true;
        $this->assertEquals(" ( `id` = 1)", (string) $where);
    }

    public function testCaseInsensitive()
    {
        $where = new DBWhere(new DBField('name'), 'Frank');
        $where->caseInsensitive = true;
        $this->assertEquals(" LOWER(`name`) = LOWER(Frank)", (string) $where);
    }

    public function testInvalidArgumentSet()
    {
        $this->expectException(InvalidArgumentException::class);
        $where = new DBWhere(new DBField());
        $where->foo = 'bar';
    }

    public function testGetInvalidProperty()
    {
        $this->expectException(InvalidArgumentException::class);
        $where = new DBWhere(new DBField());
        return $where->foo;
    }
}
