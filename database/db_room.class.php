<?php

include_once(dirname(__FILE__) . "/../config.php");

class db_room extends database_table {
    const STATUS_EMPTY = 0;
    const STATUS_WAITING = 1;
    const STATUS_CHESSING = 2;

    const TYPE_WUZI = 0;

    private static $instance = null;
    public static function inst() {
        if (self::$instance == null)
            self::$instance = new db_room();
        return self::$instance;
    }

    private function db_room() {
        parent::database_table(MYSQL_DATABASE, TABLE_WUZI_ROOM);
    }

    public function get_all_rooms() {
        return $this->get_all();
    }

    public function get_room_by_id($id) {
        $id = (int)$id;
        return $this->get_one("id = $id");
    }

    public function get_room_by_matchid($mid) {
        $mid = (int)$mid;
        return $this->get_one("matchid = $mid");
    }

    public function update_player1($roomid, $playerid) {
        $roomid = (int)$roomid;
        $playerid = (int)$playerid;
        return $this->update(array("player1" => $playerid), "id = $roomid");
    }

    public function update_player2($roomid, $playerid) {
        $roomid = (int)$roomid;
        $playerid = (int)$playerid;
        return $this->update(array("player2" => $playerid), "id = $roomid");
    }

    public function update_players($roomid, $pids) {
        $roomid = (int)$roomid;
        if (is_array($pids)) {
            $pids = implode(",", $pids);
        }
        return $this->update(array("players" => $pids), "id = $roomid");
    }

    public function update_status($roomid, $status) {
        $roomid = (int)$roomid;
        $status = (int)$status;
        return $this->update(array("status" => $status), "id = $roomid");
    }

    public function update_match($roomid, $mid) {
        $roomid = (int)$roomid;
        $mid = (int)$mid;
        return $this->update(array("matchid" => $mid), "id = $roomid");
    }

    public function reset_after_match($matchid) {
        $matchid = (int)$matchid;
        return $this->update(array("matchid" => 0, "status" => self::STATUS_EMPTY, "player1" => 0, "player2" => 0, "players" => ""), "matchid = $matchid");
    }
};


