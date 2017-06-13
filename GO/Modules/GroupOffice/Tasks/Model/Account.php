<?php
namespace GO\Modules\GroupOffice\Tasks\Model;

use GO\Core\Accounts\Model\AccountAdaptorModel;

/**
 * The Account model
 *
 * @copyright (c) 2017, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */

class Account extends AccountAdaptorModel {
	
	public static function getCapabilities() {
		return [Task::class];
	}

}
