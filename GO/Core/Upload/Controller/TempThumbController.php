<?php
namespace GO\Core\Upload\Controller;

use GO\Core\Upload\Controller\ThumbController;

/**
 * The controller for address books
 *
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class TempThumbController extends ThumbController {

protected function thumbGetFile() {
		return GO()->getAuth()->getTempFolder()->getFile(GO()->getRouter()->routeParams['tempFile']);
	}
	
	protected function thumbUseCache() {
		return false;
	}

}

