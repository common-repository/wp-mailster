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

require_once plugin_dir_path( __FILE__ )."../models/MailsterModel.php";
/**
 * Groupusers Model
 *
 */
class MailsterModelGroupusers extends MailsterModel
{
	var $_data = null;
	var $_groupData = null;
	var $_nonMemberData = null;
	var $_pagination = null;
	var $_groupID = null;

	function __construct(){
		parent::__construct();
	}

	function getData($groupID)
	{
		$log = MstFactory::getLogger();
		$this->_groupID = $groupID;
		if (empty($this->_data))
		{
			$query = $this->_buildQuery();
			$this->_data = $this->_getList($query, 0, 0);
			//$log->debug('groupusers->getData() query: '.$query);
		}
		return $this->_data;
	}

	function getAllGroupsUsersOfCoreUserData()
	{
		$orderby = ' ORDER BY gu.user_id';
		$where = ' WHERE gu.is_core_user = \'1\'';
		$query = $this->_buildSelectClause();
		$query = $query	. $where . $orderby;
		return $this->_getList($query, 0, 0);
	}

	/**
	 * Method to get general group data
	 */
	function getGroupData($groupID)
	{
		$this->_groupID = $groupID;
		if (empty($this->_groupData))
		{
			$query = $this->_buildGroupQuery();
			$this->_groupData = $this->_getList($query, 0, 0);
		}
		return $this->_groupData;

	}

	/**
	 * Method to get all users that are not not in the group
	 */
	function getNonMemberData($groupID)
	{
		$this->_groupID = $groupID;
		if (empty($this->_nonMemberData))
		{
			$query = $this->_buildNonMemberQuery();
			$this->_nonMemberData = $this->_getList($query, 0, 0);
		}
		return $this->_nonMemberData;

	}

	/**
	 * Build the Non Member Data query
	 */
	function _buildNonMemberQuery()
	{
		global $wpdb;
		$query = 'SELECT u.id, u.name, u.email, u.notes, FORMAT(0,0) AS is_core_user'
			. ' FROM ' . $wpdb->prefix . 'mailster_users u'
			. ' WHERE u.id NOT IN'
			. ' 	(SELECT gu.user_id'
			. ' 	FROM ' . $wpdb->prefix . 'mailster_group_users gu'
			. ' 	WHERE gu.group_id = \'' . $this->_groupID . '\''
			. ' 	AND gu.is_core_user = \'0\' )';
		$query = $query
			. ' UNION'
			. ' SELECT ju.id, ju.name, ju.email, " ", FORMAT(1,0)'
			. ' FROM ' . $wpdb->base_prefix . 'users ju'
			. ' WHERE ju.id NOT IN'
			. '		(SELECT gu.user_id'
			. ' 	FROM ' . $wpdb->prefix . 'mailster_group_users gu'
			. ' 	WHERE gu.group_id = \'' . $this->_groupID . '\''
			. '		AND gu.is_core_user = \'1\' )'
			. ' ORDER BY name, email';
		return $query;
	}

	/**
	 * Build the Group Data query
	 */
	function _buildGroupQuery()
	{
		global $wpdb;
		$query = 'SELECT g.*'
			. ' FROM ' . $wpdb->prefix . 'mailster_groups g '
			. ' WHERE g.id=\'' . $this->_groupID . '\'';
		return $query;
	}

	function _buildQuery()
	{
		$groupModel = MstFactory::getGroupModel();
		$groupModel->setId($this->_groupID);
		$group = $groupModel->getData();

		$where		= $this->_buildContentWhere();
		$orderby	= $this->_buildContentOrderBy();

		$query = $this->_buildSelectClause();
		$query = $query	. $where;
		$query = $query	. $orderby;
        //MstFactory::getLogger()->debug('MailsterModelGroupusers->_buildQuery query: '.$query);
		return $query;
	}

