<?php

namespace GO\Modules\GroupOffice\Dav\Model;

use Sabre\DAV\Auth\Backend\AbstractBasic;

class BasicAuth extends AbstractBasic {
	
	protected function validateUserPass($username, $password) {
		$user = \GO\Core\Users\Model\User::find(['username' => $username])->single();
		
		if(!$user) {
			return false;
		}
		
		if(!$user->checkPassword($password)){
			return false;
		}
		
		$user->setCurrent();
		
		return $user;
	}

}