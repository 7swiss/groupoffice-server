<?php
namespace {namespace}\Controller;

use GO\Core\Controller;
use {namespace}\Model\{modelUcfirst};
use IFW\Orm\Query;
use IFW\Exception\NotFound;

/**
 * The controller for the {modelUcfirst} record
 *
 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class {modelUcfirst}Controller extends Controller {


	/**
	 * Fetch {modelLowerCase}s
	 *
	 * @param string $orderColumn Order by this column
	 * @param string $orderDirection Sort in this direction 'ASC' or 'DESC'
	 * @param int $limit Limit the returned records
	 * @param int $offset Start the select on this offset
	 * @param string $searchQuery Search on this query.
	 * @param array|JSON $returnProperties The properties to return to the client. eg. ['\*','emailAddresses.\*']. See {@see \IFW\Orm\Record::toArray()} for more information.
	 * @param string $q See {@see \IFW\Orm\Query::setFromClient()}
	 * @return array JSON Record data
	 */
	protected function actionStore($orderColumn = 'name', $orderDirection = 'ASC', $limit = 10, $offset = 0, $searchQuery = "", $returnProperties = "", $q = null) {

		$query = (new Query())
				->orderBy([$orderColumn => $orderDirection])
				->limit($limit)
				->offset($offset)
				->search($searchQuery, ['t.name']);
				
		if(isset($q)) {
			$query->setFromClient($q);			
		}

		${modelLowerCase}s = {modelUcfirst}::find($query);
		${modelLowerCase}s->setReturnProperties($returnProperties);

		$this->renderStore(${modelLowerCase}s);
	}
	
	
	/**
	 * Get's the default data for a new {modelLowerCase}
	 * 
	 * 
	 * 
	 * @param $returnProperties
	 * @return array
	 */
	protected function actionNew($returnProperties = ""){
		
		$user = new {modelUcfirst}();

		$this->renderModel($user, $returnProperties);
	}

	/**
	 * GET a list of {modelLowerCase}s or fetch a single {modelLowerCase}
	 *
	 * The attributes of this {modelLowerCase} should be posted as JSON in a {modelLowerCase} object
	 *
	 * <p>Example for POST and return data:</p>
	 * <code>
	 * {"data":{"attributes":{"name":"test",...}}}
	 * </code>
	 * 
	 * @param int ${modelLowerCase}Id The ID of the {modelLowerCase}
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 */
	protected function actionRead(${modelLowerCase}Id = null, $returnProperties = "") {	
		${modelLowerCase} = {modelUcfirst}::findByPk(${modelLowerCase}Id);


		if (!${modelLowerCase}) {
			throw new NotFound();
		}

		$this->renderModel(${modelLowerCase}, $returnProperties);
		
	}

	/**
	 * Create a new {modelLowerCase}. Use GET to fetch the default attributes or POST to add a new {modelLowerCase}.
	 *
	 * The attributes of this {modelLowerCase} should be posted as JSON in a {modelLowerCase} object
	 *
	 * <p>Example for POST and return data:</p>
	 * <code>
	 * {"data":{"name":"test",...}}
	 * </code>
	 * 
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 */
	public function actionCreate($returnProperties = "") {

		${modelLowerCase} = new {modelUcfirst}();
		${modelLowerCase}->setValues(GO()->getRequest()->body['data']);
		${modelLowerCase}->save();

		$this->renderModel(${modelLowerCase}, $returnProperties);
	}

	/**
	 * Update a {modelLowerCase}. Use GET to fetch the default attributes or POST to add a new {modelLowerCase}.
	 *
	 * The attributes of this {modelLowerCase} should be posted as JSON in a {modelLowerCase} object
	 *
	 * <p>Example for POST and return data:</p>
	 * <code>
	 * {"data":{"{modelLowerCase}name":"test",...}}
	 * </code>
	 * 
	 * @param int ${modelLowerCase}Id The ID of the {modelLowerCase}
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 * @throws NotFound
	 */
	public function actionUpdate(${modelLowerCase}Id, $returnProperties = "") {

		${modelLowerCase} = {modelUcfirst}::findByPk(${modelLowerCase}Id);

		if (!${modelLowerCase}) {
			throw new NotFound();
		}

		${modelLowerCase}->setValues(GO()->getRequest()->body['data']);
		${modelLowerCase}->save();

		$this->renderModel(${modelLowerCase}, $returnProperties);
	}

	/**
	 * Delete a {modelLowerCase}
	 *
	 * @param int ${modelLowerCase}Id
	 * @throws NotFound
	 */
	public function actionDelete(${modelLowerCase}Id) {
		${modelLowerCase} = {modelUcfirst}::findByPk(${modelLowerCase}Id);

		if (!${modelLowerCase}) {
			throw new NotFound();
		}

		${modelLowerCase}->delete();

		$this->renderModel(${modelLowerCase});
	}
}
