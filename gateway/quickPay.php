<?php

/**
 * 快捷支付
 * User: 刘孝全
 * Date: 2016/5/11
 * Time: 13:31
 */
class quickPay extends Gateway
{
    //------------------------ 快捷支付 -------------------------
    function pay( $data ) //$order_id=0, $pay_id=0, $card_number=0, $vcode=''
    {
        if (empty($data['auth_code'])) { throw new AllPayException('验证码不能为空'); }
        if (empty($data['card_num'])) { throw new AllPayException('银行卡号不能为空'); }

        //失效订单，结束交易
        if (empty($data['out_trade_no'])){ throw new AllPayException('失效订单'); }

        $payName = (22==$data['pay_id'] )?'creditPay':'debitPay';
        CY_log::add("{$payName} do ...");

        if ( !empty($data['out_trade_no']) && !empty($data['total_fee']) && !empty($data['auth_code']) && !empty($data['card_num']) &&  is_array($data) )
        {
            //查询订单金额
            $total_fee	= $data['total_fee']*100;

            //查询银行卡号
            $param['origQid']               = ''; //原交易返回的qid, 从数据库中获取
            $param['orderAmount']           = $total_fee;        //交易金额
            $param['orderNumber']           = $data['out_trade_no'].'00'; //订单号，必须唯一(不能与原交易相同)
            $param['orderTime']             = date('YmdHis');   //交易时间, YYYYmmhhddHHMMSS
            $param['cardNumber']			= $data['card_num'];
            $param['customerIp']            = $_SERVER['REMOTE_ADDR'];  //用户IP
            $param['frontEndUrl']           = "";    //前台回调URL, 后台交易可为空
            $param['merCode']				= "";
            $param['acqCode']				= "";
            $param['smsCode']				= $data['auth_code'];

            //自定义配置快捷支付参数 1储蓄卡 2信用卡
            require_once(__DIR__.'/quickpay/quickpay.config.php');
            if (22 == $data['pay_id']){//改为信用卡快捷支付参数
                quickpay_conf::$security_key         = 'JIOERUWIO4RUI4RJQOJRX,OQWIRX9QRU8YRJ';
                quickpay_conf::$pay_params['merId']  = '104110548991228';
                quickpay_conf::$auth_params['merId'] = '104110548991228';
            }

            //加载快捷支付文件
            require_once(__DIR__."/quickpay/quickpay.service.php");
            $param['transType']     = quickpay_conf::CONSUME;
            $param['orderCurrency'] = quickpay_conf::CURRENCY_CNY;  //交易币种
            $param['backEndUrl']    = isset($this->config['notify_url'])?$this->config['notify_url']:'http://www.yaofang.cn/a/pay/notify/'.$data['pay_id'];//后台回调URL

            //提交订单数据
            $pay_service = new quickpay_service($param, quickpay_conf::BACK_PAY);
            $ret = $pay_service->post();

            //同步返回（表示服务器已收到后台接口请求）, 处理成功与否以后台通知为准；或使用主动查询
            $response = new quickpay_service($ret, quickpay_conf::RESPONSE);

            CY_log::add($response);
            if ($response->get('respCode') == quickpay_service::RESP_SUCCESS)
            {
                //成功
                for($i=0;$i<5;$i++)
                {
                    $query['transType']				= quickpay_conf::CONSUME;
                    $query['orderNumber']			= $param['orderNumber'];
                    $query['orderTime']				= $param['orderTime'];

                    //交易查询
                    $pay_service = new quickpay_service($query, quickpay_conf::PAY_QUERY);
                    $response = new quickpay_service($pay_service->post(), quickpay_conf::RESPONSE);
                    $r = $response->get_args();
                    CY_log::add("{$payName}查询支付状态返回数据：".json_encode($r));
                    if($r['queryResult']==0)
                    {
                        //支付成功
                        CY_log::add("{$payName}订单{$param['orderNumber']}支付成功");

                        $url = "{$data['return_url']}?trade_status=success&out_trade_no={$data['out_trade_no']}&total_fee={$data['total_fee']}&order_id={$data['order_id']}";
                        CY_log::add($url);
                        if ( empty($data['getPayUrl']) ){
                            echo "<script type='text/javascript'>window.location.href='{$url}';</script>";
                            exit;
                        }else{
                            return $url;
                        }
                    } elseif($r['queryResult']==3) {
                        CY_log::add("Trade not Exists: {$payName}订单{$param['orderNumber']}");
                    }
                    sleep(2);
                }
                $msg = $this->getErrorMessage($r['respCode']);
                throw new AllPayException("{$payName}订单{$param['orderNumber']}正在处理:{$msg}");
            } else {
                $msg = $this->getErrorMessage($response->get('respCode'));
                $url = "{$data['return_url']}?trade_status=fail&msg={$msg}";
                
//                 echo "<script type='text/javascript'>window.location.href='{$url}';</script>";
            }
            //错误
            throw new AllPayException("{$response->get('respMsg')}");
        }
        throw new AllPayException('The prepay parameter is missing');
    }

