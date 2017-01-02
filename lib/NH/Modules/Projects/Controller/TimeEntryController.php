<?php

namespace NH\Modules\Projects\Controller;

use IFW;
use GO\Core\Controller;
use IFW\Data\Store;
use IFW\Exception\NotFound;
use IFW\Web\Router;
use GO_Advprojects_Model_TimeEntry;
use GO_Base_Db_FindParams;
use NH\Modules\Projects\Model\Compat;
use NH\Modules\Projects\ProjectsModule;

/**
 * The controller for the timeEntry model
 *
 * @copyright (c) 2015, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class TimeEntryController extends Controller {
	
	public function __construct(Router $router) {		
		parent::__construct($router);		
		(new ProjectsModule())->requireGO5();
	}

	/**
	 * Fetch timeEntries
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
		
		$timeEntries = GO_Advprojects_Model_TimeEntry::model()->find($findParams);		
		$timeEntries = Compat::convertStatement($timeEntries);

		$store = new Store($timeEntries);
		$this->renderStore($store);
	}
	

	/**
	 * Get's the default data for a new timeEntry
	 * 
	 * 
	 * 
	 * @param array $returnProperties
	 * @return array
	 */
	protected function actionNew($projectId, $returnProperties = "") {

		$timeEntry = new GO_Advprojects_Model_TimeEntry();
		$timeEntry->project_id = $projectId;
		
		$timeEntry->setValues(IFW::app()->getRequest()->body['data']);

		$this->renderModel(new Compat($timeEntry), $returnProperties);
	}

	/**
	 * GET a list of timeEntries or fetch a single timeEntry
	 *
	 * The attributes of this timeEntry should be posted as JSON in a timeEntry object
	 *
	 * <p>Example for POST and return data:</p>
	 * <code>
	 * {"data":{"attributes":{"name":"test",...}}}
	 * </code>
	 * 
	 * @param int $timeEntryId The ID of the timeEntry
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 */
	protected function actionRead($timeEntryId = null, $returnProperties = "") {
		$timeEntry = GO_Advprojects_Model_TimeEntry::model()->findByPk($timeEntryId);


		if (!$timeEntry) {
			throw new NotFound();
		}

		$this->renderModel(new Compat($timeEntry), $returnProperties);
	}

	/**
	 * Create a new timeEntry. Use GET to fetch the default attributes or POST to add a new timeEntry.
	 *
	 * The attributes of this timeEntry should be posted as JSON in a timeEntry object
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

		$timeEntry = new GO_Advprojects_Model_TimeEntry();
		$timeEntry->project_id = $projectId;
		$timeEntry->setValues(IFW::app()->getRequest()->body['data']);
		$timeEntry->save();

		$this->renderModel(new Compat($timeEntry), $returnProperties);
	}

	/**
	 * Update a timeEntry. Use GET to fetch the default attributes or POST to add a new timeEntry.
	 *
	 * The attributes of this timeEntry should be posted as JSON in a timeEntry object
	 *
	 * <p>Example for POST and return data:</p>
	 * <code>
	 * {"data":{"attributes":{"timeEntryname":"test",...}}}
	 * </code>
	 * 
	 * @param int $timeEntryId The ID of the timeEntry
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 * @throws NotFound
	 */
	public function actionUpdate($timeEntryId, $returnProperties = "") {

		$timeEntry = GO_Advprojects_Model_TimeEntry::model()->findByPk($timeEntryId);

		if (!$timeEntry) {
			throw new NotFound();
		}

		$timeEntry->setValues(IFW::app()->getRequest()->body['data']);
		$timeEntry->save();

		$this->renderModel(new Compat($timeEntry), $returnProperties);
	}

	/**
	 * Delete a timeEntry
	 *
	 * @param int $timeEntryId
	 * @throws NotFound
	 */
	public function actionDelete($timeEntryId) {
		$timeEntry = GO_Advprojects_Model_TimeEntry::model()->findByPk($timeEntryId);

		if (!$timeEntry) {
			throw new NotFound();
		}

		$timeEntry->delete();

		$this->renderModel(new Compat($timeEntry));
	}

}
