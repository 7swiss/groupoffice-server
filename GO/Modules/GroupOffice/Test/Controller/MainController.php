<?php
namespace GO\Modules\GroupOffice\Test\Controller;

use GO\Core\Controller;
use GO\Modules\GroupOffice\Test\Model\Main;
use IFW\Orm\Query;
use IFW\Exception\NotFound;

/**
 * The controller for the Main record
 *
 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class MainController extends Controller {


	/**
	 * Fetch mains
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
	public function actionStore($orderColumn = 'id', $orderDirection = 'DESC', $limit = 10, $offset = 0, $searchQuery = "", $returnProperties = "", $q = null) {

		$query = (new Query())
				->orderBy([$orderColumn => $orderDirection])
				->limit($limit)
				->offset($offset)
				->search($searchQuery, ['t.name']);
				
		if(isset($q)) {
			$query->setFromClient($q);			
		}

		$mains = Main::find($query);
		$mains->setReturnProperties($returnProperties);

		$this->renderStore($mains);
	}
	
	
	/**
	 * Get's the default data for a new main
	 * 
	 * 
	 * 
	 * @param $returnProperties
	 * @return array
	 */
	public function actionNew($returnProperties = ""){
		
		$user = new Main();

		$this->renderModel($user, $returnProperties);
	}

	/**
	 * GET a list of mains or fetch a single main
	 *
	 * The attributes of this main should be posted as JSON in a main object
	 *
	 * <p>Example for POST and return data:</p>
	 * ```````````````````````````````````````````````````````````````````````````
	 * {"data":{"attributes":{"name":"test",...}}}
	 * </code>
	 * 
	 * @param int $mainId The ID of the main
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 */
	public function actionRead($mainId = null, $returnProperties = "") {	
		$main = Main::findByPk($mainId);


		if (!$main) {
			throw new NotFound();
		}

		$this->renderModel($main, $returnProperties);
		
	}

	/**
	 * Create a new main. Use GET to fetch the default attributes or POST to add a new main.
	 *
	 * The attributes of this main should be posted as JSON in a main object
	 *
	 * <p>Example for POST and return data:</p>
	 * ```````````````````````````````````````````````````````````````````````````
	 * {"data":{"name":"test",...}}
	 * </code>
	 * 
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 */
	public function actionCreate($returnProperties = "") {

		$main = new Main();
		$main->setValues(GO()->getRequest()->body['data']);
		$main->save();

		$this->renderModel($main, $returnProperties);
	}

	/**
	 * Update a main. Use GET to fetch the default attributes or POST to add a new main.
	 *
	 * The attributes of this main should be posted as JSON in a main object
	 *
	 * <p>Example for POST and return data:</p>
	 * ```````````````````````````````````````````````````````````````````````````
	 * {"data":{"mainname":"test",...}}
	 * </code>
	 * 
	 * @param int $mainId The ID of the main
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 * @throws NotFound
	 */
	public function actionUpdate($mainId, $returnProperties = "") {

		$main = Main::findByPk($mainId);

		if (!$main) {
			throw new NotFound();
		}

		$main->setValues(GO()->getRequest()->body['data']);
		$main->save();

		$this->renderModel($main, $returnProperties);
	}

	/**
	 * Delete a main
	 *
	 * @param int $mainId
	 * @throws NotFound
	 */
	public function actionDelete($mainId) {
		$main = Main::findByPk($mainId);

		if (!$main) {
			throw new NotFound();
		}

		$main->delete();

		$this->renderModel($main);
	}
	
	/**
	 * Update multiple mains at once with a PUT request.
	 * 
	 * @example multi delete
	 * ```````````````````````````````````````````````````````````````````````````
	 * {
	 *	"data" : [{"id" : 1, "markDeleted" : true}, {"id" : 2, "markDeleted" : true}]
	 * }
	 * ```````````````````````````````````````````````````````````````````````````
	 * @throws NotFound
	 */
	public function actionMultiple() {
		
		$response = ['data' => []];
		
		foreach(GO()->getRequest()->getBody()['data'] as $values) {
			
			if(!empty($contactValues['id'])) {
				$main = Main::findByPk($values['id']);

				if (!$main) {
					throw new NotFound();
				}
			}else
			{
				$main = new Main();
			}
			
			$main->setValues($values);
			$main->save();
			
			$response['data'][] = $main->toArray();
		}
		
		$this->render($response);
	}
	public function test() {
		
		
		
	
		
		
	}
}
