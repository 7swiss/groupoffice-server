<?php

namespace GO\Modules\GroupOffice\CardDAVClient;

use GO\Core\Modules\Model\InstallableModule;
use GO\Modules\GroupOffice\CardDAVClient\Controller\SyncController;
use GO\Modules\GroupOffice\Contacts\Module as ContactsModule;

class Module extends InstallableModule {
	
	public static function defineWebRoutes(\IFW\Web\Router $router) {
		$router->addRoutesFor(SyncController::class)						
						->get('carddav-sync/test', 'test');
	
	}



	public function depends() {
		return [
				ContactsModule::class
		];
	}

}
