<?php

defined('C5_EXECUTE') or die("Access Denied.");

Loader::model('user_list');
Loader::model('page_list');

/**
 * Helper class for the concrete5 itm_ldap package.
 */
class ItmLdapHelper
{
	public function ldapBind($ldapBase)
	{
		$ldap = NewADOConnection('ldap');
		global $LDAP_CONNECT_OPTIONS;
		$LDAP_CONNECT_OPTIONS = array(
			array("OPTION_NAME" => LDAP_OPT_DEREF, "OPTION_VALUE" => 2),
			array("OPTION_NAME" => LDAP_OPT_SIZELIMIT, "OPTION_VALUE" => 100),
			array("OPTION_NAME" => LDAP_OPT_TIMELIMIT, "OPTION_VALUE" => 30),
			array("OPTION_NAME" => LDAP_OPT_PROTOCOL_VERSION, "OPTION_VALUE" => 3),
			array("OPTION_NAME" => LDAP_OPT_ERROR_NUMBER, "OPTION_VALUE" => 13),
			array("OPTION_NAME" => LDAP_OPT_REFERRALS, "OPTION_VALUE" => false),
			array("OPTION_NAME" => LDAP_OPT_RESTART, "OPTION_VALUE" => false)
		);

		if (!$this->hasLdapAuth())
		{
			throw new Exception('LDAP connection failed. Reason: no ldap_auth package found.');
		}
		
		$config = new Config();
		$config->setPackageObject(Package::getByHandle('ldap_auth'));
		if ($config->get('LDAP_HOST') == NULL)
		{
			throw new Exception('LDAP host has not been specified.');
		}

		if (!$ldap->Connect($config->get('LDAP_HOST'), '', '', $config->get($ldapBase)))
		{
			throw new Exception(t('LDAP connection failed!'));
		}
		
		$ldap->SetFetchMode(ADODB_FETCH_ASSOC);

		return $ldap;
	}
	
	/**
	 * Creates a new ADOdb connection and binds LDAP to the staff base given in the
	 * package 'ldap_auth'.
	 * 
	 * @global array $LDAP_CONNECT_OPTIONS will be overwritten.
	 * @return mixed ADOdb connection. 
	 */
	public function ldapBindStaff()
	{
		return $this->ldapBind('LDAP_BASE_STAFF');
	}
	
	/**
	 * Creates a new ADOdb connection and binds LDAP to the students base given in the
	 * package 'ldap_auth'.
	 * 
	 * @global array $LDAP_CONNECT_OPTIONS will be overwritten.
	 * @return mixed ADOdb connection. 
	 */
	public function ldapBindStudents()
	{
		return $this->ldapBind('LDAP_BASE_STUDENTS');
	}

	public function ldapBindGroups()
	{
		return $this->ldapBind('LDAP_BASE_GROUPS');
	}
	
	/**
	 * Resolves a LDAP user with uid $uid.
	 * Optionally $ldapBind can be used as a custom ADOdb connection.
	 * 
	 * @param string $uid the LDAP uid (no fully qualified name)
	 * 
	 * @return array assoc. array of LDAP user data or null if not found.
	 */
	public function getLdapUser($uid)
	{
		$users = $this->getLdapStaff();
		
		foreach ($users as $user)
		{
			if ($user['uid'] == $uid)
			{
				return $user;
			}
		}
		
		return null;
	}

	/**
	 * Inserts or updates a user from LDAP server in the concrete5 database.
	 * 
	 * @param string $ldapUser record of the LDAP user.
	 */
	public function addUserFromLdap($ldapUser)
	{
		$group = Group::getByName('ldap');
		if (empty($group))
		{
			throw new Exception("Required group named 'ldap' not found. Update process aborted.");
		}
		$ldapGID = $group->getGroupID();
		
		$group = Group::getByName($ldapUser['cn']);
		if (empty($group))
		{
			throw new Exception(sprintf("Required group named '%s' was not found. Update process aborted.", $ldapUser['cn']));
		}
		$cnGID = $group->getGroupID();
		
		// by now put all users to the Administrators group
		// later on it might be useful to costumize this
		$group = Group::getByName('Administrators');
		$adminGID = $group->getGroupID();

		$userInfo = UserInfo::getByUserName($ldapUser['uid']);

		$data = array(
			'uName' => $ldapUser['uid'],
			'uPassword' => '',
			'uEmail' => $ldapUser['mail'],
			'uIsActive' => 1
		);

		if (empty($userInfo))
		{

			$userInfo = UserInfo::add($data);
		}
		else
		{
			$userInfo->update($data);
		}

		if (empty($userInfo))
		{
			throw new Exception('Updating LDAP user in concrete5 database failed. Update process aborted.');
		}

		$userInfo->updateGroups(array($ldapGID, $adminGID, $cnGID));
		
		$this->setAttr($userInfo, $ldapUser['telephoneNumber'], 'telephone_number');
		$this->setAttr($userInfo, $ldapUser['facsimileTelephoneNumber'], 'telefax_number');
		$this->setAttr($userInfo, $ldapUser['description'], 'description');
		$this->setAttr($userInfo, $ldapUser['roomNumber'], 'room_number');
		$this->setAttr($userInfo, $ldapUser['gecos'], 'name');
		if (strlen($ldapUser['displayName']))
		{
			$this->setAttr($userInfo, $ldapUser['displayName'], 'name');
		}
		$this->setAttr($userInfo, $ldapUser['skypeNumber'], 'skype_number');
		$this->setAttr($userInfo, $ldapUser['icqNumber'], 'icq_number');
		$this->setAttr($userInfo, $ldapUser['title'], 'title');
	}

