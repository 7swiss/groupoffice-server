<?php

namespace GO\Modules\GroupOffice\Dav\Controller;

use IFW;
use GO\Core\Controller;
use GO\Modules\GroupOffice\Dav\Model\Addressbooks;
use GO\Modules\GroupOffice\Dav\Model\BasicAuth;
use GO\Modules\GroupOffice\Dav\Model\Principal;
use Sabre\CardDAV\AddressBookRoot;
use Sabre\CardDAV\Plugin as CardDavPlugin;
use Sabre\DAV\Auth\Plugin as AuthPlugin;
use Sabre\DAV\Browser\Plugin as BrowserPlugin;
use Sabre\DAV\Server;
use Sabre\DAVACL\Plugin as ACLPlugin;
use Sabre\DAVACL\PrincipalCollection;

class ServerController extends Controller {
	
	public function checkAccess() {
		return true;
	}

	public function dav() {
		// Backends
		$authBackend = new BasicAuth();
		$principalBackend = new Principal();
		$carddavBackend = new Addressbooks();

// Authentication plugin
		$authPlugin = new AuthPlugin($authBackend, GO()->getConfig()->productName);

// The object tree needs in turn to be passed to the server class
		$server = new Server([
				new PrincipalCollection($principalBackend),
				//new CalDAV\CalendarRootNode($principalBackend, $calendarBackend),
				new AddressBookRoot($principalBackend, $carddavBackend),
		]);

		$baseUri = GO()->getRouter()->buildUrl('dav', [], false);// strpos($_SERVER['REQUEST_URI'], 'index.php') ? \GO::config()->host . '/lib/GO/Modules/Dav/index.php/' : '/carddav/';
		$server->setBaseUri($baseUri);
		$server->addPlugin($authPlugin);
//$server->addPlugin(new CalDAV\Plugin());
		$server->addPlugin(new CardDavPlugin());
		$server->addPlugin(new BrowserPlugin());
		$server->addPlugin(new ACLPlugin());

		$server->debugExceptions = true;

// And off we go!
		$server->exec();
	}

}
