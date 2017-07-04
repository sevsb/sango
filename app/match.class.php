<?php
include_once(dirname(__FILE__) . "/../config.php");
include_once(dirname(__FILE__) . "/player.class.php");
include_once(dirname(__FILE__) . "/../database/db_wuzi_chess.class.php");

class match {
    private $summary = array();
    private $players = array();
    private $places = array();

    private function match($summary) {
        $this->summary = $summary;
        $pid1 = $summary["player1"];
        $pid2 = $summary["player2"];
        $this->players[1] = player::create($pid1);
        $this->players[2] = player::create($pid2);
    }

    public function load_places() {
        if (empty($this->places)) {
            $this->places = db_wuzi_chess::inst()->load_match_places($this->id());
        }
        return $this->places;
    }

    public function get_last_player() {
        $places = $this->load_places();
        if (empty($places)) {
            return $this->player2();
        }
        $lastplace = end($places);
        if ($lastplace["player"] == $this->pid1()) {
            return $this->player1();
        } else if ($lastplace["player"] == $this->pid2()) {
            return $this->player2();
        }
        return null;
    }

    public function piece_status($place) {
        foreach ($this->places as $key => $ps) {
            if ($ps["place"] == $place) {
                return $ps["player"];
            }
        }
        return 0;
    }

    public function id() {
        return $this->summary["id"];
    }

    public function type() {
        return $this->summary["type"];
    }

    public function progress() {
        return $this->summary["progress"];
    }

    public function winner() {
        $winner = $this->summary["winner"];
        if ($winner == $this->summary["player1"]) {
            return $this->player1();
        } else if ($winner == $this->summary["player2"]) {
            return $this->player2();
        } else {
            return null;
        }
    }

    public function player1() {
        return $this->players[1];
    }

    public function player2() {
        return $this->players[2];
    }

    public function pid1() {
        return $this->summary["player1"];
    }

    public function pid2() {
        return $this->summary["player2"];
    }

    public function is_chessing() {
        return $this->status() == db_wuzi_match::PROGRESS_CHESSING;
    }

    public static function load_all($pid = 0, $onlychessing = true) {
        $matches = array();
        $rss = db_wuzi_match::inst()->get_matches($pid, $onlychessing ? 0 : -1);
        foreach ($rss as $id => $summary) {
            $matches [$id]= new match($summary);
        }
        return $matches;
    }
    public static function create($mid) {
        $match = db_wuzi_match::inst()->get_match_by_id($mid);
        if (empty($match)) {
            return null;
        }
        return new match($match);
    }

};




