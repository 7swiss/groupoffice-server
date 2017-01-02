<?php

namespace GO\Modules\GroupOffice\CardDAVSync;

use GO\Core\Modules\Model\InstallableModule;
use GO\Modules\GroupOffice\CardDAVSync\Controller\SyncController;
use GO\Modules\GroupOffice\Contacts\Module as ContactsModule;

class Module extends InstallableModule {

	public function routes() {
		SyncController::routes()
						->get('carddav-sync/test', 'test');
	}

	public function depends() {
		return [
				ContactsModule::class
		];
	}

}
