<?php
include_once(dirname(__FILE__) . "/../config.php");

class client_controller {
    public function index_action() {
        $ret = WeChat::inst()->doOAuth(true);
        dump_var($ret);
    }

    public function test_action() {
        dump_var($_SERVER);
    }
}













