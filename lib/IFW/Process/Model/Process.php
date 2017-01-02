<?php
namespace IFW\Process\Model;

use IFW\Data\Model;

class Process extends Model {
	
//	public $status;
	
	/**
	 * The progress of the process in percentage
	 * 
	 * @var float percentage
	 */
	private $progress = null;
	
	/**
	 *
	 * @var string 
	 */
	public $maxDuration = 20;
	
	public $maxMemory = null;
	
	private $startTime;
	
	
	private $lockFp;
	
	public function __construct() {
		parent::__construct();
		
		$this->startTime = time();
		
		$this->maxDuration = (int) ini_get('max_execution_time');
		
		if($this->maxDuration > 0) {
			$this->maxDuration -= 5;
		}
		
		//Default to 50%
		$this->maxMemory = GO()->getEnvironment()->getMemoryLimit() / 2;				
	}
	
	/**
	 * Lock the process. When locked it's made sure that the action is only ran 
	 * by one user at a time.
	 * 
	 * @throws Exception
	 */
	public function lock($lockName) {

		$lockFolder = \IFW::app()->getConfig()
						->getTempFolder(true, false)
						->getFolder('locks');

		$lockFile = $lockFolder->getFile($lockName . '.lock');

		//needs to be put in a private variable otherwise the lock is released outside the function scope
		$this->lockFp = $lockFile->open('w+');
		
		if (!flock($this->lockFp, LOCK_EX|LOCK_NB, $wouldblock)) {
			if ($wouldblock) {
				// another process holds the lock
				\IFW::app()->debug("Locked");
				return false;
			} else {
				throw new \Exception("Could not lock process '" . $lockName . "'");
			}
		} else {

			\IFW::app()->debug("Not Locked");

			
			
			return true;
		}
	}

	/**
	 * Set to percentage complete or null if unknown
	 * 
	 * @param int|null $percentage
	 */
	public function setProgress($percentage) {
		$this->progress = $percentage;
		
		if($this->maxMemory > 0 && (memory_get_usage() > $this->maxMemory)) {
			
			
			$this->render();
		}
		
		if($this->maxDuration > 0 && ($this->startTime < time() - $this->maxDuration)) {
			$this->render();
		}
	}
	
	public function getProgress() {
		return $this->progress;
	}
	
	public function getUrl() {
		return GO()->getRouter()->buildUrl(GO()->getRouter()->getRoute());
	}
	
	protected function render() {
		$view = new \GO\Core\View\Web\Api();
		GO()->getResponse()->setStatus(202, 'In progress, please repeat your request.');
		GO()->getResponse()->send($view->render(['data' => $this->toArray()]));
		
		
		exit();
	}
}
