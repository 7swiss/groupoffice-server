<?php
/**
 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Michael de Hart <mdhart@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
namespace GO\Modules\GroupOffice\Files\Controller;

use IFW;
use GO\Core\Controller;
use GO\Modules\GroupOffice\Files\Model\Node;
use GO\Modules\GroupOffice\Files\Model\Directory;
use GO\Modules\GroupOffice\Files\Model\Drive;
use IFW\Exception\NotFound;
use IFW\Orm\Query;


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
	public function store($directory = null, $filter = null, $orderColumn = 't.name', $orderDirection = 'ASC', $limit = 20, $offset = 0, $searchQuery = "", $returnProperties = "*,owner[name],nodeUser", $q = null) {
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

		if(empty($flat) && empty($filter)) {
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
		if(empty($directory)){
			$dir = Drive::home()->root;
		} else {
			$dir = Directory::findByPk($directory);
		}
		
		$this->responseData['path'][] = ['id'=>$dir->id, 'name'=>$dir->getName()];

		while ($dir = $dir->parent) {
			$this->responseData['path'][] = ['id'=>$dir->id, 'name'=>$dir->getName()];
		}
//			if(!empty($filter)) {
//				array_pop($this->responseData['path']);
//			}
		
		$this->renderStore($nodes);

	}

	public function read($id, $returnProperties = "*") {

		$node = Node::findByPk($id);

		if (!$node) {
			throw new NotFound();
		}

		$this->renderModel($node, $returnProperties);
	}

	public function newInstance($returnProperties = "") {
		$event = new Node();
		$this->renderModel($event, $returnProperties);
	}

	/**
	 * Create a new node.
	 *
	 * @param array|JSON $returnProperties The attributes to return to the client.
	 * @param string[] $overwrites The uploaded filenames that should be overwritten
	 */
	public function create($returnProperties = "*") {
		$overwrites = [];
		if(isset(IFW::app()->getRequest()->body['overwrites'])) {
			$overwrites = array_flip(IFW::app()->getRequest()->body['overwrites']);
		}
		$data = IFW::app()->getRequest()->body['data'];
		if(!isset($data[0])) {
			$data = [$data];
		}
		foreach($data as $attr) {
			$node = new Node();
			$node->parentId = $attr['parentId']; // must be set first
			$node->setValues($attr);
			if(isset($overwrites[$node->name])) {
				$node->allowOverwrite = true;
			}
			$node->save();
		}

		$this->renderModel($node, $returnProperties);
	}

	/**
	 * Update node
	 *
	 * @param int $id The ID of the field
	 * @param array|JSON $returnProperties The attributes to return to the client.
	 * @return JSON Model data
	 * @throws NotFound
	 */
	public function update($id, $returnProperties = "*") {

		$node = Node::findByPk($id);

		if (!$node) {
			throw new NotFound();
		}

		$node->setValues(IFW::app()->getRequest()->body['data']);
		$node->save();

		$returnProperties .= ',nodeUser';

		$this->renderModel($node, $returnProperties);
	}

	public function move($dirId, $copy = true) {
		$nodes = Node::find(['id'=>IFW::app()->getRequest()->body['ids']]);
		$success = true;
		foreach($nodes as $node) {
			if($copy) {
				$attrs = $node->toArray();
				unset($attrs['id']);
				$node = new Node();
				$node->setValues($attrs);
			}
			$node->parentId = $dirId;
			$success = $success && $node->save();
		}
		$this->render(['success' => $success]);
	}

	/**
	 * Delete a node
	 *
	 * @param int $id
	 * @throws NotFound
	 */
	public function delete($id, $returnProperties = "*") {
		$node = Node::findByPk($id);

		if (!$node) {
			throw new NotFound();
		}
		
		$node->deleted ? $node->deleteHard() : $node->delete();

		$this->renderModel($node, $returnProperties);
	}

}
