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
							

class MstMailingListMailbox
{
	private $mBox;
	private $mList;
		
	function open($mailingList){
		
		$this->mList	= $mailingList;
		
		$log = MstFactory::getLogger();
		$mstConfig	= MstFactory::getConfig();
		$useSecAuth = intval($this->mList->mail_in_use_sec_auth) !== 0 ? '/secure' : '';
		if(is_null($this->mList->mail_in_use_secure) || $this->mList->mail_in_use_secure === ''){
			$useSec = '/notls'; // against opportunistic STARTTLS
		}else{
			$useSec = '/' . $this->mList->mail_in_use_secure;
		}
		$protocol = $this->mList->mail_in_protocol !== '' ? '/' . $this->mList->mail_in_protocol : '';
		$host =  '{'. trim($this->mList->mail_in_host) . ':' 
					. trim($this->mList->mail_in_port) 
					. $useSecAuth 
					. $protocol 
					. $useSec 
					. $this->mList->mail_in_params 
					. '}'. 'INBOX';
		$log->debug($this->mList->name . ': ' . $host . '   user: ' . $this->mList->mail_in_user, MstConsts::LOGENTRY_MAIL_RETRIEVE);	
		
		$openTimeoutOld = imap_timeout(IMAP_OPENTIMEOUT);
		$chgTimeoutSuccess = imap_timeout(IMAP_OPENTIMEOUT, $mstConfig->getMailboxOpenTimeout());
		if($chgTimeoutSuccess){
			$log->debug('Changed default IMAP_OPENTIMEOUT '.$openTimeoutOld.' to ' . $mstConfig->getMailboxOpenTimeout());
		}else{
			$log->warning('Could NOT change default IMAP_OPENTIMEOUT '.$openTimeoutOld.' to ' . $mstConfig->getMailboxOpenTimeout());	
		}	
				
		$this->mBox = @imap_open ($host, $this->mList->mail_in_user, $this->mList->mail_in_pw);
		
		$chgTimeoutSuccess = imap_timeout(IMAP_OPENTIMEOUT, $openTimeoutOld);
		if($chgTimeoutSuccess){
			$log->debug('Changed IMAP_OPENTIMEOUT back to default '.$openTimeoutOld);
		}else{
			$log->warning('Could NOT change IMAP_OPENTIMEOUT back to default '.$openTimeoutOld);	
		}	
				
		if($this->mBox){
			return true;
		}else{
			return false;
		}
	}
	
	function close(){			
		$log = MstFactory::getLogger();
		$log->debug('Deleting mails marked for deletion...', MstConsts::LOGENTRY_MAIL_RETRIEVE);
		imap_expunge($this->mBox); // delete mails marked for deletion		
		$msgs = $this->getErrors(); // clear (useless) notices/warnings		
		$log->debug('Cleared errors before closing: ' . $msgs, MstConsts::LOGENTRY_MAIL_RETRIEVE);
		$res = imap_close($this->mBox); // close mailbox	
		$log->debug('Mailbox closed: ' . ($res ? 'Yes' : 'No'), MstConsts::LOGENTRY_MAIL_RETRIEVE);
		$this->mBox = null;
	}
	
	function getErrors(){
		$msg = '';
		$imapErrors = imap_errors(); 
		if($imapErrors){
			if(count($imapErrors)>1){
				foreach($imapErrors as $error){
					$msg = $msg."\r\n".$error;
				}
			}else{
				$msg = $imapErrors[0];
			}
		}else{
			$msg = __( 'No error messages available', 'wp-mailster' );
		}
		return $msg;
	}
	
	function removeFirstMailFromMailbox(){		
		$log = MstFactory::getLogger();
		$log->debug('Remove first mail from mailbox ' . $this->mList->name . ' (id: ' . $this->mList->id . ')', MstConsts::LOGENTRY_MAIL_RETRIEVE);
		$imapcheck = imap_check($this->mBox);
		$nMsgs = $imapcheck->Nmsgs; // number of messages in mailbox
		if($nMsgs > 0) {
			$log->debug('Mailbox OK (id ' . $this->mList->id . '), #New mails: ' . $nMsgs, MstConsts::LOGENTRY_MAIL_RETRIEVE);
		}else{
			$log->debug('Mailbox OK (id ' . $this->mList->id . '), No new mails', MstConsts::LOGENTRY_MAIL_RETRIEVE);
		}
		$nr = 1;
		if($nMsgs > 0){
			$log->debug('Deleting mail ' . $nr . ' from mailbox', MstConsts::LOGENTRY_MAIL_RETRIEVE);
			$res = $this->markOrDeleteOrMoveMail($nr, false, '', true, false, '');
		}else{
			$log->debug('No mail to remove from mailbox', MstConsts::LOGENTRY_MAIL_RETRIEVE);
		}
	}
	
	function removeAllMailsFromMailbox(){		
		$log = MstFactory::getLogger();
		$log->debug('Remove all mail from mailbox ' . $this->mList->name . ' (id: ' . $this->mList->id . ')', MstConsts::LOGENTRY_MAIL_RETRIEVE);
		$imapcheck = imap_check($this->mBox);
		$nMsgs = $imapcheck->Nmsgs; // number of messages in mailbox
		if($nMsgs > 0) {
			$log->debug('Mailbox OK (id ' . $this->mList->id . '), #New mails: ' . $nMsgs, MstConsts::LOGENTRY_MAIL_RETRIEVE);
		}else{
			$log->debug('Mailbox OK (id ' . $this->mList->id . '), No new mails', MstConsts::LOGENTRY_MAIL_RETRIEVE);
		}
		$nr = 1; // mail nr 		
		while ($nr <= $nMsgs) {
			$log->debug('Deleting mail ' . $nr . ' from mailbox', MstConsts::LOGENTRY_MAIL_RETRIEVE);
			$res = $this->markOrDeleteOrMoveMail($nr, false, '', true, false, '');
			$nr++;
		}
	}
	
	function getInboxStatus(){		
		$log = MstFactory::getLogger();
		$log->debug('getInboxStatus() Get Inbox Status for ' . $this->mList->name . ' (id: ' . $this->mList->id . ')');
		$status = new stdClass();
		$mailboxStatus = imap_mailboxmsginfo($this->mBox);
		$nMsgs = $mailboxStatus->Nmsgs; // number of messages in mailbox
		$log->debug('Mailbox OK (id ' . $this->mList->id . '), Status: ' .print_r($mailboxStatus, true));
		$mailboxStatus->messages = array();
		for($i=1; $i<=$nMsgs; $i++){
			$message = new stdClass();
			$message->no = $i;
			$message->UID = imap_uid($this->mBox, $i); // get UID of current message no
			$log->debug('UID of message no '.$i.': '.$message->UID);
			$message->headerInfo = imap_headerinfo($this->mBox, $i);
			$mailboxStatus->messages[] = $message;
		}
		$log->debug('getInboxStatus() Result: '.print_r($mailboxStatus, true));
		return $mailboxStatus;
	}
		
