<?php
namespace GO\Modules\GroupOffice\Files\Model;

use GO\Core\Users\Model\User;
use GO\Utils\ModuleCase;
use IFW\Orm\Query;


class NodeTest extends ModuleCase {

	private static $adminDrive;
	private static $henkDrive;
	private static $fakeBlob;

	private $fileData = [
		'isDirectory' => false,
		'name' => 'test.txt',
		'type' => 'image/jpeg',
		'size' => (50*1024), // 50k
	];
	private $folderData = [
		'isDirectory' => true,
		'name' => 'some_folder'
	];

	function setUp() {
		if(empty(self::$fakeBlob)) {
			$blob = new \GO\Core\Blob\Model\Blob();
			$blob->blobId = '0000000000000000000000000000000000000000';
			$blob->contentType = 'image/jpeg';
			$blob->name = 'test.txt';
			$blob->size = (50*1024);
			$blob->save();
			self::$fakeBlob = $blob;
			$this->fileData['blobId'] = self::$fakeBlob->blobId;
		}
		if(empty(self::$adminDrive)) {
			$drive = new Drive();
			$drive->name = 'Admin drive';
			$drive->save();
			self::$adminDrive = $drive;
		}
		if(empty(self::$henkDrive)) {
			$this->changeUser('henk');
			$drive = new Drive();
			$drive->name = 'Henk drive';
			$drive->save();
			self::$henkDrive = $drive;
		}

		$this->changeUser('admin'); // make sure we start each test as admin
	}

	private function crud(Drive $drive) {
		//create files and folder
		$file = $drive->newNode();
		$file->setValues($this->fileData);
		$this->assertTrue($file->save());
		$this->assertEquals(50*1024, $drive->usage);
		$this->assertEquals($drive->name.'/'.$this->fileData['name'], $file->path);

		$folder = $drive->newNode();
		$folder->setValues($this->folderData);
		$this->assertTrue($folder->save());
		$this->assertEquals(50*1024, $drive->usage);
		$this->assertEquals($drive->name.'/'.$this->folderData['name'], $folder->path);

		$file2 = $drive->newNode();
		$file2->setValues($this->fileData);
		$file2->setParentId($folder->id);
		$this->assertTrue($file2->save());
		$this->assertEquals(100*1024, $drive->usage);
		$this->assertEquals($drive->name.'/'.$this->folderData['name'].'/'.$this->fileData['name'], $file2->path);

		//read
		$node = Node::findByPk(['id'=>$file->id]);
		$this->assertNotEmpty($node);
		$this->assertTrue($node instanceof Node);
		$this->assertFalse($node->isDirectory);
		$this->assertEquals($this->fileData['name'], $node->name);
		$this->assertEquals($node->driveId, $drive->id);
		$node2 = Node::findByPath($drive->name.'/'.$this->folderData['name'].'/'.$this->fileData['name']);
		$this->assertNotEmpty($node2);
		$this->assertEquals($this->fileData['name'], $node2->name);

		//update
		$node->name = 'test2.txt'; // todo change more values
		$this->assertTrue($node->save());
		$this->assertEquals('test2.txt', $node->name);
		$this->assertEquals($drive->name.'/test2.txt', $node->path);

		//delete
		$id = $file->id;
		$this->assertTrue($file->delete());
		$file = Node::findByPk(['id' => $id]);
		$file->drive = $drive;
		$this->assertTrue($file->deleted);
		$this->assertEquals(100*1024, $drive->usage);
		$this->assertTrue($file->deleteHard());
		$file = Node::findByPk(['id' => $id]);
		$this->assertEmpty($file);
		$this->assertEquals(50*1024, $drive->usage);
		// TODO delete none empty folder?
	}

	function testCrudNode() {
		$this->fileData['parentId'] = self::$adminDrive->rootId;
		$this->folderData['parentId'] = self::$adminDrive->rootId;
		$this->crud(self::$adminDrive);
	}

	function testCrudHenk() {
		$this->changeUser('henk');
		$this->fileData['blobId'] = self::$fakeBlob->blobId;
		$this->fileData['parentId'] = self::$henkDrive->rootId;
		$this->folderData['parentId'] = self::$henkDrive->rootId;
		$this->crud(self::$henkDrive);
		
		$this->changeUser('admin');
	}

}