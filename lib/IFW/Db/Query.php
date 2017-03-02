<?php
namespace IFW\Db;

use IFW\Db\Criteria;
use ReflectionClass;

/**
 * Used to build parameters for ActiveRecord::find() database queries
 *
 * @see Record::find()
 * 
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class Query extends Criteria {
	
	private $tableAlias = 't';

	private $distinct;
	
	private $select;
	
	private $orderBy;	
	
	private $groupBy;
	
	private $having;	
	
	private $limit;
	
	private $offset = 0;	
	
	protected $joins = [];

	private $fetchMode;
	
	private $withDeleted;
	
	protected $queryBuilder;
	
	private $skipReadPermission = false;
	
	
	private $relation;
	private $relationFromRecord;

	
	public function getTableAlias() {
		return $this->tableAlias;
	}
	
	public function getWithDeleted() {
		return $this->withDeleted;
	}
	
	public function getHaving() {
		return $this->having;
	}

	
	public function getDistinct() {
		return $this->distinct;
	}
	public function getSelect() {
		return $this->select;
	}
	public function getOrderBy() {
		return $this->orderBy;
	}
	public function getGroupBy() {
		return $this->groupBy;
	}
	public function getLimit() {
		return $this->limit;
	}
	public function getOffset() {
		return $this->offset;
	}
	public function getJoins() {
		return $this->joins;
	}
	public function getFetchMode() {
		return $this->fetchMode;
	}
	
	public function getSkipReadPermission(){
		return $this->skipReadPermission;
	}
	
	public function getIsRelational(){
		return isset($this->relation);
	}
	
	/**
	 * When this query is made by a relation this holds the relation object.
	 * 
	 * @return \IFW\Orm\Relation
	 */
	public function getRelation() {
		return $this->relation;
	}
	
	/**
	 * When this query is created through a relation this holds the from record of the relation.
	 * 
	 * @example
	 * 
	 * ``````````````````````````````
	 * $contact->emailAddresses
	 * ``````````````````````````````
	 * The store returned will hold a query and this function returns $contact.
	 * 
	 * @return \IFW\Orm\Record
	 */
	public function getRelationFromRecord() {
		return $this->relationFromRecord;
	}

		
	/**
	 * for internal use only
	 * 
	 * Set when doing relational queries an extra property "_isRelational" is set
	 * so the record can do an extra permission check when fetching relations.
	 * 
	 * @access private
	 * @return static
	 */
	public function setRelation(\IFW\Orm\Relation $relation, \IFW\Orm\Record $record) {
		$this->relation = $relation;
		$this->relationFromRecord = $record;
		
		return $this;
	}
	



//	public $calcFoundRows = false;
//	
//	public function calcFoundRows($calcFoundRows = true) {
//		$this->calcFoundRows = $calcFoundRows;
//		
//		return $this;
//	}
	
	private $tableName;
	
	public function getFrom() {
		return $this->tableName;
	}
	
	/**
	 * 
	 * @param string $tableName
	 * @return $this
	 */
	public function from($tableName) {
		$this->tableName = $tableName;
		
		return $this;
	}
	
	
	/**
	 * Set the PDO fetch mode
	 * 
	 * By default the model is returned. But in some cases it might be better to
	 * fetch it as an array.
	 * 
	 * The arg1 and arg2 param depends on the $mode argument. See the PHP documentation:
	 * 
	 * {@see http://php.net/manual/en/pdostatement.setfetchmode.php}
	 * 
	 * @param int $mode
	 * @param mixed $arg1 
	 * @param mixed $arg2
	 * @return static
	 */
	public function fetchMode($mode, $arg1 = null, $arg2 = null){
		$this->fetchMode = [$mode];
		
		if(isset($arg1)){
			$this->fetchMode[] = $arg1;
			
			if(isset($arg2)){
				$this->fetchMode[] = $arg2;
			}
		}
		
		return $this;
	}
	
	
	/**
	 * Select a sinlge column or count(*) for example.
	 * 
	 * Shortcut for:
	 * $query->fetchMode(\PDO::FETCH_COLUMN,0)->select($select)
	 * 
	 * @param string $select
	 * @return static
	 */
	public function fetchSingleValue($select) {
		return $this->fetchMode(\PDO::FETCH_COLUMN,0)->select($select);
	}

	/**
	 * Set the Distinct select option
	 *
	 * @param boolean $useDistinct
	 * @return static
	 */
	public function distinct($useDistinct = true) {
		$this->distinct = $useDistinct;
		return $this;
	}

	/**
	 * Merge this with another Query object.
	 *
	 * @param Query $query
	 * @return static
	 */
	public function mergeWith(Query $query) {

		$reflection = new ReflectionClass(Query::class);
		
		$props = $reflection->getProperties();
		
		foreach ($props as $prop) {
			$key = $prop->getName();
			$value = $query->$key;
//			echo $key.': '.var_export($value, true)."\n";

			if (!isset($value)) {
				continue;
			}

			if (is_array($value)) {
				$this->$key = isset($this->$key) ? array_merge($this->$key, $value) : $value;
			} else {
				$this->$key = $value;
			}
		}

		return $this;
	}

	/**
	 * Set the selected fields for the select query.
	 *
	 * Remember the model table is aliased with 't'. Using this may result in incomplete models.
	 *
	 * @param string|array $select
	 * @return static
	 */
	public function select($select = '*') {
		$this->select = $select;
		return $this;
	}
	
	
