<?php

namespace IFW\Orm;

use ArrayAccess;
use ArrayIterator;
use Exception;

/**
 * Relation Store
 * 
 * Is a collection of {@see Record} models for has many relations to iterate 
 * through the records.
 * 
 * It can also be used to set modified records on.
 * 
 * Example:
 * 
 * <code>
 * //Array with hasmany relation of the contact model
 * $contactAttr = [
 *   'firstName' => 'Test', 
 *   'lastName'=>'Has One',
 *   'emailAddresses' => [
 *			['email'=>'test1@intermesh.nl','type'=>'work'],
 *			['email'=>'test2@intermesh.nl','type'=>'work']
 *		]
 * ];
 *	
 *	$contact = new Contact();
 *	$contact->setAttributes($contactAttr);				
 *	$contact->save();
 * 
 *  //Get first e-mail address
 * 	$firstEmail = $contact->emailAddresses->single();
 * 	$firstEmail->type='home';
 * 	
 *	//It's important to understand that this code doesn't add an e-mail address
 *  //but it adds an updated e-mail address that will be saved with the contact.
 * 
 *	//This line would have the same effect: $contact->emailAddresses = [$firstEmail];
 * 
 *	$contact->emailAddresses[] = $firstEmail;
 *	
 *	$contact->save();
 * </code>
 * 
 */
class RelationStore extends Store implements ArrayAccess {

	/**
	 * @var Record[]
	 */
	private $modified;

	/**
	 *
	 * @var Relation
	 */
	private $relation;

	/**
	 *
	 * @var Record 
	 */
	private $model;

	/**
	 * 
	 * @param \IFW\Orm\Relation $relation
	 * @param \IFW\Orm\Record $model
	 * @param \IFW\Orm\Query $query When query is null we should not query the results but just hold modified records
	 */
	public function __construct(Relation $relation, Record $model, Query $query = null) {
		$this->model = $model;
		$this->relation = $relation;
		parent::__construct($relation->getToRecordName(), isset($query) ? $query : new Query() );
		
		if($query == null) {
			$this->modified = [];
		}
	}
	
	/**
	 * Get the relation component of this store
	 * 
	 * @return Relation
	 */
	public function getRelation() {
		return $this->relation;
	}

	/**
	 * Get's the iterator
	 * 
	 * @return ArrayIterator|\PDOStatement
	 */
	public function getIterator() {
		if (isset($this->modified)) {
			return new ArrayIterator($this->modified);
		} else {
			return parent::getIterator();
		}
	}
	
	/**
	 * Fetch single record
	 * 
	 * @return Record | false
	 */
	public function single() {
		if(isset($this->modified)) {
			return isset($this->modified[0]) ? $this->modified[0] : null;
		}else
		{
			
			
			$record = parent::single();							
			if($record) {
				
				/**
				 * put record in modified array so when changes are made to it they will be saved along.
				 * 
				 * eg.
				 * 
				 * $contact = Contact::findByPk(1);
				 * $contact->customFields->foo = 'bar';
				 * $contact->save();
				 */
				
				$this->modified[0] = $record;
			}
			
			return $record;
		}
	}
	
	public function all() {		
		if(!isset($this->modified)) {
			$this->modified = parent::all();
		}		
		return $this->modified;
	}

	public function offsetExists($offset) {
		return isset($this->modified[$offset]);
	}

	public function offsetGet($offset) {
		return isset($this->modified[$offset]) ? $this->modified[$offset] : null;
	}

	public function offsetSet($offset, $value) {
		$value = $this->normalize($value);
		
		if(!isset($value)) {
			\IFW::app()->debug("WARNING: Ignoring null value given in relation ".$this->relation->getFromRecordName().'->'.$this->relation->getName()." you should not set a relation to null");
			return;
		}
		
		if (is_null($offset)) {
			$this->modified[] = $value;
		} else {
			$this->modified[$offset] = $value;
		}
	}
	
	/**
	 * Add a new modified record
	 * 
	 * Normally you'd just push a new record or record attributes to the array:
	 * ```````````````````````````````````````````````````````````````````````````
	 * $model->relation[] = $new;
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * But this function returns the normalized model for further operation.
	 * 
	 * See {@see Relation::setQuery} for example code.
	 * 
	 * @param mixed $value
	 * @return Record
	 */
	public function add($value) {
		$value = $this->normalize($value);		
		$this->modified[] = $value;		
		return $value;
	}
	
