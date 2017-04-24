<?php
namespace GO\Core\Accounts\Model;

use GO\Core\Accounts\Model\Account as CoreAccount;

/**
 * The AddressBook record
 *
 * @copyright (c) 2017, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */

abstract class AccountAdaptorModel implements \GO\Core\Accounts\Model\AccountAdaptorInterface {
	
	/**
	 *
	 * @var CoreAccount
	 */
	private $coreAccount;
	
	
	public function setCoreAccount(CoreAccount $record) {
		$this->coreAccount = $record;
	}

	public static function getInstance(\GO\Core\Accounts\Model\Account $record) {
		return new static($record);
	}
	
	public static function getCapabilities() {
		return [];
	}

}
