<?php
namespace GO\Modules\GroupOffice\Dav\Model;

/**
 * @property AccountCollection $collections
 */
class Account extends \GO\Core\Accounts\Model\AccountAdaptorRecord {
	
	/**
	 * 
	 * @var int
	 */							
	public $id;

	public $url;
	
	public $username;
	
	public $password;
	
	public function getName() {
		return $this->username;
	}
	
	protected static function defineRelations() {
		parent::defineRelations();
		
		self::hasMany('collections', AccountCollection::class, ['id' => 'accountId']);
	}
	
	protected function init() {
		parent::init();
		
		if($this->isNew()) {
			$this->coreAccount->capabilities[] = (new \GO\Core\Accounts\Model\Capabiltiy())->setValues(['modelName' => \GO\Modules\GroupOffice\Contacts\Model\Contact::class]);
		}
	}
	
	private $client;
	
	
	/**
	 * 
	 * @return \IFW\Dav\Client
	 */
	public function getClient() {
		if(!isset($this->client)) {
			$this->client = new \IFW\Dav\Client($this->url);
			$this->client->setAuth($this->username, $this->password);
		}
		
		return $this->client;
	}
	
	public function sync() {
			foreach($this->collections as $collection) {
				$collection->sync();
			}
	}

}
