<?php
namespace GO\Modules\Tutorial\Bands\Model;

use GO\Core\CustomFields\Model\CustomFieldsRecord;

class BandCustomFields extends CustomFieldsRecord {
	public static function find($query = null) {
//		throw new \Exception("HIer");
		
		parent::find($query);
	}
}
