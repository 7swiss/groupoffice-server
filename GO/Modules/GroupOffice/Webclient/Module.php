<?php
namespace GO\Modules\GroupOffice\Webclient;

use GO\Core\Modules\Model\InstallableModule;
use GO\Modules\GroupOffice\Webclient\Controller\CssController;
use IFW\Web\Router;
/**						
 * @copyright (c) 2017, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class Module extends InstallableModule {

	public static function defineWebRoutes(Router $router) {
		$router->addRoutesFor(CssController::class)
						->get('webclient/custom.css', 'download');
		
		$router->addRoutesFor(Controller\SettingsController::class)
						->get('webclient/settings', 'read')
						->put('webclient/settings', 'update');
	}
	
	public static function defineCliRoutes(\IFW\Cli\Router $router) {
		$router->addRoutesFor(Controller\LanguageController::class)
						->set('webclient/language/update-all', 'updateAll')
						->set('webclient/language/export-csv', 'exportCsv')
						->set('webclient/language/import-csv', 'importCsv');
	}
}