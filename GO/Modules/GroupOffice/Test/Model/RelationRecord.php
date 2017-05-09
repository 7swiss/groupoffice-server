<?php
namespace GO\Modules\GroupOffice\Test\Model;

use IFW\Orm\PropertyRecord;
						
/**
 * The RelationRecord record
 *
 * @copyright (c) 2017, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */

class RelationRecord extends PropertyRecord {


	/**
	 * 
	 * @var int
	 */							
	public $id;

	/**
	 * 
	 * @var int
	 */							
	public $mainId;

	/**
	 * 
	 * @var bool
	 */							
	public $deleted;

	/**
	 * 
	 * @var string
	 */							
	public $name;
	
	
	/**
	 * 
	 * @var string
	 */		
	public $description;
	
	protected static function defineRelations() {
		self::hasOne('main', Main::class, ['mainId' => 'id']);
	}


}