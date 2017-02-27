<?php

namespace GO\Core\Email\Model;

use GO\Core\Smtp\Model\Account;
use Swift_Message;

class Message extends Swift_Message {

	/**
	 *
	 * @var Account 
	 */
	private $account;

	/**
	 * Create a new Message.
	 *
	 * Details may be optionally passed into the constructor.
	 * 
	 * @example
	 * ```````````````````````````````````````````````````````````````````````````
	 * $message = new \GO\Core\Email\Model\Message(
	 *						GO()->getSettings()->smtpAccount, 
	 *						"Test subject", 
	 *						"The body");
	 *		
	 * $message->setTo(GO()->getSettings()->smtpAccount->fromEmail, GO()->getSettings()->smtpAccount->fromName);
	 * 
	 * $message->attach(\Swift_Attachment::fromPath('path to file');
	 *		
	 * $numberOfRecipients = $message->send();
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * @param Account
	 * @param string $subject
	 * @param string $body
	 * @param string $contentType
	 * @param string $charset
	 * 
	 */
	public function __construct(Account $account, $subject = null, $body = null, $contentType = null, $charset = null) {

		$this->account = $account;

		parent::__construct($subject, $body, $contentType, $charset);

		$this->setFrom($this->account->fromEmail, $this->account->fromName);
	}
	
	public static function newInstance($subject = null, $body = null, $contentType = null, $charset = null) {
		throw new \Exception("Please construct a new object");
	}

	/**
	 * Send the message like it would be sent in a mail client.
	 *
	 * All recipients (with the exception of Bcc) will be able to see the other
	 * recipients this message was sent to.
	 *
	 * Recipient/sender data will be retrieved from the Message object.
	 *
	 * The return value is the number of recipients who were accepted for
	 * delivery.
	 *
	 * @param array $failedRecipients An array of failures by-reference
	 *
	 * @return int The number of successful recipients. Can be 0 which indicates failure
	 */
	public function send(&$failedRecipients = null) {
		return $this->account->createMailer()->send($this, $failedRecipients);
	}

}
