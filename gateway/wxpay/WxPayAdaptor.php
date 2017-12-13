<?php

/**
 * 微信支付适配器
 * User: Administrator
 * Date: 2016/6/30
 * Time: 16:29
 */
class WxPayAdaptor
{
    /**
     * @param array     $config         收款帐户参数配置
     * @param bool|true $vNotify        是否开启notify_url 等地址判空
     *
     * @throws AllPayException
     */
    function __construct($config=array(),$vNotify=true)
    {
        if (is_file(__DIR__.'/WxPay.Config.php')){
            require_once(__DIR__.'/WxPay.Config.php');
            WxPayConfig::$JS_API_CALL = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
            if (!empty($config)){
                $this->_config($config,$vNotify);
            }
        }else{
            throw new AllPayException('error: Configuration file is not found!!');
        }
    }

    /**
     * 配置文件.
    $alipay_config = array(
    'partner'       => '2088201895953532',
    'key'           => 'hi9c8qvpnyowmjocnx82c3jyaglvbtng',
    'sign_type'     => 'MD5',
    'input_charset' => 'utf-8',
    'cacert'        => ALIPAY_PATH.'/cacert.pem',
    'transport'     => 'http',
    );
     * */
    protected function _config( $config=array(), $vNotify )
    {
        //配置开发者自定义参数
        $default_config = get_class_vars('WxPayConfig');
        if (!empty($config)){
            foreach ( $default_config as $k=>$v ){
                WxPayConfig::$$k = array_key_exists($k,$config)?$config[$k]:$v;
            }
        }

        //判断父方法名:notify则不要此验证
        if ( $vNotify && (empty( WxPayConfig::$NOTIFY_URL ) || empty( WxPayConfig::$RETURN_URL ) || empty( WxPayConfig::$JS_API_CALL )) )
        {
            throw new AllPayException('error: Lack of specified parameters notify_url or return_url or JS_API_CALL');
        }

        //判断自定义参数是否生效
        if ( $c=array_diff($default_config, get_class_vars('WxPayConfig')) )
        {
            //自定义参数成功
            if ( get_class_vars('WxPayConfig') )
            {
                //自定义参数生效
                CY_log::add('info: The Configuration parameter Has been changed!! config='.json_encode($config));
            }else{
                //没有参数
                throw new AllPayException('error: Configuration parameter is empty!!');
            }
        }else{
            //使用默认参数
            CY_log::add('info: Configuration parameter is default!! config='.json_encode($config));
        }
    }

    /**
     * 微信浏览器支付
     * @param $order_id
     *
     * @return string
     * @throws WxPayException
     */
    function JsApiPay( $order_id, $out_trade_no, $total_fee, $notify_url, $return_url, $isUrl='' )
    {
        //临时存储 order_id （在自刷新之前）
        if ( !empty($order_id) ) $_SESSION['weixin_order_id'] = $order_id;

        //----------------------JSAPI支付
        require_once (__DIR__.'/lib/WxPay.JsApiPay.php');
        $tools = new JsApiPay();

        $openId = $tools->GetOpenid();//自刷新 （获得openId）
        //$openId = 'o-zWjtzJu502u9YbAXLuys9tTEy4';
        if (empty($openId)) {
            throw new AllPayException('openId is empty!');
        }

        //获得 order_id （在自刷新之后）
        if ( empty($order_id) && !empty($_SESSION['weixin_order_id'] ) ){
            $order_id = $_SESSION['weixin_order_id'];
            unset($_SESSION['weixin_order_id']);
        }

        //设置支付日志
        if ( empty($out_trade_no) ) {
            throw new AllPayException("out_trade_no is empty! order_id={$order_id}");
        }

        //========= 使用统一支付接口，依次获取openid -> prepay_id 组成签名并组合成post数据============
        $input = new WxPayUnifiedOrder();

        //设置统一支付接口参数
        $input->SetAttach( "微信订单:{$order_id}" );
        $input->SetOut_trade_no( $out_trade_no.'|JP'.time() );
        $input->SetTotal_fee( $total_fee*100 );
        $input->SetTime_start( date("YmdHis") );
        $input->SetTime_expire( date("YmdHis", time() + 600) );
        $input->SetGoods_tag( "WAP" );
        $input->SetNotify_url( $notify_url );
        $input->SetBody( "公众号支付" );
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($openId);
        $order = WxPayApi::unifiedOrder($input, 60);

        //发起支付失败
        if ( isset($order['result_code']) && strcasecmp('FAIL',$order['result_code']) == 0 ) {
            throw new AllPayException('WeiXin PAY FAIL:'.$order['err_code_des']);
        }else if( isset($order['return_code']) && strcasecmp('FAIL',$order['return_code']) == 0 ){
            throw new AllPayException('WeiXin PAY FAIL:'.$order['return_msg']);
        }

        $jsApiParameters = (array) json_decode($tools->GetJsApiParameters($order));
        if ( !is_file(__DIR__.'/lib/jssdk.php') )
        {
            CY_log::add('file not found:'.__DIR__.'/lib/jssdk.php');
            throw new AllPayException('file not found: jssdk.php');
        }
        require_once __DIR__.'/lib/jssdk.php';
        $jssdk = new JSSDK( WxPayConfig::$APPID, WxPayConfig::$APPSECRET,$jsApiParameters['nonceStr'],$jsApiParameters['timeStamp'] );

        //config 验证签名
        $signPackage = $jssdk->GetSignPackage();
        $jsApiParameters['signature'] = $signPackage['signature'];

        //========= 使用jsapi调起支付============
        $jsApiParameters['return_url'] = $return_url."?out_trade_no={$out_trade_no}&total_fee={$total_fee}"; //支付完成跳转url

        //调用内置js支付
        !empty($isUrl) OR require_once(__DIR__.'/jsapi_js.php');

        return $jsApiParameters;
    }

