<?php
namespace IFW\Orm;

use Exception;
use IFW\Orm\Query;
use IFW\Orm\Record;
use PDOStatement;


/**
 * Find operations return this collection object
 * 
 * It holds {@see Record} models.
 *
 * This is generally not used directly. {@see Record::find()}
 *
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class Store extends \IFW\Data\Store {

	
	/**
	 * When findByPk is used the results are cached in this variable
	 * 
	 * @var Record[] 
	 */
	private static $cache;

	/**
	 *
	 * @var Query 
	 */
	private $query;
	
	/**
	 * Don't call this directly. Just do a foreach($finder as $model){}
	 *
	 * @param Query $query
	 * @return PDOStatement
	 * @throws Exception
	 */
	public function getIterator() {
		$iterator = new StoreIterator($this->query->createCommand()->execute(), $this);
		return $iterator;
	}
	
	public function __toString() {
		return $this->query->createCommand()->toString();
	}
		
	/**
	 * Name of the AbstractRecord derived class this finder is for.
	 *
	 * @param string
	 */
	public function getRecordClassName() {
		return $this->query->recordClassName;
	}

	/**
	 * Counts the records in the result.
	 * 
	 * @return int
	 */
	public function getRowCount() {
		return $this->getIterator()->getInnerIterator()->rowCount();
	}

	/**
	 *
	 */
	public function __construct(Query $query) {

		parent::__construct();

		$this->query = $query;
	}

	/**
	 * Get the query object
	 * 
	 * @return Query
	 */
	public function getQuery() {
		return $this->query;
	}


	/**
	 * Return one {@see Record}. It also set's the limit on.
	 * 
	 * If you repeat the same query twice this will return the result from a static
	 * cache variable.
	 *
	 * @return Record|FALSE
	 */
	public function single() {
		$this->query->limit(1);

		// Cause segfault in /var/www/groupoffice-server/GO/Modules/Instructiefilm/Elearning/Model/Course.php
		//Expirimental caching if query is findByPk		
		if (!isset($this->query->fetchMode) && ($primaryKeyValues = $this->isFindByPk())) {
			
//			$this->query->withDeleted();

			$keyStr = implode('-', $primaryKeyValues);

			$hash = $this->query->getRecordClassName() . '-' . $keyStr;
			if (!isset(self::$cache[$hash])) {
				$model = $this->getIterator()->fetch();

				if (!$model) {
					return $model;
				}

//				IFW::app()->debug("Caching find by primary key for ".$this->recordClassName.'::('.$keyStr.')');

				self::$cache[$hash] = $model;
			}
//			else {
//				IFW::app()->debug("Return from findByPk cache for for ".$this->recordClassName.'::('.$keyStr.')');
//			}

			return self::$cache[$hash];
		}
		
		$model = $this->getIterator()->fetch();
		return $model;
	}
	
	

	/**
	 * Check if the query object is a find by primary key action
	 * 
	 * @return false|array the primary key values that can be used for a cache hash
	 */
	private function isFindByPk() {

		//IFW::app()->debug($this->_query->where);
		$w = $this->query->getWhere();
		$count = count($w);

		if ($count != 1) {
			return false;
		}

//		//soft delete trait support here...
//		if($count == 2) {
////			$last = array_pop($this->_query->where[1]);
//			$deletedWhere = $this->_query->where[1][1];
//			
//			if(!is_array($deletedWhere) || $deletedWhere[0] != 'AND' || $deletedWhere[1] != '!=') {
//				
////			IFW::app()->debug('1');
//				return false;
//			}		
//			
//			if(empty($deletedWhere[2]['deleted'])){
////							IFW::app()->debug('2');
//				return false;
//			}			
//		}elseif($count != 1) {
//			return false;
//		}
//		//end soft delete support
//		
		//looks like: ['AND', '=', ['id'=>'1','name'=>'merijn']
		$where = $w[0][1];

		if (!is_array($where) || $where[0] != 'AND' || $where[1] != '=') {
			return false;
		}

		$whereKeyValues = array_pop($where);
		$whereKeys = array_keys($whereKeyValues);

		$recordClassName = $this->query->getRecordClassName();

		$primaryKeys = $recordClassName::getPrimaryKey();

		if (count($whereKeys) != count($primaryKeys)) {
			return false;
		}

		foreach ($whereKeys as $key) {
			if (!in_array($key, $primaryKeys)) {
				return false;
			}
		}

		return array_values($whereKeyValues);
	}

	/**
	 * Fetch all records from the database server. Not lazy but immediately.
	 * May use a lot of memory.
	 *
	 * @return Record[]
	 */
	public function all() {
		
//		$iterator = $this->getIterator();
		
//		if($iterator instanceOf ArrayIterator) {
//			return iterator_to_array($iterator);
//		}
		
//		if(!method_exists($iterator, 'fetchAll')) {
//			throw new \Exception("Strange iterator! ". var_dump($iterator));
//		}
		
		$all = [];
		foreach($this->getIterator() as $r) {
			$all[] = $r;
		}
		
		return $all;
	}

	
	
	/**
	 * Set the attributes to return from the records. It also adjust the select part
	 * in the SQL query and automatically joins relations.
	 *
	 * {@see Model::toArray()}
	 * 
	 * @param string $returnProperties comma separated string can be provided or array.
	 * @return Store
	 * 
	 * @todo Automatic select() and joins
	 */
	public function setReturnProperties($returnProperties = null) {

		$this->returnProperties = $returnProperties;		
	
//		if (isset($returnProperties)) {			
//			$recordClassName = $this->recordClassName;
//			$colAndRel = $this->getColumnsAndRelations($returnProperties, $recordClassName);
//			//auto set select part if not already set.
//			if(empty($this->getQuery()->select)) {			
//				$this->getQuery()->select($colAndRel['columns']);
//			}
//			
//			foreach($colAndRel['relations'] as $relationName=>$returnProperties) {
//				$this->joinRelation($recordClassName, $relationName, $returnProperties);
//			}			
//		}

		return $this;
	}
	
