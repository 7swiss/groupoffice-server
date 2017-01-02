<?php

namespace GO\Modules\GroupOffice\Messages\Model;

use GO\Core\Accounts\Model\Account;
use GO\Modules\GroupOffice\Messages\Model\Message;
use GO\Modules\GroupOffice\Messages\Model\Thread;
use IFW;
use IFW\Data\Filter\FilterOption;
use IFW\Data\Filter\MultiselectFilter;
use IFW\Orm\Query;
use PDO;

class AccountFilter extends MultiselectFilter {	
	
	private $options;
	
	public function getOptions() {
		
		if(isset($this->options)) {
			return $this->options;
		}
		
		$accounts = Account::find(['modelName' => \GO\Modules\GroupOffice\Messages\Module::getAccountModelNames()]);		
		
		$this->options = [];			

		foreach($accounts as $account) {			
			$option = new FilterOption($this, $account->id, $account->name);			
			$this->options[] = $option;
		}
		
		return $this->options;
	}	
//	
//	private function count($accountId) {
//		 return (int) Thread::find(
//                (new Query())
//                    ->select('count(DISTINCT t.id)')
//                    ->fetchMode(PDO::FETCH_COLUMN, 0)
//									->where(['accountId' => $accountId ,'messages.type' => ThreadMessage::TYPE_INCOMING,'messages.seen' => false])									
//                )->single();
//	}
	
	protected function getSelected() {
		
		$selected = parent::getSelected();
		if(empty($selected)) {
			$selected = [];
			foreach($this->getOptions() as $option) {
				$selected[] = $option->value;
			}
		}
		
		//return null result
		if(empty($selected)) {
			return [0];
		}
		
		return $selected;
	}

	
	public function apply(Query $query) {		
		$query->andWhere(['accountId' => $this->selected]);				
	}

}