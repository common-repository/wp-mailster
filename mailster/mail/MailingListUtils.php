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

class MstMailingListUtils
{			
	public static function getMailingListIdByMailId($mailId){
		global $wpdb;
		$query = 'SELECT * FROM ' . $wpdb->prefix . 'mailster_mails WHERE id=\'' . $mailId . '\'';
		$mail = $wpdb->get_row($query);
		if($mail){
			return $mail->list_id;
		}else{
			return false;
		}		
	}
	
	public static function getMailingList($listId){
		global $wpdb;
		$mstList = MstFactory::getMailingList();
        $query = 'SELECT l.*,'
                .' inbox.server_host AS mail_in_host,'
                .' inbox.server_port AS mail_in_port,'
                .' inbox.secure_protocol AS mail_in_use_secure,'
                .' inbox.secure_authentication AS mail_in_use_sec_auth,'
                .' inbox.protocol AS mail_in_protocol,'
                .' inbox.connection_parameter AS mail_in_params,'
                .' outbox.server_host AS mail_out_host,'
                .' outbox.server_port AS mail_out_port,'
                .' outbox.secure_protocol AS mail_out_use_secure,'
                .' outbox.secure_authentication AS mail_out_use_sec_auth'
            .' FROM ' . $wpdb->prefix . 'mailster_lists l'
            .' LEFT JOIN ' . $wpdb->prefix . 'mailster_servers inbox ON (l.server_inb_id = inbox.id)'
            .' LEFT JOIN ' . $wpdb->prefix . 'mailster_servers outbox ON (l.server_out_id = outbox.id)'
            .' WHERE l.id=\'' . $listId . '\'';
		$mList = $mstList->getInstance( $wpdb->get_row( $query ) );
		return $mList;
	}
	
	public static function getMailingListByName($listName){
		$listName = strtolower($listName);
		global $wpdb;
		$query = ' SELECT id'
				. ' FROM ' . $wpdb->prefix . 'mailster_lists'
				. ' WHERE lower(name) = ' . $listName;		
		$lists = $wpdb->get_results( $query );
		if($lists && count($lists) > 0){
            $list = $lists[0];
            $listId = $list->id;
            $mList = self::getMailingList($listId);
            return $mList;
		}
		return null;
	}

    public static function getMailingListsUsingServerId($serverId){
        global $wpdb;
        $query = ' SELECT id'
            . ' FROM ' . $wpdb->prefix . 'mailster_lists'
            . ' WHERE server_inb_id = \''.intval($serverId).'\' OR (server_out_id = \''.intval($serverId).'\' AND use_cms_mailer = \'0\') ';
        $lists = $wpdb->get_results( $query );
        if($lists && count($lists) > 0){
            $mLists = array();
            foreach($lists AS $list){
                $mLists[] = self::getmailinglist($list->id);
            }
            return $mLists;
        }
        return null;
    }

	public static function getActiveMailingLists($orderByLastCheck=true, $includeServerSettings=false, $onlyNotCheckedSinceSeconds=false){
		global $wpdb;
		$mstList = MstFactory::getMailingList();
		$query =   'SELECT l.*';
        if($includeServerSettings){
            $query .= (','
                    .' inbox.server_host AS mail_in_host,'
                    .' inbox.server_port AS mail_in_port,'
                    .' inbox.secure_protocol AS mail_in_use_secure,'
                    .' inbox.secure_authentication AS mail_in_use_sec_auth,'
                    .' inbox.protocol AS mail_in_protocol,'
                    .' inbox.connection_parameter AS mail_in_params,'
                    .' outbox.server_host AS mail_out_host,'
                    .' outbox.server_port AS mail_out_port,'
                    .' outbox.secure_protocol AS mail_out_use_secure,'
                    .' outbox.secure_authentication AS mail_out_use_sec_auth'
                .' FROM ' . $wpdb->prefix . 'mailster_lists l'
                .' LEFT JOIN ' . $wpdb->prefix . 'mailster_servers inbox ON (l.server_inb_id = inbox.id)'
                .' LEFT JOIN ' . $wpdb->prefix . 'mailster_servers outbox ON (l.server_out_id = outbox.id)');
        }else{
            $query .=  ' FROM ' . $wpdb->prefix . 'mailster_lists l';
        }
		$query .= ' WHERE l.active =\'1\' ';
        $query .= ($onlyNotCheckedSinceSeconds ? ( ' AND ((l.last_check IS NULL) OR (DATE_ADD(l.last_check, INTERVAL '.intval($onlyNotCheckedSinceSeconds).' SECOND) <  NOW()))' ) : ' ');
		$query .= ($orderByLastCheck ? ' ORDER BY l.last_check ASC' : ' ');
		$mLists = $wpdb->get_results( $query );
		$nrLists = $wpdb->num_rows;
		for($i = 0; $i < $nrLists; $i++){
			$mLists[$i] = $mstList->getInstance($mLists[$i]);
		}
		return $mLists;	
	}

