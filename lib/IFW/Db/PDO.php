<?php
namespace IFW\Db;

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
class PDO extends \PDO{
	public function __construct($dsn, $username, $passwd, $options=null) {
		parent::__construct($dsn, $username, $passwd, $options);
		
		$this->setAttribute(\PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$this->setAttribute(\PDO::ATTR_PERSISTENT, true);

		//needed for foundRows
//		$this->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY,true); 

		$this->query("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");
		$this->query("SET sql_mode='STRICT_ALL_TABLES'");
		$this->query("SET time_zone = '+00:00'");
	}
}
