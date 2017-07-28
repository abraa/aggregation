<?php
/**
 * Created by PhpStorm.
 * User: 1002571
 * Date: 2017/7/28
 * Time: 11:35
 */
namespace Aggregation\Pay\Base;

abstract class BasePay{
    /** @var array 配置参数数组 */
    protected $config = array() ;
    /**
     * 初始化对象
     * @param Mixed
     */
    abstract function init();
    /**
     * 初始化对象所需参数数组
     * @return Array
     */
    abstract function setup();
    /**
     * 获取可用于支付的htmlcode如果有的话
     * @return String
     * */
    abstract public function getCode();
    /**
     *  验证支付网站返回通知
     */
    abstract public function notify();
    /**
     * 拼接用于传递的参数(格式化参数)
     */
    abstract protected  function formatParaMap();


    /**
     * 设置参数 $key可以配合setup使用
     * @param $key
     * @param $value
     */
    function setConfig($key,$value){
        $this->config[$key] = $value;
    }
}