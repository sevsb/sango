<?php
include_once(dirname(__FILE__) . "/../config.php");

class client_controller {
    public function index_action() {
        $ret = WeChat::inst()->doOAuth(true);
        if ($ret == null) {
            die("认证失败。");
        }
        // $_SESSION["wechat"] = $ret;
        $openid = $ret["openid"];
        $player = db_players::inst()->get_player_by_openid($openid);
        if (empty($player)) {
            $nick = $ret["nickname"];
            $faceurl = $ret["headimgurl"];
            db_players::inst()->add_by_wechat($openid, $nick, $faceurl);
            $player = array("id" => db_players::inst()->last_insert_id(), "openid" => $openid, "nick" => $nick, "faceurl" => $faceurl);
        }
        $_SESSION["player"] = $player;
        $_SESSION["user.name"] = $player["nick"];
        go("client/wuzi/index");
    }

    public function test_action() {
        unset($_SESSION["player"]);

        $userid = get_request_assert("userid");
        $player = db_players::inst()->get_player_by_id($userid);
        logging::assert(!empty($player), "No such player.");
        $_SESSION["player"] = $player;
        go("client/wuzi/room");
    }

}













