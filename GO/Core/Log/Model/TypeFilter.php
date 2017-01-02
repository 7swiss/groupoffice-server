<?php
namespace GO\Core\Log\Model;

use IFW\Data\Filter\FilterOption;
use IFW\Data\Filter\MultiselectFilter;
use IFW\Orm\Query;


class TypeFilter extends MultiselectFilter {
		
	public function getOptions() {
		
		$query = (new Query())
						->distinct()
						->select('type')
						->fetchMode(\PDO::FETCH_COLUMN, 0);
		
		$names = Entry::find($query);
		
		$options = [];
		
		foreach($names as $name) {
			$options[] = new FilterOption($this, $name, $name);
		}
		
		return $options;
	}
	

	
	public function apply(Query $query) {		
		
		if(!empty($this->selected)) {			
			$query->andWhere(['type' => $this->selected]);
		}
	}
}

