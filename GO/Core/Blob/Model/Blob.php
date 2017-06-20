<?php

/**
 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Michael de Hart <mdhart@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */

namespace GO\Core\Blob\Model;

use DateInterval;
use DateTime;
use Exception;
use GO\Core\Orm\Model\RecordType;
use GO\Core\Orm\Record;
use IFW\Auth\Permissions\Everyone;
use IFW\Fs\File;
use IFW\Orm\Record as Record2;
use IFW\Orm\Relation;
use IFW\Util\ClassFinder;
use function GO;

/**
 * This represents the data in disk.
 * 
 * Without a file module Group-Office still has (temp) file
 * The expireAst property is set when a file is uploaded.
 * This is removed when the Blob is link to an object (email, event, contact photo or file in files module.
 * 
 * @example
 * `````````````````````
 * $blob = \GO\Core\Blob\Model\Blob::fromFile(new \IFW\Fs\File(dirname(__DIR__).'/Resources/intermesh-logo.png'));
 * `````````````````````````````
 * 
 * IMPORTANT
 * 
 * When using blobs make sure you define:
 * 1. A restricting foreign key relation to the blob_blob table to prevent data loss
 * 2. A relation to the blob model so the garbage collector will detect usage of the blob. Otherwise it will try to delete it and the foreign key relation will fail.
 *
 * @property string $id SHA1 40-char hash of the binary data,
 * @property int $ownedBy the PK of the user tha thas uploaded this file,
 * @property string $filename fiel name,
 */
class Blob extends Record implements \GO\Core\GarbageCollection\GarbageCollectionInterface {

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
	public $expiresAt;

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
	
	/**
	 * Used by garbage collection
	 * 
	 * @var boolean
	 */
	protected $used;
	
	public $deleted;

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
		return new Everyone();
	}



	/**
	 * Create a new blob from a given file.
	 *
	 * @example 
	 * ````
	 * $blob = \GO\Core\Blob\Model\Blob::fromFile(new \IFW\Fs\File(dirname(__DIR__).'/Resources/intermesh-logo.png'));
	 * ```
	 * @param File $file
	 * @return Blob
	 * @throws Exception
	 */
	static public function fromFile(File $file, \DateTime $expireAt = null) {

		if (!$file->exists()) {
			throw new Exception("Given file does not exist!");
		}

		$blobId = $file->getSha1Hash();
		if (($blob = self::findByPk($blobId))) {
			if($blob->deleted) {
				$blob->deleted = false;
				$blob->save();
			}
			return $blob;
		} else {
			$blob = new self();
		}

		$blob->blobId = $blobId;
		$blob->tmpFile = $file;
		$blob->contentType = $file->getMimeType();
		$blob->size = $file->getSize();
		$blob->name = $file->getName();

		$blob->expiresAt = $expireAt;

		if (!$blob->save()) {
			throw new Exception("Could not save blob!");
		}

		return $blob;
	}

	/**
	 * 
	 * @param string $str the binary data
	 * @param DateTime $expireAt
	 * @return Blob
	 */
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
		return $this->expiresAt < new \DateTime();
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



	/**
	 * By default an uploaded file is valid for 1 hour
	 * @param string $interval time to live
	 */
	public function expire($interval = self::EXPIRE_TIME) {
		$expireDate = new \DateTime();
		$expireDate->add(new DateInterval($interval));
		$this->expiresAt = $expireDate;
		return $this;
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
		if (!isset($this->createdAt)) {
			return null;
		}
		return $this->storagePath() . $this->blobId;
	}
	
	protected function internalDelete($hard) {
		if(!parent::internalDelete($hard)) {
			return false;
		}
		
		if($hard && file_exists($this->getPath()) && !unlink($this->getPath())) {
			return false;
		}
		
		return true;
	}
	
	
	
	public static function collectGarbage() {
		//cleanup expired blobs
		$blobs = self::find(['<=', ['expiresAt' => new \DateTime()]]);
		foreach ($blobs as $blob) {
			$blob->delete();
		}
		
		self::updateBlobUsage();
		
		//soft delete unused blobs
		GO()->getDbConnection()->createCommand()->update(Blob::tableName(), ['deleted' => true], ['used' => false])->execute();
	}
	
	
	private static function updateBlobUsage() {
		GO()->getDbConnection()->createCommand()->update(Blob::tableName(), ['used' => false])->execute();
		
		$users = self::findUsers();
		
		foreach($users as $user) {
			
			$select = (new \IFW\Db\Query)
							->select('*')
							->tableAlias('sub')							
							->from($user['tableName'])
							->withDeleted()
							->where('sub.'.$user['columnName'].' = t.blobId');
			
			GO()->getDbConnection()->createCommand()->update(Blob::tableName(), ['used' => true], ['EXISTS', $select])->execute();
		}
	}
	
	
	private static function findUsers() {
		
		$users = [];
		
		$classFinder = new ClassFinder();
		$classes = $classFinder->findByParent(Record2::class);
		foreach($classes as $recordClass) {
			
			$recordClass::defineRelations(); //for disabled modules
			
			$relations = (array) $recordClass::getRelations();
			
			/* @var $relations Relation  */
			
			foreach($relations as $relation) {
				if($relation->getToRecordName() == Blob::class) {
					
					$keys = array_keys($relation->getKeys());
					$tableName = $recordClass::tableName();
					
					if(\IFW\Db\Utils::tableExists($tableName)) {

						$users[] = [
								'tableName' => $tableName,
								'columnName' => $keys[0],
								'pk' => $recordClass::getPrimaryKey()
										];
						
					}
				
				}
			}
		}
		
		return $users;
	}
	

}
