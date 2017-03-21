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
 * @property string $blobId blob has reference to the Blob table
 * @property FileType $type Type of file
 * @property Version[] $version Older versions of the file
 *
 */
class File extends Node {

	protected static function defineRelations() {
		self::hasOne('blob', Blob::class, ['blobId'=>'blobId']);
		self::hasMany('versions', Version::class, ['id'=>'nodeId']);
		parent::defineRelations();
		
	}

	/**
	 * User by WebDAV
	 * @param string $data
	 * @return File
	 */
	public static function create($data) {
		$blob = \GO\Core\Blob\Model\Blob::fromString($data);
		$file = new self();
		$file->blob = $blob;
		return $file;
	}

}
