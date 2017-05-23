<?php
namespace IFW\Auth;

use IFW\Auth\UserInterface;
use IFW\Fs\Folder;

/**
 * Authentication user provider interface
 * 
 * The app instance of the user provider is available by calling:
 * 
 * ````````````````````````````````````````````````````````````````````````````
 * IFW::app()->getAuth();
 * ````````````````````````````````````````````````````````````````````````````
 * @copyright (c) 2017, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
interface UserProviderInterface {
	/**
	 * Get the user data
	 * 
	 * It must implement {@see UserInterface}
	 * 
	 * @return UserInterface
	 */
	public function user();
	
	/**
	 * Check if there's an authenticated user
	 * 
	 * @return boolean
	 */
	public function isLoggedIn();
	
	
	/**
	 * Verify the XSRF token
	 * 
	 * For image resources you might want to disable this check
	 * 
	 * @return boolean
	 */
	public function checkXSRF();
	
	/**
	 * Get the XSRF token
	 * 
	 * @return string
	 */
	public function XSRFToken();
	
	
	/**
	 * Check if the logged in user is admin
	 * 
	 * @return boolean
	 */
	public function isAdmin();
	
	/**
	 * Set's a user as the logged in user
	 * 
	 * @param UserInterface $user
	 */
	public function setCurrentUser(UserInterface $user = null);
	
	
	/**
	 * Run code as administrator
	 * 
	 * Can be useful when you need to do stuff that the current user isn't 
	 * allowed to. For example when you create a contact you don't have the 
	 * permissions to do that while adding it.
	 * 
	 * @param callable $callback Code in this function will run as administrator
	 * @param UserInterface $user The user to run the function as. If null is given then run as admin.
	 * @param array $args The method arguments
	 * @return mixed return value of the callback
	 */
	public function sudo(callable $callable, UserInterface $user = null, $args = []);
	
	/**
	 * Get a temporary folder that is cleanned up when the user is logged out
	 * 
	 * @param boolean $autoCreate
	 * @return Folder
	 */
	public function getTempFolder($autoCreate = true);
}
