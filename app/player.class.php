<?php
include_once(dirname(__FILE__) . "/../config.php");

class player {
    private $summary = array();
    public function player($summary) {
        $this->summary = $summary;
    }

    public function id() {
        return $this->summary["id"];
    }

    public function faceurl() {
        return $this->summary["faceurl"];
    }

    public function nick() {
        return $this->summary["nick"];
    }

    private static $all_players = array();
    public static function load_all() {
        if (!empty(self::$all_players)) {
            return self::$all_players;
        }
        $players = array();
        $rss = db_players::inst()->get_all();
        foreach ($rss as $id => $summary) {
            $players []= new player($summary);
        }
        
        self::$all_players = $players;
        return $players;
    }

    public static function create($id) {
        if ($id <= 0) {
            return null;
        }
        $s = db_players::inst()->get_player_by_id($id);
        return new player($s);
    }

};

