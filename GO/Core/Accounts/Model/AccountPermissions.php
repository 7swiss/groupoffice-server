<?php 
namespace GO\Core\Accounts\Model;

/**
 * Permission model for accounts
 * 
 * Accounts use has many groups relation for granting access to the contents.
 * 
 * The permissions model of the contents is almost identical to the model of the account itself with these exceptions:
 * 
 * 1. If you have write permisssions on the contents you do NOT have write permissions on the account.
 * 2. You need to be the owner of the account to have write permissions on the account.
 */
class AccountPermissions extends \GO\Core\Auth\Permissions\Model\GroupPermissions {
	
	const PERMISSION_WRITE_CONTENTS = 'writeContents';
	
	public function __construct() {
		parent::__construct(AccountGroup::class, 'id');
	}
	
	protected function internalCan($permissionType, \IFW\Auth\UserInterface $user) {
	
		switch($permissionType) {
			case self::PERMISSION_WRITE_CONTENTS:
				return parent::internalCan(self::PERMISSION_WRITE, $user);
			case self::PERMISSION_WRITE:
				return parent::internalCan(self::PERMISSION_MANAGE, $user);
			default:
				return parent::internalCan($permissionType, $user);
		}
		
	}
	
	protected function internalApplyToQuery(\IFW\Orm\Query $query, \IFW\Auth\UserInterface $user) {
		
		$requirePermissionType = $query->getRequirePermissionType();
		switch($requirePermissionType) {
			case self::PERMISSION_WRITE_CONTENTS:
				$query->requirePermissionType(self::PERMISSION_WRITE);
				break;
			
			case self::PERMISSION_WRITE:
				$query->requirePermissionType(self::PERMISSION_MANAGE);				
				break;
			
			default:
				break;
		}
		
		return parent::internalApplyToQuery($query, $user);
	}
	
	protected function internalApplyToQueryForAdmin(\IFW\Orm\Query $query, \IFW\Auth\UserInterface $user) {
		$requirePermissionType = $query->getRequirePermissionType();
		
//		if($requirePermissionType == self::PERMISSION_WRITE || $requirePermissionType == self::PERMISSION_MANAGE) {
			$query->where(['ownedBy' => \GO\Core\Users\Model\Group::ID_ADMINS]);
//		}
	}

}
