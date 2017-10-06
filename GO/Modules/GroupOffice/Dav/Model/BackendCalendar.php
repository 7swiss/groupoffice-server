<?php

namespace GO\Modules\GroupOffice\Dav\Model;

use Sabre\CalDAV;
use Sabre\CalDAV\Backend\AbstractBackend;
use Sabre\CalDAV\Backend\SyncSupport;
use Sabre\DAV;

use GO;
use IFW\Orm\Query;
use GO\Core\Users\Model\User;
use GO\Modules\GroupOffice\Calendar\Model\Event As CalendarEvent;
use GO\Modules\GroupOffice\Calendar\Model\Calendar;
use GO\Modules\GroupOffice\Calendar\Model\ICalendarHelper;

/**
 * Things todo:
 * - calendar transp setting
 * - calendar-timezone, now hardcode to europe/amsterdam
 * - supported component VTODO (now VEVENT only)
 * - validate if save successful
 * - etag md5 of data
 * - fix delete instance
 * - getChangesForCalendar implementation
 */
class BackendCalendar extends AbstractBackend implements SyncSupport {

	/**
	 * We need to specify a max date, because we need to stop *somewhere*
	 *
	 * On 32 bit system the maximum for a signed integer is 2147483647, so
	 * MAX_DATE cannot be higher than date('Y-m-d', 2147483647) which results
	 * in 2038-01-19 to avoid problems when the date is converted
	 * to a unix timestamp.
	 */
	const MAX_DATE = '2038-01-01';

	function getCalendarsForUser($principalUri) {

		$ctag = GO()->getDbConnection()->query('SELECT max(modifiedAt) as highestModTime FROM calendar_event')->fetchColumn();

		$calendars = Calendar::find()->all();
		$result = [];
		foreach ($calendars as $calendar) {
			/* @var $calendar Calendar */
			$result[] = [
				 'id' => $calendar->id,
				 'uri' => $calendar->getUri(),
				 'principaluri' => $principalUri,
				 '{http://calendarserver.org/ns/}getctag' => 'GroupOffice/calendar/' . $ctag,
				 '{http://sabredav.org/ns}sync-token' => $ctag,
				 '{urn:ietf:params:xml:ns:caldav}supported-calendar-component-set' => new CalDAV\Xml\Property\SupportedCalendarComponentSet(['VEVENT']),
				 '{urn:ietf:params:xml:ns:caldav}schedule-calendar-transp' => new CalDAV\Xml\Property\ScheduleCalendarTransp('opaque'),
				 '{DAV:}displayname' => $calendar->name,
				 '{urn:ietf:params:xml:ns:caldav}calendar-description' => 'User calendar',
				 '{urn:ietf:params:xml:ns:caldav}calendar-timezone' => 'Europe/Amsterdam',
				 'read-only' => !$calendar->permissions->can('write'),
				 //			'access'=> \Sabre\DAV\Sharing\Plugin::ACCESS_READWRITE
				 //			'{http://apple.com/ns/ical/}calendar-order' => $calendar->id,
				 '{http://apple.com/ns/ical/}calendar-color' => '#' . $calendar->color
			];
		}
		return $result;
	}

	function createCalendar($principalUri, $calendarUri, array $properties) {

		throw new DAV\Exception\Forbidden();
	}

	function updateCalendar($calendarId, DAV\PropPatch $propPatch) {
		return true;
	}

	function deleteCalendar($calendarId) {

		throw new DAV\Exception\Forbidden();
	}

