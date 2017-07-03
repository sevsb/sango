<?php

if (file_exists(dirname(__FILE__) . "/../PATH.php")) {
    include_once(dirname(__FILE__) . "/../PATH.php");
}

include_once(dirname(__FILE__) . "/../framework/config.php");

include_once(FRAMEWORK_PATH . "/helper.php");
include_once(FRAMEWORK_PATH . "/logging.php");
include_once(FRAMEWORK_PATH . "/tpl.php");
include_once(FRAMEWORK_PATH . "/cache.php");
include_once(FRAMEWORK_PATH . "/database.php");

include_once(dirname(__FILE__) . "/database/db_players.class.php");
include_once(dirname(__FILE__) . "/libs/Lock.php");
include_once(dirname(__FILE__) . "/libs/WeChat.php");


// database
defined('MYSQL_SERVER') or define('MYSQL_SERVER', '180.76.188.68');
defined('MYSQL_USERNAME') or define('MYSQL_USERNAME', 'sango');
defined('MYSQL_PASSWORD') or define('MYSQL_PASSWORD', 'sango');
defined('MYSQL_DATABASE') or define('MYSQL_DATABASE', 'sango');
defined('MYSQL_PREFIX') or define('MYSQL_PREFIX', '');


defined('TABLE_WIZI_ROOM') or define('TABLE_WUZI_ROOM', MYSQL_PREFIX . "wuzi_room");
defined('TABLE_WIZI_MATCH') or define('TABLE_WUZI_MATCH', MYSQL_PREFIX . "wuzi_match");
defined('TABLE_WIZI_CHESS') or define('TABLE_WUZI_CHESS', MYSQL_PREFIX . "wuzi_chess");
defined('TABLE_WIZI_CHAT') or define('TABLE_WUZI_CHAT', MYSQL_PREFIX . "wuzi_chat");
defined('TABLE_PLAYERS') or define('TABLE_PLAYERS', MYSQL_PREFIX . "players");


// mailer
defined('MAIL_SUBJECT_PREFIX') or define('MAIL_SUBJECT_PREFIX', '');

