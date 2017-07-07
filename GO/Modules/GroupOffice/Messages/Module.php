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
						->crud('messages/threads', 'threadId')
						->get('messages/threads/:threadId/messages', 'messages')
						->delete('messages/trash', 'emptyTrash')
						->delete('messages/junk', 'emptyJunk');
		
		$router->addRoutesFor(MessageController::class)
						->crud('messages', 'messageId');
		
		

		$router->addRoutesFor(AttachmentController::class)
						->get('messages/:messageId/attachments/:attachmentId', 'read');
		
		$router->addRoutesFor(Controller\AccountController::class)
						->get('messages/accounts', 'store')
						->get('messages/tags', 'tags');
		
	}
}
