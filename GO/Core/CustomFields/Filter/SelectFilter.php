<?php
namespace GO\Core\CustomFields\Filter;

use IFW\Data\Filter\MultiselectFilter;
use IFW\Data\Filter\FilterOption;
use IFW\Orm\Query;


class SelectFilter extends MultiselectFilter {
	
	use CustomFieldFilterTrait;
	
	public function apply(Query $query) {
		
		if(isset($this->selected)) {
			$query->joinRelation('customfields', false);
			$query->andWhere(['customfields.'.$this->field->databaseName => $this->selected]);
		}
	}
	
	public function getOptions() {
		$options = [];
		foreach($this->field->data['options'] as $option) {				
			$options[] = new FilterOption($this, $option, $option, $this->_count($option));
		}
		
		return $options;	
	}

	private function _count($value) {
		$query = $this->collection->countQuery();		
		$this->collection->apply($query);		
		$query->where(['customfields.'.$this->field->databaseName => $value]);
		
		return (int) call_user_func([$this->collection->getModelClassName(), 'find'], $query)->single();
	}

}