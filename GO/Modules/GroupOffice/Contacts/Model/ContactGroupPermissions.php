<?php
namespace GO\Modules\GroupOffice\Contacts\Model;

use IFW\Auth\Permissions\Model;
use IFW\Auth\UserInterface;


class ContactGroupPermissions extends Model {
	protected function internalCan($permissionType, UserInterface $user) {		
		
//		
//		if($action == self::ACTION_READ && $this->record->userId == GO()->getAuth()->user()->id()) {
//			return true;
//		}

		return $this->record->contact->getPermissions()->can(self::PERMISSION_CHANGE_PERMISSIONS, $user);
	}
}
