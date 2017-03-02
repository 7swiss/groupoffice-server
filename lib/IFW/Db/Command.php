<?php
namespace IFW\Db;

use Exception;
use IFW;
use PDOException;
use PDOStatement;

class Command {
	
	const TYPE_INSERT = 0;
	const TYPE_UPDATE = 1;
	const TYPE_DELETE = 2;
	const TYPE_SELECT = 4;
	
	/**
	 *
	 * @var int 
	 */
	private $type;
	
	/**
	 * Table to perform command on
	 * 
	 * @var string 
	 */
	private $tableName;
	
	/**
	 * Command data
	 * 
	 * @var mixed 
	 */
	private $data;
	
	/**
	 * Query for commands
	 * 
	 * @var Query
	 */
	private $query;

	
	/**
	 * Create an insert command
	 * 
	 * {@see Connection::createCommand()}
	 * 
	 * @param string $tableName
	 * @param array|Query $data Key value array or select query
	 * @return $this
	 */
	public function insert($tableName, $data) {
		$this->type = self::TYPE_INSERT;
		$this->tableName = $tableName;
		$this->data = $data;
	
		return $this;
	}
	
	/**
	 * Create an update command
	 * 
	 * {@see Connection::createCommand()}
	 * 
	 * @param string $tableName
	 * @param Query $query
	 * @return $this
	 */
	public function update($tableName, $data, $query = null) {
		$this->type = self::TYPE_UPDATE;
		$this->tableName = $tableName;
		$this->data = $data;
		
		$this->query = \IFW\Db\Query::normalize($query);
		
		return $this;
	}
	
	/**
	 * Create a delete command
	 * 
	 * {@see Connection::createCommand()}
	 * 
	 * @param string $tableName
	 * @param Query $query
	 * @return $this
	 */
	public function delete($tableName, $query = null) {
		$this->type = self::TYPE_DELETE;
		$this->tableName = $tableName;
		
		$this->query = \IFW\Db\Query::normalize($query);
		
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
			if(is_string($p['value']) && !mb_check_encoding($p['value'], 'utf8')) {
				$queryValue = "[NON UTF8 VALUE]";
			}else
			{
				$queryValue = var_export($p['value'], true);
			}
			$binds[$p['paramTag']] = $queryValue;
		}

		//sort so $binds :param1 does not replace :param11 first.
		krsort($binds);

		foreach ($binds as $tag => $value) {	
			$sql = str_replace($tag, $value, $sql);
		}

		return $sql;
	}
	
	public function __toString() {
			
		return $this->toString();
	}
	
	/**
	 * Build SQL string and replace bind parameters for debugging purposes
	 * 
	 * @return string
	 */
	public function toString() {
		$build = $this->build();
		
		return $this->replaceBindParameters($build['sql'], $build['params']);		
	}
	
	/**
	 * Builds the SQL and bind parameters
	 * 
	 * @return array ['sql' => 'SELECT...', 'params' => [':ifw1' => 'value']]
	 * @throws Exception
	 */
	public function build() {
		switch($this->type) {
			case self::TYPE_INSERT:				
				$queryBuilder = new QueryBuilder($this->tableName);
				return  $queryBuilder->buildInsert($this->data);
				
			case self::TYPE_UPDATE:				
				$queryBuilder = new QueryBuilder($this->tableName);
				return $queryBuilder->buildUpdate($this->data, $this->query);
				
			case self::TYPE_DELETE:				
				$queryBuilder = new QueryBuilder($this->tableName);
				return $queryBuilder->buildDelete($this->query);
				
			case self::TYPE_SELECT:			
				$queryBuilder = $this->query->getBuilder();
		
				return $queryBuilder->buildSelect($this->query);
			
			default:				
				throw new Exception("Please call insert, update or delete first");
		}
	}
	
	/**
	 * Execute the command
	 * 
	 * @return PDOStatement
	 * @throws PDOException
	 */
	public function execute() {		
		
		$build = $this->build();
		
		IFW::app()->debug($this->replaceBindParameters($build['sql'], $build['params']), \IFW\Debugger::TYPE_SQL, 2);
		
		try {
			
			$binds = [];
			$stmt = IFW::app()->getDbConnection()->getPDO()->prepare($build['sql']);

			foreach ($build['params'] as $p) {
				$binds[$p['paramTag']] = $p['value'];
				$stmt->bindValue($p['paramTag'], $p['value'], $p['pdoType']);
			}

			if($this->type == self::TYPE_SELECT) {
				if (!$this->query->getFetchMode()) {
					$stmt->setFetchMode(PDO::FETCH_ASSOC);
				} else {
					call_user_func_array([$stmt, 'setFetchMode'], $this->query->getFetchMode());
				}
			}
			
			$stmt->execute();

		} catch (PDOException $e) {
			
			\IFW::app()->debug("FAILED SQL: ".$this->replaceBindParameters($build['sql'], $build['params']));
			
			throw $e;
		}
		
		return $stmt;
	}	
}