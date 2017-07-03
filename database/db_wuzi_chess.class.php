<?php

include_once(dirname(__FILE__) . "/../config.php");

class db_wuzi_chess extends database_table {
    private static $instance = null;
    public static function inst() {
        if (self::$instance == null)
            self::$instance = new db_wuzi_chess();
        return self::$instance;
    }

    private function __construct() {
        parent::__construct(MYSQL_DATABASE, TABLE_WUZI_CHESS);
    }

};


