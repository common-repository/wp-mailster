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
	 * Thread Model
	 *
	 */
	class MailsterModelThread extends MailsterModel
	{
		var $_id = 0;
		var $_data = null;
		var $_total = null;
		var $_pagination = null;
		var $_filterByFrontendMailArchiveACL = false;

		function __construct()
		{
			parent::__construct();
		}

		function getData($threadId, $overrideLimits=false, $filterByFrontendMailArchiveACL=false)
		{
			if (empty($this->_data) || $this->_id != $threadId){
				$this->_id = $threadId;
				$this->_filterByFrontendMailArchiveACL = $filterByFrontendMailArchiveACL;

				$query = $this->_buildQuery($threadId);

				$this->_data = $this->_getList($query, 0, 0);

			}
			return $this->_data;
		}

		function getThreadSubject($threadId){
			global $wpdb;
			$query = 'SELECT m.subject '
				. ' FROM ' . $wpdb->prefix . 'mailster_mails AS m'
				. ' WHERE m.thread_id = \'' . $threadId . '\''
				. ' ORDER BY m.receive_timestamp';
			$subjects = $this->_getList($query, 0, 1);
			return count($subjects)>0 ? $subjects[0]->subject : '';
		}

		function getTotal()
		{
			if ( empty( $this->_total ) ) {
				global $wpdb;
				$query = $this->_buildQuery( $this->_id );
				$results = $wpdb->get_results( $query );
				$this->_total =  $wpdb->num_rows;
			}
			return $this->_total;
		}

		function _buildQuery($threadId)
		{
			global $wpdb;
			$where		= $this->_buildContentWhere($threadId);
			$orderby	= $this->_buildContentOrderBy();

			$query = 'SELECT * '
				. ' FROM ' . $wpdb->prefix . 'mailster_mails AS m'
				. $where
				. $orderby;
			return $query;
		}

		function _buildContentOrderBy()
		{
			$orderby 	= ' ORDER BY m.receive_timestamp DESC';
			return $orderby;
		}

		function _buildContentWhere($threadId, $filterOutBlockedAndBouncedMails=true, $filterOutMailsInModerationOrRejectedInModeration=true)
		{
			$log = MstFactory::getLogger();
			$where = array();
			if($threadId > 0){
				$where[] = ' m.thread_id = \'' . $threadId . '\'';
			}
			if($filterOutBlockedAndBouncedMails){
				$where[] = ' ((m.bounced_mail IS NULL AND m.blocked_mail IS NULL) OR (m.bounced_mail = \'0\' AND m.blocked_mail = \'0\'))';
			}
            if($filterOutMailsInModerationOrRejectedInModeration){
                $where[] = ' m.moderation_status >= \'0\'';
            }
			$where = ( count( $where ) ? ' WHERE ' . implode( ' AND ', $where ) : '' );

			return $where;
		}
		
	}//Class end
