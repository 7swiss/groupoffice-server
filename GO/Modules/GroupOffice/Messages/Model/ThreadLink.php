<?php
namespace GO\Modules\GroupOffice\Messages\Model;

use GO\Core\Orm\Record;

class ThreadLink extends Record {
	
	/**
	 * 
	 * @var int
	 */							
	public $threadId;

	/**
	 * 
	 * @var int
	 */							
	public $recordTypeId;

	/**
	 * 
	 * @var int
	 */							
	public $recordId;

	protected static function defineRelations() {
		self::hasOne('thread', Thread::class, ['threadId'=>'id']);
	}
	
	public function setRecord(Record $record) {
		$this->recordTypeId = $record->getRecordType()->id;
		$this->recordId = $record->id;
	}
	
	
	public function getLinkedRecord() {
		$className = RecordType::findByPk($this->recordTypeId)->className;		
		return $className::findByPk($this->recordId);
	}
}
