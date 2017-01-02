<?php
/*
 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Michael de Hart <mdhart@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
namespace GO\Modules\GroupOffice\Files\Model;

use GO\Core\Blob\Model\Blob;
use GO\Core\Orm\Record;

/**
 * File is a reference to a blob and a blob user
 *
 * @property Version[] $version Older versions of the file
 *
 */
class Version extends Record {

	/**
	 * 
	 * @var int
	 */							
	public $id;

	/**
	 * Type of file
	 * @var \DateTime
	 */							
	public $createdAt;

	/**
	 * 
	 * @var int
	 */							
	public $nodeId;

	/**
	 * blob has reference to the Blob table
	 * @var string
	 */							
	public $blobId;

	protected static function defineRelations() {
		self::hasOne('blob', Blob::class, ['blobId'=>'blobId']);
		self::hasOne('file', File::class, ['nodeId'=>'id']);
	}


}
