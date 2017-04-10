<?php

namespace Tests\Util;


trait SwitchUserTrait {
	/**
	 * Returns a fully configured PDO object.
	 *
	 * @return PDO
	 */
	function switchUser($username) {
		$user = GO()->getAuth()->sudo(function(){
			return \GO\Core\Users\Model\User::find(['username' => $username])->single();
		});

		GO()->getAuth()->setCurrentUser($user);
	}


}
