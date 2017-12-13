<?php
//并加载配置文件
require_once(__DIR__.'/config/config.php');

/**
 * 支付聚合接口
 * User: 刘孝全
 * Date: 15-8-13
 * Time: 上午11:08
 * To change this template use File | Settings | File Templates.
 */
class cy_allpay
{
    /**
     * 支付帐号参数配置
    //例：微信 自定义配置收款帐号
    $config = array(
        'APPID'=>'wx65cb52352db85d5e',
        'MCHID'=>'1220405701',
        'KEY'=>'h4GuHGelLIX87Ha9ChrO5z7qWvV02jPn',
        'APPSECRET'=>'41ffa612047a236db1d8b3781345e8a8',
        'JS_API_CALL'=>'http://m.yaofang.cn/wxpay/pay',//调起支付页(仅用于JSAPI必配)
        'SSLCERT_PATH'=>'http://component.yaofang.cn/allpay/gateway/wxpay/cert/apiclient_cert.pem',
        'SSLKEY_PATH'=>'http://component.yaofang.cn/allpay/gateway/wxpay/cert/apiclient_key.pem',
        'CURL_PROXY_HOST'=>'0.0.0.0',
        'CURL_PROXY_PORT'=>0,
        'REPORT_LEVENL'=>1,
    );
     * @param array $config
     */
    public function _config( $config=array() )
    {
        if ( !empty($config) && is_array($config) ) {

            //开发者设置日志路径
            if ( !empty($config['log_path']))
            {
                CY_Log::path($config['log_path']);
                $config['log_path']='';
                $this->config =  array_filter($config,'strlen');
            }
        }
    }

    //----------------------------- TODO:支付调起接口 -------------------------------------
    /**
     * 三方支付入口：调起支付
     * @param int $order_id                             订单id                        (必填)
     * @param int $pay_id                               支付方式                      (必填)
     * @param     $card_id                              储蓄/信用卡  卡号              (可填)
     * @param int $vcode                                储蓄/信用卡快捷支付 短信验证码  (可填)
     *
     * @return string
     */
    public function pay( $params=array() )
    {
        //接收直接传参
        $params = array(
            //必填参数
            'out_trade_no' => isset($params['out_trade_no']) ? $params['out_trade_no'] : '',//必填
            'total_fee'    => isset($params['total_fee']) ? $params['total_fee'] : '',//必填
            'pay_id'       => isset($params['pay_id']) ? $params['pay_id'] : '',//必填
            'notify_url'   => isset($params['notify_url']) ? $params['notify_url'] : '',//必填
            'return_url'   => isset($params['return_url']) ? $params['return_url'] : '',//必填
            'referer'      => isset($params['referer']) ? $params['referer'] : '',//必填 0:wap 1:PC (默认为0)

            //可选参数【除微信与支付宝外的支付】
            'card_num'     => isset($params['card_num']) ? $params['card_num'] : '',//信用卡快捷支付必填
            'auth_code'    => isset($params['auth_code']) ? $params['auth_code'] : '',//信用卡快捷支付必填
            'bankcode'     => isset($params['bankcode']) ? $params['bankcode'] : '',//银联支付必填
            'order_id'     => isset($params['order_id']) ? $params['order_id'] : '',//非必填（建议填写,以便帐户查阅）
            'js_api_call'  => isset($params['js_api_call']) ? $params['js_api_call'] : '',//非必填 [回调支付页(仅用于JSAPI)]
            'getPayUrl'    => isset($params['getPayUrl']) ? $params['getPayUrl'] : '',//非必填（不推荐使用）
        );

        //接收get或post参数
        if ( !empty($_REQUEST) ){
            foreach ($params as $k=>$v){
                if ( isset($_REQUEST[$k]) ){
                    $params[$k] = $_REQUEST[$k];
                }
            }
        }

        //过滤为空的参数
        $params =  array_filter($params,'strlen');
        $params['order_id']     = isset($params['order_id']) ? $params['order_id'] : '';

        //日志开始记录
        CY_log::add("pay start <<<params=".json_encode($params));

        try{
//            var_dump(array_keys($params));
            //验证必传参数
            if ($count=array_intersect(array('out_trade_no','total_fee','pay_id','notify_url','return_url'),array_keys($params)))
            {
                if (count($count)!=5){
                    $hint = implode(array_diff(array('out_trade_no','total_fee','pay_id','notify_url','return_url'),array_keys($params)),', ');
                    throw new AllPayException("required parameter missing: {$hint}");
                }
            }

            //选择支付方式
            $payName = payWay( $params['pay_id'] );

            //加载调起支付类 ---支付路由，转接对应支付类
            $pay = new $payName;
            if (! $pay instanceof $payName OR empty($pay)) { throw new AllPayException("Class file {$payName}.php is not found!"); }

            $this->config['notify_url'] = empty($params['notify_url'])?'':$params['notify_url'];
            $this->config['return_url'] = empty($params['return_url'])?'':$params['return_url'];
            !empty($this->config) AND $pay->config = $this->config;//支付参数

            $url = $pay->pay( $params );
        }catch(AllPayException $e){
            $err_msg = $e->getMessage();
            $result['err_msg'] = isset($err_msg)?$err_msg:'';//错误信息
        }
        //注：所有接口必返回的三个元素
        $result['return_code'] = isset($err_msg)?1:0;//状态返回码，一般用于判断请求/调用接口是否成功返回所需数据
        $result['data'] = isset($url)?$url:'';//返回最终数据

        return $result;
    }

