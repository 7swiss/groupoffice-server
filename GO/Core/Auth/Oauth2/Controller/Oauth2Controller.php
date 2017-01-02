<?php

namespace GO\Core\Auth\Oauth2\Controller;

use GO\Core\Auth\Oauth2\Request;
use GO\Core\Auth\Oauth2\Server;
use GO\Core\Controller;

class Oauth2Controller extends Controller{
	
	protected function checkAccess() {
		return true;
	}

	
	public function actionToken(){
		
		// Handle a request for an OAuth2.0 Access Token and send the response to the client		
		$server = Server::newInstance();		
		$server->handleTokenRequest(new Request())->send();
	}

}