	function retrieveAllMessages($minDuration, $execEnd){			
		$log = MstFactory::getLogger();
		$mailingListUtils = MstFactory::getMailingListUtils();
		$timeout = false;
		$imapcheck = imap_check($this->mBox);
		$nMsgs = $imapcheck->Nmsgs; // number of messages in mailbox
		if($nMsgs > 0){
			$log->info('Mailbox OK (id ' . $this->mList->id . '), #New Mails: ' . $nMsgs, MstConsts::LOGENTRY_MAIL_RETRIEVE);
		}else{
			$log->debug('Mailbox OK (id ' . $this->mList->id . '), No new mails', MstConsts::LOGENTRY_MAIL_RETRIEVE);
		}
		$nr = 1; // mail nr (index within IMAP/POP inbox, starts with 1)
        $nrRetrievedMessages = 0; // counter of actual retrieved messages (not including emails being dropped before retrieved fully)
		while ($nr <= $nMsgs && $timeout == false) {
			$timeLeft = $execEnd - time();
			$log->debug('Time left: ' .  $timeLeft
						. ' for retrieving mails (execEnd: ' . $execEnd 
						. ', minDuration: ' . $minDuration . ')', MstConsts::LOGENTRY_MAIL_RETRIEVE);				
			if(($execEnd - time()) > $minDuration){	
				$dropMail = $this->isMailToBeDroppedUnretrieved($nr);
				if($dropMail){
					$log->debug('Drop mail, deleting mail from mailbox', MstConsts::LOGENTRY_MAIL_RETRIEVE);
					$res = $this->markOrDeleteOrMoveMail($nr, false, '', true, false, '');
					$log->info('Mail to drop was removed from mailbox: ' . ($res ? 'Yes' : 'No'), MstConsts::LOGENTRY_MAIL_RETRIEVE);
					$nr++;
					continue;
				}
				$mail = $this->getMessage($nr);
				$mail = $this->preprocessMessage($mail);
				if($this->storeMessageAndAttachments($mail)){	
					$log->debug('Deleting mail from mailbox', MstConsts::LOGENTRY_MAIL_RETRIEVE);
					$res = $this->markOrDeleteOrMoveMail($nr, false, '', true, false, '');
					$log->info('Mail removed from mailbox: ' . ($res ? 'Yes' : 'No'), MstConsts::LOGENTRY_MAIL_RETRIEVE);
					$mailingListUtils->setLastMailRetrieved($this->mList->id);
				}else{
					$log->error('Could not store mail: '.print_r($mail, true), MstConsts::LOGENTRY_MAIL_RETRIEVE);
				}
                $mail = null;
                unset($mail); // object not needed anymore
                $nrRetrievedMessages++;
			}else{
				$log->info('Timeout while retrieving mails...', MstConsts::LOGENTRY_MAIL_RETRIEVE);	
				$timeout = true;
			}
            $nr++;
        }
        return $nrRetrievedMessages; // return number of retrieved emails
	}
	
	function isMailToBeDroppedUnretrieved($nr){
		$log = MstFactory::getLogger();
		$log->debug('isMailToBeDroppedUnretrieved() for mail no '.$nr);
		$convUtils 	= MstFactory::getConverterUtils();
		$mailUtils = MstFactory::getMailUtils();
		$dropMail = false;
		$rawHeader = imap_fetchheader($this->mBox, $nr);

        if(!$rawHeader || strlen(trim($rawHeader)) <= 10){
            // Header was blank or close to blank, we will try this once again
            $log->error('isMailToBeDroppedUnretrieved Failed to receive raw header, result returned was: '.$rawHeader);
            $rawHeader = imap_fetchheader($this->mBox, $nr);
            $log->error('isMailToBeDroppedUnretrieved 2nd try for raw header yielded: '.$rawHeader);
        }

		$headers = $mailUtils->getHeaderFieldsAndContents($rawHeader);
		$log->debug('Converted Raw Header: '.print_r($headers, true));
				
		$header = $convUtils->object2Array(imap_headerinfo($this->mBox,$nr,255,255)); 
		$subject = $convUtils->getStringAsNativeUtf8($header['subject']);

		$msgIdValue = $mailUtils->getHeaderValue($rawHeader, MstConsts::MAIL_HEADER_MSG_ID);
		if($mailUtils->arraySearchWithVariations($headers->headerFields, MstConsts::MAIL_HEADER_MSG_ID) !== false){
			$dropMail = true;		
			$log->debug('Found ' . MstConsts::MAIL_HEADER_MSG_ID . ' (value: '.$msgIdValue.') in header of mail: ' . $subject, MstConsts::LOGENTRY_MAIL_RETRIEVE);
		}
		
		$senderValue = $mailUtils->getHeaderValue($rawHeader, MstConsts::MAIL_HEADER_SENDER);
		if($senderValue && !is_null($senderValue)){
			if(strtolower(trim($senderValue)) === strtolower(trim($this->mList->list_mail))){
				$dropMail = true;
				$log->debug('Found ' . MstConsts::MAIL_HEADER_SENDER . ' (value: '.$senderValue.') in header of mail: ' . $subject, MstConsts::LOGENTRY_MAIL_RETRIEVE);
			}
		}
		
		$returnPathValue = $mailUtils->getHeaderValue($rawHeader, MstConsts::MAIL_HEADER_RETURN_PATH);
		if($returnPathValue && !is_null($returnPathValue)){
			if(strtolower(trim($returnPathValue)) === strtolower(trim($this->mList->list_mail))){
				$dropMail = true;
				$log->debug('Found ' . MstConsts::MAIL_HEADER_RETURN_PATH . ' (value: '.$returnPathValue.') in header of mail: ' . $subject, MstConsts::LOGENTRY_MAIL_RETRIEVE);
			}
		}
				
		if(!$dropMail){
			$mailSizeLimit = $this->mList->mail_size_limit;
			if(trim($mailSizeLimit) === ''){
				$mailSizeLimit = 0;
			}
			if($mailSizeLimit > 0){
				$log->debug('Mail size limit defined for this list: ' . $mailSizeLimit . 'kByte');
				// we have defined an email size limit
				$structure 	= $convUtils->object2Array(imap_fetchstructure($this->mBox, $nr)); // get complete mail structure				
				$mailSize = $mailUtils->getMailSize($structure);
				if($mailSize > 0){
					// we know the mail size
					$mailSize = floor($mailSize/1024); // bytes to kBytes
					$log->debug('We have an email size available for size check: ' . $mailSize . 'kByte');
					if($mailSize > $mailSizeLimit){
						$log->info('Email has ' . $mailSize . 'kByte -> larger than allowed maximum of ' . $mailSizeLimit . 'kByte, will be dropped');
						$dropMail = true; // mail too large, will be dropped	
						
						$senderName	= $convUtils->imapUtf8(array_key_exists('personal', $header['from'][0]) ? $header['from'][0]['personal'] : '');
						$senderEmail = $convUtils->imapUtf8($header['from'][0]['mailbox'] . '@' . $header['from'][0]['host']);	
						$emailTooLarge = true;

                        $allowPrecedenceBulkMessages = ($this->mList->allow_bulk_precedence > 0 ? true : false);
						$isBouncedMail 	= $mailUtils->isBouncedMail($rawHeader, $allowPrecedenceBulkMessages);
						if(!$isBouncedMail){ // only fire event if it is not a bounced email	
							// ####### TRIGGER NEW EVENT #######
							$mstEvents = MstFactory::getEvents();
							$mstEvents->mailIsNotForwarded($this->mList->id, $subject, $senderName, $senderEmail, false, false, $emailTooLarge);
							// #################################
						}					
					}else{
						$log->debug('Email has ' . $mailSize . 'kByte -> smaller than allowed maximum of ' . $mailSizeLimit . 'kByte');
					}
				}
			}
		}
		return $dropMail;
	}
	
