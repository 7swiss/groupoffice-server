<?php

namespace GO\Modules\GroupOffice\Dav\Model;

use Sabre\DAV;

class File extends Node implements DAV\IFile {

    function put($data) {
		$blob = \GO\Core\Blob\Model\Blob::fromString($data);
		$blob->save();
		$this->node->blobId = $blob->id;
		$this->node->save();
    }

    function get() {
        return fopen($this->node->blob->getPath(), 'r');
    }

    function delete() {
       $this->node->delete();
    }

    function getSize() {
        return $this->node->blob->size;
    }

    function getETag() {
        return '"' . $this->node->blobId . '"';
    }

    function getContentType() {
        return $this->node->blob->contentType;
    }

}