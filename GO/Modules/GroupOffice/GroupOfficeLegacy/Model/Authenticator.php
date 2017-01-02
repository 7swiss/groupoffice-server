<?php

namespace GO\Modules\GroupOffice\GroupOfficeLegacy\Model;

use DateTime;
use Exception;
use GO\Core\Users\Model\User;
use GO\Modules\GroupOffice\Contacts\Model\Contact;
use GO\Modules\GroupOffice\Contacts\Model\Address;
use IFW;
use IFW\Web\Client;

class Authenticator {


	private $_client;

	public function __construct($baseUrl) {

		$this->_client = new Client();
		$this->_client->baseUrl = $baseUrl;						
		$this->_client->enableCookies();
		$this->_client->setCurlOption(CURLOPT_HTTPHEADER, array("X-Requested-With: XMLHttpRequest"));
	}

	public function login($username, $password) {

		$response = $this->_client->request("auth/login", ['username' => $username, 'password' => $password]);
		$json = json_decode($response, true);

		
		if (!$json['success']) {
			
			GO()->debug('Could not login to GO6');			
			return false;
		}

		$this->_client->baseParams['security_token'] = $json['security_token'];

		$response = $this->_client->request('settings/load', ['id' => $json['user_id']]);
		$settings = json_decode($response, true);


		return \GO()->getAuth()->sudo(function() use ($username, $password, $settings) {
							return $this->_createUser($username, $password, $settings);
						});
	}

