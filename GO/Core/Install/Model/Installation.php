<?php
namespace GO\Core\Install\Model;

use IFW\Orm\Record;

class Installation extends Record {
	/**
	 * 
	 * @var int
	 */							
	public $id;

	/**
	 * 
	 * @var int
	 */							
	public $dbVersion;

	public static function tableName() {
		return 'core_installation';
	}
}