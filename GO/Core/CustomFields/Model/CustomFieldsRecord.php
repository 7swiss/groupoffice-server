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
abstract class CustomFieldsRecord extends \IFW\Orm\PropertyRecord {

	private $attributes;

	public function __set($name, $value) {
		
		if($this->getRelation($name)) {
			return $this->getRelated($name);
		}
		
		$this->attributes[$name] = $value;
	}

	public function __get($name) {
		if (array_key_exists($name, $this->attributes)) {
			return $this->attributes[$name];
		}else
		{
			return parent::__get($name);
		}
	
	}

	public function __isset($name) {
		if(isset($this->attributes[$name])) {
			return true;
		}else
		{
			return parent::__isset($name);
		}
		
	}
	
	public function __unset($name) {
		if(isset($this->attributes[$name])) {
			unset($this->attributes[$name]);
		}else
		{
			parent::__unset($name);
		}
	}
	
	public static function getDefaultReturnProperties() {
		return implode(',', self::getTable()->getColumnNames());
	}
}