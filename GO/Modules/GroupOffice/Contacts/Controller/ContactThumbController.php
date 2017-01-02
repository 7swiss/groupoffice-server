<?php

namespace GO\Modules\GroupOffice\Contacts\Controller;

use GO\Core\Users\Model\User;
use GO\Modules\GroupOffice\Contacts\Model\Contact;
use GO\Core\Upload\Controller\ThumbController;

/**
 * The controller for address books
 *
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class ContactThumbController extends ThumbController {
	
	

	protected function thumbGetFile() {

//		$contact = false;

//		if (isset($_GET['userId'])) {
//			$user = User::findByPk($_GET['userId']);
//
//			if ($user) {
//				$contact = $user->contact;
//			}
//		} else if (isset($_GET['contactId'])) {
//			$contact = Contact::findByPk($_GET['contactId']);
//		} elseif (isset($_GET['email'])) {
//
//			$query = (new Query())
//					->joinRelation('emailAddresses')
//					->groupBy(['t.id'])
//					->where(['emailAddresses.email' => $_GET['email']]);
//
//			$contact = Contact::findPermitted($query, 'readAccess')->single();
//		}
		
		if(isset(\GO()->getRouter()->routeParams['userId'])) {
			$user = User::findByPk(\GO()->getRouter()->routeParams['userId']);

			if ($user) {
				$contact = $user->contact;
			}
		}else {
			$contact = !empty(\GO()->getRouter()->routeParams['contactId']) ? Contact::findByPk(\GO()->getRouter()->routeParams['contactId']) : false;
		}

		if (!$contact) {
			$contact = new Contact();
		}else
		{			
//			if (!$contact->checkPermission('readAccess')) {
//				throw new Forbidden();
//			}
		}
		
		return $contact->getPhoto()->getFile();
	}

	protected function thumbUseCache() {
		return true;
	}

}
