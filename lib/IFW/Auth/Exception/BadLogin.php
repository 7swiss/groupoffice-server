<?php
namespace IFW\Auth\Exception;

use Exception;

class BadLogin extends Exception {
	
	public function __construct($message = "Wron username or password", $code = 0, Exception $previous = null) {
		parent::__construct($message, $code, $previous);
	}
	
}