	private function setAttr($userInfo, $ldapVal, $c5Key)
	{
		try
		{
			$userInfo->setAttribute($c5Key, $ldapVal);
		}
		catch (Exception $e)
		{
			throw new Exception(t('Attribute not found while updating users:') . " $c5Key");
		}
	}

	/**
	 * Generates intersection of the given sets (depending on the user name).
	 * Defined by: $set1 \cap $set2
	 * 
	 * @param array $set1 array of users (UserInfo object, ItmLdapUserTuple
	 *                    object or LDAP entry array).
	 * @param array $set2 array of users (UserInfo object, ItmLdapUserTuple
	 *                    object or LDAP entry array).
	 * 
	 * @return array assoc. array of intersected elements.
	 */
	public function intersect($set1, $set2)
	{
		$resultSet = array();
		foreach ($set1 as $set1Item)
		{
			$set1ItemUName = $this->resolveUsername($set1Item);
			foreach ($set2 as $set2Item)
			{
				$set2ItemUName = $this->resolveUsername($set2Item);
				if ($set1ItemUName == $set2ItemUName)
				{
					$resultSet[$set1ItemUName] = new ItmLdapUserTuple($set1Item, $set2Item);
					break;
				}
			}
		}

		return $resultSet;
	}

	/**
	 * Given a UserInfo object, ItmLdapUserTuple object or LDAP entry array,
	 * this method determines the user name.
	 * 
	 * @param UserInfo|ItmLdapUserTuple|array $item UserInfo object,
	 *                                        ItmLdapUserTuple object or LDAP
	 *                                        entry array
	 * 
	 * @return string the user name of the given object.
	 */
	public function resolveUsername($item)
	{
		if ($item instanceof UserInfo)
		{
			return $item->getUserName();
		}
		elseif ($item instanceof ItmLdapUserTuple)
		{
			return $this->resolveUsername($item->first);
		}
		else
		{
			return $item['uid'];
		}
	}

	/**
	 * Generates subtraction of the given sets (depending on the user name).
	 * Defined by: $set1 \ $set2
	 * 
	 * @param array $set1 array of users (UserInfo object, ItmLdapUserTuple
	 *                    object or LDAP entry array).
	 * @param array $set2 array of users (UserInfo object, ItmLdapUserTuple
	 *                    object or LDAP entry array).
	 * 
	 * @return array assoc. array of subtracted elements.
	 */
	public function subtract($set1, $set2)
	{
		$resultSet = array();
		foreach ($set1 as $set1Item)
		{
			$found = false;
			$set1ItemUName = $this->resolveUsername($set1Item);
			foreach ($set2 as $set2Item)
			{
				$set2ItemUName = $this->resolveUsername($set2Item);
				$uName = $this->resolveUsername($set1Item);
				if ($set1ItemUName == $set2ItemUName)
				{
					$found = true;
					break;
				}
			}

			if (!$found)
			{
				$resultSet[$set1ItemUName] = $set1Item;
			}
		}

		return $resultSet;
	}

