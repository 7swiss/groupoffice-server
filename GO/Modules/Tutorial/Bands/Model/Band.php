<?php

namespace GO\Modules\Tutorial\Bands\Model;

//add this use


use GO\Core\Users\Model\User;
use GO\Core\CustomFields\Model\Field;
use IFW\Data\Filter\FilterCollection;
use IFW\Orm\Record;
use GO\Modules\Tutorial\Bands\Model\BandCustomFields;

/**
 * The Band model
 *
 * @property int $id
 * @property StringUtil $name
 * @property int $createdBy
 * @property User $owner
 * @property StringUtil $createdAt
 * @property StringUtil $modifiedAt
 * 
 * @property Album[] $albums
 * @property BandCustomFields $customFields
 *
 * @copyright (c) 2015, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class Band extends Record {

	protected static function defineRelations() {
		self::hasMany('albums', Album::class, ['id' => 'bandId']);
		self::hasOne('owner', User::class, ['createdBy' => 'id']);
		
		//add this custom field relation
		self::hasOne('customFields', BandCustomFields::class, ['id' => 'id']);

		parent::defineRelations();
	}
	
	protected function createPermissions() {
		return new BandPermissions($this);
	}
	
	public static function createFilterCollection() {
		$filters = new FilterCollection(Band::class);		
		$filters->addFilter(GenreFilter::class);		
		
		//Adds custom field filters automatically
		Field::addFilters(BandCustomFields::class, $filters);
		
		return $filters;
	}

}
