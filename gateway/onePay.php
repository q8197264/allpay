<?php

/**
 * 一卡通支付
 * User: 刘孝全-改
 * Date: 2016/5/11
 * Time: 14:11
 */
class onePay extends Gateway
{
    /**
     * 一卡通支付调起
     * @param string $order_id      订单支付ID
     *
     * @return string
     */
    function pay( $data )
    {
        $referer = empty($data['referer'])?'':1;
        $isUrl = empty($data['getPayUrl'])?'':1;
        if ( empty($data['out_trade_no']) || !is_array($data) || empty($data['total_fee']) )
        {
            throw new AllPayException('alipay the params is illegal: '.json_encode($data));
        }

        //$mchntid="800010000001";//测试账号
        //$url="https://test.hzt360.com/HZTPayPlatform/transaction/paymentIndex";//测试地址
        //$key="f675933a-2d13-4415-bf87-232fcd099e25";//测试的密匙
        $mchntid = "800011305005";   //正式账号商户编号，不为空
        $url = "https://www.hzt360.com/HZTPayPlatform/transaction/paymentIndex";//正式地址
        $key = "9722b55b-7ec3-491c-bfd3-8001f8beccca";//正式的密匙
        $orderId = $data['out_trade_no'];              //订单号，不为空
        $txnAmt = $data['total_fee'] * 100;           //订单交易金额，不为空
        $currencyId = "156";         //订单交易币种，不为空 固定156
        $transTime = date( 'Ymdhis' ); //订单交易时间，不为空，格式为：年[4 位]月[2 位]日[2 位]时[2 位]分[2 位]秒[2 位] 例如：20110328020101
        $txnType = "0001";           //订单交易类型，不为空，固定值0001
        $version = "20110328";       //支付接入版本号,不为空，固定值：20110328
        $pageUrl = $this->config['return_url'] . '?out_trade_no=' . $orderId;//页面交易接收Url，不可空	页面接收应答地址，用于引导使用者返回支付后的商户网站页面。
        $bgUrl = $this->config['notify_url']; //后台交易接收Url，不可空	后台接收应答地址，用于商户记录交易信息和处理，对于使用者是不可见的。
        $productName = ""; //商品名称	可空
        $productNum = "";  //商品数量 可空
        $productDesc = ""; //商品描述	可空
        $reserved = "http://www.yaofang.cn";    //商户保留域	可空	商户通过此字段向汇智通发送信息，汇智通将依原样填充返回给商户。

        $sign = "mchntid={$mchntid}&orderId={$orderId}&txnAmt={$txnAmt}&currencyId={$currencyId}&transTime={$transTime}&txnType={$txnType}&version={$version}&pageUrl={$pageUrl}&bgUrl={$bgUrl}&productName={$productName}&productNum={$productNum}&productDesc={$productDesc}&reserved={$reserved}&key={$key}";
        $signMsg = strtoupper( MD5( $sign ) );     //数字签名	32位 不可空	以上所有非空参数及其值与密钥组合，经MD5 加密生成并转化为大写的32 位字符串。
        //对于所有参数及对应值，按照如上顺序和如下规则组成字符串，其中key为密钥：
        //       参数1={参数1}&参数2={参数2}&……&参数n={参数n}&key={key}
        //      然后进行32位算法的MD5 加密后，将结果转化为大写。
        if ( empty($this->config['getPayUrl']) ) {
            $string = "<form  id='onePay' action='" . $url . "'  method='get'>\n";
            $string .= "<input type='hidden' name='mchntid'           value='" . $mchntid . "'/>\n";
            $string .= "<input type='hidden' name='orderId'           value='" . $orderId . "'/>\n";
            $string .= "<input type='hidden' name='txnAmt'            value='" . $txnAmt . "'/>\n";
            $string .= "<input type='hidden' name='currencyId'        value='" . $currencyId . "'/>\n";
            $string .= "<input type='hidden' name='transTime'         value='" . $transTime . "'/>\n";
            $string .= "<input type='hidden' name='txnType'           value='" . $txnType . "'/>\n";
            $string .= "<input type='hidden' name='version'           value='" . $version . "'/>\n";
            $string .= "<input type='hidden' name='pageUrl'           value='" . $pageUrl . "'/>\n";
            $string .= "<input type='hidden' name='bgUrl'             value='" . $bgUrl . "'/>\n";
            $string .= "<input type='hidden' name='productName'       value='" . $productName . "'/>\n";
            $string .= "<input type='hidden' name='productNum'        value='" . $productNum . "'/>\n";
            $string .= "<input type='hidden' name='productDesc'       value='" . $productDesc . "'/>\n";
            $string .= "<input type='hidden' name='reserved'          value='" . $reserved . "'/>\n";
            $string .= "<input type='hidden' name='signMsg'           value='" . $signMsg . "'/>\n";
            //$string.="<input type='submit' value='' style='height:44px;widht:94px;cursor:pointer;background:url(http://www.yaofang.cn/themes/newbj/images/pay17.gif)'/>\n";
            $auto_sub = $string . "</form><script type='text/javascript'>document.forms['onePay'].submit();</script>";
        } else {
            parse_str( $sign, $params );
            unset($params['key']);
            $params['signMsg'] = $signMsg;
            $params = http_build_query( $params );
            $auto_sub = $url . '?' . $params;
        }

        echo $auto_sub;
    }