    public static function isOpenSslNeededForActiveLists(){
        $mLists = self::getActiveMailingLists(true, true);
        foreach($mLists AS $mList){
            if($mList->mail_in_use_secure === 'ssl'
                || $mList->mail_in_use_secure === 'tls'
                || $mList->mail_out_use_secure === 'ssl'
                || $mList->mail_out_use_secure === 'tls'
            ){
                return true;
            }
        }
        return false;
    }

	public static function getAllMailingLists($orderByName=true){
		global $wpdb;
		$mstList = MstFactory::getMailingList();
        $query =   'SELECT l.*,'
                .' inbox.server_host AS mail_in_host,'
                .' inbox.server_port AS mail_in_port,'
                .' inbox.secure_protocol AS mail_in_use_secure,'
                .' inbox.secure_authentication AS mail_in_use_sec_auth,'
                .' inbox.protocol AS mail_in_protocol,'
                .' inbox.connection_parameter AS mail_in_params,'
                .' outbox.server_host AS mail_out_host,'
                .' outbox.server_port AS mail_out_port,'
                .' outbox.secure_protocol AS mail_out_use_secure,'
                .' outbox.secure_authentication AS mail_out_use_sec_auth'
            .' FROM ' . $wpdb->prefix . 'mailster_lists l'
            .' LEFT JOIN ' . $wpdb->prefix . 'mailster_servers inbox ON (l.server_inb_id = inbox.id)'
            .' LEFT JOIN ' . $wpdb->prefix . 'mailster_servers outbox ON (l.server_out_id = outbox.id)';
		$query = $query . ($orderByName ? ' ORDER BY l.name ASC' : ' ');
		$mLists = $wpdb->get_results( $query );
		$nrLists = $wpdb->num_rows;
		for($i = 0; $i < $nrLists; $i++){
			$mLists[$i] = $mstList->getInstance($mLists[$i]);
		}
		return $mLists;	
	}
	
	public static function getAllMailingListsUserCanAccessMailsInFrontend($jUserId){
		$recip = MstFactory::getRecipients();
		$listIdsAllowedToBeShown = array();
		$mLists = self::getAllMailingLists();
        $user = wp_get_current_user();
        $userIsLoggedIn = false;
        if($user->ID > 0){
            $userIsLoggedIn = true;
        }
		foreach($mLists AS $mList){
			if($mList->front_archive_access == MstConsts::FRONT_ARCHIVE_ACCESS_ALL_USERS){
				$listIdsAllowedToBeShown[] = $mList->id;
			}elseif($mList->front_archive_access == MstConsts::FRONT_ARCHIVE_ACCESS_LOGGED_IN_USERS && $userIsLoggedIn){
				$listIdsAllowedToBeShown[] = $mList->id;
			}elseif($mList->front_archive_access == MstConsts::FRONT_ARCHIVE_ACCESS_LOGGED_IN_SUBSCRIBERS_OF_MAILING_LIST && ($jUserId>0)){
				$subscribedListIdsOfUser = $recip->getListsUserIsMemberOf($jUserId, 1);
				for($i=0;$i<count($subscribedListIdsOfUser);$i++){
					if($subscribedListIdsOfUser[$i] == $mList->id){
						$listIdsAllowedToBeShown[] = $mList->id;
						break;
					}
				}
            }elseif($mList->front_archive_access == MstConsts::FRONT_ARCHIVE_ACCESS_NOBODY){
                continue; // nope, this list is not allowed...
            }
		}
		return $listIdsAllowedToBeShown;
	}
	
