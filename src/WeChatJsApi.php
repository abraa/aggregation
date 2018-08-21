<?php
/**
 * 微信公众号支付(JSAPI)
 * User: 1002571
 * Date: 2017/7/28
 * Time: 11:00
 */
namespace aggregation\pay;

use \aggregation\pay\base;
use \aggregation\lib\wechat;


class WeChatJsApi extends  Base\BasePay{

    //=======【基本信息设置】=====================================
    //
    /**
     *
     * 微信公众号信息配置
     *
     * APPID：绑定支付的APPID（必须配置，开户邮件中可查看）
     *
     * MCHID：商户号（必须配置，开户邮件中可查看）
     *
     * KEY：商户支付密钥，参考开户邮件设置（必须配置，登录商户平台自行设置）
     * 设置地址：https://pay.weixin.qq.com/index.php/account/api_cert
     *
     * APPSECRET：公众帐号secert（仅JSAPI支付的时候需要配置， 登录公众平台，进入开发者中心可设置），
     * 获取地址：https://mp.weixin.qq.com/advanced/advanced?action=dev&t=advanced/dev&token=2005451881&lang=zh_CN
     * @var string
     */
//    const APPID = 'wx426b3015555a46be';
//    const MCHID = '1900009851';
//    const KEY = '8934e7d15453e97507ef794cf7b0519d';
//    const APPSECRET = '7813490da6f1265e4901ffb80afaa36f';



    //========【上面是参考配置常量】===========================================================================================================
    /**
     *
     * 网页授权接口微信服务器返回的数据，返回样例如下
     * {
     *  "access_token":"ACCESS_TOKEN",
     *  "expires_in":7200,
     *  "refresh_token":"REFRESH_TOKEN",
     *  "openid":"OPENID",
     *  "scope":"SCOPE",
     *  "unionid": "o6_bmasdasdsad6_2sgVt7hMZOPfL"
     * }
     * 其中access_token可用于获取共享收货地址
     * openid是微信支付jsapi支付接口必须的参数
     * @var array
     */
    public $data = null;


    /**
     * 初始化对象
     * @param array $config  配置参数数组
     */
    function init($config){
        $this->config = array_merge($this->config,$config);
        foreach($this->config as $key =>$value){
            wechat\WxPayConfig::$$key = $value;
        }
    }

    /**
     * 返回初始化话对象需要的参数 init使用配置key
     *  type="|String|Text|Number|Date|"
     * @return array
     */
    function setup(){
        return array(
            "app_id"=>array("type"=>"text","name"=>"应用ID APP_ID","value"=>""),
            "machine_id"=>array("type"=>"text","name"=>"商户号 MACHINE_ID","value"=>""),
            "pay_key"=>array("type"=>"text","name"=>"支付密钥 PAY_KEY","value"=>""),
            "app_secret"=>array("type"=>"text","name"=>"应用密钥 APP_SECRET","value"=>""),
            "notify_url"=>array('type'=>"text","name"=>"异步通知Url NOTIFY_URL" , "value"=>""),
            );
    }

    /**
     * @param $data
     * @param string $openid
     * @return array|String
     * @throws wechat\WxPayException
     */
    function getCode($data, $openid = ''){
        return $this->pay($data, $openid);
    }

    /**
     * 输出到支付网站通知处理结果
     * @param boolean 是否需要签名输出
     * @throws wechat\WxPayException
     */
    function notify($needSign = false){
        $msg = "OK";
        $WxPayNotifyReply = new \aggregation\lib\wechat\Data\WxPayNotifyReply();
        $result = wechat\WxPayApi::notify($msg);
        //验签
        if($result == false){
            $WxPayNotifyReply->SetReturn_code("FAIL");
            $WxPayNotifyReply->SetReturn_msg($msg);
        } else {
            $WxPayNotifyReply->SetReturn_code("SUCCESS");
            $WxPayNotifyReply->SetReturn_msg("OK");
        }
        //如果需要签名
        if($needSign == true &&
            $WxPayNotifyReply->GetReturn_code() == "SUCCESS")
        {
            $WxPayNotifyReply->SetSign();
        }
        wechat\WxpayApi::replyNotify($WxPayNotifyReply->ToXml());
    }

