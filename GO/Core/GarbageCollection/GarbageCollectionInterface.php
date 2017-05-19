<?php
namespace GO\Core\GarbageCollection;

/**
 * Use garbage collection
 * 
 * When you need to collect garbage use this interface
 * 
 * @example
 * ````
 * public static function collectGarbage() {		
 *	 //delete expired notifications
 * 	 GO()->getDbConnection()->createCommand()->delete(self::tableName(), ['<=', ['expiresAt' => new DateTime()]])->execute();
 * }
 * ```
 * 
 * @copyright (c) 2017, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
interface GarbageCollectionInterface {
	
	/**
	 * Will be called by the garbage collection cron job
	 */
	public static function collectGarbage();
}
