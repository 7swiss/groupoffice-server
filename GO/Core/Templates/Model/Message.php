<?php
namespace GO\Core\Templates\Model;
use GO\Core\Orm\Record;

/**
 * The Message model
 *
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

}
