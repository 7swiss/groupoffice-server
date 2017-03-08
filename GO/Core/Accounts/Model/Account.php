<?php
namespace GO\Core\Accounts\Model;

use IFW\Orm\Record;
/**
 * The Account model
 *
 *
 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class Account extends Record {	
	
	/**
	 * Primary key
	 * @var int
	 */							
	public $id;

	/**
	 * User inputted name
	 * @var string
	 */							
	public $name;

	/**
	 * The PHP class name of the model that contains the actual account data. For example an IMAP account.
	 * @var string
	 */							
	public $modelName;

	/**
	 * 
	 * @var int
	 */							
	public $createdBy;

	public static function syncAll() {
		$accounts = self::find();
		
		foreach($accounts as $account) {
			if(!$account->getIsSyncable()){
				continue;
			}
			
			$account->getAccountRecord()->sync();
		}
	}
		
		
	public function getIsSyncable() {
		return is_a($this->modelName, SyncableInterface::class, true);
	}
	
	protected static function internalGetPermissions() {
		return new \IFW\Auth\Permissions\CreatorOnly();
	}
	
	/**
	 * Get the account record that actually does the work
	 * 
	 * For example an imap account
	 * 
	 * @return AccountRecord
	 */
	public function getAccountRecord() {
		$modelName = $this->modelName;
		return $modelName::findByPk($this->id);
	}
}
