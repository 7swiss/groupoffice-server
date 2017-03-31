<?php

// Use GO
$classLoader = require(dirname(__DIR__)."/vendor/autoload.php");
$app = new \GO\Core\Web\App($classLoader, require(dirname(__DIR__).'/config.php'));
\GO()->getDebugger()->setSection('controller');

use
	Sabre\DAV,
	Sabre\CalDAV,
	Sabre\CardDAV,
	Sabre\DAVACL;

use GO\Modules\GroupOffice\Dav\Model;


// Backends
$authBackend = false ? new Model\DigestAuth() : new Model\BasicAuth();
$principalBackend = new Model\BackendPrincipal();
$calendarBackend = new Model\BackendCalendar();
$addressbookBackend = new Model\BackendAddressbook();

// Directory tree
$nodes = [
   new DAVACL\PrincipalCollection($principalBackend),
   new CalDAV\CalendarRoot($principalBackend, $calendarBackend),
	new Model\BackendFiles('/'),
	new CardDAV\AddressBookRoot($principalBackend, $addressbookBackend)
];

// The object tree needs in turn to be passed to the server class
$server = new DAV\Server($nodes);
$server->setBaseUri('/dav/');
$server->addPlugin(new DAV\Auth\Plugin($authBackend));
$server->addPlugin(new DAV\Browser\Plugin());
$server->addPlugin(new DAV\Sync\Plugin());
$server->addPlugin(new DAV\Sharing\Plugin());
$server->addPlugin(new DAVACL\Plugin());

//caldav
$server->addPlugin(new CalDAV\Plugin());
//$server->addPlugin(new CalDAV\Schedule\Plugin());
//$server->addPlugin(new CalDAV\SharingPlugin());
$server->addPlugin(new CalDAV\ICSExportPlugin());

//carddav
$server->addPlugin(new CardDAV\Plugin());
$server->addPlugin(new CardDAV\VCFExportPlugin());
$server->on('exception', function($e) {
	GO()->debug($e);
});
$server->exec();