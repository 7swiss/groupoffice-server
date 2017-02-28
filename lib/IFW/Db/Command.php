<?php
namespace IFW\Db;

class Command extends Criteria {
	
	public function __construct(Connection $connection) {
		
		$this->connection = $connection;
		
		parent::__construct();
	}
	
	const TYPE_INSERT = 0;
	const TYPE_UPDATE = 1;
	const TYPE_DELETE = 2;
	
	private $type;
	private $tableName;
	private $data;
	
	/**
	 *
	 * @var Columns 
	 */
	private $columns;
	
	/**
	 *
	 * @var Connection
	 */
	private $connection;
	
	public function insert($tableName, $data) {
		$this->type = self::TYPE_INSERT;
		$this->tableName = $tableName;
		$this->data = $data;
		
		$this->columns = new Columns($tableName);
		
		return $this;
	}
	
	public function update($tableName, $data) {
		$this->type = self::TYPE_UPDATE;
		$this->tableName = $tableName;
		$this->data = $data;
		
		$this->columns = new Columns($tableName);
		
		return $this;
	}
	
	public function delete($tableName) {
		$this->type = self::TYPE_DELETE;
		$this->tableName = $tableName;
		
		$this->columns = new Columns($tableName);
		
		return $this;
	}
	
	public function getSql() {
		
	}
	
	public function execute() {
		switch($this->type) {
			case self::TYPE_INSERT:				
				return $this->executeInsert();
				
			case self::TYPE_UPDATE:				
				return $this->executeUpdate();
			
			default:				
				throw new \Exception("Please call insert, update or delete first");
		}
	}
	
	private $paramCount = 0;
	private $paramPrefix = ':ifw';
	
	/**
	 * Private function to get the current parameter prefix.
	 *
	 * @param string The next available parameter prefix.
	 */
	private function getParamTag() {
		self::$paramCount++;
		return self::$paramPrefix . self::$paramCount;
	}
	
	private function executeUpdate() {
		
		$binds = [];
		foreach($this->data as $colName => $value) {
			$binds[$colName] = self::getParamTag();
			$updates[] = '`'.$colName.'` = '.$binds[$colName];
		}
		
		$sql = "UPDATE `{$this->tableName}` SET ". implode(',', $updates);
		
		$stmt = $this->connection->getPDO()->prepare($sql);
		
		foreach($this->data as $colName => $value) {			
			$column = $this->columns[$colName];		
			$stmt->bindValue($binds[$colName], $column->recordToDb($this->$colName), $column->pdoType);
		}
		
		return $this->execute();
	}
	
	/**
	 * 
	 * @return bool
	 */
	private function executeInsert() {
		
		$binds = [];
		foreach($this->data as $colName => $value) {
			$binds[$colName] = self::getParamTag();
		}
		
		$sql = "INSERT INTO `{$this->tableName}` (`" . implode('`,` ', array_keys($this->data)) . "`) VALUES " .
						"(" . implode(', ', array_values($binds)) . ")";
		
		$stmt = $this->connection->getPDO()->prepare($sql);
		
		
		foreach($this->data as $colName => $value) {			
			$column = $this->columns[$colName];		
			$stmt->bindValue($binds[$colName], $column->recordToDb($this->$colName), $column->pdoType);
		}
		
		return $stmt->execute();
		
	}
}
