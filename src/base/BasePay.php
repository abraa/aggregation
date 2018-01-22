<?php
/**
 * Created by PhpStorm.
 * User: 1002571
 * Date: 2017/7/28
 * Time: 11:35
 */
namespace aggregation\pay\base;

abstract class BasePay{
    /** @var array 配置参数数组 */
    protected $config = array() ;
    /**
     * 初始化对象
     * @param array
     */
    abstract function init($config);
    /**
     * 初始化对象所需参数数组
     * @return Array
     */
    abstract function setup();
    /**
     * 获取可用于支付的htmlcode如果有的话
     * @param array
     * @return String
     * */
    abstract public function getCode($data);
    /**
     *  直接调用api支付
     * @param array
     */
    abstract public function pay($data);
    /**
     *  查询订单
     * @params array
     * @return array 查询结果
     */
    abstract public function queryOrder($params);
    /**
     *  交易退款
     * @params array
     * @return array 查询结果
     */
    abstract public function refund($params);
    /**
     *  下载账单
     * @params array
     * @return array 查询结果
     */
    abstract public function downloadbill($params);

    /**
     *  输出到支付网站通知处理结果
     */
    abstract public function notify();
    /**
     *  验证签名
     * @return boolean
     */
    abstract public function verify();
    /**
     * 拼接用于传递的参数(格式化参数)
     * @param $paraMap Array
     * @param $urlencode Boolean
     * @return string
     */
    abstract protected  function formatParaMap($paraMap, $urlencode);


    /**
     * 设置参数 $key可以配合setup使用
     * @param $key
     * @param $value
     */
    function setConfig($key,$value){
        $this->config[$key] = $value;
    }
}