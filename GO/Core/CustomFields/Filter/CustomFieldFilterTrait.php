<?php
namespace GO\Core\CustomFields\Filter;	

use GO\Core\CustomFields\Model\Field;

trait CustomFieldFilterTrait {

	/**
	 *
	 * @var Field
	 */
	protected $field;
	
	public function setField(Field $field) {
		$this->field = $field;
	}
	
	public function getName() {
		return $this->field->databaseName;
	}
	
	public function getLabel() {
		return $this->field->name;
	}
}