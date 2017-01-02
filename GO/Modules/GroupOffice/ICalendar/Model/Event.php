<?php

namespace GO\Modules\GroupOffice\ICalendar\Model;

use IFW\Data\Model;

class Event extends Model {	
	
	public $id;
	
	public $hasTime = true;
	public $computedStartTime;
	public $computedEndTime;
	public $summary;
}