<?php

namespace NH\Modules\Projects\Controller;

use IFW;
use GO\Core\Controller;
use IFW\Data\Store;
use IFW\Exception\NotFound;
use IFW\Web\Router;
use GO_Advprojects_Model_Expense;
use GO_Base_Db_FindParams;
use NH\Modules\Projects\Model\Compat;
use NH\Modules\Projects\ProjectsModule;

/**
 * The controller for the disbursement model
 *
 * @copyright (c) 2015, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class DisbursementController extends Controller {
	
	public function __construct(Router $router) {		
		parent::__construct($router);		
		(new ProjectsModule())->requireGO5();
	}

	/**
	 * Fetch disbursements
	 *
	 * @param string $orderColumn Order by this column
	 * @param string $orderDirection Sort in this direction 'ASC' or 'DESC'
	 * @param int $limit Limit the returned records
	 * @param int $offset Start the select on this offset
	 * @param string $searchQuery Search on this query.
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return array JSON Model data
	 */
	protected function actionStore($projectId = 0, $orderColumn = 'date', $orderDirection = 'ASC', $limit = 10, $offset = 0, $searchQuery = "") {

		$findParams = GO_Base_Db_FindParams::newInstance()
						->order($orderColumn, $orderDirection)
						->limit($limit)
						->start($offset)
						->searchQuery($searchQuery);
						
		$findParams->getCriteria()->addCondition('project_id', $projectId);
		
		$disbursements = \GO_Advprojects_Model_Expense::model()->find($findParams);		
		$disbursements = Compat::convertStatement($disbursements);
		$this->renderStore($disbursements);
	}
	

	/**
	 * Get's the default data for a new disbursement
	 * 
	 * 
	 * 
	 * @param array $returnProperties
	 * @return array
	 */
	protected function actionNew($projectId, $returnProperties = "") {

		$disbursement = new GO_Advprojects_Model_Expense();
		$disbursement->project_id = $projectId;
		
		$disbursement->setValues(IFW::app()->getRequest()->body['data']);

		$this->renderModel(new Compat($disbursement), $returnProperties);
	}

	/**
	 * GET a list of disbursements or fetch a single disbursement
	 *
	 * The attributes of this disbursement should be posted as JSON in a disbursement object
	 *
	 * <p>Example for POST and return data:</p>
	 * <code>
	 * {"data":{"attributes":{"name":"test",...}}}
	 * </code>
	 * 
	 * @param int $disbursementId The ID of the disbursement
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 */
	protected function actionRead($disbursementId = null, $returnProperties = "") {
		$disbursement = GO_Advprojects_Model_Expense::model()->findByPk($disbursementId);


		if (!$disbursement) {
			throw new NotFound();
		}

		$this->renderModel(new Compat($disbursement), $returnProperties);
	}

	/**
	 * Create a new disbursement. Use GET to fetch the default attributes or POST to add a new disbursement.
	 *
	 * The attributes of this disbursement should be posted as JSON in a disbursement object
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

		$disbursement = new GO_Advprojects_Model_Expense();
		$disbursement->project_id = $projectId;
		$disbursement->setValues(IFW::app()->getRequest()->body['data']);
		$disbursement->save();

		$this->renderModel(new Compat($disbursement), $returnProperties);
	}

	/**
	 * Update a disbursement. Use GET to fetch the default attributes or POST to add a new disbursement.
	 *
	 * The attributes of this disbursement should be posted as JSON in a disbursement object
	 *
	 * <p>Example for POST and return data:</p>
	 * <code>
	 * {"data":{"attributes":{"disbursementname":"test",...}}}
	 * </code>
	 * 
	 * @param int $disbursementId The ID of the disbursement
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 * @throws NotFound
	 */
	public function actionUpdate($disbursementId, $returnProperties = "") {

		$disbursement = GO_Advprojects_Model_Expense::model()->findByPk($disbursementId);

		if (!$disbursement) {
			throw new NotFound();
		}

		$disbursement->setValues(IFW::app()->getRequest()->body['data']);
		$disbursement->save();

		$this->renderModel(new Compat($disbursement), $returnProperties);
	}

	/**
	 * Delete a disbursement
	 *
	 * @param int $disbursementId
	 * @throws NotFound
	 */
	public function actionDelete($disbursementId) {
		$disbursement = GO_Advprojects_Model_Expense::model()->findByPk($disbursementId);

		if (!$disbursement) {
			throw new NotFound();
		}

		$disbursement->delete();

		$this->renderModel(new Compat($disbursement));
	}

}
