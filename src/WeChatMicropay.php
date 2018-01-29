<?php
/**
 * 微信刷卡支付(Micropay)
 * User: 1002571
 * Date: 2017/7/28
 * Time: 11:00
 */
namespace aggregation\pay;
use \aggregation\pay\base;
use \aggregation\lib\wechat;


class WeChatMicropay extends  Base\BasePay{

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
     * @param Array $config  配置参数数组
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
     * @return Array
     */
    function setup(){
        return array(
            "app_id"=>array("type"=>"text","name"=>"应用ID APP_ID","value"=>""),
            "machine_id"=>array("type"=>"text","name"=>"商户号 MACHINE_ID","value"=>""),
            "pay_key"=>array("type"=>"text","name"=>"支付密钥 PAY_KEY","value"=>""),
            "app_secret"=>array("type"=>"text","name"=>"应用密钥 APP_SECRET","value"=>""),
//            "notify_url"=>array('type'=>"String","name"=>"异步通知Url NOTIFY_URL" , "value"=>""),
        );
    }

    /**
     * 获取支付代码
     * @param $data
     * @return string
     */
    function getCode($data){
        return "";
    }

    /**
     * 输出到支付网站通知处理结果
     * @param boolean 是否需要签名输出
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
     *  直接调用api支付
     * @param $data
     * @return bool
     * @throws wechat\WxPayException
     * @internal param 参数参照微信统一下单接口 $array
     */
    public function pay($data)
    {
        //1.设置参数
        $input = new wechat\Data\WxPayMicroPay();
        $input->SetAuth_code($data["auth_code"]); //授权码
        $input->SetBody($data['body']);         //商品描述
        $input->SetAttach($data['attach']);         //附加数据 原样返回
        $input->SetOut_trade_no($data['out_trade_no']); //商户订单号
        if(isset($data['fee_type'])){$input->SetFee_type($data['fee_type']);}      //标价币种 默认人民币：CNY
        $input->SetTotal_fee($data['total_fee']);   //标价金额 单位分
        //2.提交被扫参数
        $result = wechat\WxPayApi::micropay($input, 5);
        //如果返回成功
        if(!array_key_exists("return_code", $result)
            || !array_key_exists("out_trade_no", $result)
            || !array_key_exists("result_code", $result))
        {
            throw new wechat\WxPayException("接口调用失败！");
        }
        //签名验证
        $out_trade_no = $input->GetOut_trade_no();
        //3、接口调用成功，明确返回调用失败
        if($result["return_code"] == "SUCCESS" &&
            $result["result_code"] == "FAIL" &&
            $result["err_code"] != "USERPAYING" &&
            $result["err_code"] != "SYSTEMERROR")
        {
            return false;
        }
        //4、确认支付是否成功
        $queryTimes = 10;
        $cancel = true; //判断是否需要撤单 true 撤
        while($queryTimes > 0)
        {
            $queryResult = $this->queryOrder($out_trade_no);
            //4.1判断查询结果
            //4.2成功跳出
            if($queryResult["return_code"] == "SUCCESS"
                && $queryResult["result_code"] == "SUCCESS")
            {
                //支付成功
                if($queryResult["trade_state"] == "SUCCESS"){
                    $cancel = false;
                    break;
                }
            }
            //如果返回错误码为“此交易订单号不存在”则直接认定失败
            if($queryResult["err_code"] == "ORDERNOTEXIST")
            {
               break;
            }
            sleep(2);
        }
        //5、次确认失败，则撤销订单
        if($cancel && !$this->cancel(array("out_trade_no"=>$out_trade_no)))
        {
            throw new wechat\WxpayException("撤销单失败！");
        }
        return true;
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
     * @return array 查询结果
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
     *
     * 撤销订单，如果失败会重复调用10次
     * @params array  out_trade_no | transaction_id
     * @params int 调用深度 $depth
     * @return boolean
     */
    public function cancel($params, $depth = 0)
    {
        if($depth > 10){
            return false;
        }
        $clostOrder = new wechat\Data\WxPayReverse();
        if(isset($params["out_trade_no"]) && $params["out_trade_no"] != ""){
            $clostOrder->SetOut_trade_no($params['out_trade_no']);
        }else
        if(isset($params["transaction_id"]) && $params["transaction_id"] != ""){
            $clostOrder->SetOut_trade_no($params['transaction_id']);
        }
        $result = wechat\WxPayApi::reverse($clostOrder);

        //接口调用失败
        if($result["return_code"] != "SUCCESS"){
            return false;
        }

        //如果结果为success且不需要重新调用撤销，则表示撤销成功
        if($result["result_code"] != "SUCCESS"
            && $result["recall"] == "N"){
            return true;
        } else if($result["recall"] == "Y") {
            return $this->cancel($params, ++$depth);
        }
        return false;
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
}
