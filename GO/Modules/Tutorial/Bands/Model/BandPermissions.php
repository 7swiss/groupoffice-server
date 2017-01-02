<?php
namespace GO\Modules\Tutorial\Bands\Model;

use IFW\Auth\Permissions\Model;
use IFW\Auth\UserInterface;

class BandPermissions extends Model {
	protected function internalCan($permissionType, UserInterface $user) {
		
		return ($permissionType == self::PERMISSION_READ || $permissionType == self::PERMISSION_UPDATE);
		
	}
}
