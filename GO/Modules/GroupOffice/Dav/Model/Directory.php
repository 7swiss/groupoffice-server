<?php

namespace GO\Modules\GroupOffice\Dav\Model;

use Sabre\DAV;
use \GO\Modules\GroupOffice\Files\Model\Node as GoNode;

class Directory extends Node implements DAV\ICollection, DAV\IQuota {

	function createFile($name, $data = null) {
		$file = \GO\Modules\GroupOffice\Files\Model\File::create($data);
		$file->name = $name;
		$file->parentId = $this->node->id;
		$file->save();
	}

	function createDirectory($name) {
		$dir = new GoNode();
		$dir->name = $name;
		$dir->parentId = $this->node->id;
		$dir->save();
	}

	function getChild($name) {
		$node = $this->node->getChild(basename($name));
		if (empty($node)) {
			throw new DAV\Exception\NotFound('Node with name "' . $name . '" could not be located in '.$this->node->path);
		}
		return $node->isDirectory ? new Directory($node->path) : new File($node->path);
	}

	function getChildren() {
		
		$nodes = [];
		$children = $this->node->children;
		foreach ($children as $entry) {
			$nodes[] = $this->getChild($entry->path);
		}
		return $nodes;
	}

	function childExists($name) {
		return !empty($this->node->getChild($name));
	}

	function delete() {
		foreach ($this->getChildren() as $child)
			$child->delete();
		$this->node->delete();
	}

	function getQuotaInfo() {
		$absolute = realpath(\GO()->getConfig()->getDataFolder()->getPath());
		return [
			 disk_total_space($absolute) - disk_free_space($absolute),
			 disk_free_space($absolute)
		];
	}
}
