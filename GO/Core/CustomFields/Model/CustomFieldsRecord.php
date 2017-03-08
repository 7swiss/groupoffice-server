<?php
namespace GO\Core\CustomFields\Model;

use IFW\Orm\Record;

/**
 * Custom fields record
 * 
 * If you implement custom fields for a record you must create a has one relation
 * to a record that extends this abstract record class.
 * 
 * It allows dynamic attributes.
 * 
 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
abstract class CustomFieldsRecord extends Record {

	private $attributes;

	public function __set($name, $value) {
		$this->attributes[$name] = $value;
	}

	public function __get($name) {
		if (array_key_exists($name, $this->attributes)) {
			return $this->attributes[$name];
		}

		$trace = debug_backtrace();
		trigger_error(
						'Undefined property via __get(): ' . $name .
						' in ' . $trace[0]['file'] .
						' on line ' . $trace[0]['line'], E_USER_NOTICE);
		return null;
	}

	public function __isset($name) {
		return isset($this->attributes[$name]);
	}
	
	public function __unset($name) {
		unset($this->attributes[$name]);
	}
	
	public static function getDefaultReturnProperties() {
		return implode(',', self::getTable()->getColumnNames());
	}
}