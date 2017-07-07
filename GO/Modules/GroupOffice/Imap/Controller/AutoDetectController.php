<?php

namespace GO\Modules\GroupOffice\Imap\Controller;

use GO\Core\Controller;
use GO\Core\Smtp\Model\SMTPDetector;
use IFW\Imap\IMAPDetector;

/**
 * The controller for accounts. Admin group is required.
 *
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class AutoDetectController extends Controller {

	public function actionNew() {
		
		$detector = new IMAPDetector();
		$this->renderModel($detector);
	}
	
	public function actionDetect() {
		
		$data = GO()->getRequest()->body['data'];
		unset($data['validationErrors'], $data['smtpAccount']);
		
		$detector = new IMAPDetector();
		$detector->setValues($data);
		$detector->detect();
		
		
//		$detector->smtpAccount = new SMTPDetector();
//		$detector->smtpAccount->setValues($data);
//		$detector->smtpAccount->detect();
		
		$this->renderModel($detector);
	}
	
}