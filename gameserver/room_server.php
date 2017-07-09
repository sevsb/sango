<?php
include_once(dirname(__FILE__) . "/../config.php");
include_once(dirname(__FILE__) . "/../database/db_room.class.php");
include_once(dirname(__FILE__) . "/../database/db_wuzi_match.class.php");
include_once(dirname(__FILE__) . "/../database/db_wuzi_chess.class.php");

include_once(dirname(__FILE__) . "/server.php");
include_once(dirname(__FILE__) . "/client.php");

class RoomClient extends Client {
    private $mPlayer = null;

    public function RoomClient($server, $fd, $player) {
        parent::Client($server, $fd);
        $this->mPlayer = $player;
    }

    public function onCommand($op, $data) {
        switch ($op) {
        case "sit":
            return $this->onSit($data["roomid"]);
        case "stand":
            return $this->onStand($data["roomid"]);
        case "start":
            return $this->onStart($data["roomid"]);
        }
    }

    public function player() {
        return $this->mPlayer;
    }

    public function onSit($roomid) {
        $room = $this->server()->room($roomid);
        if ($room == null) {
            return;
        }
        $ret = $room->player_join($this->mPlayer);
        if (!$ret) {
            return;
        }
        $this->server()->update_room($roomid, $room);
    } 

    public function onStand($roomid) {
        $room = $this->server()->room($roomid);
        if ($room == null) {
            return;
        }
        $ret = $room->player_leave($this->mPlayer);
        if (!$ret) {
            return;
        }
        $this->server()->update_room($roomid, $room);
    }

    public function onStart($roomid) {
        $room = $this->server()->room($roomid);
        if (!$room->is_waiting()) {
            return;
        }
        if (!$room->is_full_seats()) {
            return;
        }
        $ret = $room->create_match();
        if (!$ret) {
            return;
        }
        $this->server()->update_room($roomid, $room);

        $mid = $room->matchid();
        $this->match($mid);
    }

    public function tip($msg) {
        $this->send("tip", array("message" => $msg));
    }

    public function match($mid) {
        $this->send("match", array("match" => $mid));
    }

    public function refresh() {
        $player = $this->player();
        $ret = array();
        foreach ($this->server()->get_all_rooms() as $mid => $room) {
            $ret []= $room->pack_info($player);
        }
        $this->send("refresh", $ret);
    }
};

class RoomServer extends Server {
    private $clients = array();
    private $rooms = array();

    protected function onCommand($fd, $op, $data) {
        logging::d("RoomServer", "onCommand: " . print_r($data, true));
        if ($op == "login") {
            return $this->onLogin($fd, $data["token"]);
        }
        if (!isset($this->clients[$fd])) {
            return;
        }
        $c = $this->clients[$fd];
        return $c->onCommand($op, $data);
    }

    protected function onInit() {
        logging::d("RoomServer", "reload rooms.");
        $this->rooms = room::load_all();
    }

    protected function onFirstRef() {
        swoole_timer_tick(10000, array($this, "onInit"));
    }

    public function room($id) {
        if (isset($this->rooms[$id])) {
            return $this->rooms[$id];
        }
        return null;
    }

    public function get_all_rooms() {
        return $this->rooms;
    }

    public function update_room($id, $room) {
        $this->rooms[$id] = $room;
        $this->broadcastRoomStatus();
    }

    private function onLogin($fd, $token) {
        $player = player::create_by_openid($token);
        if ($player == null) {
            return;
        }
        $this->clients[$fd] = new RoomClient($this, $fd, $player);
        logging::d("RoomServer", "{$player->nick()} enters room.");
        $this->clients[$fd]->refresh();
    }

    protected function onClientClose($fd) {
        if (!isset($this->clients[$fd])) {
            return;
        }
        $player = $this->clients[$fd]->player();
        logging::d("RoomServer", "{$player->nick()} leaves room.");
        unset ($this->clients[$fd]);
    }

    public function broadcast($op, $data) {
        foreach ($this->clients as $fd => $client) {
            $this->send($fd, $op, $data);
        }
    }

    public function broadcastRoomStatus() {
        foreach ($this->clients as $fd => $client) {
            $client->refresh();
        }
    }
};

logging::set_file_prefix("roomserver-");
logging::set_logging_dir(dirname(__FILE__) . "/../logs/");

$s = new RoomServer();
$s->start(ROOM_SERVER_PORT);



