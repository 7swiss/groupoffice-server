<?php

namespace IFW\Orm;

use Exception;
use IFW\Auth\Permissions\Everyone;

/**
 * Property record
 * 
 * This is a special type of record that behaves as property. It has no permissions
 * because it's only allowed to fetch and save it relationally.
 * 
 */
class PropertyRecord extends Record {
	
	public static function find($query = null) {
		
		$query = Query::normalize($query);
		if(!$query->getRelation()) {
			throw new Exception("Property '".static::class."' can't be queried directly. Use as relation only.");
		}
		
		return parent::find($query);
	}
	
	protected function internalSave() {
		
		if(!$this->isSavedByRelation()) {
			throw new Exception("Property '".static::class."' can't be saved directly. Use as relation only.");
		}
		
		return parent::internalSave();
	}
	
	protected static function internalGetPermissions() {
		return new Everyone();
	}
	
	public static function getDefaultReturnProperties() {
		$props =  array_diff(parent::getReadableProperties(), ['validationErrors','modified', 'modifiedAttributes', 'markDeleted', 'permissions']);
		
		return implode(',', $props);
	}		
}
