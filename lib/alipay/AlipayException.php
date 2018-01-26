<?php
/**
 * 
 * 微信支付API异常类
 * @author widyhu
 *
 */
namespace aggregation\lib\alipay;
use \aggregation\lib\PayException;

class AlipayException extends PayException{
	public function errorMessage()
	{
		return $this->getMessage();
	}
}
