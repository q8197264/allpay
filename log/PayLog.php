<?php

/**
 * 支付日志类
 * User:刘孝全
 * Date: 2016/2/18
 * Time: 9:59
 */
error_reporting(0);
class PayLog
{
    public static $path = PAY_LOG;
    private static $log = '';

    /**
     * 支付日志
     * @param string $tip       提示
     * @param array  $log       日志内容
     */
    public static function notify( $tip, $log='' )
    {
        //创建日志目录
        $year = date('Y');
        $mouth = date('m');
        $day = date('d');
        $path = rtrim(NOTIFY_LOG,'/')."/{$year}/{$mouth}";//log日志
        if (!is_dir($path)) {
            chmod($path,0777);
            mkdir($path,0777,true);
        }
        $path = "{$path}/{$day}.log";

        if (empty($log)) {
            file_put_contents($path,"$tip\n时间：".date('Y-m-d H:i:s',time())."\n\n",FILE_APPEND);
            return false;
        }

        //判断是get数组还是post的XML
        if (is_array($log)) {
            $notify_data = $log;
        }else{
            //把xml解析成关联数组
            $notify_data = json_decode(json_encode(simplexml_load_string($log, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        }

        //写入日志
        $time= date('Y-m-d H:i:s',time());
        $log = <<<EOF
订单支付ID: {$notify_data['out_trade_no']}
时     间：{$time}\r\n
EOF;
        $fp = fopen($path,"a");
        //flock($fp, LOCK_EX);
        if (!empty($notify_data) && is_array($notify_data)){
            self::$log = "$tip\r\n";
            array_walk_recursive($notify_data,'self::combin_arr');
            $log .= self::$log;
            self::$log='';
        }
        fwrite($fp,"$log\r\n");
        //flock($fp, LOCK_UN);
        fclose($fp);
    }

    /**
     * 循环拼接字符串
     * @param $v
     * @param $k
     */
    private static function combin_arr( $v, $k )
    {
        self::$log .= <<<EOF
            【{$k}】: "$v"\r\n
EOF;
    }

}