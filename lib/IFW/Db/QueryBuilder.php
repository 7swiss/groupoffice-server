<?php

namespace IFW\Db;

use Exception;
use IFW;
use IFW\Db\Column;
use IFW\Db\Criteria;
use IFW\Db\PDO;
use PDOException;
use PDOStatement;
use function GO;

/**
 * QueryBuilder
 *
 * Builds or executes an SQL string with a {@see Query} object anmd {@see AbstractRecord}
 *
 * @copyright (c) 2015, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class QueryBuilder {
	
//	const TYPE_INSERT = 0;
//	const TYPE_UPDATE = 1;
//	const TYPE_DELETE = 2;
//	const TYPE_SELECT = 3;

	use \IFW\Event\EventEmitterTrait;

	/**
	 * Fires when this query object is converted into an SQL string.
	 *
	 * This is used in {@see \GO\Core\Modules\Users\Model\Permissions} for example.
	 *
	 * Some extra parameters are inserted just before building the query. They are
	 * not added immediately because this makes caching find() results harder as
	 * we can't determine easily then if the find was a simple query on primary key.
	 * {@see Finder::single()}
	 */
	const EVENT_BUILD_QUERY = 0;

	/**
	 *
	 * @var string
	 */
	private $sql;

	/**
	 *
	 * @var Query
	 */
	private $query;

	/**
	 * The name of the record model.
	 *
	 * eg. GO\Modules\Contacts\Model\Contact
	 *
	 * @var string
	 */
	protected $tableName;
	protected $defaultRecordForEmptyAlias;
	protected $tableAlias;
//	protected $defaultTableAlias = 't';

	/**
	 * Key value array of parameters to bind to the SQL Statement
	 *
	 * Query::where() parameters will be put in here to bind.
	 *
	 * @var array[]
	 */
	private $buildBindParameters;

	/**
	 * Array of joined relation names
	 *
	 * @var string[]
	 */
	protected $alreadyJoinedRelations;
	private static $paramCount = 0;

	/**
	 * Prefix of the bind parameter tag
	 * @var type
	 */
	private static $paramPrefix = ':ifw';

	/**
	 * Key value array with [tableAlias => modelClassName]
	 *
	 * Used to find the model that belongs to an alias to find column types
	 * @var array
	 */
	protected $aliasMap = [];
	
	/**
	 *
	 * @var Columns 
	 */
	private $columns;

	/**
	 * Constructor
	 *
	 * @param string $tableName The table to operate on
	 */
	public function __construct($tableName) {		
		$this->tableName = $this->defaultRecordForEmptyAlias = $tableName;
		
		$this->columns = new Columns($tableName);
	}

	/**
	 * Used when building sub queries for when aliases of the main query are used
	 * in the subquery.
	 *
	 * @param array $aliasMap
	 */
	public function mergeAliasMap($aliasMap) {
		$this->aliasMap = array_merge($this->aliasMap, $aliasMap);
	}

	/**
	 * Get the query parameters
	 *
	 * @return Query
	 */
	public function getQuery() {
		return $this->query;
	}
	
	


	/**
	 * Get the name of the record this query builder is for.
	 *
	 * @param string
	 */
	public function getTableName() {
		return $this->tableName;
	}

