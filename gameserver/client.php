<?php
class Client {
    private $mServer = null;
    private $fd = null;
    public function Client($server, $fd) {
        $this->fd = $fd;
        $this->mServer = $server;
    }

    public function send($op, $data) {
        $this->mServer->send($this->$fd, $op, $data);
    }

    public function server() {
        return $this->mServer;
    }
};

