<?php
class JSSDK
{
    private $appId;
    private $appSecret;
    private $timestamp;
    private $nonceStr;

    public function __construct($appId, $appSecret, $nonceStr, $timeStamp )
    {
        $this->appId     = $appId;
        $this->appSecret = $appSecret;
        $this->nonceStr  = $nonceStr;
        $this->timestamp = $timeStamp;
    }

    /**
     * 获取config验证包
     * @return array
     */
    public function getSignPackage()
    {
        $jsapiTicket = $this->getJsApiTicket ();

        // 注意 URL 一定要动态获取，不能 hardcode.
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $url      = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

//        $timestamp = time ();
//        $nonceStr  = $this->createNonceStr ();

        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$this->nonceStr&timestamp=$this->timestamp&url=$url";
        $signature = sha1 ($string);

        $signPackage = array(
            "appId"     => $this->appId,
            "nonceStr"  => $this->nonceStr,
            "timestamp" => $this->timestamp,
            "url"       => $url,
            "signature" => $signature,
            "rawString" => $string
        );

        return $signPackage;
    }

    /**
     * 从缓存中获得票据
     * @return string
     */
    private function getJsApiTicket()
    {
        $url = "http://api.yaofang.cn/weixin_access_token/getJsApiTicket?appid=$this->appId&secret=$this->appSecret&check=4vNplDOYcti4sZxhpSb_sU5L1NmvZ";
        $res    = json_decode ($this->httpGet ($url));
        $ticket = $res->ticket;

        return $ticket;
    }

    private function httpGet($url)
    {
        $curl = curl_init ();
        curl_setopt ($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt ($curl, CURLOPT_TIMEOUT, 500);
        // 为保证第三方服务器与微信服务器之间数据传输的安全性，所有微信接口采用https方式调用，必须使用下面2行代码打开ssl安全校验。
        // 如果在部署过程中代码在此处验证失败，请到 http://curl.haxx.se/ca/cacert.pem 下载新的证书判别文件。
        curl_setopt ($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt ($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt ($curl, CURLOPT_URL, $url);

        $res = curl_exec ($curl);
        curl_close ($curl);

        return $res;
    }

    //创建随机数
//    private function createNonceStr($length = 16)
//    {
//        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
//        $str   = "";
//        for ($i = 0; $i < $length; $i++) {
//            $str .= substr ($chars, mt_rand (0, strlen ($chars) - 1), 1);
//        }
//
//        return $str;
//    }
}

