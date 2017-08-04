<?php
/**
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Michael de Hart <mdhart@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
namespace GO\Core\Blob\Controller;

use GO\Core\Blob\Model\Blob;
use GO\Core\Blob\Model\TransportUtil;
use GO\Core\Controller;
use IFW\Auth\Exception\LoginRequired;
use IFW\Exception\NotFound;
use IFW\Util\Image;

/**
 * The controller that handles file up and download
 */
class BlobController extends Controller {
	
	public function checkAccess() {
		
		if(!GO()->getAuth()->isLoggedIn())
		{
			throw new LoginRequired();
		}
		
		return true;
	}

	public function download($id) {
		
		$blob = Blob::findByPk($id);
		if(!$blob) {
			throw new NotFound();
		}

		TransportUtil::download($blob);
	}

	public function upload() {
		$blob = TransportUtil::upload();
		$this->renderModel($blob);
	}

	/**
	 * TODO cache the created thumbnail
	 * @param int $id the blob id
	 * @param int $w width of the image
	 * @param int $h height of the image
	 */
	public function thumb($id, $w, $h, $zoomCrop = 1) {

		$blob = Blob::findByPk($id);
		if($blob->getType() != Blob::IMAGE) {
			echo 'Error: trying to create thumbnail of none image';
			return;
		}
		$image = new Image($blob->getPath());
		if($zoomCrop) {
			$image->zoomcrop($h, $w);
		}else
		{
			$image->fitBox($w, $h);
		}

		GO()->getResponse()->setHeader('Content-Type', $blob->contentType);
		GO()->getResponse()->setHeader('Content-Disposition', 'inline; filename="' . $blob->name . '"');
		GO()->getResponse()->setHeader('Content-Transfer-Encoding', 'binary');
		$image->output();
	}


	public function test() {
		
		GO()->getDbConnection()->createCommand()->update(Blob::tableName(), ['used' => false])->execute();
		
		$users = Blob::findUsers();
		
		foreach($users as $user) {
			
			$select = (new \IFW\Db\Query)
							->select('*')
							->tableAlias('sub')							
							->from($user['tableName'])
							->withDeleted()
							->where('sub.'.$user['columnName'].' = t.blobId');
			
			GO()->getDbConnection()->createCommand()->update(Blob::tableName(), ['used' => true], ['EXISTS', $select])->execute();
		}
	}
}
