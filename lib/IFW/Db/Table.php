<?php

namespace IFW\Db;

use Exception;
use IFW;

/**
 * Class that fetches database column information for the ActiveRecord.
 * It detects the length, type, default and required attribute etc.
 *
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class Table {
	
	private static $cache = [];
	
	/**
	 * Get a table instance
	 * 
	 * @param string $tableName
	 * @return self
	 */
	public static function getInstance($tableName) {
		
		if(!isset(self::$cache[$tableName])) {
			self::$cache[$tableName] = new Table($tableName);
		}
		
		return self::$cache[$tableName];	
	}
	
	private $tableName;
	private $columns;	
	
	private function __construct($tableName) {
		$this->tableName = $tableName;
		
		$this->createFromDatabase();
	}	
	
	/**
	 * Get's the name of the table
	 * @return string
	 */
	public function getTableName() {
		return $this->tableName;
	}

	private function getCacheKey() {
		return 'dbColumns_' . $this->tableName;
	}

	/**
	 * Clear the columns cache
	 */
	public function clearCache() {
		IFW::app()->getCache()->delete($this->getCacheKey($this->tableName));
	}

	/**
	 * Get all columns of a model
	 *
	 * @return Column[] Array with column name as key
	 */
	private function createFromDatabase() {
		
		if (isset($this->columns)) {
			return $this->columns;
		}
		
		$cacheKey = $this->getCacheKey($this->tableName);

		if (($columns = IFW::app()->getCache()->get($cacheKey))) {
			$this->columns = $columns;
			return $this->columns;
		}	

//		if (!Utils::tableExists($this->tableName)) {
//			throw new Exception("Table '".$this->tableName."' does not exist!");
//		}	
//		
		$this->columns = [];

		$sql = "SHOW FULL COLUMNS FROM `" . $this->tableName . "`;";
		IFW::app()->debug($sql, 'sql');
		
		$stmt = IFW::app()->getDbConnection()->getPDO()->query($sql);
		while ($field = $stmt->fetch()) {
			$this->columns[$field['Field']] = $this->createColumn($field);
		}

		$this->processIndexes($this->tableName);

		IFW::app()->getCache()->set($cacheKey, $this->columns);


		return $this->columns;
	}
	
	/**
	 * A column name may not have the name of a Record property name.
	 * 
	 * @param type $fieldName
	 * @throws Exception
	 */
	private function checkReservedName($fieldName) {
		if(strpos($fieldName, '@') !== false) {
			throw new \Exception("The @ char is reserved for framework usage.");
		}
		
		if(property_exists(IFW\Orm\Record::class, $fieldName)) {
			throw new \Exception("The name '$fieldName' is reserved. Please choose another column name.");
		}
	}

	private function createColumn($field) {
		
		$this->checkReservedName($field['Field']);
		
			
		$c = new Column();
		$c->name = $field['Field'];
		$c->pdoType = PDO::PARAM_STR;
		$c->required = false;
		$c->default = $field['Default'];
		$c->comment = $field['Comment'];
		$c->nullAllowed = strtoupper($field['Null']) == 'YES';
		$c->autoIncrement = strpos($field['Extra'], 'auto_increment') !== false;
		$c->trimInput = false;
		
		preg_match('/(.*)\(([1-9].*)\)/', $field['Type'], $matches);		
		if ($matches) {
			$c->length  = intval($matches[2]);
			$c->dbType = strtolower($matches[1]);
		} else {
			$c->dbType = strtolower($field['Type']);
			$c->length = 0;
		}
		
		if($c->default == 'CURRENT_TIMESTAMP') {
			throw new \Exception("Please don't use CURRENT_TIMESTAMP as default mysql value. It's only supported in MySQL 5.6+");
		}
		
		switch ($c->dbType) {
			case 'int':
			case 'tinyint':
			case 'bigint':
				if ($c->length == 1 && $c->dbType == 'tinyint') {
					$c->pdoType = PDO::PARAM_BOOL;
					$c->default = !isset($field['Default']) ? null : (bool) $c->default;
				} else {
					$c->pdoType = PDO::PARAM_INT;
					$c->default = $c->autoIncrement || !isset($field['Default']) ? null : intval($c->default);
				}

				break;

			case 'float':
			case 'double':
			case 'decimal':
				$c->pdoType = PDO::PARAM_STR;
				$c->length = 0;
				$c->default = $c->default == null ? null : floatval($c->default);
				break;
			
			case 'datetime':
				if($c->default == 'CURRENT_TIMESTAMP') {
					$c->default = date(Column::DATETIME_FORMAT);
				}
				break;
			case 'date':
				if($c->default == 'CURRENT_TIMESTAMP') {
					$c->default = date(Column::DATE_FORMAT);
				}				
				break;
			case 'binary':
				$c->pdoType = PDO::PARAM_LOB;
				break;
			default:				
				$c->trimInput = true;
				break;			
		}

		$c->required = is_null($c->default) && $field['Null'] == 'NO' && strpos($field['Extra'], 'auto_increment') === false;

		if ($field['Field'] == 'createdAt' || $field['Field'] == 'modifiedAt' || $field['Field'] == 'createdBy' || $field['Field'] == 'modifiedBy') {
			//don't validate because they will be set by the Record
			$c->required = false;
		}

		return $c;
	}

	private function processIndexes($tableName) {
		$query = "SHOW INDEXES FROM `" . $tableName . "`";

		$unique = [];

		//group keys;
		// ['keyName' => ['col1', 'col2']];

		$stmt = IFW::app()->getDbConnection()->getPDO()->query($query);
		while ($index = $stmt->fetch()) {

			if ($index['Key_name'] === 'PRIMARY') {

				$this->columns[$index['Column_name']]->primary = true;

				//don't validate uniqueness on primary key
				continue;
			}

			if ($index['Non_unique'] === "0") {
				if (!isset($unique[$index['Key_name']])) {
					$unique[$index['Key_name']] = [];
				}

				$unique[$index['Key_name']][] = $index['Column_name'];
			}
		}

		foreach ($unique as $cols) {
			foreach ($cols as $colName) {
				$this->columns[$colName]->unique = $cols;
			}
		}
	}

	
	
	/**
	 * Get all column names
	 * 
	 * @param string[]
	 */
	public function getColumnNames() {
		return array_keys($this->columns);
	}
	
	/**
	 * Get a column
	 * 
	 * @param string $name
	 * @return Column
	 */
	public function getColumn($name) {
		if(!isset($this->columns[$name])) {
			return null;
		}
		
		return $this->columns[$name];
	}
	
	/**
	 * Get the columns of the table
	 * 
	 * @return Column[]
	 */
	public function getColumns() {
		return $this->columns;
	}

}
