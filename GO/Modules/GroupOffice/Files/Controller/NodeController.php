<?php

namespace GO\Modules\GroupOffice\Files\Controller;

use IFW;
use GO\Core\Controller;
use GO\Modules\GroupOffice\Files\Model\Node;
use GO\Modules\GroupOffice\Files\Model\Directory;
use GO\Modules\GroupOffice\Files\Model\Drive;
use IFW\Exception\NotFound;
use IFW\Orm\Query;

/**
 * The controller for calendars
 *
 * See {@see Event} model for the available properties

 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Michael de Hart <mdhart@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class NodeController extends Controller {

	/**
	 * node store
	 *
	 * @param int $directory ID of dir to look in
	 * @param string $filter [shared, starred, recent, owned, trash]
	 * @param string $orderColumn Order by this column
	 * @param string $orderDirection Sort in this direction 'ASC' or 'DESC'
	 * @param int $limit Limit the returned records
	 * @param int $offset Start the select on this offset
	 * @param string $searchQuery Search on this query.
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return array JSON Model data
	 */
	public function actionStore($directory = null, $filter = null, $orderColumn = 't.name', $orderDirection = 'ASC', $limit = 20, $offset = 0, $searchQuery = "", $returnProperties = "*,owner[name],nodeUser", $q = null) {
		$filter = json_decode($filter, true);
		$query = (new Query)
				  ->joinRelation('blob', false, 'LEFT') // folder has no size
				  ->joinRelation('nodeUser', true, 'LEFT')
				  ->joinRelation('owner', 'name')
				  ->orderBy(['isDirectory' => 'DESC', $orderColumn => $orderDirection])
				  ->limit($limit)
				  ->offset($offset);

		if (!empty($searchQuery)) {
			$query->search($searchQuery, ['name']);
		}
		if(isset($q)) {
			$query->setFromClient($q);
			$flat = true;
		}
		if(empty($directory) || $directory === "home") {
			$directory = Drive::home()->getRoot()->id;
		}
		if(!empty($filter['locations'])) {
			$query->join(Drive::tableName(),'d', 't.id = d.rootId');
			$query->select('t.*, 1 as isDrive');
			$flat = true;
		}

		if(empty($flat) && empty($filter['trash']) && empty($filter['shared'])) {
			$query->where(['parentId' => $directory]);
		}
		if (!empty($filter['starred'])) {
			$query->andWhere('nodeUser.starred = 1');
		}
		if (!empty($filter['trash'])) {
			$query->withDeleted()->andWhere('t.deleted = 1');
		}
		if (!empty($filter['recent'])) {
			$query
				->orderBy(['isDirectory' => 'DESC', 'nodeUser.touchedAt' => 'ASC'])
				->andWhere('nodeUser.touchedAt IS NOT NULL');
		}

		$nodes = Node::find($query);
		$nodes->setReturnProperties($returnProperties);

		$this->responseData['path'] = [];
		if(!empty($directory)){
			$dir = Directory::findByPk($directory);
			$this->responseData['path'][] = ['id'=>$dir->id, 'name'=>$dir->getName()];
			while ($dir = $dir->parent) {
				$this->responseData['path'][] = ['id'=>$dir->id, 'name'=>$dir->getName()];
			}
		}
		$this->renderStore($nodes);

	}

	protected function actionRead($id, $returnProperties = "*") {

		$node = Node::findByPk($id);

		if (!$node) {
			throw new NotFound();
		}

		$this->renderModel($node, $returnProperties);
	}

	protected function actionNew($returnProperties = "") {
		$event = new Node();
		$this->renderModel($event, $returnProperties);
	}

	/**
	 * Create a new calendar.
	 *
	 * @param array|JSON $returnProperties The attributes to return to the client.
	 */
	public function actionCreate($returnProperties = "*") {

		$data = IFW::app()->getRequest()->body['data'];
		if(!isset($data[0]))
			$data = [$data];
		foreach($data as $attr) {
			$node = new Node();
			$node->setValues($attr);
			$node->save();
		}

		$this->renderModel($node, $returnProperties);
	}

	/**
	 * Update calendar
	 *
	 * @param int $id The ID of the field
	 * @param array|JSON $returnProperties The attributes to return to the client.
	 * @return JSON Model data
	 * @throws NotFound
	 */
	public function actionUpdate($id, $returnProperties = "*") {

		$node = Node::findByPk($id);

		if (!$node) {
			throw new NotFound();
		}

		$node->setValues(IFW::app()->getRequest()->body['data']);
		$node->save();

		$returnProperties .= ',nodeUser';

		$this->renderModel($node, $returnProperties);
	}

	/**
	 * Delete an event
	 *
	 * @param int $id
	 * @throws NotFound
	 */
	public function actionDelete($id, $hard = false, $returnProperties = "*") {
		$node = Node::findByPk($id);

		if (!$node) {
			throw new NotFound();
		}
		
		$hard ? $node->deleteHard() : $node->delete();

		$this->renderModel($node, $returnProperties);
	}

}
