<?php
namespace GO\Modules\GroupOffice\Dav\Controller;

use GO\Core\Controller;
use GO\Modules\GroupOffice\Dav\Model\AccountCollections;
use IFW\Orm\Query;
use IFW\Exception\NotFound;

/**
 * The controller for the AccountCollections record
 *
 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class AccountCollectionsController extends Controller {


	/**
	 * Fetch accountCollectionss
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
	protected function actionStore($orderColumn = 'id', $orderDirection = 'DESC', $limit = 10, $offset = 0, $searchQuery = "", $returnProperties = "", $q = null) {

		$query = (new Query())
				->orderBy([$orderColumn => $orderDirection])
				->limit($limit)
				->offset($offset)
				->search($searchQuery, ['t.name']);
				
		if(isset($q)) {
			$query->setFromClient($q);			
		}

		$accountCollectionss = AccountCollections::find($query);
		$accountCollectionss->setReturnProperties($returnProperties);

		$this->renderStore($accountCollectionss);
	}
	
	
	/**
	 * Get's the default data for a new accountCollections
	 * 
	 * 
	 * 
	 * @param $returnProperties
	 * @return array
	 */
	protected function actionNew($returnProperties = ""){
		
		$user = new AccountCollections();

		$this->renderModel($user, $returnProperties);
	}

	/**
	 * GET a list of accountCollectionss or fetch a single accountCollections
	 *
	 * The attributes of this accountCollections should be posted as JSON in a accountCollections object
	 *
	 * <p>Example for POST and return data:</p>
	 * ```````````````````````````````````````````````````````````````````````````
	 * {"data":{"attributes":{"name":"test",...}}}
	 * </code>
	 * 
	 * @param int $accountCollectionsId The ID of the accountCollections
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 */
	protected function actionRead($accountCollectionsId = null, $returnProperties = "") {	
		$accountCollections = AccountCollections::findByPk($accountCollectionsId);


		if (!$accountCollections) {
			throw new NotFound();
		}

		$this->renderModel($accountCollections, $returnProperties);
		
	}

	/**
	 * Create a new accountCollections. Use GET to fetch the default attributes or POST to add a new accountCollections.
	 *
	 * The attributes of this accountCollections should be posted as JSON in a accountCollections object
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

		$accountCollections = new AccountCollections();
		$accountCollections->setValues(GO()->getRequest()->body['data']);
		$accountCollections->save();

		$this->renderModel($accountCollections, $returnProperties);
	}

	/**
	 * Update a accountCollections. Use GET to fetch the default attributes or POST to add a new accountCollections.
	 *
	 * The attributes of this accountCollections should be posted as JSON in a accountCollections object
	 *
	 * <p>Example for POST and return data:</p>
	 * ```````````````````````````````````````````````````````````````````````````
	 * {"data":{"accountCollectionsname":"test",...}}
	 * </code>
	 * 
	 * @param int $accountCollectionsId The ID of the accountCollections
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 * @throws NotFound
	 */
	public function actionUpdate($accountCollectionsId, $returnProperties = "") {

		$accountCollections = AccountCollections::findByPk($accountCollectionsId);

		if (!$accountCollections) {
			throw new NotFound();
		}

		$accountCollections->setValues(GO()->getRequest()->body['data']);
		$accountCollections->save();

		$this->renderModel($accountCollections, $returnProperties);
	}

	/**
	 * Delete a accountCollections
	 *
	 * @param int $accountCollectionsId
	 * @throws NotFound
	 */
	public function actionDelete($accountCollectionsId) {
		$accountCollections = AccountCollections::findByPk($accountCollectionsId);

		if (!$accountCollections) {
			throw new NotFound();
		}

		$accountCollections->delete();

		$this->renderModel($accountCollections);
	}
	
	/**
	 * Update multiple accountCollectionss at once with a PUT request.
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
				$accountCollections = AccountCollections::findByPk($values['id']);

				if (!$accountCollections) {
					throw new NotFound();
				}
			}else
			{
				$accountCollections = new AccountCollections();
			}
			
			$accountCollections->setValues($values);
			$accountCollections->save();
			
			$response['data'][] = $accountCollections->toArray();
		}
		
		$this->render($response);
	}
}
