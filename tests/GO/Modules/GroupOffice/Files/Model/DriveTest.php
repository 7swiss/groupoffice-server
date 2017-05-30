<?php
namespace GO\Modules\GroupOffice\Files\Model;

use GO\Core\Users\Model\User;
use GO\Utils\ModuleCase;
use IFW\Orm\Query;


class DriveTest extends ModuleCase {

	function testCrud() {

		// create
		$drive = new Drive();
		$drive->name = 'Test drive';
		$drive->quota = 100 * 1024; // 100k

		$this->assertTrue($drive->save());
		$driveId = $drive->id;

		// read
		$drive = Drive::findByPk($driveId);
		$this->assertNotEmpty($drive);
		$this->assertNotEmpty($drive->root);
		$this->assertTrue($drive->root instanceof Node);
		$this->assertTrue($drive->root->isDirectory);
		$this->assertTrue($drive instanceof Drive);

		// update
		$drive->name = 'New name';
		$this->assertTrue($drive->save());

		// delete
		$drive = Drive::findByPk($driveId);
		$this->assertNotEmpty($drive);

		$this->assertTrue($drive->delete());

		$drive = Drive::findByPk($driveId);
		$this->assertEmpty($drive); // no longer found
   }


	function testListDrive() {
		$all = Drive::find();

		$this->assertEquals(0, $all->getRowCount());

		$drive = new Drive();
		$drive->name = 'Test2 drive';
		$drive->quota = 100 * 1024; // 100k

		$this->assertTrue($drive->save());
		$all = Drive::find();
		$this->assertEquals(1, $all->getRowCount());

		$this->changeUser('henk');
		$all = Drive::find();
		$this->assertEquals(0, $all->getRowCount());
		$this->changeUser('admin');
	}

	function testShareDrive() {

		$henk = $this->getUser('henk');

		$this->assertTrue($henk instanceof User);

		$drive = new Drive();
		$drive->name = 'Test3 drive';
		$drive->quota = 100 * 1024;
		$drive->groups[] = ['groupId' => $henk->group->id, 'write' => true];
		$this->assertTrue($drive->save());

		$driveId = $drive->id;

		$this->changeUser('henk');

		$drive = Drive::findByPk($driveId);
		$this->assertNotEmpty($drive);

		$this->assertFalse($drive->ownedBy == $henk->id);
		//$this->assertTrue($cal->getPermissions()->can('write')); // fails??

		$drive->quota = 200 * 1024;
		$this->assertTrue($drive->save());

		$this->changeUser('admin');
	}

	function testMountDrive() {

		$drive = new Drive();
		$drive->name = 'Test4 drive';
		$drive->quota = 100 * 1024;
		$this->assertTrue($drive->save());

		$henk = $this->getUser('henk');
		$admin = $this->getUser('admin');
		
		$drive->mount($henk->id);
		$this->assertTrue($drive->save());

		$query = (new Query())
				  ->join(Mount::tableName(),'m','t.id = m.driveId AND m.userId = '.$henk->id)
				  ->joinRelation('root')
				  ->where('m.userId = '.$henk->id);
		$mountedDrives = Drive::find($query);
		$this->assertEquals(1, $mountedDrives->getRowCount());

		$query = (new Query())
				  ->join(Mount::tableName(),'m','t.id = m.driveId AND m.userId = '.$admin->id)
				  ->joinRelation('root')
				  ->where('m.userId = '.$admin->id);
		$mountedDrives = Drive::find($query);
		$this->assertEquals(0, $mountedDrives->getRowCount());
	}


}
