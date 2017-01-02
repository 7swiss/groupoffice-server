<?php
/**
 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Michael de Hart <mdhart@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
namespace GO\Core\Blob\Model;

use Flow\Basic;
use Flow\Config;
use Flow\Request;
use IFW\Exception\HttpException;
use IFW\Fs\File;

/**
 * This class contains helper functions for transporting blob
 */
class TransportUtil {

	/**
	 * Upload file and return blob record object
	 */
	static public function upload() {

		if(isset($_POST['flowFilename'])){
			return self::chunkedUpload();
		}
		return self::normalUpload();
	}

	/**
	 * Set all the header en output the file
	 * @param Blob $blobId
	 */
	static public function download(Blob $blob){

		
		self::setDownloadHeaders($blob);

		if ($fd = fopen ($blob->getPath(), "r")) {
			while(!feof($fd)) {
				$buffer = fread($fd, 2048);
				echo $buffer;
			}
			fclose ($fd);
		}
	}

	static private function normalUpload() {
		$blob = new Blob();
		//@TODO: fix
		if(!isset($_FILES['file'])) {
			throw new HttpException(400, "'file' data missing");
		}
		$file = $_FILES['file'];
		$finalFile = GO()->getAuth()->getTempFolder()->getFile($file['name']);

		if(move_uploaded_file($file['tmp_name'], $finalFile->getPath())){
			//$blob->name = $finalFile->getRelativePath(GO()->getAuth()->getTempFolder());
			$blob = Blob::fromFile($finalFile);
		}
		return $blob;
	}

	/**
	 * TODO: test chuked upload
	 *
	 * @return \GO\Core\Blob\Model\Blob
	 */
	static private function chunkedUpload() {
		$blob = new Blob();
		$blob->inProgress = true;
		$request = new Request();
		$finalFile = GO()->getAuth()->getTempFolder()->getFile($request->getFileName());

		$config = new Config();
		$chunksTempFolder = GO()->getAuth()->getTempFolder()->getFolder('uploadChunks')->create();
		$config->setTempDir($chunksTempFolder->getPath());

		if (Basic::save($finalFile->getPath(), $config)) {
			// file saved successfully and can be accessed at './final_file_destination'
			//$blob->name = $finalFile->getRelativePath(GO()->getAuth()->getTempFolder());
			$blob = Blob::fromFile($finalFile);
			$blob->inProgress = false;
		}
		return $blob;
	}

	static private function setDownloadHeaders(Blob $blob) {
		ignore_user_abort(true);
		set_time_limit(0);
		header("Content-type: $blob->contentType");
		header("Content-Disposition: filename=\"".$blob->name."\"");
		header("Content-length: $blob->size");
	}

//	private function check() {
//		file_uploads = On
//		upload_max_filesize = 2M
//		post_max_size = 20M
//
//		max_execution_time = 30
//		max_input_time = 60
//		max_input_nesting_level = 64
//		memory_limit = 128M
//	}
}
