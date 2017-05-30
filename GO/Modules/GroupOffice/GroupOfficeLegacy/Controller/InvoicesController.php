<?php
namespace GO\Modules\GroupOffice\GroupOfficeLegacy\Controller;

use DateTime;
use Exception;
use GO\Core\Controller;
use GO\Modules\GroupOffice\Contacts\Model\Address;
use GO\Modules\GroupOffice\Contacts\Model\Contact;
use GO\Modules\GroupOffice\Contacts\Model\EmailAddress;
use GO\Modules\Intermesh\Invoices\Model\Invoice;
use IFW\Http\Client;
use IFW\Orm\Query;


class InvoicesController extends Controller {
	
	protected $client;
	
	protected $host;
	
	protected $username;
	
	protected $password;
	
	protected $book_id;
	
	protected $businessId;
	
	protected $contactsAccountId;
	
	public function actionSync() {
		
		GO()->logSuspend();
		\GO\Core\Notifications\Model\Notification::suspend();
		
		$this->client = new Client($this->host);
		$this->client->setAuth($this->username, $this->password);
		
		$response = $this->client->request('/index.php?r=billing/sync/stat&book_id='.$this->book_id);
		
		$stat = json_decode($response->body, true);
		
		foreach($stat as $statItem) {
			$invoice = Invoice::find(['businessId' => $this->businessId, 'number' => $statItem['order_id']])->single();
						
			if(!$invoice) {
				$invoice = new Invoice();
				$invoice->businessId = $this->businessId;
			}
			
			$remoteModifiedAt = new DateTime($statItem['mtime']);
			
			if($invoice->isNew() || $remoteModifiedAt > $invoice->modifiedAt)
			{
				$this->updateInvoice($invoice, $statItem['id']);
			}
		}
	}
	

	
	private function updateInvoice(Invoice $invoice, $remoteId) {
			
		$response = $this->client->request('/index.php?r=billing/sync/read&id='.$remoteId);		
		$remoteData = json_decode($response->body, true);
		
		$contact = $this->getContact($remoteData);
		
		
		if($invoice->isNew()) {
			$invoice->setNumber($remoteData['order_id']);
		}
		
		$invoice->customerReference = $remoteData['reference'];
		$invoice->invoiceDate = \DateTime::createFromFormat("U", $remoteData['btime']);
		$invoice->dueAt = \DateTime::createFromFormat("U", $remoteData['due_date']);
		
		$invoice->note = \IFW\Util\StringUtil::htmlToText($remoteData['frontpage_text']);
		$invoice->vatReverseCharge = $remoteData['vat'] == 0;
		$invoice->setCustomer($contact);
		
		$items = [];
		$remoteData = $this->fixItemsBug($remoteData);
		foreach($remoteData['items'] as $i) {			
			$item = new \GO\Modules\Intermesh\Invoices\Model\InvoiceItem();
			$item->description = $i['description'];
			$item->unit = $i['unit'];
			$item->discount = $i['discount'];
			$item->quantity = $i['amount'];
			$item->unitPrice = $i['unit_price'];
			$item->vatCode = \GO\Modules\Intermesh\Invoices\Model\VatCode::findByRate($i['vat']);
			
			$items[] = $item;
		}
		
		$invoice->items->replace($items);
		
		$remoteData = $this->fixPaymentsBug($remoteData);
		
		$payments = [];
		foreach($remoteData['payments'] as $i) {			
			$payment = new \GO\Modules\Intermesh\Invoices\Model\InvoicePayment();
			$payment->amount = $i['amount'];
			$payment->paidAt = \DateTime::createFromFormat('U', $i['date']);
			
			if($remoteData['total'] < 0 && $payment->amount > 0) { //credit notes have inversed payments in GO6
				$payment->amount *= -1;
			}
			
			$payments[] = $payment;
		}
		
		$invoice->payments->replace($payments);
		
		if(!$invoice->save()) {
			throw new Exception("Failed to save invoice");
		}
		
		if(!$invoice->getIsPaid() && $remoteData['status']['payment_required'] == 0) {
			
			//status indicates the invoice is paid but payments are incorrect. Fix the payment table.
			$payment = new \GO\Modules\Intermesh\Invoices\Model\InvoicePayment();
			$payment->amount = $invoice->grossTotal;
			$payment->paidAt = \DateTime::createFromFormat('U', $remoteData['ptime']);
			$invoice->payments->replace($payments);
			
			if(!$invoice->save()) {
				throw new Exception("Failed to save invoice");
			}
		}
		
//		var_dump($remoteData);
	}
	
	
	private function fixPaymentsBug($remoteData) {
		if(empty($remoteData['payments']) && !empty($remoteData['total_paid'])) {
			$remoteData['payments'] = [['amount' => $remoteData['total_paid'], 'date' => $remoteData['ptime']]];
		}
		
		return $remoteData;
	}
	
	private function fixItemsBug($remoteData) {
		if(empty($remoteData['items']) && !empty($remoteData['total'])) {
			
			$rate = (($remoteData['total'] / $remoteData['subtotal']) - 1) * 100;
			
			$remoteData['items'] = [['amount' => 1, 'description' => 'unknown', 'unit_price' => $remoteData['subtotal'], 'vat' => $rate]];
		}
		
		return $remoteData;
	}
	
	
	private function getContact($remoteData) {
		
		$query = new Query();
		$query->where(['name' => $remoteData['customer_name'], 'isOrganization' => true]);
		
		$contact = Contact::find($query)->single();
		if($contact) {
			return $contact;
		}
		
		$contact = new Contact();
		$contact->accountId = $this->contactsAccountId;
		$contact->language = $remoteData['language']['language'];
		
		$contact->isOrganization = true;
		$contact->name = $remoteData['customer_name'];
		if(!empty($remoteData['customer_vat_no'])) {
			$contact->vatNo = $remoteData['customer_vat_no'];
		}
		
		if(!empty($remoteData['customer_crn'])) {
			$contact->registrationNumber = $remoteData['customer_crn'];
		}
		
		if(!empty($remoteData['customer_email'])) {
			$email = new EmailAddress();
			$email->type = EmailAddress::TYPE_INVOICE;
			$email->email = $remoteData['customer_email'];
			$contact->emailAddresses[] = $email;
		}
		
		$address = new Address();
		$address->country = (string) $remoteData['customer_country'];
		$address->state = (string) $remoteData['customer_state'];
		$address->city = (string) $remoteData['customer_city'];
		$address->zipCode = (string) $remoteData['customer_zip'];
		$address->street = $remoteData['customer_address'].' '.$remoteData['customer_address_no'];
		
		$contact->addresses[] = $address;
		
		if(!$contact->save()) {
			throw new Exception("Could not save debtor: ".var_export($contact->getValidationErrors(), true));
		}
		
		return $contact;
	}
	
}