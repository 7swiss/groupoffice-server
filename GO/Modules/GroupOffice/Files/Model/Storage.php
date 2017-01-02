<?php
/*
 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Michael de Hart <mdhart@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
namespace GO\Modules\GroupOffice\Files\Model;

use GO\Core\Orm\Record;

/**
 * A Node can be a File or a Folder
 * The time depends on the object it is attached to
 *
 */
class Storage extends Record {

	/**
	 * PK
	 * @var int
	 */							
	public $id;

	/**
	 * PK
	 * @var string
	 */							
	public $name;

	/**
	 * 
	 * @var int
	 */							
	public $quota;

	/**
	 * 
	 * @var int
	 */							
	public $usage = 0;

}
