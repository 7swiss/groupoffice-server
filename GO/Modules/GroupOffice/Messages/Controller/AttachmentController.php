<?php
namespace GO\Modules\GroupOffice\Messages\Controller;

use GO\Core\Controller;
use GO\Modules\GroupOffice\Messages\Model\Attachment;

class AttachmentController extends Controller {

	public function __construct() {
		\GO\Core\Auth\Model\Token::$allowCookie = true;
		parent::__construct();
	}
	public function read($messageId, $attachmentId) {
		
		//cache for a month
//		$this->cacheHeaders(null, "attachment-".$attachmentId, new \DateTime('@'.(time()+86400*30)));
		
		
		$attachment = Attachment::findByPk($attachmentId);
		$attachment->output();

	}
}