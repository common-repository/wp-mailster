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
 * List Groups Model
 *
 */
class MailsterModelListGroups extends MailsterModel
{
	var $_data = null;
	var $_listData = null;
	var $_nonMemberGroupsData = null;
	var $_pagination = null;
	var $_listID = null;

	function __construct(){
		parent::__construct();
	}

	function getData($listID)
	{
		$this->_listID = $listID;
		$groupModel = MstFactory::getGroupModel();
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

	/**
	 * Method to get general list data
	 */
	function getListData($listID)
	{
		$this->_listID = $listID;
		if (empty($this->_listData))
		{
			$query = $this->_buildListQuery();
			$this->_listData = $this->_getList($query, 0, 0);
		}
		return $this->_listData;

	}

	/**
	 * Method to get all groups that are not not in the list
	 */
	function getListsWithGroups($groupIds)
	{
		global $wpdb;
		if(count($groupIds)){
			$ids = implode( ',', $groupIds );
			$query = 'SELECT DISTINCT l.*'
			         . ' FROM ' . $wpdb->prefix . 'mailster_lists l'
			         . ' WHERE l.id IN ('
			         . ' 	SELECT list_id AS id'
			         . ' 	FROM ' . $wpdb->prefix . 'mailster_list_groups lg'
			         . ' 	WHERE lg.group_id IN ('.$ids.')'
			         . ' )';
			$lists = $this->_getList($query, 0, 0);
		}else{
			$lists = array();
		}
		return $lists;
	}

	/**
	 * Method to get all groups that are not not in the list
	 */
	function getNonMemberGroupsData($listID)
	{
		global $wpdb;
		$this->_listID = $listID;
		$groupModel = MstFactory::getGroupModel();
		if (empty($this->_nonMemberGroupsData))
		{
			$query = $query = 'SELECT g.*'
			                  . ' FROM ' . $wpdb->prefix . 'mailster_groups g'
			                  . ' WHERE g.id NOT IN'
			                  . ' 	(SELECT lg.group_id'
			                  . ' 	FROM ' . $wpdb->prefix . 'mailster_list_groups lg'
			                  . ' 	WHERE lg.list_id = \'' . $this->_listID . '\')'
			                  . ' ORDER BY g.name';
			$groups = $this->_getList($query, 0, 0);
			$this->_nonMemberGroupsData = array();
			foreach($groups AS $group){
				$groupModel->setId($group->id);
				$this->_nonMemberGroupsData[] = $groupModel->getData();
			}
		}
		return $this->_nonMemberGroupsData;

	}

	function _buildListQuery()
	{
		global $wpdb;
		$query = 'SELECT l.*'
		         . ' FROM ' . $wpdb->prefix . 'mailster_lists l '
		         . ' WHERE l.id=\'' . $this->_listID . '\'';
		return $query;
	}

	function _buildQuery()
	{
		global $wpdb;
		$where		= $this->_buildContentWhere();
		$orderby	= $this->_buildContentOrderBy();

		$query = 	'SELECT g.*'
		            . ' FROM ' . $wpdb->prefix . 'mailster_groups g'
		            . ' WHERE id in'
		            . ' ( SELECT lg.group_id'
		            . ' FROM ' . $wpdb->prefix . 'mailster_list_groups lg '
		;
		$query = $query	. $where;
		$query = $query	. $orderby;
		return $query;
	}

	function _buildContentOrderBy()
	{
		$orderby 	= ' ORDER BY name';
		return $orderby;
	}

	function _buildContentWhere()
	{
		$where = array();
		$where[] = ' lg.list_id = \'' . $this->_listID . '\'';
		$where 		= ( count( $where ) ? ' WHERE ' . implode( ' AND ', $where ) : '' );
		$where .= ') ';
		return $where;
	}

	public function getTable($type = 'mailster_list_groups', $prefix = '', $config = array()){
		global $wpdb;
		return $wpdb->prefix . $type;
	}

	function store($data)
	{
		global $wpdb;
		$columns = array();
		$values = array();
		foreach($data as $key => $value) {
			$columns[] = $key;
			$values[] = $value;
		}

		$wpdb->insert($wpdb->prefix . 'mailster_digest_queue', $columns, $values);
		return true;
	}

	/**
	 * Method to remove member groups from the list
	 */
	function delete($list_id, $group_ids = array())
	{
		global $wpdb;
		$result = false;
		$groupCount = count( $group_ids );
		if ($groupCount)
		{
			for($i=0; $i < $groupCount; $i++)
			{
				$query = 'DELETE FROM ' . $wpdb->prefix . 'mailster_list_groups'
				         . ' WHERE group_id =\''. $group_ids[$i] . '\''
				         . ' AND list_id =\''. $list_id . '\'';
				$errorMsg = '';
				try {
					$result = $wpdb->query( $query );
				} catch (Exception $e) {
					$errorMsg = 'Exception ' . get_class($e) . ' Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
                    $this->setError($errorMsg, 'delete');
				}
				if(false === $result) {
					return false;
				}
			}
		}
		return true;
	}
}//Class end
