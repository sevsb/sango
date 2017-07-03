<?php

include_once(dirname(__FILE__) . "/../config.php");

class db_wuzi_room extends database_table {
    private static $instance = null;
    public static function inst() {
        if (self::$instance == null)
            self::$instance = new db_wuzi_room();
        return self::$instance;
    }

    private function db_wuzi_room() {
        parent::database_table(MYSQL_DATABASE, TABLE_WUZI_ROOM);
    }

    public function get_all_rooms() {
        return $this->get_all();
    }
};


