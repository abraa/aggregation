<?php
/**
 * Created by PhpStorm.
 * User: 1002571
 * Date: 2017/8/16
 * Time: 10:38
 */

namespace aggregation\pay;
use aggregation\pay\base;
use aggregation\lib\alipay\AlipayConfig;
use aggregation\lib\alipay\AopClient;
use aggregation\lib\alipay\Builder;
use aggregation\lib\alipay\request;

class AlipayPagePay extends Base\BasePay{

    /**
     * 初始化对象
     * @param array
     */
    function init($config){
        $this->config = array_merge($this->config,$config);
        foreach($this->config as $key =>$value){
            AlipayConfig::$$key = $value;
        }
    }

    /**
     * 初始化对象所需参数数组
     * @return Array
     */
    function setup()
    {
        return array(
            "app_id"=>array("type"=>"text","name"=>"应用ID APP_ID","value"=>""),
            "alipay_public_key"=>array("type"=>"text","name"=>"支付宝公钥","value"=>""),
            "merchant_private_key"=>array("type"=>"text","name"=>"商户私钥","value"=>""),
            "return_url"=>array("type"=>"text","name"=>"同步跳转Url RETURN_URL","value"=>""),
            "notify_url"=>array('type'=>"text","name"=>"异步通知Url NOTIFY_URL" , "value"=>""),
        );
    }

    /**
     * 获取可用于支付的htmlcode如果有的话
     * @param array
     * @return String
     * */
    public function getCode($data)
    {
        $builder = new Builder\AlipayTradePagePayContentBuilder();
        //商户订单号，商户网站订单系统中唯一订单号，必填
        $builder->setOutTradeNo($data['out_trade_no']);
        //订单名称，必填
        $builder->setSubject($data['subject']);
        //付款金额，必填
        $builder->setTotalAmount($data['total_amount']);
        //商品描述，可空
        $builder->setBody($data['body']);
        //构造参数
        $biz_content=$builder->getBizContent($data);
        //构造参数 除公共参数外所有参数都必须在这里传递
//        $data['product_code'] = 'FAST_INSTANT_TRADE_PAY';
//        $biz_content = json_encode($data,JSON_UNESCAPED_UNICODE);

        $request = new request\AlipayTradePagePayRequest();
        $request->setNotifyUrl(AlipayConfig::$notify_url);
        $request->setReturnUrl(AlipayConfig::$return_url);
        $request->setBizContent ( $biz_content );

        // 调用支付api
        $aop = new AopClient ();
        $aop->gatewayUrl = AlipayConfig::$gatewayUrl;
        $aop->appId = AlipayConfig::$app_id;
        $aop->rsaPrivateKeyFilePath =  AlipayConfig::$merchant_private_key;
        $aop->alipayPublicKey = AlipayConfig::$alipay_public_key;
        $aop->apiVersion ="1.0";
        $aop->postCharset = AlipayConfig::$charset;
        $aop->format= "json";
        $aop->signType= AlipayConfig::$sign_type;
        // 开启页面信息输出
        $aop->debugInfo=false;
        $response = $aop->pageExecute($request,"post");
        return $response;
    }

    /**
     *  直接调用api支付
     * @param array
     * @return String
     */
    public function pay($data)
    {
        // TODO: Implement pay() method.
        return $this->getCode($data);
    }

    /**
     *  查询订单
     * @params array
     * @param $params
     * @return array 查询结果
     * @throws \aggregation\lib\alipay\AlipayException
     */
    public function queryOrder($params)
    {
        // TODO: Implement queryOrder() method.
        $aop = new AopClient ();
        $aop->gatewayUrl = AlipayConfig::$gatewayUrl;
        $aop->appId = AlipayConfig::$app_id;
        $aop->rsaPrivateKeyFilePath =  AlipayConfig::$merchant_private_key;
        $aop->alipayPublicKey = AlipayConfig::$alipay_public_key;
        $aop->apiVersion = '1.0';
        $aop->postCharset = AlipayConfig::$charset;
        $aop->format= "json";
        $aop->signType= AlipayConfig::$sign_type;
        $request = new request\AlipayTradeFastpayRefundQueryRequest ();

        $request->setBizContent(json_encode($params,JSON_UNESCAPED_UNICODE));
//            "{" .
//            "\"trade_no\":\"20150320010101001\"," .
//            "\"out_trade_no\":\"2014112611001004680073956707\"," .
//            "\"out_request_no\":\"2014112611001004680073956707\"" .
//            "  }"
        $result = $aop->execute ( $request);

        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;
        if(!empty($resultCode)&&$resultCode == 10000){
            echo "成功";
        } else {
            echo "失败";
        }
    }

    /**
     *  交易退款
     * @params array
     * @return array 查询结果
     */
    public function refund($params)
    {
        // TODO: Implement refund() method.
    }

    /**
     *  下载账单
     * @params array
     * @return array 查询结果
     */
    public function downloadbill($params)
    {
        // TODO: Implement downloadbill() method.
    }

    /**
     *  输出到支付网站通知处理结果
     */
    public function notify($result = true)
    {
        // TODO: Implement notify() method
        if($result){
             echo "success";	//请不要修改或删除
        }else {
            //验证失败
            echo "fail";
        }
    }

    /**
     *  验证签名
     * @param array $params
     * @return bool
     */
    public function verify($params =[])
    {
        // TODO: Implement verify() method.
        $aop = new AopClient();
        $aop->alipayPublicKey = AlipayConfig::$alipay_public_key;
        return $aop->rsaCheckV1($params, AlipayConfig::$alipay_public_key,AlipayConfig::$sign_type);

    }

    /**
     * 拼接用于传递的参数(格式化参数)
     * @param $paraMap Array
     * @param $urlencode Boolean
     * @return string
     */
    protected function formatParaMap($paraMap, $urlencode)
    {
        // TODO: Implement formatParaMap() method.
    }
}