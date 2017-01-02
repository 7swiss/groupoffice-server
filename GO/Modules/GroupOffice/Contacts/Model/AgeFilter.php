<?php
namespace GO\Modules\GroupOffice\Contacts\Model;

use DateInterval;
use DateTime;
use IFW\Data\Filter\NumberrangeFilter;
use IFW\Db\Column;
use IFW\Orm\Query;

class AgeFilter extends NumberrangeFilter {
	
	public function apply(Query $query) {		
		
		if(!empty($this->min)) {			
			
			$minDate = new DateTime();
			$minDate->setTime(0,0,0);
			
			$minInterval = new DateInterval("P".$this->min."Y");
			$minInterval->invert = true;
			
			$minDate->add($minInterval);
			
			$query->andWhere(['<=',['dates.date'=>$minDate->format(Column::DATE_FORMAT)]]);
		}
		
		if(!empty($this->max)) {			
			
			$maxDate = new DateTime();
			$maxDate->setTime(0,0,0);
			$maxInterval = new DateInterval("P".($this->max+1)."Y");
			$maxInterval->invert = true;
			
			$maxDate->add($maxInterval);
			
			$query->andWhere(['>',['dates.date'=>$maxDate->format(Column::DATE_FORMAT)]]);
		}
	}
}