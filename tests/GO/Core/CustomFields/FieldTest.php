<?php

use GO\Core\CustomFields\Model\Field;
use GO\Core\CustomFields\Model\FieldSet;
use GO\Modules\GroupOffice\Contacts\Model\Contact;
use GO\Modules\GroupOffice\Contacts\Module;
use GO\Utils\ModuleCase;

class FieldTest extends ModuleCase {

	public static function module() {
		return Module::class;
	}

	function testField() {
		$field = Field::findByDbName(\GO\Modules\GroupOffice\Contacts\Model\CustomFields::class, 'testfield');
		if ($field) {
			$field->deleteHard();
		}
		
		$fieldSet = FieldSet::findOrCreate(\GO\Modules\GroupOffice\Contacts\Model\CustomFields::class, 'Test fieldset');
		
		$field = new Field();
		$field->fieldSet = $fieldSet;
		$field->type = Field::TYPE_NUMBER;
		$field->databaseName = 'testfield';
		$field->name = 'Test Field';
		$success = $field->save();

		$this->assertEquals(true, $success);
	}

}
