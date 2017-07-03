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
        $rooms = db_wuzi_room::inst()->get_all_rooms();
        $tpl = new tpl("client/header", "client/footer");
        $tpl->set("rooms", $rooms);
        $tpl->display("client/wuzi/index");
    }

    public function room_action() {
        $roomid = get_request_assert("room");
        $tpl = new tpl("client/header", "client/footer");
        $tpl->display("client/wuzi/match");
    }

}