	public static function lockMailingList($listId, $setLastCheckOnSuccess=true){
		$log = MstFactory::getLogger();
		$log->debug('Checking whether list is already locked...');
		if(self::isListLocked($listId)){
			if(self::isListLockInvalid($listId)){
				$log->warning('List lock invalid, unlock list...');
				self::unlockMailingList($listId);
			}
		}
		if(!self::isListLocked($listId)){
			$log->debug('List not locked, attempt locking...');
			$lockId = self::attemptListLock($listId);
			$log->debug('Attempted to lock with lockId ' . $lockId);
			if(self::checkListLock($listId, $lockId)){
				$log->debug('Locking went fine!');
				if($setLastCheckOnSuccess){
					self::setLastCheck($listId);
				}
				return true;
			}
		}
		$log->debug('Locking failed!');
		return false;
	}
	
	public static function deactivateAllMailingLists(){	
		global $wpdb;
		$query = ' UPDATE ' . $wpdb->prefix . 'mailster_lists SET'
				. ' active = \'0\'';	
		$result = $wpdb->query( $query );
		if($result !== false){
			return true;
		}
		return false;
	}
	
	public static function activateMailingList($listId){
		global $wpdb;	
		$query = ' UPDATE ' . $wpdb->prefix . 'mailster_lists SET'
				. ' active = \'1\''	
				. ' WHERE id=\'' . $listId . '\'';		
		$result = $wpdb->query( $query );
		if($result !== false){
			return true;
		}
		return false;
	}
	
	public static function unlockMailingList($listId){	
		global $wpdb;
		if(self::isListLocked($listId)){		
			$query = ' UPDATE ' . $wpdb->prefix . 'mailster_lists SET'
					. ' is_locked = \'0\','
					. ' lock_id = \'0\','
					. ' last_check = last_check,'	
					. ' last_lock = last_lock,'
					. ' last_mail_retrieved = last_mail_retrieved,'
					. ' last_mail_sent = last_mail_sent'				
					. ' WHERE id=\'' . $listId . '\'';	
			$result = $wpdb->query( $query );
			if($result != false){
				return true;
			}
		}
		return false;
	}
	
	public static function setLastCheck($listId){	
		global $wpdb;
		$query = ' UPDATE ' . $wpdb->prefix . 'mailster_lists SET'
				. ' last_check = NOW(),'	
				. ' last_lock = last_lock,'
				. ' last_mail_retrieved = last_mail_retrieved,'
				. ' last_mail_sent = last_mail_sent'
				. ' WHERE id=\'' . $listId . '\'';	
		$result = $wpdb->query( $query );
	}
	
	public static function setLastMailRetrieved($listId){
		global $wpdb;
		$log = MstFactory::getLogger();
		$query = ' UPDATE ' . $wpdb->prefix . 'mailster_lists SET'
				. ' last_check = last_check,'	
				. ' last_lock = last_lock,'
				. ' last_mail_retrieved = NOW(),'
				. ' last_mail_sent = last_mail_sent'
				. ' WHERE id=\'' . $listId . '\'';	
		$result = $wpdb->query( $query );
		$log->debug('setLastMailRetrieved for list ID '.$listId.', result: '.print_r($result, true).', query: '.$query);
	}
	
	public static function setLastMailSent($listId){
		global $wpdb;
		$log = MstFactory::getLogger();
		$query = ' UPDATE ' . $wpdb->prefix . 'mailster_lists SET'
				. ' last_check = last_check,'	
				. ' last_lock = last_lock,'
				. ' last_mail_retrieved = last_mail_retrieved,'
				. ' last_mail_sent = NOW()'
				. ' WHERE id=\'' . $listId . '\'';	
		$result = $wpdb->query( $query );
		$log->debug('setLastMailSent for list ID '.$listId.', result: '.print_r($result, true).', query: '.$query);
	}
	
