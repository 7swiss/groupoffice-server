<?php
namespace IFW\Fs;

/**
 * A file which only exists in memory. Just a variable with data.
 * 
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class MemoryFile extends File{
	
	private $_data;
	
	public function __construct($filename, $data) {
		
		$this->_data = $data;
		
		
		return parent::__construct($filename);
	}
	
	public function getContents() {
		return $this->_data;
	}
	public function contents() {
		return $this->_data;
	}
	
	public function putContents($data, $flags = null, $context = null) {
		if($flags = FILE_APPEND){
			$this->_data .= $data;
		}else
		{
			$this->_data = $data;
		}
	}
	
	public function getSize() {
		return strlen($this->_data);
	}
	
	public function getModifiedAt() {
		return time();
	}
	
	public function getCreatedAt() {
		return time();
	}
	
	public function exists() {
		return true;
	}
	
	public function move(File $destination) {
					
		throw Exception("move not implemented for memory file");
	}
	
	public function copy(File $destinationFile) {
		throw Exception("copy not implemented for memory file");
	}
	public function getParent() {
		return false;
	}
	
	public function getChild($filename) {
		throw Exception("child not possible for memory file");
	}
	
	public function createChild($filename, $isFile = true) {
		throw Exception("createChild not possible for memory file");
	}
	
	
		/**
	 * Check if the file or folder is writable for the webserver user.
	 * 
	 * @return boolean 
	 */
	public function isWritable(){
		return true;
	}
		
	/**
	 * Change owner
	 * @param string $user
	 * @return boolean 
	 */
	public function chown($user){
		return false;
	}
	
	/**
	 * Change group
	 * 
	 * @param string $group
	 * @return boolean 
	 */
	public function chgrp($group){
		return false;
	}
	
	/**
	 *
	 * @param int $permissionsMode <p>
	 * Note that mode is not automatically
	 * assumed to be an octal value, so strings (such as "g+w") will
	 * not work properly. To ensure the expected operation,
	 * you need to prefix mode with a zero (0):
	 * </p>
	 * 
	 * @return boolean 
	 */
	public function chmod($permissionsMode){
		return false;
	}
	
	/**
	 * Delete the file
	 * 
	 * @return boolean 
	 */
	public function delete(){
		return false;
	}
	
	public function isFolder() {
		return false;
	}
	
	/**
	 * Check if this object is a file.
	 * 
	 * @return boolean 
	 */
	public function isFile(){
		return true;
	}
	
	public function setName($name){
		$this->path=$name;
	}
	
	public function appendNumberToNameIfExists() {
		return $this->path;
	}
	
	public function output($sendHeaders = true, $useCache = true) {
		echo $this->_data;
	}
	
	public function setDefaultPermissions() {
		
	}
	
	public function md5Hash(){
		return md5($this->_data);
	}
}