//	public function __toString() {
//		return $this->buildSelect(true);
//	}

	private function softDelete() {

		if ($this->query->getWithDeleted()) {
			return;
		}

		if ($this->columns->getColumn('deleted')) {

			//Group all existing where criteria. For example WHERE id=1 OR id=2 will become WHERE (id=1 OR id=2)
			$criteria = $this->query->getWhereAsCriteria();
			$this->query->resetCriteria();
			$this->query->andWhere(['!=', ['deleted' => true]]);

			if (isset($criteria)) {
				$this->query->andWhere($criteria);
			}
		}
	}
	


	/**
	 * Build the SQL string
	 *
	 * @param boolean $replaceBindParameters Will replace all :paramName tags with the values. Used for debugging the SQL string.
	 * @return string
	 */
	public function buildSelect(Query $query = null, $prefix = '') {

		$this->query = $query;

		if (!isset($this->sql)) {
			$this->fireEvent(self::EVENT_BUILD_QUERY, $this);

			$this->softDelete();

			$this->alreadyJoinedRelations = [];
			$this->buildBindParameters = [];

			$this->tableAlias = $this->query->getTableAlias();
			$this->aliasMap[$this->tableAlias] = new Columns($this->tableName);

			

			
			$joins = "";

			
			foreach ($this->query->getJoins() as $join) {
				$method = 'join' . $join[0];
				$joins .= "\n".$prefix.$this->$method($join[1]);
			}
			
			$select = "\n".$prefix.$this->buildSelectFields();
			
			$select .= "\n".$prefix."FROM `" . $this->tableName . '` `' . $this->tableAlias . "`";
			
			
			$where = "\n".$prefix . $this->buildWhere(null, $prefix);


			$group = "\n".$prefix.$this->buildGroupBy();
			$having = "\n".$prefix.$this->buildHaving();
			$orderBy = "\n".$prefix.$this->buildOrderBy();

			$limit = "";
			if ($this->query->getLimit() > 0) {
				$limit .= "\n".$prefix."LIMIT " . $this->query->getOffset() . ',' . $this->query->getLimit();
			}
			
			$this->sql = trim($prefix . $select . $joins . $where . $group . $having . $orderBy . $limit);
		}
//		if ($replaceBindParameters) {
//			return $this->replaceBindParameters($this->sql);
//		} else {
			return ['sql' => $this->sql, 'params' => $this->getBindParameters()];
//		}
	}

	protected function buildSelectFields() {
		$select = "SELECT ";

		//		if ($this->_query->calcFoundRows && $this->_query->limit > 0) {
		//			$select .= "SQL_CALC_FOUND_ROWS ";
		//		}

		if ($this->query->getDistinct()) {
			$select .= "DISTINCT ";
		}

		$s = $this->query->getSelect();
		if (!empty($s)) {
			if (is_array($s)) {
				foreach ($s as $attr) {
					$select .= $this->quoteTableAndColumnName($attr) . ', ';
				}
				$select = trim($select, ', ');
			} else {
				$select .= $s;
			}
		} else {
			$select .= $this->query->getTableAlias() . '.*';
		}


		return $select . ' ';
	}

