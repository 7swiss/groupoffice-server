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
	
	protected function internalApplyToQuery(\IFW\Orm\Query $query, UserInterface $user) {
		//Return no results
		$query->andWhere('0');
	}
}