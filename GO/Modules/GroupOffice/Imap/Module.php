<?php
namespace GO\Modules\GroupOffice\Imap;

use GO\Core\Modules\Model\InstallableModule;
use GO\Modules\GroupOffice\Imap\Controller\AccountController;
use IFW\Cli\Router as Router2;
use IFW\Web\Router;

class Module extends InstallableModule {
	public static function defineWebRoutes(Router $router) {
//		$router->addRoutesFor(AccountController::class)
//						->crud('imap/accounts', 'accountId')
//						->get('imap/resyncmessage/:messageId', 'resyncmessage');
//
//		$router->addRoutesFor(AccountController::class)
//						->get('imap/sync/:accountId', 'sync');

		
		$router->addRoutesFor(Controller\AutoDetectController::class)
						->get('imap/autodetect', 'newInstance')
						->post('imap/autodetect', 'detect');

	}

	
	public function depends() {
		return [
		\GO\Modules\GroupOffice\Messages\Module::class
		];
	}
	
	public function autoInstall() {
		return false;
	}
}

