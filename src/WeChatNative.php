<?php
/**
 * 微信扫码支付(Native)
 * User: 1002571
 * Date: 2017/7/28
 * Time: 11:00
 */
namespace aggregation\pay;
use aggregation\lib\QRcode;
use aggregation\pay\base;
use aggregation\lib\wechat;


class WeChatNative extends  Base\BasePay{

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
            "notify_url"=>array('type'=>"text","name"=>"异步通知Url NOTIFY_URL" , "value"=>""),
        );
    }

    /**
     * 返回支付表单代码(url需要生成二维码)
     * @param $data (需要却没有set的自己加上去)
     * @return string
     */
    function getCode($data){
        //模式1.生成扫描支付URL(需要切换解开注释)
//      $url = $this->GetPrePayUrl($data['product_id']);
//        //转换短链接
//        $input = new wechat\Data\WxPayShortUrl();
//
//        $input->SetLong_url($url);
//        $result = wechat\WxPayApi::shorturl($input);
//        if($result['short_url']){
//            $url = $result['short_url'];
//        }
        //模式2.统一下单
        $input = new wechat\Data\WxPayUnifiedOrder();
        $input->SetBody($data['body']);         //商品描述
        isset($data['attach']) and $input->SetAttach($data['attach']);         //附加数据 原样返回
        $input->SetOut_trade_no($data['out_trade_no']); //商户订单号
        isset($data['fee_type']) and $input->SetFee_type($data['fee_type']);      //标价币种 默认人民币：CNY
        $input->SetTotal_fee($data['total_fee']);   //标价金额 单位分
        $input->SetTime_start(date("YmdHis"));  //订单生成时间，格式为yyyyMMddHHmmss
        $input->SetTime_expire(date("YmdHis", time() + 600));   //订单失效时间，格式为yyyyMMddHHmmss
        isset($data['goods_tag']) and $input->SetGoods_tag($data['goods_tag']);           //订单优惠标记
        $input->SetNotify_url($this->config['notify_url']);
        $input->SetTrade_type("NATIVE");       //扫码支付
        $input->SetProduct_id($data['product_id']);
        $result = $this->GetPayUrl($input);
        if("FAIL" == $result['return_code']){
            return false;
        }else{
            $url = $result["code_url"];  //支付链接 (请将链接生成二维码)
            return $url;
        }
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
        //输出回wechat
        wechat\WxpayApi::replyNotify($WxPayNotifyReply->ToXml());
    }

    /**
     *  验证支付网站返回通知
     * @reutrn mixed false 代表失败
     */
    public function verify()
    {
        $result = wechat\WxPayApi::notify($msg);
        //查询订单 如果需要查询建议在外面查询顺便判断是否和数据库一致   $this->queryOrder()
//       $query_result =  $this->queryOrder($result);
//        if(array_key_exists("return_code", $query_result)
//            && array_key_exists("result_code", $query_result)
//            && $query_result["return_code"] == "SUCCESS"
//            && $query_result["result_code"] == "SUCCESS")
//        {
//            return $result;
//        }else{
//            return false;
//        }
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
     *   生成扫描支付URL,模式一
     * * 流程：
     * 1、组装包含支付信息的url，生成二维码
     * 2、用户扫描二维码，进行支付
     * 3、确定支付之后，微信服务器会回调预先配置的回调地址，在【微信开放平台-微信支付-支付配置】中进行配置
     * 4、在接到回调通知之后，用户进行统一下单支付，并返回支付信息以完成支付（见：native_notify.php）
     * 5、支付完成之后，微信服务器会通知支付成功
     * 6、在支付成功通知中需要查单确认是否真正支付成功（见：notify.php）
     * @param $productId
     * @return string
     * @internal param wechat\Data\WxPayBizPayUrl $bizUrlInfo
     */
    public function GetPrePayUrl($productId)
    {
        $biz = new wechat\Data\WxPayBizPayUrl();
        $biz->SetProduct_id($productId);
        $values = wechat\WxPayApi::bizpayurl($biz);
        $url = "weixin://wxpay/bizpayurl?" . $this->ToUrlParams($values);
        return $url;
    }

    /**
     *
     * 生成直接支付url，支付url有效期为2小时,模式二
     *  * 流程：
     * 1、调用统一下单，取得code_url，生成二维码
     * 2、用户扫描二维码，进行支付
     * 3、支付完成之后，微信服务器会通知支付成功
     * 4、在支付成功通知中需要查单确认是否真正支付成功（见：notify.php）
     * @param wechat\Data\UnifiedOrderInput $input
     * @return array
     */
    public function GetPayUrl($input)
    {
        if($input->GetTrade_type() == "NATIVE")
        {
            $result = wechat\WxPayApi::unifiedOrder($input);
            return $result;
        }
    }

    /**
     *  模式1 确定支付之后，微信服务器会回调预先配置的回调地址，在【微信开放平台-微信支付-支付配置】中进行配置
     * @param boolean  $needSign 微信返回数据
     * @return mixed
     */
    public function nativeNotify($needSign = true){
        $msg = "OK";
        $WxPayNotifyReply = new wechat\Data\WxPayNotifyReply();
        $data = wechat\WxPayApi::notify($msg);
        //验签
        if($data == false){
            $WxPayNotifyReply->SetReturn_code("FAIL");
            $WxPayNotifyReply->SetReturn_msg($msg);
        } else {

            if(!array_key_exists("openid", $data) ||
                !array_key_exists("product_id", $data))
            {
//            $msg = "回调数据异常";
                return false;
            }
            //统一下单
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
            $input->SetTrade_type("NATIVE");       //扫码支付
            $input->SetOpenid($data["openid"]);
            $input->SetProduct_id($data["product_id"]);
            $result = wechat\WxPayApi::unifiedOrder($input);
            if(!array_key_exists("appid", $result) ||
                !array_key_exists("mch_id", $result) ||
                !array_key_exists("prepay_id", $result))
            {
//            $msg = "统一下单失败";
                return false;
            }
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
     *  直接调用api支付
     * @param array 直接生成支付
     */
    public function pay($data)
    {
        return $this->getCode($data);
    }


    /**
     *  查询订单
     * @params array
     * @throws wechat\WxPayException
     * @return array 查询结果
     */
    public function queryOrder($params)
    {
        $input = new wechat\Data\WxPayOrderQuery();
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
     * url生成二维码
     * @param $url
     * @param bool $outfile
     * @param int $level
     * @param int $size
     * @param int $margin
     */
    public function getQrCode($url , $outfile = false, $level = QR_ECLEVEL_L ,$size = 3, $margin = 4){
        return QRcode::png($url, $outfile, $level , $size, $margin);
    }
}
