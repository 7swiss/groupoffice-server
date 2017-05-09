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
	
	public $deleted = false;
	
	protected static function defineRelations() {
		self::hasMany('capabilities', Capability::class, ['id' => 'accountId']);
		self::hasMany('groups', AccountGroup::class, ['id' => 'accountId']);
	}
	
	protected function internalSave() {
		
		if($this->isNew()) {
			
			$modelName = $this->modelName;
			$capabilities = $this->getAdaptor()->getCapabilities();
			
			foreach($capabilities as $capability) {
				$this->capabilities[] = (new Capability())->setValues(['modelName' => $capability]);
			}
		}
		
		if(!parent::internalSave()) {
			return false;
		}
		
		if($this->adaptor instanceof \IFW\Orm\Record) {
			$this->adaptor->id = $this->id;

			if(!$this->adaptor->save()) {

				$this->setValidationError('adaptor', \IFW\Validate\ErrorCode::RELATIONAL, "Could not save adaptor");
				return false;
			}
			}
		
		return true;
	}

	public static function syncAll() {
		$accounts = self::find();
		
		foreach($accounts as $account) {
			if(!$account->getIsSyncable()){
				continue;
			}
			try {
				$account->getAdaptor()->sync();
			} catch(\Exception $e) {
				GO()->error("An exception occurred in while syncing account: " . $this->name. " (" . $this->id. ") ".$e->getMessage(), $account);
			
				GO()->debug((string) $e);
			}
		}
	}
		
		
	public function getIsSyncable() {
		return is_a($this->modelName, SyncableInterface::class, true);
	}
	
	protected static function internalGetPermissions() {
		return new AccountPermissions();
	}

	private $adaptor;
	
	/**
	 * Get the account record that actually does the work
	 * 
	 * For example an IMAP account
	 * 
	 * @return AccountAdaptorInterface
	 */
	public function getAdaptor() {
		if(!isset($this->modelName)) {
			return null;
		}
		
		if(!isset($this->adaptor)) {
			if($this->isNew()) {
				$this->adaptor = new $this->modelName;
				$this->adaptor->coreAccount = $this;
			}else
			{		
				$modelName = $this->modelName;
				$this->adaptor = $modelName::getInstance($this);		
			}
		}
		
		
		
		return $this->adaptor;
	}
	
	public function setAdaptor($data) {
		if(is_array($data)) {
			$this->modelName = $data['className'];
			$this->getAdaptor()->setValues($data);
		} else {
			$this->adaptor = $data;
			$this->modelName = $data->className;
		}		
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
