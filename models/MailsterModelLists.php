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
 * Mailing Lists Model
 *
 */
class MailsterModelLists extends MailsterModel
{
	var $_data = null;
	var $_total = null;
	var $_pagination = null;

	function __construct(){
		parent::__construct();
	}

	function getData()
	{
		if (empty($this->_data))
		{
			$query = $this->_buildQuery();
			$this->_data = $this->_getList($query, 0, 0);
			$this->_data = $this->_additionals($this->_data);
		}
		return $this->_data;
	}

    public function getTable($type = 'mailster_lists', $prefix = '', $config = array()){
        global $wpdb;
        $table_name = $wpdb->prefix . "mailster_lists";
        return $table_name;
    }

	function getTotal()
	{
		if (empty($this->_total))
		{
			$query = $this->_buildQuery();
			$this->_total = $this->_getListCount($query);
		}

		return $this->_total;
	}

	function _buildQuery()
	{
		global $wpdb;
		$where		= $this->_buildContentWhere();
		$orderby	= $this->_buildContentOrderBy();

		$query = 'SELECT a.*'
		         . ' FROM ' . $wpdb->prefix . 'mailster_lists AS a'
		         . $where
		         . $orderby
		;
		return $query;
	}

	function _buildContentOrderBy()
	{
		$orderby 	= ' ORDER BY a.name';
		return $orderby;
	}

	function _buildContentWhere()
	{
		$where = array();
		$where 		= ( count( $where ) ? ' WHERE ' . implode( ' AND ', $where ) : '' );
		return $where;
	}

	/**
	 * Additional tasks
	 */
	function _additionals($rows)
	{
		$listCount = count ($this->_data);
		for($i=0; $i < $listCount; $i++){
			$list = &$this->_data[$i];
			$list->nrMembers = $this->_getRecipientsCount($list->id);
			$list->nrMails = $this->_getMailsCount($list->id);
		}
		return $rows;
	}

	function _getRecipientsCount($listId){
		$mstRecipients = MstFactory::getRecipients();
		return $mstRecipients->getTotalRecipientsCount($listId);
	}

	function _getMailsCount($listId){
		global $wpdb;
		$query = 'SELECT count( * ) AS totalMails'
		         . ' FROM ' . $wpdb->prefix . 'mailster_mails'
		         . ' WHERE list_id =\'' . $listId . '\''
		         . ' AND ('
		         . '          (bounced_mail IS NULL AND blocked_mail IS NULL)'
		         . '       OR (bounced_mail = \'0\' AND blocked_mail = \'0\')'
		         . ' )';
		$result = $wpdb->get_var( $query );
		return $result;
	}

	/**
	 * Method to (un)publish a list
	 *
	 * @access	public
	 * @return	boolean	True on success
	 */
	function publish($cid = array(), $publish = 1)
	{

		$userid = get_current_user_id();

		if (count( $cid ))
		{
			$cids = implode( ',', $cid );
			global $wpdb;
			$query = 'UPDATE ' . $wpdb->prefix . 'mailster_lists'
			         . ' SET published = '. (int) $publish
			         . ' WHERE id IN ('. $cids .')'
			;

			$errorMsg = '';
			$result = false;
			try {
				$result = $wpdb->query( $query );
			} catch (Exception $e) {
				$errorMsg = 'Exception ' . get_class($e) . ' Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
			}
			if(false === $result) {
				return false;
			}
		}
	}

	function delete($cid = array())
	{
		$result = false;
		global $wpdb;
		if (count( $cid ))
		{
			$cids = implode( ',', $cid );
			$query = 'DELETE FROM ' . $wpdb->prefix . 'mailster_lists'
			         . ' WHERE id IN ('. $cids .')';

			$result = false;
			$errorMsg = '';
			try {
				$result = $wpdb->query( $query );
			} catch (Exception $e) {
				$errorMsg = 'Exception ' . get_class($e) . ' Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
			}
			if(false === $result) {
				return false;
			}

			$query = 'DELETE FROM ' . $wpdb->prefix . 'mailster_list_groups'
			         . ' WHERE list_id IN ('. $cids .')';

			$result = false;
			$errorMsg = '';
			try {
				$result = $wpdb->query( $query );
			} catch (Exception $e) {
				$errorMsg = 'Exception ' . get_class($e) . ' Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
			}
			if(false === $result) {
				return false;
			}

			$query = 'DELETE FROM ' . $wpdb->prefix . 'mailster_list_members'
			         . ' WHERE list_id IN ('. $cids .')';

			$errorMsg = '';
			$result = false;
			try {
				$result = $wpdb->query( $query );
			} catch (Exception $e) {
				$errorMsg = 'Exception ' . get_class($e) . ' Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
                $this->setError($errorMsg, 'delete');
			}
			if(false === $result) {
				return false;
			}

			$query = 'DELETE FROM ' . $wpdb->prefix . 'mailster_notifies'
			         . ' WHERE list_id IN ('. $cids .')';

			$errorMsg = '';
			$result = false;
			try {
				$result = $wpdb->query( $query);
			} catch (Exception $e) {
				$errorMsg = 'Exception ' . get_class($e) . ' Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
			}
			if(false === $result) {
				return false;
			}
		}

		return true;
	}
}//Class end