	public static function attemptListLock($listId){
		global $wpdb;
		$lockId = rand(1, 123456);			
		$query = ' UPDATE ' . $wpdb->prefix . 'mailster_lists SET'
				. ' is_locked = \'1\','
				. ' lock_id = \'' . $lockId . '\','
				. ' last_check = last_check,'	
				. ' last_lock = NOW(),'
				. ' last_mail_retrieved = last_mail_retrieved,'
				. ' last_mail_sent = last_mail_sent'					
				. ' WHERE id=\'' . $listId . '\'';	
		$result = $wpdb->query( $query );
		
		return $lockId;
	}	
	
	public static function checkListLock($listId, $lockId){
		$mList = self::getMailingList($listId);
		if(($mList->is_locked > 0) && ($mList->lock_id == $lockId)){
			return true;
		}
		return false;
	}
	
	public static function isListLocked($listId){	
		global $wpdb;
		$query =  ' SELECT is_locked'
				. ' FROM ' . $wpdb->prefix . 'mailster_lists' 
				. ' WHERE id=\'' . $listId . '\'';
		$isLocked = $wpdb->get_var( $query );
		if($isLocked > 0){
			return true;
		}
		return false;
	}
	
	public static function isListLockInvalid($listId){	
		$log = MstFactory::getLogger();
		global $wpdb;
		$query =  ' SELECT last_lock'
				. ' FROM ' . $wpdb->prefix . 'mailster_lists' 
				. ' WHERE id=\'' . $listId . '\''
				. ' AND last_lock < DATE_SUB(NOW(), INTERVAL 5 MINUTE)';
		$results = $wpdb->get_var( $query );
		if($wpdb->num_rows > 0){
			return true; // list lock invalid
		}
		
		$query =  ' SELECT last_lock'
				. ' FROM ' . $wpdb->prefix . 'mailster_lists' 
				. ' WHERE id=\'' . $listId . '\'';
		$lastLock = $wpdb->get_var( $query );
		
		$query =  ' SELECT NOW() As lock_time_now';
		$timeNow = $wpdb->get_var( $query );
		
		$log->debug('List lock not invalid, last lock at: ' . $lastLock . ', now: ' . $timeNow . ' (not 5 min diff)');
		
		return false; // list lock valid		
	}
	
