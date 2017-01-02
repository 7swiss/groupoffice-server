<?php
namespace GO\Core\Tags\Model;

use IFW\Auth\Permissions\Model;
use IFW\Auth\UserInterface;

class TagPermissions extends Model {
	protected function internalCan($permissionType, UserInterface $user) {
		return $permissionType == self::PERMISSION_CREATE || $permissionType == self::PERMISSION_READ;
	}
}