<?php

namespace GO\Core\Auth;

use GO\Core\Users\Model\Acl;
use GO\Core\Users\Model\AclRole;
use GO\Core\Users\Model\Permissions;
use GO\Core\Users\Model\Role;
use PHPUnit_Framework_TestCase;

/**
 * The App class is a collection of static functions to access common services
 * like the configuration, reqeuest, debugger etc.
 */
class ParentAclTest extends PHPUnit_Framework_TestCase {
	public function testParentAcl() {
		
//
//		//create parent ACL with read permission for role "Everyone"
//		$everyOneRole = Role::findEveryoneRole();
//		
//		$everyoneAclRole = new AclRole();
//		$everyoneAclRole->permissionType = Permissions::PERMISSION_READ;
//		$everyoneAclRole->roleId = $everyOneRole->id;		
//		
//		$parentAcl = new Acl();
//		$parentAcl->modelName = "\PhpUnitTestParent"; //dummy
//		$parentAcl->roles = [$everyoneAclRole];
//		$success = $parentAcl->save();
//		
//		$this->assertEquals(true, $success);
//		
//		//create child ACL		
//		$childAcl = new Acl();
//		$childAcl->modelName = "\PhpUnitTestChild"; 
//		$childAcl->parents = [$parentAcl];
//		$success = $childAcl->save();
//		
//		$this->assertEquals(true, $success);
//		
//		//now the child ACL should have the everyone role too with the parentAclId set.		
//		$childEveryoneAclRole = $childAcl->roles->single();
//		
//		$this->assertEquals($everyOneRole->id, $childEveryoneAclRole->roleId);		
//		$this->assertEquals($parentAcl->id, $childEveryoneAclRole->parentAclId);
//		
//		
//		//Create new role and add it to the parent. This should also be added to the child
//		$newRole =Role::find(['name' => "Unit test"])->single();
//		
//		if(!$newRole) {
//			$newRole = new Role();
//			$newRole->name = "Unit test";
//			$success = $newRole->save();
//			$this->assertEquals(true, $success);
//		}
//		
//		
//		
//		
//		$newAclRole = new AclRole();
//		$newAclRole->permissionType = Permissions::PERMISSION_READ;
//		$newAclRole->roleId = $newRole->id;		
//		
//		$parentAcl->roles = [$newAclRole];
//		$success = $parentAcl->save();
//		
//		$this->assertEquals(true, $success);
//		
//		
//		//The new role should be in the child as well.
//		$newChildAclRole = $childAcl->roles(['roleId' => $newRole->id])->single();		
//		$this->assertEquals(false, !$newChildAclRole);	
//		$this->assertEquals($parentAcl->id, $newChildAclRole->parentAclId);
//		
//		
//		//now try to remove permissions from the parent		
//		$success = $everyoneAclRole->delete();
//		$this->assertEquals(true, $success);
//		//this roles should be removed from the child too.
//		$everyoneChildAclRole = $childAcl->roles(['roleId' => $everyOneRole->id])->single();		
//		$this->assertEquals(false, $everyoneChildAclRole);	
//		
//		//cleanup
//		$parentAcl->delete();
//		$childAcl->delete();
//		$newRole->delete(true);
		
	}
}