<?php
/*
 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Michael de Hart <mdhart@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
namespace GO\Modules\GroupOffice\Calendar\Model;

use GO\Core\Blob\Model\Blob;
use GO\Core\Orm\Record;
use IFW\Auth\Permissions\Everyone;
/**
 * An attachment for an event. Many Many to blobs
 *
 */
class EventAttachment extends Record {

	/**
	 * FK of the event this attachment is for
	 * @var int
	 */							
	public $eventId;

	/**
	 * 40char hash of the file and FK for the blob_blob table
	 * @var string
	 */							
	public $blobId;

	/**
	 * 
	 * @var \DateTime
	 */							
	public $recurrenceId;

	use \GO\Core\Blob\Model\BlobNotifierTrait;

	protected static function defineRelations() {
		self::hasOne('blob', Blob::class, ['blobId' => 'blobId']);
	}

	protected static function internalGetPermissions() {
		return new Everyone();
	}

	public function setName($value) {
		//prevent error
	}

	public function getName() {
		return $this->blob->name;
	}

	public function getType() {
		return $this->blob->getType();
	}

	protected function internalSave() {
		if(!$this->isNew() && !empty($this->getOldAttributeValue('blobId'))) {
			$this->freeBlob($this->getOldAttributeValue('blobId'));
		}
		$this->useBlob($this->blobId);

		return parent::internalSave();
	}

	protected function internalDelete($hard) {
		$this->freeBlob($this->blobId);
		return parent::internalDelete($hard);
	}
}
