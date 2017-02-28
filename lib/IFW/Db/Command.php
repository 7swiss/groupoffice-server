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
	
	public function update($tableName, $data, $query) {
		$this->type = self::TYPE_UPDATE;
		$this->tableName = $tableName;
		$this->data = $data;
		
		$this->query = \IFW\Db\Query::normalize($query);
		
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
			
		return $this->toString();
	}
	
	public function toString() {
		$build = $this->build();
		
		return $this->replaceBindParameters($build['sql'], $build['params']);
		
	}
	
	private function build() {
		switch($this->type) {
			case self::TYPE_INSERT:				
				$queryBuilder = new QueryBuilder($this->tableName);
				return  $queryBuilder->buildInsert($this->data);
				
			case self::TYPE_UPDATE:				
				$queryBuilder = new QueryBuilder($this->tableName);
				return $queryBuilder->buildUpdate($this->data, $this->query);
				
			case self::TYPE_SELECT:			
				$queryBuilder = $this->query->getBuilder();
		
				return $queryBuilder->buildSelect($this->query);
			
			default:				
				throw new Exception("Please call insert, update or delete first");
		}
	}
	
	public function execute() {		
		
		$build = $this->build();
		
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


				if ($this->query->getDebug()) {
					IFW::app()->getDebugger()->debugSql($build['sql'], $binds, 2);
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