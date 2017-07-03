<?php
include_once(dirname(__FILE__) . "/../../config.php");
include_once(dirname(__FILE__) . "/../../database/db_wuzi_room.class.php");

class wuzi_controller {
    
    public function preaction($action) {
        if (!isset($_SESSION["player"])) {
            die("please login.");
        }
    }

    public function index_action() {
        $rooms = room::load_all();

        $tpl = new tpl("client/header", "client/footer");
        $tpl->set("rooms", $rooms);
        $tpl->display("client/wuzi/index");
    }

    public function room_action() {
        $roomid = get_request_assert("room");
        $tpl = new tpl("client/header", "client/footer");
        $tpl->display("client/wuzi/match");
    }

    public function join_ajax() {
        $roomid = get_request_assert("room");
        $playerid = get_request_assert("player");

        $sp = get_session_assert("player");
        $pid = $sp["id"];

        $room = db_wuzi_room::inst()->get_room_by_id($roomid);
        if ($room["status"] == db_wuzi_room::STATUS_CHESSING) {
            return "fail|正在对弈。";
        }

        $ret = true;
        if ($playerid == 1) {
            if ($room["player1"] != 0) {
                return "fail|已有玩家。";
            }

            if ($room["player2"] == $pid) {
                $ret &= db_wuzi_room::inst()->update_player2($roomid, 0);
                $room["player2"] = 0;
            }
            $ret &= db_wuzi_room::inst()->update_player1($roomid, $pid);
            $room["player1"] = $pid;
        } else if ($playerid == 2) {
            if ($room["player2"] != 0) {
                return "fail|已有玩家。";
            }

            if ($room["player1"] == $pid) {
                $ret &= db_wuzi_room::inst()->update_player1($roomid, 0);
                $room["player1"] = 0;
            }
            $ret &= db_wuzi_room::inst()->update_player2($roomid, $pid);
            $room["player2"] = $pid;
        }

        if ($room["player1"] != 0 && $room["player2"] != 0) {
            $ret &= db_wuzi_room::inst()->update_status($roomid, db_wuzi_room::STATUS_CHESSING);
            $ret &= db_wuzi_match::inst()->add(db_wuzi_match::TYPE_NORMAL, $room["player1"], $room["player2"]);
            $mid = db_wuzi_match::inst()->last_insert_id();
            $ret &= db_wuzi_room::inst()->update_match($roomid, $mid);
        }

        return ($ret !== false) ? "success" : "fail|数据库操作失败，请稍后重试。";
    }

    public function leave_ajax() {
        $roomid = get_request_assert("room");
        $playerid = get_request_assert("player");

        $ret = 0;
        if ($playerid == 1) {
            $ret = db_wuzi_room::inst()->update_player1($roomid, 0);
        } else if ($playerid == 2) {
            $ret = db_wuzi_room::inst()->update_player2($roomid, 0);
        }
        return ($ret !== false) ? "success" : "fail|数据库操作失败，请稍后重试。";
    }


}