    /**
     *  验证支付网站返回通知
     * @reutrn mixed false 代表失败
     */
    public function verify()
    {
        $result = wechat\WxPayApi::notify($msg);
        return $result;
    }



    /**
     *
     * 通过跳转获取用户的openid，跳转流程如下：
     * 1、设置自己需要调回的url及其其他参数，跳转到微信服务器https://open.weixin.qq.com/connect/oauth2/authorize
     * 2、微信服务处理完成之后会跳转回用户redirect_uri地址，此时会带上一些参数，如：code
     *
     * @return 用户的openid
     */
    public function GetOpenid()
    {
        //通过code获得openid
        if (!isset($_GET['code'])){
            //触发微信返回code码
            $baseUrl = urlencode('http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].$_SERVER['QUERY_STRING']);
            $url = $this->__CreateOauthUrlForCode($baseUrl);
            Header("Location: $url");
            exit();
        } else {
            //获取code码，以获取openid
            $code = $_GET['code'];
            $openid = $this->getOpenidFromMp($code);
            return $openid;
        }
    }
    /**
     *
     * 构造获取code的url连接
     * @param string $redirectUrl 微信服务器回跳的url，需要url编码
     *
     * @return 返回构造好的url
     */
    private function __CreateOauthUrlForCode($redirectUrl)
    {
        $urlObj["appid"] = $this->config['app_id'];
        $urlObj["redirect_uri"] = "$redirectUrl";
        $urlObj["response_type"] = "code";
        $urlObj["scope"] = "snsapi_base";
        $urlObj["state"] = "STATE"."#wechat_redirect";
        $bizString = $this->ToUrlParams($urlObj);
        return "https://open.weixin.qq.com/connect/oauth2/authorize?".$bizString;
    }
    /**
     *
     * 构造获取open和access_toke的url地址
     * @param string $code，微信跳转带回的code
     *
     * @return 请求的url
     */
    private function __CreateOauthUrlForOpenid($code)
    {
        $urlObj["appid"] = $this->config['app_id'];
        $urlObj["secret"] = $this->config['app_secret'];
        $urlObj["code"] = $code;
        $urlObj["grant_type"] = "authorization_code";
        $bizString = $this->ToUrlParams($urlObj);
        return "https://api.weixin.qq.com/sns/oauth2/access_token?".$bizString;
    }
    /**
     *
     * 获取jsapi支付的参数
     * @param array $UnifiedOrderResult 统一支付接口返回的数据
     * @throws wechat\WxPayException
     *
     * @return json 数据，可直接填入js函数作为参数
     */
    public function GetJsApiParameters($UnifiedOrderResult)
    {
        if(!array_key_exists("appid", $UnifiedOrderResult)
            || !array_key_exists("prepay_id", $UnifiedOrderResult)
            || $UnifiedOrderResult['prepay_id'] == "")
        {
            throw new wechat\WxPayException("参数错误");
        }
        $jsapi = new wechat\Data\WxPayJsApiPay();
        $jsapi->SetAppid($UnifiedOrderResult["appid"]);
        $timeStamp = time();
        $jsapi->SetTimeStamp("$timeStamp");
        $jsapi->SetNonceStr(wechat\WxPayApi::getNonceStr());
        $jsapi->SetPackage("prepay_id=" . $UnifiedOrderResult['prepay_id']);
        $jsapi->SetSignType("MD5");
        $jsapi->SetPaySign($jsapi->MakeSign());
        $parameters = json_encode($jsapi->GetValues());
        return $parameters;
    }

    /**
     *
     * 通过code从工作平台获取openid
     * @param string $code 微信跳转回来带上的code
     *
     * @return openid
     */
    public function GetOpenidFromMp($code)
    {
        $url = $this->__CreateOauthUrlForOpenid($code);
        //初始化curl
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT,10);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,FALSE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        if(wechat\WxPayConfig::CURL_PROXY_HOST != "0.0.0.0"
            && wechat\WxPayConfig::CURL_PROXY_PORT != 0){
            curl_setopt($ch,CURLOPT_PROXY, wechat\WxPayConfig::CURL_PROXY_HOST);
            curl_setopt($ch,CURLOPT_PROXYPORT, wechat\WxPayConfig::CURL_PROXY_PORT);
        }
        //运行curl，结果以jason形式返回
        $res = curl_exec($ch);
        curl_close($ch);
        //取出openid
        $data = json_decode($res,true);
        $this->data = $data;
        $openid = $data['openid'];
        return $openid;
    }

