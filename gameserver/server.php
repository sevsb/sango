<?php

class Server {
    private $ws = null;
    public function Start($port) {
        $this->ws = new swoole_websocket_server("0.0.0.0", $port);
        $this->ws->on("open", array($this, "onOpen"));
        $this->ws->on("message", array($this, "onMessage"));
        $this->ws->on("close", array($this, "onClose"));
        logging::d("Server", "start websocket.");
        $this->onInit();
        $this->ws->start();
    }

    public function onOpen(swoole_websocket_server $server, swoole_http_request $request) {
        logging::d("Server", "webserver open.");
    }

    public function onMessage(swoole_server $server, swoole_websocket_frame $frame) {
        logging::d("Server", "on_message: " . $frame->data);
        $jsarr = json_decode($frame->data, true);
        $op = $jsarr["op"];
        $this->onCommand($frame->fd, $op, $jsarr);
    }

    public function onHandshake(swoole_http_request $request, swoole_http_response $response) {
        logging::d("Server", "webserver handshake.");
    }

    public function onClose(swoole_server $server, $fd, $reactorId) {
        logging::d("Server", "webserver close: $fd.");
        $this->onClientClose($fd);
    }

    public function send($fd, $op, $data) {
        logging::assert(is_array($data), "data must be an array.");
        $temp = array("op" => $op, "data" => $data);
        $s = json_encode($temp);
        $this->ws->push($fd, $s);
        // logging::d("Server", $s);
        return 0;
    }

    public function broadcast($op, $data) {
        logging::fatal("Server", "broadcast not implemented.");
    }

    protected function onInit() {
    }

    protected function onCommand($fd, $op, $data) {
        logging::fatal("Server", "onCommand not implemented.");
    }

    protected function onClientClose($fd) {
    }

};


