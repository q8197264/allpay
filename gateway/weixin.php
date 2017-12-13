<?php
/**
 * 微信支付类  包括两种支付方式：JSAPI 与 线上扫码支付
 * User: Administrator
 * Date: 2016/5/11
 * Time: 12:58
 */
class weixin extends Gateway
{
    /**
     * 微信支付 路由
     * @param string $order_id
     *
     * @throws WxPayException
     */
    function pay( $data )
    {
        //订单来源平台 0:wap 1:PC
        $referer = empty($data['referer'])?'':1;
        $isUrl = empty($data['getPayUrl'])?'':1;
        if ( !empty($data['out_trade_no']) && is_array($data) )
        {
            if ( is_file(__DIR__.'/wxpay/WxPayAdaptor.php') )
            {
                require_once(__DIR__.'/wxpay/WxPayAdaptor.php');

                //加载自定义支付参数配置
                if ( empty($data['notify_url']) || empty($data['return_url']) )
                {
                    throw new AllPayException('error: Lack of specified parameters notify_url or return_url');
                }
                $this->config['NOTIFY_URL'] = $data['notify_url'];
                $this->config['RETURN_URL'] = $data['return_url'];
                !empty($data['js_api_call']) AND ($this->config['JS_API_CALL']= $data['js_api_call']);
                $config = !empty($this->config)?$this->config:'';
                $wxPayAdaptor = new WxPayAdaptor( $config );

                //判断客户端浏览器是否来自微信
                if ( is_numeric(strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')) && empty($referer) ) {
                    $msg = '微信jsapi支付';
                    $pay_way = 'JsApiPay';
                }elseif( empty($referer) ){
                    $msg = '微信H5支付';
                    $pay_way = 'WapPay';
                }else{
                    $msg = "微信扫码支付";
                    $pay_way = 'NactivePay';
                }
                $url = $wxPayAdaptor->$pay_way( $data['order_id'], $data['out_trade_no'], $data['total_fee'],$data['notify_url'], $data['return_url'], $isUrl);
                CY_log::add($msg);
                CY_log::add($url);

                return $url;
            }else{
                throw new AllPayException(__DIR__.'//wxpay/WxPayAdaptor.php is not found');
            }
        }else{
            throw new AllPayException('wxpay the params is illegal: '.json_encode($data));
        }
    }



    function notify( $pay_id='' )
    {
//        $GLOBALS['HTTP_RAW_POST_DATA'] = <<<EOF
//        <xml>
//          <appid><![CDATA[wx65cb52352db85d5e]]></appid>
//          <attach><![CDATA[支付测试]]></attach>
//          <bank_type><![CDATA[CFT]]></bank_type>
//          <fee_type><![CDATA[CNY]]></fee_type>
//          <is_subscribe><![CDATA[Y]]></is_subscribe>
//          <mch_id><![CDATA[1220405701]]></mch_id>
//          <nonce_str><![CDATA[5d2b6c2a8db53831f7eda20af46e531c]]></nonce_str>
//          <openid><![CDATA[oUpF8uMEb4qRXf22hE3X68TekukE]]></openid>
//          <out_trade_no><![CDATA[1834880]]></out_trade_no>
//          <result_code><![CDATA[SUCCESS]]></result_code>
//          <return_code><![CDATA[SUCCESS]]></return_code>
//          <sign><![CDATA[8255CDCDBB1B5E6CA321CFBA5BC22F17]]></sign>
//          <sub_mch_id><![CDATA[1220405701]]></sub_mch_id>
//          <time_end><![CDATA[20150903131540]]></time_end>
//          <total_fee>1</total_fee>
//          <trade_type><![CDATA[JSAPI]]></trade_type>
//          <transaction_id><![CDATA[1004400740201409030005092168]]></transaction_id>
//        </xml>
//EOF;
        if (empty($GLOBALS['HTTP_RAW_POST_DATA'])) {
            throw new AllPayException('Did not get notify data');
        }

        if ( is_file(__DIR__.'/wxpay/WxPayAdaptor.php') )
        {
            require_once(__DIR__.'/wxpay/WxPayAdaptor.php');

            //加载自定义支付配置
            $config = !empty($this->config)?$this->config:'';
            $wxPayAdaptor = new WxPayAdaptor( $config, false );
            $data = $wxPayAdaptor->notify();
        }else{
            throw new AllPayException(__DIR__.'/wxpay/WxPayAdaptor.php is not found');
        }

        return $data;
    }

    /**
     * 支付宝跳转返回通知
     * @return array
     * @throws WxPayException
     */
    function return_url()
    {
        //商户订单号
        $out_trade_no = $_GET['out_trade_no'];
        if ( empty($out_trade_no) ){
            throw new AllPayException("out_trade_no is empty");
        }
        if (!empty($_GET['msg'])){
            throw new AllPayException($_GET['msg']);
        }
        $data['out_trade_no'] = isset($out_trade_no)?$out_trade_no:'';
        $data['total_fee']  = isset($_GET['total_fee'])?$_GET['total_fee']:'';
        $data['msg']        = '';

        return $data;
    }

}