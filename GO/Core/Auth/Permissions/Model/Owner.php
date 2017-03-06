<?php

namespace GO\Core\Auth\Permissions\Model;

use GO\Core\Users\Model\UserGroup;
use IFW\Auth\Permissions\Model;
use IFW\Auth\UserInterface;
use IFW\Orm\Query;

/**
 * Group permissions model
 * 
 * You can use this model if you want to limit access to an item and grant 
 * access to one or more groups.
 * 
 * 1. Create a link table between your record and {@see \GO\Core\Users\Model\Group}. 
 * 2. Create the record model and make this record extend {@see GroupAccess}. 
 *		For example see {@see \GO\Modules\GroupOffice\Contacts\Model\ContactGroup}
 * 3. Use the this model in the record that you want to secure. For example see 
 *		{@see \GO\Modules\GroupOffice\Contacts\Model\Contact::internalGetPermissions()}
 *		``````````````````````````````````````````````````````````````````````````
 *		protected static function internalGetPermissions() {
 *		  return new \GO\Core\Auth\Permissions\Model\GroupPermissions(ContactGroup::class);
 *	  }	
 * 		``````````````````````````````````````````````````````````````````````````
 * 4. Define a 'groups' relation to the new GroupAccess record in the record you 
 *		want to secure in function defineRelations():
 *		``````````````````````````````````````````````````````````````````````````
 *		self::hasMany('groups', ContactGroup::class, ['id' => 'contactId']);
 *		``````````````````````````````````````````````````````````````````````````
 * 5. By default there are the properties write and delete. If you don't add them
 *		to your database they will always be false.
 * 
 */
class Owner extends Model {	
		
	private $userGroup;

	
	protected function internalCan($permissionType, UserInterface $user) {		
		switch($permissionType) {
			case self::PERMISSION_CREATE:
				return true;
			default:
				return $this->getUserGroup($user) != false;			
		}

		return false;
	}
	
	private function getUserGroup($user) {
		if(!isset($this->userGroup)) {			
			
			return $this->userGroup = UserGroup::find(['groupId' => $this->record->ownedBy, 'userId' => $user->id()])->single();
		}
		
		return $this->userGroup;
	}
	
	protected function internalApplyToQuery(Query $query, UserInterface $user) {
		
		$subQuery = UserGroup::find(
						(new Query())
						->tableAlias('ug')
						->where(['userId' => $user->id()])
						->andWhere('ug.groupId = '.$query->getTableAlias().'.ownedBy')
						);
		
		$query->allowPermissionTypes([\IFW\Auth\Permissions\Model::PERMISSION_READ])
						->andWhere(['EXISTS', $subQuery]);
		
//		->allowPermissionTypes([\IFW\Auth\Permissions\Model::PERMISSION_READ])
//						->joinRelation('groupPermissions')->debug()
//						->joinRelation(
//										'groupPermissions.groupUsers', 
//										false, 
//										'INNER', 
//										['groupUsers.userId' => $user->id()]
//										)
//						->groupBy(['t.id']);
	}

}

