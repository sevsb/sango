<?php
include_once(dirname(__FILE__) . "/../../config.php");
include_once(dirname(__FILE__) . "/../../database/db_room.class.php");
include_once(dirname(__FILE__) . "/../../database/db_wuzi_match.class.php");
include_once(dirname(__FILE__) . "/../../database/db_wuzi_chess.class.php");

class wuzi_controller {
    
    public function preaction($action) {
        if (!isset($_SESSION["player"])) {
            die("please login.");
        }
    }

    public function index_action() {
        go("client/wuzi/room");

        $rooms = room::load_all();
        $player = get_session_assert("player");

        $tpl = new tpl("client/header", "client/footer");
        $tpl->set("rooms", $rooms);
        $tpl->set("player", $player);
        $tpl->display("client/wuzi/index");
    }

    public function room_action() {
        $player = get_session_assert("player");
        $player = new player($player);
        $tpl = new tpl("client/header", "client/footer");
        $tpl->set("player", $player);
        $tpl->display("client/wuzi/room");
    }

    public function matchlist_action() {
        $player = get_session_assert("player");
        $matches = wuzi_match::load_all($player["id"], false);
        $tpl = new tpl("client/header", "client/footer");
        $tpl->set("matches", $matches);
        $tpl->display("client/wuzi/matchlist2");
    }


    public function match_action() {
        $matchid = get_request_assert("match");
        $match = wuzi_match::create($matchid);
        $match->load_places();

        $player = get_session_assert("player");
        $player = new player($player);

        $tpl = new tpl("client/header", "client/footer");
        $tpl->set("match", $match);
        $tpl->set("player", $player);
        $tpl->display("client/wuzi/match");
    }

    public function join_ajax() {
        $roomid = get_request_assert("room");
        $playerid = get_request_assert("player");

        $sp = get_session_assert("player");
        $pid = $sp["id"];

        $room = db_room::inst()->get_room_by_id($roomid);
        if ($room["status"] == db_room::STATUS_CHESSING) {
            return "fail|正在对弈。";
        }

        $ret = true;
        if ($playerid == 1) {
            if ($room["player1"] != 0) {
                return "fail|已有玩家。";
            }

            if ($room["player2"] == $pid) {
                $ret &= db_room::inst()->update_player2($roomid, 0);
                $room["player2"] = 0;
            }
            $ret &= db_room::inst()->update_player1($roomid, $pid);
            $room["player1"] = $pid;
        } else if ($playerid == 2) {
            if ($room["player2"] != 0) {
                return "fail|已有玩家。";
            }

            if ($room["player1"] == $pid) {
                $ret &= db_room::inst()->update_player1($roomid, 0);
                $room["player1"] = 0;
            }
            $ret &= db_room::inst()->update_player2($roomid, $pid);
            $room["player2"] = $pid;
        }

        if ($room["player1"] != 0 && $room["player2"] != 0) {
            $ret &= db_room::inst()->update_status($roomid, db_room::STATUS_CHESSING);
            $ret &= db_wuzi_match::inst()->add(db_wuzi_match::TYPE_NORMAL, $room["player1"], $room["player2"]);
            $mid = db_wuzi_match::inst()->last_insert_id();
            $ret &= db_room::inst()->update_match($roomid, $mid);
        }

        return ($ret !== false) ? "success" : "fail|数据库操作失败，请稍后重试。";
    }

    public function leave_ajax() {
        $roomid = get_request_assert("room");
        $playerid = get_request_assert("player");

        $ret = 0;
        if ($playerid == 1) {
            $ret = db_room::inst()->update_player1($roomid, 0);
        } else if ($playerid == 2) {
            $ret = db_room::inst()->update_player2($roomid, 0);
        }
        return ($ret !== false) ? "success" : "fail|数据库操作失败，请稍后重试。";
    }

    public function place_ajax() {
        $matchid = get_request_assert("matchid");
        $place = get_request_assert("place");
        $player = get_session_assert("player");

        $match = wuzi_match::create($matchid);
        if ($match->winner() != null) {
            return "fail|game over. {$match->winner()->nick()} win.";
        }

        $lp = $match->next_player();
        if ($lp->id() != $player["id"]) {
            return "fail|Not your turn.";
        }
        return $match->place_piece($place);
    }

    public function refresh_ajax() {
        $matchid = get_request_assert("matchid");
        $placeid = get_request_assert("placeid");
        // logging::d("Debug", "long pool: check $matchid/$placeid");
        $start = time();
        while (time() - $start < 5) {
            $one = db_wuzi_chess::inst()->get_last_place($matchid);
            if ($one["id"] != $placeid) {
                logging::d("Debug", "long pool: new place. {$one["id"]} vs $placeid");
                return "success";
            }
            usleep(5000000);
        }
        // logging::d("Debug", "long pool: no place.");
        return "fail";
    }
}













