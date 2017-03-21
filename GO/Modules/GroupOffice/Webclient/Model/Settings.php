<?php
namespace GO\Modules\GroupOffice\Webclient\Model;
						
use GO\Core\Orm\Record;
						
/**
 * The Settings record
 *
 * @copyright (c) 2017, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */

class Settings extends Record{


	/**
	 * 
	 * @var int
	 */							
	public $id;

	/**
	 * 
	 * @var string
	 */							
	public $css;
	
	public static function getInstance() {
		$settings = self::find()->single();
		
		if (!$settings) {
			$settings = new self();
			$settings->id = 1;			
		}
		
		return $settings;
	}

}