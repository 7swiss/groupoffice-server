<?php
namespace GO\Core\Email\Model;

/**
 * Recipient interface
 * 
 * Used when a model can be used as e-mail recipient.
 */
interface RecipientInterface {
	/**
	 * Finds recipient
	 * 
	 * @param string $searchQuery The search query passed
	 * @param int $limit Return a maximum number of record
	 * @param array $foundEmailAddresses Skip these e-mail addresses as they are already included by other models.
	 * 
	 * @return array Recipient records with personal and address keys. eg [['personal' => 'John', 'address' => 'john@intermesh.nl']] 
	 */
	public static function findRecipients($searchQuery, $limit, $foundEmailAddresses = []);
}
