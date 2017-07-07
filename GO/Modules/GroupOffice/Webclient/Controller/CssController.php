<?php
namespace GO\Modules\GroupOffice\Webclient\Controller;

use GO\Core\Controller;
use GO\Modules\GroupOffice\Webclient\Model\Settings;
use IFW\Orm\Query;
use IFW\Exception\NotFound;

/**
 * The controller for the Settings record
 *
 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class CssController extends Controller {

	protected function checkAccess() {
		return true;
	}
	
	protected function checkXSRF() {
		return true;
	}
	
	/**
	 * GET a list of settingss or fetch a single settings
	 *
	 * The attributes of this settings should be posted as JSON in a settings object
	 *
	 * <p>Example for POST and return data:</p>
	 * ```````````````````````````````````````````````````````````````````````````
	 * {"data":{"attributes":{"name":"test",...}}}
	 * </code>
	 * 
	 * @param int $settingsId The ID of the settings
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 */
	public function download() {			
		
		GO()->getResponse()->setContentType('text/css');
		
		$settings = GO()->getAuth()->sudo(function(){
			return Settings::getInstance();
		});
		
		if($settings) {
			echo $settings->css;
		}
		
	}

}

