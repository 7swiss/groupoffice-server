<?php

namespace GO\Modules\GroupOffice\Dav\Model;

use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\INode;
use Sabre\DAV;
use Sabre\Uri;
use GO\Modules\GroupOffice\Files\Model\Node as NodeRecord;

/**
 * Base node-class
 */
abstract class Node implements INode {

	/**
	 * @var string
	 */
	protected $path;

	/**
	 * @var GoNode the record from the database
	 */
	protected $node;

	function __construct($pathOrNode) {
		if($pathOrNode instanceof NodeRecord) {
			$this->path = $pathOrNode->path;
			$this->node = $pathOrNode;
		} else {
			$this->path = $pathOrNode;
			$this->node = NodeRecord::findByPath($pathOrNode);
		}
		if (empty($this->node)) {
			throw new DAV\Exception\NotFound('File with path "' . $this->path . '" could not be located');
		}
	}

	function getName() {
		return $this->node->name;
	}

	function setName($name) {
		$this->node->name = $name;
		$this->node->save();
		$this->path = $this->node->path;
	}

	function getLastModified() {
		if(!empty($this->node)) {
			return $this->node->modifiedAt->getTimestamp();
		}
		return null;
	}

}
