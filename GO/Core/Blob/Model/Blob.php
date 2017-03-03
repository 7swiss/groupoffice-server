<?php

/**
 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Michael de Hart <mdhart@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */

namespace GO\Core\Blob\Model;

use DateInterval;
use DateTime;
use GO\Core\Orm\Record;
use IFW\Fs\File;

/**
 * This represents the data in disk.
 * Without a file module Group-Office still has (temp) file
 * The expireAt property is set when a file is uploaded.
 * This is removed when the Blob is link to an object (email, event, contact photo or file in files module.
 * 
 * @example
 * `````````````````````
 * $blob = \GO\Core\Blob\Model\Blob::fromFile(new \IFW\Fs\File(dirname(__DIR__).'/Resources/intermesh-logo.png'));
 * `````````````````````````````
 *
 * @property string $id SHA1 40-char hash of the binary data,
 * @property int $ownedBy the PK of the user tha thas uploaded this file,
 * @property string $filename fiel name,
 */
class Blob extends Record {

	/**
	 * 
	 * @var string
	 */							
	public $blobId;

	/**
	 * ctime,
	 * @var \DateTime
	 */							
	public $createdAt;

	/**
	 * 
	 * @var int
	 */							
	public $createdBy;

	/**
	 * This record will be removed by garbage collection of this is set
	 * @var \DateTime
	 */							
	public $expireAt;

	/**
	 * the contentType type of the file
	 * @var string
	 */							
	public $contentType;

	/**
	 * 
	 * @var string
	 */							
	public $name;

	/**
	 * filesize in bytes
	 * @var int
	 */							
	public $size;

	const IMAGE = 'Image';
	const EXPIRE_TIME = 'PT1H';

	private $tmpFile = null;
	public $inProgress = false;

	public function setTmpFile($value) {
		$this->tmpFile = $value;
	}

	protected function init() {
		if ($this->isNew()) {
			$this->createdAt = new DateTime();
			$this->expire();
		}
	}

	protected static function internalGetPermissions() {
		return new \IFW\Auth\Permissions\Everyone();
	}

	protected static function defineRelations() {
		self::hasMany('users', BlobUser::class, ['blobId' => 'blobId']);
	}

	/**
	 * Create a new blob from a given file.
	 *
	 * @example 
	 * ````
	 * $blob = \GO\Core\Blob\Model\Blob::fromFile(new \IFW\Fs\File(dirname(__DIR__).'/Resources/intermesh-logo.png'));
	 * ```
	 * @param File $file
	 * @return \self
	 * @throws \Exception
	 */
	static public function fromFile(File $file, \DateTime $expireAt = null) {

		if (!$file->exists()) {
			throw new \Exception("Given file does not exist!");
		}

		$blobId = $file->getSha1Hash();
		if (($blob = self::findByPk($blobId))) {
			return $blob;
		} else {
			$blob = new self();
		}

		$blob->blobId = $blobId;
		$blob->tmpFile = $file;
		$blob->contentType = $file->getMimeType();
		$blob->size = $file->getSize();
		$blob->name = $file->getName();

		$blob->expireAt = $expireAt;

		if (!$blob->save()) {
			throw new \Exception("Could not save blob!");
		}

		return $blob;
	}

	public static function fromString($str, \DateTime $expireAt = null) {
		$file = GO()->getAuth()->getTempFolder()->getFile(uniqid());
		$file->putContents($str);

		return self::fromFile($file, $expireAt);
	}

	/**
	 * Binary contents
	 * @return BINARY
	 */
	public function contents() {
		return file_get_contents($this->getPath());
	}

	/**
	 * When the expireAt has passed or there are no more users
	 * @return bool
	 */
	public function isStale() {
		return $this->expireAt < new \DateTime();
	}

	protected function internalSave() {
		if ($this->inProgress) {
			return false; // cannot save while chucked uplaod in progress
		}
		$success = true;

		$file = new File($this->getPath());
		if (!empty($this->tmpFile) && !$file->exists()) {
			$file->getFolder()->create();
			if ($this->tmpFile->isTemporary()) {
				$success = $this->tmpFile->move($file);
			} else {
				$success = $this->tmpFile->copy($file);
			}
		}
		return $success && parent::internalSave();
	}

	public function addUser($pk, \GO\Core\Orm\Model\RecordType $recordType) {
		$blobUser = BlobUser::findByPk(['blobId' => $this->blobId, 'modelTypeId' => $recordType->id, 'modelPk' => implode('-', $pk)]);
		if (!empty($blobUser)) {
			return; // already using (by other fields)
		}
		$blobUser = new BlobUser();
		$blobUser->modelPk = implode('-', $pk);
		$blobUser->modelTypeId = $recordType->id;
		$blobUser->blobId = $this->blobId;
		$this->expireAt = null;
		$this->users[] = $blobUser;
		return $this->save();
	}

	/**
	 * By default an uploaded file is valid for 1 hour
	 * @param string $interval time to live
	 */
	public function expire($interval = self::EXPIRE_TIME) {
		$expireDate = new \DateTime();
		$expireDate->add(new DateInterval($interval));
		$this->expireAt = $expireDate;
		return $this;
	}

	public function isUsed() {
		$firstUser = BlobUser::find(['blobId' => $this->blobId])->single();
		return !empty($firstUser);
	}

	public function getType() {

		if (!isset($this->contentType)) {
			return "unknown";
		}

		list($type, $format) = explode('/', $this->contentType);
		return ucfirst($type);
	}

	public function getFormat() {
		if (!isset($this->contentType)) {
			return "unknown";
		}

		list($type, $format) = explode('/', $this->contentType);
		return strtoupper($format);
	}

	private function storagePath() {
		
		return GO()->getConfig()->getDataFolder()->getPath() . '/blob/' . $this->createdAt->format('Y') . '/' . $this->createdAt->format('m') . '/';
	}

	public function getPath() {
		if(!isset($this->createdAt)) {
			return null;
		}
		return $this->storagePath() . $this->blobId;
	}

}
