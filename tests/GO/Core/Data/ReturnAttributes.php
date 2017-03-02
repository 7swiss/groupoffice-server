<?php

namespace IFW\Data;

use PHPUnit\Framework\TestCase;


class Test extends \IFW\Model {
	public $name = "Test";
	
	public $prop1 = "value 1";
	
	public $another;
	
	public $array = [];
}


/**
 * The App class is a collection of static functions to access common services
 * like the configuration, reqeuest, debugger etc.
 */
class ReturnAttributesTest extends \PHPUnit\Framework\TestCase{
	function testInit(){
		
		$test = new Test();		
		$test->another = new Test();		
		$test->array = [new Test(), new Test()];

		var_dump($test->toArray("*,another[name,prop1]"));

		
	}
}