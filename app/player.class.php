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

    public function openid() {
        return $this->summary["openid"];
    }

    public function faceurl() {
        return $this->summary["faceurl"];
    }

    public function nick() {
        return $this->summary["nick"];
    }

    public function equals($player) {
        if ($player == null) {
            return false;
        }
        return ($this->id() == $player->id());
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
    public static function create_by_openid($openid) {
        $s = db_players::inst()->get_player_by_openid($openid);
        if (empty($s)) {
            return null;
        }
        return new player($s);
    }

    public function pack_info() {
        $parr = array(
            "id" => $this->id(),
            "nick" => $this->nick(),
            "face" => $this->faceurl(),
        );
        return $parr;
    }
};

