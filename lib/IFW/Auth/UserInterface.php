<?php
namespace IFW\Auth;

/**
 * User interface
 * 
 * @copyright (c) 2017, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
interface UserInterface {
	
	/**
	 * @return The primary key of the user
	 */
	public function id();
	
	/**
	 * Check if this user is an administrator
	 *
	 * @return bool
	 */
	public function isAdmin();
}
