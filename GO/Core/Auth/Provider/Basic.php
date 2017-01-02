<?php
namespace GO\Core\Auth\Provider;

use GO\Core\Users\Model\User;

class Basic implements AuthenticationProviderInterface{
	public function getUser(){
		
		if(empty($_SERVER['PHP_AUTH_USER']) || empty($_SERVER['PHP_AUTH_PW'])){
			return false;
		}
		
		$user = User::find(['username' => $_SERVER['PHP_AUTH_USER']])->single();
		if(!$user || !$user->checkPassword($_SERVER['PHP_AUTH_PW'])){
			return false;
		}
		
		return $user;
	}
}