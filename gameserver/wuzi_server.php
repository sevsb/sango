<?php
include_once(dirname(__FILE__) . "/../config.php");
include_once(dirname(__FILE__) . "/../database/db_room.class.php");
include_once(dirname(__FILE__) . "/../database/db_wuzi_match.class.php");
include_once(dirname(__FILE__) . "/../database/db_wuzi_chess.class.php");

include_once(dirname(__FILE__) . "/server.php");
include_once(dirname(__FILE__) . "/client.php");

class WuziClient extends Client {
    private $mPlayer = null;
    private $mMatchId = 0;

    public function WuziClient($server, $fd, $player, $matchid) {
        parent::Client($server, $fd);
        $this->mPlayer = $player;
        $this->mMatchId = $matchid;
    }

    public function onCommand($op, $data) {
        switch ($op) {
        case "place":
            return $this->onPlace($data["place"]);
        }
    }

    public function player() {
        return $this->mPlayer;
    }

    public function matchid() {
        return $this->mMatchId;
    }

    public function onPlace($place) {
        $match = $this->server()->match($this->matchid());
        if ($match == null) {
            logging::e("WuziServer", "no such match: {$this->matchid()}, for player: {$this->player()->nick()}");
            return;
        }

        $winner = $match->winner();
        if ($winner != null) {
            $this->tip("{$winner->nick()} win.");
            return;
        }

        if (!$this->player()->equals($match->next_player())) {
            $this->tip("Not your turn");
            return;
        }

        $ret = $match->place_piece($place);
        if (!$ret) {
            return;
        }
        $this->server()->update_match($this->matchid(), $match);

        $winner = $match->winner();
        if ($winner != null) {
            $this->server()->broadcastWinner($match);
        }
    } 


    public function tip($message) {
        $this->send("tip", array("message" => $message));
    }

    public function refresh($match) {
        $info = $match->pack_info();
        $this->send("board", $info);
        logging::d("WuziServer", "refresh client: {$this->player()->nick()} for match: {$match->id()}");
    }

};

class WuziServer extends Server {
    private $clients = array();
    private $matches = array();

    protected function onCommand($fd, $op, $data) {
        logging::d("WuziServer", "onCommand: " . print_r($data, true));
        if ($op == "login") {
            return $this->onLogin($fd, $data["token"], $data["match"]);
        }
        if (!isset($this->clients[$fd])) {
            return;
        }
        $c = $this->clients[$fd];
        return $c->onCommand($op, $data);
    }

    public function onInit() {
        $this->matches = wuzi_match::load_all();
    }

    public function match($mid) {
        if (isset($this->matches[$mid])) {
            return $this->matches[$mid];
        }
        return null;
    }

    public function update_match($id, $match) {
        $this->matches[$id] = $match;
        $this->broadcastWuziStatus($match);
    }

    private function onLogin($fd, $token, $mid) {
        $player = player::create_by_openid($token);
        if ($player == null) {
            return;
        }
        $this->clients[$fd] = new WuziClient($this, $fd, $player, $mid);
        $this->onInit();
        logging::d("WuziServer", "{$player->nick()} joins match.");
    }

    protected function onClientClose($fd) {
        if (!isset($this->clients[$fd])) {
            return;
        }
        $player = $this->clients[$fd]->player();
        logging::d("WuziServer", "{$player->nick()} leaves match.");
        unset ($this->clients[$fd]);
    }

    public function broadcast($op, $data) {
        foreach ($this->clients as $fd => $client) {
            $this->send($fd, $op, $data);
        }
    }

    public function broadcastWuziStatus($match) {
        $info = $match->pack_info();
        $data = array("op" => "board", "data" => $info);

        foreach ($this->clients as $fd => $client) {
            if ($client->matchid() == $match->id()) {
                $client->refresh($match);
            }
        }
    }

    public function broadcastWinner($match) {
        $winner = $match->winner();
        if ($winner == null) {
            return;
        }
        foreach ($this->clients as $fd => $client) {
            if ($client->matchid() == $match->id()) {
                $client->tip("{$winner->nick()} win.");
            }
        }
    }

};

logging::set_file_prefix("wuziserver-");
logging::set_logging_dir(dirname(__FILE__) . "/../logs/");

$s = new WuziServer();
$s->start(WUZI_SERVER_PORT);



