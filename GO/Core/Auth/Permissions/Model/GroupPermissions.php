<?php

namespace GO\Core\Auth\Permissions\Model;

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
class GroupPermissions extends Model {	
		
	private $groupAccess;
	
	private $groupAccessRecordName;
	
	public function __construct($groupAccessRecordName) {
		
		$this->groupAccessRecordName = $groupAccessRecordName;
		parent::__construct();
	}
	
	private function getGroupAccessRecordName() {
		return $this->groupAccessRecordName;
	}
	
	protected function internalCan($permissionType, UserInterface $user) {		
		switch($permissionType) {
			case self::PERMISSION_CREATE:
				return true;
				
			case self::PERMISSION_READ:
				return $this->getGroupAccess($user) != false;			
				
			case self::PERMISSION_UPDATE:
				return $this->getGroupAccess($user) != false && $this->getGroupAccess($user)->write;
				
			case self::PERMISSION_DELETE:
				return $this->getGroupAccess($user) != false && $this->getGroupAccess($user)->delete;
				
			case self::PERMISSION_CHANGE_PERMISSIONS:
				return $this->record->ownedBy == $user->group->id; //owner
					
			default:
				return false;
		}

		return false;
	}
	
	private function getGroupAccess($user) {
		if(!isset($this->groupAccess)) {
			
			$cls = $this->getGroupAccessRecordName();
			
			return $this->groupAccess = $cls::find((new Query())
							->joinRelation('groupUsers')
							->andWhere([$cls::getForPk() => $this->record->id])							
							->andWhere(['groupUsers.userId' => $user->id()])
							)->single();
		}
		
		return $this->groupAccess;
	}
	
	protected function internalApplyToQuery(Query $query, UserInterface $user) {
		
		$cls = $this->getGroupAccessRecordName();
		
		$groupAccess = $cls::find(
						(new Query())
						->tableAlias('groupAccess')						
						->joinRelation('groupUsers')
						->where(['groupUsers.userId' => $user->id()])
						->andWhere('groupAccess.'.$cls::getForPk().' = t.id')
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
