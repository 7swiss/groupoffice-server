<?php

namespace IFW\Event;

interface EventListenerInterface {

	/**
	 * Define events here
	 * 
	 * @example
	 * ```
	 * \SomeEventEmitter::on('someEvent', self::class, 'staticClassMethod');
	 * ```
	 * 
	 * @see EventEmitterTrait
	 * 
	 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
	 * @author Merijn Schering <mschering@intermesh.nl>
	 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
	 */
	public static function defineEvents();
}
