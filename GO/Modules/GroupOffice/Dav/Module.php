<?php
namespace GO\Modules\GroupOffice\Dav;

use GO\Core\Modules\Model\InstallableModule;
use GO\Modules\GroupOffice\Contacts\Module as ContactsModule;
use GO\Modules\GroupOffice\Dav\Controller\SyncController;
use IFW\Cli\Router;

class Module extends InstallableModule {

	public function depends() {
		return [ContactsModule::class];
	}
	
	public static function defineCliRoutes(Router $router) {
		$router->addRoutesFor(SyncController::class)						
						->set('dav/sync/test', 'test');
	
	}
}