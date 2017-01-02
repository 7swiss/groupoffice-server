<?php

namespace GO\Modules\GroupOffice\Dav\Model;

use IFW\App,
	GO\Core\Users\Model\User,
	GO\Core\Modules\Model\Module,
	GO\Core\Users\Model\Acl,
	Sabre\DAV\Auth\Backend\AbstractDigest,
	Sabre\DAV\Exception;

class DigestAuth extends AbstractDigest {
	
	private $_user;
	
	/**
	 * Check user access for this module
	 * 
	 * @var string 
	 */
	public $checkModuleAccess='dav';
	
	public function getDigestHash($realm, $username) {
		GO()->debug('Dav login: '.$username);
		
		$user = User::find(['username' => $username])->single();
		
		if($user) {
			//check dav module access		
			//$davModule = Module::findByPk($this->checkModuleAccess, false, true);		
			if (false) { // NO module access
				
				$errorMsg = "No '".$this->checkModuleAccess."' module access for user '".$user->username."'";
				GO()->debug($errorMsg);			
				throw new Exception\Forbidden($errorMsg);			
			} else {		
				$this->_user = $user;
				return $user->digest;
			}		
		} else {
			return null;
		}
	}	
	
	public function authenticate(\Sabre\DAV\Server $server, $realm) {		
		
		if(parent::authenticate($server, $realm)){
			
			$this->_user->setCurrent();
			return true;
		}

	}
}