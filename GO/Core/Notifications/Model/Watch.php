<?php

namespace GO\Core\Notifications\Model;

use GO\Core\GarbageCollection\GarbageCollectionInterface;
use GO\Core\Orm\Model\RecordType;
use GO\Core\Orm\Record;
use GO\Core\Users\Model\Group;
use IFW\Auth\Permissions\Everyone;
use IFW\Orm\Query;

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
class Watch extends Record implements GarbageCollectionInterface {

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
		self::hasOne('group', Group::class, ['groupId' => 'id']);
		//self::hasOne('groupUsers', \GO\Core\Users\Model\UserGroup::class, ['groupId' => 'groupId']);
		self::hasOne('about', RecordType::class, ['recordTypeId' => 'id']);
	}

	protected static function internalGetPermissions() {
		return new Everyone();
	}

	public static function create(Record $record, $groupId) {
		$watch = new self;
		$watch->groupId = $groupId;
		$watch->recordTypeId = $record->getRecordType()->id;
		$watch->recordId = $record->id;

		return $watch->save();
	}

	public function exists(Record $record, $groupId) {
		return self::findByPk([
								'groupId' => $groupId,
								'recordTypeId' => $record->getRecordType()->id,
								'recordId' => $record->id
						])->single();
	}

	public static function collectGarbage() {
		$recordTypes = RecordType::find(['IN', ['id' => self::find(
										(new Query())->distinct()->fetchSingleValue('recordTypeId')
		)]]);
		
		//find all record types and delete records that don't exist anymore
		foreach($recordTypes as $recordType) {
			$recordClass = $recordType->name;			
			GO()->getDbConnection()->createCommand()->delete(self::tableName(), 
							(new Query())
							->where(['recordTypeId' => $recordType->id])
							->andWhere(['NOT EXISTS', $recordClass::find('sub.id = t.recordId')])
							)->execute();
		}
	}

}
