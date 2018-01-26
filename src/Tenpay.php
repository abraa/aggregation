<?php
 /**
 * ====================================
 * thinkphp5
 * ====================================
 * Author: 1002571
 * Date: 2018/1/24 17:01
 * ====================================
 * File: Tenpay.php
 * ====================================
 */

namespace aggregation\pay;


use aggregation\lib\tenpay\TenPayConfig;
use aggregation\pay\base\BasePay;

class Tenpay extends BasePay{

    public function __construct($data = [])
    {
        $this->init($data);
    }
    /**
     * 初始化对象
     * @param array
     */
    function init($config){
        $this->config = array_merge($this->config,$config);
        foreach($this->config as $key =>$value){
            TenPayConfig::$$key = $value;
        }
    }

    /**
     * 初始化对象所需参数数组
     * @return Array
     */
    function setup()
    {
        return array(
            "spname"=>array("type"=>"text","name"=>"应用名称","value"=>""),
            "partner"=>array("type"=>"text","name"=>"商户号","value"=>""),
            "key"=>array("type"=>"text","name"=>"密钥","value"=>""),
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
        //支付配置
        $return_url =  isset($data['return_url'])?$data['return_url']: $this->config['return_url'];			//显示支付结果页面
        $notify_url = isset($data['notify_url'])?$data['notify_url']: $this->config['notify_url'];				//支付完成后的回调处理页面
        $bank_id    = isset($data["bank_type"]) ? $data["bank_type"] : "DEFAULT";
        $trade_mode    = isset($data["trade_mode"]) ? $data["trade_mode"] : "1";
        $subject    = isset($data["subject"]) ? $data["subject"] : "";
        /* 支付方式 */
        /* 创建支付请求对象 */
        $reqHandler = new \aggregation\lib\tenpay\TenPay();
        //----------------------------------------
        //设置支付参数
        //----------------------------------------
        $reqHandler->setParameter("partner", TenPayConfig::$partner);
        $reqHandler->setParameter("out_trade_no",$data['out_trade_no']);  /* 获取提交的订单号 */
        $reqHandler->setParameter("total_fee", $data['total_fee']);  //总金额  fen

        $reqHandler->setParameter("return_url", $return_url);
        $reqHandler->setParameter("notify_url", $notify_url);
        $reqHandler->setParameter("body", $data['body']);        /* 商品描述 */
        $reqHandler->setParameter("bank_type", $bank_id);  	  //银行类型，默认为财付通
        //用户ip
        $reqHandler->setParameter("spbill_create_ip", $_SERVER['REMOTE_ADDR']);//客户端IP
        $reqHandler->setParameter("fee_type", "1");               //币种
        $reqHandler->setParameter("subject",$subject);          //商品名称，（中介交易时必填）

        //系统可选参数
        $reqHandler->setParameter("sign_type", "MD5");  	 	  //签名方式，默认为MD5，可选RSA
        $reqHandler->setParameter("service_version", "1.0"); 	  //接口版本号
        $reqHandler->setParameter("input_charset", "utf-8");   	  //字符集
        $reqHandler->setParameter("sign_key_index", "1");    	  //密钥序号

        //业务可选参数
        $reqHandler->setParameter("attach", "");             	  //附件数据，原样返回就可以了
        $reqHandler->setParameter("product_fee", "");        	  //商品费用
        $reqHandler->setParameter("transport_fee", "0");      	  //物流费用
//        $reqHandler->setParameter("time_start", date("YmdHis"));  //订单生成时间
//        $reqHandler->setParameter("time_expire", "");             //订单失效时间
        $reqHandler->setParameter("buyer_id", "");                //买方财付通帐号
        $reqHandler->setParameter("goods_tag", "");               //商品标记
        $reqHandler->setParameter("trade_mode",$trade_mode);              //交易模式（1.即时到帐模式，2.中介担保模式，3.后台选择（卖家进入支付中心列表选择））
        $reqHandler->setParameter("transport_desc","");              //物流说明
        $reqHandler->setParameter("trans_type","1");              //交易类型
        $reqHandler->setParameter("agentid","");                  //平台ID
        $reqHandler->setParameter("agent_type","");               //代理模式（0.无代理，1.表示卡易售模式，2.表示网店模式）
        $reqHandler->setParameter("seller_id","");                //卖家的商户号

        $params = $reqHandler->getAllParameters();
        $params['sign'] = $reqHandler->createSign($params);                   //sign

        $button  = '<form style="text-align:center;" action="'.TenPayConfig::$gatewayUrl.'" style="margin:0px;padding:0px" >';

        foreach ($params AS $key=>$val)
        {
            $button  .= "<input type='hidden' name='$key' value='$val' />";
        }
        $button  .= '<input id="tenpay_button" type="submit" value="财付通支付"></form><br />';

        return $button;
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
     * @param array $params
     * @param  array $callBack  回调函数
     * @return bool
     */
    public function notify($params = [],$callBack = [])
    {
        // TODO: Implement notify() method.
        $notify_id = $params['notify_id'];
        $payObj = new \aggregation\lib\tenpay\TenPay();
       if($payObj->response($params)){              //验证签名
           if($payObj->notify($notify_id)){         //检查是否来自财付通
                call_user_func($callBack);          //通过后续处理
               echo 'success';
               exit;
           };
       }
        echo 'error';
        return false;
    }

    /**
     *  验证签名
     * @param array $params
     * @return bool
     */
    public function verify($params =[])
    {
        // TODO: Implement verify() method.
        $payObj = new \aggregation\lib\tenpay\TenPay();
        return $payObj->response($params);
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