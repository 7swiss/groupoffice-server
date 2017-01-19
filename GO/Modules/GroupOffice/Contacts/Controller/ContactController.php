<?php

namespace GO\Modules\GroupOffice\Contacts\Controller;

use GO\Core\Controller;
use GO\Core\CustomFields\Model\Field;
use GO\Core\Tags\Filter\TagFilter;
use GO\Modules\GroupOffice\Contacts\Model\AgeFilter;
use GO\Modules\GroupOffice\Contacts\Model\Contact;
use GO\Modules\GroupOffice\Contacts\Model\ContactCustomFields;
use GO\Modules\GroupOffice\Contacts\Model\GenderFilter;
use GO\Modules\GroupOffice\Contacts\Model\TypeFilter;
use GO\Modules\GroupOffice\Contacts\Model\VCardHelper;
use GO\Core\Blob\Model\Blob;
use IFW;
use IFW\Data\Filter\FilterCollection;
use IFW\Exception\NotFound;

/**
 * The controller for contacts
 * 
 * See {@see Contact} model for the available properties
 * 
 * 
 *
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class ContactController extends Controller {
	

//	protected $checkModulePermision = true;
//
//	use ThumbControllerTrait;
//
//	protected function thumbGetFile() {
//
//		$contact = false;
//
//		if (isset($_GET['userId'])) {
//			$user = User::findByPk($_GET['userId']);
//
//			if ($user) {
//				$contact = $user->contact;
//			}
//		} else if (isset($_GET['contactId'])) {
//			$contact = Contact::findByPk($_GET['contactId']);
//		} elseif (isset($_GET['email'])) {
//
//			$query = (new Query())
//					->joinRelation('emailAddresses')
//					->groupBy(['t.id'])
//					->where(['emailAddresses.email' => $_GET['email']]);
//
//			$contact = Contact::findPermitted($query, 'readAccess')->single();
//		}
//
//
//		if ($contact) {
//
//			if (!$contact->checkPermission('readAccess')) {
//
//
//				throw new Forbidden();
//			}
//
//			return $contact->getPhotoFile();
//		}
//
//
//		return false;
//	}

//	protected function thumbUseCache() {
//		return true;
//	}
	
	
	
	
	
	
	public function actionFilters() {
		$this->render($this->getFilterCollection()->toArray());		
	}
	
	private function getFilterCollection() {
		$filters = new FilterCollection(Contact::class);
		
		//$filters->setCountQuery(ContactPermissions::query());
		
		$filters->addFilter(TypeFilter::class);
		$filters->addFilter(TagFilter::class);
		$filters->addFilter(GenderFilter::class);
		$filters->addFilter(AgeFilter::class);
		
		
//		Field::addFilters(ContactCustomFields::class, $filters);
		
		return $filters;
	}
	

	/**
	 * Fetch contacts
	 *
	 * @param string $orderColumn Order by this column
	 * @param string $orderDirection Sort in this direction 'ASC' or 'DESC'
	 * @param int $limit Limit the returned records
	 * @param int $offset Start the select on this offset
	 * @param string $searchQuery Search on this query.
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return array JSON Model data
	 */
	public function actionStore($orderColumn = 't.name', $orderDirection = 'ASC', $limit = 10, $offset = 0, $searchQuery = "", $returnProperties = "", $q = null) {

		$query = (new IFW\Orm\Query())
				->orderBy([$orderColumn => $orderDirection])
				->limit($limit)
				->offset($offset);

		if (!empty($searchQuery)) {
			$query->search($searchQuery, ['name', 'debtorNumber']);
		}

		if(isset($q)) {
			$query->setFromClient($q);			
		}
		
		$this->getFilterCollection()->apply($query);		

		$contacts = Contact::find($query);
		$contacts->setReturnProperties($returnProperties);

		$this->renderStore($contacts);
	}	
	
	protected function actionNew($returnProperties = ""){
		$contact = new Contact();
		$this->renderModel($contact, $returnProperties);
	}
	
	
	protected function actionReadByUser($userId, $returnProperties = "*"){
		$contact = Contact::find(['userId' => $userId])->single();
		
		if(!$contact) {
			$contact = new Contact();
			$contact->userId = $userId;
		}
		
		$this->renderModel($contact, $returnProperties);
	}
	
	/**
	 * GET a list of contacts or fetch a single contact
	 *
	 * The attributes of this contact should be posted as JSON in a group object
	 *
	 * <p>Example for POST and return data:</p>
	 * <code>
	 * {"data":{"attributes":{"name":"test",...}}}
	 * </code>
	 * 
	 * @param int $contactId The ID of the group
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 */
	protected function actionRead($contactId, $returnProperties = "*"){
		
		if($contactId == "current"){
			$contact = \GO()->getAuth()->user()->contact;
		}else
		{
			$contact = Contact::findByPk($contactId);
		}
	
		if (!$contact) {
			throw new NotFound();
		}
		
		$this->renderModel($contact, $returnProperties);

	}

	protected function actionVCard($contactId){

		$contact = Contact::findByPk($contactId);

		if (!$contact) {
			throw new NotFound();
		}

		$vObject = VCardHelper::toVCard($contact);
		header('Content-type: text/x-vcard; charset=utf-8');
		header('Content-Disposition: inline; filename=card.vcf');
		echo $vObject->serialize();

	}

	protected function actionImport($blobId) { // to vCard

		$blob = Blob::findByPk($blobId);
		if (!$blob) {
			throw new NotFound();
		}
		$vcard = VCardHelper::read($blob);
		if (!$vcard) {
			throw new NotFound();
		}
		$count = ['created' => 0, 'failed' => 0, 'validationErrors' => []];
		$contacts = VCardHelper::fromVCard($vcard);
		foreach($contacts as $contact) {
			if($contact->save()){
				$count['created']++;
			} else {
				$count['failed']++;
				$count['validationErrors'][$contact->name] = $contact->getValidationErrors();
			}
		}
		$this->render($count);
	}

	
	/**
	 * Create a new field. Use GET to fetch the default attributes or POST to add a new field.
	 *
	 * The attributes of this field should be posted as JSON in a field object
	 *
	 * <p>Example for POST and return data:</p>
	 * <code>
	 * {"field":{"attributes":{"fieldname":"test",...}}}
	 * </code>
	 * 
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 */
	public function actionCreate($returnProperties = "") {
		
		$contact = new Contact();		
		$contact->setValues(GO()->getRequest()->body['data']);
		$contact->save();
		

		$this->renderModel($contact, $returnProperties);
	}

	/**
	 * Update a field. Use GET to fetch the default attributes or POST to add a new field.
	 *
	 * The attributes of this field should be posted as JSON in a field object
	 *
	 * <p>Example for POST and return data:</p>
	 * <code>
	 * {"field":{"attributes":{"fieldname":"test",...}}}
	 * </code>
	 * 
	 * @param int $contactId The ID of the field
	 * @param array|JSON $returnProperties The attributes to return to the client. eg. ['\*','emailAddresses.\*']. See {@see IFW\Db\ActiveRecord::getAttributes()} for more information.
	 * @return JSON Model data
	 * @throws NotFound
	 */
	public function actionUpdate($contactId, $returnProperties = "") {

		if($contactId == "current"){
			$contact = \GO()->getAuth()->user()->contact;
		}else
		{
			$contact = Contact::findByPk($contactId);
		}

		if (!$contact) {
			throw new NotFound();
		}

		$contact->setValues(GO()->getRequest()->body['data']);
		$contact->save();
		
		$this->renderModel($contact, $returnProperties);
	}

	/**
	 * Delete a field
	 *
	 * @param int $contactId
	 * @throws NotFound
	 */
	public function actionDelete($contactId) {
		$contact = Contact::findByPk($contactId);

		if (!$contact) {
			throw new NotFound();
		}

		$contact->delete();

		$this->renderModel($contact);
	}
	
	/**
	 * Update multiple contacts at once with a PUT request.
	 * 
	 * @example multi delete
	 * ```````````````````````````````````````````````````````````````````````````
	 * {
	 *	"data" : [{"id" : 1, "markDeleted" : true}, {"id" : 2, "markDeleted" : true}]
	 * }
	 * ```````````````````````````````````````````````````````````````````````````
	 * @throws NotFound
	 */
	public function actionMultiple() {
		
		$response = ['data' => []];
		
		foreach(GO()->getRequest()->getBody()['data'] as $values) {
			
			if(!empty($values['id'])) {
				$contact = Contact::findByPk($values['id']);

				if (!$contact) {
					throw new NotFound();
				}
			}else
			{
				$contact = new Contact();
			}
			
			$contact->setValues($values);
			$contact->save();
			
			$response['data'][] = $contact->toArray();
		}
		
		$this->render($response);
	}
}
