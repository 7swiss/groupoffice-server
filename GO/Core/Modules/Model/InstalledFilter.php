<?php
namespace GO\Core\Modules\Model;

use IFW\Data\Filter\FilterOption;
use IFW\Data\Filter\MultiselectFilter;
use IFW\Orm\Query;


class InstalledFilter extends MultiselectFilter {
	
	
		
	public function getOptions() {		
		$options = [
				new FilterOption($this, 'installed', 'installed'),
				new FilterOption($this, 'notinstalled', 'notinstalled')
		];		
		return $options;
	}
	

	
	public function apply(Query $query) {		
		
//		if(!empty($this->selected)) {			
//			$query->andWhere(['event.recordTypeId' => $this->selected]);
//		}
	}
		
	
	public function filter(\GO\Core\Modules\Model\Module $record) {
		if(empty($this->getSelected()) || count($this->getSelected()) == 2) {
			return true;
		}
		
		if($this->getSelected()[0]=='installed') {
			if($record->isNew()){
				return false;
			}
			
			if($record->deleted) {
				return false;
			}
			
			return true;
		}
		
		if($this->getSelected()[0]=='notinstalled') {

			if($record->isNew()){
				return true;
			}
			if($record->deleted) {
				return true;
			}
			
			return false;
		}
		
		return false;
	}
}
