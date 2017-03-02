<?php

namespace GO\Core\Modules\Model;

use IFW\Auth\Permissions\Model;
use IFW\Auth\UserInterface;
use IFW\Orm\Query;


/**
 * Module permissions
 * 
 * Modules store their permissions in a separate model {@see ModuleGroup}.
 * 
 * Modules can define differnt actions to grant permissions on with constants 
 * in their manager class. The constants are prefixed with ACTION_. 
 * eg.:
 * 
 * ```````````````````````````````````````````````````````````````````````````
 * const ACTION_CREATE_CONTACTS = 'createContacts';
 * ```````````````````````````````````````````````````````````````````````````
 * 
 * These can be checked like this:
 * 
 * ```````````````````````````````````````````````````````````````````````````
 * $can = \GO\Modules\Contacts\ContactsModule::model()
 *   ->getPermissions()
 *   ->can(\GO\Modules\Contacts\ContactsModule::ACTION_CREATE_CONTACTS);
 * ```````````````````````````````````````````````````````````````````````````
 */
class ModuleManagerPermissions extends Model {

	protected function internalCan($permissionType, UserInterface $user) {
		
		return ModuleGroup::find(
										(new Query())
														->joinRelation("userGroup", false)
														->where(['moduleId' => $this->record->id, 'userGroup.userId' => $user->id(), 'action' => $permissionType])
						)->single() != false;
	}
	
	/**
	 * Return permission types.
	 * 
	 * It can be a string for a writable permission ttype or an array with name and readonly as key for readonly properties
	 * 
	 * @return string[]|array[] eg [self::PERMISSION_READ, ['name' => self::PERMISSION_SPECIAL, 'readonly' => true]
	 */
	protected function definePermissionTypes() {
		return [self::PERMISSION_READ];
	}
}
