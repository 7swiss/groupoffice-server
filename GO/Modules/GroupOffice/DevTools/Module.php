<?php

namespace GO\Modules\GroupOffice\DevTools;

use GO\Core\Modules\Model\InstallableModule;
use GO\Modules\GroupOffice\DevTools\Controller\ModelController;
use GO\Modules\GroupOffice\DevTools\Controller\RoutesController;

class Module extends InstallableModule {

	public static function defineWebRoutes(\IFW\Web\Router $router) {
		$router->addRoutesFor(ModelController::class)
						->get('devtools/models', 'list')
						->get('devtools/models/:modelName/props', 'props')
						
						->addRoute('*', 'devtools/test', 'test', true);
		
		
		$router->addRoutesFor(RoutesController::class)
						->get('devtools/routes', 'markdown');
	
	}
	
	public static function defineCliRoutes(\IFW\Cli\Router $router) {
//		$router->addRoutesFor(Controller\RecordTestController::class)
//						->set('devtools/record-test/benchmark', 'benchmark')
//						->set('devtools/record-test/convert', 'convert');
		
		$router->addRoutesFor(Controller\ModuleController::class)
						->set('devtools/module/init', 'init');
	}

}
