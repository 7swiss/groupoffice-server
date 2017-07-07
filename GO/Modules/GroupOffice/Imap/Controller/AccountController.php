<?php // 
namespace GO\Modules\GroupOffice\Imap\Controller;

use GO\Core\Controller;
use GO\Modules\GroupOffice\Imap\Model\Account;
use IFW;
use IFW\Exception\NotFound;
use IFW\Orm\Query;

/**
 * The controller for accounts. Admin group is required.
 *
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class AccountController extends Controller {
////
////	protected function actionSync($accountId, $resync = false) {
////
////		$account = Account::findByPk($accountId);
////		if (!empty($resync)) {
////			GO()->getDbConnection()->query("DELETE FROM messages_thread");
////		}
////		
////		$process = new \GO\Core\Process\Model\Process('imapsync');
////
////		$account->sync($process);
////
////		$this->render($process->toArray());
////	}
////	
//	
//	protected function actionResyncMessage($messageId) {
//		$message = \GO\Modules\GroupOffice\Imap\Model\Message::find(['messageId'=>$messageId])->single();
//		$message->message->message->delete();
////		$message->message->delete();
//		$message->folder->sync();
//		
//		
//	}
////
////	/**
////	 * Fetch accounts
////	 *
////	 * @param string $orderColumn Order by this column
////	 * @param string $orderDirection Sort in this direction 'ASC' or 'DESC'
////	 * @param int $limit Limit the returned records
////	 * @param int $offset Start the select on this offset
////	 * @param string $searchQuery Search on this query.
////	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
////	 * @return array JSON Model data
////	 */
////	protected function actionStore($orderColumn = 'hostname', $orderDirection = 'ASC', $limit = 10, $offset = 0, $searchQuery = "", $returnProperties = "", $mine = false) {
////
////		$query = (new Query())
////														->orderBy([$orderColumn => $orderDirection])
////														->limit($limit)
////														->offset($offset)
////														->search($searchQuery, array('t.hostname'));
////		
////		//$query->where(['t.ownedBy' => \GO()->getAuth()->user()->id()]);
////		
////		$accounts = Account::find($query);
////		$accounts->setReturnProperties($returnProperties);
////
////		$this->renderStore($accounts);
////	}
//
//	/**
//	 * GET a list of accounts or fetch a single account
//	 *
//	 * 
//	 * @param int $accountId The ID of the group
//	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
//	 * @return JSON Model data
//	 */
//	protected function actionRead($accountId = null, $returnProperties = "") {
//
//		$account = Account::findByPk($accountId);
//
//		if (!$account) {
//			throw new NotFound();
//		}
//
//		$this->renderModel($account, $returnProperties);
//	}
//
//	/**
//	 * Get's the default data for a new account
//	 * 
//	 * 
//	 * 
//	 * @param $returnProperties
//	 * @return array
//	 */
//	protected function actionNew($returnProperties = "") {
//
//		$account = new Account();
//
//		$this->renderModel($account, $returnProperties);
//	}
//
//	/**
//	 * Create a new field. Use GET to fetch the default attributes or POST to add a new field.
//	 *
//	 * The attributes of this field should be posted as JSON in a field object
//	 *
//	 * <p>Example for POST and return data:</p>
//	 * ```````````````````````````````````````````````````````````````````````````
//	 * {"data":{"attributes":{"fieldname":"test",...}}}
//	 * ```````````````````````````````````````````````````````````````````````````
//	 * 
//	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
//	 * @return JSON Model data
//	 */
//	public function actionCreate($returnProperties = "") {
//
//		$account = new Account();
//		$account->setValues(GO()->getRequest()->body['data']);
//		$account->save();
//
//		$this->renderModel($account, $returnProperties);
//	}
//
//	/**
//	 * Update a field. Use GET to fetch the default attributes or POST to add a new field.
//	 *
//	 * The attributes of this field should be posted as JSON in a field object
//	 *
//	 * <p>Example for POST and return data:</p>
//	 * ```````````````````````````````````````````````````````````````````````````
//	 * {"data":{"attributes":{"fieldname":"test",...}}}
//	 * ```````````````````````````````````````````````````````````````````````````
//	 * 
//	 * @param int $accountId The ID of the field
//	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
//	 * @return JSON Model data
//	 * @throws NotFound
//	 */
//	public function actionUpdate($accountId, $returnProperties = "") {
//
//		$account = Account::findByPk($accountId);
//
//		if (!$account) {
//			throw new NotFound();
//		}
//
//		$account->setValues(GO()->getRequest()->body['data']);
//		$account->save();
//
//		$this->renderModel($account, $returnProperties);
//	}
//
//	/**
//	 * Delete a field
//	 *
//	 * @param int $accountId
//	 * @throws NotFound
//	 */
//	public function actionDelete($accountId) {
//		$account = Account::findByPk($accountId);
//
//		if (!$account) {
//			throw new NotFound();
//		}
//
//		$account->delete();
//
//		$this->renderModel($account);
//	}
//	
//	
//	
//	public function actionArchiveIncoming($accountId) {
//		$account = Account::findByPk($accountId);
//
//		if (!$account) {
//			throw new NotFound();
//		}
//		
//		$account->archiveIncoming();
//		
//		$this->render();
//		
//	}

}
