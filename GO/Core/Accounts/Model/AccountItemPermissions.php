<?php 
namespace GO\Core\Accounts\Model;

use IFW\Auth\Permissions\Model;
use IFW\Auth\Permissions\ViaRelation;
use IFW\Auth\UserInterface;
use IFW\Orm\Query;

/**
 * Permission model for account items
 * 
 */
class AccountItemPermissions extends ViaRelation {
	
	
	public function __construct($accountRelation = 'account') {
		parent::__construct($accountRelation);
	}
	
	
	protected function internalCan($permissionType, UserInterface $user) {		
		
		$relatedRecord = $this->getRelatedRecord();
		
		if($permissionType == self::PERMISSION_WRITE || $permissionType == self::PERMISSION_CREATE) {
			$permissionType = AccountPermissions::PERMISSION_WRITE_CONTENTS;
		}
		
		return $relatedRecord->permissions->can($permissionType, $user);
	}
	
	
	protected function internalApplyToQuery(Query $query, UserInterface $user) {
		$subQuery = (new Query())
						->tableAlias('groupAccess')						
						->joinRelation('groupUsers')
						->where(['groupUsers.userId' => $user->id()])
						->andWhere('groupAccess.accountId = '.$query->getTableAlias(). '.accountId');
		
		$groupAccess = AccountGroup::find($subQuery);
		
		$query->allowPermissionTypes([Model::PERMISSION_READ])
						->andWhere(['EXISTS', $groupAccess]);
	}


}
