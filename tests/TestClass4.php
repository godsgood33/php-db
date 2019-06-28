<?php

class TestClass4 implements \Godsgood33\Php_Db\DBInterface
{
    public function _escape()
    {
        return false;
    }

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

    public function where()
    {
        return false;
    }
}