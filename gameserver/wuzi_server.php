<?php
include_once(dirname(__FILE__) . "../config.php");
include_once(dirname(__FILE__) . "../database/db_room.class.php");
include_once(dirname(__FILE__) . "../database/db_wuzi_match.class.php");
include_once(dirname(__FILE__) . "../database/db_wuzi_chess.class.php");


class server {
    private $ws = null;
    private $clients = array();

    public function start() {
        $this->ws = new swoole_websocket_server("0.0.0.0", 19504);
        $this->ws->on("open", array($this, "on_open"));
        $this->ws->on("message", array($this, "on_message"));
        $this->ws->on("close", array($this, "on_close"));
        logging::d("Server", "start websocket.");
        $this->ws->start();
    }

    public function on_open(swoole_websocket_server $server, swoole_http_request $request) {
        logging::d("Server", "webserver open.");
    }   

    public function on_message(swoole_server $server, swoole_websocket_frame $frame) {
        logging::d("Server", "on_message: " . $frame->data);

        if (!isset($this->clients[$frame->fd])) {
            $this->clients[$frame->fd] = array("match" => 0, "player" => 0);
        }
        $jsarr = json_decode($frame->data, true);
        $op = $jsarr["op"];
        if ($op == "login") {
            $player = $jsarr["player"];
            $this->clients[$frame->fd]["player"] = $player;
        } else if ($op == "match") {
            $type = db_room::TYPE_WUZI;
            $mid = $jsarr["match"];
            $this->clients[$frame->fd]["match"] = $mid;
            $this->clients[$frame->fd]["type"] = $type;
        } else if ($op == "place") {
            $place = $jsarr["place"];
            $player = $this->clients[$frame->fd]["player"];
            $mid = $this->clients[$frame->fd]["match"];
            $type = $this->clients[$frame->fd]["type"];

            $match = match::create($type, $mid);
            if ($match->winner() != null) {
                $this->ws->push($frame->fd, json_encode(array("op" => "tip", "ret" => "fail", "reason" => "game over. {$match->winner()->nick()} win.")));
                return;
            }
            $lp = $match->next_player();
            if ($lp->id() != $player) {
                $this->ws->push($frame->fd, json_encode(array("op" => "tip", "ret" => "fail", "reason" => "Not your turn.")));
                return;
            }
            $ret = $match->place_piece($place);
            $retarr = explode("|", $ret);
            if (count($retarr) == 2) {
                $this->ws->push($frame->fd, json_encode(array("op" => "tip", "ret" => $retarr[0], "reason" => $retarr[1])));
            } else {
                $this->ws->push($frame->fd, json_encode(array("op" => "tip", "ret" => $ret)));
            }
            foreach ($this->clients as $fd => $client) {
                if ($client["match"] == $mid) {
                    $this->ws->push($fd, json_encode(array("op" => "place", "place" => $place, "player" => $player)));
                }
            }
        }
    }   

    public function on_handshake(swoole_http_request $request, swoole_http_response $response) {
        logging::d("Server", "webserver handshake.");
    }   

    public function on_close(swoole_server $server, $fd, $reactorId) {
        logging::d("Server", "webserver close: $fd, " . $this->clients[$fd]["player"]);
        unset($this->clients[$fd]);
    }   
};

$s = new server();
$s->start();

