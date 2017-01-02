<?php
namespace NH\Modules\Projects\Model;

class Compat extends \IFW\Data\Model {
	
	private $_record;
	
	public function __construct(\GO_Base_Db_ActiveRecord $record) {
		
		$this->_record = $record;
		
		parent::__construct();
	}
	
	public function getClassName() {
		return get_class($this->_record);
	}
	
	public function getValidationErrors() {
		return $this->_record->getValidationErrors();
	}
	
	public function getValidationError($key) {
		return $this->_record->getValidationError($key);
	}
	
	public function toArray($attributes = null) {
		
		return $this->_record->getAttributes();
	}
	public function __call($name, $arguments) {
		return call_user_func_array([$this->_record, $name], $arguments);
	}
	public function __get($name) {
		return $this->_record->{$name};
	}
	
	public function __isset($name) {
		return isset($this->_record->{$name});
	}
	
	public function __set($name, $value) {
		$this->_record->{$name} = $value;
	}
	
	public static function convertStatement($stmt) {
		
		$records = [];
		foreach($stmt as $record) {			
			$records[] = new self($record);			
		}		
		return $records;
	}
}