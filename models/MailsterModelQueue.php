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
class MailsterModelQueue extends MailsterModel
{

	var $_data = null;
	var $_total = null;
	var $_pagination = null;

	function __construct(){
		parent::__construct();
		//TODO FIX limitstart
	}


	function getData($overrideLimits=false, $limitedCols=false, $mailId=false)
	{
		if (empty($this->_data))
		{
			$query = $this->_buildQuery($limitedCols, $mailId);
			$this->_data = $this->_getList($query, 0, 0);
		}
		return $this->_data;
	}

	function getTotal()
	{
		if (empty($this->_total)){
			global $wpdb;
			$query = $this->_buildQuery(true);
			$results = $wpdb->get_results( $query );
			$this->_total =  $wpdb->num_rows;
		}
		return $this->_total;
	}

	function _buildQuery($limitedCols=false, $mailId=false)
	{
		global $wpdb;
		$where		= $this->_buildContentWhere($mailId);
		$orderby	= $this->_buildContentOrderBy();

		if($limitedCols){
			$query = 'SELECT m.mail_id, m.name, m.email, m.is_locked, ma.id, ma.subject';
		}else{
			$query = 'SELECT m.*, ma.*';
		}

		$query = $query	. ' FROM ' . $wpdb->prefix . 'mailster_queued_mails m	LEFT JOIN ' . $wpdb->prefix . 'mailster_mails ma ON (m.mail_id = ma.id)'
		         . $where
		         . $orderby;

		return $query;
	}

	function _buildContentOrderBy()
	{
		$orderby 	= ' ORDER BY m.mail_id ASC, m.email ASC';
		return $orderby;
	}

	function _buildContentWhere($mailId=false)
	{
		$where = array();
		if($mailId){
			$where[] = 'm.mail_id = \''.$mailId.'\'';
		}
		$where 		= ( count( $where ) ? ' WHERE ' . implode( ' AND ', $where ) : '' );
		return $where;
	}


	function clearQueue()
	{
		global $wpdb;
		$query = 'DELETE FROM ' . $wpdb->prefix . 'mailster_queued_mails';
		$errorMsg = '';
		$result = null;
		try {
			$result = $wpdb->query( $query );
		} catch (Exception $e) {
			$errorMsg = 'Exception ' . get_class($e) . ' Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
            $this->setError($errorMsg, 'clearQueue');
		}
		if(false === $result) {
			return false;
		}
		return true;
	}

	function delete($cid = array())
	{
		global $wpdb;
		$result = false;

		if (count( $cid )){

			for($i=0;$i<count($cid);$i++){
				$queueEntry = explode(':',$cid[$i]);
				$mailId = $queueEntry[0];
				$email = $queueEntry[1];
				$query = 'DELETE FROM ' . $wpdb->prefix . 'mailster_queued_mails'
				         . ' WHERE mail_id = \'' . $mailId . '\''
				         . ' AND email = \'' . $email .'\'';
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

	function deleteQueueEntriesOfMails($mailIds){
		$result = false;
		global $wpdb;
		if (count($mailIds)){
			$mailIdsStr = implode( ',', $mailIds);
			$query = 'DELETE FROM ' . $wpdb->prefix . 'mailster_queued_mails'
			         . ' WHERE mail_id IN ('. $mailIdsStr .')';
			$errorMsg = '';
			try {
				$result = $wpdb->query( $query );
			} catch (Exception $e) {
				$errorMsg = 'Exception ' . get_class($e) . ' Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
                $this->setError($errorMsg, 'deleteQueueEntriesOfMails');
			}
			if(false === $result) {
				return false;
			}
		}

		return true;
	}
}//Class end
