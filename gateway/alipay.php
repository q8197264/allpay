<?php
/**
 * 支付宝支付
 * User: 刘孝全
 * Date: 2016/5/10
 * Time: 15:38
 */
class alipay extends Gateway
{
    /**
     * TODO:支付宝 支付
     * @param int $order_id
     *
     * @return mixed
     */
    function pay( $data )
    {
        //订单来源平台 0:wap 1:PC
        $referer = empty($data['referer'])?'':1;
        $isUrl = empty($data['getPayUrl'])?'':1;
        if ( !empty($data['out_trade_no']) && is_array($data) )
        {
            if ( is_file(__DIR__.'/alipay/AlipayAdaptor.php') ){
                require(__DIR__.'/alipay/AlipayAdaptor.php');

                //加载自定义支付配置

                $config = !empty($this->config) ? $this->config : array();
                $alipayAdaptor = new AlipayAdaptor( $config );

//                if ( !empty($data['bankcode']) ){ //银联支付
//                    $msg = "调起银联支付: {$data['bankcode']}";
//                    $url = $alipayAdaptor->alibank( $data['order_id'], $data['out_trade_no'], $data['total_fee'], $data['bankcode'], $referer, $isUrl );
//                }else{//支付宝支付
                    $msg = '调起支付宝：';
                    $url = $alipayAdaptor->alipay( $data['order_id'], $data['out_trade_no'], $data['total_fee'], $referer, $isUrl );
//                }
                if (1==$isUrl){
                    CY_log::add("$msg <<<".$url);
                }else{
                    echo $url;//支付表单提交
                    CY_log::add("$msg <<<".$url);
                }
                //echo "<p><a href='{$url}' target='_blank'>pay</a></p>";
                return $url;
            }else{
                throw new AllPayException(__DIR__."/alipay/alipayAPI.php 文件加载失败！");
            }
        }else{
            throw new AllPayException('alipay the params is illegal: '.json_encode($data));
        }
    }

    //-------- TODO:通知回写处理 ----
    /**
     * 支付宝 异步通知
     * @param int $pay_id  支付方式
     */
    function notify()
    {
//        $_POST = array(
////            'discount'            => "0.00",
//            'payment_type'        => "1",
//            'subject'             => "订单12824062,12824063,12824064",
//            'trade_no'            => "2016052421001004520258066043",
//            'buyer_email'         => "674812039@qq.com",
//            'gmt_create'          => "2016-05-24 14:48:52",
//            'notify_type'         => "trade_status_sync",
//            'quantity'            => "1",
//            'out_trade_no'        => "2493900",
//            'seller_id'           => "2088201895953532",
//            'notify_time'         => "2016-05-24 14:49:01",
//            'body'                => "药房网订单支付",
//            'trade_status'        => "TRADE_SUCCESS",
//            'is_total_fee_adjust' => "N",
//            'total_fee'           => "0.01",
//            'gmt_payment'         => "2016-05-24 14:49:00",
//            'seller_email'        => "001yaofang@yaofang.cn",
//            'price'               => "0.01",
//            'buyer_id'            => "2088002857440523",
//            'notify_id'           => "82edba4471cc46a6fcd15ccf19a8fc7k0i",
//            'use_coupon'          => "N",
//            'sign_type'           => "MD5",
//            'sign'                => "46823e72d4c60318c6be2a9582ae385c"
//        );

        if ( is_file(__DIR__.'/alipay/AlipayAdaptor.php') )
        {
            require(__DIR__ . '/alipay/AlipayAdaptor.php');

            //加载自定义支付配置
            $config = !empty($this->config) ? $this->config : '';
            $alipayAdaptor = new AlipayAdaptor( $config );

            //验证
            if( @$alipayAdaptor->verifyNotify() )
            {
                //支付宝交易号
                //$trade_no = $_POST['trade_no'];

                //交易状态
                $trade_status = empty($_POST['trade_status'])?'':$_POST['trade_status'];
                if( $trade_status == 'TRADE_FINISHED' OR $trade_status == 'TRADE_SUCCESS') {
                    //判断该笔订单是否在商户网站中已经做过处理
                    //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                    //请务必判断请求时的total_fee、seller_id与通知时获取的total_fee、seller_id为一致的
                    //如果有做过处理，不执行商户的业务程序
                    $result['result_code'] = 0;
                    $result['msg'] = 'SUCCESS';
                    $result['out_trade_no'] = empty($_POST['out_trade_no'])?'':$_POST['out_trade_no'];//商户订单号
                    $result['total_fee'] = empty($_POST['total_fee'])?'':$_POST['total_fee'];//交易金额
                    //注意：
                    //退款日期超过可退款期限后（如三个月可退款），支付宝系统发送该交易状态通知

                    //调试用，写文本函数记录程序运行情况是否正常
                    CY_log::add('alipayNotify:验证通过');
                    return $result;
                }else{
                    //验证失败
                    $msg = 'alipayNotify:交易失败';
                }
            }else {
                //验证失败
                $msg = 'alipayNotify:验证失败';
            }
        }else{
            $msg = __DIR__.'/alipay/alipayAPI.php 文件加载失败！';
        }
        CY_log::add( $_POST );
        throw new AllPayException( $msg );
    }

    /**
     * 同步跳转通知页面
     * @return mixed
     */
    function return_url()
    {
        if ( is_file(__DIR__.'/alipay/AlipayAdaptor.php') ) {
            require(__DIR__ . '/alipay/AlipayAdaptor.php');

            //加载自定义支付配置
            $config = !empty($this->config) ? $this->config : '';
            $alipayAdaptor = new AlipayAdaptor( $config );
            if( $alipayAdaptor->verifyReturn() )
            {//验证成功

                //商户订单号
                $out_trade_no = $_GET['out_trade_no'];

                //支付宝交易号
                //$trade_no = $_GET['trade_no'];

                //交易状态
                $trade_status = $_GET['trade_status'];

                if($trade_status == 'TRADE_FINISHED' || $trade_status == 'TRADE_SUCCESS') {
                    //判断该笔订单是否在商户网站中已经做过处理
                    //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                    //如果有做过处理，不执行商户的业务程序
                    //计算得出通知验证结果
                    $result['result_code'] = 0;
                    $result['msg'] = 'SUCCESS';
                    $result['out_trade_no'] = $out_trade_no;
                    $result['total_fee'] = $_GET['total_fee'];

                    CY_log::add('alipayReturn:成功通知');
                    return $result;
                } else {
                    CY_log::add( $_SERVER['SERVER_NAME'].urldecode($_SERVER['REQUEST_URI']) );
                }
            }else{
                //验证失败
                CY_log::add( $_SERVER['SERVER_NAME'].urldecode($_SERVER['REQUEST_URI']) );

                //如要调试，请看alipay_notify.php页面的verifyReturn函数
                throw new AllPayException('alipayReturn:验证失败');
            }

        }else{
            CY_log::add( $_GET );
            throw new AllPayException(__DIR__.'/alipay/alipayAPI.php 文件加载失败！');
        }
    }
}