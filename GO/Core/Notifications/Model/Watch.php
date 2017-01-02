<?php
namespace GO\Core\Notifications\Model;

/**
 * The NotificationGroup model
 *
 * 
 * 
 *
 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */

class Watch extends \GO\Core\Orm\Record {
	/**
	 * 
	 * @var int
	 */							
	public $groupId;

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
		self::hasOne('group', \GO\Core\Users\Model\Group::class, ['groupId' => 'id']);
		//self::hasOne('groupUsers', \GO\Core\Users\Model\UserGroup::class, ['groupId' => 'groupId']);
		self::hasOne('about', \GO\Core\Orm\Model\RecordType::class, ['recordTypeId' => 'id']);
	}
	
	protected static function internalGetPermissions() {
		return new \IFW\Auth\Permissions\Everyone();
	}
	
	public static function create(\GO\Core\Orm\Record $record, $groupId) {
		$watch = new self;
		$watch->groupId = $groupId;
		$watch->recordTypeId = $record->getRecordType()->id;
		$watch->recordId = $record->id;
		
		return $watch->save();
	}
	
	public function exists(\GO\Core\Orm\Record $record, $groupId)
	{
		return self::findByPk([
				'groupId' =>  $groupId,
				'recordTypeId' => $record->getRecordType()->id,
				'recordId' => $record->id
		])->single();
	}
	
}

