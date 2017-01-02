<?php

namespace GO\Modules\GroupOffice\Tasks;

use GO\Core\Modules\Model\InstallableModule;
use GO\Modules\GroupOffice\Tasks\Controller\TaskController;
use IFW\Web\Router;

class Module extends InstallableModule{	
	
	/**
	 * Allows adding of new contacts and companies
	 */
//	const ACTION_CREATE_TASKS = "createTasks";
	
	public static function defineWebRoutes(Router $router){
		
		$router->addRoutesFor(TaskController::class)
				->get('tasks', 'store')
				->get('tasks/0','new')
				->get('tasks/:taskId','read')
				->put('tasks/:taskId', 'update')
				->post('tasks', 'create')
				->delete('tasks/:taskId','delete')
				->get('tasks/comments/:taskId', 'comments');
				
	}
	
	
	public function depends() {
		return [
		\GO\Modules\GroupOffice\Contacts\Module::class
		];
	}
	
}