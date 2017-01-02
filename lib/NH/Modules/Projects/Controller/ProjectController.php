<?php

namespace NH\Modules\Projects\Controller;

use IFW;
use GO\Core\Controller;
use IFW\Data\Store;
use IFW\Exception\NotFound;
use IFW\Web\Router;
use GO_Advprojects_Model_Project;
use GO_Base_Db_FindParams;
use NH\Modules\Projects\Model\Compat;
use NH\Modules\Projects\ProjectsModule;

/**
 * The controller for the project model
 *
 * @copyright (c) 2015, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class ProjectController extends Controller {
	
	public function __construct(Router $router) {		
		parent::__construct($router);		
		(new ProjectsModule())->requireGO5();
	}

	/**
	 * Fetch projects
	 *
	 * @param string $orderColumn Order by this column
	 * @param string $orderDirection Sort in this direction 'ASC' or 'DESC'
	 * @param int $limit Limit the returned records
	 * @param int $offset Start the select on this offset
	 * @param string $searchQuery Search on this query.
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return array JSON Model data
	 */
	protected function actionStore($parent_project_id = 0, $orderColumn = 'path', $orderDirection = 'ASC', $limit = 10, $offset = 0, $searchQuery = "") {

		$findParams = GO_Base_Db_FindParams::newInstance()
						->order($orderColumn, $orderDirection)
						->limit($limit)
						->start($offset)
						->searchQuery($searchQuery);
						
		$findParams->getCriteria()->addCondition('parent_project_id', $parent_project_id);
		
		$projects = GO_Advprojects_Model_Project::model()->find($findParams);		
		$projects = Compat::convertStatement($projects);

		$store = new Store($projects);
		$this->renderStore($store);
	}
	

	/**
	 * Get's the default data for a new project
	 * 
	 * 
	 * 
	 * @param array $returnProperties
	 * @return array
	 */
	protected function actionNew($returnProperties = "") {

		$project = new GO_Advprojects_Model_Project();
		
		$project->setValues(IFW::app()->getRequest()->body['data']);

		$this->renderModel(new Compat($project), $returnProperties);
	}

	/**
	 * GET a list of projects or fetch a single project
	 *
	 * The attributes of this project should be posted as JSON in a project object
	 *
	 * <p>Example for POST and return data:</p>
	 * <code>
	 * {"data":{"attributes":{"name":"test",...}}}
	 * </code>
	 * 
	 * @param int $projectId The ID of the project
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 */
	protected function actionRead($projectId = null, $returnProperties = "") {
		$project = GO_Advprojects_Model_Project::model()->findByPk($projectId);


		if (!$project) {
			throw new NotFound();
		}

		$this->renderModel(new Compat($project), $returnProperties);
	}

	/**
	 * Create a new project. Use GET to fetch the default attributes or POST to add a new project.
	 *
	 * The attributes of this project should be posted as JSON in a project object
	 *
	 * <p>Example for POST and return data:</p>
	 * <code>
	 * {"data":{"attributes":{"name":"test",...}}}
	 * </code>
	 * 
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 */
	public function actionCreate($returnProperties = "") {

		$project = new GO_Advprojects_Model_Project();
		$project->setValues(IFW::app()->getRequest()->body['data']);
		$project->save();

		$this->renderModel(new Compat($project), $returnProperties);
	}

	/**
	 * Update a project. Use GET to fetch the default attributes or POST to add a new project.
	 *
	 * The attributes of this project should be posted as JSON in a project object
	 *
	 * <p>Example for POST and return data:</p>
	 * <code>
	 * {"data":{"attributes":{"projectname":"test",...}}}
	 * </code>
	 * 
	 * @param int $projectId The ID of the project
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 * @throws NotFound
	 */
	public function actionUpdate($projectId, $returnProperties = "") {

		$project = GO_Advprojects_Model_Project::model()->findByPk($projectId);

		if (!$project) {
			throw new NotFound();
		}

		$project->setValues(IFW::app()->getRequest()->body['data']);
		$project->save();

		$this->renderModel(new Compat($project), $returnProperties);
	}

	/**
	 * Delete a project
	 *
	 * @param int $projectId
	 * @throws NotFound
	 */
	public function actionDelete($projectId) {
		$project = GO_Advprojects_Model_Project::model()->findByPk($projectId);

		if (!$project) {
			throw new NotFound();
		}

		$project->delete();

		$this->renderModel(new Compat($project));
	}

}
