<?php

namespace GO\Modules\GroupOffice\Contacts;

use GO\Core\Modules\Model\InstallableModule;
use GO\Modules\GroupOffice\Contacts\Controller\ContactController;
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
				->get('contacts/byuser/:userId','readByUser');
		
		$router->addRoutesFor(Controller\CommentController::class)
						->crud('contacts/:contactId/comments', 'commentId');
		

	}
	
	
	public function autoInstall() {
		return true;
	}
	
	
	public function depends() {
		return [
//			\GO\Modules\GroupOffice\Email\Module::class, //for activity view
//			\GO\Modules\GroupOffice\Tasks\Module::class  //..
		];
	}
	
}
