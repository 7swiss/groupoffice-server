<?php
namespace GO\Core\Web;

use GO\Core\AppTrait;
use GO\Core\Auth\UserProvider;
use IFW\Web\App as BaseApp;

/**
 * Main application class.
 * 
 * The following REST routes are available:
 * 
 * | Method | Route | Controller     |
 * |--------|-------|----------------|
 * |GET | auth | {@link GO\Core\Auth\Browser\Controller\AuthController::actionIsLoggedIn}|
 * |POST | auth | {@link GO\Core\Auth\Browser\Controller\AuthController::actionLogin}|
 * |DELETE | auth | {@link GO\Core\Auth\Browser\Controller\AuthController::actionLogout}|
 * |POST | auth/users/:userId/switch-to | {@link GO\Core\Auth\Browser\Controller\AuthController::actionSwitchTo}|
 * |GET | auth/users | {@link GO\Core\Users\Controller\UserController::actionStore}|
 * |GET | auth/users/0 | {@link GO\Core\Users\Controller\UserController::actionNew}|
 * |GET | auth/users/:userId | {@link GO\Core\Users\Controller\UserController::actionRead}|
 * |PUT | auth/users/:userId | {@link GO\Core\Users\Controller\UserController::actionUpdate}|
 * |POST | auth/users | {@link GO\Core\Users\Controller\UserController::actionCreate}|
 * |DELETE | auth/users | {@link GO\Core\Users\Controller\UserController::actionDelete}|
 * |GET | auth/users/filters | {@link GO\Core\Users\Controller\UserController::actionFilters}|
 * |GET | auth/groups | {@link GO\Core\Users\Controller\GroupController::actionStore}|
 * |GET | auth/groups/0 | {@link GO\Core\Users\Controller\GroupController::actionNew}|
 * |GET | auth/groups/:groupId | {@link GO\Core\Users\Controller\GroupController::actionRead}|
 * |PUT | auth/groups/:groupId | {@link GO\Core\Users\Controller\GroupController::actionUpdate}|
 * |POST | auth/groups | {@link GO\Core\Users\Controller\GroupController::actionCreate}|
 * |DELETE | auth/groups | {@link GO\Core\Users\Controller\GroupController::actionDelete}|
 * |GET | cron/run | {@link GO\Core\Cron\Controller\JobController::actionRun}|
 * |GET | customfields/models | {@link GO\Core\CustomFields\Controller\ModelController::actionGet}|
 * |GET | customfields/fieldsets/:modelName | {@link GO\Core\CustomFields\Controller\FieldSetController::actionStore}|
 * |GET | customfields/fieldsets/:modelName/0 | {@link GO\Core\CustomFields\Controller\FieldSetController::actionNew}|
 * |GET | customfields/fieldsets/:modelName/:fieldSetId | {@link GO\Core\CustomFields\Controller\FieldSetController::actionRead}|
 * |PUT | customfields/fieldsets/:modelName/:fieldSetId | {@link GO\Core\CustomFields\Controller\FieldSetController::actionUpdate}|
 * |POST | customfields/fieldsets/:modelName | {@link GO\Core\CustomFields\Controller\FieldSetController::actionCreate}|
 * |DELETE | customfields/fieldsets/:modelName/:fieldSetId | {@link GO\Core\CustomFields\Controller\FieldSetController::actionDelete}|
 * |GET | customfields/fieldsets/:modelName/:fieldSetId/fields | {@link GO\Core\CustomFields\Controller\FieldController::actionStore}|
 * |GET | customfields/fieldsets/:modelName/:fieldSetId/fields/0 | {@link GO\Core\CustomFields\Controller\FieldController::actionNew}|
 * |GET | customfields/fieldsets/:modelName/:fieldSetId/fields/:fieldId | {@link GO\Core\CustomFields\Controller\FieldController::actionRead}|
 * |PUT | customfields/fieldsets/:modelName/:fieldSetId/fields/:fieldId | {@link GO\Core\CustomFields\Controller\FieldController::actionUpdate}|
 * |POST | customfields/fieldsets/:modelName/:fieldSetId/fields | {@link GO\Core\CustomFields\Controller\FieldController::actionCreate}|
 * |DELETE | customfields/fieldsets/:modelName/:fieldSetId/fields/:fieldId | {@link GO\Core\CustomFields\Controller\FieldController::actionDelete}|
 * |GET | system/install | {@link GO\Core\Install\Controller\SystemController::actionInstall}|
 * |GET | system/upgrade | {@link GO\Core\Install\Controller\SystemController::actionUpgrade}|
 * |GET | system/check | {@link GO\Core\Install\Controller\SystemController::actionCheck}|
 * |GET | modules | {@link GO\Core\Modules\Controller\ModuleController::actionStore}|
 * |GET | modules/0 | {@link GO\Core\Modules\Controller\ModuleController::actionNew}|
 * |GET | modules/:moduleId | {@link GO\Core\Modules\Controller\ModuleController::actionRead}|
 * |PUT | modules/:moduleId | {@link GO\Core\Modules\Controller\ModuleController::actionUpdate}|
 * |POST | modules | {@link GO\Core\Modules\Controller\ModuleController::actionCreate}|
 * |DELETE | modules/:moduleId | {@link GO\Core\Modules\Controller\ModuleController::actionDelete}|
 * |GET | modules/all | {@link GO\Core\Modules\Controller\ModuleController::actionAllModules}|
 * |GET | modules/:moduleId/permissions | {@link GO\Core\Modules\Controller\PermissionsController::actionStore}|
 * |POST | modules/:moduleId/permissions/:groupId/:action | {@link GO\Core\Modules\Controller\PermissionsController::actionCreate}|
 * |DELETE | modules/:moduleId/permissions/:groupId/:action | {@link GO\Core\Modules\Controller\PermissionsController::actionDelete}|
 * |GET | tags | {@link GO\Core\Tags\Controller\TagController::actionStore}|
 * |GET | tags/0 | {@link GO\Core\Tags\Controller\TagController::actionNew}|
 * |GET | tags/:tagId | {@link GO\Core\Tags\Controller\TagController::actionRead}|
 * |PUT | tags/:tagId | {@link GO\Core\Tags\Controller\TagController::actionUpdate}|
 * |POST | tags | {@link GO\Core\Tags\Controller\TagController::actionCreate}|
 * |DELETE | tags/:tagId | {@link GO\Core\Tags\Controller\TagController::actionDelete}|
 * |GET | upload | {@link GO\Core\Upload\Controller\FlowController::actionUpload}|
 * |POST | upload | {@link GO\Core\Upload\Controller\FlowController::actionUpload}|
 * |GET | upload/thumb/:tempFile | {@link GO\Core\Upload\Controller\ThumbController::actionDownload}|
 * |GET | bands | {@link GO\Modules\Bands\Controller\BandController::actionStore}|
 * |GET | bands/0 | {@link GO\Modules\Bands\Controller\BandController::actionNew}|
 * |GET | bands/:bandId | {@link GO\Modules\Bands\Controller\BandController::actionRead}|
 * |PUT | bands/:bandId | {@link GO\Modules\Bands\Controller\BandController::actionUpdate}|
 * |POST | bands | {@link GO\Modules\Bands\Controller\BandController::actionCreate}|
 * |DELETE | bands/:bandId | {@link GO\Modules\Bands\Controller\BandController::actionDelete}|
 * |GET | bands/hello | {@link GO\Modules\Bands\Controller\HelloController::actionName}|
 * |GET | contacts | {@link GO\Modules\Contacts\Controller\ContactController::actionStore}|
 * |GET | contacts/0 | {@link GO\Modules\Contacts\Controller\ContactController::actionNew}|
 * |GET | contacts/:contactId | {@link GO\Modules\Contacts\Controller\ContactController::actionRead}|
 * |PUT | contacts/:contactId | {@link GO\Modules\Contacts\Controller\ContactController::actionUpdate}|
 * |POST | contacts | {@link GO\Modules\Contacts\Controller\ContactController::actionCreate}|
 * |DELETE | contacts/:contactId | {@link GO\Modules\Contacts\Controller\ContactController::actionDelete}|
 * |GET | contacts/:contactId/activities | {@link GO\Modules\Contacts\Controller\ContactController::actionActivities}|
 * |GET | contacts/filters | {@link GO\Modules\Contacts\Controller\ContactController::actionFilters}|
 * |GET | contacts/byuser/:userId | {@link GO\Modules\Contacts\Controller\ContactController::actionReadByUser}|
 * |GET | contacts/0/thumb | {@link GO\Modules\Contacts\Controller\ThumbController::actionDownload}|
 * |GET | contacts/:contactId/thumb | {@link GO\Modules\Contacts\Controller\ThumbController::actionDownload}|
 * |GET | contacts/byuser/:userId/thumb | {@link GO\Modules\Contacts\Controller\ThumbController::actionDownload}|
 * |GET | contacts/:contactId/files | {@link GO\Modules\Contacts\Controller\FilesController::actionStore}|
 * |GET | contacts/:contactId/files/:fileId | {@link GO\Modules\Contacts\Controller\FilesController::actionRead}|
 * |GET | contacts/:contactId/files/:fileId/download | {@link GO\Modules\Contacts\Controller\FilesController::actionDownload}|
 * |PUT | contacts/:contactId/files/:fileId | {@link GO\Modules\Contacts\Controller\FilesController::actionUpdate}|
 * |POST | contacts/:contactId/files | {@link GO\Modules\Contacts\Controller\FilesController::actionCreate}|
 * |DELETE | contacts/:contactId/files/:fileId | {@link GO\Modules\Contacts\Controller\FilesController::actionDelete}|
 * |GET | devtools/models | {@link GO\Modules\DevTools\Controller\ModelController::actionList}|
 * |GET | devtools/models/:modelName/props | {@link GO\Modules\DevTools\Controller\ModelController::actionProps}|
 * |GET | devtools/routes | {@link GO\Modules\DevTools\Controller\RoutesController::actionMarkdown}|
 * |GET | email/recipients | {@link GO\Modules\Email\Controller\RecipientController::actionRecipients}|
 * |GET | email/accounts | {@link GO\Modules\Email\Controller\AccountController::actionStore}|
 * |GET | email/accounts/0 | {@link GO\Modules\Email\Controller\AccountController::actionNew}|
 * |GET | email/accounts/:accountId | {@link GO\Modules\Email\Controller\AccountController::actionRead}|
 * |GET | email/accounts/:accountId/sync | {@link GO\Modules\Email\Controller\AccountController::actionSync}|
 * |PUT | email/accounts/:accountId | {@link GO\Modules\Email\Controller\AccountController::actionUpdate}|
 * |POST | email/accounts | {@link GO\Modules\Email\Controller\AccountController::actionCreate}|
 * |DELETE | email/accounts/:accountId | {@link GO\Modules\Email\Controller\AccountController::actionDelete}|
 * |PUT | email/accounts/:accountId/archive-incoming | {@link GO\Modules\Email\Controller\AccountController::actionArchiveIncoming}|
 * |GET | email/autodetect/0 | {@link GO\Modules\Email\Controller\AutoDetectController::actionNew}|
 * |POST | email/autodetect | {@link GO\Modules\Email\Controller\AutoDetectController::actionDetect}|
 * |GET | email/accounts/:accountId/folders | {@link GO\Modules\Email\Controller\FolderController::actionStore}|
 * |GET | email/accounts/:accountId/folders/0 | {@link GO\Modules\Email\Controller\FolderController::actionNew}|
 * |GET | email/accounts/:accountId/folders/:folderId | {@link GO\Modules\Email\Controller\FolderController::actionRead}|
 * |PUT | email/accounts/:accountId/folders/:folderId | {@link GO\Modules\Email\Controller\FolderController::actionUpdate}|
 * |POST | email/accounts/:accountId/folders | {@link GO\Modules\Email\Controller\FolderController::actionCreate}|
 * |DELETE | email/accounts/:accountId/folders/:folderId | {@link GO\Modules\Email\Controller\FolderController::actionDelete}|
 * |GET | email/filters | {@link GO\Modules\Email\Controller\ThreadController::actionFilters}|
 * |GET | email/threads | {@link GO\Modules\Email\Controller\ThreadController::actionStore}|
 * |GET | email/threads/:threadId | {@link GO\Modules\Email\Controller\ThreadController::actionRead}|
 * |GET | email/threads/:threadId/messages | {@link GO\Modules\Email\Controller\ThreadController::actionMessages}|
 * |PUT | email/threads/:threadId/archive | {@link GO\Modules\Email\Controller\ThreadController::actionArchive}|
 * |DELETE | email/threads/:threadId | {@link GO\Modules\Email\Controller\ThreadController::actionDelete}|
 * |POST | email/threads/:threadId/move | {@link GO\Modules\Email\Controller\ThreadController::actionMove}|
 * |POST | email/threads/:threadId/copy | {@link GO\Modules\Email\Controller\ThreadController::actionCopy}|
 * |POST | email/threads/:threadId/setFlags | {@link GO\Modules\Email\Controller\ThreadController::actionSetFlags}|
 * |POST | email/messages | {@link GO\Modules\Email\Controller\MessageController::actionSend}|
 * |GET | email/sources/:messageId | {@link GO\Modules\Email\Controller\MessageController::actionSource}|
 * |GET | email/messages/:messageId/sync | {@link GO\Modules\Email\Controller\MessageController::actionSync}|
 * |POST | email/messages/:messageId/forwardAttachments | {@link GO\Modules\Email\Controller\MessageController::actionForwardAttachments}|
 * |GET | email/threads/:threadId/attachments/:attachmentId | {@link GO\Modules\Email\Controller\AttachmentController::actionRead}|
 * |GET | icalendar/accounts | {@link GO\Modules\ICalendar\Controller\AccountController::actionStore}|
 * |GET | icalendar/accounts/0 | {@link GO\Modules\ICalendar\Controller\AccountController::actionNew}|
 * |GET | icalendar/accounts/:accountId | {@link GO\Modules\ICalendar\Controller\AccountController::actionRead}|
 * |PUT | icalendar/accounts/:accountId | {@link GO\Modules\ICalendar\Controller\AccountController::actionUpdate}|
 * |POST | icalendar/accounts | {@link GO\Modules\ICalendar\Controller\AccountController::actionCreate}|
 * |DELETE | icalendar/accounts/:accountId | {@link GO\Modules\ICalendar\Controller\AccountController::actionDelete}|
 * |GET | icalendar/sync | {@link GO\Modules\ICalendar\Controller\AccountController::actionSync}|
 */
class App extends BaseApp {
	
	use AppTrait;
	
	protected function init() {
		parent::init();
		
		require(dirname(__DIR__).'/AppFunction.php');
	}
	
	/**
	 * @var UserProvider
	 */
	private $auth;
	
	public function getAuth() {
		
		if(!isset($this->auth)) {
			$this->auth = new UserProvider();
		}		
		return $this->auth;
	}	
}