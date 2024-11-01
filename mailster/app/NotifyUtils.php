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
	
	class MstNotifyUtils
	{
        public function __construct(){
            $notify = MstFactory::getNotify(); // load class into classpath
        }
		
		public function MstNotifyUtils(){
			self::__construct();
		}
		
		public function createNewNotify(){
			$notify = new MstNotify(); // create a really new instance
			return $notify;
		}
		
		public function getNotifyMailTmpl($notify, $subject, $body, $replyTo, $listId=null, $htmlMail=false){
            $log = MstFactory::getLogger();
			if(!is_null($listId)){
				$mailSender = MstFactory::getMailSender();
				$mailingListUtils = MstFactory::getMailingListUtils();
				$mList = $mailingListUtils->getMailingList($listId);
				$mail = $mailSender->getListMailTmpl($mList);
			}else{
				$mail =& MstFactory::getMailer();
				$mail->ClearAllRecipients();	  
			}
			try {
                $mail->addReplyTo($replyTo[0], $replyTo[1]);
            } catch (Exception $e) {
                $exceptionErrorMsg = 'Exception ' . get_class($e) . ' Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
                $log->error('getNotifyMailTmpl addReplyTo for '.print_r($replyTo, true).' caused exception: '.$exceptionErrorMsg);
            }
			$mail->FromName = 'WP Mailster';
			
			//$mail->addCustomHeader(MstConsts::MAIL_HEADER_RETURN_PATH . ': <>'); // try to set return path to NULL
			$mail->addCustomHeader(MstConsts::MAIL_HEADER_AUTO_SUBMITTED . ': auto-generated'); // indicate this was generated and we do not want a response
		
			$recipients = $this->getNotifyRecipients($notify);
			$mail->SingleTo = true; // one mail per recipient
			
			for($j=0; $j<count($recipients); $j++){
				$recip = &$recipients[$j];
				try{
				    $mail->AddAddress($recip->email, $recip->name); // add all recipients of this notify
                } catch (Exception $e) {
                    $exceptionErrorMsg = 'Exception ' . get_class($e) . ' Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
                    $log->error('getNotifyMailTmpl AddAdress for recipient '.$recip->email.' (name: '.$recip->name.') caused exception: '.$exceptionErrorMsg);
                }
			}
			
			$mail->setSubject($subject);
			if($htmlMail){
				$mail->IsHTML(true);
			}
			$mail->setBody($body);	
			
			return $mail;
		}
		
		public function getSenderNotifyMailTmpl($senderName, $senderEmail, $subject, $body, $replyTo, $listId=null){
            $log = MstFactory::getLogger();
			if(!is_null($listId)){
				$mailSender = MstFactory::getMailSender();
				$mailingListUtils = MstFactory::getMailingListUtils();
				$mList = $mailingListUtils->getMailingList($listId);
				$mail = $mailSender->getListMailTmpl($mList);
			}else{
				$mail = MstFactory::getMailer();
				$mail->ClearAllRecipients();	  
			}
			try{
				$mail->addReplyTo($replyTo[0], $replyTo[1]);
            } catch (Exception $e) {
                $exceptionErrorMsg = 'Exception ' . get_class($e) . ' Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
                $log->error('getSenderNotifyMailTmpl addReplyTo for '.print_r($replyTo, true).' caused exception: '.$exceptionErrorMsg);
            }
			$mail->FromName = 'WP Mailster';
			
			//$mail->addCustomHeader(MstConsts::MAIL_HEADER_RETURN_PATH . ': <>'); // try to set return path to NULL
			$mail->addCustomHeader(MstConsts::MAIL_HEADER_AUTO_SUBMITTED . ': auto-generated'); // indicate this was generated and we do not want a response
					
			 try{
			    $mail->AddAddress($senderEmail, $senderName); // add all recipients of this notify
            } catch (Exception $e) {
                $exceptionErrorMsg = 'Exception ' . get_class($e) . ' Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
                $log->error('getSenderNotifyMailTmpl AddAdress for recipient '.$senderEmail.' (name: '.$senderName.') caused exception: '.$exceptionErrorMsg);
            }
			
			$mail->setSubject($subject);
			$mail->setBody($body);	
			
			return $mail;
		}
		
		public function getNotifyRecipients($notify){
            $log = MstFactory::getLogger();
			$recipients = array();
			if(is_null($notify->target_type)){
				return '';
			}
			switch($notify->target_type){
				case MstNotify::TARGET_TYPE_LIST_ADMIN:
					$listUtils = MstFactory::getMailingListUtils();
					$mList = $listUtils->getMailingList($notify->list_id);
					$recip = new stdClass();
					$recip->email = $mList->admin_mail;
					$recip->name = '';
					$recipients[] = $recip;
					break;
				case MstNotify::TARGET_TYPE_CORE_USER:
					$user = get_user_by( "ID", $notify->user_id );
					$recip = new stdClass();
					$recip->email = $user->email;
					$recip->name = $user->name;
					$recipients[] = $recip;
					break;
				case MstNotify::TARGET_TYPE_USER_GROUP:
					$groupUsersModel = MstFactory::getGroupUsersModel();
					$users = $groupUsersModel->getData($notify->group_id);
					$recipients = $users; // replace array
					break;
			}
            $log->debug('Notify ID '.$notify->target_type.' yielded recipients: '.print_r($recipients, true));
			return $recipients;
		}
		
		public function notifyMatches($notify, $notifyType, $triggerType){
			if($notify->notify_type == $notifyType){
				if($notify->trigger_type == $triggerType){
					return true;
				}
			}
			return false;
		}
		
		public function storeNotify($notify){
			$log = MstFactory::getLogger();
			global $wpdb;
			
			if($notify->id > 0){ // already existing, need to update
                $log->debug('storeNotify - UPDATE: '.print_r($notify, true));
				$query = 'UPDATE ' . $wpdb->prefix . 'mailster_notifies SET '
							. ' notify_type =\'' . $notify->notify_type . '\','
							. ' trigger_type =\'' . $notify->trigger_type . '\','
							. ' target_type =\'' . $notify->target_type . '\','
							. ' list_id =\'' . $notify->list_id . '\','
							. ' user_id =\'' . $notify->user_id . '\','
							. ' group_id =\'' . $notify->group_id . '\''
							. ' WHERE id=\'' . $notify->id . '\'';		
				$wpdb->query($query);
				$errorMsg = '';
                try {
                    $result = $wpdb->update(
                    	$wpdb->prefix . 'mailster_notifies',
                    	array(
                    		'notify_type' => $notify->notify_type,
                    		'trigger_type' => $notify->trigger_type,
                    		'target_type' => $notify->target_type,
                    		'list_id' => $notify->list_id,
                    		'user_id' => $notify->user_id,
                    		'group_id' => $notify->group_id,
                    		),
                    	array( "id" => $notify->id),
                    	array(
                    		"%d",
                    		"%d",
                    		"%d",
                    		"%d",
                    		"%d",
                    		"%d",
                    		),
                    	array("%d")
                    	);
                } catch (Exception $e) {
                    $errorMsg = 'Exception ' . get_class($e) . ' Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
                    $log->error('Updating of notification failed, errorMsg: ' . $errorMsg.', query was: '.$query);
                    return false;
                }
				
				if($result === false){
					$log->error('Updating of notification failed, errorMsg: ' . $errorMsg.', query was: '.$query);
					return false;
				}else{
					$log->debug('Successfully updated notification ID ' . $notify->id);
					return true;
				}				
			}else{ // new notify
                $log->debug('storeNotify - NEW: '.print_r($notify, true));
				$query = 'INSERT INTO '. $wpdb->prefix . 'mailster_notifies ('
							. ' id,'
							. ' notify_type,'
							. ' trigger_type,'
							. ' target_type,'
							. ' list_id,'
							. ' user_id,'
							. ' group_id'
						. ') VALUES ('
							. ' NULL, \'' 	
							. $notify->notify_type . '\', \''
						 	. $notify->trigger_type . '\', \'' 
						 	. $notify->target_type . '\', \'' 
						 	. $notify->list_id . '\', \'' 
						 	. $notify->user_id . '\', \'' 
						 	. $notify->group_id . '\''
						. ')';
				
				$errorMsg = '';
                try {
                    $wpdb->query( $query );
                } catch (Exception $e) {
                    $errorMsg = 'Exception ' . get_class($e) . ' Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
                }
				
				$notifyId = $wpdb->insert_id; 
				if($notifyId < 1){
					$log->error('Failed to insert new notification, errorMsg: ' . $errorMsg.', query was: '.$query);
					return false;
				}else{
					$log->debug('New notification ID: ' . $notifyId);
					return true;
				}
			}
		}
		
		
		
		public function deleteNotify($notifyId){
			$log = MstFactory::getLogger();
			$log->debug('Deleting notify ' . $notifyId . '...');
			global $wpdb;
			$result = $wpdb->delete(
				$wpdb->prefix . "mailster_notifies",
				array("id" => $notifyId),
				array("%d")
			);

			$affRows = $result;
			if($affRows > 0){
				$log->debug('Successfully deleted notify ' . $notifyId);
				return true;
			}
			return false;
		}
		
		public function getNotifiesOfMailingList($listId){
			global $wpdb;
			$log = MstFactory::getLogger();
			$query = 'SELECT * FROM ' . $wpdb->prefix . 'mailster_notifies '
					. ' WHERE list_id =\'' . $listId . '\'';
			$notifies = $wpdb->get_results( $query );
			
			for($i=0; $i<count($notifies); $i++){
				$dbNotify = &$notifies[$i];
				$notify = $this->createNewNotify();
				foreach ($dbNotify as $varName => $val) { // add db data to instance
		            $notify->$varName = $val;
		        }
		        $notifies[$i] = $notify;
			}
           // $log->debug('getNotifiesOfMailingList for list '.$listId.': '.print_r($notifies, true));
			
			return $notifies;	
		}
		
		public function getNotifyTypeStr($notifyType){
			if(!is_null($notifyType)){
				switch($notifyType){
					case MstNotify::NOTIFY_TYPE_GENERAL:
						return __( "General notification", 'wp-mailster' );
						break;
					case MstNotify::NOTIFY_TYPE_LIST_BASED:
						return __( "Mailing list notification", 'wp-mailster' );
						break;
				}
			}
			return '';
		}
		public function getTriggerTypeStr($triggerType){
			if(!is_null($triggerType)){
				switch($triggerType){
					case MstEventTypes::NEW_LIST_MAIL:
						return __( "New email/post", 'wp-mailster' );
						break;
					case MstEventTypes::NEW_BLOCKED_MAIL:
						return __( "Sender blocked", 'wp-mailster' );
						break;
					case MstEventTypes::NEW_BOUNCED_MAIL:
						return __( "Bounced email", 'wp-mailster' );
						break;
					case MstEventTypes::NEW_FILTERED_MAIL:
						return __( "Filtered email", 'wp-mailster' );
						break;
					case MstEventTypes::USER_SUBSCRIBED_ON_WEBSITE:
						return __( "User subscribed", 'wp-mailster' );
						break;
					case MstEventTypes::USER_UNSUBSCRIBED_ON_WEBSITE:
						return __( "User unsubscribed", 'wp-mailster' );
						break;
					case MstEventTypes::SEND_ERROR:
						return __( "Send error", 'wp-mailster' );
						break;
				}
			}
			return '';
		}
		
		public function getAvailableTriggerTypes(){
			$triggerTypes = array();
			$triggers = MstEventTypes::getAllTriggerTypes();			
			foreach ($triggers as $t) {
                $triggerTypes[$t] = $this->getTriggerTypeStr($t);
                /*
				$trigger = new stdClass();
				$trigger->type = $t;
				$trigger->name = $this->getTriggerTypeStr($t);
				$triggerTypes[] = $trigger;
                */
			}
			return $triggerTypes;
		}
		
		public function getTargetTypeStr($targetType){
			if(!is_null($targetType)){
				switch($targetType){
					case MstNotify::TARGET_TYPE_LIST_ADMIN:
						return __( "List administrator", 'wp-mailster' );
						break;
					case MstNotify::TARGET_TYPE_CORE_USER:
						return __( "CMS user", 'wp-mailster' );
						break;
					case MstNotify::TARGET_TYPE_USER_GROUP:
						return __( "User group", 'wp-mailster' );
						break;
				}
			}
			return '';
		}	
		
		public function getAvailableTargetTypes(){
			$targetTypes = array();
			$targets = array();
			$targets[] = MstNotify::TARGET_TYPE_CORE_USER;
			$targets[] = MstNotify::TARGET_TYPE_LIST_ADMIN;
			$targets[] = MstNotify::TARGET_TYPE_USER_GROUP;
			
			foreach ($targets as $t) {
				$target = new stdClass();
				$target->type = $t;
				$target->name = $this->getTargetTypeStr($t);
				$targetTypes[] = $target;
			}
			return $targetTypes;
		}
		
	}
