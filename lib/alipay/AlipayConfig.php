<?php
namespace aggregation\lib\alipay;

class AlipayConfig {
    //应用ID,您的APPID。
    public static $app_id;

    //商户私钥
    public static $merchant_private_key;

    //异步通知地址
    public static $notify_url;

    //同步跳转
    public static $return_url;

    //支付宝公钥,查看地址：https://openhome.alipay.com/platform/keyManage.htm 对应APPID下的支付宝公钥。
    public static $alipay_public_key ;

    //编码格式
    public static $charset = "UTF-8";

        //签名方式
    public static $sign_type = "RSA2";

        //支付宝网关
    public static $gatewayUrl =  "https://openapi.alipay.com/gateway.do";



}
