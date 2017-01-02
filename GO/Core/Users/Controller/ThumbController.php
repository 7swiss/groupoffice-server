<?php

namespace GO\Core\Users\Controller;

use GO\Core\Users\Model\User;
use GO\Core\Upload\Controller\ThumbController as CoreThumbController;

/**
 * The controller for address books
 *
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class ThumbController extends CoreThumbController {
	
	

	protected function thumbGetFile() {

		$user = User::findByPk(\GO()->getRouter()->routeParams['userId']);
		

		if(!$user->getPhoto()->hasFile()) {			
			
			$url = "https://www.gravatar.com/avatar/".md5($user->email).'?d=identicon';
			
			if(isset(GO()->getRequest()->queryParams['w'])) {
				$url .= '&s='.GO()->getRequest()->queryParams['w'];
			}
			
			GO()->getResponse()->redirect($url);
		}
		
		return $user->getPhoto()->getFile();
	}

	protected function thumbUseCache() {
		return true;
	}

}

