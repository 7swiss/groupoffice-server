<?php

namespace GO\Modules\GroupOffice\Calendar\Model;

use GO\Core\Orm\Record;

/**
 * Exception to a recurrence rule
 *
 * @property int $eventId the id of the recurring event that this is an exception of
 * @property Datetime $date start date of the exception
 * @property int $replaceEventId the event that is replacing the origional occurence. If null the occurence is deleted
 *
 * @author mdhart
 */
class RecurrenceException extends Record {
	// table: calendar_recurrence_exception
}
