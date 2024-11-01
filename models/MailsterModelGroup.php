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
 * Group Model
 *
 */
class MailsterModelGroup extends MailsterModel
{
	var $_id = null;
	var $_data = null;
	var $_total = null;

	function __construct($id = null){			
		parent::__construct((int)$id);
		$this->_total = null;
	}

	function _initData()
	{
		if (empty($this->_data))
		{
			$item = new stdClass();
			$item->name				= "";
			$item->is_core_user		= false;
			$item->core_group_id	= 0;
			$this->_data = $item;
			return (boolean) $this->_data;
		}
		return true;
	}
	
	public function getTable($type = 'mailster_groups', $prefix = '', $config = array()){
		global $wpdb;
		$table_name = $wpdb->prefix . "mailster_groups";
		return $table_name;
	}

	function getTotal() {
		if ( empty( $this->_total ) ) {
			global $wpdb;
			$query = $this->_buildQueryMemberCount();
			$wpdb->get_results( $query );
            MstFactory::getLogger()->debug('getTotal query: '.$query);
			$this->_total = $wpdb->num_rows;
		}else{
            MstFactory::getLogger()->debug('getTotal Not empty: '.$this->_total);
        }
		return $this->_total;
	}

	function _buildQueryMemberCount() {
		global $wpdb;
		$query =  'SELECT * FROM ' . $wpdb->prefix . 'mailster_group_users where group_id = \''.intval($this->_id).'\'';
		return $query;
	}

	/**
	 * @param $user MailsterModelUser
	 * @return bool
	 */
	public function addUser($user) {
		global $wpdb;
		//check if user is already a member
		$core = "false";
		if($user->isCoreUser()) {
			$core = "true";
		}
		$res = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'mailster_group_users WHERE user_id = ' . $user->getId() . ' AND group_id = ' . $this->_id . ' AND is_core_user = ' . $core);
		if(!$res) {
			$columns = $wpdb->insert(
				$wpdb->prefix . 'mailster_group_users',
				array(
					'group_id' => $this->_id,
					'user_id' => $user->getId(),
					'is_core_user' => $user->isCoreUser()
				),
				array(
					'%d',
					'%d',
					'%d'
				)
			);
			if ($columns) {
				return true;
			} else {
				return false;
			}
		} else {
			return true;
		}
	}

	public function addUserById($user_id, $iscore) {
		global $wpdb;
		$columns = $wpdb->insert( 
			$wpdb->prefix.'mailster_group_users', 
			array( 
				'group_id' => $this->_id, 
				'user_id' => $user_id,
				'is_core_user' => $iscore
			), 
			array( 
				'%d', 
				'%d', 
				'%d' 
			) 
		);
		if ( $columns ) {
			return true;
		}
		return false;
	}
	public function addCoreUser($user_id) {
		global $wpdb;
		$columns = $wpdb->insert( 
			$wpdb->prefix.'mailster_group_users', 
			array( 
				'group_id' => $this->_id, 
				'user_id' => $user_id,
				'is_core_user' => true
			), 
			array( 
				'%d', 
				'%d', 
				'%s' 
			) 
		);
		if ( $columns ) {
			return true;
		} else {
			$result = false;
		}
	}

	public function removeUser( $user ) {
		global $wpdb;
		return $wpdb->delete( 
			$wpdb->prefix.'mailster_group_users',
			array( 
				'group_id' => $this->_id, 
				'user_id' => $user->getId(),
				'is_core_user' => $user->isCoreUser()
			),
			array( 
				'%d', 
				'%d', 
				'%d' 
			)
		);
	}

	public function getAllUsers() {
		global $wpdb;
		$query = "SELECT * FROM " . $wpdb->prefix . "mailster_group_users WHERE group_id=" . $this->_id;
		$result = $wpdb->get_results( $query );
		return $result;
	}

	public function emtpyUsers() {
		global $wpdb;
		return $wpdb->delete( 
			$wpdb->prefix.'mailster_group_users',
			array( 
				'group_id' => $this->_id
			),
			array( 
				'%d' 
			)
		);
	}

	public function getAllGroups() {
		global $wpdb;
		$query = "SELECT * FROM " . $this->getTable() . " ORDER BY name";
		$result = $wpdb->get_results( $query );
		return $result;
	}

	public function getAllMembers() {
		global $wpdb;
		$query = "SELECT * FROM " . $wpdb->prefix . "mailster_group_users WHERE group_id=" . $this->_id;
		return $wpdb->get_results($query, OBJECT);
	}

	/**
	 * Get group by group name
	 */
	function &getGroupByName($name){
		// reset object
		$this->setId(0);
		$this->_initData();
		global $wpdb;
		$query = 'SELECT id'
		         . ' FROM ' . $wpdb->prefix . 'mailster_groups'
		         . ' WHERE name = \''.$name.'\'';
		$groupId = $wpdb->get_var( $query );

		if($groupId && $groupId > 0){
			$this->setId($groupId); // group found
		}

        $data = $this->getData(); // auto-load object
        return $data;
	}

	function getData($id=null)
	{
		if(!is_null($id)){
			$this->setId($id);
		}
		if ($this->_loadData())
		{

		}
		else  $this->_initData();

		return $this->_data;
	}

	function getAllGroupsForm($inclDoNotAddText = true, $inclDoNotRemoveText = false){
		global $wpdb;
        $query = "SELECT id AS value, name AS list_choice FROM " . $this->getTable();
        if($inclDoNotAddText){
            $text = __('- Do not add to a group -', 'wp-mailster');
        }elseif($inclDoNotRemoveText){
            $text = __('- Do not remove from a group -', 'wp-mailster');
        }else{
            $text = null;
        }
		$result = $wpdb->get_results( $query );
        if($text){
            $textObj = new stdClass();
            $textObj->value = 0;
            $textObj->list_choice = $text;
            array_unshift($result , $textObj);
        }
		return $result;
	}

	function countMembers($groupid) {
		global $wpdb;
		$result = 0;
		if($groupid) {
			$query = 'SELECT count(user_id) as userCount FROM ' . $wpdb->prefix . 'mailster_group_users'
			         . ' WHERE group_id =\''. $groupid . '\'';
			$result = $wpdb->get_row( $query );
		}
		return $result->userCount;
	}
}