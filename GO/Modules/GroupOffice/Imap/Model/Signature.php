<?php

namespace GO\Modules\GroupOffice\Imap\Model;

use IFW\Auth\Permissions\ViaRelation;
use IFW\Orm\Record;



/**
 * The Signature model
 *
 * 
 * @property Account $acount
 *
 * @copyright (c) 2015, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class Signature extends Record {	
	
	/**
	 * 
	 * @var int
	 */							
	public $id;

	/**
	 * 
	 * @var int
	 */							
	public $accountId;

	/**
	 * 
	 * @var string
	 */							
	public $name;

	/**
	 * 
	 * @var string
	 */							
	public $signature;

	protected static function defineRelations() {
		self::hasOne('account', Account::class, ['accountId' => 'id']);
	}
	
	public static function internalGetPermissions() {
		return new ViaRelation( 'account');
	}
}