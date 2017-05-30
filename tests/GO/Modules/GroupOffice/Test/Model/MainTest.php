<?php

namespace GO\Modules\GroupOffice\Test\Model;


/**
 * The App class is a collection of static functions to access common services
 * like the configuration, reqeuest, debugger etc.
 */
class MainTest extends \GO\Utils\ModuleCase {
	
	use \GO\Utils\UserTrait;


	public function testJoinRelation() {
		
		$this->changeUser('henk');
	
		$main = new Main();
		$main->name = 'test';
		
		$hasOne = new RelationRecord();
		$hasOne->name = 'test';
		
//		$main->hasOne = $hasOne;
		$success = $main->save();
		$this->assertEquals(true, $success);
		
		
		$mains = Main::find(
						(new \IFW\Orm\Query)
						->joinRelation('hasOne', true, 'LEFT')
						);
		
		foreach($mains as $main) {
			$this->assertEquals(null, $main->hasOne->id);
		}
		
		$main->hasOne = $hasOne;
		$success = $main->save();
		$this->assertEquals(true, $success);
		
		
		$mains = Main::find(
						(new \IFW\Orm\Query)
						->joinRelation('hasOne', true, 'LEFT')
						);
		
		foreach($mains as $main) {
			$this->assertEquals($hasOne->id, $main->hasOne->id);
		}
		
		$hasOne->delete();
		
		
		$mains = Main::find(
						(new \IFW\Orm\Query)
						->joinRelation('hasOne', true, 'LEFT')
						);
		
		foreach($mains as $main) {
			$this->assertEquals(null, $main->hasOne->id);
		}
		
		$this->changeUser('admin');
	}
	
	public function testSetValues() {
		$this->changeUser('henk');
		
		$main = new Main();
		$main->name = 'test 2';
		$main->hasOne = [
			'description' => 'Already set'	
		];
		
		
		$main->hasOne->name = 'test 2';
		
		$this->assertEquals('Already set', $main->hasOne->description);
		
		
		//now with set values
		$main->setValues([
				'hasOne' => [
						'name' => 'test 3'						
				]
		]);
		
		$this->assertEquals('Already set', $main->hasOne->description);
		$this->assertEquals('test 3', $main->hasOne->name);
		
		$this->changeUser('admin');
	}
	
	
	public function testParentRelationReference() {
		
		$this->changeUser('henk');
		
		$main = new Main();
		$main->name = 'test 1';
		$main->hasOne = [
			'name' => 'hasOne 1'			
		];
		
		$main->hasMany = [
			['name' => 'hasMany 1'],
			['name' => 'hasMany 2']
		];
		
		
		$this->assertEquals(spl_object_hash($main->hasMany[0]->main), spl_object_hash($main));
		
		$this->assertEquals(spl_object_hash($main->hasOne->main), spl_object_hash($main));
		
		
		$main->save();
		
		$mainFind = Main::findByPk($main->id);
		
		$this->assertEquals(spl_object_hash($mainFind->hasMany[0]->main), spl_object_hash($mainFind));
		
		$this->assertEquals(spl_object_hash($mainFind->hasOne->main), spl_object_hash($mainFind));
		
		$this->changeUser('admin');
	}

	

}