	/*
	 * Example for header given to getOriginalRecipientsOfMail
	 * [to] => Array
        (
            [0] => Array
                (
                    [personal] => John Doe
                    [mailbox] => john.doe
                    [host] => example.com
                )

        )
	 */
	function getOriginalRecipientsOfMail($header, $toOrCC){
		$log = MstFactory::getLogger();
		$convUtils 	= MstFactory::getConverterUtils();
		$recips = array();
		if(array_key_exists($toOrCC, $header)){
			$headerRecipArr = $header[$toOrCC];
			foreach ($headerRecipArr AS $headerRecip){
				$recipEmail = $convUtils->imapUtf8($headerRecip['mailbox'] . '@' . $headerRecip['host']);
				$recipName = '';
				if(array_key_exists('personal', $headerRecip)){
					$recipName = $convUtils->imapUtf8($headerRecip['personal']);
				}
				$recipObj = new stdClass();
				$recipObj->email = $recipEmail;
				$recipObj->name = $recipName;
				$recips[] = $recipObj;
			}
		}
		$log->debug('getOriginalRecipientsOfMail for "'.$toOrCC.'": '.print_r($recips, true));
		return $recips;
	}
	
	function getMessage($nr){					
		$log = MstFactory::getLogger();
		$dbUtils	= MstFactory::getDBUtils();
		$mstUtils 	= MstFactory::getUtils();
		$mailUtils 	= MstFactory::getMailUtils();
		$hashUtils 	= MstFactory::getHashUtils();
		$convUtils 	= MstFactory::getConverterUtils();

		$mail = new stdClass;
		$mail->isBouncedMail 	= false;
		$mail->isFilteredMail 	= false;
		$mail->hasUnauthSender 	= false;
		
		$header 	= $convUtils->object2Array(imap_headerinfo($this->mBox,$nr,255,255)); // get most important mail header fields
		$rawHeader	= imap_fetchheader($this->mBox, $nr); // get raw header
		$structure 	= $convUtils->object2Array(imap_fetchstructure($this->mBox, $nr)); // get complete mail structure		
		
		$log->debug(' ################## NEW_MAIL_START ################## ', MstConsts::LOGENTRY_MAIL_RETRIEVE);
		$log->debug('Working on mail #No ' . $nr, MstConsts::LOGENTRY_MAIL_RETRIEVE);
		$log->debug('Mail Header:', MstConsts::LOGENTRY_MAIL_RETRIEVE);
		$log->debug(print_r($header, true), MstConsts::LOGENTRY_MAIL_RETRIEVE);
		$log->debug('Raw Mail Header:', MstConsts::LOGENTRY_MAIL_RETRIEVE);
		$log->debug($rawHeader, MstConsts::LOGENTRY_MAIL_RETRIEVE);
		$log->debug('Mail Structure:', MstConsts::LOGENTRY_MAIL_RETRIEVE);
		$log->debug(print_r($structure, true), MstConsts::LOGENTRY_MAIL_RETRIEVE);

        $log->debug('Now '.time());
		$mail = new stdClass;			
		$mail->udate_timestamp		= $header['udate'];
        $dateTimeObj = DateTime::createFromFormat('U', $header['udate']); // convert to object (knowing we keep this in UTC TZ)
        $mail->receive_timestamp	= $dateTimeObj->format('Y-m-d H:i:s');
        $log->debug('From udate '.$header['udate'].' to UTC date '.$mail->receive_timestamp); // prior v1.8.7, we had localized dates in the DB :-/
		$mail->subject				= $convUtils->getStringAsNativeUtf8($header['subject']);

        if(array_key_exists('to', $header)){
            $mail->to_name			= array_key_exists('personal', $header['to'][0]) ? $convUtils->imapUtf8($header['to'][0]['personal']) : '';
            $mail->to_email			= (array_key_exists('mailbox', $header['to'][0]) && array_key_exists('host', $header['to'][0])) ? $convUtils->imapUtf8($header['to'][0]['mailbox'] . '@' . $header['to'][0]['host']) : '';
        }else{
            $mail->to_name          = '';
            $mail->to_email         = '';
        }

		$mail->from_name			= $convUtils->imapUtf8(array_key_exists('personal', $header['from'][0]) ? $header['from'][0]['personal'] : '');
		$mail->from_email			= $convUtils->imapUtf8($header['from'][0]['mailbox'] . '@' . $header['from'][0]['host']);
		$mail->orig_to_recips		= $mstUtils->jsonEncode($this->getOriginalRecipientsOfMail($header, 'to'));
		$mail->orig_cc_recips		= $mstUtils->jsonEncode($this->getOriginalRecipientsOfMail($header, 'cc'));
		$mail->rawHeader			= $rawHeader;	
		$mail->structure			= $structure;	
		$mail->message_id			= array_key_exists('message_id', $header) ? $header['message_id'] : null;
		$mail->in_reply_to 			= array_key_exists('in_reply_to', $header) ? $header['in_reply_to'] : null;
		$mail->references_to		= array_key_exists('references', $header) ? $header['references'] : null;
		$mail->size_in_bytes		= $mailUtils->getMailSize($structure);
		$mail->hashkey 				= $hashUtils->getMailHashkey();
		$mail->listId				= $this->mList->id;
		
		$mail->type 		= $structure['type'];
		$mail->encoding 	= $structure['encoding'];
		$mail->parameters 	= $structure['parameters'];
		$mail->charset	 	= $mailUtils->extractCharset($mail->parameters);
		$mail->hasSubtype 	= $structure['ifsubtype'] == 1 ? true : false;
		$mail->subtype 		= $mail->hasSubtype ? $structure['subtype'] : null;

        $mail->content_type = $mailUtils->extractContentType($mailUtils->getHeaderValue($rawHeader, MstConsts::MAIL_HEADER_CONTENT_TYPE));
		
		if($mail->size_in_bytes > 0){ // Make notes about the size before actually retrieving the body part(s)
			$mailSizeInKBytes = floor($mail->size_in_bytes/1024); // bytes to kBytes
			if($mailSizeInKBytes >= 1024){ // larger than 1 MB
				$mailSizeInMegaBytes = floor($mailSizeInKBytes/1024);
                $logMsg = 'Mail (list ID: ' . $this->mList->id . ') "'.$mail->subject.'" has a size of '.$mailSizeInMegaBytes.' MB!';
				$log->debug($logMsg);
				if($mailSizeInMegaBytes >= 3){ // larger than 3 MB
					$log->info($logMsg);
				}
				if($mailSizeInMegaBytes >= 10){ // larger than 10 MB
					$log->warning($logMsg);
				}
                if($mailSizeInMegaBytes >= 20){ // larger than 20 MB
                    $log->error($logMsg);
                }
			}else{
				$log->debug('Mail (list ID: ' . $this->mList->id . ') "'.$mail->subject.'" is small with a size of '.$mailSizeInKBytes.' kBytes');
			}
		}else{
			$log->debug('No size info for mail "'.$mail->subject.'" (list ID: ' . $this->mList->id . ')');
		}
		
		$content = $this->getBodyAndAttachmentsOfMail($nr, $mail);
        $mail->body = array_key_exists('body', $content) ? $content['body'] : null;
        $mail->html = array_key_exists('html', $content) ? $content['html'] : null;
        $mail->attachments = array_key_exists('attachments', $content) ? $content['attachments'] : array();
		$mail->has_attachments = ( (is_array($mail->attachments) && count($mail->attachments) > 0) ? '1' : '0');
		
		return $mail;
	}
	
