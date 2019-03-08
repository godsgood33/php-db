<?php

class TestClass3 implements Godsgood33\Php_Db\DBInterface
{
    public function insert()
    {
        return [
            'meta_key' => 'test3',
            'meta_value' => 'test3'
        ];
    }

    public function extendedInsert()
    {
        return "('test1','test1'),('test2','test2')";
    }

    public function update()
    {
        return "`meta_value`='george'";
    }

    public function replace()
    {
        return [
            'id' => '3',
            'meta_key' => 'test3',
            'meta_value' => 'test25'
        ];
    }
}