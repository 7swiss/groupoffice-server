<?php

namespace NH\Modules\Projects\Controller;

use IFW;
use GO\Core\Controller;
use IFW\Data\Store;
use IFW\Exception\NotFound;
use IFW\Web\Router;
use GO_Advprojects_Model_ExpenseBudget;
use GO_Base_Db_FindParams;
use NH\Modules\Projects\Model\Compat;
use NH\Modules\Projects\ProjectsModule;

/**
 * The controller for the budget model
 *
 * @copyright (c) 2015, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class BudgetController extends Controller {
	
	public function __construct(Router $router) {		
		parent::__construct($router);		
		(new ProjectsModule())->requireGO5();
	}

	/**
	 * Fetch budgets
	 *
	 * @param string $orderColumn Order by this column
	 * @param string $orderDirection Sort in this direction 'ASC' or 'DESC'
	 * @param int $limit Limit the returned records
	 * @param int $offset Start the select on this offset
	 * @param string $searchQuery Search on this query.
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return array JSON Model data
	 */
	public function actionStore($projectId = 0, $orderColumn = 'ctime', $orderDirection = 'ASC', $limit = 10, $offset = 0, $searchQuery = "") {

		$findParams = GO_Base_Db_FindParams::newInstance()
						->order($orderColumn, $orderDirection)
						->limit($limit)
						->start($offset)
						->searchQuery($searchQuery);
						
		$findParams->getCriteria()->addCondition('project_id', $projectId);
		
		$budgets = GO_Advprojects_Model_ExpenseBudget::model()->find($findParams);		
		$budgets = Compat::convertStatement($budgets);

		$this->renderStore($budgets);
	}
	

	/**
	 * Get's the default data for a new budget
	 * 
	 * 
	 * 
	 * @param array $returnProperties
	 * @return array
	 */
	public function actionNew($projectId, $returnProperties = "") {

		$budget = new GO_Advprojects_Model_ExpenseBudget();
		$budget->project_id = $projectId;
		
		$budget->setValues(IFW::app()->getRequest()->body['data']);

		$this->renderModel(new Compat($budget), $returnProperties);
	}

	/**
	 * GET a list of budgets or fetch a single budget
	 *
	 * The attributes of this budget should be posted as JSON in a budget object
	 *
	 * <p>Example for POST and return data:</p>
	 * ```````````````````````````````````````````````````````````````````````````
	 * {"data":{"attributes":{"name":"test",...}}}
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * @param int $budgetId The ID of the budget
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 */
	public function actionRead($budgetId = null, $returnProperties = "") {
		$budget = GO_Advprojects_Model_ExpenseBudget::model()->findByPk($budgetId);


		if (!$budget) {
			throw new NotFound();
		}

		$this->renderModel(new Compat($budget), $returnProperties);
	}

	/**
	 * Create a new budget. Use GET to fetch the default attributes or POST to add a new budget.
	 *
	 * The attributes of this budget should be posted as JSON in a budget object
	 *
	 * <p>Example for POST and return data:</p>
	 * ```````````````````````````````````````````````````````````````````````````
	 * {"data":{"attributes":{"name":"test",...}}}
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 */
	public function actionCreate($projectId, $returnProperties = "") {

		$budget = new GO_Advprojects_Model_ExpenseBudget();
		$budget->project_id = $projectId;
		$budget->setValues(IFW::app()->getRequest()->body['data']);
		$budget->save();

		$this->renderModel(new Compat($budget), $returnProperties);
	}

	/**
	 * Update a budget. Use GET to fetch the default attributes or POST to add a new budget.
	 *
	 * The attributes of this budget should be posted as JSON in a budget object
	 *
	 * <p>Example for POST and return data:</p>
	 * ```````````````````````````````````````````````````````````````````````````
	 * {"data":{"attributes":{"budgetname":"test",...}}}
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * @param int $budgetId The ID of the budget
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 * @throws NotFound
	 */
	public function actionUpdate($budgetId, $returnProperties = "") {

		$budget = GO_Advprojects_Model_ExpenseBudget::model()->findByPk($budgetId);

		if (!$budget) {
			throw new NotFound();
		}

		$budget->setValues(IFW::app()->getRequest()->body['data']);
		$budget->save();

		$this->renderModel(new Compat($budget), $returnProperties);
	}

	/**
	 * Delete a budget
	 *
	 * @param int $budgetId
	 * @throws NotFound
	 */
	public function actionDelete($budgetId) {
		$budget = GO_Advprojects_Model_ExpenseBudget::model()->findByPk($budgetId);

		if (!$budget) {
			throw new NotFound();
		}

		$budget->delete();

		$this->renderModel(new Compat($budget));
	}

}
