<?php
namespace aggregation\lib;
/**
 * 
 * 支付API异常类
 * @author abraa
 *
 */
class PayException extends \Exception {
	public function errorMessage()
	{
		return $this->getMessage();
	}
}
