<?php

namespace GO\Core\Templates\Controller;

use GO\Core\Controller;
use GO\Core\Modules\Model\Module;
use GO\Core\Templates\Model\Message;
use IFW\Exception\NotFound;
use IFW\Orm\Query;
use IFW\Orm\Utils;
use function GO;

/**
 * The controller for the message model
 *
 * @copyright (c) 2015, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class MessageController extends Controller {

	/**
	 * Fetch messages
	 *
	 * @param string $orderColumn Order by this column
	 * @param string $orderDirection Sort in this direction 'ASC' or 'DESC'
	 * @param int $limit Limit the returned records
	 * @param int $offset Start the select on this offset
	 * @param string $searchQuery Search on this query.
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return array JSON Model data
	 */
	public function store($moduleClassName, $orderColumn = 'name', $orderDirection = 'ASC', $limit = 10, $offset = 0, $searchQuery = "", $returnProperties = "") {

		$module = Module::find(['name' => $moduleClassName])->single();

		$query = (new Query())
						->orderBy([$orderColumn => $orderDirection])
						->limit($limit)
						->offset($offset)
						->search($searchQuery, ['t.name'])
						->where(['moduleId' => $module->id]);

		$messages = Message::find($query);
		$messages->setReturnProperties($returnProperties);

		$this->renderStore($messages);
	}

	/**
	 * Get's the default data for a new message
	 * 
	 * 
	 * 
	 * @param $returnProperties
	 * @return array
	 */
	public function newInstance($returnProperties = "") {

		$user = new Message();

		$this->renderModel($user, $returnProperties);
	}

	/**
	 * GET a list of messages or fetch a single message
	 *
	 * The attributes of this message should be posted as JSON in a message object
	 *
	 * <p>Example for POST and return data:</p>
	 * ```````````````````````````````````````````````````````````````````````````
	 * {"data":{"attributes":{"name":"test",...}}}
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * @param int $templateMessageId The ID of the message
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 */
	public function read($templateMessageId = null, $returnProperties = "") {
		$message = Message::findByPk($templateMessageId);


		if (!$message) {
			throw new NotFound();
		}

		$this->renderModel($message, $returnProperties);
	}

	/**
	 * Create a new message. Use GET to fetch the default attributes or POST to add a new message.
	 *
	 * The attributes of this message should be posted as JSON in a message object
	 *
	 * <p>Example for POST and return data:</p>
	 * ```````````````````````````````````````````````````````````````````````````
	 * {"data":{"attributes":{"name":"test",...}}}
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 */
	public function create($moduleClassName, $returnProperties = "") {


		$message = new Message();
		$message->setValues(GO()->getRequest()->body['data']);
		$message->setModuleClassName($moduleClassName);
		$message->save();

		$this->renderModel($message, $returnProperties);
	}

	/**
	 * Update a message. Use GET to fetch the default attributes or POST to add a new message.
	 *
	 * The attributes of this message should be posted as JSON in a message object
	 *
	 * <p>Example for POST and return data:</p>
	 * ```````````````````````````````````````````````````````````````````````````
	 * {"data":{"attributes":{"messagename":"test",...}}}
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * @param int $templateMessageId The ID of the message
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 * @throws NotFound
	 */
	public function update($templateMessageId, $returnProperties = "") {

		$message = Message::findByPk($templateMessageId);

		if (!$message) {
			throw new NotFound();
		}

		$message->setValues(GO()->getRequest()->body['data']);
		$message->save();

		$this->renderModel($message, $returnProperties);
	}

	/**
	 * Delete a message
	 *
	 * @param int $templateMessageId
	 * @throws NotFound
	 */
	public function delete($templateMessageId) {
		$message = Message::findByPk($templateMessageId);

		if (!$message) {
			throw new NotFound();
		}

		$message->delete();

		$this->renderModel($message);
	}

	public function duplicate($templateMessageId) {
		$message = Message::findByPk($templateMessageId);

		if (!$message) {
			throw new NotFound();
		}

		$name = Utils::findUniqueValue($message->tableName(), 'name', $message->name);
		$duplicate = Utils::duplicate($message, ['name' => $name]);
	
		$this->renderModel($duplicate);
	}

		/**
	 * Update multiple records at once with a PUT request.
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
				$record = Message::findByPk($values['id']);

				if (!$record) {
					throw new NotFound();
				}
			}else
			{
				$record = new Message();
			}
			
			$record->setValues($values);
			$record->save();
			
			$response['data'][] = $record->toArray();
		}
		
		$this->render($response);
	}
}
