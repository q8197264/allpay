<?php

/**
 * 支付网关逻辑 统一接口
 * 实现了支付网关与订单业务之间的调用
 * 支付方式与支付结果异步通知返回处理
 * User: 刘孝全
 * Date: 2016/5/10
 * Time: 15:38
 */
abstract class Gateway
{
    /**
     * 支付订单  必须实现
     * @return mixed
     */
    abstract protected function pay( $data );

    /**
     * 支付结果异步通知 必须实现
     * @return mixed
     */
    abstract protected function notify();
}
