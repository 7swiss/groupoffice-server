<?php
namespace IFW\Auth;

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
