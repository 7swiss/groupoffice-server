<?php

namespace GO\Core\Install\Model;

use IFW\Data\Model;

/**
 * System check result model
 * 
 * It contains a boolean and feedback.
 */
class Check extends Model {

	public $success = true;
	public $message = "OK";
	public $name;
	
	private $callable;
	
	public function __construct($name, callable $callable) {
		parent::__construct();
		
		$this->callable = $callable;
		$this->name = $name;
	}

	
	public function run(){
		call_user_func($this->callable, $this);
	}

}
