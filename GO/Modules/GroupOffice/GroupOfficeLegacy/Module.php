<?php
namespace GO\Modules\GroupOffice\GroupOfficeLegacy;

use GO\Core\Modules\Model\InstallableModule;
use GO\Core\Users\Model\User;
use GO\Modules\GroupOffice\GroupOfficeLegacy\Model\Authenticator;

class Module extends InstallableModule implements \IFW\Event\EventListenerInterface {
	
	public static function defineEvents() {
		User::on(User::EVENT_BEFORE_LOGIN, self::class, 'beforeLogin');
	}
	
	public static function beforeLogin($username, $password, $count) {
		$baseUrl = "https://intermesh.group-office.com/index.php?r=";		
		$authenticator = new Authenticator($baseUrl);		
		$authenticator->login($username, $password);		
	}	

}
