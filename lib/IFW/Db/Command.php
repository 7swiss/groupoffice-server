<?php
namespace IFW\Db;

use Exception;
use IFW;
use PDOException;
use function GO;

class Command {
	
	public function __construct(Connection $connection) {
		
		$this->connection = $connection;
		
	}
	
	const TYPE_INSERT = 0;
	const TYPE_UPDATE = 1;
	const TYPE_DELETE = 2;
	const TYPE_SELECT = 4;
	
	private $type;
	private $tableName;
	private $data;
	
	/**
	 *
	 * @var Query
	 */
	private $query;
	
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
	
		return $this;
	}
	
	public function update($tableName, $data, Query $query) {
		$this->type = self::TYPE_UPDATE;
		$this->tableName = $tableName;
		$this->data = $data;
		
		$this->query = $query;
		
		return $this;
	}
	
	public function delete($tableName) {
		$this->type = self::TYPE_DELETE;
		$this->tableName = $tableName;
		
		return $this;
	}
	
	/**
	 * Create select command
	 * 
	 * @param \IFW\Db\Query $query
	 * @return $this
	 */
	public function select(Query $query) {		
		$this->type = self::TYPE_SELECT;
		$this->query = $query;
		
		return $this;
	}
	
	/**
	 * Will replace all :paramName tags with the values. Used for debugging the SQL string.
	 *
	 * @param string $sql
	 * @param string
	 */
	private function replaceBindParameters($sql, $bindParams) {		

		$binds = [];
		foreach ($bindParams as $p) {
			$binds[$p['paramTag']] = var_export($p['value'], true);
		}

		//sort so $binds :param1 does not replace :param11 first.
		krsort($binds);

		foreach ($binds as $tag => $value) {
			$sql = str_replace($tag, $value, $sql);
		}

		return $sql;
	}
	
	public function __toString() {
		$queryBuilder = $this->query->getBuilder();
		$build = $queryBuilder->buildSelect($this->query);
		
		return $this->replaceBindParameters($build['sql'], $build['params']);
		
	}
	
	public function execute() {
		switch($this->type) {
			case self::TYPE_INSERT:				
				return $this->executeInsert();
				
			case self::TYPE_UPDATE:				
				return $this->executeUpdate();
				
			case self::TYPE_SELECT:				
				return $this->executeSelect();
			
			default:				
				throw new Exception("Please call insert, update or delete first");
		}
	}

	/**
	 * 
	 *  @todo move to querybuilder
	 */
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
	 * @todo move to querybuilder
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
	

	private function executeSelect() {
		
		$queryBuilder = $this->query->getBuilder();
		$build = $queryBuilder->buildSelect($this->query);

		try {
			$binds = [];
			$stmt = IFW::app()->getDbConnection()->getPDO()->prepare($build['sql']);

			foreach ($build['params'] as $p) {
				$binds[$p['paramTag']] = $p['value'];
				$stmt->bindValue($p['paramTag'], $p['value'], $p['pdoType']);
			}

			if (!$this->query->getFetchMode()) {
				$stmt->setFetchMode(PDO::FETCH_ASSOC);
			} else {
				call_user_func_array([$stmt, 'setFetchMode'], $this->query->getFetchMode());
			}

			if ($this->query->getDebug()) {
				IFW::app()->getDebugger()->debugSql($build['sql'], $binds, 2);
			}
			
			$stmt->execute();

		} catch (PDOException $e) {
			
			\IFW::app()->debug("FAILED SQL: ".$this->replaceBindParameters($build['sql'], $build['params']));
			
			throw $e;
		}

		return $stmt;
	}
}
