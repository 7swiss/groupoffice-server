<?php
/*
 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Michael de Hart <mdhart@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
namespace GO\Modules\GroupOffice\Calendar\Model;

use GO\Core\Orm\Record;

/**
 * An alarms will ring a bell on a set datum / time
 * The time depends on the object it is attached to
 *
 * @property string $individualEmail emai lof the attendee that the alarm is for
 */
class Alarm extends Record {

	const RelStart = 1;
	const RelEnd = 2;

	/**
	 * auto increment primary key
	 * @var int
	 */							
	public $id;

	/**
	 * ISO 8061 period specification (without fractions)
	 * @var string
	 */							
	public $offsetDuration;

	/**
	 * Time to trigger the alarm. If this is set secondsFrom will be ignorerd
	 * @var \DateTime
	 */							
	public $triggerAt;

	/**
	 * @var int
	 */							
	public $relativeTo = self::RelStart;

	/**
	 * PK of the evetn this alarm is set on
	 * @var int
	 */							
	public $eventId;

	/**
	 * 
	 * @var int
	 */							
	public $groupId;

	const Start = 1;
	const End = 2;

	public function internalValidate() {
		if($this->isModified('trigger'))  {
			$di = new \DateInterval($this->trigger);
			$time = new \DateTime;
			switch($this->relativeTo) {
				case self::Start:
					$time = $this->attendee->event->startAt;
					break;
				case self::End:
					$time = $this->attendee->event->endAt;
					break;
			}
			$this->triggerAt = $time->sub($di);
		}

		return parent::internalValidate();
	}

	protected static function defineRelations() {
		self::hasOne('attendee', Attendee::class, ['eventId'=>'eventId', 'groupId' => 'groupId']);
	}

	public function addTo(Attendee $attendee) {
		$attendee->alarms[] = $this;
	}

}
