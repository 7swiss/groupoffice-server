<?php

namespace GO\Modules\Tutorial\Bands;

use GO\Core\Modules\Model\InstallableModule;
use IFW\Web\Router;
use GO\Modules\Tutorial\Bands\Controller\BandController;
use GO\Modules\Tutorial\Bands\Controller\HelloController;

/**
 * The bands module
 * 
 * A module for the tutorial.
 *
 * @copyright (c) 2015, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class Module extends InstallableModule {

	public static function defineWebRoutes(Router $router) {

		$router->addRoutesFor(HelloController::class)
						->get('bands/hello', 'name');
		
		$router->addRoutesFor(BandController::class)
						->get('bands', 'store')
						->get('bands/0', 'new')
						->get('bands/:bandId', 'read')
						->put('bands/:bandId', 'update')
						->post('bands', 'create')
						->delete('bands/:bandId', 'delete')
						->get('bands/filters', 'filters');
	}
}

