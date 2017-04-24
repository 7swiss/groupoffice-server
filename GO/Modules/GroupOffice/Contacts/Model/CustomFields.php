<?php
namespace GO\Modules\GroupOffice\Contacts\Model;

use IFW\Auth\Permissions\ViaRelation;
use GO\Core\CustomFields\Model\CustomFieldsRecord;

class CustomFields extends CustomFieldsRecord{	


	/**
	 * 
	 * @var int
	 */							
	public $id;


	protected static function defineRelations() {
		
		self::hasOne('contact', Contact::class, ['id' => 'id']);
		parent::defineRelations();
	}

	
}