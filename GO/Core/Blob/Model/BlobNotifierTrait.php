<?php
/**
 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Michael de Hart <mdhart@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
namespace GO\Core\Blob\Model;

use GO\Core\Blob\Model\Blob;
use GO\Core\Blob\Model\BlobUser;

/**
 * Added this trait to your Record derived class will tell the used blob object
 * That it is being used by your Record instance.
 * Use the freeBlob and useBlob function to make the Trait user use or free a blob:
 * 
 * ``````````````````````````````````````````
 * protected function internalSave() {
		$this->saveBlob('blobId');
		return parent::internalSave();
	}

	protected function internalDelete($hard) {
		$this->freeBlob($this->blobId);
		return parent::internalDelete($hard);
	}
 * ```````````````````````````````````````````````````````````
 */
trait BlobNotifierTrait {

	private function useBlob($id) {
		$blob = Blob::findByPk($id);
		if(!empty($blob)) {
			return $blob->addUser($this->pk(), $this->getRecordType());
		}
	}
	
	private function saveBlob($columnName) {
		if($this->isModified($columnName)) {
			
			if($this->getOldAttributeValue($columnName)){
				$this->freeBlob($this->getOldAttributeValue($columnName));
			}
			$this->useBlob($this->$columnName);
		}
	}

	private function freeBlob($id) {
		
		//I think there's a mistake here. When using softDelete it shouldn't be freed
		return;
		
		$blobUser = BlobUser::find(['blobId'=>$id,'modelPk'=>implode('-', $this->pk()),'modelTypeId'=>$this->getRecordType()->id])->single();
		if(!empty($blobUser)) {
			$success = $blobUser->delete();
			$blob = Blob::findByPk($id);
			if(!$blob->isUsed()) {
				$success = $success && $blob->expire()->save();
			}
			return $success;
		}
	}

}