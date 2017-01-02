<?php
namespace GO\Core\Auth\Oauth2;

use OAuth2\GrantType\UserCredentials;
use OAuth2\Server as Oauth2Server;

class Server extends Oauth2Server{
	public static function newInstance(){
		$storage = new Storage();		
		$server = new self($storage,['allow_implicit' => false]);		
		
		$grantType = new UserCredentials($storage);		
		$server->addGrantType($grantType);
		
		// the refresh token grant request will have a "refresh_token" field
		// with a new refresh token on each request
		$grantType = new OAuth2\GrantType\RefreshToken($storage, array(
			'always_issue_new_refresh_token' => true
		));
		$server->addGrantType($grantType);
		
		return $server;
	}
}