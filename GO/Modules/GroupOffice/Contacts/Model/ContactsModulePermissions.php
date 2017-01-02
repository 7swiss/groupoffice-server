<?php
namespace GO\Modules\GroupOffice\Contacts\Model;

use GO\Core\Modules\Model\ModuleManagerPermissions;

class ContactsModulePermissions extends ModuleManagerPermissions {
	
	/**
	 * Allows adding of new contacts and companies
	 */
	const PERMISSION_CREATE_CONTACTS = "createContacts";
	
	protected function definePermissionTypes() {
		return [self::PERMISSION_READ, self::PERMISSION_CREATE_CONTACTS];
	}

	

}
