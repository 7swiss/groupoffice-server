<?php

namespace GO\Modules\GroupOffice\Dav\Model;

use GO\Core\Accounts\Model\AccountAdaptorRecord;
use GO\Modules\GroupOffice\Contacts\Model\Contact;
use IFW\Dav\Client;
use IFW\Util\Crypt;

/**
 * @property AccountCollection $collections
 */
class Account extends AccountAdaptorRecord {

	/**
	 * 
	 * @var int
	 */
	public $id;
	public $url;
	public $username;
	protected $password;

	public function getName() {
		return $this->username;
	}

	public function setPassword($password) {
		$crypt = new Crypt();
		$this->password = $crypt->encrypt($password);
	}

	private function getDescryptedPassword() {
		$crypt = new Crypt();
		if (!$crypt->isEncrypted($this->password)) {
			$this->password = $crypt->encrypt($this->password);
			$this->update();

			return $this->getDescryptedPassword();
		} else {
			return $crypt->decrypt($this->password);
		}
	}

	protected static function defineRelations() {
		parent::defineRelations();

		self::hasMany('collections', AccountCollection::class, ['id' => 'accountId']);
	}

	public static function getCapabilities() {
		return [Contact::class];
	}

	private $client;

	/**
	 * 
	 * @return Client
	 */
	public function getClient() {
		if (!isset($this->client)) {
			$this->client = new Client($this->url);
			$this->client->setAuth($this->username, $this->getDescryptedPassword());
		}

		return $this->client;
	}

	public function sync() {
		foreach ($this->collections as $collection) {
			$collection->sync();
		}
	}

}