    /**
     * H5支付 非微信内置浏览器
     * @param        $order_id
     * @param        $out_trade_no
     * @param        $total_fee
     * @param        $notify_url
     * @param        $return_url
     * @param string $isUrl
     *
     * @return string
     */
    function WapPay( $order_id, $out_trade_no, $total_fee, $notify_url, $return_url, $isUrl='' )
    {
        //设置支付日志
        require_once (__DIR__.'/lib/WxPay.JsApiPay.php');

        //异步通知地址
        $input = new WxPayUnifiedOrder();
        $input->SetBody("H5支付");
        $input->SetAttach("微信订单:{$order_id}");
        $input->SetOut_trade_no($out_trade_no);
        $input->SetTotal_fee($total_fee*100);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag("WAP");
        $input->SetNotify_url($notify_url);
        $input->SetTrade_type("MWEB");
        $input->SetProduct_id($out_trade_no);
        $order = WxPayApi::unifiedOrder($input);
        //var_dump($order);
        //echo $order['mweb_url'];//支付链接
        exit("微信暂不支付持此类支付方式，敬请期待！");
    }

    /**
     * TODO:PC扫码支付
     * @param $data
     *
     * @return string url
     */
    function NactivePay( $order_id, $out_trade_no, $total_fee, $notify_url, $return_url, $isUrl='' )
    {
        //设置支付日志
        require_once (__DIR__."/lib/WxPay.NativePay.php");
        $notify = new NativePay();

        //模式一
        /**
         * 流程：
         * 1、组装包含支付信息的url，生成二维码
         * 2、用户扫描二维码，进行支付
         * 3、确定支付之后，微信服务器会回调预先配置的回调地址，在【微信开放平台-微信支付-支付配置】中进行配置
         * 4、在接到回调通知之后，用户进行统一下单支付，并返回支付信息以完成支付（见：native_notify.php）
         * 5、支付完成之后，微信服务器会通知支付成功
         * 6、在支付成功通知中需要查单确认是否真正支付成功（见：notify.php）
         */
        //$url1 = $notify->GetPrePayUrl("123456789");

        //模式二
        /**
         * 流程：
         * 1、调用统一下单，取得code_url，生成二维码
         * 2、用户扫描二维码，进行支付
         * 3、支付完成之后，微信服务器会通知支付成功
         * 4、在支付成功通知中需要查单确认是否真正支付成功（见：notify.php）
         */
        //异步通知地址
        $input = new WxPayUnifiedOrder();
        $input->SetBody("扫码支付");
        $input->SetAttach("微信订单:{$order_id}");
        $input->SetOut_trade_no($out_trade_no.'|NP'.time());
        $input->SetTotal_fee($total_fee*100);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag("PC");
        $input->SetNotify_url($notify_url);
        $input->SetTrade_type("NATIVE");
        $input->SetProduct_id($out_trade_no);
        $result = $notify->GetPayUrl($input);
        $url = $result["code_url"];
        if ( empty($isUrl) )
        {
            //生成图片
            require_once (__DIR__.'/phpqrcode/phpqrcode.php');
            QRcode::png( $url, false, 'QR_ECLEVEL_L', 8.3, 0);
        }else{
            $url = urldecode($url);
        }

        return $url;
    }


    /**
     * 异步通知成功判断
     * @return mixed
     */
    function notify()
    {
        require_once(__DIR__.'/lib/notify.php');
        $notify = new PayNotifyCallBack();

        //商户订单号 = 支付日志表parent_log_id
        $notify_data = $notify->FromXml($GLOBALS['HTTP_RAW_POST_DATA']);
        $data['out_trade_no'] = $out_trade_no = strpos($notify_data['out_trade_no'],'|')
            ?substr($notify_data['out_trade_no'],0,strpos($notify_data['out_trade_no'],'|'))
            :$notify_data['out_trade_no'];
        $data['total_fee'] = $notify_data['total_fee']/100;

        if ( $notify->Handle(false) ) {

            //支付验证成功,保存成功的通知
            $result['result_code']  = 0;
            $result['msg']          = $notify->ToXml();
            $result['out_trade_no'] = empty($data['out_trade_no']) ? '' : $data['out_trade_no'];//商户订单号
            $result['wx_out_trade_no'] = empty($notify_data['out_trade_no']) ? '' : $notify_data['out_trade_no'];//真实商户订单号
            $result['total_fee']    = empty($data['total_fee']) ? '' : $data['total_fee'];//交易金额

            return $result;
        }

        //验证失败
        throw new AllPayException("verify fail");//<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>
    }
}