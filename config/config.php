<?php
/**
 * 支付接口框架配置
 * User: 刘孝全
 * Date: 2016/2/23
 * Time: 11:42
 */
header('Content-type:text/html;charset=utf-8');
date_default_timezone_set('PRC') OR ini_set('date.timezone','Asia/Shanghai');
defined('COMPONENT_ROOT') OR define('COMPONENT_ROOT',dirname(dirname(__DIR__)));

//加载日志类
if ( !class_exists('CY_Log') && is_file(COMPONENT_ROOT.'/log/cy_log.php') ){
    require_once(COMPONENT_ROOT.'/log/cy_log.php');
}else{
    if (!property_exists('CY_Log','path')){
        exit('the Log::path is not exists'.' file:'.__FILE__);
    }
}

//默认日志路径
CY_Log::path("/apache/applog/www.yaofang.cn/paylog");

//错误异常类
is_file(dirname(__DIR__).'/Exception/AllPayException.php') AND require_once(dirname(__DIR__).'/Exception/AllPayException.php');

//公共函数库
is_file(dirname(__DIR__).'/common/common.func.php') AND require_once(dirname(__DIR__).'/common/common.func.php');

//自动加载类
require_once(dirname(__DIR__).'/core/autoload.php');

//数据库驱动
//require_once(COMPONENT_ROOT.'/DAO/DAO.php');

//http访问---测试时用 m代表方法
include_once(dirname(__DIR__).'/core/router.php');