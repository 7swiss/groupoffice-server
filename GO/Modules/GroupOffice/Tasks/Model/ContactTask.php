<?php
namespace GO\Modules\GroupOffice\Tasks\Model;

use GO\Core\Orm\Record;

class ContactTask extends Record {	
	
	/**
	 * 
	 * @var int
	 */							
	public $taskId;

	/**
	 * 
	 * @var int
	 */							
	public $contactId;
	
	protected static function defineRelations() {
		self::hasOne('task', Task::class, ['taskId' => 'id']);
	}
	
	protected static function internalGetPermissions() {
		return new \IFW\Auth\Permissions\ViaRelation('task');
	}

}

