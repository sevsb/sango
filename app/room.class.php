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
        if ($pid1 != 0) {
            $this->players[0] = player::create($pid1);
        }
        if ($pid2 != 0) {
            $this->players[1] = player::create($pid2);
        }
        $ps = $summary["players"];
        if (!empty($ps)) {
            $pids = explode(",", $ps);
            foreach ($pids as $pid) {
                $this->players[] = player::create($pid);
            }
        }
    }

    public function id() {
        return $this->summary["id"];
    }

    public function player1() {
        if (count($this->players) > 0) {
            reset($this->players);
            return current($this->players);
        }
        return null;
    }

    public function player2() {
        if (count($this->players) > 1) {
            reset($this->players);
            return next($this->players);
        }
        return null;
    }

    public function get_all_players() {
        return $this->players;
    }

    public function has_player($player) {
        foreach ($this->players as $p) {
            if ($player->equals($p)) {
                return true;
            }
        }
        return false;
    }

    public function first_player() {
        if (empty($this->players)) {
            return null;
        }
        reset($this->players);
        return current($this->players);
    }

    public function status_text() {
        switch ($this->summary["status"]) {
        case db_room::STATUS_EMPTY:
            return "空闲";
        case db_room::STATUS_WAITING:
            return "等待开始";
        case db_room::STATUS_CHESSING:
            return "对弈中";
        default:
            return "未知";
        }
    }

    public function status() {
        return $this->summary["status"];
    }

    public function type() {
        return $this->summary["type"];
    }

    public static function get_type_text($type) {
        switch ($type) {
        case db_room::TYPE_WUZI:
            return "五子棋";
        default:
            break;
        }
        return "未知";
    }

    public function type_text() {
        return self::get_type_text($this->type());
    }

    public function seats() {
        switch ($this->type()) {
        case db_room::TYPE_WUZI:
            return 2;
        default:
            return 0;
        }
    }

    public function is_full_seats() {
        return count($this->players) == $this->seats();
    }

    public function is_chessing() {
        return $this->status() == db_room::STATUS_CHESSING;
    }

    public function is_empty() {
        return $this->status() == db_room::STATUS_EMPTY;
    }

    public function is_waiting() {
        return $this->status() == db_room::STATUS_WAITING;
    }

    public function title() {
        return $this->summary["title"];
    }

    public function matchid() {
        return $this->summary["matchid"];
    }

    public static function load_all() {
        $rooms = array();
        $rss = db_room::inst()->get_all();
        foreach ($rss as $id => $summary) {
            $rooms [$id]= new room($summary);
        }
        return $rooms;
    }

    public function player_join($player) {
        foreach ($this->players as $p) {
            if ($player->equals($p)) {
                return false;
            }
        }
        $ret = false;

        array_push($this->players, $player);
        $pids = array();
        foreach ($this->players as $k => $p) {
            $pids []= $p->id();
        }
        db_room::inst()->begin_transaction();
        $ret = db_room::inst()->update_players($this->id(), $pids);
        if ($ret === false) {
            array_pop($this->players);
            db_room::inst()->rollback();
            return false;
        }

        if (count($this->players) == $this->seats()) {
            $ret = db_room::inst()->update_status($this->id(), db_room::STATUS_WAITING);
            if ($ret === false) {
                array_pop($this->players);
                db_room::inst()->rollback();
                return false;
            }
            $this->summary["status"] = db_room::STATUS_WAITING;
        }
        db_room::inst()->commit();
        return true;
    }

    public function player_leave($player) {
        $index = -1;
        foreach ($this->players as $k => $p) {
            if ($player->equals($p)) {
                $index = $k;
                break;
            }
        }
        if ($index == -1) {
            return false;
        }
        unset($this->players[$index]);

        db_room::inst()->begin_transaction();

        $pids = array();
        foreach ($this->players as $k => $p) {
            $pids []= $p->id();
        }
        $ret = db_room::inst()->update_players($this->id(), $pids);
        if ($ret === false) {
            $this->players[$index] = $player;
            db_room::inst()->rollback();
            return false;
        }

        if ($this->status() == db_room::STATUS_WAITING) {
            $ret = db_room::inst()->update_status($this->id(), db_room::STATUS_EMPTY);
            if ($ret === false) {
                $this->players[$index] = $player;
                db_room::inst()->rollback();
                return false;
            }
            $this->summary["status"] = db_room::STATUS_EMPTY;
        }
        db_room::inst()->commit();
        return true;
    }

    public function create_match() {
        db_room::inst()->begin_transaction();
        $ret = db_room::inst()->update_status($this->id(), db_room::STATUS_CHESSING);
        if ($ret === false) {
            db_room::inst()->rollback();
            return false;
        }

        if ($this->type() == db_room::TYPE_WUZI) {
            $ret = db_wuzi_match::inst()->add(db_wuzi_match::TYPE_NORMAL, $this->player1()->id(), $this->player2()->id());
            if ($ret === false) {
                db_room::inst()->rollback();
                return false;
            }
            $mid = db_wuzi_match::inst()->last_insert_id();
            $ret = db_room::inst()->update_match($this->id(), $mid);
            if ($ret === false) {
                db_room::inst()->rollback();
                return false;
            }
        } else {
            db_room::inst()->rollback();
            logging::fatal("No such game.");
            return false;
        }
        db_room::inst()->commit();
        $this->summary["status"] = db_room::STATUS_CHESSING;
        $this->summary["matchid"] = $mid;
        return true;
    }

    public function pack_info($player) {
        $data = array();
        $data["info"] = array(
            "id" => $this->id(),
            "title" => $this->title(),
            "status" => $this->status_text(),
            "type" => $this->type_text(),
            "seats" => $this->seats(),
            "match" => $this->matchid(),
        );
        $data["players"] = array();
        foreach ($this->get_all_players() as $p) {
            $data["players"] []= $p->pack_info();
        }

        $actions  = array("sit" => 0, "stand" => 0, "start" => 0, "watch" => 0);
        if ($this->is_chessing()) {
            $actions["sit"] = 0;
            $actions["stand"] = 0;
            $actions["start"] = 0;
            $actions["watch"] = 1;
        } else if ($this->is_empty()) {
            $actions["start"] = 0;
            $actions["watch"] = 0;
            if ($this->has_player($player)) {
                $actions["sit"] = 0;
                $actions["stand"] = 1;
            } else {
                $actions["sit"] = 1;
                $actions["stand"] = 0;
            }
        } else if ($this->is_waiting()) {
            $actions["watch"] = 0;
            $actions["start"] = 0;
            if ($this->has_player($player)) {
                $actions["sit"] = 0;
                $actions["stand"] = 1;
                if ($player->equals($this->first_player())) {
                    $actions["start"] = 1;
                }
            } else {
                $actions["sit"] = 1;
                $actions["stand"] = 0;
            }
        } else {
            logging::fatal("room", "Why run here?");
        }
        $data["actions"] = $actions;
        return $data;
    }
};









