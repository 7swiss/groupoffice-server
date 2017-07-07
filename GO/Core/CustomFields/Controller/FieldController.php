<?php

namespace GO\Core\CustomFields\Controller;

use IFW;
use GO\Core\Controller;
use IFW\Data\Store;
use IFW\Orm\Query;
use IFW\Exception\NotFound;
use GO\Core\CustomFields\Model\Field;

/**
 * The controller for address books
 *
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class FieldController extends Controller {
	
	
	/**
	 * GET a list of fields or fetch a single field
	 *
	 * 
	 * @param int $fieldId The ID of the group
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 */
	public function read($fieldId = null, $returnProperties = "") {

		$field = Field::findByPk($fieldId);

		if (!$field) {
			throw new NotFound();
		}

		$this->renderModel($field, $returnProperties);
	}

	/**
	 * Get's the default data for a new field
	 * 
	 * @param $returnProperties
	 * @return array
	 */
	public function newInstance($fieldSetId, $returnProperties = "") {

		$field = new Field();
		$field->fieldSetId = $fieldSetId;

		$this->renderModel($field, $returnProperties);
	}

	/**
	 * Fetch fields
	 *
	 * @param string $orderColumn Order by this column
	 * @param string $orderDirection Sort in this direction 'ASC' or 'DESC'
	 * @param int $limit Limit the returned records
	 * @param int $offset Start the select on this offset
	 * @param string $searchQuery Search on this query.
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return array JSON Model data
	 */
	public function store($fieldSetId, $orderColumn = 'sortOrder', $orderDirection = 'ASC', $limit = 10, $offset = 0, $searchQuery = "", $returnProperties = "", $where=null) {


		$query = (new Query())
				->orderBy([$orderColumn => $orderDirection])
				->limit($limit)
				->offset($offset)
				->search($searchQuery, ['name'])
				->where(['fieldSetId' => $fieldSetId]);
				
		if(isset($where)){
			$where = json_decode($where, true);
			$query->where($where);
		}

		$fields = Field::find($query);
		$fields->setReturnProperties($returnProperties);
		$this->renderStore($fields);
	}
	

	/**
	 * Create a new field. Use GET to fetch the default attributes or POST to add a new field.
	 *
	 * The attributes of this field should be posted as JSON in a field object
	 *
	 * <p>Example for POST and return data:</p>
	 * ```````````````````````````````````````````````````````````````````````````
	 * {"field":{"attributes":{"fieldname":"test",...}}}
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 */
	public function create($fieldSetId, $returnProperties = "") {

		$field = new Field();
		$field->fieldSetId = $fieldSetId;

		$field->setValues(GO()->getRequest()->body['data']);

		$field->save();
		

		$this->renderModel($field, $returnProperties);
	}

	/**
	 * Update a field. Use GET to fetch the default attributes or POST to add a new field.
	 *
	 * The attributes of this field should be posted as JSON in a field object
	 *
	 * <p>Example for POST and return data:</p>
	 * ```````````````````````````````````````````````````````````````````````````
	 * {"field":{"attributes":{"fieldname":"test",...}}}
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * @param int $fieldId The ID of the field
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 * @throws NotFound
	 */
	public function update($fieldId, $returnProperties = "") {

		$field = Field::findByPk($fieldId);

		if (!$field) {
			$this->renderError(404);
		}

		$field->setValues(GO()->getRequest()->body['data']);
		$field->save();
		
		$this->renderModel($field, $returnProperties);
	}

	/**
	 * Delete a field
	 *
	 * @param int $fieldId
	 * @throws NotFound
	 */
	public function delete($fieldId) {
		$field = Field::findByPk($fieldId);

		if (!$field) {
			$this->renderError(404);
		}

		$field->delete();

		$this->renderModel($field);
	}
	
	/**
	 * Update multiple contacts at once with a PUT request.
	 * 
	 * @example multi delete
	 * ```````````````````````````````````````````````````````````````````````````
	 * {
	 *	"data" : [{"id" : 1, "markDeleted" : true}, {"id" : 2, "markDeleted" : true}]
	 * }
	 * ```````````````````````````````````````````````````````````````````````````
	 * @throws NotFound
	 */
	public function multiple() {
		
		$response = ['data' => []];
		
		foreach(GO()->getRequest()->getBody()['data'] as $values) {
			
			if(!empty($values['id'])) {
				$field = Field::findByPk($values['id']);

				if (!$field) {
					throw new NotFound();
				}
			}else
			{
				$field = new Field();
			}
			
			$field->setValues($values);
			$field->save();
			
			$response['data'][] = $field->toArray('id');
		}
		
		$this->render($response);
	}
}
