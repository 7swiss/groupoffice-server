<?php
namespace GO\Core\View\Web;

use IFW;

/**
 * The default API view
 * 
 * Views are used in {@see Controller}.
 * Renders JSON or XML based on the HTTP Accept header the client sent.
 * 
 * Example:
 * 
 * <code>  
 * $data = ['success' => true];
 * 
 * $view = new Api();		
 * $output = $view->render($data);	
 * </code>
 * 
 * @copyright (c) 2015, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class Api extends IFW\View\Web\Api {
	/**
	 * Renders the JSON or XML
	 * 
	 * @param array $data
	 * @return self
	 */
	public function render($data) {
		
//		$data = GO()->getActionCollection()->apply($data);
		
		$data['notificationCount'] = \GO\Core\Notifications\Model\Notification::countForCurrentUser();

		return parent::render($data);
	}
}