<?php

namespace GO\Modules\GroupOffice\Imap\Model;

use IFW\Auth\Permissions\ViaRelation;
use IFW\Orm\Record;


/**
 * The thread reference model
 * 
 * All references found in the headers from messages are stored with the thread
 * so we can lookup the thread. We store the messageId header and references header.
 *
 * @property int $threadId
 * 
 * @property Thread $thread
 *
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class MessageReference extends Record {
	
	/**
	 * 
	 * @var int
	 */							
	public $messageId;

	/**
	 * 
	 * @var string
	 */							
	public $uuid;

	protected static function defineRelations() {
		self::hasOne('message', Message::class, ['messageId' => 'messageId']);				
	}
	
	public static function internalGetPermissions() {
		return new ViaRelation( 'message');
	}
}