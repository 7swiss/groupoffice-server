<?php

namespace GO\Core\Settings\Model;

/**
 * Core settings record
 * 
 * @example 
 * 
 * GO()->getSettings()->defaultLanuage;
 * 
 * @property \GO\Core\Smtp\Model\Account $smtpAccount
 */
class Settings extends \GO\Core\Orm\Record {

	/**
	 * 
	 * @var int
	 */
	public $id;

	/**
	 * 
	 * @var int
	 */
	public $smtpAccountId;

	/**
	 * Default language ISO code
	 * 
	 * Defaults to "en"
	 * 
	 * @var string
	 */
	public $defaultLanguage = 'en';

	protected static function internalGetPermissions() {
		return new \IFW\Auth\Permissions\ReadOnly();
	}

	protected function init() {
		$this->id = 1;
	}

	protected static function defineRelations() {
		self::hasOne('smtpAccount', \GO\Core\Smtp\Model\Account::class, ['smtpAccountId' => 'id'])->allowPermissionTypes([\IFW\Auth\Permissions\Model::PERMISSION_READ]);
	}

	public static function getDefaultReturnProperties() {
		return parent::getDefaultReturnProperties() . ',' . implode(',', array_keys(self::getRelations()));
	}

	public static function tableName() {
		return 'core_settings';
	}

	public function toArray($properties = null) {
		$arr = parent::toArray($properties);

		//remove ID as we want to access settings without ID. It's always 1.
		unset($arr['id']);

		return $arr;
	}

}
