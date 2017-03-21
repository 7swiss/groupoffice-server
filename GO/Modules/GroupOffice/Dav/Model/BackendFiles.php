<?php

/**
 * Copyright Intermesh
 *
 * This file is part of Group-Office. You should have received a copy of the
 * Group-Office license along with Group-Office. See the file /LICENSE.TXT
 *
 * If you have questions write an e-mail to info@intermesh.nl
 *
 * @version $Id: Shared_Directory.class.inc.php 7752 2011-07-26 13:48:43Z mschering $
 * @copyright Copyright Intermesh
 * @author Merijn Schering <mschering@intermesh.nl>
 */

namespace GO\Modules\GroupOffice\Dav\Model;

use GO;
use Sabre;
use GO\Modules\GroupOffice\Files\Model\Node as NodeRecord;

class BackendFiles extends Directory {

	const rootDirs = ['Mine','Shared','Starred','Recent'];

	public function __construct($path) {
		$this->path = $path;
		$this->node = new NodeRecord();
		$this->node->name = $path;
		if ($path == '/') {
			$this->node->name = 'storage';
		}
	}

	function getChildren() {
		$nodes = [];
		if($this->path == '/') {
			foreach (self::rootDirs as $dir) {
				$nodes[] = new self($dir);
			}
			return $nodes;
		}

		$children = [];
		$query = (new \IFW\Orm\Query)
				  ->joinRelation('blob', true, 'LEFT') // folder has no size
				  ->joinRelation('nodeUser', 'starred', 'LEFT')
				  ->joinRelation('owner', 'name');

		if(in_array($this->path, self::rootDirs)) {
			$children = call_user_func(array($this, 'get'.$this->path), $query);
		} else {
			$nodes = parent::getChildren();
		}
		
		foreach ($children as $entry) {
			$nodes[] = $this->getChild($entry->path);
		}
		
		return $nodes;
	}

	private function getMine($query) {
		return NodeRecord::find($query->where(['parentId' => null])->andWhere('ownedBy = '.\GO()->getAuth()->user()->group->id));
	}

	private function getShared($query) {
		return NodeRecord::find($query->andWhere('ownedBy != '.\GO()->getAuth()->user()->group->id));
	}

	private function getStarred($query) {
		return NodeRecord::find($query->andWhere('nodeUser.starred = 1'));
	}

	private function getRecent($query) {
		return NodeRecord::find($query
					  ->orderBy(['isDirectory' => 'DESC', 'nodeUser.touchedAt' => 'ASC'])
					  ->andWhere('nodeUser.touchedAt IS NOT NULL')
				  );
	}

	public function getChild($name) {
		if(in_array($name, self::rootDirs)) {
			return new self($name);
		}
		return parent::getChild($name);
		throw new Sabre\DAV\Exception\NotFound("$name not found in the root");
	}

	/**
	 * Creates a new file in the directory
	 *
	 * data is a readable stream resource
	 *
	 * @param StringHelper $name Name of the file
	 * @param resource $data Initial payload
	 * @return void
	 */
	public function createFile($name, $data = null) {
		throw new Sabre\DAV\Exception\Forbidden();
	}

	/**
	 * Creates a new subdirectory
	 *
	 * @param StringHelper $name
	 * @return void
	 */
	public function createDirectory($name) {
		throw new Sabre\DAV\Exception\Forbidden();
	}

	/**
	 * Deletes all files in this directory, and then itself
	 *
	 * @return void
	 */
	public function delete() {
		throw new Sabre\DAV\Exception\Forbidden();
	}

	/**
	 * Returns the last modification time, as a unix timestamp
	 *
	 * @return int
	 */
	public function getLastModified() {
		$absolute = realpath(\GO()->getConfig()->getDataFolder()->getPath());
		return filemtime($absolute);
	}

}
