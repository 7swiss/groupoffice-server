<?php

namespace GO\Modules\GroupOffice\ICalendar;

use GO\Core\Cron\Model\Job;
use GO\Core\Modules\Model\InstallableModule;
use GO\Core\Modules\Model\Module as ModuleModel;
use GO\Modules\GroupOffice\ICalendar\Model\Account;
use IFW\Web\Router;

class Module extends InstallableModule {

	public static function defineWebRoutes(Router $router) {

		$router->addRoutesFor(\GO\Modules\GroupOffice\ICalendar\Controller\AccountController::class)
						->crud('icalendar/accounts', 'accountId')
						->get('icalendar/sync', 'sync');
	}

	public function install(ModuleModel $moduleModel) {

		$cronJob = new Job();
		$cronJob->module = $moduleModel;
		$cronJob->name = 'iCalendar sync';
		$cronJob->cronClassName = Account::class;
		$cronJob->method = 'syncAll';
		$cronJob->cronExpression = '/5 * * * * *';
		$cronJob->save();
	}
	
	public function autoInstall() {
		return false;
	}

	public function depends() {
		return [\GO\Modules\GroupOffice\Tasks\Module::class];
	}


}
