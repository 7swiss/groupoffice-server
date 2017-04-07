<?php

namespace GO\Core\Users;

use GO\Core\Modules\Model\InstallableModule;
use GO\Core\Modules\Model\Module as CoreModule;
use GO\Core\Users\Controller\ForgotPasswordController;
use GO\Core\Users\Controller\GroupController;
use GO\Core\Users\Controller\UserController;
use GO\Core\Users\Model\UsersModulePermissions;
use IFW\Web\Router;

class Module extends InstallableModule {
	
	protected static function internalGetPermissions() {
		return new UsersModulePermissions();
	}

	public static function defineWebRoutes(Router $router) {
		
	
		$router->addRoutesFor(UserController::class)
						->get('auth/users', 'store')
						->get('auth/users/0', 'new')
						->get('auth/users/:userId', 'read')
						->put('auth/users/:userId', 'update')
						->post('auth/users', 'create')
						->delete('auth/users/:userId', 'delete')
						->get('auth/users/filters', 'filters')
						->put('auth/users/:userId/change-password', 'changePassword');
		
		$router->addRoutesFor(ForgotPasswordController::class)
						->post('auth/forgotpassword/:email', 'send')
						->post('auth/users/:userId/resetpassword', 'resetPassword');
		
		
		$router->addRoutesFor(GroupController::class)
						->get('auth/groups', 'store')
						->get('auth/groups/0', 'new')
						->get('auth/groups/:groupId', 'read')
						->put('auth/groups/:groupId', 'update')
						->post('auth/groups', 'create')
						->delete('auth/groups', 'delete');
		
	}
	
	protected function installPermissions(CoreModule $record) {
		//we don't want to grant everyone access on install
		return true;
	}
	
}
