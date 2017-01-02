GroupOffice REST API Server
===========================

Read more about installing and development on [http://intermesh.io](http://intermesh.io)


TODO:

1. Framework/Platform doorspreken
  - SQL Select, insert en update DAO


App->dbConnection->createCommand()
	->insert(['name'=>'piet'])
	->table('tabel')
	->execute();


Contact::find(new Query()->limit(1)-where(['od'=>1]))


(new Query())
	->select('*')
	->table('tabel')
	->join('tabel2 t2',['t.id'=>'t2.theireid'] )
	->execute();

(new Command())
->update
->insert
->delete
->select

App->dbConnection->command(
	new Query->select('*')->table('tabel')
	)->execute();



3. Client builder
4. Web client doorspreken
5. PV's maken
6. Uitvoeren modules

Elke donderdag en vrijdag GO7







Interface algemeen:
- Empty states
- Interface in geheel onder de loep
- Launcher scherm
- Swipe to delete
- Multiselect

Core
- Change password
- SSO Group-Office 6

Projects
	- Add comments to proposal

E-mail
	- Move to folder (touch menu en drag and drop)

Contacts
	-	CardDav

Tasks
	- 

Reports
 - Projecten gantt



Later:

Time tracking
	- Automatic entries on task completion

Billing

Notes

Projects
	- Proposal versioning

Agenda 
	- Geheel
	- Caldav
	- Import	
	- Export

Files
	- Webdav
	- Folders in webclient

Timeline of all actions (eg. Wesley created project X)

Contacts
	- Contracts
	- Import
	- Export



VOIP integration

Postfix integration

Server manager