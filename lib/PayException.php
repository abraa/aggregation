<?php
namespace Aggregation\Lib;
/**
 * 
 * 支付API异常类
 * @author abraa
 *
 */
class PayException extends Exception {
	public function errorMessage()
	{
		return $this->getMessage();
	}
}