	/**
	 * Returns all calendar objects within a calendar.
	 *
	 * Every item contains an array with the following keys:
	 *   * calendardata - The iCalendar-compatible calendar data
	 *   * uri - a unique key which will be used to construct the uri. This can
	 *     be any arbitrary string, but making sure it ends with '.ics' is a
	 *     good idea. This is only the basename, or filename, not the full
	 *     path.
	 *   * lastmodified - a timestamp of the last modification time
	 *   * etag - An arbitrary string, surrounded by double-quotes. (e.g.:
	 *   '  "abcdef"')
	 *   * size - The size of the calendar objects, in bytes.
	 *   * component - optional, a string containing the type of object, such
	 *     as 'vevent' or 'vtodo'. If specified, this will be used to populate
	 *     the Content-Type header.
	 * @param mixed $calendarId
	 * @return array
	 */
	function getCalendarObjects($calendarId) {

		$calendar = Calendar::findByPk($calendarId);
		if (empty($calendar)) {
			throw new \InvalidArgumentException('Calendar with id '. $calendarId. ' was not found');
		} else {
			
		}
		GO()->getDebugger()->setSection(\IFW\Debugger::SECTION_CONTROLLER);

		$events = Event::find((new Query)->select('t.*')
				  ->join('calendar_attendee', 'a', 't.id = a.eventId')
				  ->where(['a.calendarId'=>$calendarId, 'a.groupId' => $calendar->ownedBy]));
//var_dump($events->getQuery());
//var_dump($calendar->ownedBy);
		$result = [];
		foreach ($events as $event) {
			$result[] = [
				 'id' => $event->id.'-'.$calendar->ownedBy,
				 'uri' => $event->uuid.'-'.$event->id.'-'.$calendar->ownedBy,
				 'lastmodified' => $event->modifiedAt->getTimestamp(),
				 'etag' => '"' . $event->modifiedAt->getTimestamp() . '"',
				 'calendarid' => $calendarId
				 //'size' => (int) $event->size,
				 //'component' => 'vevent',
			];
		}

		return $result;
	}

	/**
	 * @param mixed $calendarId
	 * @param string $objectUri
	 * @return array|null
	 */
	function getCalendarObject($calendarId, $objectUri) {

//		if (!is_array($calendarId)) {
//			throw new \InvalidArgumentException('The value passed to $calendarId is expected to be an array with a calendarId and an instanceId');
//		}
//		list($calendarId, $instanceId) = $calendarId;
		$uriParts = explode('-', $objectUri);
		$groupId = array_pop($uriParts);
		$eventId = array_pop($uriParts);
		
		$event = Event::find((new Query)
				  ->join('calendar_attendee', 'a', 't.id = a.eventId')
				  ->where(['a.calendarId'=>$calendarId, 'a.groupId' => $groupId, 't.id'=>$eventId]))->single();

		if (!$event)
			return null;

		$calendarData = ICalendarHelper::toVObject($event)->serialize();

		return [
			'id' => $event->id.'-'.$groupId,
			'uri' => $event->uuid.'-'.$event->id.'-'.$groupId,
			'lastmodified' => $event->modifiedAt->getTimestamp(),
			'etag' => '"' . $event->modifiedAt->getTimestamp() . '"',
			'calendardata' => $calendarData,
			'size' => strlen($calendarData),
		];
	}

	/**
	 * @param mixed $calendarId
	 * @param array $uris
	 * @return array
	 */
	function getMultipleCalendarObjects($calendarId, array $uris) {

		if (!is_array($calendarId)) {
			throw new \InvalidArgumentException('The value passed to $calendarId is expected to be an array with a calendarId and an instanceId');
		}
		list($calendarId, $instanceId) = $calendarId;

		$result = [];
		foreach (array_chunk($uris, 900) as $chunk) {
			array_walk($chunk, function($item){ return substr($item, 0, strpos($item, '-')); });

			$events = Event::find((new Query)
				  ->join('calendar_attendee', 'a',['id'=>'eventId'])
				  ->where(['a.calendarId'=>$calendarId, 'a.groupId' => $groupId, 'a.eventId'=>$chunk]));

			foreach($events as $event) {
				$calendarData = ICalendarHelper::toVObject($event);
				$result[] = [
					'id' => $event->id.'-'.$event->groupId,
					'uri' => $event->name.'-'.$event->id.'-'.$event->groupId,
					'lastmodified' => $event->modifiedAt->getTimestamp(),
					'etag' => '"' . $event->modifiedAt->getTimestamp() . '"',
					'calendardata' => $calendarData,
					'size' => strlen($calendarData),
				];
			}
		}
		return $result;
	}

	/**
	 * @param mixed $calendarId
	 * @param string $objectUri
	 * @param string $calendarData
	 * @return string|null
	 */
	function createCalendarObject($calendarId, $objectUri, $calendarData) {

		if (!is_array($calendarId)) {
			throw new \InvalidArgumentException('The value passed to $calendarId is expected to be an array with a calendarId and an instanceId');
		}
		list($calendarId, $instanceId) = $calendarId;

		$event = ICalendarHelper::fromVObject(ICalendarHelper::read($calendarData), $calendarId);
		$event->save();
		return '"' . $event->modifiedAt->getTimestamp() . '"';
	}

