<?php
namespace GO\Core\Accounts\Model;

use IFW\Orm\PropertyRecord;
/**
 * The Capability model
 *
 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class Capability extends PropertyRecord {	
	
	/**
	 * Primary key
	 * @var int
	 */							
	public $id;

	/**
	 
	 * @var int
	 */							
	public $accountId;

	/**
	 * The PHP class name of the model that can be contained by this account
	 * 
	 * @var string
	 */							
	public $modelName;

	protected static function defineRelations() {
		self::hasOne('account', Account::class, ['accountId' => 'id']);
	}
}
