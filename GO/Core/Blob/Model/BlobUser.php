<?php
/**
 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Michael de Hart <mdhart@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
namespace GO\Core\Blob\Model;

/**
 * This represents the data in disk.
 * Without a file module Group-Office still has (temp) file
 * The expireAt property is set when a file is uploaded.
 * This is removed when the Blob is link to an object (email, event, contact photo or file in files module.
 *
 * @property string $id SHA1 40-char hash of the binary data,
 */
class BlobUser extends \GO\Core\Orm\Record {
	
	/**
	 * 
	 * @var string
	 */							
	public $blobId;

	/**
	 * 
	 * @var int
	 */							
	public $modelTypeId;

	/**
	 * 
	 * @var string
	 */							
	public $modelPk;
	
	protected static function internalGetPermissions() {
		return new \IFW\Auth\Permissions\Everyone();
	}

}
