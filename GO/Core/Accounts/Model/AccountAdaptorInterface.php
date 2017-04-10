<?php
namespace GO\Core\Accounts\Model;

interface AccountAdaptorInterface{
	
	public function getName();	
	
	public static function getInstance(Account $record);
	
	public static function getCapabilities();
}

