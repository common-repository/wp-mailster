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
 * Mails Model
 *
 */
class MailsterModelMails extends MailsterModel
{

	var $_id = 0;
	var $_data = null;
	var $_limit_offset = null;
	var $_total = null;
	var $_pagination = null;	
	var $_order_by = 'rfirst';	
	var $_filterByFrontendMailArchiveACL = false;
	var $_filterByCoreACL = false;

	function __construct(){
		parent::__construct();
	}

	function getData($listId, $overrideLimits=false, $orderBy='rfirst', $filterByFrontendMailArchiveACL=false, $filterByCoreACL=false, $page = null, $includeEveryModerationStatus=true)
	{
        $log = MstFactory::getLogger();
		global $wpdb;
		if (empty($this->_data) || $this->_id != $listId){
			$this->_id = $listId;
			$this->_order_by = $orderBy;
			$this->_filterByFrontendMailArchiveACL = $filterByFrontendMailArchiveACL;
			$this->_filterByCoreACL = $filterByCoreACL;
            if($includeEveryModerationStatus){
                $minModerationStatus = MstConsts::MAIL_FLAG_MODERATED_IN_MODERATION;
            }else{
                $minModerationStatus = null;
            }
			$query = $this->_buildQuery($listId, null, null, null, null, $page, $minModerationStatus);
			$this->_data = $wpdb->get_results( $query );
		}
		return $this->_data;
	}
	
	function getDataForBackendMailArchive($listId){
		return $this->getData($listId, false, 'rfirst', false, true, null, true); // filter by core ACL
	}

	/**
	 * Method to get blocked mails
	 */
	function getBlockedMailData($listId, $overrideLimits=false, $filterByCoreACL=false)
	{
		if($this->_id != $listId){
			$this->_id = $listId;
			$this->_data = null;
			$this->_filterByCoreACL = $filterByCoreACL;
		}
			
		$firstLast = $this->getFirstLastDate();
		if(!is_null($firstLast)){
			$query = $this->_buildQuery($listId, true, null, $firstLast['first'], $firstLast['last'], null, MstConsts::MAIL_FLAG_MODERATED_REJECTED, MstConsts::MAIL_FLAG_MODERATED_REJECTED);
		}else{
			$query = $this->_buildQuery($listId, true, null);
		}
		
		$blockedMails = $this->_getList($query, 0, 0);
		return $blockedMails;
	}

	/**
	 * Method to get bounced mails
	 */
	function getBouncedMailData($listId, $overrideLimits=false, $filterByCoreACL=false)
	{
		if($this->_id != $listId){
			$this->_id = $listId;
			$this->_data = null;
			$this->_filterByCoreACL = $filterByCoreACL;
		}
		$firstLast = $this->getFirstLastDate();
		if(!is_null($firstLast)){
			$query = $this->_buildQuery($listId, null, true, $firstLast['first'], $firstLast['last']);
		}else{
			$query = $this->_buildQuery($listId, null, true);
		}
		
		$bouncedMails = $this->_getList($query, 0, 0);		
		return $bouncedMails;
	}

	function getFirstLastDate(){
		$firstLast = null;
		if(!empty($this->_data)){
			$firstLast = array();
			$first = $this->_data[0];
			$last = $this->_data[count($this->_data)-1];
			$firstTstmp = $first->receive_timestamp; // MySQL format
			$firstParsed = strtotime($firstTstmp); // timestamp
			$lastTstmp = $last->receive_timestamp; // MySQL format
			$lastParsed = strtotime($lastTstmp); // timestamp

			$firstMail = $firstTstmp; // MySQL format
			$lastMail = $lastTstmp; // MySQL format

			if($lastParsed < $firstParsed){ // determine smaller timestamp
				$firstMail = $lastTstmp; // MySQL format
				$lastMail = $firstTstmp; // MySQL format
			}
			$firstLast['first'] = $firstMail; // MySQL format
			$firstLast['last']	= $lastMail; // MySQL format
			
			if($this->_limit_offset == 0){
				// This means the forwarded emails are shown from the beginning
				// To include bounced/blocked emails that are more recent,
				// we have to null the last date timestamp.
				$firstLast['last'] = null;
			}
		}
		return $firstLast;
	}

