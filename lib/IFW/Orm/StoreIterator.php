<?php
namespace IFW\Orm;

use IteratorIterator;

/**
 * Wrapper for the store iterator. Most likely a PDO statement or array iterator
 * This wrapper set's the parent relations when it's used with a relational query.
 * 
 * See {@see RelationStore::setParentRelation()} for more info
 */
class StoreIterator extends IteratorIterator {
	
	/**
	 *
	 * @var RelationStore
	 */
	private $store;
	
	public function __construct(\Traversable $iterator, \IFW\Orm\Store $store) {
		parent::__construct($iterator);
		
		$this->store = $store;
	}

	public function current() {
		$record =parent::current();
	
		//set's the parent
		if($this->store instanceof RelationStore && $record instanceof Record) {
			$relation = $record::findParentRelation($this->store->getRelation());
			//check if it hasn't been fetched or set already to prevent loops
			if($relation && !$record->relationIsFetched($relation->getName())) {				
				$record->{$relation->getName()} = $this->store->getRecord();				
			}
		}
		
		return $record;
	}
	
	private $rewind = false;
	
	public function rewind() {
		$this->rewind = true;
		return parent::rewind();
	}
	
	public function valid() {
		$valid = parent::valid();
		if(!$valid) {
			$this->rewind = false;
		}
		
		return $valid;
	}

	public function fetch() {
		if(!$this->rewind) {
			$this->rewind();
		}else
		{
			$this->next();
		}
		$current = $this->current();
		
		return isset($current) ? $current : false;
	}
	
}
