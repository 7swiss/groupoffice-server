<?php

namespace GO\Core\Auth\Permissions\Model;

use Exception;
use IFW\Auth\Permissions\Model;
use IFW\Auth\UserInterface;

/**
 * GroupAccess permissions model. Used by {@see GroupAccess}
 */
class GroupAccessPermissions extends Model {

	private $relationName;

	public function __construct($relationName) {
		$this->relationName = $relationName;
	}

	protected function internalCan($permissionType, UserInterface $user) {

		if ($permissionType == self::PERMISSION_READ) {
			return true;
		}

		$relationName = $this->relationName;

		$relatedRecord = $this->record->{$relationName};
		if (!isset($relatedRecord)) {
			throw new Exception("Relation $relationName is not set in " . $this->record->getClassName() . ", Maybe you didn't select or set the key?");
		}

		//don't edit owner record
		if (!$this->record->isNew() && $this->record->groupId == $relatedRecord->ownedBy) {
			return false;
		}

		return $relatedRecord->permissions->can(self::PERMISSION_MANAGE, $user);
	}	
}
