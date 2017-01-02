<?php
namespace GO\Core\Users\Model;

use GO\Core\Modules\Model\ModuleManagerPermissions;

class UsersModulePermissions extends ModuleManagerPermissions {
	
	/**
	 * Allows adding of new contacts and companies
	 */
	const PERMISSION_MANAGE = "manage";
	
	protected function definePermissionTypes() {
		return [self::PERMISSION_READ, self::PERMISSION_MANAGE];
	}

	

}
