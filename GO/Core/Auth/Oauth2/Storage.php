<?php

namespace GO\Core\Auth\Oauth2;

use GO\Core\Users\Model\User;
use GO\Core\Auth\Oauth2\Model\AccessToken;
use GO\Core\Auth\Oauth2\Model\RefreshToken;
use OAuth2\Storage\AccessTokenInterface;
use OAuth2\Storage\ClientCredentialsInterface;
use OAuth2\Storage\RefreshTokenInterface;
use OAuth2\Storage\UserCredentialsInterface;

class Storage implements AccessTokenInterface, ClientCredentialsInterface, UserCredentialsInterface, RefreshTokenInterface {
	
	public function checkClientCredentials($client_id, $client_secret = null) {
		return $client_id === 'groupoffice-webclient' && $client_secret === null;
	}

	public function checkRestrictedGrantType($client_id, $grant_type) {
		return true;
	}


	public function getClientDetails($client_id) {
		return array(
                    "redirect_uri" => null,      // REQUIRED redirect_uri registered for the client
                    "client_id"    => "groupoffice-webclient",         // OPTIONAL the client id
                    "grant_types"  => "",       // OPTIONAL an array of restricted grant types
                    "user_id"      => "",           // OPTIONAL the user identifier associated with this client
                    "scope"        => "",             // OPTIONAL the scopes allowed for this client
                    );
	}

	public function getClientScope($client_id) {
		return "";
	}
	
	public function isPublicClient($client_id) {
		return $client_id === 'groupoffice-webclient';
	}

	public function setAccessToken($oauth_token, $client_id, $user_id, $expires, $scope = null) {
		
		// convert expires to datestring
        $expires = date('Y-m-d H:i:s', $expires);
		
		$accessToken = AccessToken::findByPk($oauth_token);
		
		if(!$accessToken) {		
			$accessToken = new AccessToken();
			$accessToken->accessToken = $oauth_token;
		}
		
		$accessToken->clientId = $client_id;
		$accessToken->userId = $user_id;
		$accessToken->expires = $expires;
		$accessToken->scope = $scope;
		$accessToken->save();
	}

	public function getAccessToken($oauth_token) {
		$accessToken = AccessToken::findByPk($oauth_token);
		
		$token= [
			'access_token' => $accessToken->refreshToken,
			'client_id' => $accessToken->clientId,
			'user_id' => $accessToken->userId,
			'expires' => strtotime($accessToken->expires),
			'scope' => $accessToken->scope,
			];
		
		return $token;
	}

	public function checkUserCredentials($username, $password) {
		$user = User::find(['username' => $username])->single();
		
		if(!$user){
			return false;
		}
		
		return $user->checkPassword($password);
		
	}

	public function getUserDetails($username) {
		$user = User::find(['username' => $username])->single();
		
		if(!$user){
			return false;
		}
		return ['user_id' => $user->id, 'scope' => null];
	}

	public function getRefreshToken($refresh_token) {
		$refreshToken = RefreshToken::findByPk($refresh_token);
		
		$token= [
			'refresh_token' => $refreshToken->refreshToken,
			'client_id' => $refreshToken->clientId,
			'user_id' => $refreshToken->userId,
			'expires' => strtotime($refreshToken->expires),
			'scope' => $refreshToken->scope,
			];
		
		return $token;
	}

	public function setRefreshToken($refresh_token, $client_id, $user_id, $expires, $scope = null) {
		// convert expires to datestring
        $expires = date('Y-m-d H:i:s', $expires);
		
		$refreshToken = RefreshToken::findByPk($refresh_token);
		
		if(!$refreshToken) {		
			$refreshToken = new RefreshToken();
			$refreshToken->refreshToken = $refresh_token;
		}
		
		$refreshToken->clientId = $client_id;
		$refreshToken->userId = $user_id;
		$refreshToken->expires = $expires;
		$refreshToken->scope = $scope;
		$refreshToken->save();
	}

	public function unsetRefreshToken($refresh_token) {
		$refreshToken = RefreshToken::findByPk($refresh_token);
		
		if($refreshToken) {		
			return $refreshToken->delete();
		}
		
		return true;
	}

}