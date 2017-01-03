<?php
namespace GO\Modules\GroupOffice\Messages;

use GO\Core\Modules\Model\InstallableModule;
use GO\Modules\GroupOffice\Messages\Controller\AttachmentController;
use GO\Modules\GroupOffice\Messages\Controller\ThreadController;
use GO\Modules\GroupOffice\Messages\Controller\MessageController;
use IFW\Web\Router;

class Module extends InstallableModule {
	public static function defineWebRoutes(Router $router) {
		
		$router->addRoutesFor(ThreadController::class)
						->get('messages/filters', 'filters')
						->crud('messages/threads', 'threadId')
						->delete('messages/threads', 'multiDelete')
						->get('messages/threads/:threadId/messages', 'messages')
						->get('messages/threads/links/:recordClassName/:recordId', 'links');
		
		$router->addRoutesFor(MessageController::class)
						->crud('messages', 'messageId');
		
		$router->addRoutesFor(Controller\RecipientController::class)
						->get('messages/recipients','recipients');

		$router->addRoutesFor(AttachmentController::class)
						->get('messages/:messageId/attachments/:attachmentId', 'read');
		
		$router->addRoutesFor(Controller\AccountController::class)
						->get('messages/accounts', 'store');
		
	}
	
	
	public static function getAccountModelNames() {
		return [\GO\Modules\GroupOffice\Imap\Model\Account::class];
	}
}
