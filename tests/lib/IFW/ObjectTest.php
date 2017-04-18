<?php

namespace IFW;

use IFW\Data\Model;

class TestModel extends Model {

	private $test;

	public function setTest($value) {
		$this->test = $value;
	}

	public function getTest() {
		return $this->test;
	}

}

/**
 * The App class is a collection of static functions to access common services
 * like the configuration, reqeuest, debugger etc.
 */
class ModelTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @expectedException        Exception
	 *
	 */
	public function testGetNotExistingProperty() {


		$model = new TestModel();
		$value = $model->notExistingProperty;
	}

	/**
	 * @expectedException        Exception
	 *
	 */
	public function testSetNotExistingProperty() {

		$model = new TestModel();
		$model->notExistingProperty = "test";
	}

	public function testSetterAndGetter() {
		$model = new TestModel();

		$this->assertEquals(isset($model->test), false);

		$model->test = "test";

		$this->assertEquals(isset($model->test), true);

		$this->assertEquals($model->test, "test");
	}

}
