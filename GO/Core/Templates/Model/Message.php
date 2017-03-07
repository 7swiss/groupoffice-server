<?php

namespace GO\Core\Templates\Model;

use GO\Core\Orm\Record;

/**
 * The Message model
 * 
 * Contains subject and body templates. Template syntax can be used see {@see \IFW\Template\VariableParser}
 *
 * @example
 * 
 * ````````````````````````````````````````````````````````````````````````````
 * $tpl = GO\Core\Templates\Model::findByPk($id);
 * 
 * //create template parser
 * $tplParser = new VariableParser();
 * $tplParser->addModel('invitation', $this)
 * 	->addModel('user', GO()->getAuth()->user());
 * 
 * //Create E-mail message for sending via SMTP
 * $message = new \GO\Core\Email\Model\Message(
 * 						GO()->getSettings()->smtpAccount, 
 * 						$tplParser->parse($tpl->subject), 
 * 						$tplParser->parse($tpl->body));
 * 		
 * //Send to system account
 * $message->setTo(GO()->getSettings()->smtpAccount->fromEmail, GO()->getSettings()->smtpAccount->fromName);
 * 
 * $message->send();
 * 
 * ````````````````````````````````````````````````````````````````````````````
 *
 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class Message extends Record {

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
	 * 
	 * @var string
	 */
	public $name;

	/**
	 * 
	 * @var string
	 */
	public $subject;

	/**
	 * 
	 * @var string
	 */
	public $body;

	/**
	 * 
	 * @var string
	 */
	public $language;
	
	public function setModuleClassName($moduleClassName) {
		$module = \GO\Core\Modules\Model\Module::find(['name' => $moduleClassName])->single();
		$this->moduleId = $module->id;
	}
	
	protected static function internalGetPermissions() {
		return new \IFW\Auth\Permissions\Everyone();
	}

}
