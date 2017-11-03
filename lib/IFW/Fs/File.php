<?php
namespace IFW\Fs;

use DateTime;
use Exception;
use IFW;
use IFW\Fs\FileSystemObject;
use IFW\Fs\Folder;
use IFW\Util\StringUtil;



/**
 * A file object
 *
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class File extends FileSystemObject {
	

	
	/**
	 * Get the parent folder object
	 *
	 * @return Folder Parent folder object
	 */
	public function getFolder() {
		$parentPath = dirname($this->path);		
		return new Folder($parentPath);
	}
	
	
	/**
	 * Check if the file or folder is writable for the webserver user.
	 *
	 * @return boolean
	 */
	public function isWritable() {
		
		if($this->exists()) {
			return is_writable($this->path);
		}else
		{
			return $this->getFolder()->isWritable();
		}
	}
	
//	/**
//	 * Get the size formatted nicely like 1.5 MB
//	 *
//	 * @param int $decimals
//	 * @return string
//	 */
//	public function getHumanSize($decimals = 1) {
//		$size = $this->getSize();
//		if ($size == 0) {
//			return 0;
//		}
//
//		switch ($size) {
//			case ($size > 1073741824) :
//				$size = \IFW\Util\Number::localize($size / 1073741824, $decimals);
//				$size .= " GB";
//				break;
//
//			case ($size > 1048576) :
//				$size = \IFW\Util\Number::localize($size / 1048576, $decimals);
//				$size .= " MB";
//				break;
//
//			case ($size > 1024) :
//				$size = \IFW\Util\Number::localize($size / 1024, $decimals);
//				$size .= " KB";
//				break;
//
//			default :
//				$size = \IFW\Util\Number::localize($size, $decimals);
//				$size .= " bytes";
//				break;
//		}
//		return $size;
//	}

	/**
	 * Delete the file
	 *
	 * @return boolean
	 */
	public function delete() {
		if (!file_exists($this->path)) {
			return true;
		}else{		
			return unlink($this->path);
		}
	}


	/**
	 * Get the extension of a filename
	 *
	 * @param string $filename
	 * @param string
	 */
	public function getExtension() {
		
		$filename = $this->getName();
		
		$extension = '';

		$pos = strrpos($filename, '.');
		if ($pos) {
			$extension = substr($filename, $pos + 1);
		}
		//return trim(strtolower($extension)); // Does not work when extension on disk is in capital letters (.PDF, .XLSX)
		return trim($extension);
	}

	/**
	 * Get the file name with out extension
	 * @param string
	 */
	public function getNameWithoutExtension() {
		$filename = $this->getName();
		$pos = strrpos($filename, '.');
		
		if ($pos) {
			$filename = substr($filename, 0, $pos);
		}
		
		return $filename;
	}

	/**
	 * Checks if a filename exists and renames it.
	 *
	 * @param	StringUtil $filepath The complete path to the file
	 * @access public
	 * @param string  New filepath
	 */
	public function appendNumberToNameIfExists() {
		$dir = $this->getFolder()->getPath();
		$origName = $this->getNameWithoutExtension();
		$extension = $this->getExtension();
		$x = 1;
		while ($this->exists()) {
			$this->path = $dir . '/' . $origName . ' (' . $x . ').' . $extension;
			$x++;
		}
		return $this->path;
	}

	/**
	 * Put data in the file. (See php function file_put_contents())
	 *
	 * @param string $data
	 * @param type $flags
	 * @param type $context
	 * @return boolean
	 */
	public function putContents($data, $flags = null, $context = null) {
		
		$this->create();
		
		if (file_put_contents($this->path, $data, $flags, $context)) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Get the contents of this file.
	 *
	 * @param string
	 */
	public function getContents() {
		return file_get_contents($this->getPath());
	}

	public function getMimeType() {
		return mime_content_type($this->getPath());
	}

	/**
	 * Returns the mime type for the file.
	 * If it can't detect it it will return application/octet-stream
	 *
	 * @param string
	 */
	public function getContentType() {
		$mimes = new \Mimey\MimeTypes;
		// Convert extension to MIME type:
		return $mimes->getMimeType(strtolower($this->getExtension()));
	}

	/**
	 * Send download headers and output the contents of this file to standard out (browser).
	 * @param boolean $sendHeaders
	 * @param boolean $useCache
	 */
	public function output($sendHeaders = true, $useCache = true) {

		if($sendHeaders) {
			IFW::app()->getResponse()->setHeader('Content-Type', $this->getContentType());
			IFW::app()->getResponse()->setHeader('Content-Disposition', 'inline; filename="' . $this->getName() . '"');
			IFW::app()->getResponse()->setHeader('Content-Transfer-Encoding', 'binary');
			if ($useCache) {			
				IFW::app()->getResponse()->setModifiedAt(new DateTime('@'.$this->getModifiedAt()));
				IFW::app()->getResponse()->setETag($this->getMd5Hash());
				IFW::app()->getResponse()->abortIfCached();
			}		
		}	
		
		if (isset($_SERVER['HTTP_RANGE'])){
			$this->rangeDownload($this->getPath());
			return;
		}

		if(ob_get_contents() != '') {			
			throw new \Exception("Could not output file because output has already been sent. Turn off output buffering to find out where output has been started.");
		}

		$handle = fopen($this->getPath(), "rb");

		if (!is_resource($handle)) {
			throw new Exception("Could not read file");
		}

		while (!feof($handle)) {
			echo fread($handle, 1024);
			flush();
		}
	}
	
	private function rangeDownload($file) {
 
		$fp = @fopen($file, 'rb');

		$size   = filesize($file); // File size
		$length = $size;           // Content length
		$start  = 0;               // Start byte
		$end    = $size - 1;       // End byte
		IFW::app()->getResponse()->setHeader("Accept-Ranges", "0-$length");
		// header('Accept-Ranges: bytes');
		// multipart/byteranges
		// http://www.w3.org/Protocols/rfc2616/rfc2616-sec19.html#sec19.2
		if (isset($_SERVER['HTTP_RANGE'])) {

			$c_start = $start;
			$c_end   = $end;
			// Extract the range string
			list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
			// Make sure the client hasn't sent us a multibyte range
			if (strpos($range, ',') !== false) {

				// (?) Shoud this be issued here, or should the first
				// range be used? Or should the header be ignored and
				// we output the whole content?
				header('HTTP/1.1 416 Requested Range Not Satisfiable');
				IFW::app()->getResponse()->setHeader("Content-Range", "bytes $start-$end/$size");
				// (?) Echo some info to the client?
				exit;
			}
			// If the range starts with an '-' we start from the beginning
			// If not, we forward the file pointer
			// And make sure to get the end byte if spesified
			if ($range{0} == '-') {

				// The n-number of the last bytes is requested
				$c_start = $size - substr($range, 1);
			}
			else {

				$range  = explode('-', $range);
				$c_start = $range[0];
				$c_end   = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $size;
			}
			/* Check the range and make sure it's treated according to the specs.
			 * http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html
			 */
			// End bytes can not be larger than $end.
			$c_end = ($c_end > $end) ? $end : $c_end;
			// Validate the requested range and return an error if it's not correct.
			if ($c_start > $c_end || $c_start > $size - 1 || $c_end >= $size) {

				header('HTTP/1.1 416 Requested Range Not Satisfiable');
				IFW::app()->getResponse()->setHeader("Content-Range,", "bytes $start-$end/$size");
				// (?) Echo some info to the client?
				exit;
			}
			$start  = $c_start;
			$end    = $c_end;
			$length = $end - $start + 1; // Calculate new content length
			fseek($fp, $start);
			header('HTTP/1.1 206 Partial Content');
		}
		// Notify the client the byte range we'll be outputting
		IFW::app()->getResponse()->setHeader("Content-Range", "bytes $start-$end/$size");
		IFW::app()->getResponse()->setHeader("Content-Length", "$length");

		// Start buffered download
		$buffer = 1024 * 8;
		while(!feof($fp) && ($p = ftell($fp)) <= $end) {

			if ($p + $buffer > $end) {

				// In case we're only outputtin a chunk, make sure we don't
				// read past the length
				$buffer = $end - $p + 1;
			}
			set_time_limit(0); // Reset time limit for big files
			echo fread($fp, $buffer);
			flush(); // Free up memory. Otherwise large files will trigger PHP's memory limit.
		}

		fclose($fp);

	}
	
	
	/**
	 * Open file pointer
	 * 
	 * See php fopen function
	 * 
	 * @param string $mode
	 * @return resource
	 */
	public function open($mode){
		
		$this->create();
		
		return fopen($this->getPath(), $mode);
	}

	/**
	 * Move a file to another folder.
	 *
	 * @param File $destination The file may not exist yet.
	 * @return boolean
	 */
	public function move(File $destination) {

		if ($destination->exists()) {
			throw new Exception("File exists in move!");
		}

		if (rename($this->path, $destination->getPath())) {
			$this->path = $destination->getPath();
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Create a hard link
	 * 
	 * @link http://php.net/manual/en/function.link.php
	 * 
	 * @param File $targetLink The link name.
	 * 
	 * @return File|bool <b>File</b> on success or <b>FALSE</b> on failure.
	 */
	public function createLink(File $targetLink) {
		return link($this->getPath(), $targetLink->getPath());
	}

	/**
	 * Copy a file to another folder.
	 *
	 * @param File $destinationFile
	 * @return File
	 */
	public function copy(File $destinationFile) {
		
		if($destinationFile->exists()) {
			throw new \Exception("The destination '".$destinationFile->getPath()."' already exists!");
		}
	
		if (!copy($this->path, $destinationFile->getPath())) {
			return false;
		}else{
			return $destinationFile;
		}
	}

	/**
	 * Convert and clean the file to ensure it has valid UTF-8 data.
	 *
	 * @return boolean
	 */
	public function convertToUtf8() {

		if (!$this->isWritable()){
			return false;
		}

		$str = $this->getContents();
		if (!$str) {
			return false;
		}

		$enc = $this->detectEncoding($str);
		if (!$enc) {
			$enc = 'UTF-8';
		}

		$bom = pack("CCC", 0xef, 0xbb, 0xbf);
		if (0 == strncmp($str, $bom, 3)) {
			//echo "BOM detected - file is UTF-8\n";
			$str = substr($str, 3);
		}

		return $this->putContents(StringUtil::cleanUtf8($str, $enc));
	}

	/**
	 * Get the md5 hash from this file
	 *
	 * @param string
	 */
	public function getMd5Hash() {
		return md5_file($this->path);
	}

	/**
	 * Pull 40-char sha1 hex from the binary data
	 *
	 * @param string
	 */
	public function getSha1Hash() {
		return sha1_file($this->path);
	}

	/**
	 * Compare this file with an other file.
	 *
	 * @param File $file
	 * @return bool True if the file is different, false if file is the same.
	 */
	public function equals(File $file) {
		if ($this->md5Hash() != $file->md5Hash()){
			return true;
		}else{
			return false;
		}
	}

	/**
	 * Create the file
	 *
	 * @param boolean $createPath Create the folders for this file also?
	 * @return self|bool $successfull
	 */
	public function touch($createPath = false) {
		if ($createPath){
			$this->getFolder()->create();
		}

		if (touch($this->getPath())) {
			return $this;
		} else {
			return false;
		}
	}
	
	private function create() {
		if(!$this->exists()) {
			$this->touch(true);
		}
	}
}