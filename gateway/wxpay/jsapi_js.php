<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
   <title>微信安全支付</title>
   <link rel="stylesheet" type="text/css" href="css/activity.css">
</head>
<body>
<!--<a href="#" id="checkJsApi">判断是否支持当前版本</a>-->
</body>
</html>
<script type="text/javascript" charset="UTF-8" src="http://res.wx.qq.com/open/js/jweixin-1.0.0.js"></script>
<script type="text/javascript">
    window.onload = function(){
        var appId       = "<?php echo $jsApiParameters['appId'] ?>";
        var timeStamp   = "<?php echo $jsApiParameters['timeStamp'] ?>";
        var nonceStr    = "<?php echo $jsApiParameters['nonceStr'] ?>";
        var packages    = "<?php echo $jsApiParameters['package'] ?>";
        var signType    = "<?php echo $jsApiParameters['signType'] ?>";
        var paySign     = "<?php echo $jsApiParameters['paySign'] ?>";
        var signature   = "<?php echo $jsApiParameters['signature'] ?>";

        var return_url  = "<?php echo $jsApiParameters['return_url'] ?>";

        wx.config({
            debug: false, // 开启调试模式,调用的所有api的返回值会在客户端alert出来，若要查看传入的参数，可以在pc端打开，参数信息会通过log打出，仅在pc端时才会打印。
            appId: appId, // 必填，公众号的唯一标识
            timestamp:timeStamp , // 必填，生成签名的时间戳
            nonceStr: nonceStr, // 必填，生成签名的随机串
            signature: signature,// 必填，签名，见附录1
            jsApiList: [
                'chooseWXPay',
                'checkJsApi'
            ] // 必填，需要使用的JS接口列表，所有JS接口列表见附录2
        });
        wx.ready(function(){
            // 1 判断当前版本是否支持指定 JS 接口，支持批量判断
            // document.querySelector('#checkJsApi').onclick = function () {
            //     wx.checkJsApi({
            //         jsApiList: [
            //             'chooseWXPay'
            //         ],
            //         complete: function (res) {
            //             alert(JSON.stringify(res));
            //         }
            //     });
            // };
            // config信息验证后会执行ready方法，所有接口调用都必须在config接口获得结果之后，config是一个客户端的异步操作，所以如果需要在页面加载时就调用相关接口，则须把相关接口放在ready函数中调用来确保正确执行。对于用户触发时才调用的接口，则可以直接调用，不需要放在ready函数中。
            wx.chooseWXPay({
                timestamp: timeStamp, // 支付签名时间戳，注意微信jssdk中的所有使用timestamp字段均为小写。但最新版的支付后台生成签名使用的timeStamp字段名需大写其中的S字符
                nonceStr: nonceStr, // 支付签名随机串，不长于 32 位
                package: packages, // 统一支付接口返回的prepay_id参数值，提交格式如：prepay_id=***）
                signType: signType, // 签名方式，默认为'SHA1'，使用新版支付需传入'MD5'
                paySign: paySign, // 支付签名
                success: function (res) {
                    // 支付成功后的回调函数
                    location.href = return_url;
                },
                cancel: function (res) {
                    location.href=return_url+'&msg=支付已取消';
                },
                fail:function(res){
                    if ( res.errMsg == 'chooseWXPay:cancel' ) {
                        location.href=return_url+'&msg=支付已取消';
                    }else{
                        alert('微信支付失败，请联系客服或去网页端支付！'+res.errMsg);
                        //location.href=return_url+'&msg=微信支付失败，请联系客服或去网页端支付！';
                    }

                }
            });
        });
        wx.error(function (res) {
            alert(res.errMsg);
        });
    }
</script>
