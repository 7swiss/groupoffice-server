<?php

namespace IFW\Data;

use Closure;
use Exception;
use IFW\Data\Object;
use IteratorAggregate;
use Traversable;

/**
 * Data store object
 *
 * Create a store response with this class.
 *
 * <p>Example</p>
 * ```````````````````````````````````````````````````````````````````````````
 * public function actionStore($orderColumn='username', $orderDirection='ASC', $limit=10, $offset=0, $searchQuery=""){

  $users = User::find((new Query())
  ->orderBy([$orderColumn => $orderDirection])
  ->limit($limit)
  ->offset($offset)
  ->search($searchQuery, array('t.username','t.email'))
  );

  $store = new Store($users);


  if(isset(IFW::app()->request()->post['returnProperties'])){
  $store->setReturnAttributes(IFW::app()->request()->post['returnProperties']);
  }

  $store->format('specialValue', function(User $model){
  return $model->username." is special";
  });

  echo $this->view->render('store', $store);
  }
 * ```````````````````````````````````````````````````````````````````````````
 */
class Store extends Object implements IteratorAggregate, ArrayableInterface, \Countable  {

	/**
	 * The traversable object in the store.
	 * 
	 * In most cases this a {@see Finder} object that contains {@see Record} models.
	 * @var Traversable
	 */
	public $traversable;
	
	private $formatters = [];
	
	/**
	 *
	 * @var string 
	 */
	protected $returnProperties;
	
	
	/**
	 * Set the attributes to return from the records. It also adjust the select part
	 * in the SQL query and automatically joins relations.
	 *
	 * {@see Model::toArray()}
	 * 
	 * @param string $returnProperties comma separated string can be provided or array.
	 * @return Store
	 */
	public function setReturnProperties($returnProperties = null) {

		$this->returnProperties = $returnProperties;	
	}
	
	
//	private $recordCount;

//	/**
//	 * The default Query limit if none given
//	 *
//	 * @see Query::limit()
//	 * @var int
//	 */
//	public $defaultLimit = 10;

	/**
	 * Constructor of the store
	 *
	 * @param Finder|array $traversable
	 * @throws Exception
	 */
	public function __construct($traversable = null) {
		parent::__construct();

		$this->traversable = $traversable;
		
//		if ($this->traversable instanceof Finder) {
//			
//			//calculate total number of
//			$this->traversable->getQuery()->calcFoundRows();
//		}

//		if($finder->getQuery()->limit > $this->maxLimit){
//			throw new Exception("Limit may not be greater than ".$this->maxLimit);
//		}
//		if (!isset($traversable->getQuery()->limit)) {
//			$traversable->getQuery()->limit($this->defaultLimit);
//		}
	}

	/**
	 * Format a record attribute.
	 * 
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * $multiplier = 10;
	 * 
	 * $store = new Store();
	 * $store->format("multipliedAttribute", function($record) use ($multiplier) {
	 *	return $record->someAttribute * $multiplier;
	 * });
	 * 
	 * ```````````````````````````````````````````````````````````````````````````
	 *
	 * @param string $storeField Name of the field in all store records
	 * @param Closure $function The function is called with the {@see Model} as argument
	 */
	public function format($storeField, Closure $function) {
		$this->formatters[$storeField] = $function;
	}	

	private function formatRecord($record, $model) {
		foreach ($this->formatters as $attributeName => $function) {
			$record[$attributeName] = $function($model);
		}

		return $record;
	}

	
	/**
	 * Convert collection to API array
	 * 
	 * {@see \IFW\Data\Model::toArray()}
	 * 
	 * @param string $properties
	 * @return array
	 */
	
	public function toArray($properties = null) {
				
		if(!isset($properties)) {
			$properties = $this->returnProperties;
		} 
		
		$records = [];
		
		foreach ($this->getIterator() as $model) {
			if(is_array($model)){
				$record = $model;
//				$model = null;
			} else
			{
				$record = $model->toArray($properties);
			}
			$records[] = $this->formatRecord($record, $model);
		}

		return $records;
	}

	/**
	 * 
	 * @return \Traversable
	 */
	public function getIterator() {
		return $this->traversable;
	}

	/**
	 * Get the next record of the store iterator
	 * 
	 * @return mixed
	 */
	public function next() {
		$this->getIterator()->next();
		return $this->getIterator()->current();
	}

	public function count() {
		return iterator_count($this->traversable);
	}



}
