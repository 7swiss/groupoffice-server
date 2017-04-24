<?php
namespace GO\Core\Accounts\Model;

use GO\Core\Orm\Record;

/**
 * @param Account $coreAccount
 */
abstract class AccountAdaptorRecord extends Record implements AccountAdaptorInterface{
	protected static function defineRelations() {
		
		self::hasOne('coreAccount', Account::class, ['id' => 'id']);
		
		parent::defineRelations();
	}
	
	protected static function internalGetPermissions() {
		return new \IFW\Auth\Permissions\ViaRelation('coreAccount');
	}

	
	protected function internalDelete($hard) {

		if(!$this->coreAccount->delete()) {
			$this->setValidationError('id', \IFW\Validate\ErrorCode::RELATIONAL, "Couldn't delete core account record");
			return false;
		}
		
		return parent::internalDelete($hard);
	}
	
	public static function getDefaultReturnProperties() {
		$props = parent::getDefaultReturnProperties();
		$props .= ',className';
		
		return $props;
	}
	
	
	public static function getInstance(\GO\Core\Accounts\Model\Account $record) {
		return static::findByPk($record->id);
	}	
	
	public static function getCapabilities() {
		return [];
	}
	
}
