<?php

namespace GO\Modules\GroupOffice\Dav\Model;

use Sabre\DAV\Auth\Backend\AbstractBasic;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use GO\Core\Users\Model\User;
use GO;

class BasicAuth extends AbstractBasic {

	private $_user;

	public function __construct() {
		$this->setRealm('Group-Office');
	}

	protected function validateUserPass($username, $password) {
		$this->_user = GO()->getAuth()->sudo(function() use ($username) {
			return User::find(['username' => $username])->single();
		});
		if(!$this->_user) {
			return false;
		}
		
		if(!$this->_user->checkPassword($password)){
			return false;
		}
		GO()->getAuth()->setCurrentUser($this->_user);

		return !empty($this->_user);
	}

	public function check(RequestInterface $request, ResponseInterface $response) {
		$result = parent::check($request, $response);

		if($result[0]==true) {
			GO()->debug("Login basicauth successfull as ".$this->_user->username);
			GO()->getAuth()->setCurrentUser($this->_user);
		}

		return $result;
	}

}