//	private $selectAttributes;	
//	
//	public function selectAttributes(array $attributes) {
//		$this->selectAttributes = $attributes;
//		return $this;
//	}
//	
//	protected function getSelectAttributes() {
//		return $this->selectAttributes;
//	}
	
	
	
	
	
	public function tableAlias($alias) {
		
		$this->tableAlias = $alias;
		return $this;
	}
	
	


	/**
	 * Execute a simple search query
	 *
	 * @param string $query
	 * @param array $fields eg. array("t.username","t.email")
	 * @param boolean $exactPhrase If false, then the query will be wrapped with wildcards and spaces will be replaced. eg. John Smits will become %John%Smith%.
	 * @return static
	 */
	public function search($query, $fields, $exactPhrase = false) {

		if (!empty($query)) {
			if (!$exactPhrase) {
				$query = "%" . preg_replace("/[\s]+/", "%", $query) . "%";
			}
			
			$hashValues = [];
			
			foreach($fields as $field){
				$hashValues[$field] = $query;
			}
			
			$this->andWhere(['OR','LIKE',$hashValues]);
		}

		return $this;
	}

	/**
	 * Set sort order
	 *
	 * @param array $by or ['field1'=>'ASC','field2'=>'DESC', new IFW\Db\Expression('ISNULL(column) ASC')] for multiple values	 
	 * 
	 * @return static
	 */
	public function orderBy($by) {	
		$this->orderBy = $by;	

		return $this;
	}	

	/**
	 * Adds a group by clause.
	 *
	 * @param array $columns eg. array('t.id');
	 * @return static
	 */
	public function groupBy(array $columns) {		
		$this->groupBy = $columns;
		return $this;
	}

	/**
	 * Adds a having clause. 
	 *
	 * @param Criteria|array|string $condition {@see Criteria::normalize()}
	 * @return static
	 */
	public function having($condition, $operator = 'AND') {
		$this->having[] = [$operator, $this->normalizeCondition($condition)];
		return $this;
	}
	
	/**
	 * Adds a having clause group with AND.
	 * 
	 * {@see having()}
	 * 
	 * @param Criteria|array|string $condition {@see Criteria::normalize()}
	 * @return static
	 */
	public function andHaving($condition){
		return $this->having($condition);
	}
	
	/**
	 * Adds a having clause group with OR.
	 * 
	 * {@see having()}
	 * 
	 * @param Criteria|array|string $condition {@see Criteria::normalize()}
	 * @return static
	 */
	public function orHaving($condition){
		return $this->having($condition, 'OR');
	}
	
