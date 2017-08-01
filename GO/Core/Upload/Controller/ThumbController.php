<?php

namespace GO\Core\Upload\Controller;

use GO\Core\Controller;
use IFW\Exception\Forbidden;
use IFW\Exception\NotFound;
use IFW\Fs\File;
use IFW\Util\Image;
use IFW\Util\StringUtil;

/**
 * Trait to implement a thumbnail controller action.
 * 
 * <p>For example put this code in your controller:</p>
 * 
 * `````````````````````````````````````````````````````````````````````````````
 * protected function thumbGetFile() {
 *		$thread = !empty(\GO()->getRouter()->routeParams['threadId']) ? Thread::findByPk(\GO()->getRouter()->routeParams['threadId']) : false;		
 *
 *		if (!$thread) {
 *			$thread = new Thread();
 *		}		
 *		return $thread->photoFile();
 *	}
 * `````````````````````````````````````````````````````````````````````````````
 * 
 * The router definition:
 * `````````````````````````````````````````````````````````````````````````````
 * $router->addRoutesFor(Controller\ThumbController::class)
 *						->get('messages/thread/:threadId/photo', "download");
 * `````````````````````````````````````````````````````````````````````````````
 * 
 * @see FlowController
 *
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
abstract class ThumbController extends Controller {
	
public function __construct() {
		\GO\Core\Auth\Model\Token::$allowCookie = true;
		parent::__construct();
	}
	/**
	 * Get the relative folder the image should be fetched from.
	 * Be careful, images in this folder are available to anyone that can access this controller.
	 * 
	 * @return File
	 */
	abstract protected function thumbGetFile();
	
	
	/**
	 * Return true if you want to enable thumbnail caching. This is recommended.
	 * 
	 * @return bool
	 */
	abstract protected function thumbUseCache();


	/**
	 * Image thumbnailer.
	 *
	 * @param string $src Relative path to the image from folder returned in $this->thumbGetFolder();
	 * @param int $w
	 * @param int $h
	 * @param bool $zoomCrop
	 * @param bool $fitBox
	 */
	public function download($w = 0, $h = 0, $zoomCrop = false, $fitBox = false) {

		try{
			$file = $this->thumbGetFile();
			
			if(!$file){
				Throw new NotFound();
			}
			
		}catch(Forbidden $e){
			GO()->getResponse()->redirect('https://www.placehold.it/'.$w.'x'.$h.'/EFEFEF/AAAAAA&text=Forbidden');
		}
		
		$useCache = $this->thumbUseCache();
		
		if(($w == 0 && $h == 0) || $file->getExtension() == 'svg'){
			
			//output original
//			$this->thumbHeaders(false, $file);
			$file->output(true, $useCache);
			exit();
		}
		
		
		if (!$file || !$file->exists()) {			
			GO()->getResponse()->redirect('https://www.placehold.it/'.$w.'x'.$h.'/EFEFEF/AAAAAA&text=No+image');
		}

		if ($file->getSize() > 4 * 1024 * 1024) {
			GO()->getResponse()->redirect('https://www.placehold.it/'.$w.'x'.$h.'/EFEFEF/AAAAAA&text=Image+too+large');
		}

		if ($useCache) {
			
			$cacheDir = GO()->getConfig()->getTempFolder()->getFolder('thumbcache')->create();			
			$cacheFilename = str_replace(array('/', '\\'), '_', $file->getFolder()->getPath() . '_' . $w . '_' . $h);
			if ($zoomCrop) {
				$cacheFilename .= '_zc';
			}

			if ($fitBox) {
				$cacheFilename .= '_fb';
			}
			$cacheFilename .= urlencode($file->getName());
			
			$cachedThumb = $cacheDir->getFile($cacheFilename);
			
			if($cachedThumb->exists() && $cachedThumb->getModifiedAt() > $file->getModifiedAt()) {
				
				$cachedThumb->output(true, $useCache);				
				return;
			}
		}
		
	
		$image = new Image($file->getPath());
		if (!$image) {
			GO()->getResponse()->redirect('https://www.placehold.it/100x100/EFEFEF/AAAAAA&text=Could+not+load+image');
		} else {
			if ($zoomCrop) {
				$success = $image->zoomcrop($w, $h);
			} else if ($fitBox) {
				$success = $image->fitBox($w, $h);
			} elseif ($w && $h) {
				$success = $image->resize($w, $h);
			} elseif ($w) {
				$success = $image->resizeToWidth($w);
			} else {
				$success = $image->resizeToHeight($h);
			}

			if (!$success) {
				GO()->getResponse()->redirect('https://www.placehold.it/' + $image->getWidth() + 'x' + $image->getHeight() + '/EFEFEF/AAAAAA&text=Could+not+resize+image');
			}

			if ($useCache) {

				$success = $image->save($cachedThumb->getPath());

				if (!$success) {
					GO()->getResponse()->redirect('https://www.placehold.it/' + $image->getWidth() + 'x' + $image->getHeight() + '/EFEFEF/AAAAAA&text=Could+not+resize+image');
				}

				$cachedThumb->output(true, $useCache);
			} else {
				
//				$file->output();
				
				GO()->getResponse()->setHeader('Content-Type', $file->getContentType());
				GO()->getResponse()->setHeader('Content-Disposition', 'inline; filename="' . $file->getName() . '"');
				GO()->getResponse()->setHeader('Content-Transfer-Encoding', 'binary');
				$image->output();
			}
		}
	
	}

	
}
