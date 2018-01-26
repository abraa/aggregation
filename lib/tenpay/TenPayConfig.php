<?php
namespace aggregation\lib\tenpay;


class TenPayConfig
{
    public static   $spname ;   //名称
    public static   $partner ;  //商户号
    public static   $key ;      //支付密钥
    public static   $return_url ;   //同步回调地
    public static   $notify_url ;   //异步通知url
    public static   $gatewayUrl = 'https://gw.tenpay.com/gateway/pay.htm';   //支付网关
}

?>