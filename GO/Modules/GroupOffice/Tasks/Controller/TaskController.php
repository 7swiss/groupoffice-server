<?php
namespace GO\Modules\GroupOffice\Tasks\Controller;

use GO\Core\Controller;
use GO\Core\Users\Model\Group;
use GO\Modules\GroupOffice\Tasks\Model\Task;
use IFW;
use IFW\Exception\NotFound;
use IFW\Orm\Query;
use function GO;

/**
 * The controller for tasks
 * 
 * See {@see Task} model for the available properties
 * 
 * 
 *
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Wesley Smits <wsmits@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class TaskController extends Controller {

	/**
	 * Fetch tasks
	 *
	 * @param string $orderColumn Order by this column
	 * @param string $orderDirection Sort in this direction 'ASC' or 'DESC'
	 * @param int $limit Limit the returned records
	 * @param int $offset Start the select on this offset
	 * @param string $searchQuery Search on this query.
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return array JSON Model data
	 */
	public function actionStore($orderColumn = 't.dueAt', $orderDirection = 'ASC', $limit = 10, $offset = 0, $searchQuery = "", $returnProperties = "", $q = null) {
		
		$query = (new Query)
				->limit($limit)
				->offset($offset);
		
		if($orderColumn == 't.dueAt') {
			$query->orderBy([new IFW\Db\Expression('ISNULL(t.dueAt) '.($orderDirection == 'ASC' ? 'ASC' : 'DESC')), 'dueAt' => $orderDirection]);
		} else
		{
			$query->orderBy([$orderColumn => $orderDirection]);
		}

		if (!empty($searchQuery)) {
			$query->search($searchQuery, ['description']);
		}

		if (!empty($q)) {
			$query->setFromClient($q);
		}
		
//		$this->getFilterCollection()->apply($query);		

		$tasks = Task::find($query);
		$tasks->setReturnProperties($returnProperties);

		$this->renderStore($tasks);
	}
	
	
	protected function actionNew($returnProperties = ""){
		$task = new Task();
		$this->renderModel($task, $returnProperties);
	}
	
	
	/**
	 * GET a list of tasks or fetch a single task
	 *
	 * The attributes of this task should be posted as JSON in a group object
	 *
	 * <p>Example for POST and return data:</p>
	 * ```````````````````````````````````````````````````````````````````````````
	 * {"data":{"attributes":{"name":"test",...}}}
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * @param int $taskId The ID of the group
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 */
	protected function actionRead($taskId, $returnProperties = "*"){
		
		$task = Task::findByPk($taskId);
		
		if (!$task) {
			throw new NotFound();
		}

		$this->renderModel($task, $returnProperties);
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
	public function actionCreate($returnProperties = "") {
		
		$task = new Task();		
		$task->setValues(GO()->getRequest()->body['data']);
		$task->save();

		$this->renderModel($task, $returnProperties);
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
	 * @param int $taskId The ID of the field
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 * @throws NotFound
	 */
	public function actionUpdate($taskId, $returnProperties = "") {

		$task = Task::findByPk($taskId);

		if (!$task) {
			throw new NotFound();
		}

		$task->setValues(GO()->getRequest()->body['data']);
		$task->save();
		
		$this->renderModel($task, $returnProperties);
	}

	/**
	 * Delete a field
	 *
	 * @param int $taskId
	 * @throws NotFound
	 */
	public function actionDelete($taskId) {
		$task = Task::findByPk($taskId);

		if (!$task) {
			throw new NotFound();
		}

		$task->delete();

		$this->renderModel($task);
	}
	
	
	/**
	 * Update multiple Tasks at once with a PUT request.
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
			
			if(!empty($values['id'])) {
				$task = Task::findByPk($values['id']);

				if (!$task) {
					throw new NotFound();
				}
			}else
			{
				$task = new Task();
			}
			
			$task->setValues($values);
			$task->save();
			
			$response['data'][] = $task->toArray('id');
		}
		
		$this->render($response);
	}
	
	
	public function actionAssignees () {
		
		$groups = Group::find(
						(new Query())
						
						
						->where(['EXISTS' , Task::find((new Query)->tableAlias('tasks')->where('tasks.assignedTo = t.id'))])
						);
		$groups->setReturnProperties('id,name');
		$this->renderStore($groups);
		
	}
	
	
}