    /**
     * 储蓄/信用卡 快捷支付短信验证码获取
     * @param     $order_id             订单号
     * @param     $card_id              卡号
     * @param int $type                 卡类型: 储蓄卡 信用卡
     *
     * @return array|string
     * @throws Exception
     */
    public function verify_sms( $card_no='', $out_trade_no='', $total_fee='', $phone='', $pay_id='' )
    {
        //判断支付方式是否存在：存在则调用
        try{
            $pay = new quickPay;
            !empty($this->config) AND ($pay->config = $this->config);//支付参数
            $msg = $pay->get_sms( $card_no, $out_trade_no,$total_fee, $phone, $pay_id );
        }catch(AllPayException $e){
            $msg = $e->getMessage();
        }
        return $msg;
    }

    //----------------------------- TODO:支付结果通知接口 -------------------------------------
    /**
     * 支付结果通知
     * 支付完成后，微信会把相关支付和用户信息发送到商户设定的通知URL，
     * 商户接收回调信息后，根据需要设定相应的处理流程。
     *
     * 这里使用log文件形式记录回调信息
     * @param int $pay_id     支付的方式,以支付方式调用相应支付的处理方法: 1=支付宝 ,25=微信...
     *
     * @return bool
     */
    public function notify( $pay_id='' )
    {
        CY_log::add("begin notify ...");
        try{
            //选择支付方式
            $payName = payWay( $pay_id );

            //加载调起支付类 ---支付路由，转接对应支付类
            $pay = new $payName;
            if (!$pay instanceof $payName){throw new AllPayException("object[$pay] is not a class[$payName] instantiation");}

            //支付参数
            !empty($this->config) AND $pay->config = $this->config;

            $result = $pay->notify( $pay_id );
        }catch(AllPayException $e){
            $result['result_code'] = 1;
            $result['msg'] = 'fail';
            $err_msg = $e->getMessage();
        }
        //注：所有接口必返回的三个元素
        $result['err_msg'] = isset($err_msg)?$err_msg:'';//错误信息
        CY_log::add($result);
        CY_log::add("end notify!");

        return $result;
    }



    /**
     * 跳转通知页 （可选）
     * @return mixed
     * @throws WxPayException
     */
    function return_url( $pay_id=0 )
    {
        try{
            //选择支付方式
            $payName = payWay( $pay_id );

            //加载调起支付类 ---支付路由，转接对应支付类
            $pay = new $payName;
            if (!$pay instanceof $payName){ throw new AllPayException("object[$pay] is not a class[$payName] instantiation"); }

            //支付参数
            !empty($this->config) AND $pay->config = $this->config;

            $result = $pay->return_url();
        }catch(AllPayException $e){
            $result['result_code'] = 1;
            $result['msg'] = 'fail';
            $result['err_msg'] = $e->getMessage();//错误信息
        }
        CY_log::add("return_url pay end\n\n");

        return $result;
    }
}