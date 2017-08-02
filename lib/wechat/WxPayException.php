<?php
/**
 * 
 * 微信支付API异常类
 * @author widyhu
 *
 */
namespace Lib\Wechat;
use \Lib\PayException;

class WxPayException extends PayException{
	public function errorMessage()
	{
		return $this->getMessage();
	}
}
