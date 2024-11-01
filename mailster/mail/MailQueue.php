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

class MstMailQueue
{		
	public static function saveAndEnqueueMail($mail, $mList){
		$log = MstFactory::getLogger();
		$mstConf 	= MstFactory::getConfig();
		
		$enqueuingStart = time();
		
		if(empty($mail->thread_id)){
			$mail->thread_id = 0;
		}
		
		$has_send_report = '0';
		if($mList->save_send_reports > 0){
			$has_send_report = '1';
		}
		
		global $wpdb;

		$errorMsg = '';
		$wpdb->show_errors(true);

		$query = ' INSERT'
		         . ' INTO ' . $wpdb->prefix . 'mailster_mails'
		         . ' (id,'
		         . ' list_id,'
		         . ' thread_id,'
		         . ' receive_timestamp,'
		         . ' hashkey,'
		         . ' message_id,'
		         . ' in_reply_to,'
		         . ' references_to,'
		         . ' from_name,'
		         . ' from_email,'
		         . ' sender_user_id,'
		         . ' sender_is_core_user,'
                 . ' sender_dmarc_relevant,'
		         . ' orig_to_recips,'
		         . ' orig_cc_recips,'
		         . ' subject,'
		         . ' body,'
		         . ' html,'
		         . ' has_attachments,'
                 . ' content_type,'
		         . ' fwd_errors, fwd_completed,'
		         . ' blocked_mail, bounced_mail,'
                 . ' moderation_status,'
		         . ' has_send_report,'
		         . ' size_in_bytes)'
		         . ' VALUES'
		         . ' (NULL,'
		         . ' \'' . $mList->id . '\','
		         . ' \'' . $mail->thread_id . '\','
		         . ' \'' . $mail->receive_timestamp . '\','
		         . ' \'' .$wpdb->_real_escape($mail->hashkey) . '\','
		         . ' \'' .$wpdb->_real_escape($mail->message_id) . '\','
		         . ' \'' .$wpdb->_real_escape($mail->in_reply_to) . '\','
		         . ' \'' .$wpdb->_real_escape($mail->references_to). '\','
		         . ' \'' .$wpdb->_real_escape($mail->from_name) . '\','
		         . ' \'' .$wpdb->_real_escape($mail->from_email) . '\','
		         . ' \'' . $mail->sender_user_id . '\','
		         . ' \'' . $mail->sender_is_core_user . '\','
                 . ' \'' . $mail->sender_dmarc_relevant . '\','
		         . ' \'' .$wpdb->_real_escape($mail->orig_to_recips) . '\','
		         . ' \'' .$wpdb->_real_escape($mail->orig_cc_recips) . '\','
		         . ' \'' .$wpdb->_real_escape($mail->subject) . '\','
		         . ' \'' .$wpdb->_real_escape($mail->body) . '\','
		         . ' \'' .$wpdb->_real_escape($mail->html) . '\','
		         . ' \'' .$wpdb->_real_escape($mail->has_attachments) . '\','
                 . ' \'' .$wpdb->_real_escape($mail->content_type) . '\','
		         . ' \'0\', \'0\','
		         . ' \'0\', \'0\','
                 . ' \'0\','
		         . ' \''.$has_send_report.'\','
		         . ' \''.$mail->size_in_bytes.'\')';

        $log->debug('saveAndEnqueueMail() query: '.$query);

        $mstDB = MstFactory::getDB();

		try {
	        // save email to database
			$result = $mstDB->query($query, true);
			if( $result === false ) {
				$log->error('saveAndEnqueueMail() last error: '.$mstDB->last_error . " " . $mstDB->last_query);
                $errorMsg = $mstDB->last_error;
            }
			
        } catch (Exception $e) {
            $result = false;
            $errorMsg = 'Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage().', last error: '.$mstDB->last_error . ", query: " . $mstDB->last_query;
            $log->error('saveAndEnqueueMail() exception: '.$errorMsg);
        }
		$mailId = $mstDB->insert_id;
		
		if( $result === false ) {
			$log->error('saveAndEnqueueMail() Inserting of mail failed, ' . $errorMsg);
            $log->error('saveAndEnqueueMail() query was: '.$query);
            $log->info('saveAndEnqueueMail() Failed inserting email ' . $mail->subject . '  new id: ' . $mailId);
            $log->error('saveAndEnqueueMail() Email failed to be inserted was: '.print_r($mail, true));
		}else{
			$log->info('saveAndEnqueueMail() Saved mail for enqueuing ' . $mail->subject . '  new id: ' . $mailId);
            $log->debug('saveAndEnqueueMail() Email saved was: '.print_r($mail, true));
		}
		
		$mail->id = $mailId;
		$mail->list_id = $mList->id;
		
		// ####### TRIGGER NEW EVENT #######
		$mstEvents = MstFactory::getEvents();
		$mstEvents->newMailingListMail($mail->id);
		// #################################
				
		self::enqueueMail($mail, $mList);

		$enqueuingEnd = time();
		$log->debug('saveAndEnqueueMail - Time needed for enqueuing mail: ' . ($enqueuingEnd-$enqueuingStart));	
		
		return $mailId;
	}
	
