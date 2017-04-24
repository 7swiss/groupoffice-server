<?php

namespace IFW\Orm;

use Exception;
use GO\Core\Accounts\Model\Account;
use GO\Core\Accounts\Model\Capability;
use GO\Modules\GroupOffice\Contacts\Model\Contact;
use PHPUnit\Framework\TestCase;

/**
 * The App class is a collection of static functions to access common services
 * like the configuration, reqeuest, debugger etc.
 */
class PropertyRecordTest extends TestCase {

	/**
	 * 
	 * @expectedException Exception
	 */
	public function testDirectQueryException() {

		$capabilities = Capability::find();
		$capabilities->all();
	}

	public function testAsSubquery() {
		$query = new Query();

		$capabilities = Capability::find(
										(new Query)
														->tableAlias('capabilities')
														->where('capabilities.accountId = t.id')
														->andWhere(['modelName' => Contact::class])
		);

		$query->andWhere(['EXISTS', $capabilities]);


		$accounts = Account::find($query);
		
		$sql = (string) $accounts;
		
		$this->assertStringStartsWith('SELECT', $sql);
	}

}