	function _buildSelectClause(){
		global $wpdb;
		$query = 'SELECT gu.*,'
			. ' CASE WHEN gu.is_core_user = \'1\''
			. ' THEN wpcore.wp_name ELSE u.name END AS name,'
			. ' CASE WHEN gu.is_core_user = \'1\''
			. ' THEN wpcore.user_email ELSE u.email END AS email,'
			. ' CASE WHEN gu.is_core_user = \'1\''
			. ' THEN " " ELSE u.notes END AS notes'
			. ' FROM ' . $wpdb->prefix . 'mailster_group_users gu '
			. ' LEFT JOIN ' . $wpdb->prefix . 'mailster_users u ON (gu.user_id = u.id AND gu.is_core_user = \'0\')'
			. ' LEFT JOIN ('
                    . ' SELECT ID, is_core_user, GROUP_CONCAT(meta_value SEPARATOR \' \') AS wp_name, user_email'
                    . ' FROM  ('
                        . ' SELECT ID, \'1\' AS is_core_user, user_email, meta_value' // all WP users directly linked to list
                        . ' FROM '. $wpdb->base_prefix . 'users ju'
                        . ' LEFT JOIN ' . $wpdb->base_prefix . 'usermeta wpusrmeta ON ( ju.id = wpusrmeta.user_id )'
                        . ' WHERE meta_key IN ( \'first_name\', \'last_name\' )'
                        . ' ORDER BY meta_key ASC'
                        . ' ) DTBL'
                    . ' GROUP BY ID, is_core_user, user_email'
            . ') wpcore ON (gu.user_id = wpcore.ID AND gu.is_core_user = \'1\') '
        ;
		return $query;
	}

	function _buildContentOrderBy()
	{
		$orderby 	= ' ORDER BY name, email, gu.is_core_user';
		return $orderby;
	}

	public function getTable($type = 'mailster_group_users', $prefix = '', $config = array()){
		global $wpdb;
		return $wpdb->prefix . $type;
	}

	function store($data)
	{
		global $wpdb;
		$log		= MstFactory::getLogger();

		if(!property_exists($data, 'group_id')
			|| !property_exists($data, 'user_id')
			|| !property_exists($data, 'is_core_user')
			|| $data->user_id <= 0
		){
			$log->warning('groupusers->store() data not setup okay, will exit because: '.print_r($data, true));
			return false;
		}

		// Delete from table if existing
		$deleteSuccess = $this->delete($data->group_id, array($data->user_id), array($data->is_core_user));

		$dataToStore['group_id'] = $data->group_id;
		$dataToStore['user_id'] = $data->user_id;
		$dataToStore['is_core_user'] = $data->is_core_user;

		$format = array("%d","%d","%d");

		$wpdb->insert($this->getTable(), $dataToStore, $format);
		return true;
	}

	/**
	 * Get all mailing lists that use this group as recipient group
	 */
	function getListsWithGroup($groupID)
	{
		global $wpdb;
		$query = 'SELECT l.*'
			. ' FROM ' . $wpdb->prefix . 'mailster_lists l '
			. ' WHERE l.id IN ('
			. '		SELECT lg.list_id'
			. '		FROM ' . $wpdb->prefix . 'mailster_list_groups lg '
			. '		WHERE lg.group_id=\'' . $groupID. '\''
			. ' )';
		$lists = $this->_getList($query, 0, 0);
		return $lists;
	}

	function _buildContentWhere()
	{
		$where = array();
		$where[] = ' gu.group_id = \'' . $this->_groupID . '\'';
		$where 		= ( count( $where ) ? ' WHERE ' . implode( ' AND ', $where ) : '' );
		return $where;
	}


	/**
	 * Method to remove Users from the group
	 */
	function delete($group_id, $user_ids = array(), $is_core_user_flags = array())
	{
		$log = MstFactory::getLogger();
		global $wpdb;
		$result = false;
		$userCount = count( $user_ids );
		if ($userCount)
		{
			for($i=0; $i < $userCount; $i++)
			{
				$query = 'DELETE FROM ' . $wpdb->prefix . 'mailster_group_users'
					. ' WHERE user_id =\''. $user_ids[$i] . '\''
					. ' AND group_id =\''. $group_id . '\''
					. ' AND is_core_user =\''. $is_core_user_flags[$i] . '\'';

				$errorMsg = '';
				try {
					$result = $wpdb->query( $query );
				} catch (Exception $e) {
					$errorMsg = 'Exception ' . get_class($e) . ' Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
                    $this->setError($errorMsg, 'delete');
                    $log->error($errorMsg." deleting from groupusers");
				}
				if(!$result) {
					return false;
				}
			}
		}

		return true;
	}
}//Class end