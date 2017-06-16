<?php

namespace GO\Core\Email\Controller;

use GO\Core\Controller;
use GO\Core\Email\Model\RecipientInterface;
use IFW;
use IFW\Data\Store;
use IFW\Mail\Recipient;
use IFW\Util\ClassFinder;

class RecipientController extends Controller {
	
	use IFW\Event\EventEmitterTrait;
	
	/**
	 * Event fired when recipients are requested
	 * 
	 * @param int $limit The total number that should be returned.
	 * @param array $records The records filled by other listeners 
	 */
	const EVENT_RECIPIENTS = 0;
	
	
	/**
	 * Get the model class names that implement {@see \GO\Core\Email\Model\RecipientInterface}
	 * 
	 * @return string[]
	 */
	private function findRecipientModels() {
		$cacheKey = self::class.'::findRecipientModels';
		$models = \GO()->getCache()->get($cacheKey);
		
		if(!isset($models)) {
			$models = [];
			foreach(\IFW::app()->getModules() as $module) {

				$classFinder = new ClassFinder();		
				$classFinder->setNamespace($module::getNamespace());

				$models = array_merge($models, $classFinder->findByParent(RecipientInterface::class));
			}
			
			\GO()->getCache()->set($cacheKey, $models);
		}
		
		return $models;
	}

	public function actionStore($searchQuery = "") {

		$limit = 10;
		$alreadyFoundEmails = [];
		$records = [];
		
		$models = $this->findRecipientModels();
		
		foreach($models as $model) {
			$newRecords = $model::findRecipients($searchQuery, $limit, $alreadyFoundEmails);
			$records = array_merge($records, $newRecords);
			
			foreach($newRecords as $record) {
				$alreadyFoundEmails[] = $record['address'];
			}
			
			$limit -= count($newRecords);
			
			if($limit <= 0) {
				break;
			}			
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
