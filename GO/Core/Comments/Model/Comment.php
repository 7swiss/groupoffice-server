<?php

namespace GO\Core\Comments\Model;

use DateTime;
use GO\Core\Orm\Record;
use GO\Core\Users\Model\User;
use IFW\Orm\Query;

/**
 * The Comment record
 * 
 * Using:
 * 
 * 1. Create a new link record between an item and comment. It should have a 
 * 'commentId' and for example 'taskId' as in {@see GO\Modules\GroupOffice\Tasks\Model\TaskComment}
 * 
 * 2. Create a controller for the new link record. See {@see \GO\Core\Comments\Controller\CommentController}
 * 
 * 
 *
 * @property Attachment[] $attachments
 * @property User $creator
 * 
 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class Comment extends Record {

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
	 * @var DateTime
	 */
	public $createdAt;

	/**
	 * 
	 * @var int
	 */
	public $modifiedBy;

	/**
	 * 
	 * @var DateTime
	 */
	public $modifiedAt;

	/**
	 * 
	 * @var string
	 */
	public $content;
	



	protected static function defineRelations() {
		self::hasMany('attachments', Attachment::class, ['id' => 'commentId']);
		self::hasOne('creator', User::class, ['createdBy' => 'id']);
	}	

	public static function getDefaultReturnProperties() {
		return parent::getDefaultReturnProperties() . ',attachments,creator[id,username,photoBlobId]';
	}
	
//	public static function find($query = null) {
//		
//		$query = Query::normalize($query)
//						->select('t.*,c.*')
//						->join('comments_comment', 'c', 't.commentId = c.id', 'INNER');
//		
//		return parent::find($query);
//	}
//	
//	protected function init() {
//		parent::init();
//		
//		//disable this validation as it will be populated by the auto increment of the comments_comment table
//		$this->getColumn('commentId')->required = false;
//	}
//	
//	public static function getColumn($name) {
//		$col = parent::getColumn($name);
//		if($col) {
//			return $col;
//		}
//		
//		return \IFW\Db\Table::getInstance('comments_comment')->getColumn($name);
//	}
//	
//	protected function internalSave() {
//		
//		$data = [
//					'modifiedBy' => GO()->getAuth()->user()->id(),
//					'modifiedAt' => new DateTime(),
//					'content' => $this->content
//			];
//		
//		if($this->isNew()) {
//			
//			$data ['createdBy'] = GO()->getAuth()->user()->id();
//			$data ['createdAt'] = new DateTime();
//			
//			if(!GO()->getDbConnection()->createCommand()->insert('comments_comment', $data)->execute()) {
//				return false;
//			}		
//			$this->commentId = $this->id = GO()->getDbConnection()->getPDO()->lastInsertId();		
//			
//			
//		}else
//		{			
//			if(!GO()->getDbConnection()->createCommand()->update('comments_comment', $data, ['id' => $this->id])->execute()) {
//				return false;
//			}		
//		}
//		
//		
//		
//		return parent::internalSave();
//	}

}
