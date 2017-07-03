<?php
include_once(dirname(__FILE__) . "/../../config.php");

class wuzi_controller {
    public function index_action() {
         $tpl = new tpl("client/header", "client/footer");
         $tpl->display("client/wuzi/index");
    }
}