//	function test() {
//		
//	$on = 't.id=contact.contact_id';
//	
//	$on  = 'contact';
//		
//	$models = Company::query()
//					->select(['id', 'name'])
//					->join(Company::query()->selset('x')->order('x'), $alias, $on, 'INNER')
//					->joinRelation('contact', (new Query())->select(['id','name','SUM(`amount`) AS amount']));
//					
//	
//	
//	
//	$models = Company::query()->select(['id', 'name'])
//					->with('company', (new Query())->selset('x')->where(['id = :id', 'id'=>1]));
//	
//	
//	$models = Company::query()->select(['id', 'name'])
//					->join((new JoinQuery('contact'))->selset('x')->order('x'));
//	
//	$models = Company::query()->select(['id', 'name'])
//					->join(
//									(new Join('contact'))
//									->on()
//									->type()
//									->qeary()->selset('x')
//									->order('x')
//									->where(
//													(new where)
//													->or()
//													)
//									);
//	
//	$models = Company::query()->select(['id', 'name'])
//					->join(Company::joinQuery()->on('t.id=contact.contact_id')->selset('x')->order('x'));
//
//		
////	$models = Company::find((new Query())->select('id', 'name'))
////					->join(Company::class, (new query())->selset('x')->order('x'));
////	
////	
////	$models = Company::find(new Query()->select('id', 'name')
////					->join($criteria,new Query()->select('x')->order('x'));
//	}


	/**
	 * Make a join where you can specify the join criteria yourself.
	 *
	 * <p>Example:</p>
	 * ```````````````````````````````````````````````````````````````````````````
	 * $query = (new Query())
	 *  ->orderBy([$orderColumn => $orderDirection])
	 *  ->limit($limit)
	 *  ->offset($offset)
	 *  ->searchQuery($searchQuery, ['t.name']);
   * 
	 *  if(isset($userId)){
	 *		//select the checked column for this user.
	 *		$query->select('t.*, !ISNULL(userGroup.userId) AS checked')
	 *			->groupBy(['t.id']);
	 * 
	 *		$query->join(
	 *			UserGroup::class, 
	 *			'userGroup', 
	 *			't.id=userGroup.id AND userId=:userId' , 
	 *			'LEFT'
	 *			)
	 *		->bind([':userId' => $userId']);
	 *  }
	 * 
	 *  $groups = Group::find($query);
	 * ```````````````````````````````````````````````````````````````````````````
	 *
	 * @param string|\IFW\Orm\Store $tableName The record class name or sub query to join
	 * @param string $joinTableAlias Leave empty for none.
	 * @param Criteria|array|string $on The criteria used in the ON clause {@see Criteria::normalize()}
	 * @param string $type The join type. INNER, LEFT or RIGHT
	 * @return static
	 */
	public function join($tableName, $joinTableAlias, $on, $type = 'INNER') {

		$this->joins[] = ['manual' , [
				'src' => $tableName,
				'on' => Criteria::normalize($on),
				'joinTableAlias' => $joinTableAlias,
				'type' => $type
		]];

		return $this;
	}

	/**
	 * Skip this number of records
	 *
	 * @param int $offset
	 * @return static
	 */
	public function offset($offset = 0) {
		$this->offset = (int) $offset;
		return $this;
	}

	/**
	 * Limit the number of models returned
	 *
	 * @param int $limit
	 * @return static
	 */
	public function limit($limit = 0) {
		$this->limit = (int) $limit;
		return $this;
	}
	
	
	/**
	 * Include soft deleted items
	 * 
	 * @param boolean $withDeleted
	 * @return static
	 */
	public function withDeleted($withDeleted = true) {
		$this->withDeleted = $withDeleted;
		
		return $this;
	}
	
	/**
	 * 
	 * @return Command
	 */
	public function createCommand() {
		return GO()->getDbConnection()->createCommand()->select($this);
	}
	
	/**
	 * for internal use only
	 * 
	 * Used by {@see IFW\Auth\Permissions\Model} to set that models returned have
	 * already been checked for read access.
	 * 
	 * @return static
	 */
	public function skipReadPermission() {
		
		if(!isset($this->fetchMode)) {
			$this->skipReadPermission = true;
		}
		
		return $this;
	}
	
	
	
	/**
	 * Allows clients to configure the Query object.
	 * 
	 * Clients must pass a JSON encoded array as a "q" GET parameter.
	 * 
	 * These methods are allowed:
	 * 
	 * 'andWhere',
	 *				'where',
	 *				'orWhere',
	 *				'joinRelation',
	 *				'groupBy',
	 *				'having', 
	 *				'andHaving',
	 *				'orHaving',
	 *				'distinct',
	 *				'orderBy',
	 *				'limit',
	 *				'offset'
	 * 
	 * 
	 * @example Javascript
	 * 
	 * ```````````````````````````````````````````````````````````````````````````
	 * [
	 *	['method',arg1,arg2, etc.]
	 * ]
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * 
	 * 
	 * @example Javascript
	 * 
	 * ``````````````````````````````````````````````````````````````````````````
	 * q: [				
	 *		['joinRelation', 'languages.language', false, 'INNER'],
	 *		['andWhere', {
	 *				'languages.language.key':'nl',
	 *				'activities.activityId':  null
	 *			}],
	 *		['orWhere', ['!=', {
	 *				'languages.language.key':'en',
	 *				'activities.activityId':  null
	 *			}]
	 *		]
	 *	]
	 * ```````````````````````````````````````````````````````````````````````````		
	 * 
	 * @example PHP in controller
	 * ```````````````````````````````````````````````````````````````````````````
	 * public function actionStore($orderColumn = 't.name', $orderDirection = 'ASC', $limit = 10, $offset = 0, $searchQuery = "", $returnProperties = "", $q = null) {
	 *
	 *		$query = (new Query)						
	 *				->debug()
	 *				->orderBy([$orderColumn => $orderDirection])
	 *				->limit($limit)
	 *				->offset($offset);
	 *
	 *		if (!empty($searchQuery)) {
	 *			$query->search($searchQuery, ['name']);
	 *		}
	 *
	 *		if (!empty($q)) {
	 *			$query->setFromClient($q);
	 *		}
	 *		
	 *		$this->getFilterCollection()->apply($query);		
	 *
	 *		$activitys = Activity::find($query);
	 *		$activitys->setReturnProperties($returnProperties);
	 *
	 *		$this->renderStore($activitys);
	 *	}		
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * @param string $json
	 * @throws \Exception
	 * @return static
	 */
	public function setFromClient($json) {
		
		if(empty($json)) {
			return $this;
		}
		
		$allowed = [
				'andWhere',
				'where',
				'orWhere',
				'joinRelation',
				'groupBy',
//				'having', 
//				'andHaving',
//				'orHaving',
//				'distinct',
				'orderBy',
				'limit',
				'offset',
				'search'
				];
		
		$data = json_decode($json, true);
		
		$this->safeMode = true;
		
		foreach($data as $params) {
			$method = array_shift($params);
			if(!in_array($method, $allowed)){
				throw new \Exception("Query::$method method is not allowed!");
			}
			call_user_func_array([$this, $method], $params);
		}
		
		$this->safeMode = false;
		
		return $this;
	}
	
	
	
	/**
	 * Get the where arguments as criteria object.
	 * 
	 * Useful if you want to add all existing conditions to a new group:
	 * 
	 * ```````````````````````````````````````````````````````````````````````````
	 * //Group all existing where criteria. For example WHERE id=1 OR id=2 will become WHERE (id=1 OR id=2)
	 * $criteria = $this->query->getWhereAsCriteria();						
	 * $this->query->resetCriteria();			
	 * $this->query->andWhere(['!=', ['deleted' => true]]);
	 * 
	 * if(isset($criteria)) {
	 * 	$this->query->andWhere($criteria);
	 * }
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * @return \IFW\Db\Criteria
	 */
	public function getWhereAsCriteria() {	
		
//		GO()->getDebugger()->debugCalledFrom(10);
		
		if(empty($this->where)) {
			return null;
		}
		
		$c = new Criteria();
		foreach($this->where as $where) {
			$c->where($where[1], $where[0]);
		}		
		return $c;		
	}
	
	/**
	 * Reset the "WHERE" criteria
	 */
	public function resetCriteria() {
		$this->where = [];
	}

	/**
	 * Get the query builder object that can build the select SQL statement
	 * 
	 * @param string $recordClassName
	 * @return QueryBuilder
	 */
	public function getBuilder() {		
		return new QueryBuilder($this->tableName);		
	}
	
}