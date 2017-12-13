<?php
/**
 * 
 * 微信支付API异常类
 * @author widyhu
 *
 */
//初始化日志
//require_once(CURRENT_DIR.'/gateway/wxpay/log.php');
//$logHandler= new CLogFileHandler("/apache/applog/{$_SERVER['SERVER_NAME']}/paylog/notify");
//$log = Log::Init($logHandler, 15);
class AllPayException extends Exception {
    public function __construct($message){
        parent::__construct($message);
        CY_log::add('['.$this->getFile().']'.'['.$this->getLine().']'.$message);
    }

	public function getMsg()
	{
		return '['.$this->getFile().']'.'['.$this->getLine().']'.$this->getMessage();
	}
}