<?php
namespace GO\Core\Cli;

use GO\Core\AppTrait;
use GO\Core\Auth\UserProvider;
use GO\Core\Users\Model\User;
use IFW\Cli\App as BaseApp;
use IFW\Orm\Query;

class App extends BaseApp {
	
	use AppTrait;
	
	protected function init() {
		require(dirname(__DIR__).'/AppFunction.php');
		
		parent::init();		
	}
	
	/**
	 * @var UserProvider
	 */
	private $auth;
	
	/**
	 * CLI is always logged in as admin
	 * @return type
	 */
	public function getAuth() {
		
		if(!isset($this->auth)) {
			$this->auth = new UserProvider();
			$admin = $this->auth->sudo(function() {
				return User::find((new Query())->select('id,username')->where(['id' => 1]))->single();
			});
			
			$this->auth->setCurrentUser($admin);
		}		
		return $this->auth;
	}
}
