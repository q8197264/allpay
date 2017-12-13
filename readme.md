#### 支付集成网关接口 V3 ####
开发人员：刘孝全
### 一、支付流程图 ###
```c
|```````````````|
| 调起支付 pay    >>>>>>>>>>>   |`````````````|
|_______________|               | 支付宝      |
                                |  微信       |
                                |  银联       |
|````````````````|              | 快捷支付    |
| 接收通知 notify  <<<<<<<<<<<  |_____________|
|________________|
```


### 二、支付接口目录结构说明 ###
```c

                   | abstract|- gateway.abstract.php 支付网关抽象类接口
                   |
                   |        |- config.php        基础配置与核心文件加载
                   | config-|
                   |
                   | common-| comm.func.php     公共函数
                   |
                   | log ---|PayLog.php	    日志
                   |
                   |        |router.php     url路由:http://component.yaofang.cn/allpay/cy_allpay.php?m=pay&out_trade_no=123223421&total_fee=1&pay_id=1...
                   | Core --|
                   |        |
                   |
                   |
    comm_pay.php --| gateway --| alipay()    各三方支付网关
                   |
                   |
                   |
                   |
                   |
                   | db --| DB.php

```

### 三、接口调用 ###
#### 1.快速调用开发 ####
```php
<?php
        *支付所需参数:

        $config['defaultbank'] = CMB;                 //银联简码                      [银联支付必填]
        $config['notify_url'] = 'http://www.yaofang.cn/a/pay/notify.php?pay_id='.$pay_id;              [必填]
        $config['return_url'] = 'http://www.yaofang.cn/a/pay/call_back.php?pay_id='.$pay_id;           [必填]
        //$config['aliPayUrl']=1;                             //输出支付链接, 只对支付宝有效    [可选]
        $this->payapi->_config($config);    //载入参数配置
        $msg = $this->payapi->pay( $order_id, $pay_id, $card_number, $vcode ); //调起支付
?>
```

```php
<?php
        *异步通知
        $pay_id = $_GET['pay_id'];
        $config['log_path'] = "/apache/applog/www.yaofang.cn/paylog/logs";
        $this->payapi->_config($config);
        $this->payapi->notify( $pay_id );
?>
```

```php
<?php
        //同步跳转通知
        $pay_id = $_GET['pay_id'];
        $data = $this->payapi->return_url( $pay_id );
?>
```

#### 2.高级配置 ####
```php
<?php
        文档完善中...
?>
```
		
四、接口测试

    支付宝测试：
    http://component.yaofang.cn/allpay/cy_allpay/pay?order_id=4343434&out_trade_no=123223421&total_fee=1&pay_id=1&notify_url=http://m.yaofang.cn/pay/notify/1&return_url=http://m.yaofang.cn/pay/return_url/1

    微信支付二维码测试：
    http://component.yaofang.cn/allpay/cy_allpay/pay?out_trade_no=12223223421&total_fee=1&pay_id=25&notify_url=http://m.yaofang.cn/pay/notify/25&return_url=http://m.yaofang.cn/pay/return_url/25



------------------- END ----------------------------








银行简码：
中国工商银行: ICBCB2C ICBC_OUT.gif
招商银行    :CMB
$alipay=array(	0=>array(
        'bankname'=>'中国工商银行 ICBCB2C',
        'bankimg'=>'ICBC_OUT.gif',
        'bankcode'=>''),
        1=>array(
            'bankname'=>'招商银行',
            'bankimg'=>'CMB_OUT.gif',
            'bankcode'=>'CMB'),
        2=>array(
            'bankname'=>'中国建设银行',
            'bankimg'=>'CCB_OUT.gif',
            'bankcode'=>'CCB'),
        3=>array(
            'bankname'=>'中国农业银行',
            'bankimg'=>'ABC_OUT.gif',
            'bankcode'=>'ABC'),
        4=>array(
            'bankname'=>'上海浦东发展银行',
            'bankimg'=>'SPDB_OUT.gif',
            'bankcode'=>'SPDB'),
        5=>array(
            'bankname'=>'兴业银行',
            'bankimg'=>'CIB_OUT.gif',
            'bankcode'=>'CIB'),
        6=>array(
            'bankname'=>'广东发展银行',
            'bankimg'=>'GDB_OUT.gif',
            'bankcode'=>'GDB'),
        7=>array(
            'bankname'=>'温州银行',
            'bankimg'=>'wz_bank.jpg',
            'bankcode'=>'WZCBB2C-DEBIT'),
        8=>array(
            'bankname'=>'中国民生银行',
            'bankimg'=>'CMBC_OUT.gif',
            'bankcode'=>'CMBC'),
        9=>array(
            'bankname'=>'交通银行',
            'bankimg'=>'COMM_OUT.gif',
            'bankcode'=>'COMM'),
        10=>array(
            'bankname'=>'中国银行',
            'bankimg'=>'BOC_OUT.gif',
            'bankcode'=>'BOCB2C'),
        11=>array(
            'bankname'=>'中信银行',
            'bankimg'=>'CITIC_OUT.gif',
            'bankcode'=>'CITIC'),
        12=>array(
            'bankname'=>'中国光大银行',
            'bankimg'=>'CEB_OUT.gif',
            'bankcode'=>'CEBBANK'),
        13=>array(
            'bankname'=>'杭州银行',
            'bankimg'=>'HZCB_OUT.gif',
            'bankcode'=>'HZCBB2C'),
        14=>array(
            'bankname'=>'中国工商银行(B2B)',
            'bankimg'=>'ENV_ICBC_OUT.gif',
            'bankcode'=>'ICBCBTB'),
        15=>array(
            'bankname'=>'中国农业银行(B2B)',
            'bankimg'=>'ENV_ABC_OUT.gif',
            'bankcode'=>'ABCBTB'),
        16=>array(
            'bankname'=>'上海浦东发展银行(B2B)',
            'bankimg'=>'ENV_SPDB_OUT.gif',
            'bankcode'=>'SPDBB2B'),
        17=>array(
            'bankname'=>'中国建设银行(B2B)',
            'bankimg'=>'jsb2b.gif',
            'bankcode'=>'CCBB2B'),
        18=>array(
            'bankname'=>'上海银行',
            'bankimg'=>'sh.gif',
            'bankcode'=>'SHBANK'),
        19=>array(
            'bankname'=>'宁波银行',
            'bankimg'=>'nb.gif',
            'bankcode'=>'NBBANK'),
        20=>array(
            'bankname'=>'上海农商银行',
            'bankimg'=>'shns_bank.jpg',
            'bankcode'=>'SHRCB'),
        21=>array(
            'bankname'=>'富滇银行',
            'bankimg'=>'fd_bank.jpg',
            'bankcode'=>'FDB'),
        22=>array(
            'bankname'=>'北京银行',
            'bankimg'=>'bj_bank.jpg',
            'bankcode'=>'BJBANK'),
        23=>array(
            'bankname'=>'北京农村商业银行',
            'bankimg'=>'bjns_bank.jpg',
            'bankcode'=>'BJRCB'),
        24=>array(
            'bankname'=>'中国邮政储蓄银行',
            'bankimg'=>'zgyz_bank.jpg',
            'bankcode'=>'POSTGC'),
        25=>array(
            'bankname'=>'平安银行',
            'bankimg'=>'pg.gif',
            'bankcode'=>'SPABANK'),
        26=>array(
            'bankname'=>'深圳发展银行',
            'bankimg'=>'SDB_OUT.gif',
            'bankcode'=>'SDB')

    );