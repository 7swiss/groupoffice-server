Notes example module
====================

Read README.md first

Create a "Notes" module with three models:

1.Notebook (id, name, ownerUserId)
2 NotebookRole (see below)
2.Note (id, notebookId, text, ownerUserId, modifiedAt, createdAt)

Notebook has a "has many" relation with model Note.

Notebook has create, read, edit and delete permissions.

We only create an API, not a user interface. You should write a controller action that performs these tasks:

1. Create notebook
2. Create note
3. Delete note.
4. Check permissions


File structure of module in lib/Intermesh/Modules/Notes:

Controller/NotebookController.php
Controller/NoteController.php
Model/Notebook.php
Model/NotebookRole.php
Model/Note.php

Also check the "Contacts" module for code examples.


## Basic controller code. 

The route to this controller action is:

/index.php?r=Intermesh/Notes/Notebook/Test&param1=test

`````````````````````````````````````````````````````````
<?php
namespace Intermesh\Modules\Notes\Controller;

use Intermesh\Core\Controller\AbstractController;

class NotebookController extends AbstractController{
	public function actionTest($param1){

	}
	
}
``````````````````````````````````````````````````````````


## Basic Model

The table that belongs to this model should have a primary key "id" that is an
auto incrementing "int" field and the table name should be "notesNotebook".

``````````````````````````````````````````````````````````````````````````````
<?php
namespace Intermesh\Modules\Notes\Model;

use Intermesh\Core\Db\AbstractRecord;
use Intermesh\Core\Db\RelationFactory;

/**
 * The Notebook model
 *
 * @property int $id
 *
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class Notebook extends AbstractRecord{	
	
	public static function defineRelations(RelationFactory $r){
		//relations go here
	}
}
``````````````````````````````````````````````````````````````````````````````




Creating permissions
====================

See also the API docs: http://intermesh.io/php/docs/class-Intermesh.Modules.Auth.Model.RecordPermissionTrait.html


1. Create an abstractRole model table:

	``````````````````````````````````````````````````````````````````````````````````````````````````
	CREATE TABLE IF NOT EXISTS `notesNotebookRole` (
	  `notebookId` int(11) NOT NULL,
	  `roleId` int(11) NOT NULL,	  
	  `createAccess` tinyint(1) NOT NULL DEFAULT '0',
      `readAccess` tinyint(1) NOT NULL DEFAULT '0',
      `editAccess` tinyint(1) NOT NULL DEFAULT '0',
      `deleteAccess` tinyint(1) NOT NULL DEFAULT '0',
	  PRIMARY KEY (`moduleId`,`roleId`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

	ALTER TABLE `notesNotebookRole` ADD FOREIGN KEY ( `notebookId` ) REFERENCES `go7`.`notesNotebook` (
	`id`
	) ON DELETE CASCADE ON UPDATE RESTRICT ;

	ALTER TABLE `notesNotebookRole` ADD FOREIGN KEY ( `roleId` ) REFERENCES `go7`.`authRole` (
	`id`
	) ON DELETE CASCADE ON UPDATE RESTRICT ;

	``````````````````````````````````````````````````````````````````````````````````````````````````

2.  Create the role model NotebookRole

	````````````````````````````````````````````````````
	<?php
	namespace Intermesh\Modules\Contacts\Model;

	use Intermesh\Modules\Auth\Model\AbstractRole;

	/**
	 * @param int $notebookId
	 * @param int $userId
	 * @param bool $createAccess
	 * @param bool $readAccess
	 * @param bool $editAccess
	 * @param bool $deleteAccess
	 */
	class NotebookRole extends AbstractRole{	
		public static function resourceKey() {
			return 'notebookId';
		}	
	}
	`````````````````````````````````````````````````````


2. Add the trait and required "roles" relation to the notebook model

	``````````````````````````````````````````````````````````````````````````````````````````````````
	<?php
	namespace Intermesh\Modules\Modules\Model;

	use Intermesh\Core\Db\AbstractRecord;
	use Intermesh\Modules\Auth\Model\RecordPermissionTrait;

	class Notebook extends AbstractRecord{
	
		use RecordPermissionTrait;
	
		protected static function defineRelations(\Intermesh\Core\Db\RelationFactory $r) {
			return [

				$r->hasMany('roles', ModuleRole::className(), 'moduleId')
				];
		}
	}
	``````````````````````````````````````````````````````````````````````````````````````````````````


=== Controllers

Now we've got the models setup we can start with the NotebookController. Take a look at the ContactController in the contacts module and create the 
NotebookController with actions for create, read, edit and delete.

Use "postman" to test this controller. You can login with a POST request with username and password to:

/index.php?r=Intermesh/Auth/Auth/login

After that you are authenticated with cookies and you can check permissions too.

To create a notebook you should post json with postman to /index.php?r=intermesh/notes/notebook/create:

{
  "notebook": {
    "attributes": {
    	"name": "My note book"
    }
  }
}

Now do the same for the note controller
