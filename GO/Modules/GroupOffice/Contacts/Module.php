<?php

namespace GO\Modules\GroupOffice\Contacts;

use GO\Core\Modules\Model\InstallableModule;
use GO\Modules\GroupOffice\Contacts\Controller\ContactController;
use GO\Modules\GroupOffice\Contacts\Controller\ContactThumbController;
use GO\Modules\GroupOffice\Contacts\Model\ContactsModulePermissions;
use IFW\Web\Router;

class Module extends InstallableModule{	
	
	public static function internalGetPermissions() {
		return new ContactsModulePermissions();
	}

	public static function defineWebRoutes(Router $router){
		
		$router->addRoutesFor(ContactController::class)
				->crud('contacts','contactId')
				->get('contacts/:contactId/vcard','vcard')
				->get('contacts/import/:blobId','import')
				->get('contacts/filters', 'filters')
				->get('contacts/byuser/:userId','readByUser');
				
		
		$router->addRoutesFor(ContactThumbController::class)
						->get('contacts/0/thumb', 'download')
						->get('contacts/:contactId/thumb', 'download')
						->get('contacts/byuser/:userId/thumb', 'download');
		
		$router->addRoutesFor(Controller\PermissionsController::class)
						->get('contacts/:contactId/permissions', 'store')
						->put('contacts/:contactId/permissions/:groupId', 'set')
						->delete('contacts/:contactId/permissions/:groupId', 'delete');
		
//		FilesController::defaultRoutes($router, 'contacts/:contactId');
	}
	
	
	public function depends() {
		return [
//			\GO\Modules\GroupOffice\Email\Module::class, //for activity view
//			\GO\Modules\GroupOffice\Tasks\Module::class  //..
		];
	}
	
}
