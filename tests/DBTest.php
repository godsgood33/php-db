<?php

use Godsgood33\Php_Db\Database;

class DBTest extends Database
{
    public function __construct()
    {
        parent::__construct();
    }

    public function disconnect()
    {
        $this->_c = null;
    }
}