    /**
     * 快捷支付，发送短信验证码
     * @param string $card_no           银行卡号
     * @param string $out_trade_no      预付订单id
     * @param string $phone             手机
     * @param string $pay_id            支付类型
     *
     * @return string                   验证码
     */
    function get_sms( $card_no='', $out_trade_no='',$total_fee='', $phone='', $pay_id='' )
    {
        //过滤失效订单
        if (empty($out_trade_no)){
            //失效订单，结束交易
            return $msg = '失效订单';
        }

        //查询订单金额
        if(empty($total_fee)){
            return $msg =  '订单金额有误';
        }
        $param['orderNumber']			  = $out_trade_no.'00';
        $param['orderAmount']			  = $total_fee*100;
        $param['cardNumber']			  = $card_no;
        $param['phoneNumber']			  = $phone;
        $param['acqCode']				  = "";
        $param['merAbbr']				  = "药房网";

        //自定义配置快捷支付参数 1储蓄卡 2信用卡
        require_once(__DIR__.'/quickpay/quickpay.config.php');
        if (22 == $pay_id){//信用卡快捷支付参数
            quickpay_conf::$security_key         = 'JIOERUWIO4RUI4RJQOJRX,OQWIRX9QRU8YRJ';
            quickpay_conf::$pay_params['merId']  = '104110548991228';
            quickpay_conf::$auth_params['merId'] = '104110548991228';
        }

        //加载快捷支付文件
        require_once(__DIR__."/quickpay/quickpay.service.php");
        $param['orderCurrency']			  = quickpay_conf::CURRENCY_CNY;
        //提交
        $pay_service = new quickpay_service($param, quickpay_conf::SMS_SEND);
        $ret = $pay_service->post();
        //同步返回（表示服务器已收到后台接口请求）, 处理成功与否以后台通知为准；或使用主动查询
        $response = new quickpay_service($ret, quickpay_conf::RESPONSE);
        if ($response->get('respCode') != quickpay_service::RESP_SUCCESS) { //错误处理
            echo $response->get('respMsg');
            $err = sprintf("Error: %d => %s", $response->get('respCode'), $response->get('respMsg'));
            CY_log::add("SMS error:{$err}");
        }

        //后续处理
        $arr_ret = $response->get_args();
        if($arr_ret['respCode'] == 00){
            echo '短信验证码已发送，请注意查收！';
        }else{
            echo $arr_ret['respMsg'];
        }
    }

    /**
     * 异步通知
     * @param string $pay_id
     *
     * @throws Exception
     */
    function notify( $pay_id='' )
    {
        $payName = payWay( $pay_id );

        //回写日志
        if (empty($_POST)) {
            CY_log::add("{$payName}回调 没有接收到支付POST通知返回数据");
            die('fail');
        }
        CY_log::add($_POST);

        //自定义配置快捷支付参数
        if (is_file(__DIR__.'/quickpay/quickpay.config.php') && is_file(__DIR__."/quickpay/quickpay.service.php")) {
            require_once(__DIR__.'/quickpay/quickpay.config.php');//快捷支付配置文件
            require_once(__DIR__."/quickpay/quickpay.service.php");//快捷支付接口
        }else{
            PayLog::log(__DIR__.'/quickpay/ 目录下的文件加载失败');
        }

        //修改 信用卡快捷支付所需参数 默认是储蓄卡
        if (22 == $pay_id){
            quickpay_conf::$security_key         = 'JIOERUWIO4RUI4RJQOJRX,OQWIRX9QRU8YRJ';
            quickpay_conf::$pay_params['merId']  = '104110548991228';
            quickpay_conf::$auth_params['merId'] = '104110548991228';
        }
        try{
            $response = new quickpay_service($_POST, quickpay_conf::RESPONSE);
            $arr_ret = $response->get_args();
            CY_log::add('订单号(快捷支付id并接了00):'.$arr_ret['orderNumber']);
        }catch(Exception $e){
            CY_log::add('获取响应通知知败:'.$e->getMessage());
            die();
        }

        if ($arr_ret['respCode'] == 00)
        {
            //更新数据库，将交易状态设置为已付款 //注意保存qid，以便调用后台接口进行退货/消费撤销
            if ($arr_ret['queryResult'] == 0) {
                //支付成功
                $result['out_trade_no'] = substr($arr_ret['orderNumber'], 0, -2);
                $result['total_fee'] = $arr_ret['settleAmount'] / 100;
                $result['msg'] = 'SUCCESS';

                return $result;
            }else if($arr_ret['queryResult'] == 2) {
                CY_log::add('交易处理中,继续调取查询接口...');
            }
        } else { //支付失败
            //获取错误信息
            $message = $this->getErrorMessage($arr_ret['respCode']);
            $update_id = $this->update_pay_status($arr_ret['orderNumber'], $message);
            CY_log::add("支付错误:online_pay_log_res:{$update_id}");
        }
        CY_log::add("{$payName}回调 end");
    }

