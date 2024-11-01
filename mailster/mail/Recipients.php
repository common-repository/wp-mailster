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

class MstRecipients
{		

	public function updateRecipientInLists($userId, $isCoreUser){
        $log = MstFactory::getLogger();
		$lists = $this->getListsUserIsMemberOf($userId, $isCoreUser);
        if(count($lists)>0){
		    $this->recipientsUpdatedInLists($lists);
        }else{
            $log->debug('updateRecipientInLists there are not lists to be updated for user ID '.$userId.', isCoreUser: '.$isCoreUser);
        }
	}

    /**
     * @param int $userId
     * @param int $isCoreUser
     * @return int[]
     */
	public function getListsUserIsMemberOf($userId, $isCoreUser){
        /** @var MailsterModelUser $model */
		$model = MstFactory::getUserModel();
		$memberInfo = $model->getMemberInfo($userId, $isCoreUser);
		$usersLists = array();
		$lists = $memberInfo['lists'];
		for($i=0; $i<count($lists); $i++){
			$list = &$lists[$i];
			if(in_array($list->id, $usersLists) == false){
				$usersLists[] = $list->id; 
			}
		}
		$lists = $memberInfo['listGroups'];
		for($i=0; $i<count($lists); $i++){
			$list = &$lists[$i];
			if(in_array($list->id, $usersLists) == false){
				$usersLists[] = $list->id; 
			}
		}
		array_unique($usersLists);
		return $usersLists;
	}
	
	public function recipientsUpdatedInAllLists(){	
		$mListUtils = MstFactory::getMailingListUtils();
		$lists = $mListUtils->getAllMailingLists();
		foreach($lists AS $list){
			$this->recipientsUpdated($list->id);
		}
		return true;
	}
	
	public function recipientsUpdatedInLists($listIds){
        for($i=0;$i<count($listIds);$i++){
            $this->recipientsUpdated($listIds[$i]);
        }
	}
	
	public function recipientsUpdated($listId){		
		$log = MstFactory::getLogger();
        $log->debug('recipientsUpdated list ID '.$listId.', will update cache version');
		if($listId){
			if($listId != '' && $listId > 0){
				$cacheUtils = MstFactory::getCacheUtils();
				$cacheUtils->newRecipientState($listId);
				return;
			}else{
                $log->warning('recipientsUpdated list ID '.$listId.' not possible');
            }
		}
		$log->error('Cannot update Cache State for Recipients, listId not set');
		$log->error('listId: ' . $listId);
	}
	
	public function getRecipients($listId, $includeDigestInfo = false){
		$log = MstFactory::getLogger();
        /** @var MailsterModelDigest $digestModel */
		$digestModel = MstFactory::getDigestModel();
		$cacheUtils = MstFactory::getCacheUtils();
		$version = $cacheUtils->getRecipientState($listId);	
		$countOnly = 0;
        $log->debug('getRecipients list ID: '.$listId.', includeDigestInfo: '.($includeDigestInfo ? 'yes':'no'));
        $log->debug('getRecipients cache call recipients() with version '. $version);
		$res = $this->recipients($listId, $version);
        $log->debug('getRecipients result size: '.count($res));
		if($includeDigestInfo){
			for($i=0, $n=count( $res ); $i < $n; $i++) {
				$recipient = &$res[$i];
				$digests = $digestModel->getData(true, $recipient->user_id, $recipient->is_core_user, $listId);
				if(count($digests)>0){
					$recipient->digest = $digests[0];
				}else{
					$recipient->digest = false;
				}
			}
		}
		return $res;	
	}
		
	public function getTotalRecipientsCount($listId){
        $log = MstFactory::getLogger();
		$recips = $this->getRecipients($listId);
        $count = count($recips);
        $log->debug('getTotalRecipientsCount result: '.$count);
		return $count;
	}

