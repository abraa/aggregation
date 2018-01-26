<?php
 /**
 * ====================================
 * thinkphp5
 * ====================================
 * Author: 1002571
 * Date: 2018/1/24 18:28
 * ====================================
 * File: Curl.php
 * ====================================
 */

namespace aggregation\lib\unit;


class Curl {

    public static $is_proxy = true; // 是否启用代理
    public static $proxy_ip = ''; // 234.234.234.234代理服务器地址
    public static $cookie_file;
    public static $user_agent = 'Mozilla/4.0 (compatible; MSIE 6.0; SeaPort/1.2; Windows NT 5.2; .NET CLR 1.1.4322)';
    public static $compression = 'gzip';
    public static $timeout = 30;

    //证书相关
    public static $cert_type = NULL;               //PEM
    public static $cert_file = NULL;               //data/cert.pem
    public static $cert_passwrod = NULL;           //
    public static $ssl_key_file = NULL;            //data/private.pem
    public static $ssl_key_type = NULL;            //PEM
    public static $ca_file = NULL;                 //ca证书

    /**
     * 模拟GET方式获取
     * @param $url 请求链接
     * @return mixed
     */
    public static function get($url)
    {
        $curl = curl_init();
        if (self::$is_proxy) {
            curl_setopt($curl, CURLOPT_PROXY, self::$proxy_ip);
        }
        if (self::$cookie_file) {
            curl_setopt($curl, CURLOPT_COOKIEFILE, self::$cookie_file); // 读取上面所储存的Cookie信息
        }
        curl_setopt($curl, CURLOPT_URL, $url); //
        //设置证书信息
        empty(self::$cert_file) or curl_setopt($curl, CURLOPT_SSLCERT, self::$cert_file);
        empty(self::$cert_passwrod) or curl_setopt($curl, CURLOPT_SSLCERTPASSWD, self::$cert_passwrod);
        empty(self::$cert_type) or curl_setopt($curl, CURLOPT_SSLCERTTYPE,  self::$cert_type);
        empty(self::$ssl_key_type) or curl_setopt($curl,CURLOPT_SSLKEYTYPE, self::$ssl_key_type);
        empty(self::$ssl_key_file) or curl_setopt($curl,CURLOPT_SSLKEY, self::$ssl_key_file);
        //设置CA
        if(!empty(self::$ca_file)) {
            // 对认证证书来源的检查，0表示阻止对证书的合法性的检查。1需要设置CURLOPT_CAINFO
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);
            curl_setopt($curl, CURLOPT_CAINFO, self::$ca_file);
        } else {
            // 对认证证书来源的检查，0表示阻止对证书的合法性的检查。1需要设置CURLOPT_CAINFO
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        }
        curl_setopt($curl, CURLOPT_USERAGENT, self::$user_agent); // 模拟用户使用的浏览器
        @curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
        curl_setopt($curl, CURLOPT_HTTPGET, 1); // 发送一个常规的Post请求
        curl_setopt($curl, CURLOPT_TIMEOUT, self::$timeout); // 设置超时限制防止死循环
        curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
        $res = curl_exec($curl);
        if (curl_errno($curl)) {
            //echo 'Error:' . curl_error($curl);
        }
        curl_close($curl);
        return $res;
    }


    /**
     * 模拟POST方式获取
     * @param string $url 请求连接
     * @param array|string $data 请求参数
     * @param array $httpHeader
     * @return mixed
     */
    public static function post($url, $data = [],$httpHeader = [])
    {
        $curl = curl_init();
        if (self::$is_proxy) {
            curl_setopt($curl, CURLOPT_PROXY, self::$proxy_ip);
        }
        if (self::$cookie_file) {
            curl_setopt($curl, CURLOPT_COOKIEFILE, self::$cookie_file); // 读取上面所储存的Cookie信息
        }
        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
        //设置证书信息
        empty(self::$cert_file) or curl_setopt($curl, CURLOPT_SSLCERT, self::$cert_file);
        empty(self::$cert_passwrod) or curl_setopt($curl, CURLOPT_SSLCERTPASSWD, self::$cert_passwrod);
        empty(self::$cert_type) or curl_setopt($curl, CURLOPT_SSLCERTTYPE,  self::$cert_type);
        empty(self::$ssl_key_type) or curl_setopt($curl,CURLOPT_SSLKEYTYPE, self::$ssl_key_type);
        empty(self::$ssl_key_file) or curl_setopt($curl,CURLOPT_SSLKEY, self::$ssl_key_file);
        //设置CA
        if(!empty(self::$ca_file)) {
            // 对认证证书来源的检查，0表示阻止对证书的合法性的检查。1需要设置CURLOPT_CAINFO
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);
            curl_setopt($curl, CURLOPT_CAINFO, self::$ca_file);
        } else {
            // 对认证证书来源的检查，0表示阻止对证书的合法性的检查。1需要设置CURLOPT_CAINFO
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        }
        curl_setopt($curl, CURLOPT_USERAGENT, self::$user_agent); // 模拟用户使用的浏览器
        @curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
        curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
        if(!empty($httpHeader)){
            curl_setopt($curl, CURLOPT_HTTPHEADER, $httpHeader); // HTTPHEADER
        }
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包
        curl_setopt($curl, CURLOPT_TIMEOUT, self::$timeout); // 设置超时限制防止死循环
        curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
        $res = curl_exec($curl);
        if (curl_errno($curl)) {
            //echo 'Error:' . curl_error($curl);
        }
        curl_close($curl);
        return $res;
    }

    /**
     * 以post方式提交xml到对应的接口url
     * @param string $xml  需要post的xml数据
     * @param string $url  URL地址
     * @return bool|mixed
     */
    static public function postXml($xml, $url) {
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_USERAGENT, self::$user_agent); // 模拟用户使用的浏览器
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, self::$user_agent);
        curl_setopt($ch, CURLOPT_ENCODING, self::$compression);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        if(self::$proxy_ip) curl_setopt($ch,CURLOPT_PROXY, self::$proxy_ip);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,FALSE);
        curl_setopt($ch, CURLOPT_POST, 1);
        if(!empty(self::$cert_type) && !empty(self::$cert_file)){
            curl_setopt($ch,CURLOPT_SSLCERTTYPE, self::$cert_type);
            curl_setopt($ch,CURLOPT_SSLCERT, self::$cert_file);
        }
        if(!empty(self::$ssl_key_type) && !empty(self::$ssl_key_file)){
            curl_setopt($ch,CURLOPT_SSLKEYTYPE, self::$ssl_key_type);
            curl_setopt($ch,CURLOPT_SSLKEY, self::$ssl_key_file);
        }
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }


}