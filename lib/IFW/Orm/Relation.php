<?php

namespace IFW\Orm;

use IFW\Db\Criteria;

/**
 * A relation defines and queries related records
 *
 * eg. $record->relation automatically fetches the related record.
 *
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class Relation {

	

	/**
	 * Cascade delete relations. 
	 * 
	 * Only works on has_one and has_many relations. {@see setDeleteAction()}
	 */
	const DELETE_CASCADE = 2;

	/**
	 * Restrict delete relations. 
	 * 
	 * Only works on has_one and has_many relations. {@see setDeleteAction()}
	 */
	const DELETE_RESTRICT = 1;

	/**
	 * Don't do anything on delete (Default action).
	 * 
	 * {@see setDeleteAction()}
	 */
	const DELETE_NO_ACTION = 0;

	/**
	 * Name of the relation
	 * 
	 * @var string 
	 */
	private $name;

	/**
	 * Class name of the related record
	 *
	 * @var string
	 */
	private $fromRecordName;

	/**
	 * Class name of the related record
	 *
	 * @var string
	 */
	private $toRecordName;

	/**
	 * Key column of the relation
	 *
	 * @var array ['fromField' => 'toField']
	 */
	private $keys;


	/**
	 * The delete action:
	 *
	 * Relation::DELETE_RESTRICT
	 * Relation::DELETE_CASCADE
	 * 
	 * @var int 
	 */
	public $deleteAction = self::DELETE_NO_ACTION;


	/**
	 * Auto create a new relation record when creating a new record. 
	 * 
	 * This only works for TYPE_HAS_MANY and TYPE_HAS_ONE. This can be useful
	 * for loading a default email address input for a contact for example.
	 * 
	 * @var bool 
	 */
