<?php

namespace GO\Modules\GroupOffice\Files;

use GO\Core\Modules\Model\InstallableModule;
use GO\Modules\GroupOffice\Files\Controller\NodeController;
use GO\Modules\GroupOffice\Files\Controller\DriveController;
use IFW\Web\Router;

class Module extends InstallableModule {

	//TODO: implement
	public static $notifications = [
		 'fileShared', // A new file has been shared with the user
	];

	public function autoInstall() {
		return false;
	}

	public static function defineWebRoutes(Router $router) {

		$router->addRoutesFor(NodeController::class)
				  ->get('files', 'store')
				  //->get('files/:path/', 'store')
				  ->get('files/:id', 'read')
				  ->put('files/:id', 'update')
				  ->post('files', 'create')
				  ->delete('files/:id', 'delete');
		//->get("files/*path", 'store');

		$router->addRoutesFor(DriveController::class)
				  ->get('drives', 'store');
	}

}
