<?php

namespace GO\Modules\GroupOffice\Contacts\Model;

use GO\Modules\GroupOffice\Contacts\Module;
use IFW\Auth\Permissions\Model;
use IFW\Auth\UserInterface;
use IFW\Orm\Query;

/**
 * Contact permissions
 * 
 * Every contact can be shared with one group. Because every user has a private 
 * group and there's an everyone group this gives 3 options:
 * 
 * 1. private
 * 2. public
 * 3. A specific group
 * 
 * Contacts are always shared writable but only the creator or admin may change the group.
 */
class ContactPermissions extends Model {
	
		
	private $contactGroup;
	
	protected function internalCan($permissionType, UserInterface $user) {
		
		//owner can do all
		if($this->record->createdBy == $user->id()) {
			return true;
		}		
		
		switch($permissionType) {
			case self::PERMISSION_CREATE:
				$module = new Module();
				return $module->getPermissions()->can(ContactsModulePermissions::PERMISSION_CREATE_CONTACTS);
				
			case self::PERMISSION_READ:
				return $this->getContactGroup($user) != false;			
				
			case self::PERMISSION_UPDATE:
				return $this->getContactGroup($user) != false && $this->getContactGroup($user)->write;
				
			case self::PERMISSION_DELETE:
				return $this->getContactGroup($user) != false && $this->getContactGroup($user)->delete;
					
			default:
				return false;
		}

		return false;
	}
	
	private function getContactGroup($user) {
		if(!isset($this->contactGroup)) {
			return $this->contactGroup = ContactGroup::find((new Query())
							->joinRelation('groupUsers')
							->andWhere(['contactId' => $this->record->id])							
							->andWhere(['groupUsers.userId' => $user->id()])
							)->single();
		}
		
		return $this->contactGroup;
	}
	
	protected function internalApplyToQuery(Query $query, UserInterface $user) {
		
		$groupAccess = ContactGroup::find(
						(new Query())
						->tableAlias('groupAccess')						
						->joinRelation('groupUsers')
						->where(['groupUsers.userId' => $user->id()])
						->andWhere('groupAccess.contactId = t.id')
						);
		
		$query->skipReadPermission()
						->andWhere(['EXISTS', $groupAccess]);
		
//		$query->skipReadPermission()
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
