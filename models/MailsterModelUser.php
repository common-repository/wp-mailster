<?php
	/**
	 * @copyright (C) 2016 - 2024 Holger Brandt IT Solutions
	 * @license GNU/GPL, see license.txt
	 * WP Mailster is free software; you can redistribute it and/or
	 * modify it under the terms of the GNU General Public License 2
	 * as published by the Free Software Foundation.
	 * 
	 * WP Mailster is distributed in the hope that it will be useful,
	 * but WITHOUT ANY WARRANTY; without even the implied warranty of
	 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	 * GNU General Public License for more details.
	 * 
	 * You should have received a copy of the GNU General Public License
	 * along with WP Mailster; if not, write to the Free Software
	 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA
	 * or see http://www.gnu.org/licenses/.
	 */

if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
    die('These are not the droids you are looking for.');
}
/**
 * Mailing List Model
 *
 * @package cms
 * @subpackage Mailster
 */
class MailsterModelUser extends MailsterModel
{
	var $_id = null;
	var $_data = null;
	var $_isCore = false;

	function __construct($id = null, $is_core_user = false){
        parent::__construct();
        if(!is_null($id) && intval($id)>0){
            $this->getUserData(intval($id), $is_core_user);
        }
	}

	function _initData()
	{
		if (empty($this->_data))
		{
			$item = new stdClass();
			$item->name		= null;
			$item->email	= null;
			$item->notes	= null;
			$item->is_core_user = 0;
			$this->_data = $item;
			return (boolean) $this->_data;
		}
		return true;
	}
	
	public function getTable($type = 'mailster_users', $prefix = '', $config = array()){
		global $wpdb;
		$table_name = $wpdb->prefix . "mailster_users";
		return $table_name;
	}

	public function setUserData($userId, $username, $email, $is_core, $notes ) {
		$item = new stdClass();
			
		$item->name		= $username;
		$item->email	= $email;
		$item->notes	= $notes;

		$this->_id = $userId;
		$this->_isCore = $is_core;
		$this->_data = $item;
		return (boolean) $this->_data;
	}

	public function getUserData( $userId, $is_core_user = false ) {
		$log = MstFactory::getLogger();
		$row = null;
		if ( $is_core_user === false || $is_core_user === 0 || $is_core_user === "0" ) {
            $log->debug('getUserData for user_id '.$userId.', is_core_user '.$is_core_user.' --> NON core load');
			$this->setId($userId, true);
			$rows = $this->_data;
			if(is_array($rows) && count($rows) >= 1){
			    //$log->debug('getUserData '.print_r($rows, true));
                $row = $rows[0];
            } else {
				$row = $rows;
			}
			if ( is_object($row) && !property_exists($row, 'description') ) {
				$row->description = $row->notes;
			}
            if ( is_object($row) && !property_exists($row, 'is_core_user') ) {
                $row->is_core_user = 0;
            }
		} else {
            $log->debug('getUserData for user_id '.$userId.', is_core_user '.$is_core_user.' --> core user load');
		    $this->setId($userId);
		    $this->_isCore = 1;
			$row = $this->getCoreUserData($userId);
            $row->is_core_user = 1;
			if ( is_object($row) && !property_exists($row, 'description') ) {
				$row->description = '';
				$subscrUtils = MstFactory::getSubscribeUtils();
				$mstUserInfo = $subscrUtils->getUserByEmail($row->email, true, 0); // try to find non-cms user entry (= in Mailster's user data) to get description/notes info
				if($mstUserInfo && $mstUserInfo->user_found){
					$log->debug('getUserData for user_id '.$userId.', is_core_user '.$is_core_user.' - although WP User, can complete info with Mailster user data as well: '.print_r($mstUserInfo, true));
					$row->description = $mstUserInfo->description;
				}
			}
		}
		$log->debug('getUserData for user_id '.$userId.', is_core_user '.$is_core_user.' -> res: '.print_r($row, true));
		return $row;
	}
	
	//get CMS user as a Mailster user
	public function getCoreUserData($userId) {
        $log = MstFactory::getLogger();
        /** @var WP_User $userObj */
        $userObj = get_userdata( $userId );
        $item = new stdClass();
        $item->id = $userId;
        $item->name = '';
        $item->email = '';
        $item->notes = '';
        $item->user_found = false;
        if($userObj){
            $item->user_found = true;
            $description = get_user_meta($userId, 'description', true);

            if(property_exists($userObj, 'data') && property_exists($userObj->data, 'user_email')){
                $userObj = $userObj->data;
            }

            $item->name		= (property_exists($userObj, 'display_name') && $userObj->display_name && strlen(trim($userObj->display_name)) > 0) ? $userObj->display_name : ($userObj->first_name . ' ' . $userObj->last_name);
            $item->email	= $userObj->user_email;
            $item->notes	= $description;
            //MstFactory::getLogger()->debug('getCoreUserData for userId '.$userId.' from WP: '.print_r($userObj, true).', result: '.print_r($this->_data, true));
        }
        $this->_id = $userId;
        $this->_data = $item;
        $log->debug('getCoreUserData userId '.$userId.': '.print_r($this->_data, true));
		return $this->_data;
	}
	
