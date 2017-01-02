<?php

namespace GO\Modules\GroupOffice\Messages\Controller;

use GO\Core\Accounts\Model\Account;
use GO\Core\Controller;
use GO\Modules\GroupOffice\Messages\Module;
use IFW\Orm\Query;

/**
 * The controller for the message model
 *
 * @copyright (c) 2015, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class AccountController extends Controller {

	public function actionStore($q=null) {
		
		
		$query = (new Query())
						->orderBy(['name' => 'ASC'])
						->where(['modelName' => Module::getAccountModelNames()]);
		
		if(isset($q)) {
			$query->setFromClient($q);			
		}
						
		$accounts = Account::find($query);		
		$this->renderStore($accounts);		
	}
}
		