	public static function isSendThrottlingActive(){
		$log = MstFactory::getLogger();
		$mstConfig = MstFactory::getConfig();
        if($mstConfig->getMaxMailsPerHour() > 0
            || $mstConfig->getMaxMailsPerMinute() > 0){
            $log->debug('Send throttling active');
            if(MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_THROTTLE)){
                return true;
            }else{
                $log->warning('Send throttling not available in this product edition!');
                return false;
            }
        }
		$log->debug('Send throttling NOT active');
		return false;
	}
	
	public static function isSendLimitReached(){
		$log = MstFactory::getLogger();
        $mstConfig = MstFactory::getConfig();
        $listUtils = MstFactory::getMailingListUtils();

        $hourListStatSumObj = $listUtils->getListStatSumOfCurrentHour();
        $minuteListStatSumObj = $listUtils->getListStatSumOfCurrentHourAndMinute();

        $maxMailsPerHour = $mstConfig->getMaxMailsPerHour();
        if($maxMailsPerHour > 0){
            $mailsPerHour = ($hourListStatSumObj && property_exists($hourListStatSumObj, 'send_recipients_sum')) ? $hourListStatSumObj->send_recipients_sum : 0;
            if($mailsPerHour >= $maxMailsPerHour){
                $log->debug('Send limit per hour (send: '.$mailsPerHour
                    .', limit: '.$maxMailsPerHour. ') reached, stop sending for this hour');
                return true;
            }else{
                $log->debug('Send limit per hour (send: '.$mailsPerHour
                    .', limit: '.$maxMailsPerHour. ') not reached yet');
            }
        }

        $maxMailsPerMinute = $mstConfig->getMaxMailsPerMinute();
        if($maxMailsPerMinute > 0){
            $mailsPerMinute = ($hourListStatSumObj && property_exists($minuteListStatSumObj, 'send_recipients_sum')) ? $minuteListStatSumObj->send_recipients_sum : 0;
            if($mailsPerMinute >= $maxMailsPerMinute){
                $log->debug('Send limit per minute (send: '.$mailsPerMinute
                    .', limit: '.$maxMailsPerMinute. ') reached, stop sending for this minute');
                return true;
            }else{
                $log->debug('Send limit per minute (send: '.$mailsPerMinute
                    .', limit: '.$maxMailsPerMinute. ') not reached yet');
            }
        }

		return false; // limit(s) not reached
	}

    public function getListStatById($listStatId){
    	global $wpdb;
        $log = MstFactory::getLogger();
        $query =  'SELECT * FROM '.$wpdb->prefix.'mailster_list_stats'
            . ' WHERE id=\'' . $listStatId . '\'';
        $listStatObj = $wpdb->get_row( $query );
        if(!$listStatObj){
            $log->warning('getListStatById: not found stat ID '.$listStatId.', query was: '.$query);
        }
        return $listStatObj;
    }

    public function getListStatOfCurrentHourAndMinute($listId){
        $log = MstFactory::getLogger();
        $listStatObjs = $this->getListStat($listId, 'CURDATE()', 'HOUR(NOW())', 'MINUTE(NOW())');
        $listStatObj = null;
        if($listStatObjs && is_array($listStatObjs) && count($listStatObjs) >= 1){
            $listStatObj = $listStatObjs[0];
        }
        $log->debug('getListStatOfCurrentHourAndMinute for list ID '.$listId.': '.print_r($listStatObj, true));
        return $listStatObj;
    }

    public function getListStatSumOfCurrentHour($listId=null){
        $log = MstFactory::getLogger();
        $listStatSumObj = $this->getListStatSum($listId, 'CURDATE()', 'HOUR(NOW())', null);
        $log->debug('getListStatSumOfCurrentHour: '.print_r($listStatSumObj, true));
        return $listStatSumObj;
    }

    public function getListStatSumOfCurrentHourAndMinute($listId=null){
        $log = MstFactory::getLogger();
        $listStatSumObj = $this->getListStatSum($listId, 'CURDATE()', 'HOUR(NOW())', 'MINUTE(NOW())');
        $log->debug('getListStatSumOfCurrentHourAndMinute: '.print_r($listStatSumObj, true));
        return $listStatSumObj;
    }

    public function getListStatSum($listId=null, $date=null, $hour=null, $minute=null){
    	global $wpdb;
        $log = MstFactory::getLogger();

        $logMsg = 'getListStatSum for ';
        $where = array();
        if(!is_null($listId)){
            $where[] = ' list_id = \''.$listId.'\'';
            $logMsg .= ' list='.$listId;
        }
        if(!is_null($date)){
            $where[] = ' DATE(stat_date) = '.$date.'';
            $logMsg .= ' DATE(stat_date)='.$date;
        }
        if(!is_null($hour)){
            $where[] = ' stat_hour = '.$hour.'';
            $logMsg .= ' stat_hour='.$hour;
        }
        if(!is_null($minute)){
            $where[] = ' stat_minute = '.$minute.'';
            $logMsg .= ' stat_minute='.$minute;
        }

        $where 		= ( count( $where ) ? ' WHERE ' . implode( ' AND ', $where ) : '' );

        $query =  'SELECT SUM(send_mails) AS send_mails_sum,  SUM(send_recipients) AS send_recipients_sum FROM ' . $wpdb->prefix . 'mailster_list_stats' . $where;
        $listStatSumObj = $wpdb->get_row( $query );
        //$log->debug('getListStatSum query: '.$query);
        //$log->debug($logMsg.': '.print_r($listStatSumObj, true));
        return $listStatSumObj;
    }

    public function getListStat($listId=null, $date=null, $hour=null, $minute=null){
    	global $wpdb;
        $log = MstFactory::getLogger();

        $logMsg = 'getListStat for ';
        $where = array();
        if(!is_null($listId)){
            $where[] = ' list_id = \''.$listId.'\'';
            $logMsg .= ' list='.$listId;
        }
        if(!is_null($date)){
            $where[] = ' DATE(stat_date) = '.$date.'';
            $logMsg .= ' DATE(stat_date)='.$date;
        }
        if(!is_null($hour)){
            $where[] = ' stat_hour = '.$hour.'';
            $logMsg .= ' stat_hour='.$hour;
        }
        if(!is_null($hour)){
            $where[] = ' stat_minute = '.$minute.'';
            $logMsg .= ' stat_minute='.$minute;
        }

        $where 		= ( count( $where ) ? ' WHERE ' . implode( ' AND ', $where ) : '' );

        $query =  'SELECT * FROM ' . $wpdb->prefix . 'mailster_list_stats' . $where;
        $listStatObjs = $wpdb->get_results( $query );
        $log->debug($logMsg.': '.print_r($listStatObjs, true));
        return $listStatObjs;
    }


    public function createNewListStatForCurrentHour($listId){
    	global $wpdb;
        $log = MstFactory::getLogger();
        $log->debug('createNewListStatForCurrentHour: Add for list '.$listId.' a new, empty list stat');
        $query = 'INSERT INTO ' . $wpdb->prefix . 'mailster_list_stats ('
                    . ' list_id,'
                    . ' stat_date,'
                    . ' stat_hour,'
                    . ' stat_minute,'
                    . ' send_mails,'
                    . ' send_recipients'
                . ') VALUES ('
                    . ' \'' . $listId . '\','
                    . ' NOW(),'
                    . ' HOUR(NOW()),'
                    . ' MINUTE(NOW()),'
                    . ' 0,'
                    . ' 0'
                . ')';
		$errorMsg = '';
        try {
            $result = $wpdb->query( $query );
        } catch (Exception $e) {
            $errorMsg = 'Exception ' . get_class($e) . ' Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
        }
        $listStatId = $wpdb->insert_id;
        
        if($listStatId < 1){
            $log->error('Failed to insert new list stat, ' . $errorMsg . "\r\n".'Query was: '.$query);
            return false;
        }else{
            $log->debug('New list stat ID: ' . $listStatId);
        }
        return $listStatId;
    }

    public function writeListStat($listId, $sendMailsIncCr, $sendRecipientsInCr){
    	global $wpdb;
        $log = MstFactory::getLogger();
        $log->debug('writeListStatAdd: Add for list '.$listId.' '.$sendMailsIncCr.' mails and '.$sendRecipientsInCr.' recips');

        $listStatObj = $this->getListStatOfCurrentHourAndMinute($listId);
        if(!$listStatObj){
            $listStatId = $this->createNewListStatForCurrentHour($listId);
            $listStatObj = $this->getListStatById($listStatId);
        }

        $query = ' UPDATE ' . $wpdb->prefix . 'mailster_list_stats SET'
                . ' send_mails = send_mails+'.$sendMailsIncCr.','
                . ' send_recipients = send_recipients+'.$sendRecipientsInCr
                . ' WHERE list_id=\'' . $listId . '\''
                . ' AND stat_date = \''.$listStatObj->stat_date. '\''
                . ' AND stat_hour = \''.$listStatObj->stat_hour. '\''
                . ' AND stat_minute = \''.$listStatObj->stat_minute. '\'';
        $result = $wpdb->query( $query );

        if($result){
            $log->debug('writeListStatAdd: Update ok');
        }else{
            $log->error('writeListStatAdd: Update failed, query was: '.$query);
        }
        return $result;
    }

    public function getAllLists() {
	    global $wpdb;
	    $query = "SELECT '0' AS value, '- All lists -' AS list_choice UNION SELECT id AS value, name AS list_choice FROM " . $wpdb->prefix . "mailster_lists WHERE allow_subscribe = '1' OR allow_unsubscribe = '1'";
	    $result = $wpdb->get_results( $query );

	    return $result;
    }

	public function isSubscribed($listId) {
		$user = wp_get_current_user();
		if($user && $user->ID > 0){
            global $wpdb;
            $query = "SELECT * FROM " . $wpdb->prefix . "mailster_list_members WHERE list_id=" . $listId . " AND user_id = " . $user->ID . " AND is_core_user = 1 ";
            if($wpdb->get_results($query, OBJECT)) {
                return true;
            }
        }
        return false;
	}

}