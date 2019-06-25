<?php

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
            'meta_value' => 'george'
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

    public function where()
    {
        $where = new Godsgood33\Php_Db\DBWhere('foo', 'bar');
        return $where;
    }
}