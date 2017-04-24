<?php 
namespace GO\Core\Accounts\Model;

/**
 * Permission model for account items
 * 
 */
class AccountItemPermissions extends \GO\Core\Auth\Permissions\Model\GroupPermissions {
	
	
	public function __construct() {
		parent::__construct(AccountGroup::class, 'accountId');
	}
	
	
	
	protected function internalApplyToQuery(\IFW\Orm\Query $query, \IFW\Auth\UserInterface $user) {
		
		$query->joinRelation('account');
		
		return parent::internalApplyToQuery($query, $user);
	}

}
