<?php

namespace GO\Core\Auth;

use Exception;
use GO\Core\Auth\Model\Token;
use GO\Core\Users\Model\User;
use IFW\Auth\UserProviderInterface;
use IFW\Auth\UserInterface;
use IFW\Orm\Query;
use IFW\Fs\Folder;

class UserProvider implements UserProviderInterface {

	/**
	 *
	 * @var User 
	 */
	private $currentUser;
	
	/**
	 * When sudo() is used this var holds the logged in user calling sudo()
	 * @var User	 
	 */
	private $sudoUser;
	
	/**
	 * True when sudo() is calling a closure as admin.
	 * 
	 * @var boolean 
	 */
	private $inSudo = false;

	/**
	 * Check if there's an authenticated user
	 * 
	 * @return boolean
	 */
	public function isLoggedIn() {
		return $this->user() !== null;
	}

	/**
	 * Get the user data
	 * 
	 * It must implement {@see UserInterface}
	 * 
	 * @return User
	 */
	public function user() {
		if (!isset($this->currentUser)) {
			$old = \IFW\Auth\Permissions\Model::$enablePermissions;
			\IFW\Auth\Permissions\Model::$enablePermissions = false;			
			$token = Token::findByCookie();
			if ($token) {				
				$this->setCurrentUser($token->user);
			}
			\IFW\Auth\Permissions\Model::$enablePermissions = $old;
		}

		return $this->currentUser;
	}

	/**
	 * Check if the logged in user is admin
	 * 
	 * @return boolean
	 */
	public function isAdmin() {
		return $this->user() && $this->user()->isAdmin();
	}

	/**
	 * Set's a user as the logged in user
	 * 
	 * @param UserInterface $user
	 */
	public function setCurrentUser(UserInterface $user) {
		if($this->inSudo) {
			$this->sudoUser = $user;
		}else
		{
			$this->currentUser = $user;
		}
	}

	/**
	 * Verify the XSRF token
	 * 
	 * For image resources you might want to disable this check
	 * 
	 * @return boolean
	 */
	public function checkXSRF() {

		$accessToken = Token::findByCookie();

		if (!$accessToken) {
			return true;
		}

		return $accessToken->checkXSRF();
	}

	public function XSRFToken() {
		$accessToken = Token::findByCookie();

		if (!$accessToken) {
			return null;
		}

		return $accessToken->XSRFToken;
	}
	
	

	/**
	 * Run code as administrator
	 * 
	 * Can be useful when you need to do stuff that the current user isn't 
	 * allowed to. For example when you create a contact you don't have the 
	 * permissions to do that while adding it.
	 * 
	 * @param callable $callback Code in this function will run as administrator
	 * @param int $userId The user to run the function as
	 * @param array $args The method arguments
	 */
	public function sudo(callable $callback, $userId = 1, $args = []) {		

		if($this->inSudo || !\IFW\Auth\Permissions\Model::$enablePermissions) {
			return call_user_func_array($callback, $args);
		}
		
		\GO()->debug("sudo start");	
		
		$this->inSudo = true;
		\IFW\Auth\Permissions\Model::$enablePermissions = false;
		$this->sudoUser = $this->user();		
		$this->currentUser = User::find(['id' => $userId])->single();
		\IFW\Auth\Permissions\Model::$enablePermissions = true;
		
		try {
			$ret = call_user_func_array($callback, $args);
		} catch (Exception $ex) {			
			\GO()->debug("sudo exception ".$ex->getMessage());
			throw $ex;
		} finally {
			\IFW\Auth\Permissions\Model::$enablePermissions = true;
			$this->currentUser = $this->sudoUser;
			$this->sudoUser = null;
			$this->inSudo = false;
			\GO()->debug("sudo end");
		}
		
		return $ret;
	}
	
	/**
	 * Get the user calling sudo
	 * 
	 * @return User
	 */
	public function getSudoUser() {
		return $this->sudoUser;
	}
	
	private $tempFolder;

	/**
	 * Get a temporary folder that is cleanned up when the user is logged out
	 * 
	 * @param Folder $autoCreate
	 */
	public function getTempFolder($autoCreate = true) {

		if(!isset($this->tempFolder)) {
			$accessToken = Token::findByCookie();
			if(!$accessToken) {
				$this->tempFolder = new \IFW\Fs\TempFolder();
			} else {
				$this->tempFolder = $accessToken->getTempFolder($autoCreate);
			}
		}

		return $this->tempFolder;
		
	}

}
