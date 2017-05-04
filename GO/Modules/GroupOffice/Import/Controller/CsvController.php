<?php

namespace GO\Modules\GroupOffice\Import\Controller;

use Exception;
use IFW;
use GO\Core\Controller;
use IFW\Db\Column;
use IFW\Orm\Relation;
use GO\Core\Users\Model\Group;
use GO\Modules\GroupOffice\Contacts\Model\Contact;
use GO\Modules\GroupOffice\Contacts\Model\AclGroup;

class CsvController extends Controller {

	public static $delimiter = ',';
	public static $enclosure = '"';
	private $_record;

	public function httpPost() {


		//Don't be strict on imported values
		GO()->getDbConnection()->getPDO()->query("SET sql_mode=''");

		$modelName = Contact::class;
		//Zoekcode,Naam,Voornaam,Tussenvoegsel,Achternaam,Voorletters,Geboortedatum,Titel,Geslacht,Functie,Bankrekening (BBAN),IBAN/bankrekening,BIC,Datum ondertekening,Datum beëindiging,Laatste incassodatum,Eerste incasso SEPA,Eenmalige machtiging,Mandaatkenmerk,Betalingswijze,Relatiebeheerder,Niet actief,Straat hoofdadres,Postcode hoofdadres,Plaats hoofdadres,Provincie hoofdadres,Land hoofdadres,E-mailadres,Faxnummer,Telefoonnummer,Mobiel nummer,Webpagina,Straat postadres,Postcode postadres,Plaats postadres,Provincie postadres,Land postadres,Afkoop bardienst,Bardienst avond,Bardienst middag,Bardienst ochtend,Blad,Bondsnummer,Comm,Fotonummer,SD,SE,Factuur toesturen,Begindatum lidmaatschap,Einddatum lidmaatschap,Postadres via ander lid,Betalend lid,KvK-nummer,Soort relatie,Persoontype,Naam contactpersoon,Afdeling,Is afzonderlijke relatie,BTW-nummer,BrancheZoekcode,BrancheBranchenaam,RechtsvormAfkorting,RechtsvormRechtsvorm,Bedrijfsgrootte,Valuta voor relatie,Debiteur,Crediteur,Verkoopdagboek,Verkooppostnummer,Verkoper bij debiteur,Kortingsmarge debiteur,Prijslijst bij debiteur,Kredietlimiet debiteur,Debiteur geblokkeerd,Betalingsvoorwaarde debiteur,Leveringsvoorwaarde debiteur,Inkoopdagboeknummer,Inkooppostnummer,Kortingsmarge crediteur,Kredietlimiet crediteur,Betalingsvoorwaarde crediteur,Leveringsvoorwaarde crediteur,Straat afleveradres,Postcode afleveradres,Plaats afleveradres,Provincie afleveradres,Land afleveradres,Straat factuuradres,Postcode factuuradres,Plaats factuuradres,Provincie factuuradres,Land factuuradres
		$filename = '/home/mschering/Downloads/leden.csv';

		$mapping = [
			'name' => 'Naam',
			'firstName' => 'Voornaam',
			'middleName' => 'Tussenvoegsel',
			'lastName' => 'Achternaam',
			'gender' => 'Geslacht',
			'dates' => [['date' => 'Geboortedatum', 'type' => '"birthday"']],
			'emailAddresses' => [['email' => 'E-mailadres', 'type' => '"home"']],
			'customFields' => ['Bondsnummer' => 'Bondsnummer', 'Lid sinds' => 'Begindatum lidmaatschap'],
			'phoneNumbers' => [
				['number' => 'Telefoonnummer', 'type' => '"home"'],
				['number' => 'Mobiel nummer', 'type' => '"mobile"']
			],
			'user' => ['username' => 'Bondsnummer', 'password' => 'Postcode hoofdadres']
		];

		$fp = fopen($filename, 'r');

		$headings = fgetcsv($fp, 4096, self::$delimiter, self::$enclosure);

		while ($csvRecord = fgetcsv($fp, 4096, self::$delimiter, self::$enclosure)) {

			foreach ($headings as $index => $colName) {
				
				//['Naam' => 'Jansen', 'Voornaam' => 'Piet', 'Achternaam' => 'Jansen' etc...
				
				$this->_record[$colName] = $csvRecord[$index];
			}

			try {

				$model = new $modelName;

				/* @var $model Contact */


				$attributes = $this->_buildAttributes($modelName, $mapping);

				//			var_dump($attributes);
				//			exit();

				$model->setValues($attributes);

				$success = $model->save();

				if (!$success) {
					echo "Import failed: " . var_export($model->getValidationErrors(), true) . "\n----\n\n";
				} else {
					$cr = new AclGroup();
					$cr->contactId = $model->id;
					$cr->groupId = Group::ID_EVERYONE;
					$cr->readAccess = true;
					$cr->save();
				}
			} catch (\Exception $e) {
				echo $e->getMessage() . "\n";

				var_dump($attributes);

				echo "-----------------\n\n";
			}

//			break;
		}
		echo $this->view->render('json', []);
	}

	private function _buildAttributes($modelName, $mapping) {
		$attributes = [];

			// eg firstName => Voornaam
		foreach ($mapping as $goField => $csvField) {

			$attributes[$goField] = $this->_buildAttributeValue($modelName, $goField, $csvField);
		}

		return $attributes;
	}

	private function _buildAttributeValue($modelName, $goField, $csvField) {

		if (is_string($csvField) && substr($csvField, 0, 1) == '"' && substr($csvField, -1) == '"') {
			//hardcoded string value

			return substr($csvField, 1, -1);
		} elseif (($column = $modelName::getColumn($goField))) {

			/* @var $column Column */

			if ($column->required && empty($this->_record[$csvField])) {

				//throw exception here so we can catch and skup required relation attributes
				throw new Exception('Column "' . $modelName . '::' . $goField . '" is required');
			}

			//plain attribute of model
			return $this->_record[$csvField];
		} elseif (($relation = $modelName::getRelation($goField))) {


			if ($relation->isA(Relation::TYPE_HAS_MANY)) {

				//has one or belongs to relation

				$hasMany = [];


				foreach ($csvField as $mapping) {
					try {
						$hasMany[] = ['attributes' => $this->_buildAttributes($relation->getToRecordName(), $mapping)];
					} catch (Exception $e) {
						
					}
				}

				return $hasMany;
			} else {
				//has one or belongs to relation


				return ['attributes' => $this->_buildAttributes($relation->getToRecordName(), $csvField)];
			}
		} else {
			throw new Exception("Invalid mapping " . $goField . " for model $modelName");
		}
	}

	private function _parseValue($modelName, $attributeName, $value) {
//		$column = $modelName::getColumn($attributeName);
//		
//		/* @var $column \IFW\Db\Column */
//		
//		if($column->dbType == 'date' || $column->dbType == 'datetime') {
//			
//			echo $value;
//			return strtotime($value);
//		}else
//		{
		return $value;
//		}
	}

}
