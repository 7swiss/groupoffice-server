<?php

namespace GO\Modules\GroupOffice\Dav\Model;

use IFW\App,
	GO\Core\Users\Model\User,
	IFW\Orm\Query,
	GO\Modules\GroupOffice\Contacts\Model\Contact,
	Sabre\DAVACL\PrincipalBackend\BackendInterface;

class PrincipalBackend implements BackendInterface {

	private function _modelToDAVUser(User $user) {
		return [
			'id' => $user->id,
			'uri' => 'principals/' . $user->username,
//			'{DAV:}displayname' => $user->username,
			'{http://sabredav.org/ns}email-address' => $user->username.'@group-office.eu',
		];
	}

	/**
	 * Returns a list of principals based on a prefix.
	 *
	 * This prefix will often contain something like 'principals'. You are only 
	 * expected to return principals that are in this base path.
	 *
	 * You are expected to return at least a 'uri' for every user, you can 
	 * return any additional properties if you wish so. Common properties are:
	 *   {DAV:}displayname 
	 *   {http://sabredav.org/ns}email-address - This is a custom SabreDAV 
	 *     field that's actualy injected in a number of other properties. If
	 *     you have an email address, use this property.
	 * 
	 * @param string $prefixPath 
	 * @return array 
	 */
	public function getPrincipalsByPrefix($prefixPath) {

		GO()->debug('GO\DAV\Auth\Backend::getUsers()');

		if (!isset($this->users)) {
			$this->users = array($this->_modelToDAVUser(\GO()->getAuth()->user()));
		}
		return $this->users;
	}

	/**
	 * Returns a specific principal, specified by it's path.
	 * The returned structure should be the exact same as from 
	 * getPrincipalsByPrefix. 
	 * 
	 * @param string $path can be principals/username or 
	 * principals/username/calendar-proxy-write we ignore principals and the 
	 * second element is our username.
	 * @return array 
	 */
	public function getPrincipalByPath($path) {

		GO()->debug("getPrincipalByPath($path)");
		
		$pathParts = explode('/', $path);
		$username = $pathParts[1];
		$user = User::find(['username' => $username])->single();
		
		if (!$user) {
			return false;
		}
		if (isset($pathParts[2])) {
			return [
				'uri' => $path,
				'{DAV:}displayname' => $pathParts[2]
			];
		}
		return $this->_modelToDAVUser($user);
	}

	/**
	 * Returns the list of members for a group-principal 
	 * 
	 * @param string $principal 
	 * @return array 
	 */
	public function getGroupMemberSet($principal) {

		GO()->debug("getGroupMemberSet($principal)");

		return array();
	}

	/**
	 * Returns the list of groups a principal is a member of 
	 * 
	 * @param string $principal 
	 * @return array 
	 */
	public function getGroupMembership($principal) {
		GO()->debug("getGroupMemberSet($principal)");

		return array();
	}

	/**
	 * Updates the list of group members for a group principal.
	 *
	 * The principals should be passed as a list of uri's. 
	 * 
	 * @param string $principal 
	 * @param array $members 
	 * @return void
	 */
	public function setGroupMemberSet($principal, array $members) {
		GO()->debug("setGroupMemberSet($principal)");
	}

	public function updatePrincipal($path, \Sabre\DAV\PropPatch $propPatch) {
		return false;
	}
	
	public function findByUri($uri, $principalPrefix) {
		GO()->debug("findByUri($uri,$principalPrefix)");
	}
	

	public function searchPrincipals($prefixPath, array $searchProperties, $test = 'allof') {

		GO()->debug("searchPrincipals");

		$query = (new Query())
			->select('user.username as username')
			->joinRelation('user', false);

		foreach ($searchProperties as $property => $value) {

			switch ($property) {

				case '{DAV:}displayname' :
					$query->search($value, ['t.name', 'user.username']);
					break;
				case '{http://sabredav.org/ns}email-address' :
					$query->search($value, ['email']);
					break;
				default :
					return array();
			}
		}

		$stmt = Contact::find($query);

		$principals = array();
		while ($record = $stmt->fetch()) {
			$principals[] = $record->username;
		}

		return $principals;
	}

}
