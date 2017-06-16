<?php

namespace GO\Modules\GroupOffice\Messages\Model;

use GO\Core\Email\Model\RecipientInterface;
use IFW\Auth\Permissions\Everyone;
use IFW\Orm\Query;
use IFW\Orm\Record;
use PDO;



/**
 * The Address model
 *
 * 
 * @property Message $message
 *
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class Address extends Record implements RecipientInterface {	
	
	/**
	 * 
	 * @var int
	 */							
	public $id;

	/**
	 * 
	 * @var int
	 */							
	public $messageId;

	/**
	 * 
	 * @var string
	 */							
	public $address;

	/**
	 * 
	 * @var string
	 */							
	public $personal;

	/**
	 * See TYPE_* constants
	 * @var int
	 */							
	public $type = 0;

	const TYPE_FROM = 0;
	const TYPE_TO = 1;
	const TYPE_CC = 2;
	const TYPE_BCC = 3;

	protected static function defineRelations() {
		self::hasOne('message', Message::class, ['messageId' => 'id']);
	}
	
	public static function internalGetPermissions() {
//		return new ViaRelation( 'message');
		return new Everyone();	
	}

//	/**
//	 * Returns true if the address matches the e-mail account fromEmail address
//	 * 
//	 * @return boolean
//	 */
//	public function getIsMe(){
//		
//		if(!$this->message->thread->account) {
//			return false;
//		}
//		return $this->email == $this->message->thread->account->smtpAccount->fromEmail;
//	}
	
	public function __toString() {
		if (!empty($this->personal)) {
			return '"' . $this->personal . '" <' . $this->address . '>';
		} else {
			return $this->address;
		}
	}	
	
	public function internalValidate() {
		$this->truncateModifiedAttributes();
		return parent::internalValidate();
	}

	/**
	 * For {@see \GO\Core\Email\Model\RecipientInterface}
	 */
	public static function findRecipients($searchQuery, $limit, $foundEmailAddresses = array()) {
		$query = (new Query())
						->select('t.personal, t.address')
						->distinct()
						->fetchMode(PDO::FETCH_ASSOC)
						->search($searchQuery, ['t.personal', 't.address'])
						->limit($limit)
						->orderBy(['address' => 'ASC']);


		if (!empty($foundEmailAddresses)) {
			$query->andWhere(['!=', ['address' => $foundEmailAddresses]]);
		}

		return Address::find($query)->all();
	}

}