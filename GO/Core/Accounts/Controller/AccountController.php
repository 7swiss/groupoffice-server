<?php

namespace GO\Core\Accounts\Controller;

use GO\Core\Accounts\Model\Account;
use GO\Core\Controller;
use IFW;
use IFW\Exception\NotFound;
use IFW\Orm\Query;

/**
 * The controller for the account model
 *
 * @copyright (c) 2015, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class AccountController extends Controller {

	/**
	 * Fetch accounts
	 *
	 * @param string $orderColumn Order by this column
	 * @param string $orderDirection Sort in this direction 'ASC' or 'DESC'
	 * @param int $limit Limit the returned records
	 * @param int $offset Start the select on this offset
	 * @param string $searchQuery Search on this query.
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return array JSON Model data
	 */
	public function store($capability = null, $orderColumn = 'name', $orderDirection = 'ASC', $limit = 10, $offset = 0, $searchQuery = "", $returnProperties = "", $q=null) {

		$query = (new Query())
						->orderBy([$orderColumn => $orderDirection])
						->limit($limit)
						->offset($offset)
						->search($searchQuery, ['t.name']);
		
		if(isset($q)) {
			$query->setFromClient($q);			
		}
		
		if($capability) {
			$capabilities = \GO\Core\Accounts\Model\Capability::find(
							(new Query)->tableAlias('capabilities')->where('capabilities.accountId = t.id')->andWhere(['modelName' => $capability]));
			
			$query->andWhere(['EXISTS', $capabilities]);
		}

		$accounts = Account::find($query);
		
		$accounts->setReturnProperties($returnProperties);

		$this->renderStore($accounts);
	}

	/**
	 * Get's the default data for a new account
	 * 
	 * 
	 * 
	 * @param $returnProperties
	 * @return array
	 */
	public function newInstance($modelName, $returnProperties = "") {

		$account = new Account();
		$account->modelName = $modelName;

		$this->renderModel($account, $returnProperties);
	}

	/**
	 * GET a list of accounts or fetch a single account
	 *
	 * The attributes of this account should be posted as JSON in a account object
	 *
	 * <p>Example for POST and return data:</p>
	 * ```````````````````````````````````````````````````````````````````````````
	 * {"data":{"attributes":{"name":"test",...}}}
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * @param int $accountId The ID of the account
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 */
	public function read($accountId = null, $returnProperties = "") {
		$account = Account::findByPk($accountId);


		if (!$account) {
			throw new NotFound();
		}

		$this->renderModel($account, $returnProperties);
	}

	/**
	 * Create a new account. Use GET to fetch the default attributes or POST to add a new account.
	 *
	 * The attributes of this account should be posted as JSON in a account object
	 *
	 * <p>Example for POST and return data:</p>
	 * ```````````````````````````````````````````````````````````````````````````
	 * {"data":{"attributes":{"name":"test",...}}}
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 */
	public function create($returnProperties = "") {

		$account = new Account();
		$account->setValues(GO()->getRequest()->body['data']);
		$account->save();

		$this->renderModel($account, $returnProperties);
	}

	/**
	 * Update a account. Use GET to fetch the default attributes or POST to add a new account.
	 *
	 * The attributes of this account should be posted as JSON in a account object
	 *
	 * <p>Example for POST and return data:</p>
	 * ```````````````````````````````````````````````````````````````````````````
	 * {"data":{"attributes":{"accountname":"test",...}}}
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * @param int $accountId The ID of the account
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 * @throws NotFound
	 */
	public function update($accountId, $returnProperties = "") {

		$account = Account::findByPk($accountId);

		if (!$account) {
			throw new NotFound();
		}

		$account->setValues(GO()->getRequest()->body['data']);
		$account->save();

		$this->renderModel($account, $returnProperties);
	}

	/**
	 * Delete a account
	 *
	 * @param int $accountId
	 * @throws NotFound
	 */
	public function delete($accountId) {
		$account = Account::findByPk($accountId);

		if (!$account) {
			throw new NotFound();
		}

		$account->delete();

		$this->renderModel($account);
	}

	public function sync($accountId) {
		$account = Account::findByPk($accountId);

		GO()->getAuth()->sudo(function() use($account) {
		$moduleAccount = call_user_func([$account->modelName, 'findByPk'], $account->id);
		$moduleAccount->sync();
		});
		
		$this->render(['data' => GO()->getProcess()->toArray()]);
	}
	
	public function syncAll() {
		Account::syncAll();
		
		$this->render();
	}
}