	function preprocessMessage($mail){			
		$log = MstFactory::getLogger();
		$mailUtils 	= MstFactory::getMailUtils();
        $subscrUtils = MstFactory::getSubscribeUtils();
		$threadUtils = MstFactory::getThreadUtils();
		
		$log->debug('preprocessMessage()');
        $allowPrecedenceBulkMessages = ($this->mList->allow_bulk_precedence > 0 ? true : false);
        $mail->sendUnauthSenderNotification = true; // default
		$mail->isBouncedMail 	= $mailUtils->isBouncedMail($mail->rawHeader, $allowPrecedenceBulkMessages);
		$mail->hasUnauthSender 	= !($this->isAllowed2Send($mail->from_email));
		
		$log->debug('Unauth. Sender: ' . ($mail->hasUnauthSender ? 'Yes' : 'No') 
					. ', is bounced mail: ' . ($mail->isBouncedMail ? 'Yes' : 'No'), MstConsts::LOGENTRY_MAIL_RETRIEVE);

        // The from address might be among the blocked email address in the configuration (no matter whether from address might be blocked already or not)
        // We check this here and not earlier as we do not want to trigger an event should it be an unauthorized/blocked email address.
        // The reason is that this functionality is primarily used to weed out bounce emails.
        $globalBlockedEmailAddress = $mailUtils->isBlockedEmailAddress($mail->from_email);
        if($globalBlockedEmailAddress){
            $mail->hasUnauthSender = true; // is unauthorized sender
            $mail->sendUnauthSenderNotification = false; // we do not want to send events / notifications to sender
            $mstConf = MstFactory::getConfig();
            $log->info('Unauth. Sender through the fact that FROM address '.$mail->from_email.' is among blocked email addresses: '.print_r($mstConf->getBlockedEmailAddresses(), true), MstConsts::LOGENTRY_MAIL_RETRIEVE);
        }

        if($mail->hasUnauthSender && !$mail->isBouncedMail && !$globalBlockedEmailAddress){
            $senderBlocked = true;
            // ####### TRIGGER NEW EVENT #######
            $mstEvents = MstFactory::getEvents();
            $mstEvents->mailIsNotForwarded($this->mList->id, $mail->subject, $mail->from_name, $mail->from_email, $senderBlocked, false, false);
            // #################################
        }
		
		$mail->isFilteredMail = false;
		
		$filterMails = ($this->mList->filter_mails == 1 ? true : false);
		if($filterMails){
			$mail->isFilteredMail = $mailUtils->checkMailWithWordsToFilter($mail);
			$log->info('Mail filtering active for this list, mail blocked because of word filter: '
				. ($mail->isFilteredMail ? 'yes' : 'no'), MstConsts::LOGENTRY_MAIL_RETRIEVE);	
				
			if($mail->isFilteredMail && !$mail->isBouncedMail){
				$emailFilteredByWords = true;	
				// ####### TRIGGER NEW EVENT #######
				$mstEvents = MstFactory::getEvents();
				$mstEvents->mailIsNotForwarded($this->mList->id, $mail->subject, $mail->from_name, $mail->from_email, false, $emailFilteredByWords, false);
				// #################################						
			}	
		}else{
			$log->debug('Mail filtering not active');
		}

        $mail->sender_user_id = 0;
        $mail->sender_is_core_user = 0;
        $senderInfo = $subscrUtils->getUserByEmail($mail->from_email, true);
        if($senderInfo && $senderInfo->user_found){
            $mail->sender_user_id = $senderInfo->user_id;
            $mail->sender_is_core_user = $senderInfo->is_core_user;
            $log->debug('Was able to identify sender in DB, user_id: '.$mail->sender_user_id. ', is_core_user: '.$mail->sender_is_core_user);
        }else{
            $log->debug('Was NOT able to identify sender in DB, getUserByEmail returned: '.print_r($senderInfo, true));
        }

        $mail->needsModeration = $this->isMailToBeModerated($senderInfo);

        if($mail->needsModeration){
            $log->info('Mail "'.$mail->subject.'" of list '.$this->mList->name . ' (id: ' . $this->mList->id . ') needs to be put into moderation');
        }

		$mail->sender_dmarc_relevant = 0;
        if($mailUtils->isSentFromDMARC_PolicyRejectProvider($mail->from_email)){
            $mail->sender_dmarc_relevant = 1;
        }
		
		// try to remove all mail modifications that were applied previously
		// -> only when this is an answer to a mail that was processed with Mailster 			
		$mail 	= $mailUtils->undoSubjectModifications($mail, $this->mList);			  
		$mail 	= $mailUtils->undoMailBodyModifications($mail, $this->mList);

        $mail->subject = $mailUtils->shortenSubjectIfNeeded($mail->subject);

		$mail->references_to_orig = $mail->references_to; // backup references
		// Clean up empty or incomplete references
		$mail->references_to = $threadUtils->cleanUpReferencesString($mail->references_to);
		// Remove Mailster Message Reference
		$mail->references_to = $threadUtils->removeMailsterThreadReference($mail->references_to);
		// Shorten references if too long
		$mail->references_to = $threadUtils->shortenReferencesIfNeeded($mail->references_to);

        $log->debug('preprocessMessage() Mail after all modifications: '.print_r($mail, true));

		return $mail;
	}
	