    /**
     * 一卡通支付回写
     * @param string $pay_id
     */
    function notify()
    {
        if ( empty($_REQUEST) ) {throw new AllPayException('notify params is empty!');}
        $mchntid = $_REQUEST['mchntid'];   //商户编号，不为空
        $out_trade_no = $_REQUEST['orderId'];              //订单号，不为空
        $total_fee = $_REQUEST['txnAmt'];           //订单交易金额，不为空
        $currencyId = $_REQUEST['currencyId'];         //订单交易币种，不为空 固定156
        $txnType = $_REQUEST['txnType'];           //订单交易类型，不为空，固定值0001
        $txnDate = $_REQUEST['txnDate'];
        $txnTime = $_REQUEST['txnTime'];
        $reserved = $_REQUEST['reserved'];    //商户保留域	可空	商户通过此字段向汇智通发送信息，汇智通将依原样填充返回给商户。
        $responseCode = $_REQUEST['responseCode'];//该字段返回值如果是0,成功，非0为失败
        $responseMsg = $_REQUEST['responseMsg'];
        $signMsg = $_REQUEST['signMsg'];
        $key = "9722b55b-7ec3-491c-bfd3-8001f8beccca";//密匙
        $sign = "mchntid={$mchntid}&orderId={$out_trade_no}&txnAmt={$total_fee}&currencyId={$currencyId}&txnType={$txnType}&txnDate={$txnDate}&txnTime={$txnTime}&reserved={$reserved}&responseCode={$responseCode}&responseMsg={$responseMsg}&key={$key}";
        $signMsg_new = strtoupper( MD5( $sign ) );
        if ( $signMsg == $signMsg_new )//验证返回的数据的合法性
        {
            if ( $responseCode == 0 )
            {//回写成功
                $result['result_code'] = 0;
                $result['msg'] = $responseCode;
                $result['out_trade_no'] = empty($out_trade_no)?'':$out_trade_no;//商户订单号
                $result['total_fee'] = empty($total_fee)?'':$total_fee;//交易金额
                //注意：
                //退款日期超过可退款期限后（如三个月可退款），支付宝系统发送该交易状态通知

                //调试用，写文本函数记录程序运行情况是否正常
                CY_log::add('onePayNotify:验证通过');

                return $result;
            } else {
                $msg = '一卡通支付失败,IP:' . @$_SERVER['REMOTE_ADDR'];
            }
        } else {
            //搜集一卡通支付错误的信息写入log日志
            $msg =  'onePayNotify: fail; IP:' . @$_SERVER['REMOTE_ADDR'];
        }
        throw new AllPayException( $msg );
    }

    function return_url()
    {

        CY_log::add( $_GET );
        //其它支付(除支付宝外)
//        if (!empty($_GET) && $out_trade_no = $_GET['out_trade_no']){
//            $data = $this->get_trade_orders( $out_trade_no );
//            $data['msg']        = isset($data['msg'])?$data['msg']:(empty($msg)?'':$msg);
//        }else{
//            $msg='获取订单失败';
//        }
//        $data['msg']        = isset($data['msg'])?$data['msg']:$msg;
//
//        return $data;
    }
}