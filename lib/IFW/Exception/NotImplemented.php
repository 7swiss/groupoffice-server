<?php
namespace IFW\Exception;

/**
 * Thrown when a method is not implemented
 * 
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class NotImplemented extends HttpException
{
	public function __construct($message=null) {
		parent::__construct(501, $message);
	}
}