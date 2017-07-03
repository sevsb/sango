<?php

include_once(dirname(__FILE__) . "/../config.php");

class db_players extends database_table {
    private static $instance = null;
    public static function inst() {
        if (self::$instance == null)
            self::$instance = new db_players();
        return self::$instance;
    }

    private function db_players() {
        parent::database_table(MYSQL_DATABASE, TABLE_PLAYERS);
    }

    public function get_player_by_id($id) {
        $id = (int)$id;
        return $this->get_one("id = $id");
    }

    public function get_player_by_openid($openid) {
        $openid = $this->escape($openid);
        return $this->get_one("openid = $openid");
    }

    public function add_by_wechat($openid, $nick, $faceurl) {
        return $this->insert(array("openid" => $openid, "nick" => $nick, "faceurl" => $faceurl));
    }

    public function add_by_register($username, $password, $telephone) {
        return $this->insert(array("username" => $username, "password" => $password, "telephone" => $telephone));
    }
};