    /**
     * 同步通知
     * @return mixed
     */
    function return_url()
    {
        //其它支付(除支付宝外)
        if (!empty($_GET['trade_status']) && (strcasecmp($_GET['trade_status'],'success') == 0))
        {
            $data['result_code']    =  0;
            $data['out_trade_no']   =  isset($_GET['out_trade_no'])?$_GET['out_trade_no']:'';
            $data['total_fee']      =  isset($_GET['total_fee'])?$_GET['total_fee']:'';
        }else{
            $data['msg']            =  isset($_GET['msg'])?$_GET['msg']:'订单获取失败';
        }

        return $data;
    }

    //快捷支付通知
    private function getErrorMessage($code)
    {
        switch ($code) {
            case 01:
                $message = '支付失败。详情请咨询您的发卡行';
                break;
            case 02:
                $message = '您输入的卡号无效，请确认后输入';
                break;
            case 03:
                $message = '支付失败，您的发卡银行不支持该商户，请更换其他银行卡';
                break;
            case 06:
                $message = '您的卡已经过期，请使用其他卡支付';
                break;
            case 11:
                $message = '您卡上的余额不足';
                break;
            case 14:
                $message = '您的卡已过期或者是您输入的有效期不正确，支付失败';
                break;
            case 15:
                $message = '您输入的银行卡密码有误，支付失败';
                break;
            case 18:
                $message = '交易未通过，请尝试使用其他银联卡支付或联系95516';
                break;
            case 20:
                $message = '您输入的转入卡卡号有误，支付失败';
                break;
            case 21:
                $message = '您输入的验证信息有误，支付失败';
                break;
            case 25:
                $message = '查找原始交易失败';
                break;
            case 30:
                $message = '报文错误';
                break;
            case 31:
                $message = '交易受限';
                break;
            case 32:
                $message = '系统维护中';
                break;
            case 36:
                $message = '交易金额超限，支付失败';
                break;
            case 37:
                $message = '原始金额错误';
                break;
            case 39:
                $message = '您已连续多次输入错误密码';
                break;
            case 40:
                $message = '您的银行卡暂不支持在线支付业务，请向您的银行咨询如何加办银联在线支付';
                break;
            case 41:
                $message = '您的银行不支持认证支付，请选择快捷支付';
                break;
            case 42:
                $message = '您的银行不支持小额支付，请选择快捷支付';
                break;
            case 43:
                $message = '您的银行不支持认证支付';
                break;
            case 56:
                $message = '您的银行卡所能进行的交易受限，详细请致电发卡行进行查询';
                break;
            case 57:
                $message = '该银行卡未开通银联在线支付业务';
                break;
            case 60:
                $message = '银行卡未开通认证支付';
                break;
            case 61:
                $message = '银行卡开通状态查询次数过多';
                break;
            case 71:
                $message = '交易无效，无法完成，支付失败';
                break;
            case 72:
                $message = '无此交易';
                break;
            case 73:
                $message = '扣款成功但交易超时';
                break;
            case 74:
                $message = '对不起，该操作只能在交易当日进行';
                break;
            case 80:
                $message = '内部错误';
                break;
            case 81:
                $message = '可疑报文';
                break;
            case 82:
                $message = '验签失败';
                break;
            case 83:
                $message = '超时';
                break;
            case 84:
                $message = '订单不存在';
                break;
            case 85:
                $message = '不支持短信发送';
                break;
            case 86:
                $message = '短信验证码错误';
                break;
            case 87:
                $message = '您的短信发送过于频繁，请稍候再试';
                break;
            case 88:
                $message = '您的短信发送累计过于频繁，请在x分钟后重试';
                break;
            case 89:
                $message = '对不起，短信发送失败，请稍候再试';
                break;
            case 90:
                $message = '请您登录工商银行网上银行或拨打95588进行后续认证操作';
                break;
            case 93:
                $message = '请致电您的银行以确定您的个人客户基本信息中的相关信息设置正确';
                break;
            case 94:
                $message = '重复交易';
                break;
            case 95:
                $message = '您尚未在邮储银行网点柜面或个人网银签约加办银联无卡支付业务，请去柜面或网银开通';
                break;
            case 97:
                $message = '请致电您的银行以确定您的用户信息是否设置正确，并咨询是否已经开办银联在线支付';
                break;
            default:
                $message = '交易失败';
                break;
        }
        return $message;
    }
}