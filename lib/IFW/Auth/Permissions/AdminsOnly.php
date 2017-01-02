<?php
namespace IFW\Auth\Permissions;

use IFW\Auth\UserInterface;

/**
 * Permissions model
 * 
 * Only Admins can use this model.
 */
class AdminsOnly extends Model {
	protected function internalCan($permissionType, UserInterface $user) {
		return false;
	}
}