	public static function resetMailAsUnblockedAndUnsent($mailId){		
		global $wpdb;
		$log = MstFactory::getLogger();
		$log->debug('Resetting mail status of mail ' . $mailId . ' to unsent');
		$query = ' UPDATE ' . $wpdb->prefix . 'mailster_mails SET'
				. ' fwd_completed_timestamp=NULL,'
				. ' bounced_mail = \'0\','
				. ' blocked_mail = \'0\','
				. ' fwd_errors = \'0\','
				. ' fwd_completed = \'0\''				
				. ' WHERE id=\'' . $mailId . '\'';

        try {
            $result = $wpdb->query( $query );
            if(!$result){
                $log->error('Resetting of mail status failed');
            }
        } catch (Exception $e) {
            $errorMsg = 'Exception ' . get_class($e) . ' Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
            $log->error('Resetting of mail status failed, exception: '.$e->getMessage());
        }
	}
	
	public static function enqueueMail($mail, $mList){	
		global $wpdb;		
		$log = MstFactory::getLogger();
		$mstConf 		= MstFactory::getConfig();
		$mstApp 		= MstFactory::getApplication();
		$sender			= MstFactory::getMailSender();
		$mstRecipients 	= MstFactory::getRecipients();
		
		$listId = $mList->id;
		$mailId = $mail->id;
		
		$log->debug('Enqueue mail ' . $mailId . ' in list ' . $listId);
		
		$recipients = $mstRecipients->getRecipients($listId);
				
		$fromName = $mail->from_name;
		$fromEmail = $mail->from_email;

		$log->debug('Copy to sender: ' . ( ($mList->copy_to_sender == 1) ? 'yes' : 'no') );
		if(!$mstRecipients->isRecipient($listId, $fromEmail) && $mList->copy_to_sender == 1){
			$log->debug('Sender: ' . $fromEmail . ' is not among the recipients, but sender copy is on, therefore add to recipients');
			$senderCopyEntry = new stdClass();
			$senderCopyEntry->name = $fromName;
			$senderCopyEntry->email = $fromEmail;
			$senderCopyEntry->user_id = -1; // the sender is no recipient, therefore not important whether he belongs to uers or not...
			$senderCopyEntry->is_core_user = -1; // ... set this to something that cannot be found - e.g. to avoid to send digests to sender
			$recipients[] = $senderCopyEntry;
		}
				
		$recipCount = count($recipients);
		$recC =  MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_REC);
		
		$log->info('Enqueuing recipients, count: ' . $recipCount);
		
		if($recipCount > $recC){
			$sender->sendMail2ListAdmin($mList, __( 'WP Mailster Send Error - too many recipients', 'wp-mailster' ), __( 'You are using an edition of WP Mailster which has a limitation in the number of recipients per mailing list. We strongly recommend to not use this edition with this amount of recipients, otherwise mails may be dropped. Consider to purchase an upgrade, more information:', 'wp-mailster' )." <a href='http://www.wpmailster.com'>http://www.wpmailster.com</a>" );
			$log->error('Too many recipients error,  recipCount: ' . $recipCount . ',  recC: ' . $recC);	
		}

		self::addMailInDigest2ArticleQueueIfApplicable($mail, $mList);

		$nrInsertsPerQuery = MstConsts::DB_QUEUED_INSERTS_PER_QUERY;
		$enqRecipCr = 0;
		$query = '';
		$validRecipNr = -1;
		for($i = 0; $i < $recipCount; $i++) {
			$recipient = &$recipients[$i];
			
			$log->debug('#'.($i+1).': '.print_r($recipient, true));
			$isValidRecip = self::isValidRecipient($mail, $recipient, $mList);
			if($isValidRecip){
				$isDigestRecip = self::isDigestRecipient($recipient, $mList);
				$log->debug('enqueueMail: Is '.$recipient->name.' <'.$recipient->email.'> digest recipient: ' . ($isDigestRecip ? 'Yes' : 'No'));
				if($isDigestRecip){
					$log->debug('enqueueMail: Is digest recipient, therefore do not store in mail queue, save in digest queue');
					self::enqueueDigestMailForRecipient($mail, $recipient, $mList);
				}else{
					$validRecipNr = $validRecipNr + 1; // increment recipient nr, first time bringing it to = 0
					$log->debug('enqueueMail: Valid recipient ' . $recipient->email);	
				    if($validRecipNr%$nrInsertsPerQuery == 0){	// if == 0 or can be divisible
						if($validRecipNr > 0){				
							$result = $wpdb->get_results( $query );
						}

					    $query = ' INSERT'
					             . ' INTO ' . $wpdb->prefix . 'mailster_queued_mails'
					             . ' (mail_id, name, email, error_count, lock_id, is_locked)'
					             . ' VALUES'
					             . ' (\''. $mailId . '\','
					             . ' \'' .$wpdb->_real_escape($recipient->name)  . '\','
					             . ' \'' .$wpdb->_real_escape($recipient->email) . '\','
					             . ' \'0\', \'0\', \'0\''
					             . ' )';
					 }else{
					    $query = $query
					             . ', (\''. $mailId . '\','
					             . ' \'' .$wpdb->_real_escape($recipient->name)  . '\','
					             . ' \'' .$wpdb->_real_escape($recipient->email) . '\','
					             . ' \'0\', \'0\', \'0\''
					             . ' )';
					 }
					 $enqRecipCr++;
				}
			}else{
				$log->debug('enqueueMail: Do not enqueue, no valid recipient: ' . $recipient->email);	
			}
		}		
		if($query != ''){			
			// we have to execute once again as still one or more recipients are queued
			$result = $wpdb->query( $query );
		}
		// ####### SAVE SEND EVENT  ########
		$sendEvents = MstFactory::getSendEvents();
		$sendEvents->newQueueMail($mailId, $enqRecipCr, $mail->size_in_bytes);
		// #################################
		
