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

	const InvalidNameRegex = "/[\\~#%&*{}/:<>?|\"-]/";

	const TempFilePatterns = [
		'/^\._(.*)$/',     // OS/X resource forks
		'/^.DS_Store$/',   // OS/X custom folder settings
		'/^desktop.ini$/', // Windows custom folder settings
		'/^Thumbs.db$/',   // Windows thumbnail cache
		'/^.(.*).swp$/',   // ViM temporary files
		'/^\.dat(.*)$/',   // Smultron seems to create these
		'/^~lock.(.*)#$/', // Windows 7 lockfiles
   ];

	/**
	 * auto increment primary key
	 * @var int
	 */							
	public $id;

	/**
	 * name of file or folder
	 * Only the following
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
	 * 
	 * @var string
	 */							
	public $blobId;

	/**
	 * true when this Node is a directory
	 * @var int
	 */							
	public $isDirectory = false;

	/**
	 * FK to owner
	 * @var int
	 */							
	public $ownedBy;

	public $driveId;

	private $isDrive = false;

	/**
	 * 
	 * @var int
	 */							
	protected $parentId;
	/**
	 * ParentID from setRelativePath
	 * Use this as parentId if set
	 * @var int 
	 */
	private $relParentId;

	/**
	 * These directories do not exist and need to be created to save the node
	 * @var Node[]
	 */
	private $createDirectories = [];

	protected function init() {
		$this->ownedBy = GO()->getAuth()->user()->group->id;
	}

	public static function findByPath($path) {
		$dirnames = explode('/', $path);
		$query = (new Query())->where(['parentId'=>null, 'deleted'=>0]);
		$alias = $prevAlias = 't';
		$count=0;
		foreach($dirnames as $i => $dir) {
			$count++;
			$alias = 't'.$i;
			if($count == 1) {
				$parentMatch = $alias.'.parentId IS NULL';
			} else {
				$parentMatch = $alias.'.parentId = '.$prevAlias.'.id';
			}
			$query->join(self::tableName(), $alias, $parentMatch. ' AND '. $alias.'.name = "'.$dir.'" AND '.$alias.'.deleted = 0');
			$prevAlias = $alias;
		}
		$query->select($alias.'.*');
		$store = self::find($query);
		//var_dump($store->getQuery()->createCommand()->toString());
		return $store->single();
	}

	protected static function defineRelations() {

		self::hasOne('nodeUser', NodeUser::class, ['id' => 'nodeId']); //Current user is added in getRelations() override below. This is because relations are cached.
		self::hasMany('children', Node::class, ['id' => 'parentId']);
//		/self::hasMany('rootOf', Drive::class, ['id' => 'directoryId']);
		self::hasOne('drive', Drive::class, ['driveId' => 'id']);
		self::hasOne('parent', Directory::class, ['parentId' => 'id']);//->setQuery((new Query())->where('parentId IS NOT NULL'));
		self::hasOne('blob', Blob::class, ['blobId' => 'blobId']);
		self::hasOne('owner', Group::class, ['ownedBy' => 'id']);
	}

	public static function getRelations() {

		$relations = parent::getRelations();		
		$relations['nodeUser']->setQuery(['userId' => GO()->getAuth()->user()->id]);

		return $relations;
	}

	protected function internalValidate() {
		$this->name = preg_replace(self::InvalidNameRegex, "_", $this->name);

		foreach (self::TempFilePatterns as $tempFile) {
			if (preg_match($tempFile, $this->name)) {
				$this->setValidationError('name', 'Temp file pattern skip this');
			}
		}

		parent::internalValidate();
	}

	protected function internalSave() {

		if ($this->isNew()) {
			if(!empty($this->relParentId)) {
				$this->setParentId($this->relParentId);
			} else {
				$this->setParentId($this->parentId);
			}
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
		$parentId = $this->parentId;
		while ($dirName = array_shift($parts)) {
			$folder = Node::find((new Query)->where(['parentId' => $parentId, 'name' => $dirName]))->single();
			if (empty($folder)) {
				$folder = new Node();
				$folder->isDirectory = true;
				$folder->name = $dirName;
				$folder->setParentId($parentId);
				$folder->save(); // if not saved then it is not found when saving the second uploaded file
			}
			$parentId = $folder->id;
		}
		if(isset($parentId)) {
			$this->relParentId = $parentId;
			$this->parentId = $parentId;
		}
	}

	public function getPath() {

		return GO()->getAuth()->sudo(function() {
			$dir = $this;
			$path = '';
			while ($dir = $dir->parent) {
				$path = $dir->name . '/'. $path;
			}
			return $path . $this->name;
		});
	}

	public function setParentId($id) {
		$this->parentId = $id;
		if($this->isModified('parentId')) {
			$this->driveId = $this->parent->driveId;
		}
		
	}

	public function getParentId() {
		return $this->parentId;
	}

	/**
	 * @param string $name
	 * @return Node
	 */
	public function getChild($name) {
		return Node::find(['parentId'=>$this->id,'name'=>$name])->single();
	}

	public function getSize() {
		return isset($this->blob) ? $this->blob->size : null;
	}

	protected static function internalGetPermissions() {
		return new \IFW\Auth\Permissions\ViaRelation('drive');
	}

	public function getType() {
		if($this->isDrive) {
			return FileType::Drive;
		}
		if (empty($this->blob)) {
			return FileType::Folder;
		}
		
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

}
