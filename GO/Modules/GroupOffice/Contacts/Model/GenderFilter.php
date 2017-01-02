<?php
namespace GO\Modules\GroupOffice\Contacts\Model;

use IFW\Data\Filter\FilterOption;
use IFW\Data\Filter\MultiselectFilter;
use IFW\Orm\Query;


class GenderFilter extends MultiselectFilter {
		
	public function getOptions() {
		
		return [
//				new FilterOption($this, "null", "Unknown", $this->_count("M")),
				new FilterOption($this, "M", "Male"),
				new FilterOption($this, "F", "Female")
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
	
	private function count($gender){
		
		$query = $this->collection->countQuery();		
		$this->collection->apply($query);		
		$query->where(['t.gender' => $gender])->debug();
		
		return (int) call_user_func([$this->collection->getModelClassName(), 'find'], $query)->single();
	}
	
	public function apply(Query $query) {		
		
		if(!empty($this->selected)) {			
			$query->andWhere(['gender' => $this->selected]);
		}
	}
}