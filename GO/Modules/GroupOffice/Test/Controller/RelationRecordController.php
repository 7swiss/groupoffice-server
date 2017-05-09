<?php
namespace GO\Modules\GroupOffice\Test\Controller;

use GO\Core\Controller;
use GO\Modules\GroupOffice\Test\Model\RelationRecord;
use IFW\Orm\Query;
use IFW\Exception\NotFound;

/**
 * The controller for the RelationRecord record
 *
 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class RelationRecordController extends Controller {


	/**
	 * Fetch relationRecords
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

		$relationRecords = RelationRecord::find($query);
		$relationRecords->setReturnProperties($returnProperties);

		$this->renderStore($relationRecords);
	}
	
	
	/**
	 * Get's the default data for a new relationRecord
	 * 
	 * 
	 * 
	 * @param $returnProperties
	 * @return array
	 */
	protected function actionNew($returnProperties = ""){
		
		$user = new RelationRecord();

		$this->renderModel($user, $returnProperties);
	}

	/**
	 * GET a list of relationRecords or fetch a single relationRecord
	 *
	 * The attributes of this relationRecord should be posted as JSON in a relationRecord object
	 *
	 * <p>Example for POST and return data:</p>
	 * ```````````````````````````````````````````````````````````````````````````
	 * {"data":{"attributes":{"name":"test",...}}}
	 * </code>
	 * 
	 * @param int $relationRecordId The ID of the relationRecord
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 */
	protected function actionRead($relationRecordId = null, $returnProperties = "") {	
		$relationRecord = RelationRecord::findByPk($relationRecordId);


		if (!$relationRecord) {
			throw new NotFound();
		}

		$this->renderModel($relationRecord, $returnProperties);
		
	}

	/**
	 * Create a new relationRecord. Use GET to fetch the default attributes or POST to add a new relationRecord.
	 *
	 * The attributes of this relationRecord should be posted as JSON in a relationRecord object
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

		$relationRecord = new RelationRecord();
		$relationRecord->setValues(GO()->getRequest()->body['data']);
		$relationRecord->save();

		$this->renderModel($relationRecord, $returnProperties);
	}

	/**
	 * Update a relationRecord. Use GET to fetch the default attributes or POST to add a new relationRecord.
	 *
	 * The attributes of this relationRecord should be posted as JSON in a relationRecord object
	 *
	 * <p>Example for POST and return data:</p>
	 * ```````````````````````````````````````````````````````````````````````````
	 * {"data":{"relationRecordname":"test",...}}
	 * </code>
	 * 
	 * @param int $relationRecordId The ID of the relationRecord
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 * @throws NotFound
	 */
	public function actionUpdate($relationRecordId, $returnProperties = "") {

		$relationRecord = RelationRecord::findByPk($relationRecordId);

		if (!$relationRecord) {
			throw new NotFound();
		}

		$relationRecord->setValues(GO()->getRequest()->body['data']);
		$relationRecord->save();

		$this->renderModel($relationRecord, $returnProperties);
	}

	/**
	 * Delete a relationRecord
	 *
	 * @param int $relationRecordId
	 * @throws NotFound
	 */
	public function actionDelete($relationRecordId) {
		$relationRecord = RelationRecord::findByPk($relationRecordId);

		if (!$relationRecord) {
			throw new NotFound();
		}

		$relationRecord->delete();

		$this->renderModel($relationRecord);
	}
	
	/**
	 * Update multiple relationRecords at once with a PUT request.
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
				$relationRecord = RelationRecord::findByPk($values['id']);

				if (!$relationRecord) {
					throw new NotFound();
				}
			}else
			{
				$relationRecord = new RelationRecord();
			}
			
			$relationRecord->setValues($values);
			$relationRecord->save();
			
			$response['data'][] = $relationRecord->toArray();
		}
		
		$this->render($response);
	}
}
