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
	private $relationStore;
	
	public function setRelationStore(RelationStore $store) {
		$this->relationStore = $store;
	}
	
	public function current() {
		$record =parent::current();
		
		//set's the parent
		if(isset($this->relationStore) && $record) {
			$relation = $record::findParentRelation($this->relationStore->getRelation());
			if($relation) {
				$record->{$relation->getName()} = $this->relationStore->getRecord();
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
		return $this->current();
	}
	
}