	function recipients($listId, $cacheVersion){
		$log = MstFactory::getLogger();
		global $wpdb;
		$toSelect = ' SELECT CONVERT(name USING utf8mb4) as name, CONVERT(email USING utf8mb4) as email';

		$query = $toSelect.', user_id, is_core_user FROM ('
				. $toSelect . ', id AS user_id, \'0\' AS is_core_user' // all Mst users directly linked to list
				. ' FROM ' . $wpdb->prefix . 'mailster_users'
				. ' WHERE id in ('
					. ' SELECT user_id'
					. ' FROM ' . $wpdb->prefix . 'mailster_list_members'
					. ' WHERE list_id = \'' . $listId . '\''
					. ' AND is_core_user=\'0\''
				. ' )'
				. ' UNION'
                . ' SELECT CONVERT(GROUP_CONCAT(meta_value SEPARATOR \' \') USING utf8mb4) AS NAME, user_email AS email, user_id, is_core_user'
                . ' FROM  ('
                    . ' SELECT CONVERT(user_email USING utf8mb4) as user_email, ID AS user_id, \'1\' AS is_core_user, meta_value' // all WP users directly linked to list
                    . ' FROM ' . $wpdb->base_prefix . 'users wpusr'
                    . ' LEFT JOIN ' . $wpdb->base_prefix . 'usermeta wpusrmeta ON ( wpusr.id = wpusrmeta.user_id )'
                    . ' WHERE id in ('
                        . ' SELECT user_id'
                        . ' FROM ' . $wpdb->prefix . 'mailster_list_members'
                        . ' WHERE list_id = \'' . $listId . '\''
                        . ' AND is_core_user=\'1\''
                    . ' )'
                    . ' AND meta_key IN ( \'first_name\', \'last_name\' )'
                    . ' ORDER BY meta_key ASC'
                . ' ) DTBL'
                . ' GROUP BY user_email, user_id, is_core_user'
				. ' UNION'
				. $toSelect . ', id AS user_id, \'0\' AS is_core_user' // all Mst users linked through a Mst group
				. ' FROM ' . $wpdb->prefix . 'mailster_users'
				. ' WHERE id in ('
					. ' SELECT user_id'
					. ' FROM ' . $wpdb->prefix . 'mailster_group_users'
					. ' WHERE group_id in ('
						. ' SELECT group_id'
						. ' FROM ' . $wpdb->prefix . 'mailster_list_groups'
						. ' LEFT JOIN ' . $wpdb->prefix . 'mailster_groups gr ON (group_id=gr.id)'
						. ' WHERE list_id = \'' . $listId . '\''
						. ' AND gr.is_core_group=\'0\''
					. ' )'				
					. ' AND is_core_user=\'0\''
				. ' )'				
				. ' UNION'
                . ' SELECT CONVERT(GROUP_CONCAT(meta_value SEPARATOR \' \') USING utf8mb4) AS NAME, user_email AS email, user_id, is_core_user'
                . ' FROM  ('
                    . ' SELECT CONVERT(user_email USING utf8mb4) as user_email, ID AS user_id, \'1\' AS is_core_user, meta_value' // all WP users linked through a Mst group
                    . ' FROM ' . $wpdb->base_prefix . 'users wpusr'
                    . ' LEFT JOIN ' . $wpdb->base_prefix . 'usermeta wpusrmeta ON ( wpusr.id = wpusrmeta.user_id )'
                    . ' WHERE id in ('
                        . ' SELECT user_id'
                        . ' FROM ' . $wpdb->prefix . 'mailster_group_users'
                        . ' WHERE group_id in ('
                            . ' SELECT group_id'
                            . ' FROM ' . $wpdb->prefix . 'mailster_list_groups'
                            . ' LEFT JOIN ' . $wpdb->prefix . 'mailster_groups gr ON (group_id=gr.id)'
                            . ' WHERE list_id = \'' . $listId . '\''
                            . ' AND gr.is_core_group=\'0\''
                        . ' )'
                        . ' AND is_core_user=\'1\''
                    . ' ) '
                    . ' AND meta_key IN ( \'first_name\', \'last_name\' )'
                    . ' ORDER BY meta_key ASC'
                    . ' ) DTBL'
                    . ' GROUP BY user_email, user_id, is_core_user'
				. ' ORDER BY name, email';
		$query .= ') AS recips';

		$log->debug('recipients_query (cache version now: '.$cacheVersion.'): '.$query);
		
		$errorMsg = false;
		$result = false;
        try {
            $result = $wpdb->get_results( $query );
        } catch (Exception $e) {
            $errorMsg = 'Exception ' . get_class($e) . ' Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
        }
		if($wpdb->last_error || $errorMsg){
			$log->error('Recipients::recipients -> '.$errorMsg);
            $log->error('Recipients::recipients -> '.$wpdb->last_error);
			$log->error('Recipients::recipients -> recipients_query: '.$query);
		}
		return $result;	
	}
		
	function isRecipient($listId, $email, $gmailCompatible=true){
		$email = strtolower(trim($email));	
		$recipients = $this->getRecipients($listId);
		$recipCount = count($recipients);
		for($j = 0; $j < $recipCount; $j++) {
			$recipMail = strtolower(trim($recipients[$j]->email));
			if($recipMail === $email){
				return true;
			}
			if($gmailCompatible){
				if(str_replace('@gmail.com','@googlemail.com', $recipMail) === str_replace('@gmail.com','@googlemail.com', $email)){
					return true;
				}
			}
		}
		return false;		
	}	
	
}

