<?php

class Lock {
    private $keypath = null;
    // private $id = null;
    private $fp = null;

    public function __construct($key) {
        $this->keypath = "/tmp/" . $key;
        // $this->id = uniqid("", true);
        touch($this->keypath);
        @chmod($this->keypath, 0777);
    }

    public function __destruct() {
        $this->release();
    }

    public function acquire($block = true) {
        if (!file_exists($this->keypath)) {
            touch($this->keypath);
            @chmod($this->keypath, 0777);
        }
        $this->fp = fopen($this->keypath, "r");
        if (!$this->fp)
            return false;
        if ($block)
            return flock($this->fp, LOCK_EX);
        return flock($this->fp, LOCK_EX | LOCK_NB);

        // while (file_exists($this->keypath)) {
        //     if (!$wait) {
        //         return false;
        //     }
        // }
        // touch($this->keypath);
        // file_put_contents($this->keypath, $this->id);
        // return true;
    }

    public function release() {
        if ($this->fp == null || !$this->fp)
            return false;
        flock($this->fp, LOCK_UN);
        fclose($this->fp);
        $this->fp = null;
        return true;
        // if (!file_exists($this->keypath))
        //     return;
        // $c = file_get_contents($this->keypath);
        // if ($c == $this->id)
        //     unlink($this->keypath);
    }

    // public function test() {
    //     // return !file_exists($this->keypath);
    // }
}


