<?php
namespace GO\Modules\GroupOffice\Dav;
use GO\Core\Modules\Model\InstallableModule;

class Module extends InstallableModule {

	public function routes() {		
		Controller\ServerController::routes()						
				->addRoute('*', 'dav','dav', true);		
	}
	
	public function depends() {
		return [\GO\Modules\GroupOffice\Contacts\Module::class];
	}
}