    /**
     *
     * 拼接签名字符串
     * @param array $urlObj
     *
     * @return string  返回已经拼接好的字符串
     */
    private function ToUrlParams($urlObj)
    {
        return $this->formatParaMap($urlObj);
    }

    /**
     *
     * 获取地址js参数
     *
     * @return json 获取共享收货地址js函数需要的参数，json格式可以直接做参数使用
     */
    public function GetEditAddressParameters()
    {
        $getData = $this->data;
        $data = array();
        $data["appid"] = $this->config['app_id'];
        $data["url"] = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        $time = time();
        $data["timestamp"] = "$time";
        $data["noncestr"] = "".rand(10000000,999999999);
        $data["accesstoken"] = $getData["access_token"];
        ksort($data);
        $params = $this->ToUrlParams($data);
        $addrSign = sha1($params);

        $afterData = array(
            "addrSign" => $addrSign,
            "signType" => "sha1",
            "scope" => "jsapi_address",
            "appId" => $this->config['app_id'],
            "timeStamp" => $data["timestamp"],
            "nonceStr" => $data["noncestr"]
        );
        $parameters = json_encode($afterData);
        return $parameters;
    }

    /**
     *  直接调用api支付
     * @param array 参数参照微信统一下单接口
     * @param string $openId
     * @return array
     * @throws wechat\WxPayException
     */
    public function pay($data, $openId = '')
    {
        //1. 获取openid
        if(empty($openId)){
            $openId = $this->GetOpenid();
        }
        //2.统一下单
        $input = new wechat\Data\WxPayUnifiedOrder();
        $input->SetBody($data['body']);         //商品描述
        $input->SetAttach($data['attach']);         //附加数据 原样返回
        $input->SetOut_trade_no($data['out_trade_no']); //商户订单号
        if($data['fee_type']){$input->SetFee_type($data['fee_type']);}      //标价币种 默认人民币：CNY

        $input->SetTotal_fee($data['total_fee']);   //标价金额 单位分
        $input->SetTime_start(date("YmdHis"));  //订单生成时间，格式为yyyyMMddHHmmss
        $input->SetTime_expire(date("YmdHis", time() + 600));   //订单失效时间，格式为yyyyMMddHHmmss
        if($data['goods_tag']) {$input->SetGoods_tag($data['goods_tag']);}           //订单优惠标记
        $input->SetNotify_url($this->config['notify_url']);
        $input->SetTrade_type("JSAPI");       //JSAPI支付
        $input->SetOpenid($openId);
        $order = wechat\WxPayApi::unifiedOrder($input);
        return $order;
    }


    /**
     *  查询订单
     * @params array
     * @throws wechat\WxPayException
     * @return array 查询结果
     */
    public function queryOrder($params)
    {
        $input = new \aggregation\lib\wechat\Data\WxPayOrderQuery();
        if(isset($params["transaction_id"]) && $params["transaction_id"] != ""){
            $input->SetTransaction_id($params['transaction_id']);
        }elseif(isset($params["out_trade_no"]) && $params["out_trade_no"] != ""){
            $input->SetOut_trade_no($params["out_trade_no"]);
        }
        $result = wechat\WxPayApi::orderQuery($input);
        return $result;
    }

    /**
     *  交易退款
     * @params array
     * @param $params
     * @return array 查询结果
     * @throws wechat\WxPayException
     */
    public function refund($params)
    {
        $input = new wechat\Data\WxPayRefund();
        if(isset($params["transaction_id"]) && $params["transaction_id"] != ""){
            $transaction_id = $params["transaction_id"];
            $input->SetTransaction_id($transaction_id);
        }else if(isset($params["out_trade_no"]) && $params["out_trade_no"] != ""){
            $out_trade_no = $params["out_trade_no"];
            $input->SetOut_trade_no($out_trade_no);

        }
        $input->SetTotal_fee($params["total_fee"]);
        $input->SetRefund_fee($params["refund_fee"]);
        $input->SetOut_refund_no($params["out_refund_no"]); //商户内部退款订单号
        $input->SetOp_user_id(wechat\WxPayConfig::$machine_id);
        return wechat\WxPayApi::refund($input);
    }