//
//	/**
//	 * Finds the PDO type in the Record column definitions
//	 *
//	 * @param string $tableAlias
//	 * @param string  $column
//	 * @param mixed $value
//	 * @return int
//	 * @throws Exception
//	 */
//	private function findPdoType($tableAlias, $column, $value) {
//		if (!isset($tableAlias) || !isset($this->aliasMap[$tableAlias])) {
//			return PDO::PARAM_STR;
//		} else {
//			$columnObject = call_user_func([$this->aliasMap[$tableAlias], 'getColumn'], $column);
//			if (!$columnObject) {
//				throw new Exception("Column " . $column . " not found in model " . $this->aliasMap[$tableAlias]);
//			}
//			return $columnObject->pdoType;
//		}
//	}

	/**
	 *
	 * @param string $tableAlias
	 * @param string $column
	 * @return Column
	 * @throws Exception
	 */
	private function findColumn($tableAlias, $column) {

		if (!isset($this->aliasMap[$tableAlias])) {
			throw new Exception("Alias '" . $tableAlias . "'  not found in the aliasMap: " . var_export($this->aliasMap, true) . ' for ' . $column);
		}
		
		if (!isset($this->aliasMap[$tableAlias][$column])) {
			throw new Exception("Column '" . $column . "' not found in table " . $this->aliasMap[$tableAlias]->getTableName());
		}
		return $this->aliasMap[$tableAlias][$column];
	}

	

	/**
	 * Get the parameters to bind to the SQL statement
	 *
	 * @return array [['tableAlias' => 't', 'column' => 'id', 'pdoType' => PDO::PARAM_INT]]
	 */
	public function getBindParameters() {		
		return array_merge($this->query->getBindParameters(), $this->buildBindParameters);
	}

	private function buildGroupBy() {

		if (empty($this->query->groupBy)) {
			return '';
		}

		$groupBy = "GROUP BY ";

		foreach ($this->query->groupBy as $column) {
			if($column instanceof Expression){
				$groupBy .= $column . ', ';
			}else
			{
				$groupBy .= $this->quoteTableAndColumnName($column) . ', ';
			}
		}

		$groupBy = trim($groupBy, ' ,');

		return $groupBy . "\n";
	}

	/**
	 * Build the where part of the SQL string
	 *
	 *
	 *
	 * @param \IFW\Db\Criteria $query
	 * @param string $prefix Simple string prefix not really functional but only used to add some tab spaces to the SQL string for pretty formatting.
	 * @param string
	 */
	protected function buildWhere(Criteria $query = null, $prefix = "") {

		if (!isset($query)) {
			$query = $this->query;
			$appendWhere = true;
		} else {
			//import new params
			foreach ($query->getBindParameters() as $v) {
//				$this-query->bind($v['paramTag'], $v['value'], $v['pdoType']);
				$this->buildBindParameters[] = $v;
			}
			$appendWhere = false;
		}

		$conditions = $query->getWhere();
		$condition = array_shift($conditions);

		if (!$condition) {
			return '';
		}

		$where = $this->buildCondition($condition[1], $prefix) . "\n";

		foreach ($conditions as $condition) {
			$where .= $prefix . $condition[0] . "\n" . $this->buildCondition($condition[1], $prefix) . "\n";
		}
		
		$where = rtrim($where);

		return $appendWhere ? "WHERE\n" . $where : $where;
	}

	/**
	 * Convert where condition to string
	 *
	 * {@see Criteria::where()}
	 *
	 *
	 * @param string|array|Criteria $condition
	 * @param string $prefix
	 * @return string
	 * @throws Exception
	 */
	private function buildCondition($condition, $prefix = "") {
		$c = $prefix . "(\n";

		if (is_string($condition)) {
			$c .= $prefix . "\t" . $condition . "\n";
		} elseif (is_array($condition)) {
			$c .= $this->arrayConditionToString($condition, $prefix . "\t");
		} elseif (is_a($condition, Criteria::class)) {
			$c .= $this->buildWhere($condition, $prefix . "\t");
		} else {
			throw new Exception("Invalid condition passed\n\n" . var_export($condition, true));
		}

		$c .= "\n".$prefix.")";

		return $c;
	}

	protected $isSubQuery = false;
	
	/**
	 * Builds the where array syntax to a parameterized SQL string
	 *
	 * @param array $condition eg. ['AND', '=', ['id'=>'1','name'=>'merijn']
	 * @param string $prefix Simple string prefix not really functional but only used to add some tab spaces to the SQL string for pretty formatting.
	 * @param string eg. "`t`.`id` = :ifw1 AND `t`.`name` = :ifw2"
	 */
	private function arrayConditionToString(array $condition, $prefix) {

		$values = array_pop($condition);
		$comparator = array_pop($condition);
		$type = array_pop($condition);

		if ($values instanceof \IFW\Orm\Store) {
			//subquery passed ['EXISTS", $subquery]
			return $this->buildSubQuery($comparator, $values, $prefix);
		} else {
			return $this->buildAndOrCondition($type, $comparator, $values, $prefix);
		}
	}

	private function buildSubQuery($comparator, \IFW\Orm\Store $store, $prefix) {
		$builder = $store->getQuery()->getBuilder($store->getRecordClassName());
		$builder->mergeAliasMap($this->aliasMap);
		$builder->isSubQuery = true;
		
		$build = $builder->buildSelect($store->getQuery(), $prefix . "\t");

		$str = $prefix . $comparator . " (\n" . $prefix . "\t" . $build['sql'] ."\n". $prefix . ")";
		
		foreach ($build['params'] as $v) {
//			$this->query->bind($v['paramTag'], $v['value'], $v['pdoType']);
			$this->buildBindParameters[] = $v;
		}
		return $str;
	}

	private function splitTableAndColumn($column) {
		$parts = explode('.', $column);

		$c = count($parts);
		if ($c > 1) {
			$column = array_pop($parts);
			$alias = array_pop($parts);
			return [trim($alias, ' `'), trim($column, ' `')];
		} else {

//			$alias = $usePrimaryTableAsDefault ? $this->query->tableAlias : null;
			$colName = trim($column, ' `');

//			$primaryRecordClass = $this->defaultRecordForEmptyAlias;
			
			$columnObject = $this->aliasMap[$this->tableAlias]->getColumn($colName);

			//use primary table alias (t) if it's a column of the main record class
			if ($columnObject) {
				$alias = $this->tableAlias;
			} else {
				$alias = null;
			}

			return [$alias, $colName];
		}
	}

	/**
	 * Put's quotes around the table name and checks for injections
	 *
	 * @param string $tableName
	 * @param string
	 * @throws Exception
	 */
	protected function quoteTableName($tableName) {
		
		//disallow \ ` and \00  : http://stackoverflow.com/questions/1542627/escaping-field-names-in-pdo-statements
		if (preg_match("/[`\\\\\\000\(\),]/", $tableName)) {
			throw new Exception("Invalid characters found in column name: " . $tableName);
		}

		return '`' . str_replace('`', '``', $tableName) . '`';
	}

	/**
	 * Quotes a column name for use in a query.
	 * If the column name contains prefix, the prefix will also be properly quoted.
	 * If the column name is already quoted or contains '(', '[[' or '{{',
	 * then this method will do nothing.
	 *
	 * @param string $columnName column name
	 * @param string the properly quoted column name
	 */
	protected function quoteColumnName($columnName) {
		return $this->quoteTableName($columnName);
	}

	/**
	 * Splits table and column on the . separator and quotes them both.
	 *
	 * @param string $columnName
	 * @param string
	 */
	protected function quoteTableAndColumnName($columnName) {

		$parts = $this->splitTableAndColumn($columnName);

		if (isset($parts[0])) {
			return $this->quoteTableName($parts[0]) . '.' . $this->quoteColumnName($parts[1]);
		} else {
			return $this->quoteColumnName($parts[1]);
		}
	}
