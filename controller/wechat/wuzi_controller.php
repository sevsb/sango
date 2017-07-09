<?php
include_once(dirname(__FILE__) . "/../../config.php");
include_once(dirname(__FILE__) . "/../../database/db_room.class.php");
include_once(dirname(__FILE__) . "/../../database/db_wuzi_match.class.php");
include_once(dirname(__FILE__) . "/../../database/db_wuzi_chess.class.php");

class wuzi_controller {
    
    public function player_action() {
        $openid = get_request_assert("openid");
        $nick = get_request_assert("nick");
        $faceurl = get_request_assert("faceurl");
        $player = db_players::inst()->get_player_by_openid($openid);

        if (empty($player)) {
            db_players::inst()->add_by_wechat($openid, $nick, $faceurl);
            $player = array("id" => db_players::inst()->last_insert_id(), "openid" => $openid, "nick" => $nick, "faceurl" => $faceurl);
        }
        echo json_encode($player);
    }

    private function get_player() {
        $token = get_request("token");
        if ($token == null) {
            $pss = get_session_assert("player");
            $player = new player($pss);
        } else {
            $pss = db_players::inst()->get_player_by_openid($token);
            if (empty($pss)) {
                die("please login.");
            }
            $player = player::create($pss);
        }
        return $player;
    }

    public function rooms_action() {
        $player = $this->get_player();
        $rooms = room::load_all();

        $ret = array();
        foreach ($rooms as $id => $room) {
            $ret []= $room->pack_info($player);
        }
        echo json_encode($ret);
    }

    public function matchlist_action() {
        $player = $this->get_player();
        $matches = wuzi_match::load_all($player->id(), false);

        $data = array();
        $data["chessing"] = array();
        $data["completed"] = array();
        foreach ($matches as $mid => $match) {
            if ($match->is_chessing()) {
                $data["chessing"] []= $match->pack_listinfo();
            } else {
                $data["completed"] []= $match->pack_listinfo();
            }
        }
        echo json_encode(array("op" => "matchlist", "data" => $data));
    }

    public function match_action() {
        $mid = get_request_assert("match");
        $match = wuzi_match::create($mid);
        echo json_encode(array("op" => "board", "data" => $match->pack_info()));
    }

}













