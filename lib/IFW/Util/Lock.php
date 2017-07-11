<?php
namespace IFW\Util;

class Lock {
	
	public static function create($name) {
		$lock = new self($name);
		$lock->lock();
	}
	
	private function __construct($name) {
		$this->name = $name;
	}
	
	private $name;
	
	/**
	 * The file pinter for the lock method
	 * 
	 * @var resource 
	 */
	private $lockFp;
	
	/**
	 * The lock file name.
	 * Stored to cleanup after the script ends
	 */
	private $lockFile;
	
	/**
	 * Lock the controller action
	 * 
	 * Call this to make sure it can only be executed by one user at the same time.
	 * Useful for the system upgrade action for example
	 * 
	 * @throws Exception
	 */
	private function lock() {

		$lockFolder = \IFW::app()->getConfig()
						->getDataFolder()
						->getFolder('locks');
		
		$name = \IFW\Fs\File::stripInvalidChars($this->name);

		$this->lockFile = $lockFolder->getFile($name . '.lock');
		
		//needs to be put in a private variable otherwise the lock is released outside the function scope
		$this->lockFp = $this->lockFile->open('w+');
		
		if (!flock($this->lockFp, LOCK_EX|LOCK_NB, $wouldblock)) {
			
			//unset it because otherwise __destruct will destroy the lock
			unset($this->lockFile, $this->lockFp);
			
			if ($wouldblock) {
				// another process holds the lock
				throw new \Exception("The controller action is already running by another user");
			} else {
				throw new \Exception("Could not lock controller action '" . $this->name . "'");
			}
		} 
	}
	
	public function __destruct() {
		
		//cleanup lock file if lock() was used
		if(isset($this->lockFile)) {
			fclose($this->lockFp);
			unlink($this->lockFile);			
		}
	}
}
