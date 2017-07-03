<?php

include_once(dirname(__FILE__) . "/../config.php");
include_once(dirname(__FILE__) . "/Lock.php");

class WeChat {

    private $tokenfile = null; // "/tmp/wechat_token." . WEAUTH_APPID . ".txt"; // _i708luo.txt';
    private $ticketfile = null;

    private $apilock = null;

    private static $instance = null;
    public static function inst() {
        if (self::$instance == null)
            self::$instance = new WeChat();
        return self::$instance;
    }


    private function __construct() {
        // $this->apilock = new Lock("wechat_api_lock_i708luo");
        $this->tokenfile = "/tmp/wechat_token." . WEAUTH_APPID . ".txt";
        $this->ticketfile = "/tmp/wechat_ticket." . WEAUTH_APPID . ".txt";
        $this->apilock = new Lock("wechat_api_lock_" . WEAUTH_APPID);
    }

    public function __destruct() {
        $this->apilock->release();
    }

    public function read($url, $data = null) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        if ($data != null) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        $out = curl_exec($ch);
        curl_close($ch);
        return $out;
    }

    /**
     * 与微信交互，获取token
     * @return type
     */
    public function grant_new_access_token() {
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . WEAUTH_APPID . "&secret=" . WEAUTH_SECRET;
        // $url = "http://localhost/qianba/test/gen.php?action=grant_token";
        $out = $this->read($url);
        // var_dump($out);
        logging::d('WeChat', "grant new access token: $out");
        return json_decode($out, true);
    }

    /**
     * 从wechat_token.txt中拿token值，拿到返回，未拿到返回空
     * @return type
     */
    public function get_access_token_jsonarray() {
        if (!file_exists($this->tokenfile))
            return null;
        $c = json_decode(file_get_contents($this->tokenfile), true);
        if (!isset($c["access_token"])) {
            unlink($this->tokenfile);
            return null;
        }
        return $c;
    }

    public function get_access_token() {
        $c = $this->get_access_token_jsonarray();
        return ($c != null && isset($c['access_token'])) ? $c['access_token'] : null;
    }

    /**
     * 删除存储token的文件
     */
    public function release_access_token() {
        if (file_exists($this->tokenfile))
            unlink($this->tokenfile);
    }

    /**
     * 获取token值，如果未过期则直接取值，已过期则与微信交互后获取，应用了并发锁
     * @return type
     */
    public function renew_token_locked() {
        $lock = new Lock("token_lock_" . WEAUTH_APPID);
        $token = null;

        // $this->release_access_token();

        for ($i = 0; $i < 3; $i++) {
            if ($lock->acquire(false)) {
                $token = $this->grant_new_access_token();
                // var_dump($token);
                if (isset($token["access_token"])) {
                    $token["last_time"] = time();
                    $json = json_encode($token);
                    file_put_contents($this->tokenfile, $json);
                    @chmod($this->tokenfile, 0777);
                    $this->grant_new_JSSDK_ticket($token["access_token"]);
                    // echo "granted___";
                    $lock->release();
                    return $token;
                }
                // echo "___released___";
                $this->release_access_token();
                $lock->release();
                continue;
            }

            while (!$lock->acquire(false)) {
                // echo ".";
            }
            $lock->release();

            $token = $this->get_access_token_jsonarray();
            if ($token != null) {
                break;
            }
        }
        return $token;
    }

    /**
     * 锁定微信入口，获取token值，执行需要的操作
     * @param type $function
     * @param type $param
     * @return type
     */
    private function run_and_check_api($function, $param) {
        $this->apilock->acquire();

        $access_token = null;
        $out = null;
        for ($i = 0; $i < 3; $i++) {
            $access_token = $this->get_access_token_jsonarray();
            if ($access_token == null) {
                $this->release_access_token();
                $access_token = $this->renew_token_locked();
            }
            if ($access_token == null) {
                $this->apilock->release();
                return null;
            }
            $out = $this->$function($access_token["access_token"], $param);
//            file_put_contents("/tmp/error/errorLog" . date("Y-d-m") . ".txt", date("Y-m-d H:i:s") . "   {$function}   {$out}\n", FILE_APPEND);
            $out = json_decode($out, true);
            if ($out == null || (isset($out["errcode"]) && ($out['errcode'] == 42001 || $out['errcode'] == 40014 || $out['errcode'] == 40001))) {
                $this->release_access_token();
                continue;
            }
            break;
        }
        $this->apilock->release();
        return $out;
    }

    /**
     * 执行发送微信
     * @param type $access_token
     * @param type $data_array
     * @return type
     */
    private function send_template_message_interlocked($access_token, $data_array) {
        $data_json = json_encode($data_array);
        $url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=$access_token";
        // $url = "http://localhost/qianba/test/gen.php?action=token_timeout&token=$access_token";
        return $this->read($url, $data_json);
    }

    /**
     * 锁定微信入口，执行发送微信
     * @param type $data_array
     * @return type
     */
    public function send_template_message($data_array) {
        return $this->run_and_check_api('send_template_message_interlocked', $data_array);
    }

    /**
     * 获取JS-SDK的ticket
     * @param type $token
     * @param type $data
     * @return type
     */
    private function get_JSSDK_ticket_interlocked($token, $data) {
        if (!file_exists($this->ticketfile)) {
            return null;
        }
        $ticket = json_decode(file_get_contents($this->ticketfile), TRUE);
        if (empty($ticket)) {
            return null;
        }
        if (time() - $ticket['last_time'] > 7100) {
            return null;
        }
        return json_encode($ticket['ticket']);
    }

    /**
     * 给外部调用的获取ticket方法
     * @return type
     */
    public function get_JSSDK_ticket() {
        return $this->run_and_check_api('get_JSSDK_ticket_interlocked', array());
    }

    /**
     * 与微信交互获取ticket
     * @param type $token
     */
    public function grant_new_JSSDK_ticket($token) {
        $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=$token";
        $out = $this->read($url);
        $ticket = json_decode($out, true);
        if (isset($ticket['ticket'])) {
            $data['ticket'] = $ticket['ticket'];
            $data['last_time'] = time();
            file_put_contents($this->ticketfile, json_encode($data));
            @chmod($this->ticketfile, 0777);
        } else {
            file_put_contents($this->ticketfile, null);
            @chmod($this->ticketfile, 0777);
        }
        logging::d('WeChat', "grant new ticket: $out");
    }

    public function get_token_tmp($token, $data) {
        return json_encode($token);
    }

    public function get_token() {
        return $this->run_and_check_api('get_token_tmp', array());
    }

    /**
     * JS-SDK的config所需信息
     * @return type
     */
    public function get_SignPackage() {
        $jsapiTicket = $this->get_JSSDK_ticket();

        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

        $timestamp = time();
        $nonceStr = $this->createNonceStr();

        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";

        $signature = sha1($string);

        $signPackage = array(
            "appid" => WEAUTH_APPID,
            "noncestr" => $nonceStr,
            "timestamp" => $timestamp,
            "url" => $url,
            "signature" => $signature,
            "rawstring" => $string
        );
        return $signPackage;
    }

    /**
     * 创建随机字符串
     * @param type $length
     * @return type
     */
    private function createNonceStr($length = 16) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    private function getUserinfoByOpenidInterlocked($access_token, $openid) {
        $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token={$access_token}&openid={$openid}&lang=zh_CN";
        return $this->read($url);
    }

    public function getUserinfoByOpenid($openid) {
        return $this->run_and_check_api('getUserinfoByOpenidInterlocked', $openid);
    }

    private function getUsersInterlocked($access_token, $data) {
        $openid = array();
        $i = 0;
        foreach ($data as $v) {
            $openid['user_list'][$i]['openid'] = $v;
            $openid['user_list'][$i]['lang'] = "zh-CN";
            $i ++;
        }
        $url = "https://api.weixin.qq.com/cgi-bin/user/info/batchget?access_token={$access_token}";
        return $this->read($url, json_encode($openid));
    }

    public function getUsers($openidArray) {
        return $this->run_and_check_api('getUsersInterlocked', $openidArray);
    }



    private function send_custom_message_interlocked($access_token, $data_array) {
        $data_json = json_encode($data_array);
        $url = "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=$access_token";
        return $this->read($url, $data_json);
    }

    public function send_custom_message($data_array) {
        return $this->run_and_check_api('send_custom_message_interlocked', $data_array);
    }


    public function doOAuth($userinfo = false) {
        $code = isset($_REQUEST["code"]) ? $_REQUEST["code"] : null;

        if ($code == null) {
            // $currentUri = ROOT_URL . $_SERVER["REQUEST_URI"]; // substr($_SERVER['SCRIPT_NAME'], 1); //  . '?' . $_SERVER['QUERY_STRING'];
            $currentUri = get_current_url();

            // $currentUri = "http://sg.wuziyi.cc/test/t.html";
            logging::d("WeChat", "redirect_uri = $currentUri");

            $currentUri = urlencode($currentUri);
            $snsapi = $userinfo ? "snsapi_userinfo" : "snsapi_base";
            $checkUrl = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=" . WEAUTH_APPID . "&redirect_uri={$currentUri}&response_type=code&scope={$snsapi}&state=1#wechat_redirect";
            logging::d('WeChat', "doOAuth, checkurl is: $checkUrl");
            header('location:' . $checkUrl);
            exit;
        }
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=" . WEAUTH_APPID . "&secret=" . WEAUTH_SECRET . "&code=$code&grant_type=authorization_code";
        $result = $this->read($url);
        $json = json_decode($result, true);

        if (!isset($json["openid"])) {
            logging::e('WeChat', $json['errcode'] . '            msg: ' . $json['errmsg']);
            return null;
        }

        $openid = $json["openid"];
        $result = array("openid" => $openid);

        if (isset($json['scope']) && $json['scope'] == "snsapi_userinfo") {
            $access_token = $json["access_token"];
            $url = "https://api.weixin.qq.com/sns/userinfo?access_token=$access_token&openid=$openid";
            $plain = $this->read($url);
            $json = json_decode($plain, true);
            $openid = $json["openid"];
            $result["nickname"] = $json["nickname"];
            $result["sex"] = $json["sex"];
            $result["language"] = $json["language"];
            $result["city"] = $json["city"];
            $result["province"] = $json["province"];
            $result["country"] = $json["country"];
            $result["headimgurl"] = $json["headimgurl"];
        }
        return $result;
    }
}




