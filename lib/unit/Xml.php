<?php
 /**
 * ====================================
 * thinkphp5
 * ====================================
 * Author: 1002571
 * Date: 2018/1/23 15:36
 * ====================================
 * File: Xml.php
 * ====================================
 */
namespace aggregation\lib\unit;

class Xml {

    /**
     * 解析xm为数组
     * @param $xml
     * @return mixed
     * @throws \Exception
     */
    public static function FromXml($xml){
        if(!$xml){
            throw new \Exception("xml数据异常！");
        }
        //将XML转为array
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        return json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    }


    /**
     * 获取xml编码   UTF-8
     * @param $xml
     * @return string
     */
    public static function getXmlEncode($xml) {
        $ret = preg_match ("/<?xml[^>]* encoding=\"(.*)\"[^>]* ?>/i", $xml, $arr);
        if($ret) {
            return strtoupper ( $arr[1] );
        } else {
            return "";
        }
    }
}