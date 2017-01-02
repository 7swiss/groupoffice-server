<?php
namespace GO\Modules\GroupOffice\Messages\Model;

use GO\Core\Tags\Model\Tag;
use IFW\Auth\Permissions\ViaRelation;
use IFW\Orm\Record;

/**
 * The contact model
 *
 * @property int $id
 * @property string $name
 * @property Contact $contact
 *
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class ThreadTag extends Record{		
	/**
	 * 
	 * @var int
	 */							
	public $threadId;

	/**
	 * 
	 * @var int
	 */							
	public $tagId;

	public static function defineRelations(){		
		self::hasOne('thread', Thread::class, ['threadId' => 'id']);
		self::hasOne('tag', Tag::class, ['tagId' => 'id']);
	}
	public static function internalGetPermissions() {
		return new ViaRelation( 'thread');
	}
}