	//get user from group??
	function getGroupMemberInfo($userId, $isCoreUser, $filterOutCoreGroups = true) {
		global $wpdb;
		$query = 	'SELECT *, 1 AS is_group_member '
			. ' FROM ' . $wpdb->prefix . 'mailster_groups'
			. ' WHERE id IN ('
				. ' SELECT group_id'
				. ' FROM ' . $wpdb->prefix . 'mailster_group_users'
				. ' WHERE user_id=\'' . $userId . '\' AND is_core_user=\'' . $isCoreUser . '\''
			. ')'
			. ($filterOutCoreGroups ?' AND is_core_group = \'0\'' : '') // FILTER OUT cms GROUPS
			. ' UNION '
			. 'SELECT *, 0 AS is_group_member '
			. ' FROM ' . $wpdb->prefix . 'mailster_groups'
			. ' WHERE id NOT IN ('
				. ' SELECT group_id'
				. ' FROM ' . $wpdb->prefix . 'mailster_group_users'
				. ' WHERE user_id=\'' . $userId . '\' AND is_core_user=\'' . $isCoreUser . '\''
			. ')'
			. ($filterOutCoreGroups ?' AND is_core_group = \'0\'' : '') // FILTER OUT cms GROUPS
			. ' ORDER BY name';
		$results = $wpdb->get_results($query);
		return $results;
	}

	//get user from list??
	function getListMemberInfo($userId, $isCoreUser = 0) {
		global $wpdb;
		$query = 	'SELECT *, 1 AS is_list_member '
			. ' FROM ' . $wpdb->prefix . 'mailster_lists'
			. ' WHERE id IN ('
				. ' SELECT list_id'
				. ' FROM ' . $wpdb->prefix . 'mailster_list_members'
				. ' WHERE user_id=\''.$userId . '\' AND is_core_user=\''.$isCoreUser . '\''
			. ')'
			. ' UNION '
			. 'SELECT *, 0 AS is_list_member '
			. ' FROM ' . $wpdb->prefix . 'mailster_lists'
			. ' WHERE id NOT IN ('
				. ' SELECT list_id'
				. ' FROM ' . $wpdb->prefix . 'mailster_list_members'
				. ' WHERE user_id=\''.$userId . '\' AND is_core_user=\''.$isCoreUser . '\''
			. ')'
			. ' ORDER BY name';
			$results = $wpdb->get_results($query);
			return $results;
		}
		
    //get all the groups the specific user belongs to
    function getGroupsOfUser($userId, $isCoreUser) {
        /** @var MailsterModelGroup $groupModel */
        $groupModel = MstFactory::getGroupModel();
        global $wpdb;
        $query = 'SELECT * FROM ' . $wpdb->prefix . 'mailster_groups WHERE id IN ('
                . ' SELECT group_id FROM ' . $wpdb->prefix . 'mailster_group_users'
                . ' WHERE user_id=\'' . $userId . '\''
                . ' AND is_core_user=\'' . $isCoreUser . '\''
                . ')';

        $rawGroups = $wpdb->get_results($query);
        $groups = array();
        foreach($rawGroups AS $rawGroup) {
            $groupModel->setId($rawGroup->id);
            $groups[] = $groupModel->getData();
        }
        return $groups;
    }

    //check if the user is member of a specific group
    function isUserInGroup($userId, $isCoreUser, $groupId) {
        if($userId) {
            global $wpdb;
            $query = "SELECT * FROM " . $wpdb->prefix . "mailster_group_users WHERE user_id = " . $userId . " AND group_id = " . $groupId . " AND is_core_user = ". $isCoreUser ;
            $result = $wpdb->get_results( $query );
            return (boolean) $result;
        } else {
            return false;
        }
    }

    function isUserInList($userId,  $isCoreUser, $listId) {
        if($userId) {
            global $wpdb;
            $query = "SELECT * FROM " . $wpdb->prefix . "mailster_list_members WHERE user_id = " . $userId . " AND list_id = " . $listId . " AND is_core_user = ". $isCoreUser ;
            $result = $wpdb->get_results( $query );
            return (boolean) $result;
        } else {
            return false;
        }
    }

