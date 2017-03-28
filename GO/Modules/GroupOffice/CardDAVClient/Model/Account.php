<?php
namespace GO\Modules\GroupOffice\CardDAVClient\Model;

class Account extends \GO\Core\Accounts\Model\AccountRecord {
	
	public $url;
	
	public $username;
	
	public $password;
	
	public function getName() {
		return $this->username;
	}

}
