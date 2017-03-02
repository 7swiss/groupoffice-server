<?php

/*
 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Michael de Hart <mdhart@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */

namespace GO\Modules\GroupOffice\Files\Model;

use IFW\Util\DateTime;
use GO\Core\Blob\Model\Blob;
use GO\Core\Orm\Record;
use GO\Core\Users\Model\Group;
use IFW\Orm\Query;
use GO;

/**
 * A Node can be a File or a Folder
 * The time depends on the object it is attached to
 *
 * @property DateTime $modfiedAt time of last edit
 * @property string[] $path parent directory stack (parent fist, root last)
 */
class Node extends Record {

	/**
	 * auto increment primary key
	 * @var int
	 */							
	public $id;

	/**
	 * name of file or folder
	 * @var string
	 */							
	public $name;

	/**
	 * time of creation
	 * @var \DateTime
	 */							
	public $createdAt;

	/**
	 * 
	 * @var \DateTime
	 */							
	public $modifiedAt;

	/**
	 * Older versions will be kept until this date
	 * @var \DateTime
	 */							
	public $versionUntil;

	/**
	 * extra variables for the file like Document Author, ID3 and EXIF data @see MatadataParser
	 * @var string
	 */							
	public $metaData;

	/**
	 * soft delete
	 * @var bool
	 */							
	public $deleted = false;

	/**
	 * FK to the storage object
	 * @var int
	 */							
	public $storageId;

	/**
	 * 
	 * @var string
	 */							
	public $blobId;

	/**
	 * true when this Node is a directory
	 * @var int
	 */							
	public $isDirectory = 0;

	/**
	 * FK to owner
	 * @var int
	 */							
	public $ownedBy;

	/**
	 * 
	 * @var int
	 */							
	public $parentId;

	/**
	 * These directories do not exist and need to be created to save the node
	 * @var Node[]
	 */
	private $createDirectories = [];

	protected function init() {
		$this->ownedBy = GO()->getAuth()->user()->group->id;
		$this->storageId = 1; //TODO: multiple storage providers
	}

	protected static function defineRelations() {

		self::hasMany('groups', NodeGroup::class, ['id' => 'nodeId']);
		self::hasOne('nodeUser', NodeUser::class, ['id' => 'nodeId']); //Current user is added in getRelations() override below. This is because relations are cached.
		self::hasMany('children', Node::class, ['id' => 'parentid']);
		self::hasOne('parent', Node::class, ['parentId' => 'id']);
		self::hasOne('storage', Storage::class, ['storageId' => 'id']);
		self::hasOne('blob', Blob::class, ['blobId' => 'blobId']);
		self::hasOne('owner', Group::class, ['ownedBy' => 'id']);
	}

	public static function getRelations() {

		$relations = parent::getRelations();		
		$relations['nodeUser']->setQuery(['userId' => GO()->getAuth()->user()->id]);

		return $relations;
	}

	protected static function internalGetPermissions() {
		return new \GO\Core\Auth\Permissions\Model\GroupPermissions(NodeGroup::class);
	}

	protected function internalSave() {

		if ($this->isNew()) {
			$nodeUser = new NodeUser();
			$nodeUser->userId = \GO()->getAuth()->user()->id;
			$this->nodeUser = $nodeUser;
		}
		$this->nodeUser->touchedAt = new \DateTime();

		return parent::internalSave();
	}

	/**
	 * Will create folder stucture from relative path if it doesn't exist
	 */
	public function setRelativePath($path) {
		$parts = explode('/', $path);
		array_pop($parts);
		$parentId = isset(\IFW::app()->getRequest()->body['data']['parentId']) ? \IFW::app()->getRequest()->body['data']['parentId'] : null;
		while ($dirName = array_shift($parts)) {
			$folder = Node::find((new Query)->where(['parentId' => $parentId, 'name' => $dirName]))->single();
			if (empty($folder)) {
				$folder = new Node();
				$folder->isDirectory = true;
				$folder->name = $dirName;
				$folder->parentId = $parentId;
				$folder->save(); // if not saved then it is not found when saving the second uploaded file
			}
			$parentId = $folder->id;
		}
		$this->parentId = $parentId;
	}

	public function getPath() {
		$dir = $this;
		$path = '';
		while ($dir = $dir->parent) {
			$path .= $dir->name . '/';
		}
		return $path . $this->name;
	}

	public function getSize() {
		return isset($this->blob) ? $this->blob->size : null;
	}

	public function getType() {
		if (empty($this->blob))
			return FileType::Folder;
		return FileType::fromContentType($this->blob->contentType);
	}

	public function move($to) {
		$this->parentId = $to->id;
		return $this;
	}

	public function copy($to) {
		$node = new Node();
		$node->setValues($this->toArray());
		$node->parentId = $to->id;
		return $node;
	}

	public function share($groupId, $canRead = true, $canWrite = false) {
		$nodeAccess = new NodeAccess();
		$nodeAccess->groupId = $groupId;
		$nodeAccess->canRead = $canRead;
		$nodeAccess->canWrite = $canWrite;
		return $nodeAccess->save();
	}

}