	function storeMessageAndAttachments($mail){		
		$log = MstFactory::getLogger();
		$mstUtils 		= MstFactory::getUtils();
		$mstQueue 		= MstFactory::getMailQueue();
		$threadUtils 	= MstFactory::getThreadUtils();
		$insertId = 0;
		$log->debug('storeMessageAndAttachments()');
				
		if( ($mail->isBouncedMail == false) && ($mail->hasUnauthSender == false) && ($mail->isFilteredMail == false) && ($mail->needsModeration == false) ){
			$log->debug('Insert mail data into DB', MstConsts::LOGENTRY_MAIL_RETRIEVE);	
			$insertId = $mstQueue->saveAndEnqueueMail($mail, $this->mList);						
		}else{
            $log->debug('Not enqueuing mail, handling/saving as bounced or blocked or moderated', MstConsts::LOGENTRY_MAIL_RETRIEVE);
			$insertId = $mstQueue->saveNonQueueMail($mail, $this->mList, $mail->hasUnauthSender, $mail->isBouncedMail, $mail->isFilteredMail, $mail->needsModeration);
		}
		
		$log->debug('Insert ID: ' . $insertId, MstConsts::LOGENTRY_MAIL_RETRIEVE);
		$mail->id = $insertId;
		
		$mail = $this->storeAttachments($mail);
				
		$log->debug('Need to assign a thread to the mail...', MstConsts::LOGENTRY_MAIL_RETRIEVE);
		$threadId = $threadUtils->getThreadIdOfMail($mail);
		if(!$threadId){
			if($mail->isBouncedMail == false){
				$threadId = $threadUtils->createNewThread($mail); // we have to create a new thread
			}else{
				$threadId = 0;
				$log->debug('No thread ID found for bounced mail, no new thread will be assigned to the email', MstConsts::LOGENTRY_MAIL_RETRIEVE);
			}
		}
		
		$mail->thread_id = $threadId;
		
		if($threadId){
			$threadUtils->updateMailWithThreadId($mail->id, $threadId);
		}

		if($mail->needsModeration) {
			if (($mail->isBouncedMail == false) && ($mail->hasUnauthSender == false) && ($mail->isFilteredMail == false)) {
				$log->debug('Email ID '.$mail->id.' requires moderation, send request for moderation');
				$moderationUtils = MstFactory::getModerationUtils();
				$moderationUtils->sendRequestForModeration($mail->id);
			}else{
				$log->debug('Email ID '.$mail->id.': skip moderation, because is a bounced/blocked/filtered email');
			}
		}

		if ($insertId > 0) {
            $mailUtils = MstFactory::getMailUtils();
            $mailObj2Check = $mailUtils->getMail($insertId);
            $log->debug('Email ID '.$insertId.' check-loaded from DB: '.print_r($mailObj2Check, true));
			return true;
		}

		return false;
	}
	
	function storeAttachments($mail){
		$log = MstFactory::getLogger();
		$mstConfig 		= MstFactory::getConfig();
		$attachUtils 	= MstFactory::getAttachmentsUtils();
		$log->debug('storeAttachments()');
		
		$log->debug('Has attachments to save: ' . ((count($mail->attachments) > 0) ? 'Yes' : 'No'), MstConsts::LOGENTRY_MAIL_RETRIEVE);
		$baseDir = "";
		$savedAttachs = $attachUtils->storeAttachments($baseDir, $mail->attachments); // Save attachments to files
		$attachUtils->saveAttachmentsInDB($mail->id, $savedAttachs); // Save attachments in DB	
		$mail->has_attachments = ( count($mail->attachments) > 0 ? '1' : '0');
		
		return $mail;
	}
	
	
	function markOrDeleteOrMoveMail($nr, $markIt, $markType, $deleteIt, $moveIt, $moveTarget){
		$log = MstFactory::getLogger();
		$res = false;

		if($markIt){				
		    switch($markType) {
		      case 'unread':
		        $res = imap_clearflag_full($this->mBox, $nr.':'.$nr, '\\SEEN');
		      break;
		      case 'read':
		        $res = imap_setflag_full($this->mBox, $nr.':'.$nr, '\\SEEN');
		      break;
		      case 'flagged':
		        $res = imap_setflag_full($this->mBox, $nr.':'.$nr, '\\FLAGGED');
		      break;
		      case 'unflagged':
		        $res = imap_clearflag_full($this->mBox, $nr.':'.$nr, '\\FLAGGED');
		      break;
		      case 'answered':
		        $res = imap_setflag_full($this->mBox, $nr.':'.$nr, '\\Answered');
		      break;
		    }										
		}
		if($deleteIt){
			$res = imap_delete($this->mBox, $nr);
			$log->debug('markOrDeleteOrMoveMail -> delete with imap_delete of nr '.$nr.': '.($res ? 'okay':'NOT OKAY'));
			$setFlagRes = imap_setflag_full($this->mBox, $nr.':'.$nr, '\\Deleted');
			$log->debug('markOrDeleteOrMoveMail -> delete with imap_setflag_full of nr '.$nr.': '.($setFlagRes ? 'okay':'NOT OKAY'));
		}
		if($moveIt){
			$res = imap_mail_move($this->mBox, $nr, $moveTarget);	
		}
		
		return $res;
	}
	
	
	function getBodyAndAttachmentsOfMail($nr, $mail){
		$log = MstFactory::getLogger();
		$mstConfig = MstFactory::getConfig();
		
		$res = array();
		$log->debug('getBodyAndAttachmentsOfMail()');
		if($mail->type == MstConsts::MAIL_TYPE_PLAIN) { 
			$log->debug('Is not a multipart mail', MstConsts::LOGENTRY_MAIL_RETRIEVE);
			$res = $this->getMessageContentOfMail($nr, $mail);
		}elseif($mail->type == MstConsts::MAIL_TYPE_MULTIPART) {  
			$log->debug('Is multipart mail', MstConsts::LOGENTRY_MAIL_RETRIEVE);
			$res = $this->getMultipartMessageContentOfMail($nr, $mail);
		}
		if($mstConfig->isUndoLineWrapping()){
            $log->debug('getBodyAndAttachmentsOfMail --> undo line wrapping!');
			$res['html'] = (!is_null($res['html'])) ? str_replace(' '.CHR(13).CHR(10),' ',$res['html']) : $res['html'];
			$res['body'] = (!is_null($res['body'])) ? str_replace(' '.CHR(13).CHR(10),' ',$res['body']) : $res['body'];
		}
		$log->debug('Plain text (if available) - remove html entities...', MstConsts::LOGENTRY_MAIL_RETRIEVE);
		$res['body'] = array_key_exists('body', $res) ? html_entity_decode($res['body']) : null;
		
		return $res;
	}
	
