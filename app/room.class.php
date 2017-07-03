<?php
include_once(dirname(__FILE__) . "/../config.php");
include_once(dirname(__FILE__) . "/player.class.php");

class room {
    private $summary = array();
    private $players = array();
    private function room($summary) {
        $this->summary = $summary;
        $pid1 = $summary["player1"];
        $pid2 = $summary["player2"];
        $this->players[1] = player::create($pid1);
        $this->players[2] = player::create($pid2);
    }

    public function id() {
        return $this->summary["id"];
    }

    public function player1() {
        return $this->players[1];
    }

    public function player2() {
        return $this->players[2];
    }

    public function status_text() {
        switch ($this->summary["status"]) {
        case db_wuzi_room::STATUS_EMPTY:
            return "空闲";
        case db_wuzi_room::STATUS_WAITING:
            return "组队中";
        case db_wuzi_room::CHESSING:
            return "正在对弈";
        default:
            return "未知";
        }
    }

    public function status() {
        return $this->summary["status"];
    }

    public function title() {
        return $this->summary["title"];
    }

    public static function load_all() {
        $rooms = array();
        $rss = db_wuzi_room::inst()->get_all();
        foreach ($rss as $id => $summary) {
            $rooms [$id]= new room($summary);
        }
        return $rooms;
    }
};