	private function _createUser($username, $password, $settings) {
		$user = User::find(['username' => $username])->single();
		
//		var_dump($settings);

		if (!$user) {
			$user = new User();
			$user->username = $username;
			$user->email = $settings['data']['email'];
		}
		$user->password = $password;

		if (!$user->save()) {
			throw new \Exception("Could not save new user! ".var_export($user->getValidationErrors(), true));
		}

		if($user->contact)
			$user->contact->delete();
//		$contact = $user->contact;
		
		if (true || !$contact) {
			$contact = new Contact();
			$contact->userId = $user->id;



			/**
			 * $settings = 
			 * ["data"]=>
			  array(116) {
			  ["id"]=>
			  int(10674)
			  ["username"]=>
			  string(9) "mschering"
			  ["digest"]=>
			  string(32) "6f2392f5108209418d0d07f8c83efeb9"
			  ["enabled"]=>
			  bool(true)
			  ["first_name"]=>
			  string(6) "Merijn"
			  ["middle_name"]=>
			  string(0) ""
			  ["last_name"]=>
			  string(8) "Schering"
			  ["email"]=>
			  string(22) "mschering@intermesh.nl"
			  ["acl_id"]=>
			  int(2)
			  ["date_format"]=>
			  string(3) "dmY"
			  ["date_separator"]=>
			  string(1) "-"
			  ["time_format"]=>
			  string(3) "H:i"
			  ["thousands_separator"]=>
			  string(1) "."
			  ["decimal_separator"]=>
			  string(1) ","
			  ["currency"]=>
			  string(3) "€"
			  ["logins"]=>
			  int(25075)
			  ["lastlogin"]=>
			  string(16) "14-08-2015 13:08"
			  ["ctime"]=>
			  string(16) "16-01-2012 14:38"
			  ["max_rows_list"]=>
			  int(50)
			  ["timezone"]=>
			  string(16) "Europe/Amsterdam"
			  ["start_module"]=>
			  string(7) "summary"
			  ["language"]=>
			  string(2) "nl"
			  ["theme"]=>
			  string(12) "Group-Office"
			  ["first_weekday"]=>
			  int(1)
			  ["sort_name"]=>
			  string(10) "first_name"
			  ["mtime"]=>
			  string(16) "01-04-2014 15:24"
			  ["mute_sound"]=>
			  bool(true)
			  ["mute_reminder_sound"]=>
			  bool(false)
			  ["mute_new_mail_sound"]=>
			  bool(true)
			  ["show_smilies"]=>
			  bool(false)
			  ["auto_punctuation"]=>
			  bool(true)
			  ["list_separator"]=>
			  string(1) ";"
			  ["text_separator"]=>
			  string(1) """
			  ["files_folder_id"]=>
			  int(69472)
			  ["disk_usage"]=>
			  int(4819299191)
			  ["disk_quota"]=>
			  string(5) "5.000"
			  ["mail_reminders"]=>
			  bool(false)
			  ["popup_reminders"]=>
			  bool(false)
			  ["password_type"]=>
			  string(5) "crypt"
			  ["muser_id"]=>
			  int(2)
			  ["holidayset"]=>
			  string(2) "nl"
			  ["sort_email_addresses_by_time"]=>
			  bool(false)
			  ["no_reminders"]=>
			  bool(false)
			  ["generatedRandomPassword"]=>
			  bool(false)
			  ["passwordConfirm"]=>
			  NULL
			  ["skip_contact_update"]=>
			  bool(false)
			  ["contact_id"]=>
			  NULL
			  ["name"]=>
			  string(15) "Merijn Schering"
			  ["uuid"]=>
			  string(36) "04e052d2-563b-576c-9c39-9846d86f0c30"
			  ["user_id"]=>
			  int(2)
			  ["addressbook_id"]=>
			  int(0)
			  ["initials"]=>
			  string(4) "M.K."
			  ["title"]=>
			  string(4) "Ing."
			  ["sex"]=>
			  string(1) "M"
			  ["birthday"]=>
			  string(10) "11-09-1980"
			  ["email2"]=>
			  string(19) "merijn@intermesh.nl"
			  ["email3"]=>
			  string(19) "mschering@gmail.com"
			  ["company_id"]=>
			  int(1)
			  ["department"]=>
			  string(8) "Directie"
			  ["function"]=>
			  string(9) "Directeur"
			  ["home_phone"]=>
			  string(12) "+31738514494"
			  ["work_phone"]=>
			  string(10) "0736445508"
			  ["fax"]=>
			  string(0) ""
			  ["work_fax"]=>
			  string(0) ""
			  ["cellular"]=>
			  string(12) "+31619864268"
			  ["homepage"]=>
			  string(0) ""
			  ["country"]=>
			  string(2) "NL"
			  ["state"]=>
			  string(13) "Noord-Brabant"
			  ["city"]=>
			  string(9) "Den Bosch"
			  ["zip"]=>
			  string(7) "5212 PM"
			  ["address"]=>
			  string(12) "Munteltuinen"
			  ["address_no"]=>
			  string(2) "50"
			  ["comment"]=>
			  string(0) ""
			  ["salutation"]=>
			  string(10) "Hoi Merijn"
			  ["email_allowed"]=>
			  int(1)
			  ["go_user_id"]=>
			  int(2)
			  ["suffix"]=>
			  string(0) ""
			  ["cellular2"]=>
			  string(0) ""
			  ["photo"]=>
			  string(33) "addressbook/photos/1104/10674.jpg"
			  ["action_date"]=>
			  string(0) ""
			  ["url_linkedin"]=>
			  string(41) "http://www.linkedin.com/in/merijnschering"
			  ["url_facebook"]=>
			  string(40) "https://www.facebook.com/merijn.schering"
			  ["url_twitter"]=>
			  string(31) "https://twitter.com/GroupOffice"
			  ["skype_name"]=>
			  string(9) "mschering"
			  ["color"]=>
			  string(6) "000000"
			  ["company_name"]=>
			  string(12) "Intermesh BV"
			  ["skip_user_update"]=>
			  bool(false)
			  ["dateformat"]=>
			  string(5) "-:dmY"
			  ["reminder_days"]=>
			  int(0)
			  ["reminder_time"]=>
			  string(5) "08:00"
			  ["remind"]=>
			  bool(true)
			  ["default_tasklist_id"]=>
			  int(607)
			  ["calendar_id"]=>
			  int(1)
			  ["tasklist_id"]=>
			  int(0)
			  ["note_category_id"]=>
			  int(0)
			  ["server_is_master"]=>
			  bool(true)
			  ["max_days_old"]=>
			  int(30)
			  ["delete_old_events"]=>
			  bool(false)
			  ["account_id"]=>
			  int(109)
			  ["reminder_multiplier"]=>
			  int(60)
			  ["reminder_value"]=>
			  int(15)
			  ["reminder"]=>
			  int(900)
			  ["background"]=>
			  string(6) "FFFF99"
			  ["show_statuses"]=>
			  bool(false)
			  ["default_calendar_id"]=>
			  int(1)
			  ["comments_enable_read_more"]=>
			  string(1) "1"
			  ["model_id"]=>
			  int(10674)
			  ["col_18"]=>
			  string(0) ""
			  ["col_19"]=>
			  string(5) "30,00"
			  ["col_20"]=>
			  string(17) "Reseller discount"
			  ["col_22"]=>
			  string(0) ""
			  ["col_24"]=>
			  string(0) ""
			  ["col_25"]=>
			  int(0)
			  ["photo_url"]=>
			  string(141) "/index.php?r=core/thumb&w=120&h=160&zc=1&filemtime=1396358686&src=addressbook%2Fphotos%2F1104%2F10674.jpg&security_token=tCMTIysxDpZu4LdNPmW2"
			  ["original_photo_url"]=>
			  string(100) "/index.php?r=addressbook/contact/photo&id=10674&mtime=1396358686&security_token=tCMTIysxDpZu4LdNPmW2"
			  ["start_module_name"]=>
			  string(11) "Startpagina"
			  }
			 */
			$d = $settings['data'];

			$contact->firstName = $d['first_name'];
			$contact->middleName = $d['middle_name'];
			$contact->lastName = $d['last_name'];
			$contact->gender = $d['sex'];
			$contact->prefixes = $d['title'];
			$contact->suffixes = $d['suffix'];


			$emailAddresses = [];
			if (!empty($d['email'])) {
				$emailAddresses[] = ['email' => $d['email']];
			}

			if (!empty($d['email2'])) {
				$emailAddresses[] = ['email' => $d['email2']];
			}

			if (!empty($d['email3'])) {
				$emailAddresses[] = ['email' => $d['email3']];
			}

			$dates = [];
			if (!empty($d['birthday'])) {
				$dates[] = ['date' => new DateTime($d['birthday']), 'type' => 'birthday'];
			}

			$contact->dates = $dates;

			$phoneNumbers = [];

			if (!empty($d['home_phone'])) {
				$phoneNumbers[] = ['number' => $d['home_phone'], 'type' => 'home'];
			}

			if (!empty($d['work_phone'])) {
				$phoneNumbers[] = ['number' => $d['work_phone'], 'type' => 'work'];
			}
			$contact->phoneNumbers = $phoneNumbers;


			if (!empty($d['address'])) {
				$address = new Address();
				
				$address->street = $d['address'] . ' ' . $d['address_no'];
				$address->zipCode = $d['zip'];
				$address->country = $d['country'];
				$address->city = $d['city'];
				$address->state = $d['state'];

				$contact->addresses = [$address];
			}
			
//			if(!empty($d['original_photo_url'])) {
//				$photoFile = GO()->getAuth()->getTempFolder()->getFile(uniqid(time()));
//				if(!$this->_client->downloadFile(str_replace('/index.php?r=', '', $d['original_photo_url']), $photoFile)) {
//					throw new Exception("Could not download photo file");
//				}
//				$photoFile->setName($this->_client->getLastDownloadedFilename());
//				$contact->photo = $photoFile;
//			}

			if (!$contact->save()) {
				throw new Exception("Could not save contact: " . var_export($contact->getValidationErrors(), true));
//			return false;
			}
		}

		return true;
	}

}
