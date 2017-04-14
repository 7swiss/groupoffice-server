<?php

namespace IFW\Data;

use PHPUnit\Framework\TestCase;

class Test extends Model {

	public $name = "Test";
	public $prop1 = "value 1";
	public $another;
	public $array = [];

}

/**
 * The App class is a collection of static functions to access common services
 * like the configuration, reqeuest, debugger etc.
 */
class ReturnAttributesTest extends TestCase {

	public function testInit() {

		$test = new Test();
		$test->another = new Test();
		$test->array = [new Test(), new Test()];

		$array = $test->toArray("*,another[name,prop1]");
		
		$this->assertArrayHasKey('another', $array);
		
		$this->assertArrayHasKey('prop1', $array['another']);
	}

}
