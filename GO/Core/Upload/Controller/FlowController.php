<?php

namespace GO\Core\Upload\Controller;

use Flow\Basic;
use Flow\Config;
use Flow\Request;
use IFW;
use GO\Core\Controller;

/**
 * The controller that handles file uploads and can thumbnail the temporary files.
 *
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class FlowController extends Controller {	
	public function upload(){
		
		if(isset($_POST['flowFilename'])){
			return $this->_flowUpload();
		}else
		{
			return $this->_simpleUpload();
		}		
	}
	
	private function _simpleUpload() {
		//var_dump($_FILES);
		
		if(!isset($_FILES['file'])) {
			throw new \IFW\Exception\HttpException(400, "'file' data missing");
		}
		
		$file = $_FILES['file'];
		
		$finalFile = GO()->getAuth()->getTempFolder()->getFile($file['name']);
		
		$response['success'] = move_uploaded_file($file['tmp_name'], $finalFile->getPath());
		
		if($response['success']) {
			$response['file'] = $finalFile->getRelativePath(GO()->getAuth()->getTempFolder());
			$response['url']=\GO()->getRouter()->buildUrl('upload/thumb/'.urlencode($response['file']))->toArray();
		}
		
		$this->render($response);
	}
	
	private function _flowUpload() {
		$chunksTempFolder = GO()->getAuth()->getTempFolder()->getFolder('uploadChunks')->create();

		$request = new Request();

		$finalFile = GO()->getAuth()->getTempFolder()->getFile($request->getFileName());
		
		$config = new Config();
		$config->setTempDir($chunksTempFolder->getPath());

		if (Basic::save($finalFile->getPath(), $config)) {
			// file saved successfully and can be accessed at './final_file_destination'

			$file = $finalFile->getRelativePath(GO()->getAuth()->getTempFolder());
			$this->render(array(
					'success' => true,
					'file' => $file,
					'url' => \GO()->getRouter()->buildUrl('upload/thumb/'.urlencode($file))
			));
		} else {
			// This is not a final chunk or request is invalid, continue to upload.
			$this->render(array(
					'success' => true
			));
		}
	}
}