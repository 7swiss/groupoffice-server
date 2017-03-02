<?php
namespace GO\Core\Users\Model;

use GO\Core\Users\Model\User as UserModel;
use IFW\Auth\Permissions\ReadOnly;
use IFW\Auth\UserInterface;
use IFW\Orm\Query;

/**
 * User permissions
 * 
 * Only admins or the user itself may modify the user.
 * 
 * You can see users if you're in the same group. Except for everyone.
 */
class UserPermissions extends ReadOnly {
	protected function internalCan($permissionType, UserInterface $user) {
		
		if(parent::internalCan($permissionType, $user)) {	
			return true;
		}			
				
		if($this->record instanceof UserModel && $user->id() == $this->record->id && !$this->record->isModified('groups')){
			return true;
		}
		
		$module = new \GO\Core\Users\Module();
		return $module->getPermissions()->can(UsersModulePermissions::PERMISSION_MANAGE);
		
	}

	protected function internalApplyToQuery(Query $query, UserInterface $user) {
		
		$query->joinRelation('userGroup.groupUsers')
						->distinct()
						->andWhere(['userGroup.groupUsers.userId' => $user->id])
						->andWhere(['!=',['userGroup.groupUsers.groupId' => Group::ID_EVERYONE]]);
	}
}