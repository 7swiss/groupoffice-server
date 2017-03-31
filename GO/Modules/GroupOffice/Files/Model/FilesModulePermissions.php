<?php
namespace GO\Modules\GroupOffice\Files\Model;

use GO\Core\Modules\Model\ModuleManagerPermissions;

class FilesModulePermissions extends ModuleManagerPermissions {

	/**
	 * Allows adding of new contacts and companies
	 */
	const PERMISSION_MANAGE_DRIVES = "manageDrives";
	const PERMISSION_MOUNT_DRIVES = "mountDrives";

	protected function definePermissionTypes() {
		return [self::PERMISSION_READ, self::PERMISSION_MANAGE_DRIVES, self::PERMISSION_MOUNT_DRIVES];
	}



}