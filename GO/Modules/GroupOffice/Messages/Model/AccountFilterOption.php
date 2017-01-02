<?php

namespace GO\Modules\GroupOffice\Messages\Model;

use IFW\Data\Filter\Filter;
use IFW\Data\Filter\FilterOption;

class AccountFilterOption extends FilterOption {
	
	public $accountModel;
	
	public function __construct(Filter $filter, $value, $label, $accountModel,  $count = null) {
		parent::__construct($filter, $value, $label, $count);
		
		$this->accountModel = $accountModel;
	}
}
