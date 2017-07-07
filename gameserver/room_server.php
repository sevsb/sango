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
        $ret = $this->server()->player_join($roomid, $this->mPlayer);
        if (!$ret) {
            return;
        }
        $this->server()->broadcastRoomStatus($room);
    } 

    public function onStand($roomid) {
        $room = $this->server()->room($roomid);
        if ($room == null) {
            return;
        }
        $ret = $this->server()->player_leave($roomid, $this->mPlayer);
        if (!$ret) {
            return;
        }
        $this->server()->broadcastRoomStatus($room);
    }

    public function onStart($roomid) {
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
        $this->rooms = room::load_all();
    }

    public function room($id) {
        if (isset($this->rooms[$id])) {
            return $this->rooms[$id];
        }
        return null;
    }

    public function player_join($id, $player) {
        return $this->rooms[$id]->player_join($player);
    }

    public function player_leave($id, $player) {
        return $this->rooms[$id]->player_leave($player);
    }


    private function onLogin($fd, $token) {
        $player = player::create_by_openid($token);
        if ($player == null) {
            return;
        }
        $this->clients[$fd] = new RoomClient($this, $fd, $player);
        logging::d("RoomServer", "{$player->nick()} enters room.");
    }

    protected function onClientClose($fd) {
        $player = $this->clients[$fd]->player();
        logging::d("RoomServer", "{$player->nick()} leaves room.");
        unset ($this->clients[$fd]);
    }

    public function broadcast($op, $data) {
        foreach ($this->clients as $fd => $client) {
            $this->send($fd, $op, $data);
        }
    }

    public function broadcastRoomStatus($room) {
        foreach ($this->clients as $fd => $client) {
            $player = $client->player();
            $ret = array();
            $ret["data"] = array();
            foreach ($this->rooms as $mid => $room) {
                $ret["data"][]= $room->pack_info($player);
            }
            $this->send($fd, "refresh", $ret);
        }
    }
};

$s = new RoomServer();
$s->start(ROOM_SERVER_PORT);

