<?php
/**
 * url路由
 * User: 刘孝全
 * Date: 2016/6/29
 * Time: 16:41
 */
//例：http://component.yaofang.cn/allpay/cy_allpay.php?m=pay&out_trade_no=123223421&total_fee=1&pay_id=1
if (isset($_SERVER['SERVER_NAME']) AND 'component.yaofang.cn'==$_SERVER['SERVER_NAME']){
    $url = parse_url($_SERVER['REQUEST_URI']);
    $params = array();
    count($url)>1 AND parse_str(end($url),$params);
    $url = trim($url['path'],'/');

    //模式一：目录模式
    if (strpos($url,'.php')===false){
        //http://component.yaofang.cn/allpay/cy_allpay/pay?out_trade_no=123223421&total_fee=1&pay_id=1&getPayUrl=1
        $ex = explode('/',$url);
        array_shift($ex);
        $c = array_shift($ex);
        $m = array_shift($ex);
        $params = empty($params)?( empty($ex)?array():(count($ex)>0?$ex:array_shift($ex)) ):array_merge($ex,$params);
    }else{
        //模式二：query_string模式
        $c = ltrim(rtrim(strrchr($url,'/'),'.php'),'/');
        $m = array_shift($params);
    }
    if (isset($c)){
        $c = new $c;
        if (in_array($m,get_class_methods($c))){
            echo json_encode(call_user_func_array(array($c,$m),$params));
        }else{
            exit('url error :The class method'.$m.' is not found or empty!');
        }
    }else{
        exit('url error : The class file is not exists!');
    }

}