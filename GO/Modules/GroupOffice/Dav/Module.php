<?php
namespace GO\Modules\GroupOffice\Dav;

use GO\Core\Modules\Model\InstallableModule;
use GO\Modules\GroupOffice\Contacts\Module as ContactsModule;
use GO\Modules\GroupOffice\Dav\Controller\SyncController;
use IFW\Cli\Router;
use IFW\Orm\Record;

class Module extends InstallableModule {

	public function depends() {
		return [ContactsModule::class];
	}
	
	public static function defineCliRoutes(Router $router) {
		$router->addRoutesFor(SyncController::class)						
						->set('dav/sync/test', 'test');
	
	}

	public static function defineEvents() {
		if(GO()->getModules()->has('GO\Modules\GroupOffice\Calendar\Module')) {
			\GO\Modules\GroupOffice\Calendar\Model\CalendarEvent::on(Record::EVENT_BEFORE_SAVE, Model\Event::class, 'onEventChange');
		}
	}

}