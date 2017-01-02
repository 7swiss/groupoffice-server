<?php
namespace GO\Core\Links\Model;


use GO\Core\Orm\Record;

class Link extends Record {
	
	
	/**
	 * 
	 * @var int
	 */							
	public $fromRecordTypeId;

	/**
	 * 
	 * @var int
	 */							
	public $fromRecordId;

	/**
	 * 
	 * @var int
	 */							
	public $toRecordTypeId;

	/**
	 * 
	 * @var int
	 */							
	public $toRecordId;

	public static function create(Record $fromRecord, Record $toRecord) {
		$link = new self;
		$link->setFromRecord($fromRecord);
		$link->setToRecord($toRecord);		
		if(!$link->save()) {
			return false;
		}
		
		$link = new self;
		$link->setToRecord($fromRecord);
		$link->setFromRecord($toRecord);		
		if(!$link->save()) {
			return false;
		}
		
		return true;
		
	}
		
	
	public function setFromRecord(Record $record) {
		$this->fromRecordTypeId = $record->getRecordType()->id;
		$this->fromRecordId = $record->id;
	}
	
	public function setToRecord(Record $record) {
		$this->toRecordTypeId = $record->getRecordType()->id;
		$this->toRecordId = $record->id;
	}
	
	
	public function getToRecord() {
		$className = RecordType::findByPk($this->toRecordTypeId)->className;		
		return $className::findByPk($this->toRecordId);
	}
	
	
	public function getFromRecord() {
		$className = RecordType::findByPk($this->fromRecordTypeId)->className;		
		return $className::findByPk($this->fromRecordId);
	}
}

