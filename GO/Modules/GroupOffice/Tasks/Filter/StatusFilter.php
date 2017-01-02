<?php

namespace GO\Modules\GroupOffice\Tasks\Filter;

use IFW\Data\Filter\MultiselectFilter;
use IFW\Data\Filter\FilterOption;
use IFW\Orm\Query;
use GO\Modules\GroupOffice\Tasks\Model\Task;

class StatusFilter extends MultiselectFilter {

	public function apply(Query $query) {
		if(!empty($this->getSelected() && count($this->getSelected()) == 1)) {		
			foreach($this->getSelected() as $index=>$status){
				$this->applyQuery($status, $query);
			}
		}
	}

	public function getOptions() {
		return [
				new FilterOption($this, Task::STATUS_FINISHED, "TASK_STATUS_FINISHED", $this->_count(Task::STATUS_FINISHED)),
				new FilterOption($this, Task::STATUS_UNFINISHED, "TASK_STATUS_UNFINISHED", $this->_count(Task::STATUS_UNFINISHED))
		];
	}

	private function _count($status){
		
		$query = $this->collection->countQuery();		
		$this->collection->apply($query, $this);	
		
		$this->applyQuery($status, $query);
		

		return intval(Task::find($query)->single());
		
	}
	
	private function applyQuery($status,$query){
		switch($status){
			case Task::STATUS_FINISHED:
				$query->where(['AND', '!=', ['completedAt' => null]]);
				break;
			case Task::STATUS_UNFINISHED:
				$query->where(['AND', '=', ['completedAt'=>null]]);
				break;
		}
	}
	

}
