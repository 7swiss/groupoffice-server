<?php

namespace GO\Core\Install\Controller;

use GO\Core\Controller;
use GO\Core\Install\Model\System;
use GO\Core\Install\Model\SystemCheck;
use IFW;

/**
 * Perform system update
 *
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class SystemController extends Controller {

	/**
	 * Authenticate the current user
	 *
	 * Override this for special use cases.
	 *
	 * @return boolean
	 */
	protected function checkAccess() {
		return true;
	}

	/**
	 * Run system tests
	 */
	public function actionInstall() {
		
		$this->lock();

		$system = new System();

		$success = $system->install();

		$this->render(['success' => $success]);
	}

	/**
	 * Run system tests
	 */
	public function actionCheck() {

		$systemCheck = new SystemCheck();

		$this->render($systemCheck->run());
	}

	/**
	 * Run system tests
	 */
	public function actionUpgrade($skipFirstError = false) {
		
		$this->lock();

		//run as admin
		GO()->getCache()->flush(); // Sudo cant fetch user with old cache

		GO()->getAuth()->sudo(function() use ($skipFirstError) {
			$system = new System();
			$system->upgrade($skipFirstError);
		});

		$this->render([]);
	}

}
