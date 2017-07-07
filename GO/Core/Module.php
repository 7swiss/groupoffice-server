<?php

namespace GO\Core;

use GO\Core\Accounts\Controller\AccountController;
use GO\Core\Auth\Controller\AuthController;
use GO\Core\Blob\Controller\BlobController;
use GO\Core\Comments\Controller\CommentController;
use GO\Core\Cron\Controller\JobController;
use GO\Core\CustomFields\Controller\FieldController;
use GO\Core\CustomFields\Controller\FieldSetController;
use GO\Core\CustomFields\Controller\ModelController;
use GO\Core\Email\Controller\RecipientController;
use GO\Core\Install\Controller\SystemController;
use GO\Core\Log\Controller\EntryController;
use GO\Core\Modules\Controller\ModuleController;
use GO\Core\Modules\Controller\PermissionsController;
use GO\Core\Notifications\Controller\NotificationController;
use GO\Core\Resources\Controller\DownloadController;
use GO\Core\Settings\Controller\SettingsController;
use GO\Core\Smtp\Controller\AccountController as SmtpAccountController;
use GO\Core\Tags\Controller\TagController;
use GO\Core\Templates\Controller\MessageController;
use GO\Core\Templates\Controller\PdfController;
use GO\Core\Upload\Controller\TempThumbController;
use IFW;
use IFW\Cli\Router as Router2;
use IFW\Modules\Module as BaseModule;
use IFW\Web\Router;

class Module extends BaseModule {

	public static function defineWebRoutes(Router $router) {
		
	
		
		$router->addRoutesFor(SettingsController::class)
						->get('settings', 'read')
						->put('settings', 'update')
						->post('settings/testSmtp', 'testSmtp');
		
		$router->addRoutesFor(DownloadController::class)
						->get('resources/:moduleName/*path', 'download');

		$router->addRoutesFor(AccountController::class)
						->crud('accounts', 'accountId')
						->get('accounts/sync', 'syncAll')
						->get('accounts/:accountId/sync', 'sync');

//		$router->addRoutesFor(FlowController::class)
//						->get('upload', 'upload');
////						->post('upload', 'upload')
						
		$router->addRoutesFor(BlobController::class)
				->get('upload', 'upload')
				->post('upload', 'upload')
				->get('download/:id', 'download')
				->get('thumb/:id', 'thumb');

		$router->addRoutesFor(TempThumbController::class)
						->get('upload/thumb/:tempFile', 'download');



		$router->addRoutesFor(TagController::class)
						->get('tags', 'store')
						->get('tags/0', 'newInstance')
						->get('tags/:tagId', 'read')
						->put('tags/:tagId', 'update')
						->post('tags', 'create')
						->delete('tags/:tagId', 'delete');


		$router->addRoutesFor(SmtpAccountController::class)
						->crud('smtp/accounts', 'accountId');


		$router->addRoutesFor(ModuleController::class)
						->crud('modules', 'moduleName')
						->get('modules/all', 'allModules')
						->get('modules/filters', 'filters');

		$router->addRoutesFor(PermissionsController::class)
						->get('modules/:moduleName/permissions', 'store')
						->post('modules/:moduleName/permissions/:groupId/:action', 'create')
						->delete('modules/:moduleName/permissions/:groupId/:action', 'delete')
						->delete('modules/:moduleName/permissions/:groupId', 'deleteGroup');

		$router->addRoutesFor(SystemController::class)
						->get('system/install', 'install')
						->get('system/upgrade', 'upgrade')
						->get('system/check', 'check');


		$router->addRoutesFor(ModelController::class)
						->get('customfields/models', 'get')
						->get('customfields/models/:modelName','read');

		$router->addRoutesFor(FieldSetController::class)
						->get('customfields/fieldsets/:modelName', 'store')
						->get('customfields/fieldsets/:modelName/0', 'newInstance')
						->get('customfields/fieldsets/:modelName/:fieldSetId', 'read')
						->put('customfields/fieldsets/:modelName/:fieldSetId', 'update')
						->post('customfields/fieldsets/:modelName', 'create')
						->delete('customfields/fieldsets/:modelName/:fieldSetId', 'delete')
						->put('customfields/fieldsets/:modelName', 'multiple');

		$router->addRoutesFor(FieldController::class)
						->get('customfields/fieldsets/:modelName/:fieldSetId/fields', 'store')
						->get('customfields/fieldsets/:modelName/:fieldSetId/fields/0', 'newInstance')
						->get('customfields/fieldsets/:modelName/:fieldSetId/fields/:fieldId', 'read')
						->put('customfields/fieldsets/:modelName/:fieldSetId/fields/:fieldId', 'update')
						->post('customfields/fieldsets/:modelName/:fieldSetId/fields', 'create')
						->delete('customfields/fieldsets/:modelName/:fieldSetId/fields/:fieldId', 'delete')
						->put('customfields/fieldsets/:modelName/:fieldSetId/fields', 'multiple');


		$router->addRoutesFor(JobController::class)
						->crud('cron/jobs', 'jobId')
						->get('cron/run', 'run');


		//Add options route that consumes all routes
		$router->addRoutesFor(AuthController::class)
						->addRoute('OPTIONS', "*route", 'options')
						->get('auth', 'isLoggedIn')
						->get('auth/login-by-token/:token', 'loginByToken')
						->post('auth', 'login')
						->delete('auth', 'logout')
						->post('auth/users/:userId/switch-to', 'switchTo');
	
		
		$router->addRoutesFor(EntryController::class)
						->get('log', 'store')
						->get('log/filters', 'filters');
		
//		$router->addRoutesFor(DiskControler::class)->get('files', 'disk');
		
		$router->addRoutesFor(NotificationController::class)
						->get('notifications', 'store')
						->post('notifications/dismiss/:userId', 'dismissAll')
						->post('notifications/dismiss/:userId/:notificationId', 'dismiss')
						->get('notifications/watches/:recordClassName/:recordId/:userId', 'isWatched')
						->post('notifications/watches/:recordClassName/:recordId/:userId', 'watch')
						->delete('notifications/watches/:recordClassName/:recordId/:userId', 'unwatch');
		
		
		$router->addRoutesFor(MessageController::class)
						->crud('templates/messages/:moduleClassName', 'templateMessageId')
						->post('templates/messages/:moduleClassName/:templateMessageId/duplicate', 'duplicate');
		
		$router->addRoutesFor(PdfController::class)
						->crud('templates/pdf/:moduleClassName', 'pdfTemplateId')
						->get('templates/pdf/:moduleClassName/:pdfTemplateId/preview', 'preview')
						->post('templates/pdf/:moduleClassName/:pdfTemplateId/duplicate', 'duplicate');
		
		
		$router->addRoutesFor(CommentController::class)
						->crud('comments', 'commentId');
		
		$router->addRoutesFor(RecipientController::class)
						->get('recipients', 'store');
	}

	public static function defineCliRoutes(Router2 $router) {
		$router->addRoutesFor(SystemController::class)
						->set('system/check', 'check')
						->set('system/install', 'install')
						->set('system/upgrade', 'upgrade');
		
		$router->addRoutesFor(AccountController::class)
						->set('accounts/:accountId/sync', 'sync')
						->set('accounts/sync', 'syncAll');
		
		$router->addRoutesFor(JobController::class)->set('cron/run', 'run');
		
		$router->addRoutesFor(BlobController::class)->set('blob/test', 'test');
	}
	
	public function getCapabilities() {
		return [
			"uploadMaxFileSize" => IFW::app()->getEnvironment()->getMaxUploadSize()
		];
	}
	
}
