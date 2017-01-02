<?php
namespace IFW\View;

/**
 * View interface
 * 
 * Views are used by {@see \IFW\Controller} instances to render output like
 * JSON, XML or HTML
 * 
 * See {@see Api} for an example for a JSON or XML response.
 *
 * @copyright (c) 2015, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
interface ViewInterface  {
	
	/**
	 * Renders the view
	 * 
	 * @param mixed $data
	 * @return self
	 */
	public function render($data);
	
	/**
	 * Get the view encoded
	 * 
	 * @param string
	 */
	public function __toString();
}