<?php
namespace GO\Modules\GroupOffice\DevTools\Model;

use DateTime;
use GO\Core\Orm\Record as CoreRecord;

class Record extends CoreRecord {
	
	/**
	 * 
	 * @var int
	 */							
	public $id;

	/**
	 * 
	 * @var string
	 */							
	public $name;

	/**
	 * 
	 * @var DateTime
	 */							
	public $createdAt;

	/**
	 * 
	 * @var DateTime
	 */							
	public $modifiedAt;

}

