<?php

namespace GO\Modules\GroupOffice\Messages\Controller;

use GO\Core\Controller;
use GO\Core\Tags\Filter\TagFilter;
use GO\Modules\GroupOffice\Messages\Model\AccountFilter;
use GO\Modules\GroupOffice\Messages\Model\Message;
use GO\Modules\GroupOffice\Messages\Model\Thread;
use GO\Modules\GroupOffice\Messages\Model\TypeFilter;
use IFW\Data\Filter\FilterCollection;
use IFW\Exception\NotFound;
use IFW\Orm\Query;

/**
 * The controller for the thread model
 *
 * @copyright (c) 2015, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class ThreadController extends Controller {

	/**
	 * Fetch threads
	 *
	 * @param string $orderColumn Order by this column
	 * @param string $orderDirection Sort in this direction 'ASC' or 'DESC'
	 * @param int $limit Limit the returned records
	 * @param int $offset Start the select on this offset
	 * @param string $searchQuery Search on this query.
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return array JSON Model data
	 */
	protected function actionStore($type='incoming', $orderColumn = 'lastMessageSentAt', $orderDirection = 'DESC', $limit = 10, $offset = 0, $searchQuery = "", $returnProperties = "", $q = null) {

		$query = (new Query())
						->orderBy([$orderColumn => $orderDirection])
						->limit($limit)
						->offset($offset);
		
		if(isset($q)) {
			$query->setFromClient($q);
		}
		
		if(!empty($searchQuery)) {						
			$query->joinRelation('messages')
						->groupBy(['id'])
						->search($searchQuery, ['messages.subject','messages.body']);
		}

		$this->applyType($query, $type);		
		
		$threads = Thread::find($query);
		$threads->setReturnProperties($returnProperties);

		$this->renderStore($threads);
	}
	
	private function applyType(Query $query, $type) {
		$subquery = (new Query())
								->select('id')
								->tableAlias('m')
								->where('m.threadId=t.id');
		
		switch($type) {
			case 'incoming':
				$subquery->andWhere(['type'=>  Message::TYPE_INCOMING]);
				break;
			
			case 'unread':
				$subquery->andWhere(['type'=>  Message::TYPE_INCOMING, 'seen'=>false]);		
				break;
			case 'flagged':
				$subquery->andWhere(['flagged'=>true]);
				break;
			
			case 'actioned':
				$subquery->andWhere(['type'=>  Message::TYPE_ACTIONED]);				
				break;
			case 'sent':
				$subquery->andWhere(['type'=>  Message::TYPE_SENT]);				
				break;
			
			case 'drafts':
				$subquery->andWhere(['type'=>  Message::TYPE_DRAFT]);
				break;
			case 'trash':
				$subquery->andWhere(['type'=>  Message::TYPE_TRASH]);				
				break;
			case 'junk':
				$subquery->andWhere(['type'=>  Message::TYPE_JUNK]);				
				break;
			
			case 'outbox':
				$subquery->andWhere(['type'=>  Message::TYPE_OUTBOX]);				
				break;
		}
		
		$query->andWhere(['EXISTS', Message::find($subquery)]);
	}

	/**
	 * Get's the default data for a new thread
	 * 
	 * 
	 * 
	 * @param $returnProperties
	 * @return array
	 */
	protected function actionNew($returnProperties = "") {

		$user = new Thread();

		$this->renderModel($user, $returnProperties);
	}

	/**
	 * GET a list of threads or fetch a single thread
	 *
	 * The attributes of this thread should be posted as JSON in a thread object
	 *
	 * <p>Example for POST and return data:</p>
	 * <code>
	 * {"data":{"attributes":{"name":"test",...}}}
	 * </code>
	 * 
	 * @param int $threadId The ID of the thread
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 */
	protected function actionRead($threadId = null, $returnProperties = "") {
		$thread = Thread::findByPk($threadId);


		if (!$thread) {
			throw new NotFound();
		}

		$this->renderModel($thread, $returnProperties);
	}

	/**
	 * Create a new thread. Use GET to fetch the default attributes or POST to add a new thread.
	 *
	 * The attributes of this thread should be posted as JSON in a thread object
	 *
	 * <p>Example for POST and return data:</p>
	 * <code>
	 * {"data":{"attributes":{"name":"test",...}}}
	 * </code>
	 * 
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 */
	public function actionCreate($returnProperties = "") {

		$thread = new Thread();
		$thread->setValues(GO()->getRequest()->body['data']);
		$thread->save();

		$this->renderModel($thread, $returnProperties);
	}

	/**
	 * Update a thread. Use GET to fetch the default attributes or POST to add a new thread.
	 *
	 * The attributes of this thread should be posted as JSON in a thread object
	 *
	 * <p>Example for POST and return data:</p>
	 * <code>
	 * {"data":{"attributes":{"threadname":"test",...}}}
	 * </code>
	 * 
	 * @param int $threadId The ID of the thread
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 * @throws NotFound
	 */
	public function actionUpdate($threadId, $returnProperties = "") {

		$thread = Thread::findByPk($threadId);

		if (!$thread) {
			throw new NotFound();
		}

		$thread->setValues(GO()->getRequest()->body['data']);
		$thread->save();

		$this->renderModel($thread, $returnProperties);
	}

	/**
	 * Delete a thread
	 *
	 * @param int $threadId
	 * @throws NotFound
	 */
	public function actionDelete($threadId) {
		$thread = Thread::findByPk($threadId);

		if (!$thread) {
			throw new NotFound();
		}
		
//		$thread->setType(ThreadMessage::TYPE_TRASH);

		$this->renderModel($thread);
	}
	
	
	
	/**
	 * GET a list of accounts or fetch a single account
	 *
	 * 
	 * @param int $accountId The ID of the group
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 */
	protected function actionMessages($threadId, $limit = 10, $offset = 0, $returnProperties = "*") {

		$accounts = Message::find((new Query())
														->orderBy(['sentAt' => 'DESC'])
														->limit($limit)
														->offset($offset)
														->where(['threadId' => $threadId])
		);

		$accounts->setReturnProperties($returnProperties);
		
		$thread = Thread::findByPk($threadId);
		
		$this->responseData['thread'] = $thread->toArray('*,tags');

		$this->renderStore($accounts);
	}
	
	protected function actionEmptyTrash($accountId) {
		
		$messages = Message::find((new Query())->andWhere(['accountId' => json_decode($accountId)])->andWhere(['type' => Message::TYPE_TRASH])->debug());
		foreach($messages as $message) {
			$message->delete();
		}
		
		Thread::syncAll($accountId);
		
		$this->render();
	}
}