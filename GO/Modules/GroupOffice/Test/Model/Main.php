<?php
namespace GO\Modules\GroupOffice\Test\Model;
						
use GO\Core\Orm\Record;
						
/**
 * The Main record
 *
 * @copyright (c) 2017, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */

class Main extends Record{


	/**
	 * 
	 * @var int
	 */							
	public $id;

	/**
	 * 
	 * @var bool
	 */							
	public $deleted = false;

	/**
	 * 
	 * @var \IFW\Util\DateTime
	 */							
	public $createdBy;

	/**
	 * 
	 * @var int
	 */							
	public $createdAt;

	/**
	 * 
	 * @var int
	 */							
	public $modifiedBy;

	/**
	 * 
	 * @var \IFW\Util\DateTime
	 */							
	public $modifiedAt;

	/**
	 * 
	 * @var int
	 */							
	public $ownedBy;

	/**
	 * 
	 * @var string
	 */							
	public $name;
	
	protected static function internalGetPermissions() {
		return new \GO\Core\Auth\Permissions\Model\Owner();
	}
	
	protected static function defineRelations() {
		self::hasOne('hasOne', RelationRecord::class, ['id' => 'mainId']);
		
		self::hasMany('hasMany', RelationRecord::class, ['id' => 'mainId']);
	}

}