	function getMessageContentOfMail($nr, $mail){
		$log		= MstFactory::getLogger();
		$mstUtils 	= MstFactory::getUtils();
		$mailUtils 	= MstFactory::getMailUtils();
		$convUtils 	= MstFactory::getConverterUtils();

        $attachs = array();
		$singleContent = array();
		$singleContent['body'] = null;
		$singleContent['html'] = null;
		$singleContent['attachments'] = array();
		
		$log->debug('getMessageContentOfMail()');
		
		$body = imap_body($this->mBox, $nr, FT_INTERNAL);
		$log->debug('Single Part Mail Raw Body (Encoding '.$mail->encoding.', Charset '.$mail->charset.'): ' . $body, MstConsts::LOGENTRY_MAIL_RETRIEVE);
		$encBody = $convUtils->encodeText($body, $mail->encoding, $mail->charset);
		$log->debug('Single Part Mail Encoded Body: ' . $encBody, MstConsts::LOGENTRY_MAIL_RETRIEVE);
		
		if($mail->hasSubtype && (strtoupper($mail->subtype) === 'HTML')){
			$log->debug('Is HTML single part mail', MstConsts::LOGENTRY_MAIL_RETRIEVE);
			$singleContent['html'] = $encBody; // html text
		}else{
			if(strtoupper($mail->subtype) !== 'CALENDAR'){
				$log->debug('Is plain text single part mail', MstConsts::LOGENTRY_MAIL_RETRIEVE);
				$singleContent['body'] = $encBody; // plain text
			}else{
				$log->debug('This mail has subtype CALENDAR, is a an meeting invitation, consider message as attachment...', MstConsts::LOGENTRY_MAIL_RETRIEVE);
				$structure = $mail->structure;
				$fileName = $mailUtils->getAttachmentFilename($structure); // extract attachment filename
				$fileName = $convUtils->imapUtf8($fileName);
				$fileName = rawurlencode($fileName);
				$contentId = $mailUtils->getContentId($structure);
				$type = $structure['type'];
				$disposition = 'ATTACHMENT';
	
				$params = '';						
				if(array_key_exists('parameters', $structure)){
					$parameters = $structure['parameters'];
					$log->debug('Found ' . count($parameters) . ' parameters for attachment', MstConsts::LOGENTRY_MAIL_RETRIEVE);
					$params = $mailUtils->getAttachmentParameters($parameters, 255);
				}
				$attachs[] = array("filename" => $fileName, "filedata" => $encBody,
									"disposition" => $disposition, "content_id" => $contentId,
									"type" => $type, "subtype" => strtoupper($mail->subtype), "params" => $params);
				$log->debug('Meeting as attachment: ' . print_r($attachs, true), MstConsts::LOGENTRY_MAIL_RETRIEVE);
			}
		}
		$singleContent['attachments'] = $attachs;
		return $singleContent;			
	}
	