		return $enqRecipCr;
	}
	
	public static function addMailInDigest2ArticleQueueIfApplicable($mail, $mList){
		$log = MstFactory::getLogger();
		$log->debug('adding mail to digest');
		if($mList->archive_mode == MstConsts::ARCHIVE_MODE_ALL && $mList->archive2article > 0){
			$log->debug('addMailInDigest2ArticleQueueIfApplicable: archiving on, archive 2 article is active');
            /** @var MailsterModelDigest $digestModel */
			$digestModel = MstFactory::getDigestModel();
			$digests = $digestModel->getDigest2ArticleDigests($mList->id);
			if(count($digests)>0){
				$affRows = self::enqueueDigestMail($mail->id, $digests[0]->id, $mail->thread_id);
			}else{
				$log->warning('addMailInDigest2ArticleQueueIfApplicable: did not find digest for list ID '.$mList->id);
			}
		}else{
			$log->debug('addMailInDigest2ArticleQueueIfApplicable: archive 2 article is NOT active (general archiving: '.($mList->archive_mode == MstConsts::ARCHIVE_MODE_ALL?'ON':'OFF'));
		}
		$log->debug('finished archiving');
	}
	
	public static function isRecipientInToOrCcOrigHeaders($recipient, $origToRecips, $origCcRecips){
		$log = MstFactory::getLogger();
		foreach ($origToRecips As $origToRecip){
			if(strtolower(trim($recipient->email)) === strtolower(trim($origToRecip->email))){
				return true;
			}
		}
		foreach ($origCcRecips As $origCcRecip){
			if(strtolower(trim($recipient->email)) === strtolower(trim($origCcRecip->email))){
				return true;
			}
		}
		return false;
	}
		
	public static function isValidRecipient($mail, $recipient, $mList){ 
		$log = MstFactory::getLogger();
		$log->debug("FIX checking valid recipients");
		$recipientIsSender = strtolower(trim($recipient->email)) === strtolower(trim($mail->from_email));
		$copy2Sender = ($mList->copy_to_sender == 1 ? true : false);
		$senderVsRecipientValid = ( !$recipientIsSender || ( $recipientIsSender && $copy2Sender) );
		if(!empty($mail->to_email)){
			$isToRecipient = strtolower(trim($recipient->email)) === strtolower(trim($mail->to_email));
		}else{
			$isToRecipient = false;
		}
		$ccAndToAddressing = ($mList->reply_to_sender == 2 ? true : false);
		$addressingValid = ( !$ccAndToAddressing || ( $ccAndToAddressing && !$isToRecipient) );
		
		$isRecipAmongOrigRecips = false;
		if(($mList->addressing_mode == MstConsts::ADDRESSING_MODE_BCC) && ($mList->incl_orig_headers > 0)){
			$mstUtils = MstFactory::getUtils();
			$origToRecips = $mstUtils->jsonDecode($mail->orig_to_recips);
			$origCcRecips = $mstUtils->jsonDecode($mail->orig_cc_recips);
			$isRecipAmongOrigRecips = self::isRecipientInToOrCcOrigHeaders($recipient, $origToRecips, $origCcRecips);
			$log->debug('isValidRecipient -> BCC mode with include original headers -> checked whether '.$recipient->email . ' is among orig. TO/CC headers: '.($isRecipAmongOrigRecips ? 'yes':'no'));
		}
		$addressingValid = $addressingValid && !$isRecipAmongOrigRecips;
		
		$recipientValid = ($senderVsRecipientValid && $addressingValid);
		if(!$recipientValid){
			$log->debug('isValidRecipient -> recip ' . $recipient->email . ' INVALID'
						. ' - Reason: senderVsRecipientValid: ' . ($senderVsRecipientValid ? 'yes' : 'no') 
						. ', addressingValid: ' . ($addressingValid ? 'yes' : 'no')
						. ', ccAndToAddressing: ' . ($ccAndToAddressing ? 'yes' : 'no') 
						. ', isToRecipient:  ' . ($isToRecipient ? 'yes' : 'no')
						. ', recipientIsSender: ' . ($recipientIsSender ? 'yes' : 'no')
						. ', copy2Sender: ' . ($copy2Sender ? 'yes' : 'no')
						. ', isRecipAmongOrigRecips: ' . ($isRecipAmongOrigRecips ? 'yes' : 'no')
						);
		}
		return $recipientValid;
	}
	
	public static function isDigestRecipient($recipient, $mList){
        /** @var MailsterModelDigest $digestModel */
		$digestModel = MstFactory::getDigestModel();
		return $digestModel->isUserDigestRecipientOfList($recipient->user_id, $recipient->is_core_user, $mList->id);
	}
	
	public static function enqueueDigestMailForRecipient($mail, $recipient, $mList){
		$log = MstFactory::getLogger();
        /** @var MailsterModelDigest $digestModel */
		$digestModel = MstFactory::getDigestModel();
		$digests = $digestModel->getDigestsOfUser($recipient->user_id, $recipient->is_core_user, $mList->id);
		$log->debug('enqueueDigestMailForRecipient - enqueue mail ID '.$mail->id . ' in digest queue - found '.count($digests). ' fitting digests');
		global $wpdb;
		$enqueuedDigestMails = 0;
		for($i=0; $i < count($digests); $i++){
			$digestId = $digests[$i]->id;
			$log->debug('Enqueue mail ID '. $mail->id . ' in digest ID ' . $digestId . ' of '.$recipient->name . ' <'.$recipient->email.'> (user ID '. $recipient->user_id . ', isCoreUser: '. ($recipient->is_core_user?'Yes':'No') . ') for list ID ' .  $mList->id);
			$affRows = self::enqueueDigestMail($mail->id, $digestId, $mail->thread_id);
			if($affRows > 0){
				$enqueuedDigestMails++;
			}
		}
		return $enqueuedDigestMails;
	}

	public static function enqueueDigestMail($mailId, $digestId, $threadId){
		$log = MstFactory::getLogger();
		global $wpdb;

		$log->debug('Enqueue mail ID '. $mailId . ' (thread ID '.$threadId.') in digest ID ' . $digestId);
		$query = ' INSERT'
				. ' INTO ' . $wpdb->prefix . 'mailster_digest_queue'
				. ' (id, digest_id, mail_id, thread_id, digest_time)'
				. ' VALUES '
                    . '( '
                    . ' NULL,'
					. ' \''. $digestId . '\','
					. ' \''. $mailId . '\','
					. ' \''. $threadId . '\',' // thread_id is here 0, but will be updated later as soon as mail gets a thread_id
					. ' NOW()'
				    . ' )';
        $log->debug('enqueueDigestMail() query: '.$query);
		$errorMsg = '';
        $result = false;
        try {
        	$result = $wpdb->query( $query );
        } catch (Exception $e) {
            $errorMsg = 'Exception ' . get_class($e) . ' Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
            $log->error('enqueueDigestMail exception: '.$errorMsg);
        }
		$newId = $wpdb->insert_id;
		if($newId < 1){
			$log->error('enqueueDigestMail Inserting of digest queue mail failed (db err: '.$wpdb->last_error .') ' . $errorMsg . ', query was: '.$query);
		}else{
			$log->debug('enqueueDigestMail Successfully saved digest queue entry');
		}
		return $result;
			
	}
	
	
	
	public static function saveNonQueueMail($mail, $mList, $blocked, $bounced, $filtered, $moderated){
		$log = MstFactory::getLogger();
		// The blocked_mail field has the following coding:
		// 0 - not blocked
		// 1 - blocked because of not authorized sender
		// 2 - mail filtered
		$blockedMail = $blocked ? MstConsts::MAIL_FLAG_BLOCKED_BLOCKED : MstConsts::MAIL_FLAG_BLOCKED_NOT_BLOCKED;
		$blockedMail = $filtered ? MstConsts::MAIL_FLAG_BLOCKED_FILTERED : $blockedMail;
		$bouncedMail = $bounced ? MstConsts::MAIL_FLAG_BOUNCED_BOUNCED : MstConsts::MAIL_FLAG_BOUNCED_NOT_BOUNCED;
		if($blocked || $filtered || $bounced){
		    // blocked/filtered/bounced emails will not be moderated
		    $moderatedMail = MstConsts::MAIL_FLAG_MODERATED_NOT_MODERATED;
        }else{
            $moderatedMail = $moderated ? MstConsts::MAIL_FLAG_MODERATED_IN_MODERATION : MstConsts::MAIL_FLAG_MODERATED_NOT_MODERATED;
        }

		global $wpdb;
		$query = ' INSERT'
                 . ' INTO ' . $wpdb->prefix . 'mailster_mails'
		         . ' (id, list_id, receive_timestamp,'
		         . ' hashkey,'
		         . ' message_id,'
		         . ' in_reply_to,'
		         . ' references_to,'
		         . ' from_name,'
		         . ' from_email,'
		         . ' sender_user_id,'
		         . ' sender_is_core_user,'
                 . ' sender_dmarc_relevant,'
		         . ' orig_to_recips,'
		         . ' orig_cc_recips,'
		         . ' subject,'
		         . ' body,'
		         . ' html,'
		         . ' has_attachments,'
                 . ' content_type,'
		         . ' fwd_errors, fwd_completed,'
		         . ' blocked_mail, bounced_mail,'
                 . ' moderation_status,'
		         . ' has_send_report,'
		         . ' size_in_bytes)'
		         . ' VALUES'
		         . ' (NULL, \''. $mList->id . '\', \'' . $mail->receive_timestamp . '\','
		         . ' \'' .$wpdb->_real_escape($mail->hashkey) . '\','
		         . ' \'' .$wpdb->_real_escape($mail->message_id) . '\','
		         . ' \'' .$wpdb->_real_escape($mail->in_reply_to) . '\','
		         . ' \'' .$wpdb->_real_escape($mail->references_to). '\','
		         . ' \'' .$wpdb->_real_escape($mail->from_name) . '\','
		         . ' \'' .$wpdb->_real_escape($mail->from_email) . '\','
		         . ' \'' . $mail->sender_user_id . '\','
		         . ' \'' . $mail->sender_is_core_user . '\','
                 . ' \'' . $mail->sender_dmarc_relevant . '\','
		         . ' \'' .$wpdb->_real_escape($mail->orig_to_recips) . '\','
		         . ' \'' .$wpdb->_real_escape($mail->orig_cc_recips) . '\','
		         . ' \'' .$wpdb->_real_escape($mail->subject) . '\','
		         . ' \'' .$wpdb->_real_escape($mail->body) . '\','
		         . ' \'' .$wpdb->_real_escape($mail->html) . '\','
		         . ' \'' .$wpdb->_real_escape($mail->has_attachments) . '\','
                 . ' \'' .$wpdb->_real_escape($mail->content_type) . '\','
		         . ' \'0\', \'0\','
		         . ' \'' . $blockedMail  . '\', \'' . $bouncedMail  . '\','
                 . ' \'' . $moderatedMail  . '\','
		         . ' \'0\','
		         . ' \''.$mail->size_in_bytes.'\')';
		$errorMsg = '';
        $log->debug('saveNonQueueMail() query: '.$query);

        $mstDB = MstFactory::getDB();

        $result = false;
        try {
        	$result = $mstDB->query($query, true);
            if( $result === false ) {
                $log->error('saveNonQueueMail() Inserting failed, last error: '.$mstDB->last_error . " " . $mstDB->last_query);
                $errorMsg = $mstDB->last_error;
            }
        } catch (Exception $e) {
            $result = false;
            $errorMsg = 'Exception ' . get_class($e) . ' Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
            $log->error('saveNonQueueMail() exception: '.$errorMsg);
        }
		$mail->id = $mstDB->insert_id;

        if( $result === false ) {
            $log->error('saveNonQueueMail() Inserting of mail failed, ' . $errorMsg);
            $log->error('saveNonQueueMail() query was: '.$query);
            $log->error('saveNonQueueMail() Failed inserting email ' . $mail->subject . '  new id: ' . $mail->id);
            $log->error('saveNonQueueMail() Email failed to be inserted was: '.print_r($mail, true));
        }else{
            $log->info('saveNonQueueMail() Saved mail for enqueuing ' . $mail->subject . '  new id: ' . $mail->id);
            $log->debug('saveNonQueueMail() Email saved was: '.print_r($mail, true));
        }
		
		if($mail->id < 1){
			$log->error('saveNonQueueMail() Inserting of non-queue mail failed, ' . $errorMsg);
            $log->error('saveNonQueueMail() query was: '.$query);
		}else{
			$log->info('saveNonQueueMail() Saved non-queue mail ' . $mail->subject . '  new id: ' . $mail->id);
		}		
				
		// ####### TRIGGER NEW EVENT #######
		$mstEvents = MstFactory::getEvents();
		if($bounced){
			$mstEvents->newBouncedMail($mail->id);
		}elseif($blocked){
            if(!property_exists($mail, 'sendUnauthSenderNotification')
                || (property_exists($mail, 'sendUnauthSenderNotification') && $mail->sendUnauthSenderNotification)){
			    $mstEvents->newBlockedMail($mail->id);
            }else{
                $log->debug('saveNonQueueMail() Do not trigger blocked email notification for "' . $mail->subject . '"  (id: ' . $mail->id.')');
            }
		}elseif($filtered){
			$mstEvents->newFilteredMail($mail->id);
		}elseif($moderated){
            // request for moderation will be sent outside this function - a little later until attachments etc. are also stored and ready
            $mstEvents->newModeratedMail($mail->id);

            // ####### SAVE SEND EVENT  ########
            $sendEvents = MstFactory::getSendEvents();
            $sendEvents->newModeratedMail($mail->id);
            // #################################
        }
		// #################################


		
		return $mail->id;
	}
	
	public static function removeAllRecipientsOfMailFromQueue($mailId){
		$log = MstFactory::getLogger();
		global $wpdb;		
		$query = 'DELETE FROM  ' . $wpdb->prefix . 'mailster_queued_mails'
				. ' WHERE mail_id = \'' . $mailId . '\'';
        $res	= $wpdb->query($query);
        $log->debug('removeAllRecipientsOfMailFromQueue: removing all queue entries of mail with id ' . $mailId.' -> '.$res);
        return $res;
	}
	
	public static function removeMailFromQueue($mailId, $email){
		$log = MstFactory::getLogger();
		global $wpdb;
        $res = $wpdb->delete(
			$wpdb->prefix . 'mailster_queued_mails',
			array( 
				'mail_id' => $mailId,
				'email' => $email
			),
			array( '%d', "%s" )
		);
        $log->debug('removeMailFromQueue: removing mail id ' . $mailId . ' / email: ' . $email.' -> '.$res);
        return $res;
	}

    public static function clearQueue(){
        $log = MstFactory::getLogger();
        global $wpdb;
        $query = 'DELETE FROM ' . $wpdb->prefix . 'mailster_queued_mails';
        $res = $wpdb->query($query);
        $log->info('clearQueue: clear complete queue, number of removed entries: '.intval($res));
        return $res;
    }

	public static function removeAllMailsFromListFromQueue($listId){
		$log = MstFactory::getLogger();
		global $wpdb;		
		$log->debug('removeAllMailsFromListFromQueue: removing all mails from mailing list: ' . $listId);
		$mailsInQueue = self::getPendingMailsOfMailingList($listId);
		for($i=0; $i<count($mailsInQueue); $i++){
			$mail = &$mailsInQueue[$i];
			/* $query = 'DELETE FROM  ' . $wpdb->prefix . 'mailster_queued_mails'
					. ' WHERE mail_id = \'' . $mail->id . '\''; */
			$wpdb->delete( 
				$wpdb->prefix . 'mailster_queued_mails',
				array( 
					'mail_id' => $mail->id
				),
				array( '%d' )
			);
		}
		return true;	
	}
	
	public static function removeDigestMailsFromQueue($digest){
		$log = MstFactory::getLogger();
		$log->debug('removeDigestMailsFromQueue: removing queue mails of digest with id ' . $digest->id . ' added before: ' . $digest->next_send_date);
		global $wpdb;
        $query = 'DELETE FROM ' . $wpdb->prefix . 'mailster_digest_queue'
            . ' WHERE digest_id =\''. $digest->id . '\''
            . ' AND digest_time < \''. $digest->next_send_date . '\'';
        $errorMsg = '';
        try {
            $log->debug('removeDigestMailsFromQueue: query: '.$query);
            $affRows = $wpdb->query( $query );
            $log->debug('removeDigestMailsFromQueue: affected rows when removing queue mails of digest with id ' . $digest->id . ' : '.$affRows.' rows');
        } catch (Exception $e) {
            $log->error('removeDigestMailsFromQueue: query: '.$query);
            $errorMsg = 'Exception ' . get_class($e) . ' Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
            $log->error('removeDigestMailsFromQueue error: '.$errorMsg);
            return false;
        }
	}
	
	public static function getNumberOfQueueEntriesForMail($mailId){
		$log = MstFactory::getLogger();
		$log->debug('Get number of recipients for mail ' . $mailId . ' in queue');
		global $wpdb;
		$query = 'SELECT * FROM ' . $wpdb->prefix . 'mailster_queued_mails'
				. ' WHERE mail_id=\'' . $mailId . '\' ';
		$recipients = $wpdb->get_results( $query );
		return $wpdb->num_rows;
	}
	
	public static function getNextRecipientsInQueue($mailId, $limit){
		$log = MstFactory::getLogger();
		$log->debug('getNextRecipientsInQueue Get ' . $limit . ' recipients from queue for mail ' . $mailId);
		$limitStr = ($limit > 0 ? ('LIMIT ' . $limit) : '');
		global $wpdb;
		$query = 'SELECT * FROM ' . $wpdb->prefix . 'mailster_queued_mails'
				. ' WHERE mail_id=\'' . $mailId . '\' '
				. ' AND '
					. ' ( (is_locked=\'0\') '
						. ' OR ((is_locked=\'1\') AND (last_lock < DATE_SUB(NOW(), INTERVAL 3 MINUTE)) )'
					. ' )' 
				. $limitStr;
		$recipients = $wpdb->get_results( $query );
		$log->debug('getNextRecipientsInQueue Found ' . $wpdb->num_rows . ' recipients in queue for mail ' . $mailId);
		
		$lockedRecipients = array();		
		for($i=0;$i<count($recipients);$i++){
			$recip = $recipients[$i];
			$recipCurr = self::getRecipientInfo($recip->mail_id, $recip->email); // get current info
			$log->debug('Get current recipient info: '.print_r($recipCurr, true));
			
			if($recip->lock_id != $recipCurr->lock_id){
				$log->debug('Lock ID changed - another instance worked with recipient, skip it');
				continue;
			}
			
			$log->debug('Will lock recipient of mail '.$recip->mail_id.' ('.$recip->email.'), lock ID to increment: '.$recip->lock_id);
			self::lockRecipient($recip->mail_id, $recip->email);
			$isLockOk = self::checkRecipientLock($recip->mail_id, $recip->email, ($recip->lock_id+1));
			$log->debug('Lock ok: '.($isLockOk ? 'true':'false'));
			if($isLockOk){
				$lockedRecipients[] = $recip;
			}else{
				while(!$isLockOk){
					$log->debug('Search for new recipient to lock...');
					$query = 'SELECT * FROM ' . $wpdb->prefix . 'mailster_queued_mails' // don't include invalid locks as we have included already with above query
							. ' WHERE mail_id=\'' . $mailId . '\' '
							. ' AND is_locked=\'0\' '  
							. ' LIMIT 1';
					$recipient = $wpdb->get_row( $query );
					if($recipient){
						$log->debug('Recipient found: ' . print_r($recipient, true));
						self::lockRecipient($recipient->mail_id, $recipient->email);
						$isLockOk = self::checkRecipientLock($recipient->mail_id, $recipient->email, ($recipient->lock_id+1));
						if($isLockOk){
							$log->debug('This recipient locked successfully');
							$lockedRecipients[] = $recipient;
						}else{
							$log->debug('Recipient NOT locked successfully');
						}
					}else{ // no more recipients for this email in queue, so get out of this loop
						$log->debug('No more recipient found');
						$isLockOk = true; 
						break; // just to be sure...
					}
				}
			}
		}
		$log->debug('Found and locked recipients: ' . count($lockedRecipients) . ' -> ' . print_r($lockedRecipients, true));
		return $lockedRecipients;
	}
	 
	public static function lockRecipient($mailId, $email){
		global $wpdb;
		$query = ' UPDATE ' . $wpdb->prefix . 'mailster_queued_mails SET'
				. ' is_locked = \'1\','
				. ' lock_id = lock_id+1,' // increment lock ID
				. ' last_lock = NOW()'					
				. ' WHERE mail_id=\'' . $mailId . '\''
				. ' AND email= \'' . $wpdb->_real_escape($email) . '\'';
		$wpdb->query( $query );
	}
	
	public static function checkRecipientLock($mailId, $email, $lockId){		
		$recip = self::getRecipientInfo($mailId, $email);
		if(($recip->is_locked > 0) && ($recip->lock_id == $lockId)){
			return true;
		}
		return false;
	}
	
	public static function getRecipientInfo($mailId, $email){
		global $wpdb;
		$query = 'SELECT * FROM ' . $wpdb->prefix . 'mailster_queued_mails'
				. ' WHERE mail_id=\'' . $mailId . '\' '
				. ' AND email=\'' . $wpdb->_real_escape($email) . '\'';
		$recip = $wpdb->get_row( $query );		
		return $recip;
	}
	
	public static function getErrorCountOfMail($mailId){
		global $wpdb;
		$query = 'SELECT fwd_errors FROM  ' . $wpdb->prefix . 'mailster_mails'
		. ' WHERE id = \'' . $mailId . '\'';
		return $wpdb->get_var( $query );
	}
	
	public static function getErrorCountOfQueueMail($mailId, $email){
		global $wpdb;
		$query = 'SELECT error_count FROM  ' . $wpdb->prefix . 'mailster_queued_mails'
		. ' WHERE mail_id = \'' . $mailId . '\''
		. ' AND email = \'' . $wpdb->_real_escape($email) . '\''
		. ' LIMIT 1';
		return  $wpdb->get_var( $query );
	}
	
	public static function incrementError($mailId, $email, $maxSendAttempts){
        global $wpdb;
		$log = MstFactory::getLogger();
		$errorCount = self::getErrorCountOfQueueMail($mailId, $email);
		$log->debug('Increment error count for ' . $mailId 
					. ', before: ' . $errorCount 
					. ' (maxSendAttempts: ' .  $maxSendAttempts . ')');	
		if(($errorCount + 1) < $maxSendAttempts){
			// increment error and unlock message
			$query = 'UPDATE ' . $wpdb->prefix . 'mailster_queued_mails'
				. ' SET error_count=error_count+1,'
				. ' is_locked=\'0\''
				. ' WHERE mail_id = \'' . $mailId . '\''
				. ' AND email = \'' . $wpdb->_real_escape($email) . '\''
				. ' LIMIT 1';
            $affRows = $wpdb->query( $query );

			$query = 'UPDATE ' . $wpdb->prefix . 'mailster_mails'
				. ' SET fwd_errors=fwd_errors+1'
				. ' WHERE id = \'' . $mailId . '\'';
            $affRows = $wpdb->query( $query );
			
			$mailSendErrorCount = self::getErrorCountOfMail($mailId);
			if($mailSendErrorCount >= $maxSendAttempts){
				// ####### SAVE SEND EVENT  ########
				$sendEvents = MstFactory::getSendEvents();
				$recipInfoArr = array("email" => $email);
				$remainingRecipCount = self::getNumberOfQueueEntriesForMail($mailId); 
				$sendEvents->sendingAbortedDueErrors($mailId, $recipInfoArr, $mailSendErrorCount, $maxSendAttempts, $remainingRecipCount);
				// #################################
			}
		}else{			
			// ####### SAVE SEND EVENT  ########
			$sendEvents = MstFactory::getSendEvents();
			$recipInfoArr = array("email" => $email);
			$mailSendErrorCount = self::getErrorCountOfMail($mailId);
			$sendEvents->recipientQueueRemovalDueErrors($mailId, $recipInfoArr, $errorCount, $mailSendErrorCount, $maxSendAttempts);
			// #################################
			
			self::removeMailFromQueue($mailId, $email);
		}
	}	
	
	public static function sendingComplete($mailId){
		global $wpdb;
		$log = MstFactory::getLogger();
		$listUtils = MstFactory::getMailingListUtils();
		$listId = $listUtils->getMailingListIdByMailId($mailId);
		$mList = $listUtils->getMailingList($listId);
        $query = null;

        $nowTime = new DateTime('now', new DateTimeZone('UTC'));
        $nowTimeDbFormatStr = $nowTime->format('Y-m-d H:i:s');

		if($mList->archive_mode == MstConsts::ARCHIVE_MODE_ALL){
			$log->debug('Set mail as sent, keep content for archive');
			$query = 'UPDATE ' . $wpdb->prefix . 'mailster_mails'
					. ' SET fwd_completed = \'1\','
					. ' fwd_completed_timestamp = \''.$nowTimeDbFormatStr.'\''
					. ' WHERE id = \'' . $mailId . '\'';	
		}elseif($mList->archive_mode == MstConsts::ARCHIVE_MODE_NO_CONTENT){
			$log->debug('Set mail as sent, do not keep content for archive');
			$attachUtils = MstFactory::getAttachmentsUtils();
			$attachUtils->deleteAttachmentsOfMail($mailId);
			$query = 'UPDATE ' . $wpdb->prefix . 'mailster_mails'
					. ' SET fwd_completed = \'1\','
					. ' fwd_completed_timestamp = \''.$nowTimeDbFormatStr.'\','
					. ' body = null,'
					. ' html = null,'
					. ' no_content = \'1\''
					. ' WHERE id = \'' . $mailId . '\'';	
		}

		if($query) {
            $affRows = $wpdb->query($query);
            $log->debug('sendingComplete (set fwd_completed_timestamp to '.$nowTimeDbFormatStr.'), affRows: '.$affRows);
            return $affRows;
        }
        $log->debug('sendingComplete no query executed, archive mode: '.$mList->archive_mode);
		return 0;
	}
	
	public static function getPendingMails() {
		global $wpdb;
		$query = ' SELECT m.*'
				. ' FROM ' . $wpdb->prefix . 'mailster_mails m'
				. ' WHERE m.list_id in ('
					. ' SELECT iml.id'
					. ' FROM ' . $wpdb->prefix . 'mailster_lists iml'
					. ' WHERE iml.active =\'1\' )'
				. ' AND m.fwd_completed =\'0\''				
				. ' AND m.bounced_mail = \'0\''
				. ' AND m.blocked_mail = \'0\''
            	. ' AND m.moderation_status >= \'0\''
				. ' AND m.fwd_errors < (' 
					. ' SELECT l.max_send_attempts'
					. ' FROM ' . $wpdb->prefix . 'mailster_lists l' 
					. ' WHERE m.list_id = l.id LIMIT 1)'
				. ' ORDER BY m.receive_timestamp';
		$mails = $wpdb->get_results( $query );
		return $mails;	
	}
		
	public static function getPendingMailsOfMailingList($listId) {
		global $wpdb;
		$query = ' SELECT m.*'
				. ' FROM ' . $wpdb->prefix . 'mailster_mails m'
				. ' WHERE m.list_id =\'' . $listId . '\''
				. ' AND m.fwd_completed =\'0\''				
				. ' AND m.bounced_mail = \'0\''
				. ' AND m.blocked_mail = \'0\''
            	. ' AND m.moderation_status >= \'0\''
				. ' AND m.fwd_errors < (' 
					. ' SELECT l.max_send_attempts'
					. ' FROM ' . $wpdb->prefix . 'mailster_lists l' 
					. ' WHERE m.list_id = l.id LIMIT 1)'
				. ' ORDER BY m.receive_timestamp';
		$mails = $wpdb->get_results( $query );
		return $mails;	
	}
	
	public static function getPendingDigests(){
		global $wpdb;
		$query = ' SELECT d.*'
				. ' FROM ' . $wpdb->prefix . 'mailster_digests d'
				. ' WHERE d.digest_freq > 0' // digest_freq = 0 means no digest (inactive digests)			
				. ' AND d.next_send_date < NOW()'
				. ' ORDER BY d.next_send_date';	
		$digests = $wpdb->get_results( $query );
		return $digests;	
	}
	
	public static function getPendingDigestsOfMailingList($listId){
		global $wpdb;
		$query = ' SELECT d.*'
				. ' FROM ' . $wpdb->prefix . 'mailster_digests d'
				. ' WHERE d.digest_freq > 0' // digest_freq = 0 means no digest (inactive digests)		
				. ' AND d.list_id =\'' . $listId . '\''
				. ' AND d.next_send_date < NOW()'
				. ' ORDER BY d.next_send_date';		
		$digests = $wpdb->get_results( $query );
		return $digests;
	}
	
	function getMailQueueForDigest($digestId){
        global $wpdb;
		$log = MstFactory::getLogger();
        /** @var MailsterModelDigest $digestModel */
		$digestModel = MstFactory::getDigestModel();
		$digest = $digestModel->getDigest($digestId);
		$query = ' SELECT dq.*'
				. ' FROM ' . $wpdb->prefix . 'mailster_digest_queue dq'
				. ' WHERE dq.digest_id =\'' . $digestId . '\''
				. ' AND dq.digest_time <\'' . $digest->next_send_date . '\''
				. ' ORDER BY dq.thread_id, dq.digest_time';	
		global $wpdb;
		$digests = $wpdb->get_results( $query );
		$log->debug('getMailQueueForDigest query: '.$query);
		$log->debug('getMailQueueForDigest res: ');
		$log->debug(print_r($digests, true));
		return $digests;
	}
	
	public static function getAllPendingMails($limitedCols = false){
        global $wpdb;
		if($limitedCols){
			$query = 'SELECT m.mail_id, m.name, m.email, m.is_locked, ma.id, ma.subject';			
		}else{
			$query = 'SELECT m.*, ma.*';
		}
		$query = $query
					. ' FROM ' . $wpdb->prefix . 'mailster_queued_mails m'
					. ' LEFT JOIN ' . $wpdb->prefix . 'mailster_mails ma ON (m.mail_id = ma.id)';
		global $wpdb;
		$mails = $wpdb->get_results( $query );
		return $mails;	
	}
	
	public static function getPendingMailsThatExceededMaxSendAttempts($listId=false){
        global $wpdb;
		$query = ' SELECT m.*'
		. ' FROM ' . $wpdb->prefix . 'mailster_mails m'
		. ' WHERE m.fwd_completed =\'0\''
		. ' AND bounced_mail = \'0\''
		. ' AND blocked_mail = \'0\''
        . ' AND moderation_status >= \'0\''
		. ($listId ? (' AND  m.list_id =\'' . $listId . '\'') : '')
		. ' AND m.fwd_errors >= ('
			. ' SELECT max_send_attempts'
			. ' FROM ' . $wpdb->prefix . 'mailster_lists l'
			. ' WHERE m.list_id = l.id LIMIT 1)'
		. ' AND 0 < ('
			. ' SELECT COUNT(*)'
			. ' FROM ' . $wpdb->prefix . 'mailster_queued_mails qm'
			. ' WHERE qm.mail_id = m.id)'
		. ' ORDER BY m.receive_timestamp';
		global $wpdb;
		$mails = $wpdb->get_results( $query );
		return $mails;
	}
		
}