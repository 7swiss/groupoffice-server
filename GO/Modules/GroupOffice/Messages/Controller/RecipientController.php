<?php

namespace GO\Modules\GroupOffice\Messages\Controller;

use GO\Core\Controller;
use GO\Modules\GroupOffice\Contacts\Model\Contact;
use GO\Modules\GroupOffice\Contacts\Model\ContactPermissions;
use GO\Modules\GroupOffice\Messages\Model\Address;
use IFW;
use IFW\Data\Store;
use IFW\Mail\Recipient;
use IFW\Orm\Query;

class RecipientController extends Controller {

	public function actionRecipients($searchQuery = "") {

		$limit = 10;

		$query = (new Query())
						->orderBy(['name' => 'ASC'])
						->limit($limit)
						->joinRelation('emailAddresses')
						->fetchMode(\PDO::FETCH_ASSOC)
						->select('t.name AS personal, emailAddresses.email AS address')
						->groupBy(['emailAddresses.email'])
						->search($searchQuery, ['t.name', 'emailAddresses.email']);

		$contacts = Contact::find($query);
		$records = $contacts->all();

		$emails = [];
		
		foreach($records as $record) {
			$emails = $record['address'];
		}

		$count = count($records);
		if ($count < $limit) {
			$query = (new Query())
							->select('t.address, t.personal')
							->fetchMode(\PDO::FETCH_ASSOC)
							->joinRelation('message.thread.account', false)
							->where(['account.createdBy' => GO()->getAuth()->user()->id()])												
							->search($searchQuery, ['t.personal', 't.address'])
							->limit($limit - $count)
							->orderBy(['personal' => 'ASC'])
							->groupBy(['t.address']);
			
			if(!empty($emails)) {
				$query->andWhere(['!=', ['address'=>$emails]]);
			}

			$addresses = Address::find($query);
			$records = array_merge($records, $addresses->all());
		}

		$store = new Store($records);
		$store->setReturnProperties('personal,address');
		$store->format('personal', function($record) {
			return !empty($record['personal']) ? $record['personal'] : $record['address'];
		});
		$store->format('full', function($record) {
			$recipient = new Recipient($record['address'], $record['personal']);
			return (string) $recipient;
		});

		$this->renderStore($store);
	}

}
