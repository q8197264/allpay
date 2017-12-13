<?php
/**
 * 创建支付链接
 * User: 刘孝全
 * Date: 2016/3/3
 * Time: 17:39
 */
require_once(__DIR__."/lib/alipay_submit.class.php");
class AlipayAdaptor
{
    protected static $_config = '';

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
    function __construct( $config=array() )
    {
        require(__DIR__.'/alipay.config.php');
        if (!empty($config)){
            foreach ( $alipay_config as $k=>$v ){
                $alipay_config[$k] = array_key_exists($k,$config)?$config[$k]:$v;
            }
        }
        self::$_config = $alipay_config;
        if ( empty(self::$_config) ) {
            throw new AllPayException('error: Configuration parameter is not passed!! config='.json_encode($config));
        }
    }

    //------------------------------- TODO: 支付宝支付 PC/WAP-------------------
    /**
     * 调起支付，弹出支付宝页面: WAP支付 PC扫码支付
     * @param char     $order_id         订单
     * @param int      $out_trade_no     支付单号
     * @param float    $total_fee        支付金额
     * @param int      $referer          来源 PC:1  WAP:0
     * @param bool     $isUrl           是否返回支付链接 : 0表单直接跳转 1返回支付url
     *
     * @return 提交表单HTML文本
     */
    public function alipay( $order_id, $out_trade_no, $total_fee, $referer=0, $isUrl='' )
    {
        //构造要请求的参数数组，无需改动
        $parameter = array(
            "service"           => empty($referer) ? "alipay.wap.create.direct.pay.by.user" : "create_direct_pay_by_user",// 产品类型?WAP支付(备用):扫码
            "partner"           => self::$_config['partner'],
            "seller_id"         => self::$_config['seller_id'],
            "payment_type"      => self::$_config['payment_type'],
            "notify_url"        => self::$_config['notify_url'],
            "return_url"        => self::$_config['return_url'],
            "anti_phishing_key" => self::$_config['anti_phishing_key'],
            "exter_invoke_ip"   => self::$_config['exter_invoke_ip'],
            "out_trade_no"      => $out_trade_no,
            "subject"           => "订单{$order_id}",
            "total_fee"         => $total_fee,
            "body"              => '药房网订单支付',
            "_input_charset"    => trim( strtolower( self::$_config['input_charset'] ) ),
            //'paymethod'		=> 'directPay',	//余额支付
            'show_url'		=> strtolower(substr($_SERVER['SERVER_PROTOCOL'],0,strpos($_SERVER['SERVER_PROTOCOL'],'/')))."://{$_SERVER['SERVER_NAME']}",
            //其他业务参数根据在线开发文档，添加参数.文档地址:https://doc.open.alipay.com/doc2/detail.htm?spm=a219a.7629140.0.0.kiX33I&treeId=62&articleId=103740&docType=1
            //如"参数名"=>"参数值"
        );
        if (empty($referer))
        {
            $parameter['app_pay']  = "Y";//启用此参数能唤起钱包APP支付宝
        }

        //建立请求
        $alipaySubmit = new AlipaySubmit(self::$_config);
        $html_text = empty($isUrl)?$alipaySubmit->buildRequestForm($parameter,"get", "确认"):$alipaySubmit->buildRequestUrl($parameter);

        //$html_text = preg_replace('/<script>.*<\/script>|none/','',$html_text);
        return $html_text;
    }

    //-------------------------------- TODO:支付宝银联支付 PC-----------------------
    /**
     * 创建网银支付表单
     * @param char     $order_id         订单
     * @param int      $out_trade_no     支付单号
     * @param float    $total_fee        支付金额
     * @param int      $referer          来源 PC:1  WAP:0
     * @param bool     $isUrl           是否返回支付链接 : 0表单直接跳转 1返回支付url
     *
     * @return 提交表单HTML文本
     */
    public function alibank( $order_id=0, $out_trade_no=0, $order_amount=0, $defaultbank='', $referer='', $isUrl )
    {
        $parameter = array(
            "service"        => empty($referer) ? "alipay.wap.create.direct.pay.by.user" : "create_direct_pay_by_user",
            "partner"        => trim( self::$_config['partner'] ),
            "seller_email"   => self::$_config['seller_email'],
            "payment_type"   => 1,//支付类型
            "notify_url"     => self::$_config['notify_url'],
            "return_url"     => self::$_config['return_url'],
            "out_trade_no"   => $out_trade_no,
            "subject"        => '订单',
            "total_fee"      => "$order_amount",
            "body"           => '药房网订单支付'.$order_id,
            "paymethod"      => 'bankPay',//默认支付方式
            "defaultbank"    => $defaultbank,//必填，如:招商银行 CMB 银行简码请参考接口技术文档
            //"show_url"	=> $show_url,//需以http://开头的完整路径，例如：http://www.商户网址.com/myorder.html
            //"anti_phishing_key"	=> $alipaySubmit->query_timestamp(),//防钓鱼时间戳,若要使用请调用类文件submit中的query_timestamp函数
            //"exter_invoke_ip"	=> $exter_invoke_ip,//客户端的IP地址,非局域网的外网IP地址，如：221.0.0.1
            "_input_charset" => trim( strtolower( self::$_config['input_charset'] ) )
        );
        //var_dump($parameter);exit;
        $alipaySubmit = new AlipaySubmit(self::$_config);
        $html_text = empty($isUrl)?$alipaySubmit->buildRequestForm($parameter,"post", "确认"):$alipaySubmit->buildRequestUrl($parameter);

        return $html_text;
    }


    //----------------------------- TODO: 通知验证 -------------------------
    /**
     * 异步通知验证
     * @return 验证结果
     */
    function verifyNotify()
    {
        require_once(__DIR__ . '/lib/alipay_notify.class.php');

        //计算得出通知验证结果
        $Notify = new AlipayNotify(self::$_config);
        return $Notify->verifyNotify();

    }

    /**
     * 同步通知验证
     * @return 验证结果
     */
    function verifyReturn()
    {
        require_once(__DIR__ . '/lib/alipay_notify.class.php');

        //计算得出通知验证结果
        $Notify = new AlipayNotify(self::$_config);
        return $Notify->verifyReturn();
    }
}