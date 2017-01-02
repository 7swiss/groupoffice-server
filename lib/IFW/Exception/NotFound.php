<?php
namespace IFW\Exception;

/**
 * Thrown when an item was not found
 * 
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class NotFound extends HttpException
{
	public function __construct($message = null) {
		parent::__construct(404, $message);
	}
}