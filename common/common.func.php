<?php
/**-------------------------------------------------------------------|
 * 公共函数
 * User: 刘孝全
 * Date: 2016/4/8
 * Time: 18:10
 *--------------------------------------------------------------------|
/**
 * 支付方式判断
 * @param  int $type     支付类型
 *
 * @return string        支付方式
 */
if (!function_exists('payWay') )
{
    function payWay( $pay_id='' )
    {
        switch($pay_id){
            case 1://支付宝
                $payWay = 'alipay';
                break;
            case 17://一卡通
                $payWay = 'onePay';
                break;
            case 13://易宝
                $payWay = 'yeepay';
                break;
            case 14://银联
                $payWay = 'unionPay';//接口调用需要额外参数 $config['defaultbank']
                break;
            case 19://易宝预付费卡
                $payWay = 'yibaoPay';
                break;
            case 22://快捷支付(贷记卡) --信用卡
                $payWay = 'quickPay';
                break;
            case 24://快捷支付(借记卡)
                $payWay = 'quickPay';
                break;
            case 25://微信
                $payWay = 'weixin';
                break;
            default:
                throw new AllPayException("PayWay error: [pay_id error: {$pay_id}]");
                break;
        }

        return $payWay;
    }
}else{
    throw new AllPayException('function payWay was exists!');
}

/**.
 * 公共函数
 * 警告：
 * TODO: 必须在函数名前加项目前缀，防止多接口混用时重名
 */
if (!function_exists('allPayCurl'))
{
    function allPayCurl($url,$prefileds){
        $post='';
        $ch = curl_init();
        foreach($prefileds as $key => $value){
            $post .= $key.'='.$value.'&';
            $data = trim($post,'&');
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt ($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,  false);
        curl_setopt($ch, CURLOPT_TIMEOUT,  10);
        $ex = curl_exec($ch);
        curl_close($ch);
        return $ex;
    }
}