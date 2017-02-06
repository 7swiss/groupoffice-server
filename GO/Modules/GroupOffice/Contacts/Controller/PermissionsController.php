<?php
namespace GO\Modules\GroupOffice\Contacts\Controller;

use GO\Core\Auth\Permissions\Controller\GroupAccessPermissionsController;
use GO\Modules\GroupOffice\Contacts\Model\ContactGroup;

class PermissionsController extends GroupAccessPermissionsController {	
	public function getGroupRecordClassName() {
		return ContactGroup::class;
	}
}
