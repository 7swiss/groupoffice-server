<?php
namespace GO\Core\Comments\Model;
						
use GO\Core\Orm\Record;
						
/**
 * The Attachment record
 *
 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */

class Attachment extends Record{

	/**
	 * 
	 * @var int
	 */							
	public $id;

	/**
	 * 
	 * @var int
	 */							
	public $commentId;

	/**
	 * 
	 * @var string
	 */							
	public $blobId;

	/**
	 * 
	 * @var string
	 */							
	public $name;
	
	protected static function defineRelations() {
		self::hasOne('comment', Comment::class, ['commentId' => 'id']);
		self::hasOne('blob', \GO\Core\Blob\Model\Blob::class, ['blobId' => 'blobId']);
	}

	
	protected static function internalGetPermissions() {
		return new \IFW\Auth\Permissions\ViaRelation('comment');
	}

}