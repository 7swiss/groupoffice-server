<?php

namespace GO\Modules\GroupOffice\Dav\Model;

use Sabre\DAV;

class File extends Node implements DAV\IFile {

   function put($data) {
		$blob = \GO\Core\Blob\Model\Blob::fromString($data);
		$blob->save();
		$this->node->blobId = $blob->blobId;
		$this->node->save();
   }

   function get() {
      return fopen($this->node->blob->getPath(), 'r');
   }

   function delete() {
      $this->node->delete();
   }

   function getSize() {
		if($this->node->blobId) {
			 return $this->node->blob->size;
		}
      return null;
   }

   function getETag() {
		if($this->node->blobId) {
			return '"' . $this->node->blobId . '"';
		}
      return '"F' . $this->node->modfiedAt . '"';
   }

   function getContentType() {
		if($this->node->blobId) {
        return $this->node->blob->contentType;
		}
		return null;
   }

}