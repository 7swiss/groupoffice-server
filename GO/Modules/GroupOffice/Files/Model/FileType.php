<?php

/*
 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Michael de Hart <mdhart@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */

namespace GO\Modules\GroupOffice\Files\Model;

/* ENUM */

class FileType {

	const __default = self::Other;
	const Other = 0;
	const Document = 1;
	const Image = 2;
	const Video = 3;
	const Audio = 4;
	const Folder = 1000;

	static public $byType = [
		 'text' => self::Document,
		 'image' => self::Image,
		 'video' => self::Video,
		 'audio' => self::Audio,
	];

	static public function fromContentType($contentType) {
		if ($contentType === null) {
			return self::Folder;
		}
		list($type, $format) = explode('/', $contentType);
		return isset(self::$byType[$type]) ? self::$byType[$type] : self::Other;
	}

}
