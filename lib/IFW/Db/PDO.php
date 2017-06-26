<?php

namespace IFW\Db;

use IFW;
use PDO as PhpPdo;

/**
 * PDO Connection
 * 
 * PDO extension that set's some defaults for the GO framework.
 * It set's UTF8 as charset, MySQL strict mode in debug mode and persistant 
 * connections.
 * 
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class PDO extends PhpPdo {

	public $sqlMode = "ONLY_FULL_GROUP_BY,STRICT_ALL_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION";

	public function __construct($dsn, $username, $passwd, $options = null) {
		parent::__construct($dsn, $username, $passwd, $options);

		$this->applyConfig();

		$this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$this->setAttribute(PDO::ATTR_PERSISTENT, true);

		$this->query("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");

//		\IFW::app()->debug($this->sqlMode);
		
		if (isset($this->sqlMode)) {
			$this->query("SET sql_mode='" . $this->sqlMode . "'");
		}

		$this->query("SET time_zone = '+00:00'");
	}

	/**
	 * Applies config options to this object
	 */
	private function applyConfig() {
		$className = static::class;
		if (isset(IFW::app()->getConfig()->classConfig[$className])) {
			foreach (IFW::app()->getConfig()->classConfig[$className] as $key => $value) {
				$this->$key = $value;
			}
		}
	}

}
