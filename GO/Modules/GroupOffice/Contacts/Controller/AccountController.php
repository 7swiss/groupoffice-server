<?php

namespace GO\Modules\GroupOffice\Contacts\Controller;

/**
 * The controller for contacts
 * 
 * See {@see Contact} model for the available properties
 * 
 * 
 *
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class AccountController extends \GO\Core\Controller {
	public function actionStore() {
		$query = (new Query())
						->orderBy(['t.name' => 'ASC']);
						
		$capabilities = \GO\Core\Accounts\Model\Capability::find(
						(new Query)->tableAlias('capabilities')->where('capabilities.accountId = t.id')->andWhere(['modelName' => \GO\Modules\GroupOffice\Contacts\Model\Contact::class]));
			
		$query->andWhere(['EXISTS', $capabilities]);
		

		$accounts = GO()->getAuth()->sudo(function() use ($query) {
			return Account::find($query);
		});
		
		$accounts->setReturnProperties('*');

		$this->renderStore($accounts);
	}
}
