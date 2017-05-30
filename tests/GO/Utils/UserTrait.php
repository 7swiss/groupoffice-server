<?php

namespace GO\Utils;


trait UserTrait {
	/**
	 * Returns a fully configured PDO object.
	 *
	 * @return PDO
	 */
	static function changeUser($username) {
		$user = \GO()->getAuth()->sudo(function() use($username){
			return \GO\Core\Users\Model\User::find(['username' => $username])->single();
		});

		\GO()->getAuth()->setCurrentUser($user);
		return $user;
	}

	static function getUser($username) {
		$user = \GO()->getAuth()->sudo(function() use($username){
			return \GO\Core\Users\Model\User::find(['username' => $username])->single();
		});
		return $user;
	}

	static function currentUser() {
		return \GO()->getAuth()->user();
	}
}
