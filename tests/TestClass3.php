<?php

use Godsgood33\Php_Db\DBField;
use Godsgood33\Php_Db\DBWhere;

class TestClass3 implements Godsgood33\Php_Db\DBInterface
{
    public function insert() : array
    {
        return [
            'meta_key' => 'test3',
            'meta_value' => 'test3'
        ];
    }

    public function update() : array
    {
        return [
            'field1' => 'george',
            'field2' => 'frank'
        ];
    }

    public function replace() : array
    {
        return [
            'id' => '3',
            'meta_key' => 'test3',
            'meta_value' => 'test25'
        ];
    }

    public function where(): DBWhere
    {
        $where = new Godsgood33\Php_Db\DBWhere(new DBField('foo'), 'bar');
        return $where;
    }
}
