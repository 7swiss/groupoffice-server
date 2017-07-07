<?php

namespace GO\Modules\GroupOffice\Messages\Controller;

use GO\Core\Blob\Model\Blob;
use GO\Core\Controller;
use GO\Modules\GroupOffice\Messages\Model\Attachment;
use IFW\Util\ICalendarHelper;

class InteroperabilityController extends Controller {

	/**
	 * This will send a response to the organizer of an Event to caller is invited to
	 * @param int $accountId For now this is needed the fetch the current user
	 * @param int $attachmentId containing an ICS file
	 * @param string $status The participation status update of the invitee
	 */
	public function rsvp($accountId, $attachmentId, $status) {

		if(!in_array($status, ['ACCEPTED', 'DECLINED', 'TENTIATIVE'])) {
			throw new \Exception ('Incorrect response: '.$status);
		}

		$attachment = Attachment::findByPk($attachmentId);
		$currentUser = $attachment->message->to->address;
		$blob = Blob::findByPk($attachment->blobId);
		$vcalendar = ICalendarHelper::read($blob);

		ICalendarHelper::rsvp($vcalendar, $status, $currentUser);

	}

}
