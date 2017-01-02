<?php
namespace IFW\Db;

use IFW\Data\Object;
use IFW;
use PDOStatement;

/**
 * The database connection object. It uses PDO to connect to the database.
 * 
 * The app instance of this connection is available by calling:
 * 
 * ````````````````````````````````````````````````````````````````````````````
 * IFW::app()->getDbConnection();
 * ````````````````````````````````````````````````````````````````````````````
 * 
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class Connection extends Object{
	
	/**
	 *
	 * @var PDO 
	 */
	private $pdo;
	
	/**
	 * Database name
	 * @var string 
	 */
	public $database;
	
	/**
	 * MySQL user
	 * 
	 * @var string 
	 */
	public $user;
	
	/**
	 * MySQL user password
	 * @var string 
	 */
	public $pass;
	
	/**
	 * Port
	 * 
	 * @var int 
	 */
	public $port;
	
	/**
	 * MySQL Hostname
	 * 
	 * @var string 
	 */
	public $host;
	
	
	/**
	 * Connection options
	 * {@link http://php.net/manual/en/pdo.construct.php}
	 * 
	 * @var array 
	 */
	public $options=[];
	
	/**
	 * Gets the global database connection object.
	 * 
	 * {@link http://php.net/manual/en/pdo.construct.php}
	 *
	 * @return PDO Database connection object
	 */
	public function getPDO(){
		if(!isset($this->pdo)){
			$this->setPDO();
		}
		return $this->pdo;
	}
	
	/**
	 * Close the database connection. Beware that all active PDO statements must be set to null too
	 * in the current scope.
	 * 
	 * Wierd things happen when using fsockopen. This test case leaves the conneciton open. When removing the fputs call it seems to work.
	 * 
	 * 			
			
			$settings = \GO\Sync\Model\Settings::model()->findForUser(IFW::app()->user());
			$account = \GO\Email\Model\Account::model()->findByPk($settings->account_id);
			
			
			$handle = stream_socket_client("tcp://localhost:143");
			$login = 'A1 LOGIN "admin@intermesh.dev" "admin"'."\r\n";
			fputs($handle, $login);
			fclose($handle);
			$handle=null;			
			
			echo "Test\n";
			
			IFW::app()->unsetDbConnection();
			sleep(10);
	 */
	public function disconnect(){
		$this->pdo=null;
	}

	/**
	 * Set's a new PDO object base on the current connection settings
	 */
	private function setPDO(){				
		$this->pdo = null;				
		$dsn = "mysql:host=".$this->host.";dbname=".$this->database.";port=".$this->port;
		$this->pdo = new PDO($dsn, $this->user, $this->pass, $this->options);
	}	
	
	/**
	 * Execute an SQL string
	 * 
	 * Should be properly escaped!
	 * {@link http://php.net/manual/en/pdo.query.php}
	 * 
	 * @param string $sql
	 * @return PDOStatement
	 */
	public function query($sql){
//		IFW::app()->debugger()->debugSql($sql);
		return $this->getPdo()->query($sql);
	}
	
	/**
	 * UNLOCK TABLES explicitly releases any table locks held by the current session
	 */
	public function unlockTables(){
		return $this->pdo->query("UNLOCK TABLES");
	}
	
//	private $transactionSavePointLevel = 0;
	
	public function beginTransaction() {
//		IFW::app()->debug("Begin DB transation");
		
//		if($this->transactionSavePointLevel == 0) {
			$ret = $this->pdo->beginTransaction();
			
//		}else
//		{
//			$ret = $this->query("SAVEPOINT LEVEL".$this->transactionSavePointLevel);			
//		}		
//		
//		$this->transactionSavePointLevel++;		
		return $ret;
	}	
	
	
	
	
	/**
	 * Rollback the DB transaction
	 * 
	 * Supports nested transactions using savepoints too.
	 * 
	 */
	public function rollBack() {
//		IFW::app()->debug("Rollback DB transation");
		
//		$this->transactionSavePointLevel--;			
//					
//		if($this->transactionSavePointLevel == 0) {
			return $this->pdo->rollBack();
//		}else
//		{
//			return $this->query("ROLLBACK TO SAVEPOINT LEVEL".$this->transactionSavePointLevel);						
//		}
	}
	
	public function commit() {
//		IFW::app()->debug("Commit DB transation");
//		$this->transactionSavePointLevel--;
			
			
//		if($this->transactionSavePointLevel == 0) {
			return $this->pdo->commit();
//		}else
//		{
//			return $this->query("RELEASE SAVEPOINT LEVEL".$this->transactionSavePointLevel);			
//		}
	}
	
	
	public function inTransaction() {
		return $this->pdo->inTransaction();
	}
	
	
	/**
	 * Lock the table of this model and also for the 't' alias.
	 * 
	 * The locks array should be indexed by model name and the value is an array with two optional values.
	 * THe first is a boolean that enables a write lock and the second is a table alias.
	 * 
	 * @param array $locks eg. [GO\Core\Modules\Users\Model\User::tableName() => [true, 't']]
	 *
	 * @return boolean
	 */
			
	public function lock($locks){
		
		$sql = "LOCK TABLES ";
		
		foreach($locks as $tableName => $lockInfo) {
			$sql .= $tableName." ";
			
			if(isset($lockInfo[1])) {
				$sql .= ' AS '.$lockInfo[1].' ';
			}			
			$sql .= empty($lockInfo[0]) ? 'READ' : 'WRITE';
			
			$sql .= ', ';
		}
		
		$sql = rtrim($sql, ', ');		
		
		IFW::app()->debug($sql);
		return IFW::app()->getDbConnection()->query($sql);
	}
}
