<?php
namespace GO\Core\Modules\Model;

use GO\Core\Users\Model\UserGroup;
use IFW\Orm\Record;

/**
 * Module model
 * 
 * Each module that can be used in the application must have a database entry.
 *
 * @property int $id
 * @property string $name
 * @property int $version
 * @property bool $installed 
 * 
 * @property ModuleGroup $groups
 *
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class ModuleGroup extends Record{
	
	/**
	 * 
	 * @var int
	 */							
	public $moduleId;

	/**
	 * 
	 * @var int
	 */							
	public $groupId;

	/**
	 * 
	 * @var string
	 */							
	public $action;

	protected static function defineRelations() {
		
		self::hasMany('userGroup', UserGroup::class, ['groupId' => 'groupId']);
		
		parent::defineRelations();
	}
}