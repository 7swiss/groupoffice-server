<?php
namespace IFW\Auth;

/**
 * Execute function as another user
 * 
 * @copyright (c) 2017, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class Sudo {
	
	private $callable;
	private $sudoUser;
	private $originalUser;
	
	public function __construct(callable $callable, UserInterface $user) {		
		$this->callable = $callable;
		$this->sudoUser = $user;
		$this->originalUser = \IFW::app()->getAuth()->user();		
	}
	
	public function execute($args = []) {
		try {			
			\IFW::app()->getAuth()->setCurrentUser($this->sudoUser);			
			$ret = call_user_func_array($this->callable, $args);
			return $ret;
		} finally {			
			\IFW::app()->getAuth()->setCurrentUser($this->originalUser);
		}
	}
}
