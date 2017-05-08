<?php

namespace GO\Modules\GroupOffice\Test\Model;


/**
 * The App class is a collection of static functions to access common services
 * like the configuration, reqeuest, debugger etc.
 */
class MainTest extends \GO\Utils\ModuleCase {


	public function testJoinRelation() {
	
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
	}

	

}
