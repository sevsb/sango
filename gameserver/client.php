<?php
class Client {
    private $mServer = null;
    private $mFd = null;
    public function Client($server, $fd) {
        $this->mFd = $fd;
        $this->mServer = $server;
    }

    public function fd() {
        return $this->mFd;
    }

    public function send($op, $data) {
        $this->mServer->send($this->fd(), $op, $data);
    }

    public function server() {
        return $this->mServer;
    }
};

