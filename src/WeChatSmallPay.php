<?php
 /**
 * ====================================
 * thinkphp5
 * ====================================
 * Author: 1002571
 * Date: 2018/1/29 13:37
 * ====================================
 * File: WeChatSmallPay.php
 * ====================================
 */

namespace aggregation\pay;

use \aggregation\lib\wechat\WxPayConfig;
use \aggregation\lib\wechat\WxPayException;
use \aggregation\lib\wechat\WxPayApi;
use \aggregation\lib\wechat\Data\WxPayJsApiPay;
use \aggregation\lib\wechat\Data\WxPayUnifiedOrder;
use \aggregation\lib\wechat\Data\WxPayNotifyReply;
use \aggregation\lib\wechat\Data\WxPayOrderQuery;
use \aggregation\lib\wechat\Data\WxPayRefund;
use \aggregation\lib\wechat\Data\WxPayDownloadBill;

use aggregation\pay\base\BasePay;

class WeChatSmallPay extends BasePay {

    /**
     * 初始化对象
     * @param array
     */
    function init($config){
        $this->config = array_merge($this->config,$config);
        foreach($this->config as $key =>$value){
            WxPayConfig::$$key = $value;
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
            "machine_id"=>array("type"=>"text","name"=>"商户号 MACHINE_ID","value"=>""),
            "pay_key"=>array("type"=>"text","name"=>"支付密钥 PAY_KEY","value"=>""),
            "app_secret"=>array("type"=>"text","name"=>"应用密钥 APP_SECRET","value"=>""),
            "notify_url"=>array('type'=>"text","name"=>"异步通知Url NOTIFY_URL" , "value"=>""),
        );
    }

    /**
     * 获取可用于支付的htmlcode如果有的话
     * @param $data
     * @param string $openId
     * @return String
     * @throws WxPayException
     * @internal param $array
     */
    public function getCode($data,$openId='')
    {
        if(empty($openId)){
            return false;
        }
        //2.统一下单
        $input = new WxPayUnifiedOrder();
        $input->SetBody($data['body']);         //商品描述
        $input->SetAttach($data['attach']);         //附加数据 原样返回
        $input->SetOut_trade_no($data['out_trade_no']); //商户订单号
        if(isset($data['fee_type'])){$input->SetFee_type($data['fee_type']);}      //标价币种 默认人民币：CNY

        $input->SetTotal_fee($data['total_fee']);   //标价金额 单位分
        $input->SetTime_start(gmdate("YmdHis"));  //订单生成时间，格式为yyyyMMddHHmmss
        $input->SetTime_expire(gmdate("YmdHis", time() + 8 * 3600 + 600));   //订单失效时间，格式为yyyyMMddHHmmss
        if(isset($data['goods_tag'])) {$input->SetGoods_tag($data['goods_tag']);}           //订单优惠标记
        $input->SetNotify_url(isset($data['notify_url']) ? $data['notify_url'] :$this->config['notify_url']);
        $input->SetTrade_type("JSAPI");       //JSAPI支付
        $input->SetOpenid($openId);
        $order = WxPayApi::unifiedOrder($input);
        return $order;
    }


    /**
     * @param $data
     * @param string $openId
     * @return array|bool
     * @throws WxPayException
     */
    public function pay($data,$openId='')
    {
       return $this->getCode($data,$openId);
    }

    /**
     *
     * 获取jsapi支付的参数
     * @param array $UnifiedOrderResult 统一支付接口返回的数据
     * @throws WxPayException
     *
     * @return json 数据，可直接填入js函数作为参数
     */
    public function GetJsApiParameters($UnifiedOrderResult)
    {
        if(!array_key_exists("appid", $UnifiedOrderResult)
            || !array_key_exists("prepay_id", $UnifiedOrderResult)
            || $UnifiedOrderResult['prepay_id'] == "")
        {
            throw new WxPayException("参数错误");
        }
        $jsapi = new WxPayJsApiPay();
        $jsapi->SetAppid($UnifiedOrderResult["appid"]);
        $timeStamp = time();
        $jsapi->SetTimeStamp("$timeStamp");
        $jsapi->SetNonceStr(WxPayApi::getNonceStr());
        $jsapi->SetPackage("prepay_id=" . $UnifiedOrderResult['prepay_id']);
        $jsapi->SetSignType("MD5");
        $jsapi->SetPaySign($jsapi->MakeSign());
        $parameters = json_encode($jsapi->GetValues());
        return $parameters;
    }
    /**
     *  查询订单
     * @params array
     * @return array 查询结果
     */
    public function queryOrder($params)
    {
        $input = new WxPayOrderQuery();
        if(isset($params["transaction_id"]) && $params["transaction_id"] != ""){
            $input->SetTransaction_id($params['transaction_id']);
        }elseif(isset($params["out_trade_no"]) && $params["out_trade_no"] != ""){
            $input->SetOut_trade_no($params["out_trade_no"]);
        }
        $result = WxPayApi::orderQuery($input);
        return $result;
    }

    /**
     *  交易退款
     * @params array
     * @return array 查询结果
     */
    public function refund($params)
    {
        $input = new WxPayRefund();
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
        $input->SetOp_user_id(WxPayConfig::$machine_id);
        return WxPayApi::refund($input);
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
        $input = new WxPayDownloadBill();
        $input->SetBill_date($bill_date);
        $input->SetBill_type($bill_type);
        return WxPayApi::downloadBill($input);
    }

    /**
     *  输出到支付网站通知处理结果
     */
    public function notify($needSign = false)
    {
        $msg = "OK";
        $WxPayNotifyReply = new WxPayNotifyReply();
        $result = WxPayApi::notify($msg);
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
       WxpayApi::replyNotify($WxPayNotifyReply->ToXml());
    }

    /**
     *  验证签名
     * @return boolean
     */
    public function verify()
    {
        $result =WxPayApi::notify($msg);
        return $result;
    }

    /**
     * 拼接用于传递的参数(格式化参数)
     * @param $paraMap Array
     * @param $urlencode Boolean
     * @return string
     */
    protected function formatParaMap($paraMap, $urlencode)
    {
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
     * 小程序再次签名
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
            'appId'=>WxPayConfig::$app_id,
        );
        //签名步骤一：按字典序排序参数
        $string = $this->formatParaMap($data,false);
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=".WxPayConfig::$pay_key;
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $data['paySign'] = strtoupper($string);
        unset($data['appId']);
        return $data;

    }
}