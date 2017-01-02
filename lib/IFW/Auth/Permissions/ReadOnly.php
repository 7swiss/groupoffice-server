<?php
namespace IFW\Auth\Permissions;

use IFW\Auth\UserInterface;

/**
 * Permissions for users and groups
 * 
 * Everybody may read them but only admins can write them.
 */
class ReadOnly extends Model {
	protected function internalCan($permissionType, UserInterface $user) {
		
		if($permissionType == self::PERMISSION_READ) {
			return true;
		}		
		
		return false;
	}
}