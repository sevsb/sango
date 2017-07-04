<?php

include_once(dirname(__FILE__) . "/../config.php");

class db_wuzi_match extends database_table {
    const TYPE_NORMAL = 0;

    const PROGRESS_CHESSING = 0;
    const PROGRESS_DONE = 1;

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

    public function get_matches($pid, $progress) {
        $pid = (int)$pid;
        $progress = (int)$progress;
        $where = "1 = 1 ";
        if ($pid != 0) {
            $where .= "AND (player1 = $pid OR player2 = $pid) ";
        }
        if ($progress >= 0) {
            $where .= "AND progress = $progress";
        }
        return $this->get_all($where);
    }

    public function get_match_by_id($id) {
        $id = (int)$id;
        return $this->get_one("id = $id");
    }

    public function add($type, $player1, $player2) {
        return $this->insert(array("type" => $type, "player1" => $player1, "player2" => $player2));
    }

    public function update_winner($mid, $pid) {
        $mid = (int)$mid;
        $pid = (int)$pid;
        $progress = ($pid != 0) ? 1 : 0;
        return $this->update(array("progress" => $progress, "winner" => $pid), "id = $mid");
    }
};