	function getMultipartMessageContentOfMail($nr, $mail){
		$log = MstFactory::getLogger();
		$mstUtils 	= MstFactory::getUtils();
		$mailUtils 	= MstFactory::getMailUtils();
		$convUtils 	= MstFactory::getConverterUtils();
		
		$log->debug('getMultipartMessageContentOfMail()');
		
		$struct = $mail->structure;
		$parts = $struct['parts'];
		$i = 0;
		$endwhile = false;		
		$attachs = array();				
		$stack = array(); // Stack while parsing message
		$body = '';
		$html = '';
		
		$rawBody = imap_body($this->mBox, $nr, FT_INTERNAL);
		$log->debug('Raw Body: ' . $rawBody, MstConsts::LOGENTRY_MAIL_RETRIEVE);
								
		while (!$endwhile) {
			if (!array_key_exists($i, $parts)) {
                // no more entries in parts array, so we are finished (at least with this parts level)
				if (count($stack) > 0) {
					$log->debug('next in stack', MstConsts::LOGENTRY_MAIL_RETRIEVE);
					$parts = $stack[count($stack)-1]["p"];
					$i     = $stack[count($stack)-1]["i"] + 1;
					array_pop($stack);
				} else {
					$log->debug('no more stack content, finished', MstConsts::LOGENTRY_MAIL_RETRIEVE);
					$endwhile = true;
				} 
			}
			if (!$endwhile) {
				$partstring = "";							 
				foreach ($stack as $s) {
					$partstring .= ($s["i"]+1) . ".";
					$log->debug('new partstring: ' . $partstring, MstConsts::LOGENTRY_MAIL_RETRIEVE);
				}
				$partstring .= ($i+1);	
				
				if(array_key_exists($i, $parts) && !is_null($parts[$i])){
					$disposition 	= array_key_exists('disposition', 	$parts[$i]) ? trim(strtoupper($parts[$i]['disposition'])) 	: null;
					$subtype 		= array_key_exists('subtype', 		$parts[$i]) ? trim(strtoupper($parts[$i]['subtype'])) 		: null;
					$encoding 		= $parts[$i]['encoding'];
					$type	 		= $parts[$i]['type'];
					$typeStr = $mailUtils->getContentTypeString($type);
					$log->debug(print_r($parts[$i], true), MstConsts::LOGENTRY_MAIL_RETRIEVE);
					$log->debug('going for part no ' . $i. ' with disposition: ' . $disposition . ', type: ' . $typeStr . ', subtype: ' . $subtype . ', encoding: ' . $encoding, MstConsts::LOGENTRY_MAIL_RETRIEVE);
				}else{
                    $log->debug('Skip as there is no part '.$i);
					$disposition = null;
					$subtype = null;
					$encoding = null;
					$type = null;
					$typeStr = null;
				}

				if (($subtype == "PLAIN") && ($disposition != "ATTACHMENT")) { // Part is Message
					$charset = $mailUtils->extractCharset($parts[$i]['parameters']);
					$log->debug('Part no ' . $i . ' (partstr: ' . $partstring . ') is plain text with charset ' . $charset . ' and encoding ' . $encoding, MstConsts::LOGENTRY_MAIL_RETRIEVE);	
					$bodyPart = imap_fetchbody($this->mBox, $nr, $partstring, FT_INTERNAL);
					$bodyPart = $convUtils->encodeText($bodyPart, $encoding, $charset);
				//	$log->debug('Converted Plain Text:<br/>\n'.$bodyPart, MstConsts::LOGENTRY_MAIL_RETRIEVE);	
					$body .= $bodyPart;				
					
				} elseif (($subtype == "HTML") && ($disposition != "ATTACHMENT")) { // Part is HTML Message	
					$charset = $mailUtils->extractCharset($parts[$i]['parameters']);
					$log->debug('Part no ' . $i . ' (partstr: ' . $partstring . ') is HTML text with charset ' . $charset . ' and encoding ' . $encoding, MstConsts::LOGENTRY_MAIL_RETRIEVE);	
					$htmlPart = imap_fetchbody($this->mBox, $nr, $partstring, FT_INTERNAL);	
					$htmlPart = $convUtils->encodeText($htmlPart, $encoding, $charset);
				//	$log->debug('Converted HTML Text:<br/>\n'.$htmlPart, MstConsts::LOGENTRY_MAIL_RETRIEVE);	
				//	$htmlPart = htmlentities($htmlPart, ENT_NOQUOTES);
				//	$log->debug('After htmlentities:<br/>\n'.$htmlPart, MstConsts::LOGENTRY_MAIL_RETRIEVE);						
					$html .= $htmlPart;						
				} elseif ($type == MstConsts::MAIL_TYPE_MULTIPART && ($subtype == "MIXED")){
                    $log->debug('Part no ' . $i . ' (partstr: ' . $partstring . ') is multipart/mixed, thus there is nothing to do besides going to the sublevel', MstConsts::LOGENTRY_MAIL_RETRIEVE);
                }else{
					$contentId = array_key_exists($i, $parts) ? $mailUtils->getContentId($parts[$i]) : null;
					$log->debug('Part no ' . $i . ' is no message part, content id: ' . $contentId, MstConsts::LOGENTRY_MAIL_RETRIEVE);
					$hasDisposition = (!is_null($disposition)) 	&& (strlen($disposition) > 0);
					$hasSubtype 	= (!is_null($subtype)) 		&& (strlen($subtype) > 0);
					$isAlternative 	= ($subtype === 'ALTERNATIVE');
					$isRelated	 	= ($subtype === 'RELATED');
					$log->debug('PART INFO disposition: ' . $hasDisposition . ', subtype: ' . $hasSubtype . ', alternative: '. $isAlternative . ', related: '. $isRelated . '', MstConsts::LOGENTRY_MAIL_RETRIEVE);
					if(!$hasDisposition && $hasSubtype && !$isAlternative && !$isRelated){
						$log->debug('Part no ' . $i . ' with subtype ' . $subtype . ' has no disposition', MstConsts::LOGENTRY_MAIL_RETRIEVE);
						if( (!is_null($contentId)) && (strlen(trim($contentId))>0) ){
							$log->debug('Assuming part with subtype ' . $subtype . ' is INLINE attachment, has content id: ' . $contentId, MstConsts::LOGENTRY_MAIL_RETRIEVE);
							$disposition = "INLINE";
						}else{
							$log->debug('Assuming part with subtype ' . $subtype . ' is an ATTACHMENT, has NO content id', MstConsts::LOGENTRY_MAIL_RETRIEVE);
							$disposition = "ATTACHMENT";
						}
					}
					if($disposition === "ATTACHMENT" || $disposition === "INLINE") { // Part is attachment
						$log->debug('Part no ' . $i . ' is attachment', MstConsts::LOGENTRY_MAIL_RETRIEVE);
						$fileName = $mailUtils->getAttachmentFilename($parts[$i]); // extract attachment filename
						$fileName = $convUtils->imapUtf8($fileName);
						$fileName = rawurlencode($fileName);
						$log->debug('Attachment (part ' . $i . ') filename: ' . $fileName. ' has content id: ' . ($contentId?$contentId:'No'), MstConsts::LOGENTRY_MAIL_RETRIEVE);	
						$attachPart = imap_fetchbody($this->mBox, $nr, $partstring);
						if(($subtype != "PLAIN") && ($subtype != "CALENDAR") && ($encoding != 0) && ($encoding != 1)){	// encoding 0 und 1 hier? Oder lieber mit subtypes arbeiten?
							$log->debug('Binary attachment, encoding: ' . $encoding, MstConsts::LOGENTRY_MAIL_RETRIEVE);
							if($encoding == 3){ // BASE64 encoding
								$log->debug('Do base64 decoding', MstConsts::LOGENTRY_MAIL_RETRIEVE);
								$attachPart = base64_decode($attachPart);
							}elseif($encoding == 4){ // QUOTED-PRINTABLE encoding
								$log->debug('Do quoted-printable decoding', MstConsts::LOGENTRY_MAIL_RETRIEVE);
								$attachPart = quoted_printable_decode($attachPart);
							}else{
								$log->warning('Binary attachment with unknown encoding: ' . $encoding, MstConsts::LOGENTRY_MAIL_RETRIEVE);
							}
							$log->debug('Attachment after binary decoding: '.$attachPart);
							
							$doPdfWorkaround = true;
							if($doPdfWorkaround){
								$log->debug('Do PDF Workaround if applicable');
								if($disposition === "INLINE" && !$contentId){
									$log->debug('Inline attachment but no content ID...');
									if(trim(strtoupper($typeStr)) === 'APPLICATION' && $subtype === 'PDF'){
										$log->info('Do PDF Workaround: INLINE Attachment is PDF - convert disposition to ATTACHMENT...');
										$disposition = "ATTACHMENT";
									}
								}
							}
							
						}else{	
							$log->debug('Plain text attachment, encoding: ' . $encoding, MstConsts::LOGENTRY_MAIL_RETRIEVE);	
							$attachPart = $convUtils->encodeText($attachPart, $encoding, '');
						}
						
						$params = '';						
						if(array_key_exists('parameters', $parts[$i])){
							$parameters = $parts[$i]['parameters'];
							$log->debug('Found ' . count($parameters) . ' parameters for attachment', MstConsts::LOGENTRY_MAIL_RETRIEVE);
							$params = $mailUtils->getAttachmentParameters($parameters, 255);
						}
						
						$newAttach = array("filename" => $fileName, "filedata" => $attachPart,
											"disposition" => $disposition, "content_id" => $contentId,
											"type" => $type, "subtype" => $subtype, "params" => $params);
						$log->debug('NEW_ATTACH_READY: ' . print_r($newAttach, true));
						$attachs[] = $newAttach;
						
					}																			
				}			
			}
			if( is_array($parts) && array_key_exists($i, $parts) && is_array($parts[$i]) && array_key_exists('parts', $parts[$i]) ){
                $log->debug('Sub parts detected, next on same level', MstConsts::LOGENTRY_MAIL_RETRIEVE);
                $log->debug('Stack before: '.print_r($stack, true));
				$stack[] = array("p" => $parts, "i" => $i);
                $log->debug('Put this parts array on stack, stack after: '.print_r($stack, true));
				$parts = $parts[$i]['parts'];
                $log->debug('Now deal with sub parts array: '.print_r($parts, true));
				$i = 0;
			} else {
				$i++;
				$log->debug('no sub parts detected, next on same level', MstConsts::LOGENTRY_MAIL_RETRIEVE);
			}
		}
		$multiContent = array();
		$multiContent['body'] = $body;
		$multiContent['html'] = $this->buildHTML($html);
		$multiContent['attachments'] = $attachs;
		$log->debug('######################### Main_Body_Parts ############################ ' . print_r($multiContent, true), MstConsts::LOGENTRY_MAIL_RETRIEVE);
		return $multiContent;
	}
	
	
	function buildHTML($str) {	
		$log = MstFactory::getLogger();
		if(!is_null($str) && strlen($str)>0){ 	
		   	if (strpos(strtolower($str),"<html") === false){
		  		$header = "<html><head></head>\n";
			   	if (strpos(strtolower($str),"<body") === false){
			   		$body = "\n<body>\n";
			   		$str = $header . $body . $str ."\n</body></html>";
			   	} else {
			   		$str = $header . $str ."\n</html>";
			   	}
		   	}
			$log->debug('HTML after tag insertion: ' . $str, MstConsts::LOGENTRY_MAIL_RETRIEVE);
		}
	   	return $str;
	}
	
