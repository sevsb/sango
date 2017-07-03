<?php
include_once(dirname(__FILE__) . "/../../config.php");

class admin_controller {
    public function index_action() {
        if (isset($_GET["echostr"])) {
            if (!$this->checkSignature()) {
                return;
            }
            logging::d("mmserver", "success. echostr = " . $_GET["echostr"]);
            echo $_GET["echostr"];
        } else if (!isset($GLOBALS["HTTP_RAW_POST_DATA"])) {
            $result = WeChat::inst()->doOAuth();
            logging::d("mmserver", $result);
            dump_var($result);
        } else {
            $this->parsePost();
        }
    }

    private function checkSignature() {
        if (!isset($_GET["signature"]))
            return false;
        if (!isset($_GET["timestamp"]))
            return false;
        if (!isset($_GET["nonce"]))
            return false;
        if (!isset($_GET["echostr"]))
            return false;
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        $token = "123456";
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);
        if ($tmpStr == $signature) {
            return true;
        } else {
            return false;
        }
    }

    private function parsePost() {
        global $appid;
        $post = $GLOBALS["HTTP_RAW_POST_DATA"];
        logging::d("mmserver", $post);
        if (empty($post)) {
            echo "";
            return;
        }
        $xml = simplexml_load_string($post, 'SimpleXMLElement', LIBXML_NOCDATA);
        $fromUsername = $xml->FromUserName;
        $toUsername = $xml->ToUserName;
        $msgType = $xml->MsgType;


        if ($msgType == "text") {
            $content = trim($xml->Content);
            logging::d("mmserver", "received message from $fromUsername to $toUsername, type is $msgType, content = $content.");

            $now = time();
            // $title = "title";
            // $desc = "desc..";
            // $picurl = "http://mmbiz.qpic.cn/mmbiz_jpg/VYRdDWIU2oZl7ia7bIjZTbl4AMyYU9Vv4uaAOialccSsRapWRXFsKa51BeVXjtJcyehbT5GUkziaOTnHFJDzJbNiaw/640?wx_fmt=jpeg&tp=webp&wxfrom=5&wx_lazy=1";
            // $reply = "<xml>
            //     <ToUserName><![CDATA[$fromUsername]]></ToUserName>
            //     <FromUserName><![CDATA[$toUsername]]></FromUserName>
            //     <CreateTime>$now</CreateTime>
            //     <MsgType><![CDATA[news]]></MsgType>
            //     <ArticleCount>1</ArticleCount>
            //     <Articles>
            //     <item>
            //     <Title><![CDATA[$title]]></Title>
            //     <Description><![CDATA[$desc]]></Description>
            //     <PicUrl><![CDATA[$picurl]]></PicUrl>
            //     </item>
            //     </Articles>
            //     </xml>";

            $message = "http://sg.wuziyi.cc/?client";
            // $appid = "wx0d0986063a320391";
            // $appid = "wx520c15f417810387";
            // $url = "https%3A%2F%2Fchong.qq.com%2Fphp%2Findex.php%3Fd%3D%26c%3DwxAdapter%26m%3DmobileDeal%26showwxpaytitle%3D1%26vb2ctag%3D4_2030_5_1194_60";
            // $message = "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$appid}&redirect_uri={$url}&response_type=code&scope=snsapi_base&state=1#wechat_redirect";
            $reply = "<xml>
                <ToUserName><![CDATA[$fromUsername]]></ToUserName>
                <FromUserName><![CDATA[$toUsername]]></FromUserName>
                <CreateTime>$now</CreateTime>
                <MsgType><![CDATA[text]]></MsgType>
                <Content><![CDATA[$message]]></Content>
                </xml>";

            echo $reply;
        }
    }

}







