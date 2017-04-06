<?php
namespace GO\Core\Accounts\Model;

use GO\Core\Orm\Record;

/**
 * @param Account $coreAccount
 */
abstract class AccountRecord extends Record {
	protected static function defineRelations() {
		
		self::hasOne('coreAccount', Account::class, ['id' => 'id']);
		
		parent::defineRelations();
	}
	
	protected function init() {
		parent::init();
		
		if($this->isNew()) {
			$this->coreAccount = new Account();
			$this->coreAccount->createdBy  = isset($this->createdBy) ? $this->createdBy : GO()->getAuth()->user()->id();
			$this->coreAccount->modelName = $this->getClassName();
		}
	}
	
	protected static function internalGetPermissions() {
		return new \IFW\Auth\Permissions\ViaRelation('coreAccount');
	}
	
	protected function internalValidate() {
		
//		if($this->isNew()) {
			$this->coreAccount->modelName = $this->getClassName();
			$this->coreAccount->name = $this->getName();
			
			if(!$this->coreAccount->save()) {
				throw new \Exception("Could not save core account");
			}

			$this->id = $this->coreAccount->id;
//		}
		
		return parent::internalValidate();
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
	
	abstract public function getName();
	

	
	
}
