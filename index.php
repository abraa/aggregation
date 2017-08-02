<?php
/**
 * Created by PhpStorm.
 * User: 1002571
 * Date: 2017/8/2
 * Time: 15:51
 */
 require_once "./vendor/autoload.php";

$res = new \Aggregation\Pay\WeChatJsApi();
var_dump($res);

var_dump(new \Lib\Wechat\Data\WxPayResults());