<?php

namespace GO\Core\Email\Model;

use Exception;
use GO\Core\Smtp\Model\Account;
use IFW;
use Swift_Mailer;
use Swift_Mime_Message;
use Swift_SmtpTransport;
use Swift_Transport;
use function GO;

class Mailer extends Swift_Mailer {
	
	/**
	 * When set all e-mail will be sent to this address
	 * 
	 * @var string
	 */
	private $debugEmail;
	
	public function __construct(Account $account) {
		
		$this->applyConfig();
		
		parent::__construct($this->createTransport($account));
	}
	
	/**
	 * Applies config options to this object
	 */
	private function applyConfig(){
		$className = static::class;		
		if(isset(IFW::app()->getConfig()->classConfig[$className])){			
			foreach(IFW::app()->getConfig()->classConfig[$className] as $key => $value){
				$this->$key = $value;
			}
		}		
	}
	
	/**
	 * Create a swift transport with these account settings
	 * 
	 * @return Swift_Transport
	 */
	private function createTransport(Account $account){
		$transport = Swift_SmtpTransport::newInstance($account->hostname, $account->port);
		
		if(isset($account->encryption)){
			$transport->setEncryption($account->encryption);
		}
		
		if(isset($account->username)){
			$transport->setUsername($account->username);
			$transport->setPassword($account->decryptPassword());
		}

		return $transport;		
	}
	
	public static function newInstance(Swift_Transport $transport) {
		throw new Exception("Please construct a new object");
	}
	
	public function send(Swift_Mime_Message $message, &$failedRecipients = null) {
		
		if(isset($this->debugEmail)) {
			$message->setTo($this->debugEmail);
			$message->setBcc([]);
			$message->setCc([]);
			
			GO()->debug("E-mail debugging is enabled in the Group-Office config.php file. All emails are send to: ".$this->debugEmail);
		}
		
		return parent::send($message, $failedRecipients);
	}
}
