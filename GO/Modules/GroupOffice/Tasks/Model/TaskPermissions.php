<?php
namespace GO\Modules\GroupOffice\Tasks\Model;

use IFW\Auth\Permissions\Model;
use IFW\Auth\UserInterface;
use IFW\Orm\Query;

/**
 * You may see user tasks if you share a group. Group Everyone is excluded
 */
class TaskPermissions extends Model {
	
	protected function internalCan($permissionType, UserInterface $user) {
		return $user->id() == $this->record->assignedTo || $user->id() == $this->record->createdBy;		
	}
	
	protected function internalApplyToQuery(Query $query, UserInterface $user) {
		$query->andWhere(['OR','=',['assignedTo' => $user->id(), 'createdBy' => $user->id()]]);
	}

}