	private function applyKeys(Record $newToRecord) {		
		
//		GO()->debug($this->relation->getName());
		
		foreach($this->relation->getKeys() as $fromField => $toField) {					
			if($this->relation->isBelongsTo()) {				
				if(isset($newToRecord->$toField)) {					
//					GO()->debug($this->model->getClassName().'->'.$fromField .'='.$newToRecord->$toField);					
					$this->model->$fromField = $newToRecord->$toField;
				}									
			}else
			{
				if(isset($this->model->$fromField)) {					
//					GO()->debug($newToRecord->getClassName().'->'.$toField.'='.$this->model->$fromField);					
					$newToRecord->$toField = $this->model->$fromField;
				}
			}				
		}
	}
	
	/**
	 * Set all relevant keys for the relationship on modified models.
	 * 
	 * Used in record save to make sure newly set relations are connected
	 */
	public function setNewKeys() {
		if(!isset($this->modified)) {
			return;
		}
		
		foreach($this->modified as $record) {
			$this->applyKeys($record);
		}
	}
	
	/**
	 * Converts an array of properties to a model if it's not a model yet
	 * 
	 * @param type $newToRecord
	 * @return \IFW\Orm\toRecordName
	 * @throws Exception
	 */
	private function normalize($newToRecord) {
		
		if(!isset($newToRecord)) {
			return null;
		}
		
		$toRecordName = $this->relation->getToRecordName();
		$toPks = $toRecordName::getPrimaryKey();		
		
		if (is_a($newToRecord, $toRecordName)) {
			//it's already a record so set the relation keys
			if(!$this->relation->getViaRecordName()) {
				$this->applyKeys($newToRecord);
			}
			return $newToRecord;			
		}
		
		//If it's not a Record then it must be an array with record properties.
		
		if (!is_array($newToRecord)) {
			throw new Exception("Invalid value given to relation '".$this->relation->getName()."'. A '" . get_class($newToRecord).'\' was given which should be a \''.$toRecordName.'\'');
		}

		$propArray = $newToRecord;

		//set the relation keys if possible
		if(!$this->relation->getViaRecordName()) {
			
			foreach($this->relation->getKeys() as $fromField => $toField) {					
				if($this->relation->isBelongsTo()){							
					if(isset($propArray[$toField])) {
						$this->model->$fromField = $propArray[$toField];
					}
				}else
				{
					$propArray[$toField] = $this->model->$fromField;
				}				
			}
		}


		//isNew = false is selected with Query::joinRelation 
		//(set in Record::extractJoinedRelations). We can instantiate an existing record this way.
		if(isset($propArray['isNew'])) {
			$newToRecord = new $toRecordName;
			$newToRecord->setValues($propArray);
			
			return $newToRecord;
		}
		
		//try to find an existing record.
		$pk = $this->buildPk($toPks, $propArray);				

		$newToRecord = $pk ? $toRecordName::find($pk)->single() : false;
		if (!$newToRecord) {
			$newToRecord = new $toRecordName;
		}		

		$newToRecord->setValues($propArray);

		if (!$this->relation->getViaRecordName()) {
			$this->applyKeys($newToRecord);
		}
		
		return $newToRecord;
		
	}
	
	private function buildPk($primaryKey, $attributes) {
		$pk = [];

		foreach ($primaryKey as $col) {
			//negative id handled as empty too for extjs :(				
			if (empty($attributes[$col]) || $attributes[$col] < 0) {
				return false;
			}
			$pk[$col] = $attributes[$col];
		}

		return $pk;
	}

	public function offsetUnset($offset) {
		unset($this->modified[$offset]);
	}

	/**
	 * Check if there are modified records in the store
	 * 
	 * @return boolean
	 */
	public function isModified() {		
		if(!isset($this->modified)) {
			return false;
		}
		
		foreach($this->modified as $record) {
			if($record->isModified()) {
				return true;
			}
			
			//if relation has a via/link table then check if this needs to be created or deleted on save
			if ($this->relation->getViaRecordName()) {				
				$delete = $record->markDeleted;
				if($delete == $this->hasViaRecord($record)) {
					return true;
				}
			}
		}
		
		return false;
	}