    /**
     * Get all groups/lists where user is member of
     */
    function getMemberInfo($userId, $isCoreUser) {
        global $wpdb;
        $memberInfo = array();

        $groups = $this->getGroupsOfUser($userId, $isCoreUser);

        $query = 'SELECT * FROM ' . $wpdb->prefix . 'mailster_lists WHERE id IN ('
                    . ' SELECT list_id FROM ' . $wpdb->prefix . 'mailster_list_members'
                    . ' WHERE user_id=\'' . $userId . '\''
                    . ' AND is_core_user=\'' . $isCoreUser . '\''
                    . ')';
        $lists = $wpdb->get_results($query);

        $query = 'SELECT * FROM ' . $wpdb->prefix . 'mailster_lists WHERE id IN ('
                    . ' SELECT list_id FROM ' . $wpdb->prefix . 'mailster_list_groups WHERE group_id IN ('
                        . ' SELECT group_id FROM ' . $wpdb->prefix . 'mailster_group_users'
                        . ' WHERE user_id=\'' . $userId . '\''
                        . ' AND is_core_user=\'' . $isCoreUser . '\''
                        . ')'
                    . ')';

        $listGroups = $wpdb->get_results($query);

        $memberInfo['groups'] = $groups;
        $memberInfo['lists'] = $lists;
        $memberInfo['listGroups'] = $listGroups;
        return $memberInfo;
    }

	/**
	 * Check for duplicate users
	 *
	 * @param $email string
	 * @param bool $checkCMSUsersToo
	 *
	 * @return array|null|object
	 */
    function isDuplicateEntry($email, $checkCMSUsersToo=true) {
        global $wpdb;
        $email = trim(strtolower($email));
        if($checkCMSUsersToo) {
            $query = 'SELECT *'
            . ' FROM ' . $wpdb->base_prefix . 'users'
            . ' WHERE LOWER(user_email) = \''.$email.'\'';
            $user = $wpdb->get_row($query);

            if( $user ) {
                $user->is_core_user = 1;
                return $user;
            }
        }

        $query = 'SELECT *'
                . ' FROM ' . $this->getTable()
                . ' WHERE LOWER(email) = \'' . $email . '\'';
        $user = $wpdb->get_row($query);

        if ( $user ) {
            $user->is_core_user = 0;
            return $user;
        }
        return null;
    }

    //adds the current user to the specified group
    public function addToGroup( $groupid ) {
        $group = new MailsterModelGroup( $groupid );
        $result = $group->addUser( $this );
        return $result;
    }

    //removes the current user from the specified group
    public function removeFromGroup( $groupid ) {
        $group = new MailsterModelGroup( $groupid );
        $result = $group->removeUser( $this );
        return $result;
    }

    //adds the current user to the specified list
    public function addToList( $listid ) {
        $group = new MailsterModelList( $listid );
        $result = $group->addUser( $this );
        return $result;
    }

    //removes the current user from the specified list
    public function removeFromList( $listid ) {
        $group = new MailsterModelList( $listid );
        $result = $group->removeUser( $this );
        return $result;
    }

    public function isCoreUser() {
        return $this->_isCore;
    }

    public function getAllUsers() {
        global $wpdb;
        return $wpdb->get_results('
            SELECT * FROM (
                SELECT id as uid, CONVERT(name USING utf8mb4) as Name, CONVERT(email USING utf8mb4) as Email, 0 as is_core_user
                FROM ' . $wpdb->prefix . 'mailster_users
                UNION ALL
                SELECT uid, CONVERT(IF(Name IS NULL OR Name = \'\', display_name, Name) USING utf8mb4) AS Name, CONVERT(Email USING utf8mb4) as Email, is_core_user FROM (
                    SELECT uid, display_name, GROUP_CONCAT(meta_value SEPARATOR \' \') AS Name, Email, is_core_user FROM (
                        SELECT ID as uid, display_name, user_email as Email, 1 as is_core_user, meta_value
                        FROM ' . $wpdb->base_prefix . 'users wpusr
                        LEFT JOIN ' . $wpdb->base_prefix . 'usermeta wpusrmeta ON ( wpusr.id = wpusrmeta.user_id )
                        AND meta_key IN ( \'first_name\', \'last_name\' )
                        ORDER BY meta_key ASC
                    ) DTBL GROUP by uid, display_name, Email, is_core_user
                ) DTBLWPCORE
            ) RES
            ORDER BY Name, Email'
        );
    }

    public function getAllWpUsers() {
        global $wpdb;
        return $wpdb->get_results('SELECT uid, display_name, GROUP_CONCAT(meta_value SEPARATOR \' \') AS Name, Email, is_core_user FROM (
                SELECT ID as uid, display_name, user_email as Email, 1 as is_core_user, meta_value
                FROM ' . $wpdb->base_prefix . 'users wpusr
                LEFT JOIN ' . $wpdb->base_prefix . 'usermeta wpusrmeta ON ( wpusr.id = wpusrmeta.user_id )
                AND meta_key IN ( \'first_name\', \'last_name\' )
                ORDER BY meta_key ASC
            ) DTBL GROUP by uid, display_name, Email, is_core_user');
    }

}