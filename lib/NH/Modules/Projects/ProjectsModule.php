<?php

namespace NH\Modules\Projects;

use GO;
use iFW\Modules\Module;
use NH\Modules\Projects\Controller\ProjectController;
use NH\Modules\Projects\Controller\ResourceController;

class ProjectsModule extends Module {	
	
	public $go5ConfigFile = '/var/www/html/groupoffice-5.0/config.php';
	
	public $go5RootPath = '/var/www/html/groupoffice-5.0/www';
	
	public function requireGO5() {
		define('GO_CONFIG_FILE', $this->go5ConfigFile);
		require(rtrim($this->go5RootPath,'/').'/go/GO.php');
		spl_autoload_register(array('GO', 'autoload'));
		
		GO::session()->runAsRoot();
	}

	public function routes() {
		ProjectController::routes()->crud('matters', 'projectId');
		
		ResourceController::routes()->crud('matters/:projectId/resources', 'resourceId');
		Controller\BudgetController::routes()->crud('matters/:projectId/budgets', 'budgetId');
		Controller\DisbursementController::routes()->crud('matters/:projectId/disbursements', 'disbursementId');
		Controller\TimeEntryController::routes()->crud('matters/:projectId/timeentries', 'timeEntryId');

	}
}