	public function getTable($type = 'mailster_groups', $prefix = '', $config = array()){
		global $wpdb;
		return $wpdb->prefix . $type;
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

	function getPagination()
	{
		return $this->_pagination;
	}
	
	function _buildQuery($listId, $isBlocked=null, $isBounced=null, $firstDate=null, $lastDate=null, $currentpage = null, $moderationMinStatus=null, $moderationMaxStatus=null)
	{
		$per_page = 12;
		global $wpdb;
		$where		= $this->_buildContentWhere($listId, $isBlocked, $isBounced, $firstDate, $lastDate, $moderationMinStatus, $moderationMaxStatus);
		$orderby	= $this->_buildContentOrderBy();
			
		$query = 'SELECT m.*, l.name'
					. ' FROM ' . $wpdb->prefix . 'mailster_mails AS m'
					. ' LEFT JOIN ' . $wpdb->prefix . 'mailster_lists AS l'
					. ' ON (m.list_id = l.id)'
					. $where
					. $orderby;
		if($currentpage) {
			$query .= " LIMIT $per_page";
			$query .= ' OFFSET ' . ( $currentpage - 1 ) * $per_page;
		}
		return $query;
	}

	function _buildContentOrderBy()
	{
		if($this->_order_by === 'rfirst'){
			$orderby = ' ORDER BY m.receive_timestamp DESC, m.fwd_completed_timestamp DESC';
		}else{
			$orderby = ' ORDER BY m.receive_timestamp ASC, m.fwd_completed_timestamp ASC';
		}
		return $orderby;
	}

	function _buildContentWhere($listId, $isBlocked=null, $isBounced=null, $firstDate=null, $lastDate=null, $moderationMinStatus=null, $moderationMaxStatus=null)
	{
		$log = MstFactory::getLogger();
		$where = array();
		
		if($listId > 0){
			$where[] = ' m.list_id = \'' . $listId . '\'';
		}
		
		/*if($this->_filter2subscribed_lists_user_id){			
			$recip = MstFactory::getRecipients();
			$subscribedListIdsOfUser = $recip->getListsUserIsMemberOf($this->_filter2subscribed_lists_user_id, 1);
			$where[] = ' m.list_id IN ( '.implode(', ', $subscribedListIdsOfUser).' )';
		}*/
		
		if($this->_filterByFrontendMailArchiveACL){
			$listIdsAllowedToBeShown = array();
			$mailingListUtils = MstFactory::getMailingListUtils();
			$mailingLists = $mailingListUtils->getAllMailingLists();
			$recip = MstFactory::getRecipients();
			$user = wp_get_current_user();
			$userIsLoggedIn = false;
			if($user->ID > 0){
				$userIsLoggedIn = true;
			}
			$userIsLoggedIn = is_user_logged_in();
			foreach($mailingLists AS $mList){
				if($mList->front_archive_access == MstConsts::FRONT_ARCHIVE_ACCESS_ALL_USERS){
					$listIdsAllowedToBeShown[] = $mList->id;
					$log->debug('_filterByFrontendMailArchiveACL -> add list ID '.$mList->id.' as all users are allowed');
				}elseif($mList->front_archive_access == MstConsts::FRONT_ARCHIVE_ACCESS_LOGGED_IN_USERS && $userIsLoggedIn){
					$listIdsAllowedToBeShown[] = $mList->id;
					$log->debug('_filterByFrontendMailArchiveACL -> add list ID '.$mList->id.' as all logged-in users are allowed');
				}elseif($mList->front_archive_access == MstConsts::FRONT_ARCHIVE_ACCESS_LOGGED_IN_SUBSCRIBERS_OF_MAILING_LIST && $userIsLoggedIn){
					$subscribedListIdsOfUser = $recip->getListsUserIsMemberOf($user->ID, 1);
					for($i=0;$i<count($subscribedListIdsOfUser);$i++){
						if($subscribedListIdsOfUser[$i] == $mList->id){
							$listIdsAllowedToBeShown[] = $mList->id;
							$log->debug('_filterByFrontendMailArchiveACL -> add list ID '.$mList->id.' as user ID '.get_current_user_id().' is a subscriber as required');
							break;
						}
					}
                }elseif($mList->front_archive_access == MstConsts::FRONT_ARCHIVE_ACCESS_NOBODY){
                    continue; // nope, this list is not allowed...
                }
			}
			$log->debug('mails_model: _filterByFrontendMailArchiveACL -> listIdsAllowedToBeShown: '.print_r($listIdsAllowedToBeShown, true));
			if(count($listIdsAllowedToBeShown) > 0){
				$where[] = ' m.list_id IN ( '.implode(', ', $listIdsAllowedToBeShown).' )';
			}else{
				$where[] = ' m.list_id < 0'; // add impossible condition
			}
		}
		
		if($this->_filterByCoreACL){
			$listIdsAllowedToBeShown = array();
			$mailingListUtils = MstFactory::getMailingListUtils();
			$mailingLists = $mailingListUtils->getAllMailingLists();
			foreach($mailingLists AS $mList){
				if(MstFactory::getAuthorization()->isAllowed('core.edit', $mList->id)){
					$listIdsAllowedToBeShown[] = $mList->id;
				}
			}
			//$log->debug('mails_model: _filterByCoreACL -> listIdsAllowedToBeShown: '.print_r($listIdsAllowedToBeShown, true));
			if(count($listIdsAllowedToBeShown) > 0){
				$where[] = ' m.list_id IN ( '.implode(', ', $listIdsAllowedToBeShown).' )';
			}else{
				$where[] = ' m.list_id < 0'; // add impossible condition
			}
		}
				
		if(!is_null($firstDate)){
			$where[] = ' m.receive_timestamp >= \'' . $firstDate . '\'';
		}
		if(!is_null($lastDate)){
			$where[] = ' m.receive_timestamp <= \'' . $lastDate . '\'';
		}
		if(!is_null($isBlocked)){
			if($isBlocked){
				$where[] = ' m.blocked_mail > \'0\'';
                if(is_null($moderationMinStatus)){
                    $where[] = ' ((m.blocked_mail > \'0\')  OR (m.moderation_status >= \''.MstConsts::MAIL_FLAG_MODERATED_NOT_MODERATED.'\'))';
                }else{
                    $where[] = ' ((m.blocked_mail > \'0\')  OR (m.moderation_status >= \''.$moderationMinStatus.'\'))';
                    if(!is_null($moderationMinStatus) && !is_null($moderationMaxStatus)){
                        $where[] = ' ((m.blocked_mail > \'0\') OR (m.moderation_status <= \''.$moderationMaxStatus.'\'))';
                    }
                }
			}else{
				$where[] = ' m.blocked_mail = \'0\'';
			}
		}
		if(!is_null($isBounced)){
			$bounced = $isBounced ? 1 : 0;
			$where[] = ' m.bounced_mail = \'' . $bounced . '\'';
		}
		if(is_null($isBlocked) && is_null($isBounced)){
			$where[] = ' ((m.bounced_mail IS NULL AND m.blocked_mail IS NULL) OR (m.bounced_mail = \'0\' AND m.blocked_mail = \'0\'))';
		}

        if((is_null($isBlocked) || $isBlocked === false)
            && (is_null($isBounced) || $isBounced === false)){
            if(is_null($moderationMinStatus)){
                $where[] = ' m.moderation_status >= \''.MstConsts::MAIL_FLAG_MODERATED_NOT_MODERATED.'\'';
            }else{
                $where[] = ' m.moderation_status >= \''.$moderationMinStatus.'\'';
            }
            if(!is_null($moderationMinStatus) && !is_null($moderationMaxStatus)){
                $where[] = ' m.moderation_status <= \''.$moderationMaxStatus.'\'';
            }
        }

		if(isset( $_REQUEST['filter_search'] )) {
			$filterSearch = sanitize_text_field($_REQUEST['filter_search']);
			if(strlen(trim($filterSearch)) > 0){
				global $wpdb;
				$where[] = '( '
							. ' m.subject LIKE \'%'.$filterSearch.'%\''
							. ' OR m.body LIKE \'%'.$filterSearch.'%\''
							. ' OR m.html LIKE \'%'.$filterSearch.'%\''
							. ' OR m.from_name LIKE \'%'.$filterSearch.'%\''
							. ' OR m.from_email LIKE \'%'.$filterSearch.'%\''
							. ' OR EXISTS ('
						        . ' SELECT 1'
						        . ' FROM ' . $wpdb->prefix . 'mailster_attachments attach'
						        . ' WHERE attach.mail_id = m.id'
						        . ' AND attach.filename LIKE \'%'.$filterSearch.'%\''
								. ')'
							. ')';
			}
		}
		$where 		= ( count( $where ) ? ' WHERE ' . implode( ' AND ', $where ) : '' );
		$log->debug('model/mails where: '.$where);
		return $where;
	}

    function getEmailsOlderThanDays($nrDays, $listId=0, $isBlocked=false, $isBounced=false, $returnIdOnly=true, $limit=false, $inModeration=false, $rejectedInModeration=false){
    	global $wpdb;
        $where = array();
        $where[] = ' m.receive_timestamp < DATE_SUB(CURDATE(), INTERVAL '.$nrDays.' DAY)';
        if($listId > 0){
            $where[] = ' m.list_id = \'' . $listId . '\'';
        }
        if($isBlocked){
            $where[] = ' m.blocked_mail = \'1\'';
        }
        if($isBounced){
            $where[] = ' m.bounced_mail = \'1\'';
        }
        if($inModeration){
            $where[] = ' m.moderation_status = \''.MstConsts::MAIL_FLAG_MODERATED_IN_MODERATION.'\'';
        }
        if($rejectedInModeration){
            $where[] = ' m.moderation_status = \''.MstConsts::MAIL_FLAG_MODERATED_REJECTED.'\'';
        }
        $where 	 = ( count( $where ) ? ' WHERE ' . implode( ' AND ', $where ) : '' );

        $select = $returnIdOnly ? 'm.id' : 'm.*';
        $query = 'SELECT '.$select
                    . ' FROM ' . $wpdb->prefix . 'mailster_mails AS m'
                    . $where;

        $limitNr = 0;
        if($limit){
            $limitNr = $limit;
        }
        $oldEmails = $this->_getList($query, 0, $limitNr);
        return $oldEmails;
    }

    function moveEmailsToOfflineArchive($mailIds){
        $log = MstFactory::getLogger();
        $log->debug('moveEmailsToOfflineArchive for '.count($mailIds).' mails');

        if(count($mailIds) <= 0){
            $log->debug('moveEmailsToOfflineArchive: No mail IDs provided, return/exit');
            return;
        }

        $mailIdsStr = implode(',',$mailIds);
        $log->debug('moveEmailsToOfflineArchive IDs: '.print_r($mailIds, true));

        global $wpdb;
        // TODO FIX INSERT INTO DB
        $query = 'INSERT INTO ' . $wpdb->prefix . 'mailster_oa_attachments'
                    . ' SELECT * FROM ' . $wpdb->prefix . 'mailster_attachments a'
                    . ' WHERE a.mail_id IN ('.$mailIdsStr.')'
                    . ' ON DUPLICATE KEY UPDATE id=a.id;';
	    $errorMsg = '';
        $result = false;
        try {
            $result = $wpdb->query( $query );
        } catch (Exception $e) {
            $errorMsg = 'Exception ' . get_class($e) . ' Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
        }
        $deleteOldAttachments = true;
        if(false === $result) {
            $log->error('moveEmailsToOfflineArchive: Error moving attachments: '.$errorMsg);
            $deleteOldAttachments = false;
        }
        $query = 'INSERT INTO ' . $wpdb->prefix . 'mailster_oa_mails'
            . ' SELECT * FROM ' . $wpdb->prefix . 'mailster_mails m'
            . ' WHERE m.id IN ('.$mailIdsStr.')'
            . ' ON DUPLICATE KEY UPDATE id=m.id;';
        $errorMsg = '';
        $result = false;
        try {
            $result = $this->$wpdb->query( $query );
        } catch (Exception $e) {
            $errorMsg = 'Exception ' . get_class($e) . ' Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
        }
        $deleteOldMails = true;
        if(false === $result) {
            $log->error('moveEmailsToOfflineArchive: Error moving emails: '.$errorMsg);
            $deleteOldMails = false;
        }

        if($deleteOldAttachments && $deleteOldMails){
            $log->debug('moveEmailsToOfflineArchive: No error occurred copying attachments and emails, therefore now delete the old entries');

            $query = 'DELETE FROM ' . $wpdb->prefix . 'mailster_attachments'
                . ' WHERE mail_id IN ('.$mailIdsStr.')';
            $errorMsg = '';
            $result = false;
            try {
                $result = $wpdb->query( $query );
            } catch (Exception $e) {
                $errorMsg = 'Exception ' . get_class($e) . ' Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
            }
            if(false === $result) {
                $log->error('moveEmailsToOfflineArchive: Error deleting old attachments: '.$errorMsg);
            }
            $query = 'DELETE FROM ' . $wpdb->prefix . 'mailster_mails'
                . ' WHERE id IN ('.$mailIdsStr.')';
            $errorMsg = '';
            $result = false;
            try {
                $result = $wpdb->query( $query );
            } catch (Exception $e) {
                $errorMsg = 'Exception ' . get_class($e) . ' Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
            }
            if(false === $result) {
                $log->error('moveEmailsToOfflineArchive: Error deleting old emails: '.$errorMsg);
            }
            $log->debug('moveEmailsToOfflineArchive: Completed offline archiving');
        }else{
            $log->warning('moveEmailsToOfflineArchive: At least one error occurred copying attachments and emails, therefore do NOT delete the old entries for mail with IDs: '.$mailIdsStr);
        }
    }

	function delete($cid = array())
	{
		$log = MstFactory::getLogger();
		global $wpdb;
		$result = false;

		if (count( $cid )){
			$log->debug('MailsterModelMails->delete, cid: '.print_r($cid, true));
            /** @var MailsterModelQueue $queueModel */
            $queueModel = MstFactory::getQueueModel();
            $queueModel->deleteQueueEntriesOfMails($cid);

            /** @var MailsterModelDigest $digestModel */
            $digestModel = MstFactory::getDigestModel();
            $digestModel->deleteDigestQueueEntriesOfMails($cid);

            $sendEvents = MstFactory::getSendEvents();
            $sendEvents->deleteSendReportOfMails($cid);

            $attachUtils = MstFactory::getAttachmentsUtils();
            for($i=0; $i<count($cid); $i++){
                $attachUtils->deleteAttachmentsOfMail($cid[$i]);
            }

			$cids = implode( ',', $cid );
			$query = 'DELETE FROM ' . $wpdb->prefix . 'mailster_mails'
			. ' WHERE id IN ('. $cids .')';

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

		return true;
	}
}//Class end