	/**
	 * Clears the modified Records
	 */
	public function reset() {
		$this->modified = null;
	}

	/**
	 * Save the modified records on this collection
	 * 
	 * @return boolean
	 */
	public function save() {

		if (empty($this->modified)) {
			return true;
		}
				
		foreach ($this->modified as $record) {
			

			if ($this->relation->getViaRecordName()) {
				
				$delete = $record->markDeleted;
				$record->markDeleted = false;
				if ($record->isModified()) {
					if (!$record->save()) {
						return false;
					}
				}
				if($delete) {
					$this->deleteViaRecord($record);
				}  else {
					$this->createViaRecord($record);
				}
				
			} else {
				//belongs to is saved first and then keys are applied
				$belongsTo = $this->relation->isBelongsTo();				
				if(!$belongsTo) {
					$this->applyKeys($record);
				}
				
				//not sure if isModified check will have side effects.
				if ($record->isModified() && !$record->save()) {
					return false;
				}
				
				if($belongsTo) {
					$this->applyKeys($record);
				}
			}

		}
		
		
		return true;
	}
	
	private function hasViaRecord(Record $relatedRecord) {
		$viaModelName = $this->relation->getViaRecordName();
		
		$primaryKey = $this->buildViaPk($relatedRecord);
		

		return $viaModelName::find($primaryKey)->single() != false;
	}
	
	private function buildViaPk(Record $relatedModel) {
		$primaryKey = [];
		
		//contact.id => contactTag.contactId
		foreach($this->relation->getKeys() as $fromField => $toField) {
			$primaryKey[$toField] = $this->model->$fromField;
		}
		
		//contactTag.tagId => tag.id
		foreach($this->relation->getViaKeys() as $fromField => $toField) {
			$primaryKey[$fromField] = $relatedModel->$toField;
		}
		return $primaryKey;
	}

	/**
	 * 
	 * @param Relation $this->relation
	 * @param Record $relatedRecord
	 * @return IFQ\Db\Record
	 * @throws Exception
	 */
	private function createViaRecord(Record $relatedRecord) {
		$viaRecordName = $this->relation->getViaRecordName();
		
		$primaryKey = $this->buildViaPk($relatedRecord);
		

		$viaRecord = $viaRecordName::find($primaryKey)->single();

		if (!$viaRecord) {
			$viaRecord = new $viaRecordName;
			$viaRecord->setValues($primaryKey);
			if (!$viaRecord->save()) {
				throw new Exception("Could not create viaModel " . $viaRecordName.' validation errors: '.var_export($viaRecord->getValidationErrors(), true));
			}
		}
	}
	
	
	private function deleteViaRecord(Record $relatedModel) {
		$viaRecordName = $this->relation->getViaRecordName();		
		
		$primaryKey = [];
		
		//contact.id => contactTag.contactId
		foreach($this->relation->getKeys() as $fromField => $toField) {
			$primaryKey[$toField] = $this->model->$fromField;
		}
		
		//contactTag.tagId => tag.id
		foreach($this->relation->getViaKeys() as $fromField => $toField) {
			$primaryKey[$fromField] = $relatedModel->$toField;
		}

		$viaRecord = $viaRecordName::find($primaryKey)->single();
		if(!$viaRecord) {
			return true;
		}
		
		if(!$viaRecord->delete()) {
			throw new Exception("Could not delete viaModel " . $viaRecordName);
		}
	}
	
	/**
	 * Check if the relation to the given record already exists in the database.
	 * 
	 * Note: it does not check modified records!
	 * 
	 * @param \IFW\Orm\Record $relatedRecord
	 */
	public function has(Record $relatedRecord) {		
		
		if(($viaRecordName = $this->relation->getViaRecordName())) {		
			$primaryKey = $this->buildViaPk($relatedRecord);
			return $viaRecordName::find($primaryKey)->single() !== false;
		}
		
		$query = clone $this->getQuery();		
		$query->andWhere($relatedRecord->pk());
		
		$toRecordName = $this->relation->getToRecordName();
		return $toRecordName::find($query)->single();
		
	}
}