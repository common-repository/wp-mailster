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
	 * Digest Model
	 */
	class MailsterModelDigest extends MailsterModel
	{
		var $_data = null;
		var $_total = null;
		var $_pagination = null;

        function __construct($id = null){
            parent::__construct((int)$id);
        }

		function getDigestChoiceHtml($digestFreq = 0, $htmlId='digest_freq'){
			$digestOpts = array (
					MstConsts::DIGEST_NO_DIGEST => __("No digest", 'wp-mailster'),
					MstConsts::DIGEST_DAILY     => __("Daily digest", 'wp-mailster'),
					MstConsts::DIGEST_WEEKLY    => __("Weekly digest", 'wp-mailster'),
					MstConsts::DIGEST_MONTHLY   => 	__("Monthly digest", 'wp-mailster')
			);
			$digestChoice = "<select name='digest_freq' id='" . $htmlId . "'>";
			foreach($digestOpts as $key => $value) {
				if($key == $digestFreq) {
					$selected = "selected='selected'";
				} else {
					$selected = "";
				}
				$digestChoice .= "<option value='" . $key . "' " . $selected . " >" . $value . "</option>";
			}
			$digestChoice .= "</select>";
			return $digestChoice;
		}
		
		function getDigestStr($digestFreq = 0){
			switch($digestFreq){
				case MstConsts::DIGEST_NO_DIGEST:
					return __("No digest", 'wp-mailster');
					break;
				case MstConsts::DIGEST_DAILY:
					return __("Daily digest", 'wp-mailster');
					break;
				case MstConsts::DIGEST_WEEKLY:
					return __("Weekly digest", 'wp-mailster');
					break;
				case MstConsts::DIGEST_MONTHLY:
					return __("Monthly digest", 'wp-mailster');
					break;
			}
		}
		
		function getDigestSummaryStr($digestFreq = 0){
			switch($digestFreq){
				case MstConsts::DIGEST_NO_DIGEST:
					return __("No digest", 'wp-mailster');
					break;
				case MstConsts::DIGEST_DAILY:
					return __("Today's topic summary", 'wp-mailster');
					break;
				case MstConsts::DIGEST_WEEKLY:
					return __("Last week's topic summary", 'wp-mailster');
					break;
				case MstConsts::DIGEST_MONTHLY:
					return __("Last month's topic summary", 'wp-mailster');
					break;
			}
		}
		
		function isUserDigestRecipientOfList($userId, $isCoreUser, $listId){
			$log = MstFactory::getLogger();
			$log->debug('isUserDigestRecipientOfList(): '.$userId.', '.$isCoreUser.', '.$listId);
			$digests = $this->getDigestsOfUser($userId, $isCoreUser, $listId);
			if($digests && count($digests) > 0){
				for($i=0; $i<count($digests); $i++){
					if($digests[$i]->digest_freq > 0){
						return true;
					}
				}
			}
			return false;
		}

        /**
         * @param int $digestId Digest ID
         * @return null|stdClass Digest object
         */
        function getDigest($digestId){
			$log = MstFactory::getLogger();
			if($digestId > 0){
				$log->debug('getDigest(): '.$digestId);
				$digests = $this->getData(true, null, null, null, $digestId);
				if(count($digests)>0){
					return $digests[0];
				}else{
					return null;
				}
			}
			return null;
		}
		
		function getDigestsOfUser($userId, $isCoreUser, $listId=null){
			$log = MstFactory::getLogger();
			$digests = $this->getData(true, $userId, $isCoreUser, $listId, null);
            if(count($digests)){
                $log->debug('getDigestsOfUser() digests found for user ID: '.$userId.', core user: '.$isCoreUser.', list ID: '.$listId.': '.print_r($digests, true));
            }else{
                $log->debug('getDigestsOfUser() NO digests found for user ID: '.$userId.', core user: '.$isCoreUser.', list ID: '.$listId);
            }
			return $digests;
		}
		
		function getDataExcludingDigest2ArticleDigests(){
			return $this->getData(false, null, null, null, null, true);
		}
				
		function getData($overrideLimits=false, $userId=null, $isCoreUser=null, $listId=null, $digestId=null, $hideDigest2ArticleDigests=false)
		{
			$log = MstFactory::getLogger();
			global $wpdb;
			$log->debug('DigestModel::getData [override: '.$overrideLimits.', userId: '.$userId.', isCoreUser: '.$isCoreUser.', listId: '.$listId.', digestId: '.$digestId.']');
			$query = $this->_buildQuery($userId, $isCoreUser, $listId, $digestId, $hideDigest2ArticleDigests);
			$log = MstFactory::getLogger();
			//$log->debug('Query_in_digest_model: '.$query);
			$limitstart = 0; //$this->getState('limitstart'); //TODO FIX
			$limit = 30; //$this->getState('limit'); TODO FIX

			if(!$overrideLimits){
				$this->_data = $wpdb->get_results( $query . " LIMIT " . $limit . " OFFSET " . $limitstart ); // $this->_getList($query, $limitstart, $limit);
			}else{
				$this->_data = $wpdb->get_results($query);
			}			
			for($i=0, $n=count($this->_data); $i<$n; $i++){
				$this->_data[$i]->digestFreqStr = $this->getDigestStr($this->_data[$i]->digest_freq);
				$this->_data[$i]->summaryStr = $this->getDigestSummaryStr($this->_data[$i]->digest_freq);
			}
			return $this->_data;	
		}
			
		function getPagination()
		{
			return null;
		}
		
		function getTotal()
		{
			if (empty($this->_total)){
				global $wpdb;
				$query = $this->_buildQuery();
                $wpdb->get_results( $query );
				$this->_total = $wpdb->num_rows;
			}
			return $this->_total;
		}
		
		public function getTable($type = 'mailster_digests', $prefix = '', $config = array()){
			global $wpdb;
			return $wpdb->prefix . $type;
		}

		function _buildQuery($userId=null, $isCoreUser=null, $listId=null, $digestId=null, $hideDigest2ArticleDigests=false)
		{	
			global $wpdb;
			$where		= $this->_buildContentWhere($userId, $isCoreUser, $listId, $digestId, $hideDigest2ArticleDigests);
			$orderby	= $this->_buildContentOrderBy();
						
			$query = 'SELECT d.*';
			$query = $query	. ' FROM ' . $wpdb->prefix . 'mailster_digests d'
							. $where
							. $orderby;	
						
			return $query;
		}

		function _buildContentOrderBy()
		{
			$orderby 	= ' ORDER BY d.user_id, d.is_core_user';
			return $orderby;
		}

		function _buildContentWhere($userId, $isCoreUser, $listId, $digestId, $hideDigest2ArticleDigests)
		{	
			$where = array();
			if(!is_null($userId)){
				$where[] = 'd.user_id = \''.$userId.'\'';
			}
			if(!is_null($isCoreUser)){
				$where[] = 'd.is_core_user = \''.$isCoreUser.'\'';
			}
			if(!is_null($listId)){
				$where[] = 'd.list_id = \''.$listId.'\'';
			}
			if(!is_null($digestId)){
				$where[] = 'd.id = \''.$digestId.'\'';
			}
			if($hideDigest2ArticleDigests){
				$where[] = 'd.user_id > '.MstConsts::DIGEST_USER_ID_MEANING_DIGEST_TO_ARTICLE;
			}
			$where 		= ( count( $where ) ? ' WHERE ' . implode( ' AND ', $where ) : '' );	
			return $where;
		}
		
		function updateSendingDate($digestId){
			$log = MstFactory::getLogger();
			$log->debug('updateSendingDate for digest ID '.$digestId);
			$dbUtils = MstFactory::getDBUtils();
			$convUtils = MstFactory::getConverterUtils();
			$currDigest = $this->getDigest($digestId);
			if($currDigest){
				$log->debug('updateSendingDate digest before updating: '.print_r($currDigest, true));
				$currDigest->last_send_date = $dbUtils->getDateTimeNow();
				$currDigest->next_send_date = $this->getNextSendDate($digestId, $currDigest->digest_freq, true);
				$log->debug('updateSendingDate digest after updating: '.print_r($currDigest, true));
				$this->store($convUtils->object2Array($currDigest), false);
				return true;
			}
			return false;
		}
		
		function getNextSendDate($digestId, $digestFreq, $forceNewSendDate=false){
            $log = MstFactory::getLogger();
            $dbUtils = MstFactory::getDBUtils();
            $currDigest = null;
            $digestFreqChanged = false;
			if($digestId) {
                $currDigest = $this->getDigest($digestId);
                $digestFreqChanged = $currDigest->digest_freq !== $digestFreq;
            }
            if ( $digestId <= 0 || $digestFreqChanged || is_null( $currDigest->next_send_date ) || $forceNewSendDate ) {
                // new digest or digest frequency was changed
                $nextSendDate = 'NULL';
                switch ( $digestFreq ) {
                    case MstConsts::DIGEST_NO_DIGEST:
                        $log->debug('getNextSendDate Digest disabled -> set to NULL');
                        $nextSendDate = 'NULL';
                        break;
                    case MstConsts::DIGEST_DAILY:
                        $nextSendDate = $dbUtils->getDateTimeTomorrowNextMidnight();
                        $log->debug('getNextSendDate Daily digest -> tonight / next midnight is: '.$nextSendDate);
                        break;
                    case MstConsts::DIGEST_WEEKLY:
                        $nextSendDate = $dbUtils->getDateTimeNextSundayMidnight();
                        $log->debug('getNextSendDate Weekly digest -> next sunday at midnight is: '.$nextSendDate);
                        break;
                    case MstConsts::DIGEST_MONTHLY:
                        $nextSendDate = $dbUtils->getDateTimeThisMonthLastDayMidnight();
                        $log->debug('getNextSendDate Monthly digest -> last month day at midnight is: '.$nextSendDate);
                        break;
                }
                return $nextSendDate;
            } else {
                $log->debug('getNextSendDate existing digest, digest frequency did not change -> no change necessary');
                return $currDigest->next_send_date;
            }
		}
		
		function getDigest2ArticleDigests($listId){
			$data = array();
			$data['list_id'] = $listId;
			$data['user_id'] = MstConsts::DIGEST_USER_ID_MEANING_DIGEST_TO_ARTICLE; // means "non-user" digest --> which is an "archive 2 article" digest
			$data['is_core_user'] = 0;
			
			return $this->getData(true, $data['user_id'], $data['is_core_user'], $data['list_id']);
		}
		
		function storeDigest2ArticleDigest($listId, $autoUpdateSendDate=true){
			$log = MstFactory::getLogger();
			
			$data = array();			
			$data['list_id'] = $listId;
			$data['user_id'] = MstConsts::DIGEST_USER_ID_MEANING_DIGEST_TO_ARTICLE; // means "non-user" digest --> which is an "archive 2 article" digest
			$data['is_core_user'] = 0;			

			$existingDigests = $this->getDigest2ArticleDigests($listId);
			if(count($existingDigests)>0){ // already existing digest 2 article entry
				$log->debug('storeDigest2ArticleDigest: existing digests, take first of '.print_r($existingDigests, true));
				$data['id'] = $existingDigests[0]->id;
				$data['digest_freq'] = $existingDigests[0]->digest_freq;
				$data['last_send_date'] = $existingDigests[0]->last_send_date;
				$data['next_send_date'] = $existingDigests[0]->next_send_date;
			}else{
				$log->debug('storeDigest2ArticleDigest: current non existing article 2 digest, create one');
				$data['id'] = 0; // new entry
				$data['digest_freq'] = MstConsts::DIGEST_DAILY;
				$data['last_send_date'] = null;
				$data['next_send_date'] = null;
			}
						
			$log->debug('storeDigest2ArticleDigest: '.print_r($data, true));
			return $this->store($data, $autoUpdateSendDate);
		}
		
		function removeDigest2ArticleDigestIfExisting($listId){
			$log = MstFactory::getLogger();			
			$existingDigests = $existingDigests = $this->getDigest2ArticleDigests($listId);
			if(count($existingDigests)>0){ // already existing digest 2 article entry
				$log->debug('removeDigest2ArticleDigestIfExisting: existing digests, delete them: '.print_r($existingDigests, true));
				foreach($existingDigests AS $existingDigest){
					$log->debug('removeDigest2ArticleDigestIfExisting: delete digest ID '.$existingDigest->id);
					$this->delete(array($existingDigest->id));
				}
			}else{
				$log->debug('removeDigest2ArticleDigestIfExisting: non existing, nothing to delete');				
			}			
		}

        protected function getValidFields(){
            return array('id', 'list_id', 'user_id', 'is_core_user', 'digest_freq', 'last_send_date', 'next_send_date');
        }
		
		function store($data, $autoUpdateSendDate=true)
		{
			$log = MstFactory::getLogger();
			global $wpdb;

            if(is_object($data)){
                $data = (array)$data;
            }

            $log->debug('MailsterModelDigest->store data: '
                .print_r($data, true)."\r\n"
                ."autoUpdateSendDate: ".($autoUpdateSendDate?'yes':'no'));

			if($autoUpdateSendDate){
				$updatedNextSendDate = $this->getNextSendDate($data['id'], $data['digest_freq']);
                $log->debug('Updating next_send_date from '.(is_null($data['next_send_date']) ? 'NULL' : $data['next_send_date']).' to '.$updatedNextSendDate);
                $data['next_send_date'] = $updatedNextSendDate;
			}

			$cleanedData = array();
			foreach($data as $key => $value) {
                if(in_array($key, $this->getValidFields())){
                    $cleanedData[$key] = $value;
                }
			}

            if(!array_key_exists('id', $cleanedData) || $cleanedData['id'] === null || $cleanedData['id'] === 0 ){
                // when no id is provided, we need to double-check if there is already a digest with the same list_id/user_id/isCoreUser attributes
                $existingDigests = $this->getData(true, $cleanedData['user_id'], $cleanedData['is_core_user'], $cleanedData['list_id']);
                if($existingDigests && count($existingDigests) && $existingDigests[0] && property_exists($existingDigests[0], 'id')){
                    $cleanedData['id'] = $existingDigests[0]->id; // since there is already a matching digest, we will grab the digest ID to make sure the existing one is updated!
                }
            }

			if($cleanedData['id'] && $cleanedData['id'] > 0 ){
                $log->debug('MailsterModelDigest->store cleanedData to UPDATE: '.print_r($cleanedData, true));
				$wpdb->update( $wpdb->prefix . 'mailster_digests', $cleanedData, array("id" => $cleanedData['id']) );
                $digestId = $cleanedData['id'];
			} else {
                $log->debug('MailsterModelDigest->store cleanedData to INSERT: '.print_r($cleanedData, true));
                $wpdb->insert( $wpdb->prefix . 'mailster_digests', $cleanedData );
                $digestId = $wpdb->insert_id;
			}

            $log->debug('MailsterModelDigest->store return digestId '.$digestId);
							
			return $digestId;
		}
		
		function delete($cid = array())
		{
			global $wpdb;
			$result = false;

			if (count( $cid )){
				
				for($i=0;$i<count($cid);$i++){
					$digestId = $cid[$i];
					
					$query = 'DELETE FROM ' . $wpdb->prefix . 'mailster_digest_queue'
							. ' WHERE digest_id = \'' . $digestId . '\'';
					//$this->_db->setQuery( $query );
					$errorMsg = '';
                    try {
                        $result = $wpdb->get_results( $query );
                    } catch (Exception $e) {
                        $errorMsg = 'Exception ' . get_class($e) . ' Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
                        $this->setError($errorMsg, 'delete');
                    }
					if(!$result) {
						return false;
					}

					$query = 'DELETE FROM ' . $wpdb->prefix . 'mailster_digests'
							. ' WHERE id = \'' . $digestId . '\'';
					$errorMsg = '';
                    try {
                        $result = $wpdb->get_results( $query );
                    } catch (Exception $e) {
                        $errorMsg = 'Exception ' . get_class($e) . ' Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
                        $this->setError($errorMsg, 'delete');
                    }
					if(!$result) {
						return false;
					}
				}
			}

			return true;
		}

        function deleteDigestQueueEntriesOfMails($mailIds)
        {
        	global $wpdb;
            $result = false;

            if (count($mailIds)){
                $mailIdsStr = implode( ',', $mailIds);
                $query = 'DELETE FROM ' . $wpdb->prefix . 'mailster_digest_queue'
                    . ' WHERE mail_id IN ('. $mailIdsStr .')';
                
                $errorMsg = '';
                try {
                    $result = $wpdb->get_results( $query );
                } catch (Exception $e) {
                    $errorMsg = 'Error No: ' . $e->getCode() . ', Message: ' . $wpdb->last_error . ', '.$e->getMessage();
                    $this->setError($errorMsg, 'delete');
                }
                if(!$result) {
                    return false;
                }
            }

            return true;
        }

        function deleteDigestsOfNonExistingUsers(){
            global $wpdb;
            $result = false;
            $log = MstFactory::getLogger();

            $query = 'DELETE FROM ' . $wpdb->prefix . 'mailster_digest_queue WHERE digest_id IN ('
                . '    SELECT id AS digest_id FROM ('
                . '        SELECT c_dig.id, c_dig.user_id, is_core_user FROM (SELECT * FROM ' . $wpdb->prefix . 'mailster_digests WHERE is_core_user = \'1\') c_dig'
                . '        LEFT JOIN ' . $wpdb->base_prefix . 'users c_usr on (c_dig.user_id = c_usr.ID)'
                . '        WHERE c_usr.ID is null'
                . '    ) c_sel'
                . '    UNION'
                . '    SELECT id AS digest_id FROM ('
                . '        SELECT mst_dig.id, mst_dig.user_id, is_core_user FROM (SELECT * FROM ' . $wpdb->prefix . 'mailster_digests WHERE is_core_user = \'0\') mst_dig'
                . '        LEFT JOIN ' . $wpdb->base_prefix . 'mailster_users mst_usr on (mst_dig.user_id = mst_usr.id)'
                . '        WHERE mst_usr.id is null'
                . '    ) mst_sel'
                . ')';

            $errorMsg = '';
            try {
                $result = $wpdb->get_results( $query );
            } catch (Exception $e) {
                $errorMsg = 'Error No: ' . $e->getCode() . ', Message: ' . $wpdb->last_error . ', '.$e->getMessage() . ', query was: '.$query;
                $this->setError($errorMsg, 'delete');
                return false;
            }
            $log->debug('deleteDigestsOfNonExistingUsers digest queue -> affected: '.$wpdb->rows_affected);

            $query = 'DELETE FROM ' . $wpdb->prefix . 'mailster_digests WHERE id IN ('
                . '    SELECT id FROM ('
                . '        SELECT c_dig.id, c_dig.user_id, is_core_user FROM (SELECT * FROM ' . $wpdb->prefix . 'mailster_digests WHERE is_core_user = \'1\') c_dig'
                . '        LEFT JOIN ' . $wpdb->prefix . 'users c_usr on (c_dig.user_id = c_usr.ID)'
                . '        WHERE c_usr.ID is null'
                . '    ) c_sel'
                . '    UNION'
                . '    SELECT id FROM ('
                . '        SELECT mst_dig.id, mst_dig.user_id, is_core_user FROM (SELECT * FROM ' . $wpdb->prefix . 'mailster_digests WHERE is_core_user = \'0\') mst_dig'
                . '        LEFT JOIN ' . $wpdb->prefix . 'mailster_users mst_usr on (mst_dig.user_id = mst_usr.id)'
                . '        WHERE mst_usr.id is null'
                . '    ) mst_sel'
                . ')';

            $errorMsg = '';
            try {
                $result = $wpdb->get_results( $query );
            } catch (Exception $e) {
                $errorMsg = 'Error No: ' . $e->getCode() . ', Message: ' . $wpdb->last_error . ', '.$e->getMessage() . ', query was: '.$query;
                $this->setError($errorMsg, 'delete');
            }
            $log->debug('deleteDigestsOfNonExistingUsers digest objects -> affected: '.$wpdb->rows_affected);
            if(!$result) {
                return false;
            }

            return true;

        }
		
	}//Class end