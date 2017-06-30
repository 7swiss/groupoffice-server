<?php
namespace GO\Core\Log\Model;

use GO\Core\Users\Model\User;
use GO\Core\Orm\Model\RecordType;
use IFW\Orm\Record;

/**
 * The Entry model
 *
 * 
 * @property RecordType $aboutRecordType
 *
 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class Entry extends Record {
	
	/**
	 * 
	 * @var int
	 */							
	public $id;

	/**
	 * 
	 * @var \DateTime
	 */							
	public $createdAt;

	/**
	 * 
	 * @var string
	 */							
	public $createdBy;

	/**
	 * 
	 * @var string
	 */							
	public $moduleName;

	/**
	 * 
	 * @var int
	 */							
	public $recordId;

	/**
	 * 
	 * @var string
	 */							
	public $recordClassName;

	/**
	 * 
	 * @var string
	 */							
	public $remoteIpAddress;

	/**
	 * 
	 * @var string
	 */							
	public $userAgent;

	/**
	 * 
	 * @var string
	 */							
	public $type;


	/**
	 * 
	 * @var string
	 */							
	public $description;
	
	private $parsedUserAgent;
	
	protected static function internalGetPermissions() {
		return new \IFW\Auth\Permissions\Everyone();
	}
	
	protected function init() {
		parent::init();
		
		if($this->isNew()) {
			$this->createdBy = GO()->getAuth()->user()->username;
			$this->moduleName = GO()->getRouter()->getModuleName();

			if(isset($_SERVER['REMOTE_ADDR'])) {
				$this->remoteIpAddress = $_SERVER['REMOTE_ADDR'];
			}

			if(isset($_SERVER['HTTP_USER_AGENT'])) {
				$this->userAgent = $_SERVER['HTTP_USER_AGENT'];
			}else if(GO()->getEnvironment()->isCli()) {
				$this->userAgent = 'cli';
			}
		}
		$this->parsedUserAgent = parse_user_agent($this->userAgent);
		
	}
	
	/**
	 * Get client platform.
	 * 
	 * eg. Windows, Linux or Mac OS X
	 * 
	 * @return string
	 */
	public function getPlatform() {
		return $this->parsedUserAgent['platform'];
	}
	
	/**
	 * Get client browser and version
	 * 
	 * eg. Chrome 53
	 * 
	 * @return string
	 */
	public function getBrowser() {
		return $this->parsedUserAgent['browser'].' '.$this->parsedUserAgent['version'];
	}
	
	public function setRecord(\GO\Core\Orm\Record $record) {
		$this->recordClassName = $record->getClassName();
		$this->recordId = implode('-', $record->pk());
		$this->moduleName = $record->findModuleName();
	}
	
	public function getModule() {
		$module = \GO\Core\Modules\Model\Module::find(['name' => $this->moduleName])->single();
		
		if(!$module) {
			return null;
		}
		
		return $module;
 	}
	
	
	
//	public function getRecord() {
//		
//		if(!isset($this->recordId)) {
//			return null;
//		}
//		
//		$recordClass = $this->aboutRecordType->className;
//		
//		return $recordClass::findByPk($this->recordId);
//	}
	
}