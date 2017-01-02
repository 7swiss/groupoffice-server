<?php
namespace IFW\Auth\Permissions;

use IFW\Auth\UserInterface;

/**
 * Permissions for users and groups
 * 
 * Everybody may read and write them.
 */
class Everyone extends Model {
	protected function internalCan($permissionType, UserInterface $user) {
			return true;
	}
//	
//	public function toArray($properties = null) {
//		return null;
//	}
}