	function isAllowed2Send($sender, $gmailCompatible=true){
		$log = MstFactory::getLogger();
		$sender = strtolower(trim($sender));
		$log->debug('Checking whether ' . $sender . ' is allowed to send...', MstConsts::LOGENTRY_MAIL_RETRIEVE);
		$allowed = false;

		
		if($this->mList->sending_public == 1){ // everybody is allowed to send
			$log->debug('isAllowed2Send: everybody', MstConsts::LOGENTRY_MAIL_RETRIEVE);
			return true;
		}else{
			$log->debug('isAllowed2Send: restricted', MstConsts::LOGENTRY_MAIL_RETRIEVE);
		}
		
		if($this->mList->sending_admin == 1){ // only admins are allowed to send
			$adminMail = strtolower(trim($this->mList->admin_mail));
			$log->debug('isAllowed2Send: admin only (' . $adminMail . ')', MstConsts::LOGENTRY_MAIL_RETRIEVE);
			$allowed = (($sender === $adminMail) ? true : false);
			$allowed = $allowed || ($gmailCompatible && (str_replace('@gmail.com','@googlemail.com', $adminMail) === str_replace('@gmail.com','@googlemail.com', $sender)));
			if($allowed) return true;
		}
		if($this->mList->sending_recipients == 1){ // only recipients are allowed to send
			$log->debug('isAllowed2Send: recipients only', MstConsts::LOGENTRY_MAIL_RETRIEVE);
			$mstRecipients = MstFactory::getRecipients();
			$allowed = $mstRecipients->isRecipient($this->mList->id, $sender, $gmailCompatible);
			if($allowed) return true;
		}	
		if($this->mList->sending_group == 1){ // only members of certain group are allowed to send
			$log->debug('isAllowed2Send: group only', MstConsts::LOGENTRY_MAIL_RETRIEVE);
			$groupusersModel = MstFactory::getGroupUsersModel();
			$groupUsers = $groupusersModel->getData($this->mList->sending_group_id);
			$log->debug('isAllowed2Send: groupUsers: '.print_r($groupUsers, true));
			for($i=0; $i<count($groupUsers); $i++){
				$groupUser = &$groupUsers[$i];
				$guMail = strtolower(trim($groupUser->email));
				if(($guMail === $sender)
					|| ($gmailCompatible && (str_replace('@gmail.com','@googlemail.com', $guMail) === str_replace('@gmail.com','@googlemail.com', $sender)))){
					$log->debug('isAllowed2Send: '.$guMail.' is within sending group, is allowed');
					$allowed = true;
					break;
				}
			}
			if($allowed) return true;
		}
		return $allowed;
	}

    function isMailToBeModerated($senderInfo){
        $log = MstFactory::getLogger();
        /** @var MailsterModelUserGroups $userGroupsModel */
        $userGroupsModel = MstFactory::getModel('usergroups');
        /*
        The following properties are always present
        $senderInfo->user_found [TRUE / FALSE]
        $senderInfo->email
        $senderInfo->name
        $senderInfo->description
        if user_found is true, there are two more properties:
        $senderInfo->user_id
        $senderInfo->is_core_user
         */

        if((!MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_MODERATE)) || ($this->mList->mod_mode == MstConsts::MODERATION_MODE_NO_MODERATION)){
            $log->debug('Moderation mode NOT active');
            return false;
        }

        $moderationNeeded = false;
        if($this->mList->mod_mode == MstConsts::MODERATION_MODE_ALL_MESSAGES){
            $log->debug('isMailToBeModerated: All messages need to be moderated');
            $moderationNeeded = true;
        }elseif($this->mList->mod_mode == MstConsts::MODERATION_MODE_MEMBERS_OF_GROUP){
            $log->debug('isMailToBeModerated: Only messages of group '. $this->mList->mod_moderated_group .' need to be moderated');
            if($senderInfo->user_found){
                if($userGroupsModel->isUserInGroup($senderInfo->user_id, $senderInfo->is_core_user, $this->mList->mod_moderated_group)){
                    $log->debug('isMailToBeModerated: User (user_id: '.$senderInfo->user_id.', is_core_user: '.$senderInfo->is_core_user.') is member of group ID '.$this->mList->mod_moderated_group.' -> needs to be moderated');
                    $moderationNeeded = true;
                }else{
                    $log->debug('isMailToBeModerated: User (user_id: '.$senderInfo->user_id.', is_core_user: '.$senderInfo->is_core_user.') is NOT a member of group ID '.$this->mList->mod_moderated_group.', no moderation needed');
                }
            }else{ // $senderInfo->user_found == false
                $log->debug('No user found for email '.$senderInfo->email.', as such cannot be a member of the group...');
            }
        }else{
            $log->warning('isMailToBeModerated: Moderation mode is unknown: '.$this->mList->mod_mode);
        }

        if($moderationNeeded){
            $modUtils = MstFactory::getModerationUtils();
            $isEmailAddressFromModerator = $modUtils->isEmailAddressOneOfTheModerators($senderInfo->email, $this->mList);
            if($isEmailAddressFromModerator){
                $log->debug('isMailToBeModerated: Sender is (one of the) moderator(s), no moderation necessary');
                $moderationNeeded = false;
            }

            if($moderationNeeded && ($this->mList->mod_approve_recipients > 0)){
                $log->debug('isMailToBeModerated: Auto-approve recipients, check whether sender is a recipient...');
                $mstRecipients = MstFactory::getRecipients();
                $isRecipient = $mstRecipients->isRecipient($this->mList->id, $senderInfo->email);
                if($isRecipient){
                    $log->debug('isMailToBeModerated: Sender is a recipient, auto-approve this message, no moderation necessary');
                    $moderationNeeded = false;
                }else{
                    $log->debug('isMailToBeModerated: Sender is NOT a recipient, we cannot auto-approve this message');
                }
            }

            if($moderationNeeded && ($this->mList->mod_approve_group > 0)){
                $log->debug('isMailToBeModerated: Auto-approve members of group ID '.$this->mList->mod_approve_group_id.', check whether sender is a member...');
                if($senderInfo->user_found){
                    if($userGroupsModel->isUserInGroup($senderInfo->user_id, $senderInfo->is_core_user, $this->mList->mod_approve_group_id)){
                        $log->debug('isMailToBeModerated: User (user_id: '.$senderInfo->user_id.', is_core_user: '.$senderInfo->is_core_user.') is member of group ID '.$this->mList->mod_approve_group_id.' -> auto-approve this message, no moderation necessary');
                        $moderationNeeded = false;
                    }else{
                        $log->debug('isMailToBeModerated: User (user_id: '.$senderInfo->user_id.', is_core_user: '.$senderInfo->is_core_user.') is NOT a member of group ID '.$this->mList->mod_approve_group_id.', we cannot auto-approve this message');
                    }
                }else{ // $senderInfo->user_found == false
                    $log->debug('No user found for email '.$senderInfo->email.', as such cannot be a member of the group...');
                }
            }
        }

        return $moderationNeeded;
    }

}

