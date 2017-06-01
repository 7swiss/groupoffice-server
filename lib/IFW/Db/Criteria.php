<?php

namespace IFW\Db;

use IFW\Data\Object;
use Exception;

/**
 * Create "where" part of the query for {@see \IFW\Orm\Record}
 * 
 * <p>Example with finding users with a checkbox if the given group is enabled:</p>
 * ```````````````````````````````````````````````````````````````````````````
 * 
 * $query = (new Query())
 * 					->join(
 * UserGroup::class,
 * 'userGroup',
 * (new Criteria())
 * ->where('t.id=userGroup.userId')
 * ->andWhere(["userGroup.groupId" => $groupId])
 * ,
 * 'LEFT')
 * ->select('t.*, !ISNULL(userGroup.groupId) AS checked')
 * ->groupBy(['t.id']);
 * 
 * $users = User::find($query);
 * 
 * ```````````````````````````````````````````````````````````````````````````
 * 
 * @property-read array $bindParameters
 * @property-read array $where
 * 
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class Criteria extends Object {
	
	/**
	 * Set to true when string input is not allowed for data coming from client
	 * 
	 * @var boolean
	 */
	protected $safeMode = false;
	
	/**
	 * Creates a new Criteria or Query object from different input:
	 * 
	 * * null => new Criteria();
	 * * Array: ['key'= > value] = (new Criteria())->where(['key'= > value]);
	 * * String: "col=:val" = (new Criteria())->where("col=:val"); 
	 * * A Query object is returned as is.
	 * 
	 * @param \IFW\Db\Criteria $criteria
	 * @return \IFW\Db\Criteria
	 * @throws \Exception
	 */
	public static function normalize($criteria = null) {
		if (!isset($criteria)) {
			return new static;
		}
		
		if($criteria instanceof static) {
			return $criteria;
		}
		
		if(is_object($criteria)) {
			throw new \Exception("Invalid query object passed: ".get_class($criteria).". Should be an IFW\Orm\Query object, array or string.");
		}
		
		return (new static)->where($criteria);
	}

	/**
	 * The where conditions
	 * 
	 * Use {@see where()} to add new.
	 * 
	 * @var array 
	 */
	protected $where = [];	
	
	public function getWhere() {
		return $this->where;
	}
	
	/**
	 * Key value array of bind parameters.
	 * 
	 * @var array eg. ['paramTag' => ':someTag', 'value' => 'Some value', 'pdoType' => PDO::PARAM_STR]
	 */
	private $bindParameters = [];
	
	/**
	 * Key value array of bind parameters.
	 * 
	 * @return array eg. ['paramTag' => ':someTag', 'value' => 'Some value', 'pdoType' => PDO::PARAM_STR]
	 */
	public function getBindParameters() {
		return $this->bindParameters;
	}
	
	

	/**
	 * 
	 * Set where parameters. 
	 * 
	 * If relations are included they are joined automatically.
	 * 
	 * <p>Examples:</p>
	 * 
	 * ```````````````````````````````````````````````````````````````````````````
	 * (new Query())
	 * ->where(['id'=>'1','name'=>'merijn'])  // (id=1 AND name=merijn)
	 * 
	 * ->where(['AND', '=', ['id'=>'1','name'=>'merijn'])  // (id=1 AND name=merijn)
	 * ->andWhere(['OR', '=',['id'=>'1','name'=>'merijn'])  // AND (id=1 OR name=merijn)
	 * 
	 * (id=1 AND name=merijn) AND ((id=1) AND (name LIKE 'merijn'))
	 * ->andWhere('username = :username)->bind(':username', $username)
	 * ->orWhere(['OR', 'LIKE', ['id'=>'2', 'name' => 'piet']) // OR (id like 2 OR name like 'piet')
	 * ->andWhere(['id' => [1, 2, 3]]) //IN condition with array
	 * ->andWhere(['!=',['id' => [1, 2, 3]]]) //NOT IN condition with array
	 * ->andWhere(Criteria $c)
	 * 	 
	 * 
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * Subquery examples:
	 * 
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * $mainquery = new Query();
	 * 
	 * $subquery = ThreadMessage::find((new Query())
	 *								->select('threadId')
	 *								->tableAlias('m')
	 *								->where('m.threadId=t.id')
	 *								->andWhere(['type'=>  ThreadMessage::TYPE_INCOMING])								
	 *								);
	 *				
	 * $mainquery->andWhere(['IN',['id'=>$subquery]]);
	 * 
	 * $threads = Thread::find($mainquery);
	 * 
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * IN subquery:
	 * 
	 * ```````````````````````````````````````````````````````````````````````````
	 * $mainquery = new Query();
	 * $subquery = ThreadMessage::find((new Query())
	 *								->tableAlias('m')
	 *								->where('m.threadId=t.id')
	 *								->andWhere(['type'=>  ThreadMessage::TYPE_INCOMING])								
	 *								);
	 *				
	 * $mainquery->andWhere(['EXISTS', $subquery]);
	 * 
	 * $threads = Thread::find($mainquery);
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * @param string|array|Criteria $condition
	 * @param string $operator =, !=, LIKE, NOT LIKE
	 * 
	 * @return static
	 */
	public function where($condition, $operator = "AND") {		
				
		$this->where[] = [strtoupper($operator), $this->normalizeCondition($condition)];

		return $this;
	}
	
	
	

	protected function normalizeCondition($condition) {
		
		if($this->safeMode && is_string($condition)) {
			throw new Exception("RAW string input not allowed in queries");
		}
		
		
		if(!is_array($condition)) {
			return $condition;
		}
		
		if(!isset($condition[0])){
			//Hash values given directly.eg. [id=>1].			
			return ['AND', '=', $condition];
		}
		
		//array should have 3 elements but the first two may be omitted
		//eg. ['AND', '=', ['id'=>'1','name'=>'merijn']
		switch(count($condition)) {
			case 1:
				array_unshift($condition, '=');		
			case 2:
				array_unshift($condition, 'AND');			
		}
		
		$condition[0] = strtoupper($condition[0]);
		$condition[1] = strtoupper($condition[1]);
		
		$this->validateType($condition[0]);		
		$this->validateComparator($condition[1]);		
			
		return $condition;
	}
	
	private function validateType($type) {
		if(!in_array($type, ['AND', 'OR'])) {
			throw new \Exception("Invalid type: " . $type);
		}
	}
	
	private function validateComparator($comparator) {
		if (!preg_match("/[=!><a-z]/i", $comparator)) {
			throw new \Exception("Invalid comparator: " . $comparator);
		}
	}

	/**
	 * Concatenate where condition with AND
	 * 
	 * {@see where()}
	 * 
	 * @param String|array|Criteria $condition
	 * @return static
	 */
	public function andWhere($condition) {
		return $this->where($condition, 'AND');
	}
	
	/**
	 * Concetonate where condition with OR
	 * 
	 * {@see where()}
	 * 
	 * @param String|array|Criteria $condition
	 * @return static
	 */
	public function orWhere($condition) {
		return $this->where($condition, 'OR');
	}

	/**
	 * Add a parameter to bind to the SQL query
	 * 
	 * ```````````````````````````````````````````````````````````````````````````
	 * $query->where("userId = :userId")
	 *   ->bind(':userId', $userId, \PDO::PARAM_INT);
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * OR as array:
	 * 
	 * ```````````````````````````````````````````````````````````````````````````
	 * $contact = Contact::find(
	 *   (new Query())
	 *     ->where("name = :name1 OR name = :name2")
	 *     ->bind([':name1' => 'Pete', ':name2' => 'John'])
	 * );
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * @param string|array $tag eg. ":userId" or [':userId' => 1]
	 * @param mixed $value
	 * @param int $pdoType {@see \PDO} Autodetected based on the type of $value if omitted.
	 * @return static
	 */
	public function bind($tag, $value = null, $pdoType = null) {
		
		if(is_array($tag)) {
			foreach($tag as $key => $value) {
				$this->bind($key, $value);
			}			
			return $this;
		}		
		
		if (!isset($pdoType)) {			
			$pdoType = Utils::getPdoParamType($value);			
		}
		
		$this->bindParameters[] = ['paramTag' => $tag, 'value' => $value, 'pdoType' => $pdoType];
		
		return $this;
	}
	
	
	/**
	 * Allows clients to configure the Query object.
	 * 
	 * {@see Query::setFromClient()}
	 * 
	 * @param array $data
	 * @throws \Exception
	 * @return static
	 */
	public function setFromClient($data) {	
		
		$allowed = $this->getAllowedClientMethods();		
		
		$this->safeMode = true;
		
		foreach($data as $params) {
			$method = array_shift($params);
			if(!in_array($method, $allowed)){
				throw new \Exception(self::class . "::" . $method . " method is not allowed!");
			}
			call_user_func_array([$this, $method], $params);
		}
		
		$this->safeMode = false;
		
		return $this;
	}
	
	
	/**
	 * Returns methods that can be set by the client
	 * 
	 * {@see setFromClient()}
	 * 
	 * @return string[]
	 */
	protected function getAllowedClientMethods() {
		return [
				'andWhere',
				'where',
				'orWhere',				
				'whereGroup'
				];
	}
	
	/**
	 * Group conditions
	 * 
	 * Used with the q parameter by clients.
	 * 
	 * {@see Query::setFromClient()}
	 * 
	 * @param mixed $json
	 * @param string $operator
	 * 
	 * @retrun self
	 */
	public function whereGroup($json, $operator = 'AND') {
		$c = new Criteria();
		$c->setFromClient($json);
		
		return $this->where($c, $operator);		
	}
	
	
}