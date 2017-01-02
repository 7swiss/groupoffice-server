<?php

namespace GO\Modules\GroupOffice\DemoData;

use GO\Core\Modules\Model\InstallableModule;
use GO\Modules\GroupOffice\DemoData\Controller\DemoDataController;

class Module extends InstallableModule {

	public function routes() {		
		DemoDataController::routes()
						->get('demodata/create', 'create');
	}

}
