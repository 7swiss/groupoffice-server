<?php
namespace GO\Core\CustomFields\Filter;

use IFW\Data\Filter\NumberrangeFilter;
use IFW\Orm\Query;

class NumberFilter extends NumberrangeFilter {
	
	use CustomFieldFilterTrait;
	
	public function apply(Query $query) {		
		if(!empty($this->min)) {			
			$query->andWhere(['>=',['customfields.'.$this->field->databaseName => $this->min]]);
		}
		
		if(!empty($this->max)) {			
			$query->andWhere(['<=',['customfields.'.$this->field->databaseName => $this->max]]);
		}
	}
}