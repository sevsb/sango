<?php
include_once(dirname(__FILE__) . "/../config.php");
include_once(dirname(__FILE__) . "/player.class.php");
include_once(dirname(__FILE__) . "/../database/db_wuzi_chess.class.php");

class wuzi_match {
    private $summary = array();
    private $players = array();
    private $places = array();

    public static function load_all($pid = 0, $onlychessing = true) {
        $matches = array();
        $rss = db_wuzi_match::inst()->get_matches($pid, $onlychessing ? 0 : -1);
        foreach ($rss as $id => $summary) {
            $matches [$id]= new wuzi_match($summary);
        }
        return $matches;
    }
    public static function create($mid) {
        $match = db_wuzi_match::inst()->get_match_by_id($mid);
        if (empty($match)) {
            return null;
        }
        return new wuzi_match($match);
    }

    private function wuzi_match($summary) {
        $this->summary = $summary;
        $pid1 = $summary["player1"];
        $pid2 = $summary["player2"];
        $this->players[1] = player::create($pid1);
        $this->players[2] = player::create($pid2);
        $this->places = db_wuzi_chess::inst()->load_match_places($this->id());
    }

    public function load_places() {
        return $this->places;
    }

    public function next_player() {
        if (!$this->is_chessing()) {
            return null;
        }
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

    public function last_place_id() {
        $places = $this->load_places();
        if (empty($places)) {
            return 0;
        }

        $lp = end($places);
        if (empty($lp)) {
            return 0;
        }
        return $lp["id"];
    }


    public function piece_status($place) {
        $places = $this->load_places();
        foreach ($places as $key => $ps) {
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
        return $this->progress() == db_wuzi_match::PROGRESS_CHESSING;
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

        $pss = $this->piece_status($place);
        if ($pss != 0) {
            return false;
        }


        db_wuzi_chess::inst()->begin_transaction();

        $ret = db_wuzi_chess::inst()->place_piece($this->id(), $place, $player->id());
        if ($ret === false) {
            db_wuzi_chess::inst()->rollback();
            return false;
        }
        $lastid = db_wuzi_chess::inst()->last_insert_id();
        $this->places [$lastid]= array("matchid" => $this->id(), "place" => $place, "player" => $player->id());

        $winner = $this->check_winner();
        logging::d("WuziServer", "check winner: {$winner}");
        if ($winner) {
            $ret = db_wuzi_match::inst()->update_winner($this->id(), $player->id());
            if ($ret === false) {
                db_wuzi_chess::inst()->rollback();
                unset($this->places[$lastid]);
                return false;
            }
            $ret = db_room::inst()->reset_after_match($this->id());
            if ($ret === false) {
                db_wuzi_chess::inst()->rollback();
                unset($this->places[$lastid]);
                return false;
            }
        }
        if ($winner) {
            $this->summary["winner"] = $player->id();
        }
        db_wuzi_chess::inst()->commit();
        return true;
    }

    public function pack_listinfo() {
        $info = array("id" => $this->id(), "type" => room::get_type_text(db_room::TYPE_WUZI), "snapshot" => "");

        $p1 = $this->player1()->pack_info();
        $p2 = $this->player2()->pack_info();
        $p1["win"] = ($this->player1()->equals($this->winner()) ? 1 : 0);
        $p2["win"] = ($this->player2()->equals($this->winner()) ? 1 : 0);

        $players = array($p1, $p2);
        return array("info" => $info, "players" => $players);
    }

    public function pack_info() {
        $info = array();

        $p1 = $this->player1()->pack_info();
        $p2 = $this->player2()->pack_info();
        $p1["win"] = ($this->player1()->equals($this->winner()) ? 1 : 0);
        $p2["win"] = ($this->player2()->equals($this->winner()) ? 1 : 0);
        $p1["turn"] = ($this->player1()->equals($this->next_player()) ? 1 : 0);
        $p2["turn"] = ($this->player2()->equals($this->next_player()) ? 1 : 0);
        $players = array("player1" => $p1, "player2" => $p2);

        $board = array();
        for ($i = 0; $i < 15; $i++) {
            for ($j = 0; $j < 15; $j++) {
                $place = chr(ord('A') + $j) . $i;
                $ps = $this->piece_status($place);
                $piece = array("piece" => "", "index" => 0, "mark" => 0);
                if ($ps == 0) {
                    $piece["piece"] = 0;
                } else if ($ps == $this->pid1()) {
                    $piece["piece"] = 1;
                } else if ($ps == $this->pid2()) {
                    $piece["piece"] = 2;
                }
                $board [$place]= $piece;
            }
        }
        $places = $this->load_places();
        if (!empty($places)) {
            $index = 1;
            $last = null;
            foreach ($places as $pss) {
                $ps = $pss["place"];
                $board[$ps]["index"] = $index;
                $index++;
                $last = $ps;
            }
            $board[$last]["mark"] = 1;
        }

        $data = array("info" => $info, "players" => $players, "board" => $board);
        return $data;
    }
};




