<?php

namespace GO\Core\Smtp\Model;

use ErrorException;
use GO\Core\Accounts\Model\AccountAdaptorRecord;
use Swift_Mailer;
use Swift_SmtpTransport;
use Swift_Transport;

/**
 * The SmtpAccount model
 *
 * @copyright (c) 2015, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class Account extends AccountAdaptorRecord {

	/**
	 * 
	 * @var int
	 */
	public $id;

	/**
	 * 
	 * @var string
	 */
	public $hostname;

	/**
	 * 
	 * @var int
	 */
	public $port = 25;

	/**
	 * 
	 * @var string
	 */
	public $encryption;

	/**
	 * 
	 * @var string
	 */
	public $username;

	/**
	 * 
	 * @var string
	 */
	protected $password;

	/**
	 * The from name of the sent messages
	 * @var string
	 */
	public $fromName;

	/**
	 * The from email address of the sent messages
	 * @var string
	 */
	public $fromEmail;

	/**
	 * Creator
	 * 
	 * @var int 
	 */
	public $createdBy;

	private function testConnection() {
		$streamContext = stream_context_create(['ssl' => [
						"verify_peer" => false,
						"verify_peer_name" => false
		]]);

		$errorno = null;
		$errorstr = null;
		$remote = $this->encryption == 'ssl' ? 'ssl://' : '';
		$remote .= $this->hostname . ":" . $this->port;

		try {
			$handle = stream_socket_client($remote, $errorno, $errorstr, 10, STREAM_CLIENT_CONNECT, $streamContext);
		} catch (ErrorException $e) {
			GO()->debug($e->getMessage());
		}

		if (!is_resource($handle)) {
			$this->setValidationError('hostname', \IFW\Validate\ErrorCode::CONNECTION_ERROR, 'Failed to open socket #' . $errorno . '. ' . $errorstr);
			return false;
		}

		stream_socket_shutdown($handle, STREAM_SHUT_RDWR);

		return true;
	}

	/**
	 * Get the mailer using this account settings
	 * 
	 * @return Swift_Mailer
	 */
	public function createMailer() {
		return new \GO\Core\Email\Model\Mailer($this);
	}

	public function setPassword($password) {
		$crypt = new \IFW\Util\Crypt();
		$this->password = $crypt->encrypt($password);
	}

	public function decryptPassword() {
		$crypt = new \IFW\Util\Crypt();
		if (!empty($this->password) && !$crypt->isEncrypted($this->password)) {
			$this->password = $crypt->encrypt($this->password);
			$this->update();

			return $this->decryptPassword();
		} else {
			return $crypt->decrypt($this->password);
		}
	}
	
	public static function getCapabilities() {
		return [\GO\Core\Email\Model\Message::class];
	}


//
//	/**
//	 * 
//	 * @param int $userId
//	 * @return self[]
//	 */
//	public static function findForUser($userId) {
//		
//		$q = (new \IFW\Orm\Query())
//						->select('id,fromEmail')
//						->where(['createdBy' => $userId]);
//		
//		return self::find($q)->all();
//	}
}
