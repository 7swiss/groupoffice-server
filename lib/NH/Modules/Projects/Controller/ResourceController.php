<?php

namespace NH\Modules\Projects\Controller;

use IFW;
use GO\Core\Controller;
use IFW\Data\Store;
use IFW\Exception\NotFound;
use IFW\Web\Router;
use GO_Advprojects_Model_Resource;
use GO_Base_Db_FindParams;
use NH\Modules\Projects\Model\Compat;
use NH\Modules\Projects\ProjectsModule;

/**
 * The controller for the resource model
 *
 * @copyright (c) 2015, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class ResourceController extends Controller {
	
	public function __construct(Router $router) {		
		parent::__construct($router);		
		(new ProjectsModule())->requireGO5();
	}

	/**
	 * Fetch resources
	 *
	 * @param string $orderColumn Order by this column
	 * @param string $orderDirection Sort in this direction 'ASC' or 'DESC'
	 * @param int $limit Limit the returned records
	 * @param int $offset Start the select on this offset
	 * @param string $searchQuery Search on this query.
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return array JSON Model data
	 */
	protected function actionStore($projectId = 0, $orderColumn = 'user_id', $orderDirection = 'ASC', $limit = 10, $offset = 0, $searchQuery = "") {

		$findParams = GO_Base_Db_FindParams::newInstance()
						->order($orderColumn, $orderDirection)
						->limit($limit)
						->start($offset)
						->searchQuery($searchQuery);
						
		$findParams->getCriteria()->addCondition('project_id', $projectId);
		
		$resources = GO_Advprojects_Model_Resource::model()->find($findParams);		
		$resources = Compat::convertStatement($resources);

		$store = new Store($resources);
		$this->renderStore($store);
	}
	

	/**
	 * Get's the default data for a new resource
	 * 
	 * 
	 * 
	 * @param array $returnProperties
	 * @return array
	 */
	protected function actionNew($projectId, $returnProperties = "") {

		$resource = new GO_Advprojects_Model_Resource();
		$resource->project_id = $projectId;
		
		$resource->setValues(IFW::app()->getRequest()->body['data']);

		$this->renderModel(new Compat($resource), $returnProperties);
	}

	/**
	 * GET a list of resources or fetch a single resource
	 *
	 * The attributes of this resource should be posted as JSON in a resource object
	 *
	 * <p>Example for POST and return data:</p>
	 * <code>
	 * {"data":{"attributes":{"name":"test",...}}}
	 * </code>
	 * 
	 * @param int $resourceId The ID of the resource
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 */
	protected function actionRead($resourceId = null, $returnProperties = "") {
		$resource = GO_Advprojects_Model_Resource::model()->findByPk($resourceId);


		if (!$resource) {
			throw new NotFound();
		}

		$this->renderModel(new Compat($resource), $returnProperties);
	}

	/**
	 * Create a new resource. Use GET to fetch the default attributes or POST to add a new resource.
	 *
	 * The attributes of this resource should be posted as JSON in a resource object
	 *
	 * <p>Example for POST and return data:</p>
	 * <code>
	 * {"data":{"attributes":{"name":"test",...}}}
	 * </code>
	 * 
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 */
	public function actionCreate($projectId, $returnProperties = "") {

		$resource = new GO_Advprojects_Model_Resource();
		$resource->project_id = $projectId;
		$resource->setValues(IFW::app()->getRequest()->body['data']);
		$resource->save();

		$this->renderModel(new Compat($resource), $returnProperties);
	}

	/**
	 * Update a resource. Use GET to fetch the default attributes or POST to add a new resource.
	 *
	 * The attributes of this resource should be posted as JSON in a resource object
	 *
	 * <p>Example for POST and return data:</p>
	 * <code>
	 * {"data":{"attributes":{"resourcename":"test",...}}}
	 * </code>
	 * 
	 * @param int $resourceId The ID of the resource
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 * @throws NotFound
	 */
	public function actionUpdate($resourceId, $returnProperties = "") {

		$resource = GO_Advprojects_Model_Resource::model()->findByPk($resourceId);

		if (!$resource) {
			throw new NotFound();
		}

		$resource->setValues(IFW::app()->getRequest()->body['data']);
		$resource->save();

		$this->renderModel(new Compat($resource), $returnProperties);
	}

	/**
	 * Delete a resource
	 *
	 * @param int $resourceId
	 * @throws NotFound
	 */
	public function actionDelete($resourceId) {
		$resource = GO_Advprojects_Model_Resource::model()->findByPk($resourceId);

		if (!$resource) {
			throw new NotFound();
		}

		$resource->delete();

		$this->renderModel(new Compat($resource));
	}

}
