<?php
namespace IFW\Modules;

use Exception;
use IFW\Cli\Router as CliRouter;
use IFW\Data\Model;
use IFW\Fs\File;
use IFW\Web\Router as HttpRouter;
use ReflectionClass;

/**
 * Module class
 * 
 * This class manages the module. It controls the installation, upgrades and
 * routes are defined for it.
 * 
 * Eg:
 * 
 * ```````````````````````````````````````````````````````````````````````````
 * <?php
 * namespace GO\Modules\Bands;
 *
 * use GO\Core\Modules\Modules\Model\InstallableModule;
 * use GO\Modules\Bands\Controller\BandController;
 * use GO\Modules\Bands\Controller\HelloController;
 *
 * class BandsModule extends InstallableModule {
 * 
 *	const PERMISSION_CREATE = 1; //Use 1 as 0 is already defined in the module.
 *	
 *	public static function defineWebRoutes(Router $router) {
 *		
 *		$router->addRoutesFor(BandController::class)
 *				->get('bands', 'store')
 *				->get('bands/0','new')
 *				->get('bands/:bandId','read')
 *				->put('bands/:bandId', 'update')
 *				->post('bands', 'create')
 *				->delete('bands/:bandId','delete');
 *		
 *		$router->addRoutesFor(HelloController::class)
 *				->get('bands/hello', 'name');
 *	}
 * }
 *
 * ```````````````````````````````````````````````````````````````````````````
 * 
 * 
 * @copyright (c) 2015, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
abstract class Module extends Model implements ModuleInterface {
	
	/**
	 * Override this function to add routes to the HTTP router
	 * @param HttpRouter $router
	 */
	public static function defineWebRoutes(HttpRouter $router) {
		
	}
	
	/**
	 * Override this function to add routes to the HTTP router
	 * @param CliRouter $router
	 */
	public static function defineCliRoutes(CliRouter $router) {
		
	}
	
	/**
	 * Get the filesystem path of this module
	 *  
	 * @return string
	 */
	public function getPath() {
		$r = new ReflectionClass($this);

		return dirname($r->getFileName());
	}	
		
	
	/**
	 * Get the data from the module information XML file of this installable module.
	 * EG. ../Modules/Contacts/Module.xml
	 * 
	 * @return array
	 * @throws Exception
	 */
	public function getModuleInformation(){
		
		$moduleInfoFile = new File($this->getPath().'/Module.xml');
		
		if(!$moduleInfoFile->exists()){
			throw new Exception("Module information file not found: \n\n" . $moduleInfoFile->getPath());
		}
		
		$xml = simplexml_load_string($moduleInfoFile->getContents());
		$json = json_encode($xml);
		
		// Check if the post is filled with an array. Otherwise make it an empty array.
		$info = !empty($json) ? json_decode($json, true) : [];

		if (!is_array($info)) {
			throw new Exception("Malformed XML posted: \n\n" . var_export($info, true));
		}
		
		// The following functionality removes the '@attributes' parts from the array.
		// If the '@attributes' array includes a "language" attribute, then use 
		// that language as key for the 'information' array.
		if(isset($info['languages']) && is_array($info['languages']) && array_key_exists('language',$info['languages'])){
			
			$languageCollection = $info['languages']['language'];
			$count = count($languageCollection);
			
			if($count > 0){
				for($i=0; $i<$count; $i++){
					if(array_key_exists($i, $languageCollection)){
						$language = $languageCollection[$i];
						if(isset($language['@attributes']) && array_key_exists('type', $language['@attributes'])){
							$l = $language['@attributes']['type'];
							unset($languageCollection[$i]['@attributes']);
							$languageCollection[$l] = $languageCollection[$i];
							unset($languageCollection[$i]);
							
							if(isset($languageCollection[$l]['images']) && is_array($languageCollection[$l]['images']) && array_key_exists('image',$languageCollection[$l]['images'])){
							
								$imageCollection = $languageCollection[$l]['images']['image'];
								$imagecount = count($imageCollection);
								
								if($imagecount > 0){
									
									for($j=0; $j<$imagecount; $j++){
										
										if(isset($imageCollection[$j]['@attributes'])){
											
											foreach($imageCollection[$j]['@attributes'] as $key => $value){
												$imageCollection[$j][$key] = $value;
											}
											unset($imageCollection[$j]['@attributes']);
											
										}										
									}
									unset($info['images']['image']);
									$languageCollection[$l]['images'] = $imageCollection;
								}
							}
						}
					}
				}
				unset($info['languages']['language']);
				$info['languages'] = $languageCollection;
			}
		}
		
		return $info;
	}
}
