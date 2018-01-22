<?php
/**
 * 
 * 微信支付API异常类
 * @author widyhu
 *
 */
namespace aggregation\lib\wechat;
use \aggregation\lib\PayException;

class WxPayException extends PayException{
	public function errorMessage()
	{
		return $this->getMessage();
	}
}
