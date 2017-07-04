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

    public function load_match_places($matchid) {
        $matchid = (int)$matchid;
        return $this->get_all("matchid = $matchid");
    }

    public function place_piece($matchid, $place, $player) {
        $matchid = (int)$matchid;
        $player = (int)$player;
        return $this->insert(array("matchid" => $matchid, "place" => $place, "player" => $player));
    }
};


