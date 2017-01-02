<?php

namespace GO\Core\Email\Model;


class Attachment extends \Swift_Attachment {
	public static function fromBlob($blobId) {
		$blob = \GO\Core\Blob\Model\Blob::findByPk($blobId);
		
		return self::newInstance()->setFile(
            new Swift_ByteStream_FileByteStream($blob->getPath()),
            $blob->contentType
            );
	}
}
