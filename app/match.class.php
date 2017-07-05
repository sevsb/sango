<?php

include_once(dirname(__FILE__) . "/wuzi_match.class.php");
include_once(dirname(__FILE__) . "/../database/db_wuzi_match.class.php");

class match {
    public static function create($type, $matchid) {
        switch ($type) {
        case db_room::TYPE_WUZI:
            return wuzi_match::create($matchid);
        default:
            return null;
        };
    }
};

