<?php

namespace GO\Core\Users\Controller;

use GO\Core\Users\Model\Group;
use GO\Core\Controller;
use IFW;
use IFW\Exception\NotFound;
use IFW\Orm\Query;

/**
 * The controller for groups. Admin group is required.
 *
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class GroupController extends Controller {

	protected function checkAccess() {
		return parent::checkAccess() && \GO()->getAuth()->isAdmin();
	}

	/**
	 * Fetch groups
	 *
	 * @param string $orderColumn Order by this column
	 * @param string $orderDirection Sort in this direction 'ASC' or 'DESC'
	 * @param int $limit Limit the returned records
	 * @param int $offset Start the select on this offset
	 * @param string $searchQuery Search on this query.
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return array JSON Model data
	 */
	protected function actionStore($orderColumn = 'name', $orderDirection = 'ASC', $limit = 10, $offset = 0, $searchQuery = "", $returnProperties = "", $q = null) {

		$query = (new Query())
				->orderBy([$orderColumn => $orderDirection])
				->limit($limit)
				->offset($offset)
				->search($searchQuery, array('t.name'));
		
		if(isset($q)) {
			$query->setFromClient($q);
		}

		$groups = Group::find($query);
		$groups->setReturnProperties($returnProperties);

		$this->renderStore($groups);
	}
	
	
	/**
	 * Get's the default data for a new group
	 * 
	 * 
	 * 
	 * @param $returnProperties
	 * @return array
	 */
	protected function actionNew($returnProperties = ""){
		
		$user = new Group();

		$this->renderModel($user, $returnProperties);
	}

	/**
	 * GET a list of groups or fetch a single group
	 *
	 * The attributes of this group should be posted as JSON in a group object
	 *
	 * <p>Example for POST and return data:</p>
	 * <code>
	 * {"data":{"attributes":{"name":"test",...}}}
	 * </code>
	 * 
	 * @param int $groupId The ID of the group
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 */
	protected function actionRead($groupId = null, $returnProperties = "") {	
		$group = Group::findByPk($groupId);


		if (!$group) {
			throw new NotFound();
		}

		$this->renderModel($group, $returnProperties);
		
	}

	/**
	 * Create a new field. Use GET to fetch the default attributes or POST to add a new field.
	 *
	 * The attributes of this field should be posted as JSON in a field object
	 *
	 * <p>Example for POST and return data:</p>
	 * <code>
	 * {"data":{"attributes":{"fieldname":"test",...}}}
	 * </code>
	 * 
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 */
	public function actionCreate($returnProperties = "") {

		$group = new Group();
		$group->setValues(GO()->getRequest()->body['data']);
		$group->save();

		$this->renderModel($group, $returnProperties);
	}

	/**
	 * Update a field. Use GET to fetch the default attributes or POST to add a new field.
	 *
	 * The attributes of this field should be posted as JSON in a field object
	 *
	 * <p>Example for POST and return data:</p>
	 * <code>
	 * {"data":{"attributes":{"fieldname":"test",...}}}
	 * </code>
	 * 
	 * @param int $groupId The ID of the field
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 * @throws NotFound
	 */
	public function actionUpdate($groupId, $returnProperties = "") {

		$group = Group::findByPk($groupId);

		if (!$group) {
			throw new NotFound();
		}

		$group->setValues(GO()->getRequest()->body['data']);
		$group->save();

		$this->renderModel($group, $returnProperties);
	}

	/**
	 * Delete a field
	 *
	 * @param int $groupId
	 * @throws NotFound
	 */
	public function actionDelete($groupId) {
		$group = Group::findByPk($groupId);

		if (!$group) {
			throw new NotFound();
		}

		$group->delete();

		$this->renderModel($group);
	}
}
