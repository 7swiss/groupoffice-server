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
				->crud('tasks', 'taskId')
				->get('tasks/assignees', 'assignees');
		
		$router->addRoutesFor(Controller\CommentController::class)
						->crud('tasks/:taskId/comments', 'commentId');
				
	}
	
	
	public function depends() {
		return [
		\GO\Modules\GroupOffice\Contacts\Module::class
		];
	}
	
}