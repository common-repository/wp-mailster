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
    die('These droids are not the droids you are looking for.');
}
require_once plugin_dir_path( __FILE__ )."../mailster/app/PluginUtils.php";	
	/**
	 * Mailster Main Model - For Start Page
	 *
	 */
	class MailsterModelMailster 
	{
		var $_data = null;
		
		function __construct(){
			
		}

        /**
         * @return stdClass
         */
        function getData()
		{
			if (empty($this->_data)){			
				$this->_data = new stdClass();
				$this->_data = $this->_getGeneralListStats($this->_data);
				$this->_data = $this->_getDetailedListStats($this->_data);
				$this->_data = $this->_getMailStats($this->_data);
			}
			return $this->_data;
		}
		
		private function _getGeneralListStats($data)
		{		
			global $wpdb;
			//count mailing lists
			$table_name = $wpdb->prefix . "mailster_lists";
			$query = 'SELECT count( * ) AS totalLists'
						. ' FROM ' . $table_name ;						
			$totalLists = $wpdb->get_var( $query );
			
			//count inactive lists									
			$query = 'SELECT count( * ) AS inactiveLists'
						. ' FROM ' . $table_name
						. ' WHERE active =\'0\'' ;
			$inactiveLists = $wpdb->get_var( $query );

			$data->totalLists 				= $totalLists;
			$data->inactiveLists 			= $inactiveLists;
			
			$pluginUtils = MstFactory::getPluginUtils();
			$dateUtils = MstFactory::getDateUtils();

			$data->curTime	 = $dateUtils->formatDateAsConfigured();
			$data->nextRetrieveRun 	= $dateUtils->getInTime( date("Y-m-d H:i:s", $pluginUtils->getNextMailCheckTime() ) );
			$data->nextSendRun 		= $dateUtils->getInTime( date("Y-m-d H:i:s", $pluginUtils->getNextMailSendTime() ) );
			$data->nextMaintenance 	= $dateUtils->getInTime( date("Y-m-d H:i:s", $pluginUtils->getNextMaintenanceTime() ) );
			
			return $data;
		}

		protected function _getList($query, $limitstart=0,	$limit=0) {
			global $wpdb;
			$result = $wpdb->get_results( $query );
			return $result;
		}
		
		private function _getDetailedListStats($data)
		{
			global $wpdb;
			$table_name = $wpdb->prefix . "mailster_lists";
			$data->lists = array();
			
			$query = 'SELECT * '
						. ' FROM ' . $table_name
						. ' WHERE 1'
						. ' ORDER BY name';
						
			$lists = $this->_getList($query);
			$i=0;
			foreach($lists as $list) {
				
				$table = $wpdb->prefix . "mailster_mails";		
				$query = 'SELECT count( * ) AS totalMails'
						. ' FROM ' . $table
						. ' WHERE list_id =\'' . $list->id . '\''
						. ' AND bounced_mail = \'0\' AND blocked_mail = \'0\'';							
				$totalMails = $wpdb->get_var( $query );
				
				$table = $wpdb->prefix . "mailster_mails";
				$query = 'SELECT count( * ) AS unsentMails'
							. ' FROM ' . $table
							. ' WHERE fwd_completed =\'0\''
							. ' AND list_id =\'' . $list->id . '\''
							. ' AND bounced_mail = \'0\' AND blocked_mail = \'0\'';
				$unsentMails = $wpdb->get_var( $query );
				
				$table = $wpdb->prefix . "mailster_mails";
				$query = 'SELECT count( * ) AS blockedFilteredBounced'
							. ' FROM  ' . $table
							. ' WHERE list_id =\'' . $list->id . '\''
							. ' AND ('
							. ' 		(blocked_mail != \'' . MstConsts::MAIL_FLAG_BLOCKED_NOT_BLOCKED . '\')'
							. '		OR 	(bounced_mail != \'' . MstConsts::MAIL_FLAG_BOUNCED_NOT_BOUNCED . '\')'
							. ' )';	
				$blockedFilteredBounced = $wpdb->get_var( $query );				
				
				$table = $wpdb->prefix . "mailster_mails";
				$query = 'SELECT count( * ) AS errorMails'
							. ' FROM ' . $table
							. ' WHERE list_id =\'' . $list->id . '\''
							. ' AND fwd_errors >\'0\'';
				$errorMails = $wpdb->get_var( $query );	
				
				$table = $wpdb->prefix . "mailster_mails";
				$query = 'SELECT count( * ) AS bouncedMails'
							. ' FROM ' . $table
							. ' WHERE list_id =\'' . $list->id . '\''
							. ' AND bounced_mail != \'' . MstConsts::MAIL_FLAG_BOUNCED_NOT_BOUNCED . '\'';	
				$bouncedMails = $wpdb->get_var( $query );
				
				$table = $wpdb->prefix . "mailster_mails";
				$query = 'SELECT count( * ) AS blockedMails'
							. ' FROM ' . $table
							. ' WHERE list_id =\'' . $list->id . '\''
							. ' AND blocked_mail = \'' . MstConsts::MAIL_FLAG_BLOCKED_BLOCKED . '\'';	
				$blockedMails = $wpdb->get_var( $query );
				
				$query = 'SELECT count( * ) AS filteredMails'
							. ' FROM ' . $table
							. ' WHERE list_id =\'' . $list->id . '\''
							. ' AND blocked_mail = \'' . MstConsts::MAIL_FLAG_BLOCKED_FILTERED . '\'';	
				$filteredMails = $wpdb->get_var( $query );
				
				$mstRecipients = MstFactory::getRecipients();
				$recipients = $mstRecipients->getTotalRecipientsCount($list->id);
				
				$data->lists[$i] = $list;
				$data->lists[$i]->totalMails = $totalMails;
				$data->lists[$i]->blockedFilteredBounced = $blockedFilteredBounced; 
				$data->lists[$i]->unsentMails = $unsentMails;
				$data->lists[$i]->errorMails = $errorMails;
				$data->lists[$i]->bouncedMails = $bouncedMails;
				$data->lists[$i]->blockedMails = $blockedMails;
				$data->lists[$i]->filteredMails = $filteredMails;
				$data->lists[$i]->recipients = $recipients;
				$i++;
			}
			return $data;
		}
	
		private function _getMailStats($data)
		{
			global $wpdb;

			$table = $wpdb->prefix . "mailster_mails";		
			$query = 'SELECT count( * ) AS totalMails'
						. ' FROM ' . $table
						. ' WHERE 1';
			$totalMails = $wpdb->get_var( $query );
			
			$table = $wpdb->prefix . "mailster_queued_mails";
			$query = 'SELECT count( * ) AS queuedMails'
						. ' FROM ' . $table;
			$queuedMails = $wpdb->get_var( $query );
			
			$table = $wpdb->prefix . "mailster_mails";
			$query = 'SELECT count( * ) AS unsentMails'
						. ' FROM ' . $table
						. ' WHERE fwd_completed =\'0\'';
			$unsentMails = $wpdb->get_var( $query );
			
			$table = $wpdb->prefix . "mailster_mails";
			$query = 'SELECT count( * ) AS errorMails'
						. ' FROM ' . $table
						. ' WHERE fwd_errors >\'0\'';
			$errorMails = $wpdb->get_var( $query );

			$table = $wpdb->prefix . "mailster_oa_mails";
            $query = 'SELECT count( * ) AS offlineMails'
                . ' FROM ' . $table;
            $offlineMails = $wpdb->get_var( $query );
			
			$data->totalMails = $totalMails;
			$data->queuedMails = $queuedMails;
			$data->unsentMails = $unsentMails;
			$data->errorMails = $errorMails;
            $data->offlineMails = $offlineMails;
			
			return $data;
		}

		
	}//Class end

