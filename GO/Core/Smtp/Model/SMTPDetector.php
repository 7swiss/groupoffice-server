<?php
namespace GO\Core\Smtp\Model;

use IFW\Data\Model;

/**
 * SMTP Auto detection
 * 
 * Connects and communicates with an IMAP server
 * 
 * 
 * @todo Implement auto detection of SMTP settings
 * 
 * Connected to localhost.
  Escape character is '^]'.
  220 mschering-UX31A ESMTP Postfix (Ubuntu)
  ehlo localhost
  250-mschering-UX31A
  250-PIPELINING
  250-SIZE 20480000
  250-VRFY
  250-ETRN
  250-STARTTLS
  250-ENHANCEDSTATUSCODES
  250-8BITMIME
  250 DSN

 *
 * @link https://tools.ietf.org/html/rfc3501
 * 
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class SMTPDetector extends Model {
	
	use \IFW\Validate\ValidationTrait;

	public $hostname;
	public $port;
	public $encryption = null;
	public $username;
	public $authenticated = false;
	public $email = "";
	public $allowInsecure = false;
	private $password;
	private $connection;
	private $ports = [587, 25, 465];

	public function setPassword($password) {
		$this->password = $password;
	}

	private function getDomain() {
		$parts = explode('@', $this->email);

		return isset($parts[1]) ? $parts[1] : '';
	}

	private function getMailbox() {
		$parts = explode('@', $this->email);

		return isset($parts[0]) ? $parts[0] : '';
	}

	private function _possibleServers() {


		$hosts[] = 'smtp.' . $this->getDomain();
		$hosts[] = 'mail.' . $this->getDomain();

		getmxrr($this->getDomain(), $mxhosts);

		if (!empty($mxhosts)) {
			$hosts = array_merge($hosts, $mxhosts);
		}

		return array_unique($hosts);
	}

	private function ehlo() {
		$servername = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost.localdomain';
		return $this->connection->sendCommand("EHLO " . $servername);
	}

	private function findSupportedAuthMethods(array $response) {
		foreach ($response as $str) {
			if (substr($str, 0, 8) == '250-AUTH') {
				return explode(' ', substr($str, 9));
			}
		}

		return false;
	}

	public function detect() {

		$servers = $this->_possibleServers();

		foreach ($servers as $server) {

			foreach ($this->ports as $port) {
				$this->connection = new Connection();
				if (!$response = $this->connection->connect($server, $port, $port === 465, 3)) {
					continue;
				}


				$this->port = $port;
				$this->encryption = $port === 465 ? 'ssl' : '';
				$this->hostname = $server;

				$response = $this->ehlo();

				if (!$response) {
					continue;
				}


				if (in_array('250-STARTTLS', $response) && $this->connection->startTLS()) {
					$newResponse = $this->ehlo();

					if ($newResponse){
						$response = $newResponse;
						$this->encryption = 'tls';
					}
				}
				
				if($this->encryption == '' && !$this->allowInsecure){
					continue;
				}

				if (!$authMethods = $this->findSupportedAuthMethods($response)) {
					continue;
				}

				$this->tryLogin($authMethods, $this->password);				
				$this->connection->disconnect();
				
				return true;
			}
		}

		return false;
	}

	private function checkResponse($response, $code) {

		if (!$response) {

			GO()->debug("Empty response");
			return false;
		}
		$last = array_pop($response);



		$ret = substr($last, 0, 3) == $code;

		if (!$ret) {
			GO()->debug("NOT $code : $last");
		}

		return $ret;
	}

	private function authPLAIN() {

		$message = base64_encode($this->username . chr(0) . $this->username . chr(0) . $this->password);
		if (!$this->checkResponse($this->connection->sendCommand("AUTH PLAIN " . $message), 235)) {
			return false;
		}

		return true;
	}

	private function authLOGIN() {
		if (!$this->checkResponse($this->connection->sendCommand("AUTH LOGIN"), 334)) {
			return false;
		}
		if (!$this->checkResponse($this->connection->sendCommand(base64_encode($this->username)), 334)) {
			return false;
		}
		if (!$this->checkResponse($this->connection->sendCommand(base64_encode($this->password)), 235)) {
			return false;
		}
		return true;
	}

	private function tryLogin($authMethods) {

		foreach ($authMethods as $method) {

			$authMethod = 'auth' . $method;

			if (!method_exists($this, $authMethod)) {
				continue;
			}

			$this->username = $this->getMailbox();

			GO()->debug($authMethod . '(' . $this->username . ')', 'SMTP');


			if ($this->$authMethod()) {

				$this->authenticated = true;
				return true;
			}

			$this->connection->sendCommand('RSET');

			$this->username = $this->getMailbox() . '@' . $this->getDomain();

			GO()->debug($authMethod . '(' . $this->username . ')', 'SMTP');

			if ($this->$authMethod()) {
				$this->authenticated = true;
				return true;
			}
		}


		return false;
	}

}
