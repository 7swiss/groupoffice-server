<?php

namespace GO\Modules\GroupOffice\ICalendar\Model;

use DateTime;
use IFW;
use IFW\Auth\Permissions\CreatorOnly;
use IFW\Data\Filter\FilterCollection;
use IFW\Data\Store;
use IFW\Orm\Record;
use IFW\Web\Response;
use GO\Modules\GroupOffice\ICalendar\ICalendarIterator;
use GO\Modules\GroupOffice\Tasks\Controller\TaskController;
use GO\Modules\GroupOffice\Tasks\Filter\ExecutorFilter;
use GO\Modules\GroupOffice\Tasks\Model\TaskSorter;

/**
 * Imports icalendar feeds
 * 
 * These events are used for calculating task start times and are shown in the
 * task list. 
 */
class Account extends Record {

	/**
	 * 
	 * @var int
	 */							
	public $id;

	/**
	 * 
	 * @var bool
	 */							
	public $deleted = false;

	/**
	 * 
	 * @var int
	 */							
	public $ownedBy;

	/**
	 * 
	 * @var string
	 */							
	public $name;

	/**
	 * 
	 * @var string
	 */							
	public $url;

	public static function internalGetPermissions() {
		return new CreatorOnly();
	}

	public static function syncAll() {
		$accounts = Account::find();

		foreach ($accounts as $account) {
			$account->sync();
		}
	}

//	public static function defineEvents() {
//
//		TaskController::on(TaskController::EVENT_STORE, self::class, 'addEvents');
//		TaskSorter::on(TaskSorter::EVENT_BEFORE_SORT, self::class, 'blockEventsInWorkingWeek');
//		
//		parent::defineEvents();
//	}

	/**
	 * Synchronizes the account
	 * 
	 * @return boolean
	 */
	public function sync() {

		try {
			$fp = fopen($this->url, 'r');

			if (!$fp) {
				GO()->debug("Failed to sync account ID " . $this->id);
				return false;
			}
		}catch(\ErrorException $e) {
			GO()->debug("Failed to sync account ID " . $this->id. " ".$e->getMessage());
				
			return false;
		}

		$icalendarIterator = new ICalendarIterator($fp, 'VEVENT');
		foreach ($icalendarIterator as $veventData) {
			VObject::createFromIcalendarData($this->id, $veventData);
		}

		return true;
	}
	
	public static function blockEventsInWorkingWeek(TaskSorter $taskSorter) {
		$start = new \DateTime();
		$end = new DateTime('+1 month');
		
		$vevents = VObject::findVEvents($start, $end);
		
		foreach ($vevents as $vevent) {			
			$taskSorter->blockTime((string) $vevent->{"X-GO-USER-ID"}, $vevent->dtstart->getDateTime(), $vevent->dtend->getDateTime());
		}
	}
	
	/**
	 * Get events for a given time period
	 * 
	 * @param DateTime $start
	 * @param DateTime $end
	 * @return Event
	 */
	public static function addEvents(Response $response,  Store $store, FilterCollection $filterCollection) {

		$start = new \DateTime();
		
//		if(!is_array($store->traversable)) {
//			$store->traversable = $store->traversable->all();
//		}
//		
//		$lastTask = end($response['results']);
//		reset($response['results']);
//		
//		if($lastTask) {			
//			$end = clone $lastTask->computedEndTime;			
//			$end->add(new \DateInterval('P1M'));
//		}else
//		{
			$end = new DateTime('+1 month');
//		}
			
		$executorFilter = $filterCollection->getFilter(ExecutorFilter::class);
		
		
		$vevents = VObject::findVEvents($start, $end, ['account.createdBy' =>$executorFilter->getSelected()]);

		foreach ($vevents as $vevent) {

			$event = new Event();
			$event->id = uniqid();
			$event->hasTime = $vevent->dtstart->hasTime();
			$event->summary = (string) $vevent->summary;
			$event->computedStartTime = $vevent->dtstart->getDateTime();
			$event->computedEndTime = $vevent->dtend->getDateTime();

			$response->data['results'][] = $event->toArray();
		}

		
		

	}

}
