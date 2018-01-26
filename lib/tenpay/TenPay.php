<?php
 /**
 * ====================================
 * thinkphp5
 * ====================================
 * Author: 1002571
 * Date: 2018/1/24 18:26
 * ====================================
 * File: TenPay.php
 * ====================================
 */

namespace aggregation\lib\tenpay;


use aggregation\lib\unit\Curl;
use aggregation\lib\unit\Xml;

class TenPay {

    public $parameters  ;    //请求参数


    /**
     * 财付通回调验证结果
     * @param array $parameters
     * @return bool
     */
    public function response($parameters =[]){
        //判断签名
        if($this->isTenpaySign($parameters)) {
            $trade_state =$parameters["trade_state"];
            //交易模式,1即时到账
            $trade_mode = $parameters["trade_mode"];
            if (($trade_mode == '1' || $trade_mode == '2') && $trade_state == '0') {
                return true;
            }
        }
        return false;
    }

    /**
     * 检查通知是否来自财付通
     * @param $notify_id        通知id
     * @return bool
     */
    public function notify($notify_id){
        //通过通知ID查询，确保通知来至财付通
        //创建查询请求
        $queryReq = array(
            "partner"=>TenPayConfig::$partner,
            "notify_id"=>$notify_id,
        );
        $queryReq['sign'] = $this->createSign($queryReq);
        $url = $this->getRequestURL("https://gw.tenpay.com/gateway/simpleverifynotifyid.xml",$queryReq);
        //后台调用
        if($content = Curl::get($url)) {
            //设置结果参数
            $res = $this->parseXml($content);
            if($this->isTenpaySign($res) && $res["retcode"] == "0" ){
                //通过
                return true;
            }
        }
        return false;
    }

    protected function parseXml($content) {
        $xml = simplexml_load_string($content);
        $encode = Xml::getXmlEncode($content);
        $arr = array();
        if($xml && $xml->children()) {
            foreach ($xml->children() as $node){
                //有子节点
                if($node->children()) {
                    $k = $node->getName();
                    $nodeXml = $node->asXML();
                    $v = substr($nodeXml, strlen($k)+2, strlen($nodeXml)-2*strlen($k)-5);
                } else {
                    $k = $node->getName();
                    $v = (string)$node;
                }
                if($encode!="" && $encode != "UTF-8") {
                    $k = iconv("UTF-8", $encode, $k);
                    $v = iconv("UTF-8", $encode, $v);
                }
                $arr[$k] = $v;
            }
        }
        return $arr;
    }

    /**
     * 验证签名数组
     * @param array $parameters
     * @return bool
     */
    protected function isTenpaySign($parameters = []) {
        $parameters = json_decode(json_encode($parameters),true);
        $signPars = "";
        ksort($parameters);
        foreach($parameters as $k => $v) {
            if("sign" != $k && "" != $v) {
                $signPars .= $k . "=" . $v . "&";
            }
        }
        $signPars .= "key=" . TenPayConfig::$key;

        $sign = strtolower(md5($signPars));

        $tenpaySign = strtolower($parameters["sign"]);

        return $sign == $tenpaySign;

    }
    /**
     *重定向到财付通支付
     */
    public function doSend() {
        header("Location:" . TenPayConfig::$gatewayUrl);
        exit;
    }

    /**
     *创建md5摘要,规则是:按参数名称a-z排序,遇到空值的参数不参加签名。
     * @param array $parameters
     * @return string
     */
    public function createSign($parameters =[]) {
//        $parameters = json_decode(json_encode($parameters),true);
        $signPars = "";
        ksort($parameters);
        foreach($parameters as $k => $v) {
            if("" != $v && "sign" != $k) {
                $signPars .= $k . "=" . $v . "&";
            }
        }
        $signPars .= "key=" . TenPayConfig::$key;
        return strtolower(md5($signPars));
    }

    /**
     *获取带参数的请求URL
     * @param $url
     * @param array $parameters
     * @return string
     */
    protected function getRequestURL($url , $parameters =[]) {
        $reqPar = "";
        ksort($parameters);
        foreach($parameters as $k => $v) {
            $reqPar .= $k . "=" . urlencode($v) . "&";
        }
        //去掉最后一个&
        $reqPar = substr($reqPar, 0, strlen($reqPar)-1);
        $requestURL = $url . "?" . $reqPar;
        return $requestURL;
    }

    /**
     *获取参数值
     */
    function getParameter($parameter) {
        return $this->parameters[$parameter];
    }

    /**
     *设置参数值
     */
    function setParameter($parameter, $parameterValue) {
        $this->parameters[$parameter] = $parameterValue;
    }

    /**
     *获取所有请求的参数
     *@return array
     */
    function getAllParameters() {
        return $this->parameters;
    }
}