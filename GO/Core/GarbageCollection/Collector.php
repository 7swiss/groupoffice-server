<?php

namespace GO\Core\GarbageCollection;

use IFW;
use IFW\Data\Object;
use IFW\Util\ClassFinder;
use ReflectionClass;

/**
 * Collects garbage
 * 
 * Will call the method "garbageCollect" on all installed module classes
 * 
 * @copyright (c) 2017, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class Collector extends Object {

	public static function collect() {
		foreach (IFW::app()->getModules() as $module) {

			$classFinder = new ClassFinder();
			$classFinder->setNamespace($module::getNamespace());

			$classes = $classFinder->findByParent(GarbageCollectionInterface::class);

			foreach ($classes as $className) {
				if (!method_exists($className, 'collectGarbage')) {
					continue;
				}

				$reflection = new ReflectionClass($className);
				if ($reflection->isAbstract()) {
					continue;
				}
				$className::collectGarbage();
			}
		}
	}

}
