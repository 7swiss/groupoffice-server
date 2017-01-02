<?php

namespace GO\Modules\GroupOffice\Announcements\Controller;

use GO\Modules\GroupOffice\Announcements\Model\Announcement;
use GO\Core\Upload\Controller\ThumbController;

/**
 * The controller for address books
 *
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class AnnouncementThumbController extends ThumbController {

	
	protected function thumbGetFile() {
		$announcement = Announcement::findByPk(\GO()->getRouter()->routeParams['announcementId']);		

		if ($announcement) {		
			return $announcement->getPhotoFile();
		}
		
		return false;
	}


	protected function thumbUseCache() {
		return true;
	}

}
