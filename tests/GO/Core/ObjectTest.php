<?php

namespace IFW;

use IFW\Data\Model;

class TestModel extends Model{

	private $_test;

	public function setTest($value){
		$this->_test=$value;
	}

	public function getTest(){
		return $this->_test;
	}
}

/**
 * The App class is a collection of static functions to access common services
 * like the configuration, reqeuest, debugger etc.
 */
class ModelTest extends \PHPUnit_Framework_TestCase{

	/**
	 * @expectedException        Exception
	 *
	 */

	public function testGetNotExistingProperty(){


		$model = new TestModel();
		$value = $model->notExistingProperty;


	}


	/**
	 * @expectedException        Exception
	 *
	 */

	public function testSetNotExistingProperty(){

		$model = new TestModel();
		$model->notExistingProperty="test";

	}


	public function testSetterAndGetter(){
		$model = new TestModel();

		$this->assertEquals(isset($model->test), false);

		$model->test="test";

		$this->assertEquals(isset($model->test), true);

		$this->assertEquals($model->test, "test");
	}
}