//
//	/**
//	 * Code for automatic join of relations based on the where table aliases.
//	 *
//	 * @todo move to ORM
//	 * @param string $relationParts
//	 */
//	private function joinWhereRelation($relationParts) {
//
//		$relationName = implode('.', $relationParts);
//		$alias = array_pop($relationParts);
//
//		if (!isset($this->aliasMap[$alias]) && $this->query->relationIsJoined($relationName) === false) {
//			$arName = $this->recordClassName;
//			if ($arName::getRelation($relationName)) {
//				IFW::app()->debug("Joining relation in from where() param: ".$relationName);
//				$this->query->joinRelation($relationName, false, 'LEFT');
//			}
//		}
//	}

	/**
	 * Builds "`t`.`id` = :ifw1 AND `t`.`name` = :ifw2"
	 *
	 * @param string $type 'AND' or 'OR'
	 * @param string $comparator '=', '!=' etc.
	 * @param array $hashValues
	 * @param string $prefix Simple string prefix not really functional but only used to add some tab spaces to the SQL string for pretty formatting.
	 * @param string
	 */
	private function buildAndOrCondition($type, $comparator, $hashValues, $prefix) {

		$str = $prefix;

		foreach ($hashValues as $column => $value) {

			if ($str != $prefix) {
				$str .= ' ' . $type . ' ';
			}

			$columnParts = $this->splitTableAndColumn($column);
			$col = $this->quoteTableName($columnParts[0]) . '.' . $this->quoteColumnName($columnParts[1]);

//			$relationParts = explode('.', $column);
			//remove column name
//			array_pop($relationParts);

//			$this->joinWhereRelation($relationParts);

			if (!isset($value)) {
				if ($comparator == '=' || $comparator == 'IS') {

					$str .= $col . " IS NULL";
				} elseif ($comparator == '!=' || $comparator == 'NOT IS') {
					$str .= $col . " IS NOT NULL";
				} else {
					throw new Exception('Null value not possible with comparator ' . $comparator);
				}
			} else if (is_array($value)) {
				$type = $comparator == '!=' ? 'NOT IN' : 'IN';

				if ($comparator != '=' && $comparator != '!=') {
					throw new \Exception("Only = or != comparators are supported when supplying an array for an IN condition");
				}

				$str .= $this->buildInCondition($type, $columnParts, $value, $prefix);
			} else if ($value instanceof \IFW\Orm\Store) {
				//subquery
				$builder = $value->getQuery()->getBuilder($value->getRecordClassName());
				$this->mergeAliasMap($builder->aliasMap);
				$builder->isSubQuery = true;
				$build = $builder->buildSelect($value->getQuery(), $prefix . "\t");
				
				$str .=  $col . ' ' . $comparator . " (\n" .$prefix . $build['sql'] . $prefix . ")\n";
				foreach ($build['params'] as $v) {
//					$this->query->bind($v['paramTag'], $v['value'], $v['pdoType']);
					$this->buildBindParameters[] = $v;
				}
			} else {
				$paramTag = $this->getParamTag();

				$this->addBuildBindParameter($paramTag, $value, $columnParts[0], $columnParts[1]);

				$str .= $col . ' ' . $comparator . ' ' . $paramTag ;
			}
		}

		return $str;
	}

	private function buildInCondition($type, $columnParts, $hashValues, $prefix) {

		if (empty($hashValues)) {
			throw new \Exception("IN condition can not be empty!");
		}

		$str = $this->quoteTableName($columnParts[0]) . '.' . $this->quoteColumnName($columnParts[1]) . ' ' . $type . ' (';

		foreach ($hashValues as $value) {
			$paramTag = $this->getParamTag();
			$this->addBuildBindParameter($paramTag, $value, $columnParts[0], $columnParts[1]);

			$str .= $paramTag . ', ';
		}

		$str = $prefix . rtrim($str, ', ') . ")";

		return $str;
	}

	private function buildOrderBy() {
		$oBy = $this->query->getOrderBy();
		if (empty($oBy)) {
			return '';
		}

		$orderBy = "ORDER BY ";

		foreach ($oBy as $column => $direction) {
			
			if($direction instanceof Expression) {
				$orderBy .= $direction.', ';
			}else
			{			
				$direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
				$orderBy .= $this->quoteTableAndColumnName($column) . ' ' . $direction . ', ';
			}
		}

		return trim($orderBy, ' ,');
	}

	private function buildHaving() {

		$h = $this->query->getHaving();
		if (empty($h)) {
			return '';
		}

		$conditions = $h;
		$condition = array_shift($conditions);

		$having = $this->buildCondition($condition[1]) . "\n";

		foreach ($conditions as $condition) {
			$having .= $condition[0] . ' (' . $this->buildCondition($condition[1]) . ")\n";
		}

		return "HAVING" . $having;
	}

	private function addBuildBindParameter($paramTag, $value, $tableAlias, $column) {
		
//		GO()->debug("Added bind param $paramTag ".$this->recordClassName);
		
		$columnObj = $this->findColumn($tableAlias, $column);

		$this->buildBindParameters[] = [
			 'paramTag' => $paramTag,
			 'value' => $columnObj->recordToDb($columnObj->normalizeInput($value)),
//			 'tableAlias' => $tableAlias,
//			 'column' => $column
				'pdoType' => $columnObj->pdoType
		];
	}

	/**
	 * Private function to get the current parameter prefix.
	 *
	 * @param string The next available parameter prefix.
	 */
	private function getParamTag() {
		self::$paramCount++;
		return self::$paramPrefix . self::$paramCount;
	}

	private function joinManual($config) {
		$join = "";

		if ($config['src'] instanceof \IFW\Orm\Store) {
			$builder = $config['src']->getQuery()->getBuilder($config['src']->getRecordClassName());
			$this->mergeAliasMap($builder->aliasMap);
			$build = $builder->buildSelect($config['src']->getQuery());
			$joinTableName = '(' . $build['sql'] . ')';
			foreach ($build['params'] as $v) {
				$this->query->bind($v['paramTag'], $v['value'], $v['pdoType']);
			}
		} else {
			$this->aliasMap[$config['joinTableAlias']] = new Columns($config['src']);
			$joinTableName = '`' . $config['src'] . '`';
		}

		$join .= $config['type'] . ' JOIN ' . $joinTableName . ' ';

		if (!empty($config['joinTableAlias'])) {
			$join .= '`' . $config['joinTableAlias'] . '` ';
		}

		$join .= 'ON (' . $this->buildWhere($config['on'], "\t") . ")";


		return $join;
	}
}
