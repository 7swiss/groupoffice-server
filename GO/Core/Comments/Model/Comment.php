<?php
namespace GO\Core\Comments\Model;
						
use GO\Core\Orm\Record;
						
/**
 * The Comment record
 *
 * @property Attachment[] $attachments
 * @property User $creator
 * 
 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */

class Comment extends Record{


	/**
	 * 
	 * @var int
	 */							
	public $id;

	/**
	 * 
	 * @var int
	 */							
	public $createdBy;

	/**
	 * 
	 * @var \DateTime
	 */							
	public $createdAt;

	/**
	 * 
	 * @var int
	 */							
	public $modifiedBy;

	/**
	 * 
	 * @var \DateTime
	 */							
	public $modifiedAt;

	/**
	 * 
	 * @var string
	 */							
	public $content;
	
	protected static function defineRelations() {
		self::hasMany('attachments', Attachment::class, ['id'=>'commentId']);
		self::hasOne('creator', \GO\Core\Users\Model\User::class, ['createdBy' => 'id']);
	}

	public static function getDefaultReturnProperties() {
		return parent::getDefaultReturnProperties().',attachments,creator[id,username,photoBlobId]';
	}
}