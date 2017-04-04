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
class Drive extends Record {

	/**
	 * PK
	 * @var int
	 */							
	public $id;

	/**
	 * PK
	 * @var string
	 */							
	protected $name;

	/**
	 * 
	 * @var int
	 */							
	public $quota = 1024 * 1024 * 1024; // 1GB

	/**
	 * 
	 * @var int
	 */							
	public $usage = 0;
	
	public $ownedBy;

	private $isMounted = false;

	public $quotaUnit;

	/**
	 * the root directory for this drive
	 * @var int FK to files_node
	 */
	public $rootId;

	protected static function defineRelations() {
		//TODO: join events to this and select by timespan
		self::hasOne('owner', \GO\Core\Users\Model\Group::class, ['ownedBy'=>'id']);
		self::hasMany('groups', DriveGroup::class, ['id' => 'driveId']);
		self::hasOne('root', Directory::class, ['rootId' => 'id']);
		self::hasMany('mounts', Mount::class, ['id' => 'driveId']);
	}

	protected static function internalGetPermissions() {
		return new \GO\Core\Auth\Permissions\Model\GroupPermissions(DriveGroup::class);
	}

	public function getName() {
		return !empty($this->forUserId) ? $this->user->name : $this->name;
	}
	public function setName($val) {
		$this->name = $val;
	}

	public function getIsMounted() {
		return $this->isMounted;
	}

	static function home() {
		$groupId = GO()->getAuth()->user()->group->id;
		$drive = Drive::find(['ownedBy' => $groupId])->single();
		if(empty($drive)) {
			$drive = new Drive();
			$drive->name = GO()->getAuth()->user()->group->name;
			$drive->ownedBy = $groupId;
			if(!$drive->save()) {
				throw new \Exception('Could not create home drive');
			}
		}
		return $drive;
	}

	public function internalSave() {
		
		$success = parent::internalSave();

		if(empty($this->rootId)) {
			$this->createRootFolder();
		}

		return $success;
	}

	public function createRootFolder() {
		if(empty($this->rootId)) {
			$dir = new Directory();
			$dir->name = $this->name;
			//$dir->parentId = Directory::RootID;
			$dir->driveId = $this->id;
			if($dir->save()) {
				$this->rootId = $dir->id;
				$this->save();
			}
		}
	}

	public function getRoot() {

		if(!empty($this->rootId)) {
			$dir = Directory::findByPk($this->rootId);
		}
		if(!empty($dir)) {
			return $dir;
		}
	}

	public function mount($userId = null) {
		if($userId === null) {
			$userId = GO()->getAuth()->user()->id;
		}

		$mount = new Mount();
		$mount->driveId = $this->id;
		$mount->userId = $userId;
		$this->mounts[] = $mount;
		return $this;
	}

	public function unmount($userId = null) {
		if($userId === null) {
			$userId = GO()->getAuth()->user()->id;
		}
		$mount = Mount::find(['driveId'=>$this->id, 'userId'=>$userId])->single();
		if(empty($mount)) {
			return true;
		}
		return $mount->delete();
	}


}
