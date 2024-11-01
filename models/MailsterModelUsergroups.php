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
 * Mailing List Mails Model
 *
 */
class MailsterModelUserGroups extends MailsterModel
{

	var $_data = null;
	var $_pagination = null;
	var $_userID = null;
	var $_is_core_user = null;

	function __construct(){
		parent::__construct();
	}

	/**
	 * Method to get user groups item data
	 */
	function getData($userID, $is_core_user)
	{
		$groupModel          = MstFactory::getGroupModel();
		$this->_userID       = $userID;
		$this->_is_core_user = $is_core_user;

		if (empty($this->_data))
		{
			$query = $this->_buildQuery();
			$groups = $this->_getList($query, 0, 0);
			$this->_data = array();
			foreach($groups AS $group){
				$groupModel->setId($group->id);
				$this->_data[] = $groupModel->getData();
			}
		}
		return $this->_data;
	}

	function _buildQuery(){
		global $wpdb;
		$where		= $this->_buildContentWhere();
		$orderby	= $this->_buildContentOrderBy();

		$query = 'SELECT g.*'
		         . ' FROM ' . $wpdb->prefix . 'mailster_groups g ';
		$query = $query	. $where;
		$query = $query	. $orderby;
		return $query;
	}

	function _buildContentOrderBy(){
		$orderby 	= ' ORDER BY g.name, g.id';
		return $orderby;
	}

	public function getTable($type = 'mailster_group_users', $prefix = '', $config = array()){
		global $wpdb;
		return $wpdb->prefix.$type;
	}

	function isUserInGroup($userId, $isCoreUser, $groupId){
		$userModel = MstFactory::getUserModel();
		return $userModel->isUserInGroup($userId, $isCoreUser, $groupId);
	}

	function store($groupIds, $userID=null, $is_core_user=null)
	{
		global $wpdb;
		if(is_null($userID)){
			$userID = $this->_userID;
		}
		if(is_null($is_core_user)){
			$is_core_user = $this->_is_core_user;
		}

		$log = MstFactory::getLogger();
		$log->debug('MailsterModelUserGroups:store for groups: '.print_r($groupIds, true).' for user ID '.$userID.', is_core_user '.$is_core_user);
		$log->debug('MailsterModelUserGroups:store First delete current table entries..');

		// Delete from table if existing
		$this->delete($groupIds, $userID, $is_core_user);

		$log->debug('MailsterModelUserGroups:store Now store new table entries');
		for($i=0;$i<count($groupIds);$i++){

			$data = new stdClass();
			$data->group_id = $groupIds[$i];
			$data->user_id = $userID;
			$data->is_core_user = $is_core_user;

			$columns = array();
			$values = array();
			foreach($data as $key => $value) {
				$columns[] = $key;
				$values[] = $value;
			}

			$wpdb->insert($wpdb->prefix . 'mailster_groups', $columns, $values);
		}
		return true;
	}

	function _buildContentWhere()
	{
		global $wpdb;
		$where = array();
		$where[] = ' g.group_id IN ('
		           . ' SELECT DISTINCT group_id FROM ' . $wpdb->prefix . 'mailster_group_users'
		           . ' WHERE user_id = ' . $this->_userID
		           . ' AND is_core_user = ' . $this->_is_core_user
		           . ')';
		$where 		= ( count( $where ) ? ' WHERE ' . implode( ' AND ', $where ) : '' );
		return $where;
	}

	/**
	 * Get all mailing lists that use one of the groups
	 */
	function getListsWithGroups($groupIds)
	{
		global $wpdb;
		if(count($groupIds)){
			$ids = implode( ',', $groupIds );
			$query = 'SELECT l.*'
			         . ' FROM ' . $wpdb->prefix . 'mailster_lists l '
			         . ' WHERE l.id IN ('
			         . '		SELECT DISTINCT lg.list_id'
			         . '		FROM ' . $wpdb->prefix . 'mailster_list_groups lg '
			         . '		WHERE lg.group_id IN ('.$ids.')'
			         . ' )';
			$lists = $this->_getList($query, 0, 0);
		}else{
			$lists = array();
		}
		return $lists;
	}


	/**
	 * Method to remove groups from user
	 */
	function delete($groupIds=array(), $userID=null, $is_core_user=null)
	{
		global $wpdb;
		$log = MstFactory::getLogger();
		$log->debug('usergroups Model->delete, groupIds: '.print_r($groupIds, true).', userId: '.$userID. ', is_core_user: ' . $is_core_user);
		if(is_null($userID)){
			$userID = $this->_userID;
		}
		if(is_null($is_core_user)){
			$is_core_user = $this->_is_core_user;
		}

		$result = false;
		$groupCount = count( $groupIds );

		for($i=0; $i < $groupCount; $i++){
			$query = 'DELETE FROM ' . $wpdb->prefix . 'mailster_group_users'
			         . ' WHERE user_id =\''. $userID . '\''
			         . ' AND group_id =\''. $groupIds[$i] . '\''
			         . ' AND is_core_user =\'' . $is_core_user . '\'';
			$errorMsg = '';
			try {
				$result = $wpdb->query( $query );
			} catch (Exception $e) {
				$errorMsg = 'Exception ' . get_class($e) . ' Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
                $log->error('usergroups Model->delete error: '.$errorMsg);
                $this->setError($errorMsg, 'delete');
			}
			if(!$result) {
				return false;
			}
		}

		return true;
	}
}//Class end
