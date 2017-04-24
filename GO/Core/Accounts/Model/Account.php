<?php
namespace GO\Core\Accounts\Model;

use GO\Core\Auth\Permissions\Model\Owner;
use GO\Core\Orm\Record;
use IFW\Orm\Query;
/**
 * The Account model
 *
 * @property Capability[] $capabilities
 * @property AccountGroup[] $groups
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
	public $ownedBy;
	
	protected static function defineRelations() {
		self::hasMany('capabilities', Capability::class, ['id' => 'accountId']);
		self::hasMany('groups', AccountGroup::class, ['id' => 'accountId']);
	}
	
	protected function internalSave() {
		
		if($this->isNew()) {
			
			$modelName = $this->modelName;
			$capabilities = $modelName::getCapabilities();
			
			foreach($capabilities as $capability) {
				$this->capabilities[] = (new Capability())->setValues(['modelName' => $capability]);
			}
		}
		
		return parent::internalSave();
	}

	public static function syncAll() {
		$accounts = self::find();
		
		foreach($accounts as $account) {
			if(!$account->getIsSyncable()){
				continue;
			}
			
			$account->getAdaptor()->sync();
		}
	}
		
		
	public function getIsSyncable() {
		return is_a($this->modelName, SyncableInterface::class, true);
	}
	
	protected static function internalGetPermissions() {
		return new AccountPermissions();
	}

	
	/**
	 * Get the account record that actually does the work
	 * 
	 * For example an imap account
	 * 
	 * @return AccountAdaptorInterface
	 */
	public function getAdaptor() {
		$modelName = $this->modelName;
		return $modelName::getInstance($this);		
	}
	
	/**
	 * Find by capability 
	 * 
	 * @param string $modelName eg. Contact::class
	 * @param Query $query
	 * @return self[]
	 */
	public static function findByCapability($modelName, Query $query = null) {
		$query = Query::normalize($query)
					->orderBy(['name' => 'ASC'])
					->joinRelation('capabilities')
					->where(['capabilities.modelName' => $modelName]);
		
		return self::find($query);
	}
}
