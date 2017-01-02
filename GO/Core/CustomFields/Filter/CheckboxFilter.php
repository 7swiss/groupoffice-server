<?php
namespace GO\Core\CustomFields\Filter;

use IFW\Data\Filter\BooleanFilter;
use IFW\Orm\Query;


class CheckboxFilter extends BooleanFilter {
	
	use CustomFieldFilterTrait;
	
	public function apply(Query $query) {
		
		if(isset($this->selected)) {
			$query->joinRelation('customfields', false);
			$query->andWhere(['customfields.'.$this->field->databaseName => $this->selected]);
		}
	}

	public function count($checked) {
		$query = $this->collection->countQuery();		
		$this->collection->apply($query);		
		$query->where(['customfields.'.$this->field->databaseName => $checked]);
		
		return (int) call_user_func([$this->collection->getModelClassName(), 'find'], $query)->single();
	}

}