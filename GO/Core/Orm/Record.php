<?php
namespace GO\Core\Orm;

use Exception;
use GO\Core\Modules\Model\Module;
use GO\Core\Orm\Model\RecordType;
use IFW\Orm\Record as IFWRecord;

abstract class Record extends IFWRecord {	
	
	const LOG_ACTION_CREATE = 'create';
	const LOG_ACTION_READ = 'read';
	const LOG_ACTION_UPDATE = 'update';
	const LOG_ACTION_DELETE = 'delete';
	
	const NOTIFY_TYPE_CREATE = 'create';
	const NOTIFY_TYPE_UPDATE = 'update';
	const NOTIFY_TYPE_DELETE = 'delete';

	/**
	 * Get the record type
	 * 
	 * The record type is used for polymorphic relationships. Instead of storing
	 * the table or PHP class name we store the ID of the record type.
	 * 
	 * @return RecordType
	 * @throws Exception
	 */
	public static function getRecordType() {
		$recordType = RecordType::find(['name' => static::class])->single();
		
		if(!$recordType) {
			$recordType = new RecordType();
			$recordType->name = static::class;
			
			$moduleName = static::findModuleName();			
			
			$module = Module::find(['name'=>$moduleName])->single();			
			if($module) {
				$recordType->moduleId = $module->id;
			}
			
			if(!$recordType->save()) {
				throw new Exception("Could not create record type: ".static::class);
			}
		}
		
		return $recordType;
	}
}