	/**
	 * @param mixed $calendarId
	 * @param string $objectUri
	 * @param string $calendarData
	 * @return string|null
	 */
	function updateCalendarObject($calendarId, $objectUri, $calendarData) {

		if (!is_array($calendarId)) {
			throw new \InvalidArgumentException('The value passed to $calendarId is expected to be an array with a calendarId and an instanceId');
		}
		list($calendarId, $instanceId) = $calendarId;

		$event = ICalendarHelper::fromVObject(ICalendarHelper::read($calendarData), $calendarId);
		$event->update();
		return '"' . $event->modifiedAt->getTimestamp() . '"';
	}

	/**
	 * Deletes an existing calendar object.
	 *
	 * The object uri is only the basename, or filename and not a full path.
	 *
	 * @param mixed $calendarId
	 * @param string $objectUri
	 * @return void
	 */
	function deleteCalendarObject($calendarId, $objectUri) {

		if (!is_array($calendarId)) {
			throw new \InvalidArgumentException('The value passed to $calendarId is expected to be an array with a calendarId and an instanceId');
		}
		list($calendarId, $instanceId) = $calendarId;
		
		Event::delete(explode('-', $instanceId));
	}

	/**
	 * Searches through all of a users calendars and calendar objects to find
	 * an object with a specific UID.
	 *
	 * This method should return the path to this object, relative to the
	 * calendar home, so this path usually only contains two parts:
	 *
	 * calendarpath/objectpath.ics
	 *
	 * If the uid is not found, return null.
	 *
	 * This method should only consider * objects that the principal owns, so
	 * any calendars owned by other principals that also appear in this
	 * collection should be ignored.
	 *
	 * @param string $principalUri
	 * @param string $uid
	 * @return string|null
	 */
	function getCalendarObjectByUID($principalUri, $uid) {

		$query = <<<SQL
SELECT
    calendar_instances.uri AS calendaruri, calendarobjects.uri as objecturi
FROM
    $this->calendarObjectTableName AS calendarobjects
LEFT JOIN
    $this->calendarInstancesTableName AS calendar_instances
    ON calendarobjects.calendarid = calendar_instances.calendarid
WHERE
    calendar_instances.principaluri = ?
    AND
    calendarobjects.uid = ?
SQL;

		$stmt = $this->pdo->prepare($query);
		$stmt->execute([$principalUri, $uid]);

		if ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
			return $row['calendaruri'] . '/' . $row['objecturi'];
		}
	}

	/**
	 * @param mixed $calendarId
	 * @param string $syncToken
	 * @param int $syncLevel
	 * @param int $limit
	 * @return array
	 */
	function getChangesForCalendar($calendarId, $syncToken, $syncLevel, $limit = null) {

		if (!is_array($calendarId)) {
			throw new \InvalidArgumentException('The value passed to $calendarId is expected to be an array with a calendarId and an instanceId');
		}
		list($calendarId, $instanceId) = $calendarId;

		// Current synctoken
		$stmt = $this->pdo->prepare('SELECT synctoken FROM ' . $this->calendarTableName . ' WHERE id = ?');
		$stmt->execute([$calendarId]);
		$currentToken = $stmt->fetchColumn(0);

		if (is_null($currentToken))
			return null;

		$result = [
			 'syncToken' => $currentToken,
			 'added' => [],
			 'modified' => [],
			 'deleted' => [],
		];

		if ($syncToken) {

			$query = "SELECT uri, operation FROM " . $this->calendarChangesTableName . " WHERE synctoken >= ? AND synctoken < ? AND calendarid = ? ORDER BY synctoken";
			if ($limit > 0)
				$query .= " LIMIT " . (int) $limit;

			// Fetching all changes
			$stmt = $this->pdo->prepare($query);
			$stmt->execute([$syncToken, $currentToken, $calendarId]);

			$changes = [];

			// This loop ensures that any duplicates are overwritten, only the
			// last change on a node is relevant.
			while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {

				$changes[$row['uri']] = $row['operation'];
			}

			foreach ($changes as $uri => $operation) {

				switch ($operation) {
					case 1 :
						$result['added'][] = $uri;
						break;
					case 2 :
						$result['modified'][] = $uri;
						break;
					case 3 :
						$result['deleted'][] = $uri;
						break;
				}
			}
		} else {
			// No synctoken supplied, this is the initial sync.
			$result['added'] = GO()->getDbConnection()->query('SELECT CONCAT(`groupId`, "-", `eventId`) as uri FROM contacts_contact WHERE calendarId = '.$calendarId)->fetchAll(\PDO::FETCH_COLUMN);
		}
		return $result;
	}

}
