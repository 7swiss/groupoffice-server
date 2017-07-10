<?php

namespace GO\Modules\GroupOffice\Files;

use GO\Core\Modules\Model\InstallableModule;
use GO\Modules\GroupOffice\Files\Controller\NodeController;
use GO\Modules\GroupOffice\Files\Controller\DriveController;
use GO\Modules\GroupOffice\Files\Model\FilesModulePermissions;
use IFW\Web\Router;

class Module extends InstallableModule {

	//TODO: implement
	public static $notifications = [
		 'fileShared', // A new file has been shared with the user
	];

	public static function internalGetPermissions() {
		return new FilesModulePermissions();
	}

	public function autoInstall() {
		return true;
	}

	public static function defineWebRoutes(Router $router) {

		$router->addRoutesFor(NodeController::class)
			->get('files', 'store')
			//->get('files/:path/', 'store')
			->get('files/:id', 'read')
			->put('files/:id', 'update')
			->post('files', 'create')
			->post('files/:dirId', 'move')
			->delete('files/:id', 'delete');
		//->get("files/*path", 'store');

		$router->addRoutesFor(DriveController::class)
			->get('drives', 'store')
			->get('mounts', 'mountStore')
			->get('drives/:id', 'read')
			->put('drives/:id', 'update')
			->post('drives', 'create')
			->post('drives/:id/mount', 'mount');
	}
	
	

}
