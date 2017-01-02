<?php
namespace GO\Modules\GroupOffice\Contacts\Model;

use IFW\Data\Filter\FilterOption;
use IFW\Data\Filter\MultiselectFilter;
use IFW\Orm\Query;


class TypeFilter extends MultiselectFilter {
		
	public function getOptions() {
		
		return [
//				new FilterOption($this, "null", "Unknown", $this->_count("M")),
				new FilterOption($this, "0", "Person"),
				new FilterOption($this, "1", "Organization")
		];
//		
//		$query = $this->collection->countQuery();
//		$this->collection->apply($query);
//		
//		$query->select('COUNT(DISTINCT t.id) AS count, gender')
//						->setFetchMode(PDO::FETCH_ASSOC)
//						->groupBy(['gender']);
//		
//		$records = call_user_func([$this->collection->getModelClassName(), 'find'], $query);
//		
//		$results = [];
//		foreach($records as $record) {
//			if($record['gender'] != null) { //Don't return option for unknown gender
//				$option = new FilterOption($this, $record['gender'], $record['gender'], $record['count']);			
//				$results[] = $option;
//			}
//		}
//		
//		return $results;
	}
	
	private function count($isOrganization){
		
		$query = $this->collection->countQuery();		
		$this->collection->apply($query, $this);		
		$query->where(['t.isOrganization' => $isOrganization])->debug();
		
		return (int) call_user_func([$this->collection->getModelClassName(), 'find'], $query)->single();
	}
	
	public function apply(Query $query) {		
		
		if(!empty($this->selected)) {			
			$query->andWhere(['isOrganization' => $this->selected]);
		}
	}
}