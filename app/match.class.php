<?php
include_once(dirname(__FILE__) . "/../config.php");
include_once(dirname(__FILE__) . "/player.class.php");
include_once(dirname(__FILE__) . "/../database/db_wuzi_chess.class.php");

class match {
    private $summary = array();
    private $players = array();
    private $places = array();

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

    public function next_player() {
        $places = $this->load_places();
        if (empty($places)) {
            return $this->player1();
        }
        $lastplace = end($places);
        if ($lastplace["player"] == $this->pid1()) {
            return $this->player2();
        } else if ($lastplace["player"] == $this->pid2()) {
            return $this->player1();
        }
        logging::fatal("database error.");
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

    private function check_direction($arr, $pid) {
        $count = 0;
        for ($index = 4; $index >= 0; $index--) {
            $place = $arr[$index];
            $ps = $this->piece_status($place);
            if ($ps == $pid) {
                $count++;
            } else {
                break;
            }
        }
        for ($index = 5; $index <= 8; $index++) {
            $place = $arr[$index];
            $ps = $this->piece_status($place);
            if ($ps == $pid) {
                $count++;
            } else {
                break;
            }
        }
        logging::d("Debug", $count);
        return ($count >= 5);
    }

    private function check_winner() {
        $lastplace = end($this->places);
        $lp = $lastplace["place"];
        $lplayer = $lastplace["player"];

        $col = ord($lp);
        $row = substr($lp, 1);

        $dh = array();
        $dv = array();
        $dlr = array();
        $drl = array();

        for ($j = $col - 4; $j <= $col + 4; $j++) {
            $dh [] = chr($j) . $row;
        }
        for ($i = $row - 4; $i <= $row + 4; $i++) {
            $dv []= substr($lp, 0, 1) . $i;
        }
        for ($i = - 4; $i <= 4; $i++) {
            $dlr []= chr($col + $i) . ((int)$row + $i);
            $drl []= chr($col + $i) . ((int)$row - $i);
        }
        $ret = $this->check_direction($dh, $lplayer);
        $ret |= $this->check_direction($dv, $lplayer);
        $ret |= $this->check_direction($dlr, $lplayer);
        $ret |= $this->check_direction($drl, $lplayer);
        return $ret;
    }

    public function place_piece($place) {
        $player = $this->next_player();
        $ret = db_wuzi_chess::inst()->place_piece($this->id(), $place, $player->id());
        if ($ret === false) return "fail|数据库操作失败，请稍后重试。";

        $this->places []= array("matchid" => $this->id(), "place" => $place, "player" => $player->id());

        $winner = $this->check_winner();
        if ($winner) {
            $ret = db_wuzi_match::inst()->update_winner($this->id(), $player->id());
            $ret &= db_wuzi_room::inst()->reset_after_match($this->id());
            return ($ret !== false) ? "success|{$player->nick()} win." : "fail|{$player->nick()} win.";
        } else {
            return "success";
        }
    }

};