//	public $autoCreate = false;
	

	private $many = false;
	
	
	/**
	 *
	 * @var Query
	 */
	public $query;
	
	/**
	 * The via record class name
	 * @var string
	 */
	private $viaRecordName;
	
	/**
	 *
	 * @var array eg ['tagId' => 'id'] 
	 */
	private $viaKeys;
	
	
	/**
	 *
	 * @param string $name Name of the relation
	 * @param string $fromRecordName Class name of the record that has this relation
	 * @param string $toRecordName Class name of the related record
	 * @param array $keys The keys of the relation. eg. ['id' => 'contactId']
	 * @param boolean $many true if this is a has many relation 1:n
	 */
	public function __construct($name, $fromRecordName, $toRecordName, array $keys, $many) {
		$this->name = $name;
		$this->keys = $keys;
		$this->fromRecordName = $fromRecordName;
		$this->toRecordName = $toRecordName;		
		$this->many = $many;
	}

	/**
	 * Check if this is an identifying relationship
	 * 
	 * An identifying relation is like an attribute. For example email addresses of a contact are identifying related records.
	 * 
	 * It's determined by the database primary key. The to key in the related record must be included.
	 * 
	 * For example with contact.emailAddresses the primary key of the email address record must be 
	 * ['id', 'contactId']
	 * 
	 * @see http://stackoverflow.com/questions/762937/whats-the-difference-between-identifying-and-non-identifying-relationships
	 * 
	 * return @boolean
	 * 
	 */
	public function isIdentifying() {		
		
		$toRecordName = $this->toRecordName;		
		$pks = $toRecordName::getPrimaryKey();	
		
		foreach($this->keys as $fromKey => $toKey) {
			if(!in_array($toKey, $pks)) {
				return false;
			}
		}
		
		return true;
	}

	/**
	 * Set extra query paramaters
	 * 
	 * Typical use case is ordering.
	 * 
	 * You can also set extra where parameters but please be aware that they are not 
	 * applied when setting the relation.
	 * 
	 * <code>
	 * self::has('tags',Tag::class, ['id'=>'contactId'], true)
	 *						->via(ContactTag::class,['tagId'=>'id'])
	 *						->setQuery((new Query())->orderBy(['name'=>'ASC']));
	 * </code>
	 * 
	 * 
	 * For example in {@see GO\Modules\Messages\Model\Message} we have a relation 
	 * addresses but we can split them into from to, cc and bcc:
	 * 
	 * ```````````````````````````````````````````````````````````````````````````
	 *  protected static function defineRelations() {
	 *				
	 *		self::hasMany('to', Address::class, ['id' => 'messageId'])
	 *      ->setQuery((new Query())->where(['type'=>  Address::TYPE_TO]));
	 *		
	 *	}	
	 *	
	 *	public function setTo($v) {
	 *		foreach($v as $address) {					
	 *			$address = $this->addresses->add($address);
	 *			$address->type = Address::TYPE_TO;
	 *		}		
	 *	}
	 * ```````````````````````````````````````````````````````````````````````````
	 * 
	 * 
	 * @param \IFW\Orm\Query $query
	 */
	public function setQuery($query) {
		$this->query = Query::normalize($query);
		
		return $this;
	}
	


	/**
	 * Get the name of the relation
	 * 
	 * @param string
	 */
	public function getName() {
		return $this->name;
	}
	
	/**
	 * Returns true if this is a has many relation
	 * 
	 * @return boolean
	 */
	public function hasMany() {
		return $this->many;
	}
	

	/**
	 * Set the delete action
	 *
	 * You can use:
	 * 
	 * Relation::DELETE_RESTRICT
	 * Relation::DELETE_CASCADE
	 * 
	 * Note that it's better to let the database handle cascade deletes as it is
	 * much faster. However in some cases it can be necessary to make the framework
	 * handle cascading if you want to program some extra logic on delete.
	 *
	 * @param int $action
	 * @return \self
	 */
	public function setDeleteAction($action) {
		$this->deleteAction = $action;

		return $this;
	}

	/**
	 * Class name of the related record
	 *
	 * @return string
	 */
	public function getToRecordName() {
		return $this->toRecordName;
	}
	
	/**
	 * Class name of the related record
	 *
	 * @return string
	 */
	public function getFromRecordName() {
		return $this->fromRecordName;
	}

	/**
	 * Get the foreign key
	 *
	 * @return array
	 */
	public function getKeys() {
		return $this->keys;
	}
	
	
	/**
	 * Set's the link record on a many many relation.
	 *
	 * Eg. a user has a many many relation with groups. The link record
	 *
	 * is UserGroup in this case. It connects the User and Group records.
	 * 
	 * <code>
	 * self::hasMany('groups', Group::class, ["id"=>"userId"])
	 *		->via(UserGroup::class, ['groupId'=> "id"]);
	 * </code>
	 * 
	 * 
	 * The table alias assigned in the query is {RelationName}Link.
	 * 
	 *
	 * @param string $recordName The name of the link record eg. contactTag
	 * @param string $keys eg ['tagId' => 'id']
	 * @return self
	 */
	public function via($recordName, array $keys) {
		$this->viaRecordName = $recordName;	
		$this->viaKeys = $keys;
		
		return $this;
	}
	
	/**
	 * Get the via record class name if defined
	 * 
	 * @param string
	 */
	public function getViaRecordName() {
		return $this->viaRecordName;
	}
	
	/**
	 * Get the via keys
	 * 
	 * eg. ['tagId' => 'id']
	 * 
	 * @return array
	 */
	public function getViaKeys() {
		return $this->viaKeys;
	}

	/**
	 * It's not a belongs to if the whole primary key is used to connect to 
	 * another record.
	 * 
	 * Or when both keys are primary and the foreign key is auto increment.
	 * 
	 * @return boolean
	 */
	public function isBelongsTo() {
		
		//ContactEmailAddress::hasOne('contact', Contact::class, ['contactId' => 'id']);
		
		if($this->many) {
			return false;
		}
		
		$fromRecord = $this->fromRecordName;
		$fromPks = $fromRecord::getPrimaryKey();
		
		$fromKeys = array_keys($this->keys);
		$keyCount = count($fromKeys);
		$pkCount = count($fromPks);
		if($keyCount != $pkCount) {
			return true;
		}	
		
		foreach($this->keys as $fromField => $toField) {
			
			$from = $fromRecord::getColumn($fromField);
			
			if(!$from->primary) {
				return true;
			}
			
			$toRecordName = $this->toRecordName;			
			$to = $toRecordName::getColumn($toField);			
			if($from->primary && $to->primary && $to->autoIncrement) {
				return true;
			}
		}
		
		
		return false;
	}

	
	/**
	 * Set's this relation of $record to $value. Don't use this method directly.
	 * ActiveRecord uses it for you when setting relations directly.
	 * 
	 * @param Record $record
	 * @param mixed $value
	 * @return RelationStore
	 */
	public function set(Record $record, $value) {			
		$store = $this->get($record);
		
		if($this->many) {			
			foreach($value as $record) {
				$store[] = $record;
			}			
		}else
		{
			$store[] = $value;
		}
		return $store;
	}

	/**
	 * Queries the database for the relation
	 *
	 * @param Record $record The record that this relation belongs to.
	 * @param Criteria|Query $extraQuery Passed when calling a relation as a function with Query as single parameter.
	 * @return RelationStore
	 */
	public function get(Record $record) {
		//When the keys are all null we don't pass the query so we can just contain new records
		$store = new RelationStore($this, $record, $this->getQuery($record));		
		return $store;
	}
	
	/**
	 * 
	 * @return Query
	 */
	private function getQuery(Record $record) {
		$query = isset($this->query) ? clone $this->query : new Query();	
		$query->_isRelational();
		
		//if all keys are null return null
		$isNull = true;
		if(isset($this->viaRecordName)) {
			
			$linkTableAlias = $this->name.'Link';
			
			//ContactTag.tagId -> tag.id
			$on = '';
			foreach($this->viaKeys as $fromField => $toField) {
				$on .= '`'.$linkTableAlias.'`.`'.$fromField.'`=`'.$query->tableAlias.'`.`'.$toField.'`'; 
			}
			//join ContactTag
			$query->join($this->viaRecordName, $linkTableAlias, $on);

			foreach($this->keys as $myKey => $theirKey) {
				
				if(isset($record->$myKey)) {
					$isNull = false;
				}
				
				$query->andWhere([$linkTableAlias.'.'.$theirKey => $record->$myKey]);			
			}
		}else
		{
			foreach($this->keys as $myKey => $theirKey) {
				if(isset($record->$myKey)) {
					$isNull = false;
				}
				$query->andWhere([$theirKey => $record->$myKey]);			
			}
		}
		
		return $isNull ? null : $query;
	}
}
