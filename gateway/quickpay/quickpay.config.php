<?php

/*
 * @file    quickpay_service.inc.php
 * @author  fengmin(felix021@gmail.com)
 * @date    2011-08-22
 * @version $Revision$
 *
 */

class quickpay_conf
{

    const VERIFY_HTTPS_CERT = false;

    static $timezone        = "Asia/Shanghai"; //时区
    static $sign_method     = "md5"; //摘要算法，目前仅支持md5 (2011-08-22)

    static $security_key    = "HSEUIRYW8RI8AWEYRIAUWEHRUAHEOURAH"; //商户密钥
    //static $security_key    = "88888888"; //商户密钥

    //支付请求预定义字段
    static $pay_params  = array(
        'version'       => '1.0.0',
        'charset'       => 'UTF-8', //UTF-8, GBK等
        'merId'         => '104110548991227', //商户填写
        'acqCode'       => '',  //收单机构填写
        'merCode'       => '',  //收单机构填写
        'merAbbr'       => '北京京卫元华医药科技有限公司',
    );
    //开通认证支付预定义字段
    static $auth_params  = array(
        'version'       => '1.0.0',
        'charset'       => 'UTF-8', //UTF-8, GBK等
        'merId'         => '104110548991227', //商户填写
        'acqCode'       => '',  //收单机构填写
    );

    //*线上环境
    static $front_pay_url   = "https://unionpaysecure.com/api/Activate.action";
    static $back_pay_url    = "https://unionpaysecure.com/api/AuthPay.action";
    static $query_url       = "https://query.unionpaysecure.com/api/Query.action";
    static $card_query_url  = "https://unionpaysecure.com/api/ActivationQuery.action";
    static $sms_query_url   = "https://unionpaysecure.com/api/Sms.action";
    static $refund_url		= "https://besvr.unionpaysecure.com/api/BSPay.action";



    //*/

    /*测试环境
    static $front_pay_url   = "http://58.246.226.99/UpopWeb/api/Activate.action";
    //static $back_pay_url    = "http://58.246.226.99/UpopWeb/api/BSPay.action";
	static $back_pay_url    = "http://58.246.226.99/UpopWeb/api/AuthPay.action";
    static $query_url       = "http://58.246.226.99/UpopWeb/api/Query.action";
	static $card_query_url  = "http://58.246.226.99/UpopWeb/api/ActivationQuery.action";
	static $sms_query_url   = "http://58.246.226.99/UpopWeb/api/Sms.action";
	static $open_query_url  = "http://58.246.226.99/UpopWeb/api/Activate.action";

    //*/

    /* 预上线环境
    static $front_pay_url   = "http://202.101.25.184/UpopWeb/api/Activate.action";
    static $back_pay_url    = "http://202.101.25.184/UpopWeb/api/AuthPay.action";
    static $query_url       = "http://202.101.25.184/UpopWeb/api/Query.action";
	static $card_query_url  = "http://202.101.25.184 /UpopWeb/api/ActivationQuery.action";
	static $sms_query_url   = "http://202.101.25.184/UpopWeb/api/Sms.action";
	static $refund_url		= "http://202.101.25.184/UpopWeb/api/BSPay.action";
	
	static $front_pay_url   = "http://www.epay.lxdns.com/UpopWeb/api/Activate.action";
    static $back_pay_url    = "http://www.epay.lxdns.com/UpopWeb/api/AuthPay.action";
    static $query_url       = "http://www.epay.lxdns.com/UpopWeb/api/Query.action";
	static $card_query_url  = "http://www.epay.lxdns.com/UpopWeb/api/ActivationQuery.action";
	static $sms_query_url   = "http://www.epay.lxdns.com/UpopWeb/api/Sms.action";
	static $refund_url		= "http://www.epay.lxdns.com/UpopWeb/api/BSPay.action";

	

    //*/

    const FRONT_PAY = 1;
    const BACK_PAY  = 2;
    const RESPONSE  = 3;
    const QUERY     = 4;
    const SMS_SEND  = 5;
    const PAY_QUERY = 6;

    const CONSUME                = "01";
    const CONSUME_VOID           = "31";
    const PRE_AUTH               = "02";
    const PRE_AUTH_VOID          = "32";
    const PRE_AUTH_COMPLETE      = "03";
    const PRE_AUTH_VOID_COMPLETE = "33";
    const REFUND                 = "04";
    const REGISTRATION           = "71";

    const CURRENCY_CNY      = "156";

    //开通认证支付必填字段检查
    static $auth_params_check = array(
        "version",
        "charset",
        "merId",
        "acqCode",
        "merReserved",
    );

    //支付请求可为空字段（但必须填写）
    static $pay_params_empty = array(
        "origQid"           => "",
        "acqCode"           => "",
        "merCode"           => "",
        "commodityUrl"      => "",
        "commodityName"     => "",
        "commodityUnitPrice"=> "",
        "commodityQuantity" => "",
        "commodityDiscount" => "",
        "transferFee"       => "",
        "customerName"      => "",
        "defaultPayType"    => "",
        "defaultBankNumber" => "",
        "transTimeout"      => "",
        "merReserved"       => "",
    );

    //支付请求必填字段检查
    static $pay_params_check = array(
        "version",
        "charset",
        "transType",
        "origQid",
        "merId",
        "merAbbr",
        "acqCode",
        "merCode",
        "commodityUrl",
        "commodityName",
        "commodityUnitPrice",
        "commodityQuantity",
        "commodityDiscount",
        "transferFee",
        "orderNumber",
        "orderAmount",
        "orderCurrency",
        "orderTime",
        "customerIp",
        "customerName",
        "defaultPayType",
        "defaultBankNumber",
        "transTimeout",
        "frontEndUrl",
        "backEndUrl",
        "merReserved",
    );

    //查询请求必填字段检查
    static $query_params_check = array(
        "version",
        "charset",
        "transType",
        "merId",
        "orderNumber",
        "orderTime",
        "merReserved",
    );

    //查询请求必填字段检查
    static $query_params_check2 = array(
        "version",
        "charset",
        "merId",
        "merReserved",
    );

    //发送验证码请求必填字段检查
    static $query_params_check3 = array(
        "version",
        "charset",
        "merId",
        "orderNumber",
        "orderAmount",
        "merReserved",
    );

    //商户保留域可能包含的字段
    static $mer_params_reserved = array(
        //  NEW NAME            OLD NAME
        "cardNumber",       "pan",
        "cardPasswd",       "password",
        "credentialType",   "idType",
        "cardCvn2",         "cvn",
        "cardExpire",       "expire",
        "credentialNumber", "idNo",
        "credentialName",   "name",
        "phoneNumber",      "mobile",
        "merAbstract",
        "smsCode",
        "activationNotifyUrl",
        //tdb only
        "orderTimeoutDate",
        "origOrderNumber",
        "origOrderTime",
    );

    static $notify_param_check = array(
        "version",
        "charset",
        "transType",
        "respCode",
        "respMsg",
        "respTime",
        "merId",
        "merAbbr",
        "orderNumber",
        "traceNumber",
        "traceTime",
        "qid",
        "orderAmount",
        "orderCurrency",
        "settleAmount",
        "settleCurrency",
        "settleDate",
        "exchangeRate",
        "exchangeDate",
        "cupReserved",
        "signMethod",
        "signature",
    );

    static $sign_ignore_params = array(
        "bank",
    );
}

?>