	/**
	 * Determines all LDAP users currently available via the LDAP server.
	 * 
	 * @param mixed $ldapBind default value is false. By giving a ADOdb
	 *                        connection, this one will be used to resolve the
	 *                        users. Otherwise a new binding is created.
	 * 
	 * @return array array of LDAP entries (which are in turn assoc. arrays).
	 */
	public function getLdapStaff()
	{
		$ldapStaff = $this->ldapBindStaff();
		$ldapStudents = $this->ldapBindStudents();
		$ldapGroups = $this->ldapBindGroups();
		
		if (!$ldapStaff || !$ldapGroups || !$ldapStudents)
		{
			throw new Exception(t('LDAP connection failed!'));
		}

		//$ldapStaffResult = $ldapStaff->GetArray('(uid=*)');
		$ldapGroupsResult = $ldapGroups->GetArray('(cn=c5-*)');
		
		$result = array();
		foreach ($ldapGroupsResult as $ldapGroup)
		{
			for ($i = 0; $i < count($ldapGroup['memberUid']); $i++)
			{
				$tmpRes = $ldapStaff->GetArray(sprintf('(uid=%s)', $ldapGroup['memberUid'][$i]));
				if (!count($tmpRes))
				{
					$tmpRes = $ldapStudents->GetArray(sprintf('(uid=%s)', $ldapGroup['memberUid'][$i]));
				}
				
				if (count($tmpRes))
				{
					$tmpRes[0]['cn'] = $ldapGroup['cn'];
					$result[] = $tmpRes[0];
				}
			}
		}
		
		$ldapStaff->Close();
		$ldapGroups->Close();

		return $result;
	}

	/**
	 * Determines all LDAP users currently stored in the concrete5 database.
	 * It means every user is fetched which matches the 'ldap' group
	 * membership.
	 * 
	 * @return array array of UserInfo objects. 
	 */
	public function getLdapStaffFromC5()
	{
		$group = Group::getByName('ldap');
		$gId = $group->getGroupID();

		$userList = new UserList();
		$userList->sortBy('uName', 'asc');
		$userList->showInactiveUsers = true;
		$userList->showInvalidatedUsers = true;
		$userList->filterByGroupID($gId);

		return $userList->get();
	}

	/**
	 * Takes two associative arrays and merges them. If two keys clash, the
	 * value of $arr2 overwrites the value of $arr1. There is no return value,
	 * $arr1 is passed as a reference and will be manipulated.
	 * 
	 * @param mixed $arr1 the first assoc. array.
	 * @param mixed $arr2 the secons assoc. array.
	 */
	public function mergeAssocArray(&$arr1, &$arr2)
	{
		foreach ($arr2 as $key => $value)
		{
			$arr1[$key] = $value;
		}
	}

	/**
	 * Checks whether packacge ldap_auth is available.
	 * 
	 * @return bool true if package exists, else false.
	 */
	public function hasLdapAuth()
	{
		$ldapAuthPkg = Package::getByHandle('ldap_auth');
		return!empty($ldapAuthPkg);
	}
	
	/**
	 * Searches all pages which are derived from itm_ldap_user_page page type
	 * and returns the page link if there exist an itm_ldap_user block that
	 * corresponds to the given user name.
	 * 
	 * @param string $uName user name
	 * @return string URL of the page. 
	 */
	public function getUserPageLink($uName)
	{
		$nh = Loader::helper('navigation');
		$pl = new PageList();
		$pl->ignoreAliases();
		$pl->ignorePermissions();
		$pl->filterByCollectionTypeHandle('itm_ldap_user_page');
		$collections = $pl->get();
		foreach ($collections as $collection)
		{
			$blocks = $collection->getBlocks();
			foreach ($blocks as $block)
			{
				$bCtrl = $block->getController();
				if ($bCtrl instanceof ItmLdapUserBlockController)
				{
					if ($bCtrl->uName == $uName)
					{
						return $nh->getLinkToCollection($collection);
					}
				}
			}
		}
		
		return false;
	}
	
	/**
	 * Checks whether there is a title available and - if true - returns a
	 * combination of title plus name. If no title is available, merely the name
	 * will be returned.
	 * 
	 * @param UserInfo $userInfo user data
	 * @return string name
	 */
	public function getFullName($userInfo)
	{
		if (!($userInfo instanceof UserInfo))
		{
			return '';
		}
		$title = $userInfo->getAttribute('title');
		$name = $userInfo->getAttribute('name');
		return empty($title) ? $name : "$title $name";
	}

}

/**
 * Represents a tuple consisting of two elements. This class is intended to be
 * used to hold both LDAP server and concrete5 DB data.
 */
class ItmLdapUserTuple
{
	/**
	 * @var UserInfo|array first element. Array should be a LDAP result.
	 */
	public $first;

	/**
	 * @var UserInfo|array second element. Array should be a LDAP result.
	 */
	public $second;

	/**
	 * Constructs a tuple with default values.
	 * 
	 * @param UserInfo|array $first first element. Array should be a LDAP
	 *                              result.
	 * @param UserInfo|array $second second element. Array should be a LDAP
	 *                               result.
	 */
	public function __construct($first, $second)
	{
		$this->first = $first;
		$this->second = $second;
	}

}