    /**
     *  下载账单
     * @params array
     * @return array 查询结果
     */
    public function downloadbill($params)
    {
        $bill_date = $params["bill_date"];
        $bill_type = $params["bill_type"];
        $input = new wechat\Data\WxPayDownloadBill();
        $input->SetBill_date($bill_date);
        $input->SetBill_type($bill_type);
        return wechat\WxPayApi::downloadBill($input);
    }

    /**
     *  查询退款
     * @params array
     * @return array 查询结果
     */
    public function refundQuery($params){
        $input = new wechat\Data\WxPayRefundQuery();
        if(isset($params["transaction_id"]) && $params["transaction_id"] != ""){
            $transaction_id = $params["transaction_id"];
            $input->SetTransaction_id($transaction_id);
        }else
        if(isset($params["out_trade_no"]) && $params["out_trade_no"] != ""){
            $out_trade_no = $params["out_trade_no"];
            $input->SetOut_trade_no($out_trade_no);
        }else
        if(isset($params["out_refund_no"]) && $params["out_refund_no"] != ""){
            $out_refund_no = $params["out_refund_no"];
            $input->SetOut_refund_no($out_refund_no);
        }else
        if(isset($params["refund_id"]) && $params["refund_id"] != ""){
            $refund_id = $params["refund_id"];
            $input->SetRefund_id($refund_id);
        }
        return   wechat\WxPayApi::refundQuery($input);
    }

    /**
     *  关闭订单 WxPayCloseOrder中out_trade_no必填
     * appid、mchid、spbill_create_ip、nonce_str不需要填入
     * 以下情况需要调用关单接口：商户订单支付失败需要生成新单号重新发起支付，要对原订单号调用关单，避免重复支付；系统下单后，用户支付超时，系统退出不再受理，避免用户继续，请调用关单接口。
     * 注意：订单生成后不能马上调用关单接口，最短调用时间间隔为5分钟。
     * @params array
     * @return array 查询结果
     */
    public function closeOrder($params){
        $input =   new wechat\Data\WxPayCloseOrder();
        if(isset($params["out_trade_no"]) && $params["out_trade_no"] != ""){
            $out_trade_no = $params["out_trade_no"];
            $input->SetOut_trade_no($out_trade_no);
        }
        return   wechat\WxPayApi::closeOrder($input);
    }

    /**
     *  解密步骤如下：
     *（1）对加密串A做base64解码，得到加密串B
     *（2）对商户key做md5，得到32位小写key* ( key设置路径：微信商户平台(pay.weixin.qq.com)-->账户设置-->API安全-->密钥设置 )
     *（3）用key*对加密串B做AES解密
     * @params array
     * @return array 查询结果
     */
    public function refundNotify($params){
        //TODO...
        $result = "";
        if(isset($params['req_info']) && $params["req_info"] != ""){

        }
        return $result;
    }
    /**
     * 格式化参数
     * @param array $paraMap
     * @param boolean $urlencode
     * @return string
     */
    function formatParaMap($paraMap, $urlencode = false){
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v)
        {
            if($urlencode)
            {
                $v = urlencode($v);
            }
            if($k != "sign"){
                $buff .= $k . "=" . $v . "&";
            }
        }
        $buff = trim($buff, "&");
        return $buff;
    }

    /**
     * 签名js调用参数
     * @param $package   prepay_id=wx2017033010242291fcfe0db70013231072
     * @param $nonceStr  5K8264ILTKCH16CQ2502SI8ZNMTM67VS
     * @return array
     */
    public function setSign($package, $nonceStr){
        $data = array(
            'package'=>$package,
            'nonceStr'=>$nonceStr,
            'timeStamp'=>time(),
            'signType'=>'MD5',
            'appId'=>wechat\WxPayConfig::$app_id,
        );
        //签名步骤一：按字典序排序参数
        $string = $this->formatParaMap($data,false);
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=".wechat\WxPayConfig::$pay_key;
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $data['paySign'] = strtoupper($string);
        unset($data['appId']);
        return $data;

    }
}
