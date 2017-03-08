<?php
namespace IFW\Auth\Permissions;

/**
 * Permissions model
 * 
 * Only owner's and admins can do anything. By default the owner is identified
 * byt th column *createdBy* but you can change that bu setting $userIdField.
 */
class CreatorOnly extends Model {
	
	public $userIdField = 'createdBy';
	
	protected function internalCan($permissionType, \IFW\Auth\UserInterface $user) {
		
		if($permissionType == self::PERMISSION_CREATE) {
			return true;
		}
		return $this->record->{$this->userIdField} == $user->id();
	}
	
	protected function internalApplyToQuery(\IFW\Orm\Query $query, \IFW\Auth\UserInterface $user) {
		$query->andWhere([$this->userIdField => $user->id()])->allowPermissionTypes([\IFW\Auth\Permissions\Model::PERMISSION_READ]);	
	}
}