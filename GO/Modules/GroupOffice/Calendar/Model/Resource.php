<?php
/*
 * @copyright (c) 2016, Intermesh BV http://www.intermesh.nl
 * @author Michael de Hart <mdhart@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */

namespace GO\Modules\GroupOffice\Calendar\Model;

use IFW\Orm\Record;

/**
 * A calendar resource. Like a beamer of a room.
 * Add this to the attendees to keep availability schedual
 *
 * @property string $email
 * @property string $uri
 * @property User $delegate The person who delegates the resource
 * @property-read bool $needApproval If no approval is needed the resource will accept invitation automaticly
 */
class Resource extends Record implements PrincipalInterface {
	
	/**
	 * Primary key auto increment.
	 * @var int
	 */							
	public $id;

	/**
	 * @see constants
	 * @var int
	 */							
	public $type = 1;

	/**
	 * 
	 * @var string
	 */							
	public $name;

	/**
	 * 
	 * @var int
	 */							
	public $autoAccept = 1;

	/**
	 * foreign key of user the delegates the resource
	 * @var int
	 */							
	public $delegateId;

	const Room = 1;
	const Equipment = 2;
	
	public static function tableName() {
		return 'calendar_resource';
	}

	public function getEmail() {
		return $this->email;
	}
	
	public function getPrincipalType() {
		return PrincipalInterface::Resource;
	}

	public function getName() {
		return $this->name;
	}
	
	public function getSchedual() {
		return Schedule::byResource($this->id);
	}
	
	/**
	 * If approval is not needed the resource will auto accept all invitations
	 * @return bool true if approval is needed
	 */
	public function getNeedApproval() {
		return !empty($this->delegateId);
	}
	
	public function approve() {
		if($this->delegateId == \GO::user()->id) {
			//set attending status to accepted
		}
	}

}
