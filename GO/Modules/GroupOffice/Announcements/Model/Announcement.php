<?php
namespace GO\Modules\GroupOffice\Announcements\Model;

use IFW;
use IFW\Orm\Record;

use IFW\Fs\Folder;
use GO\Core\Users\Model\User;
use IFW\Fs\File;
/**
 * The Anouncement model
 *
 * @property User $owner
 *
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class Announcement extends Record{

	/**
	 * 
	 * @var int
	 */							
	public $id;

	/**
	 * 
	 * @var bool
	 */							
	public $deleted = false;

	/**
	 * 
	 * @var int
	 */							
	public $ownerUserId;

	/**
	 * 
	 * @var \DateTime
	 */							
	public $createdAt;

	/**
	 * 
	 * @var \DateTime
	 */							
	public $modifiedAt;

	/**
	 * 
	 * @var string
	 */							
	public $title;

	/**
	 * 
	 * @var string
	 */							
	public $text;

	/**
	 * 
	 * @var string
	 */							
	public $_photoFilePath;

	public static function defineRelations(){		
		self::hasOne('owner', User::class, ['ownerUserId'=>'id']);		
	}
	
	protected static function internalGetPermissions() {
		return new \IFW\Auth\Permissions\ReadOnly();
	}
	
	/**
	 * Get the folder to store photo's in.
	 *
	 * @return Folder
	 */
	public static function getPhotosFolder(){
		return GO()->getConfig()->getDataFolder()->getFolder('announcementImages')->create();
	}
	
	/**
	 * Get the photo file
	 *
	 * @return File
	 */
	public function getPhotoFile(){
		if(empty($this->_photoFilePath)){			
			return false;
		}else
		{
			return new File(self::getPhotosFolder().'/'.$this->_photoFilePath);
		}
	}
	
	
	public function getPhoto(){		
		if(empty($this->_photoFilePath)) {
			return null;
		}
		$mtime = !isset($this->modifiedAt) ? 'null' : $this->modifiedAt->format('YmdGis');
		//Added modified at so browser will reload when dynamically changed with js
		return GO()->getRouter()->buildUrl("announcements/".intval($this->id)."/thumb", ['modifiedAt' =>  $mtime]); 
	}

	/**
	 * Set a photo
	 */
	public function setPhoto($temporaryImagePath) {

		$photosFolder = self::getPhotosFolder();
		
		$file = new File(GO()->getAuth()->getTempFolder().'/'.$temporaryImagePath);
		
		$destinationFile = $photosFolder->getFile(uniqid().'.'.$file->getExtension());
		$destinationFile->delete();

		$file->move($destinationFile);
		$this->_photoFilePath = $file->getRelativePath($photosFolder);

	}
	
	public function internalSave() {

		if($this->isModified('_photoFilePath') && $this->_photoFilePath==""){
			//remove photo file after save
			$photoFile = $this->getPhotoFile();
		}

		if(!parent::internalSave()){			
			return false;
		}

		if(isset($photoFile) && $photoFile->exists()){
			$photoFile->delete();
		}
		return true;
	}
}