//	private function getColumnsAndRelations($returnPropertiesStr, $recordClassName) {
//		
//		$returnProperties = new ReturnProperties($returnPropertiesStr, $recordClassName::getDefaultApiProperties());	
//		
//		$relations =[];
//		$dbColumns = [];
//		foreach ($returnProperties as $attributeName => $relationAttributes) {
//			if ($recordClassName::getRelation($attributeName)) {
//				$relations[$attributeName] = $relationAttributes;
//			}elseif($recordClassName::getColumn($attributeName))
//			{
//				$dbColumns[] = $attributeName;
//			}
//		}
//		
//		return ['relations' => $relations, 'columns' => $dbColumns];
//	}
	
//	private function joinRelation($recordClassName, $relationName , $returnPropertiesStr, $relationPrefix = '') {
//		
//		$relation = $recordClassName::getRelation($relationName);
//
//		/* @var $relation Relation */
//
//		if (!$relation) {
//			throw new Exception("Relation '" . $relationName . "' not found in model '$recordClassName'!");
//		}
//		
//		$relatedRecordClassName = $relation->getToRecordName();
//		$colAndRel = $this->getColumnsAndRelations($returnPropertiesStr, $relatedRecordClassName);
//		
//		if (!$relation->hasMany()) {					
//			
//			IFW::app()->debug("Store is joining relation " . $relationPrefix.$relation->getName() . " because it's listed in returnProperties");
//			
//			$this->getQuery()->joinRelation($relationPrefix.$relation->getName(), $colAndRel['columns'], 'LEFT');
//
//			foreach($colAndRel['relations'] as $subRelationName=>$returnProperties) {				
//				$this->joinRelation($recordClassName, $subRelationName, $returnProperties, $relationPrefix.$relationName.'.');						
//			}
//		}		
//	}
	
	/**
	 */
	public function __clone() {
		$this->query = clone $this->query;
	}
}
