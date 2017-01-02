<?php

namespace GO\Modules\GroupOffice\Email\Smtp;

use IFW;

/**
 * Socket Connection
 * 
 *
 * @link https://tools.ietf.org/html/rfc3501
 * 
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class Connection {

	private $_handle;
		
	public $connectError;
	public $connectErrorNo;


	/**
	 * Connects to the IMAP server
	 * 
	 * @return boolean
	 */
	public function connect($server, $port, $ssl = false, $timeout = 10) {

//		if (!isset($this->handle)) {
			
			
			$streamContext = stream_context_create(['ssl' => [
					"verify_peer"=>false,
					"verify_peer_name"=>false
			]]);
			
			$remote = $ssl ? 'ssl://' : '';			
			$remote .=  $server.":".$port;
			
			GO()->debug("Connection to ".$remote, 'imap');
			
			try {
				$this->_handle = stream_socket_client($remote, $this->connectErrorNo, $this->connectError, $timeout, STREAM_CLIENT_CONNECT, $streamContext);
			}catch (\ErrorException $e) {
				\GO()->debug($e->getMessage());
			}
//		}

		if (!is_resource($this->_handle)) {	
			
			$this->_handle = null;
			
			GO()->debug("Connection to ".$remote." failed ".$this->connectError, 'imap');
			
			return false;
		}else
		{
			return $this->getResponse();
		}
	}
	
	/**
	 * Enable TLS encryption
	 * 
	 * @return boolean
	 */
	public function startTLS() {
		$response = $this->sendCommand("STARTTLS");		

		if(!stream_socket_enable_crypto($this->_handle, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
			return false;
		}
					
		$this->starttls = true;
		
		return true;
	}
	
	
	
	/**
	 * Disconneect from the IMAP server
	 * 
	 * @return boolean
	 */
	public function disconnect() {
		if (is_resource($this->_handle)) {			
			
			$this->sendCommand("QUIT");
			
			$response = $this->getResponse();
			
			fclose($this->_handle);
			$this->_handle = null;	
			
			return $response;
		}else {
			return false;
		}
	}	
	
	public function write($str){	
		if (!fputs($this->_handle, $str)) {
			throw new \Exception("Lost connection to " . $this->server);
		}
		
		return true;
	}
	
	public function sendCommand($command) {
		
		GO()->debug('> '.$command, 'SMTP');
		
		if(!$this->write($command."\r\n")){
			return false;
		}
		
		return $this->getResponse();
	}
	
	
	/**
	 * Reads a single line from the IMAP server
	 * 
	 * @param int $length
	 * @param string
	 */
	public function readLine($length = 8192){
		
		if(feof($this->_handle)) {
			return false;
		}
		
		$line = fgets($this->_handle, $length);
		
		if($line == '') {
			$metas = stream_get_meta_data($this->_handle);
			if ($metas['timed_out']) {
				throw new Exception("Connection timed out");
			}
		}
		
		return $line;
	}
	
	public function getResponse() {
		$data = [];
		
		while($line = $this->readLine()) {
			
			$line = trim($line);
			
			GO()->debug('< '.$line, 'SMTP');
			
			$data[] = $line;
			
			//if the 3rd char is a space we're done reading.
			//http://tools.ietf.org/html/rfc821#page-34
			if ((isset($line[3]) and $line[3] == ' ')) {
				break;
			}
		}
		
		return $data;
	}
}