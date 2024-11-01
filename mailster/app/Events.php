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
	
	class MstEvents
	{
	
		public static function sendError($mailId, $errorMsg){
			$log = MstFactory::getLogger();
			$log->debug('MstEvents::sendError');
			$dateUtils 	= MstFactory::getDateUtils();
			$mailUtils = MstFactory::getMailUtils();
			$mailingListUtils = MstFactory::getMailingListUtils();
			$mail = $mailUtils->getMail($mailId);
			$mList = $mailingListUtils->getMailingList($mail->list_id);
			$subject = sprintf( __('Send error for email on the mailing list %s', 'wp-mailster'), $mList->name);
			$body = sprintf(__('Subject of the mail: %s    Error message: %s', 'wp-mailster'), $mail->subject, $errorMsg);
						
			$sendEvents = MstFactory::getSendEvents();
			$sendEventsLimit = 100; // limit to 100 send events
			$sendReport = $sendEvents->getSendEventsForMail($mailId, $sendEventsLimit); 
			if(count($sendReport)>0){
				$sendReportHtml = ('
				<table>
					<tr>
						<th width="130px">'.__( 'Date', 'wp-mailster' ).'</th>
						<th width="20px">&nbsp;</th>
						<th width="200px">'. __( 'Event', 'wp-mailster' ) .'</th>
						<th width="650px">'. __( 'Description', 'wp-mailster' ) . '</th>
					</tr>');
				foreach($sendReport AS $event){
                    // TOO FIX THIS WRONG PATH !!!!!!!!!!!!!!!!!!!!!!!1
                    // And workaround for sometimes not working get_home_path function!
                    if(function_exists('get_home_path')){
                        $imgSrcPath = get_home_path() . 'admin/' . $event->imgPath;
                    }else{
                        $imgSrcPath = 'admin/' . $event->imgPath;
                    }
					$sendReportHtml .= ('<tr>
											<td width="130px">'. $dateUtils->formatDate($event->event_time). '</td>
											<td width="20px"><img src="'. $imgSrcPath . '" /></td>
											<td width="200px">'. $event->name . '</td>
											<td width="650px">'. $event->desc . '</td>
										</tr>');
				}
				if(count($sendReport) == $sendEventsLimit){
					$sendReportHtml .= ('<tr>
											<td width="130px"> </td>
											<td width="20px"> </td>
											<td width="200px"> </td>
											<td width="650px"> - snip - </td>
										</tr>');
				}
				$sendReportHtml .= '</table>';
			}else{
				$sendReportHtml = '';
			}

			$body = '<html><header></header><body>'.$body.$sendReportHtml.'</body></html>';
			
			$triggerType = MstEventTypes::SEND_ERROR;
			self::newMailingListEvent($mail->list_id, $triggerType, $subject, $body, true);
			$log->debug('MstEvents::sendError Sent notification');
		}
		
		public static function getSenderString($fromName, $fromEmail){
			$senderStr = (!is_null($fromName) && (strlen($fromName)>0)) ? ($fromName . ' ') : '';
			$senderStr .= '<'.$fromEmail.'>';
			return $senderStr;
		}
		
		public static function newMailingListMail($mailId){
			$log = MstFactory::getLogger();
			$log->debug('MstEvents::newMailingListMail');
			$mailUtils = MstFactory::getMailUtils();
			$mailingListUtils = MstFactory::getMailingListUtils();
			$mail = $mailUtils->getMail($mailId);
			$mList = $mailingListUtils->getMailingList($mail->list_id);
			$subject = sprintf(__( 'New email on the mailing list %s', 'wp-mailster'), $mList->name);
			$body = sprintf(__( 'Sender of the mail: %s', 'wp-mailster'), self::getSenderString($mail->from_name, $mail->from_email));
			$body .= "\r\n".sprintf(__('Subject of the mail: %s', 'wp-mailster'), $mail->subject);
			$triggerType = MstEventTypes::NEW_LIST_MAIL;
			self::newMailingListEvent($mail->list_id, $triggerType, $subject, $body);
			$log->debug('MstEvents::newMailingListMail Sent notification');
		}
		
		public static function newBouncedMail($mailId){	
			$log = MstFactory::getLogger();
			$log->debug('MstEvents::newBouncedMail');
			$config = MstFactory::getConfig();
			$mailUtils = MstFactory::getMailUtils();
			$mailingListUtils = MstFactory::getMailingListUtils();
			$mail = $mailUtils->getMail($mailId);
			$mList = $mailingListUtils->getMailingList($mail->list_id);
			$subject = sprintf(__( 'Bounced email on the mailing list %s', 'wp-mailster'), $mList->name);
			$htmlMail = false;
			if($config->includeBodyInBouncedBlockedNotifications()){
				$log->debug('MstEvents::newBouncedMail Include text in notification');
				$body = sprintf(__( 'Subject of the mail: %s', 'wp-mailster'), $mail->subject);
				if(!is_null($mail->html) && (strlen(trim($mail->html)) > 0)){
					$htmlMail = true;
					$body = htmlentities($body);
					$body .= '<br/><hr/><br/>';
					$body .= $mail->html;
					$body .= '<br/><hr/>';
				}else{
					$htmlMail = false;
					$body .= "\r\n\r\n";
					$body .= $mail->body;
				}				
			}else{
				$log->debug('MstEvents::newBouncedMail Do NOT include text in notification');
				$body = sprintf(__( 'Subject of the mail: %s', 'wp-mailster'), $mail->subject);
			}
			$triggerType = MstEventTypes::NEW_BOUNCED_MAIL;
			self::newMailingListEvent($mail->list_id, $triggerType, $subject, $body, $htmlMail);
			$log->debug('MstEvents::newBouncedMail Sent notification');
		}
		
		public static function newBlockedMail($mailId){	
			$log = MstFactory::getLogger();
			$log->debug('MstEvents::newBlockedMail');
			$config = MstFactory::getConfig();
			$mailUtils = MstFactory::getMailUtils();
			$mailingListUtils = MstFactory::getMailingListUtils();
			$mail = $mailUtils->getMail($mailId);
			$mList = $mailingListUtils->getMailingList($mail->list_id);
			$subject = sprintf(__( 'Blocked email on the mailing list %s', 'wp-mailster'), $mList->name);
			$htmlMail = false;
			if($config->includeBodyInBouncedBlockedNotifications()){
				$log->debug('MstEvents::newBlockedMail Include text in notification');
				$body = sprintf(__( 'Sender of the mail: %s', 'wp-mailster'), self::getSenderString($mail->from_name, $mail->from_email));
				if(!is_null($mail->html) && (strlen(trim($mail->html)) > 0)){
					$htmlMail = true;
					$body = htmlentities($body);
					$body .= '<br/>'.htmlentities(sprintf(__( 'Subject of the mail: %s', 'wp-mailster'), $mail->subject));
					$body .= '<br/><hr/><br/>';
					$body .= $mail->html;
					$body .= '<br/><hr/>';
				}else{
					$htmlMail = false;
					$body .= "\r\n".sprintf(__( 'Subject of the mail: %s', 'wp-mailster'), $mail->subject);
					$body .= "\r\n\r\n";
					$body .= $mail->body;
				}				
			}else{
				$log->debug('MstEvents::newBlockedMail Do NOT include text in notification');
				$body = sprintf(__( 'Sender of the mail: %s', 'wp-mailster'), self::getSenderString($mail->from_name, $mail->from_email));
				$body .= "\r\n".sprintf(__( 'Subject of the mail: %s', 'wp-mailster'), $mail->subject);
			}
			$triggerType = MstEventTypes::NEW_BLOCKED_MAIL;
			self::newMailingListEvent($mail->list_id, $triggerType, $subject, $body, $htmlMail);
			$log->debug('MstEvents::newBlockedMail Sent notification');
		}
		
		public static function newFilteredMail($mailId){
			$log = MstFactory::getLogger();
			$log->debug('MstEvents::newFilteredMail');
			$mailUtils = MstFactory::getMailUtils();
			$mailingListUtils = MstFactory::getMailingListUtils();
			$mail = $mailUtils->getMail($mailId);	
			$mList = $mailingListUtils->getMailingList($mail->list_id);
			$subject = sprintf(__( 'Filtered email on the mailing list %s', 'wp-mailster'), $mList->name);
			$body = sprintf(__( 'Sender of the mail: %s', 'wp-mailster'), self::getSenderString($mail->from_name, $mail->from_email));
			$body .= "\r\n".sprintf(__( 'Subject of the mail: %s', 'wp-mailster'), $mail->subject);
			$triggerType = MstEventTypes::NEW_FILTERED_MAIL;
			self::newMailingListEvent($mail->list_id, $triggerType, $subject, $body);
			$log->debug('MstEvents::newFilteredMail Sent notification');
		}


        public static function newModeratedMail($mailId){
            $log = MstFactory::getLogger();
            $log->debug('MstEvents::newModeratedMail');
            $mailUtils = MstFactory::getMailUtils();
            $mailingListUtils = MstFactory::getMailingListUtils();
            $mail = $mailUtils->getMail($mailId);
            $mList = $mailingListUtils->getMailingList($mail->list_id);
            $subject =sprintf(__( 'Moderated email on the mailing list %s', 'wp-mailster'), $mList->name);
            $htmlMail = false;
            $body = sprintf(__( 'Sender of the mail: %s', 'wp-mailster'), self::getSenderString($mail->from_name, $mail->from_email));
            if(!is_null($mail->html) && (strlen(trim($mail->html)) > 0)){
                $htmlMail = true;
                $body = htmlentities($body);
                $body .= '<br/>'.htmlentities(sprintf(__( 'Subject of the mail: %s', 'wp-mailster'), $mail->subject));
                $body .= '<br/><hr/><br/>';
                $body .= $mail->html;
                $body .= '<br/><hr/>';
            }else{
                $htmlMail = false;
                $body .= "\r\n".sprintf(__( 'Subject of the mail: %s', 'wp-mailster'), $mail->subject);
                $body .= "\r\n\r\n";
                $body .= $mail->body;
            }
            $triggerType = MstEventTypes::NEW_MODERATED_MAIL;
            self::newMailingListEvent($mail->list_id, $triggerType, $subject, $body, $htmlMail);
            $log->debug('MstEvents::newModeratedMail Sent notification');
        }
		
		public static function userSubscribedOnWebsite($name, $email, $listId){	
			$log = MstFactory::getLogger();
			$log->debug('MstEvents::userSubscribedOnWebsite');
			$mailingListUtils = MstFactory::getMailingListUtils();
			$mList = $mailingListUtils->getMailingList($listId);
			$subject = sprintf(__( 'User subscribed to the mailing list %s', 'wp-mailster'), $mList->name);
			$body = sprintf(__('%s (%s) registered at the mailing list %s', 'wp-mailster'), $name, $email, $mList->name);
			$triggerType = MstEventTypes::USER_SUBSCRIBED_ON_WEBSITE;
			self::newMailingListEvent($listId, $triggerType, $subject, $body);
			$log->debug('MstEvents::userSubscribedOnWebsite Sent notification');
		}
		
		public static function userUnsubscribedOnWebsite($email, $listId){
			$log = MstFactory::getLogger();
			$log->debug('MstEvents::userUnsubscribedOnWebsite');
			$mailingListUtils = MstFactory::getMailingListUtils();
			$mList = $mailingListUtils->getMailingList($listId);
			$subject = sprintf(__( 'User unsubscribed from the mailing list %s', 'wp-mailster'), $mList->name);
			$body = sprintf(__('User with the email address %s unsubscribed from the mailing list %s', 'wp-mailster'), $email, $mList->name);
			$triggerType = MstEventTypes::USER_UNSUBSCRIBED_ON_WEBSITE;
			self::newMailingListEvent($listId, $triggerType, $subject, $body);
			$log->debug('MstEvents::userUnsubscribedOnWebsite Sent notification');
		}
		
		private static function newMailingListEvent($listId, $triggerType, $subject, $body, $htmlMail=false){
			$log = MstFactory::getLogger();
			$log->debug('MstEvents::newMailingListEvent');
			$mailUtils = MstFactory::getMailUtils();
			$notifyUtils = MstFactory::getNotifyUtils();
			$mailingListUtils = MstFactory::getMailingListUtils();

			if(MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_NOTIFY)){
				$mailingListNotifies = $notifyUtils->getNotifiesOfMailingList($listId);
				$mList = $mailingListUtils->getMailingList($listId);
				$replyTo = array($mList->admin_mail, '');
				$log->debug('Mailing list notifications in place for list ID '.$listId.' '.print_r($mailingListNotifies, true));			
				for($i=0; $i<count($mailingListNotifies); $i++){
					$notify = $mailingListNotifies[$i];
					if($notifyUtils->notifyMatches($notify, MstNotify::NOTIFY_TYPE_LIST_BASED, $triggerType)){
                        $mail = null;
                        try{
                            $log->debug('Notify will be sent out: ' . $notifyUtils->getTriggerTypeStr($triggerType));
                            $mail = $notifyUtils->getNotifyMailTmpl($notify, $subject, $body, $replyTo, $listId, $htmlMail);
                            $sendOk = $mail->Send(); // send notification
                            $error =  $mail->IsError();
                            if($error == true) { // send errors?
                                $log->error('Sending of notify failed! Last error: ' . $mail->ErrorInfo);
                                $log->error('Email was: '.print_r($mail, true));
                            }
                        }catch(Exception $e){
                            $log->error('MstEvents::newMailingListEvent Sending Exception for Trigger '.$notifyUtils->getTriggerTypeStr($triggerType).' for list ID '.$listId.': '.$e->getMessage());
                            $log->error('Email was: '.print_r($mail, true));
                        }
					}
				}
			}else{
				$log->warning('MstEvents::newMailingListEvent Did not send notification due to check procedure');
			}
		}
		
		public static function mailIsNotForwarded($listId, $subject, $senderName, $senderEmail, $senderBlocked=false, $emailFilteredByWords=false, $emailTooLarge=false){	
			$log = MstFactory::getLogger();
			$log->debug('MstEvents::mailIsNotForwarded');
			$mailUtils = MstFactory::getMailUtils();
			$notifyUtils = MstFactory::getNotifyUtils();
			$mailingListUtils = MstFactory::getMailingListUtils();
						
			$mList = $mailingListUtils->getMailingList($listId);
			
			if($mList->notify_not_fwd_sender == 1){
				$log->debug('Notification to sender of not fowarded email will be sent out');
				$replyTo = array($mList->admin_mail, '');

                if(strtolower(trim($senderEmail)) === strtolower(trim($mList->list_mail))){
                    $log->info('Event will NOT be send out as blocked sender (and receiver of notification) is the mailing list address: '.$senderEmail);
                    return; // exit now
                }

                if((trim($senderEmail) === '') || (trim($senderEmail) === '<>') || (strpos(trim($senderEmail), 'MISSING-HOST-NAME') !== false)){
                    $log->info('Event will NOT be send out as blocked sender (and receiver of notification) is empty: '.$senderEmail);
                    return; // exit now
                }
				
				if($senderBlocked){	
					$cause = sprintf(__( 'The email address %s is not allowed to send emails to the mailing list', 'wp-mailster'), $senderEmail );
				}elseif($emailFilteredByWords){
					$cause = __( 'Email was filtered because of the email content (usage of forbidden words)', 'wp-mailster' );
				}elseif($emailTooLarge){
					$maxEmailSize = $mList->mail_size_limit;
					$cause = sprintf(__('Maximum allowed email size of %d kByte was exceeded', 'wp-mailster'), $maxEmailSize );
				}else{ // Unknown cause?! Whatever, notify sender anyway 					
					$cause = __( 'Email not forwarded' , 'wp-mailster' );
				}
				
				$log->debug('Cause for not forwarded email: ' . $cause);
				
				$notificationSubject = sprintf(__( "Email to mailing list \"%s\" was not forwarded", 'wp-mailster'), $mList->name);
				$notificationBody = sprintf(__("The email with the subject \"%s\" was not forwarded due the following reason: %s", 'wp-mailster'), $subject, $cause);

				$mail = $notifyUtils->getSenderNotifyMailTmpl($senderName, $senderEmail, $notificationSubject, $notificationBody, $replyTo, $listId);
				$log->debug('Prepared notify mail: ' . print_r($mail, true));
				try {
					$sendOk = $mail->Send(); // send notification
					$error = $mail->IsError();
					if ($error == true) { // send errors?
						$log->error('Sending of notify failed! Last error: ' . $mail->ErrorInfo);
						$log->error('Email was: ' . print_r($mail, true));
					} else {
						$log->debug('Sender was successfully notified');
					}
				}catch(Exception $e){
					$log->error('MstEvents::mailIsNotForwarded Sending of notify failed, caught exception: '.$e->getMessage());
					$log->error('Email was: '.print_r($mail, true));
				}
			}
		}		
		
	}

