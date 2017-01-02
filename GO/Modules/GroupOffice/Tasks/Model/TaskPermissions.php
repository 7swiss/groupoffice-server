<?php
namespace GO\Modules\GroupOffice\Tasks\Model;

use GO\Core\Users\Model\Group;
use GO\Core\Users\Model\UserGroup;
use IFW\Auth\Permissions\Model;
use IFW\Auth\UserInterface;
use IFW\Orm\Query;
use PDO;

/**
 * You may see user tasks if you share a group. Group Everyone is excluded
 */
class TaskPermissions extends Model {
	
	protected function internalCan($permissionType, UserInterface $user) {
		return $user->id() == $this->record->assignedTo || $user->id() == $this->record->createdBy;		
	}
	
	

}
