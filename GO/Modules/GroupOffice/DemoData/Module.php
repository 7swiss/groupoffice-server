<?php

namespace GO\Modules\GroupOffice\DemoData;

use GO\Core\Modules\Model\InstallableModule;
use GO\Modules\GroupOffice\DemoData\Controller\DemoDataController;
use IFW\Web\Router;

class Module extends InstallableModule {


	public static function defineWebRoutes(Router $router) {
		$router->addRoutesFor(DemoDataController::class)
						->get('demodata/create', 'create');
						
	}
}
