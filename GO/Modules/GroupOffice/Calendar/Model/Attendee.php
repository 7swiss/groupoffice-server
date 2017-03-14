<?php
/*
 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Michael de Hart <mdhart@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */

namespace GO\Modules\GroupOffice\Calendar\Model;

use IFW\Orm\Record;
use IFW\Auth\Permissions\ViaRelation;
use GO\Core\Users\Model\User;
use IFW\Orm\Query;

/**
 * Attendee records hold attendee / guest information.
 * An attendee can be an individual (guest) or a resource (room/equipment)
 * They also hodl the guest's attendens response (RSVP)
 *
 * @property Alarms[] $alarms ringing bells about the event
 * @property Event $event The event the Attendee is attening to
 * @property Calendar $calendar The calendar the attendens to the event is shown in
 * @property Group $group When a user in the system is an attendee the id gets saved
 */
class Attendee extends Record {
	
	/**
	 * foreignkey to the event
	 * @var int
	 */							
	public $eventId;

	/**
	 * foreignkey to user
	 * @var string
	 */							
	public $email;

	/**
	 * wither participation is required, option, chairman or not participating (None)
	 * @var int
	 */							
	public $role = 1;

	/**
	 * Whether the attendee has accepted, declined, delegated or is tentative.
	 * @var int
	 */							
	public $responseStatus = AttendeeStatus::NeedsAction;

	/**
	 * foreign key to the calendar
	 * @var int
	 */							
	public $calendarId;

	/**
	 * user linked to this event
	 * @var int
	 */							
	public $groupId;

	// DEFINE

	static public function me() {
		$me = new self();
		$me->email = \GO()->getAuth()->user()->email;
		$me->groupId = Group::current()->id;
		$defaultCalendar = $me->findDefaultCalendar();
		$me->setCalendar($defaultCalendar);
		$me->responseStatus = AttendeeStatus::Accepted;
		$event = new Event();
		$me->event = $event;
		//$me->event->attendees[] = $me;
		$me->event->organizerEmail = $me->email;
		return $me;
	}

	/**
	 * For now just the first this attendee owns
	 * @return type
	 */
	private function findDefaultCalendar() {
		$groupId = empty($this->groupId) ? Group::current()->id : $this->groupId;
		return Calendar::find((new Query)->select('id')->where(['ownedBy'=>$groupId]))->single();
	}

	// OVERRIDES

	public static function tableName() {
		return 'calendar_attendee';
	}

	public static function internalGetPermissions() {
		return new EventPermission();
	}
	
	protected static function defineRelations() {
		self::hasOne('event', Event::class, ['eventId' => 'id']);
		self::hasOne('calendar', Calendar::class, ['calendarId' => 'id']);
		self::hasMany('alarms', Alarm::class, ['eventId' => 'eventId', 'groupId' => 'groupId']);
		self::hasOne('group', Group::class, ['groupId'=> 'id']);
	}

	protected static function defineValidationRules() {
		return [
			new \IFW\Validate\ValidateEmail('email')
		];
	}

	protected function internalSave() {
		if(empty($this->calendarId)) {
			GO()->getAuth()->sudo(function(){
				$user = User::find(['email'=>$this->email])->single();
				if(!empty($user)) {
					$this->groupId = $user->group->id;
					$defaultCalendar = $this->findDefaultCalendar();
					if(!empty($defaultCalendar)) {
						$this->calendarId = $defaultCalendar->id;
					}
				}

			});
		}
		$this->event->save(); // call save to send invites & updates if needed
		
		return parent::internalSave();
	}
	
	// ATTRIBUTES
	/**
	 * True is this attendee is the organizer of the event it attends to.
	 * @return bool
	 */
	public function getIsOrganizer() {
		if(!empty($this->event)) {
			return $this->event->organizerEmail == $this->email;
		}
		return false;
	}

	public function getCanWrite() {
		if(empty($this->calendarId)) {
			return false; // when attendee has no calendar
		}
		return $this->getPermissions()->can("update") && $this->getIsOrganizer();
	}

	public function addAlarms($defaultAlarms) {
		foreach($defaultAlarms as $defaultAlarm) {
			$defaultAlarm->addTo($this);
		}
	}

	/**
	 * TODO analyse rowCount performance
	 * @return type
	 */
	public function getHasAlarms() {
		if($this->groupId == Group::current()->id) {
			return $this->alarms->getRowCount() > 0;
		}
		return null;
	}

	public function setCalendar($calendar) {
		if(empty($calendar)) {
			return; // the user has no calendar
		}
		$this->calendarId = $calendar->id;
		if($this->isNew()) {
			$this->addAlarms($calendar->defaultAlarms);
		}
		
	}

	/**
	 * An attendee can be an Individual, Resource (or Group)
	 * @return PrincipalType
	 */
	public function getType() {
		return PrincipalInterface::Individual;
	}

	public function getName() {
		if(empty($this->user))
			return '';
		return $this->user->getName();
	}
	
	// OPERATIONS

	/**
	 * When the organizer deletes the participation, the event itself will be deleted.
	 * @param type $hard
	 * @return boolean
	 */
	protected function internalDelete($hard) {
		if($this->getIsOrganizer()) {
			return $this->event->delete();
		}

		return parent::internalDelete($hard);
	}
}
