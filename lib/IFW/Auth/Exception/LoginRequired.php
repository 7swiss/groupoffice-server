<?php
namespace IFW\Auth\Exception;

use Exception;

class LoginRequired extends Exception {
	
	public function __construct($message = "Login required", $code = 0, Exception $previous = null) {
		parent::__construct($message, $code, $previous);
	}
	
}