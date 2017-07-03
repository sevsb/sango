<?php

include_once(dirname(__FILE__) . "/../config.php");

class db_wuzi_match extends database_table {
    const TYPE_NORMAL = 0;

    private static $instance = null;
    public static function inst() {
        if (self::$instance == null)
            self::$instance = new db_wuzi_match();
        return self::$instance;
    }

    private function __construct() {
        parent::__construct(MYSQL_DATABASE, TABLE_WUZI_MATCH);
    }

    public function get_all_matchs() {
        return $this->get_all();
    }

    public function add($type, $player1, $player2) {
        return $this->insert(array("type" => $type, "player1" => $player1, "player2" => $player2));
    }
};


