<?php
require_once __DIR__.'/WxPay.Api.php';
require_once __DIR__.'/WxPay.Notify.php';

class PayNotifyCallBack extends WxPayNotify
{
	//查询订单
	public function Queryorder($transaction_id)
	{
		$input = new WxPayOrderQuery();
		$input->SetTransaction_id($transaction_id);
		$result = WxPayApi::orderQuery($input);

        //订单查询报错
        if (isset($array['return_code']) AND $array['return_code']=='FAIL'){
            CY_log::add($result);
        }

        //订单查询成功
		if(array_key_exists("return_code", $result)
			&& array_key_exists("result_code", $result)
			&& $result["return_code"] == "SUCCESS"
			&& $result["result_code"] == "SUCCESS")
		{
            CY_log::add("query: the trade is legitimate!（在微信商户后台能查到）");
			return true;
		}
		return false;
	}
	
	//重写回调处理函数
	public function NotifyProcess($data, &$msg)
	{
		if(!array_key_exists("transaction_id", $data))
        {
			$msg = "entry params is error";

            //查询返回数据
            CY_log::add($data);
			return false;
		}
		//查询订单，判断订单真实性
		if(!$this->Queryorder($data["transaction_id"]))
        {
            //查询返回数据
            CY_log::add($data);
			return false;
		}
        CY_log::add("the query verify is success!");
		return true;
	}

    //数组转字符串
//    private function arrayToStr( $array=array() )
//    {
//        if (!is_array($array)) return $array;
//        if (isset($array['return_code']) AND $array['return_code']=='FAIL'){
//            return $array['return_msg'];
//        }
//        $str = "\n";
//        foreach ( $array as $k=>$v){
//            $str .= '----------------------------【'.$k.'】:'.$v."\n";
//        }
//        return rtrim($str,"\n");
//    }
}