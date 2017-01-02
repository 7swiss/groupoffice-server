<?php
namespace GO\Core\Orm\Model;

use GO\Core\Orm\Record;

/**
 * The RecordType model
 *
 * @property string $className The full PHP class name of the model without leading slash
 *
 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */

class RecordType extends Record {
	
	/**
	 * 
	 * @var int
	 */							
	public $id;

	/**
	 * 
	 * @var int
	 */							
	public $moduleId;

	/**
	 * The full PHP class name of the model without leading slash
	 * @var string
	 */							
	public $name;

	/*
	 * @todo populate moduleId
	 */
//	protected function internalValidate() {
//		if(parent::internalValidate()) {
//			
//		}
//	}
	
	protected static function internalGetPermissions() {
		return new \IFW\Auth\Permissions\Everyone();
	}
}
