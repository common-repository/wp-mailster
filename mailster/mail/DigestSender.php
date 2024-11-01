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


class MstDigestSender
{		
	
	function sendPendingDigests($pendingDigests, $minDuration, $execEnd) {
		$log = MstFactory::getLogger();
        /** @var MailsterModelDigest $digestModel */
		$digestModel = MstFactory::getDigestModel();
		$digestCount = count($pendingDigests);
        $nrDigestsSent = 0;
		if($digestCount > 0){
			$log->debug('sendPendingDigests: Digest Count to send: ' . $digestCount, MstConsts::LOGENTRY_MAIL_SEND);
			for($i = 0; $i < $digestCount; $i++) {
				$log->debug('Time left: ' . ($execEnd - time()) . ' for sending digests (execEnd: ' . $execEnd . ', minDuration: ' . $minDuration . ')', MstConsts::LOGENTRY_MAIL_SEND);	
				if(($execEnd - time()) > $minDuration){		
					$digest = $pendingDigests[$i];	
					$digest = $digestModel->getDigest($digest->id); // overwrite with enhanced object
					if($digest->user_id == MstConsts::DIGEST_USER_ID_MEANING_DIGEST_TO_ARTICLE){
						$this->storePendingDigestArticle($digest, $minDuration, $execEnd);	
					}else{
                        $nrDigestsSent = $nrDigestsSent + $this->sendPendingDigest($digest, $minDuration, $execEnd);
					}					
				}else{
					$log->debug('Timeout, do not work on next pending digest', MstConsts::LOGENTRY_MAIL_SEND);
					break;
				}
			}
		}else{
			$log->debug('No digest to send', MstConsts::LOGENTRY_MAIL_SEND);
		}
        return $nrDigestsSent;
	}
	
	function storePendingDigestArticle($digest, $minDuration, $execEnd){
		$log = MstFactory::getLogger();
		$dateUtils = MstFactory::getDateUtils();
		$mstConfig = MstFactory::getConfig();
		$mstQueue = MstFactory::getMailQueue();
		$mailingListUtils = MstFactory::getMailingListUtils();
		$digestQueue = $mstQueue->getMailQueueForDigest($digest->id);
		$digestQueueSize = count($digestQueue);
		$log->debug('storePendingDigestArticle: Digest to store in article is for list ' . $digest->list_id. ', will be built from '.$digestQueueSize.' mails');

		$mList = $mailingListUtils->getMailingList($digest->list_id);
		$digestMail = $this->prepareDigestMail4Recipient($digest, $digestQueue, $mList, true);

		if(!$digestMail){
			$log->debug('No digest to store, update DB anyway (act as no send/store errors)...');
			$this->processSendOrStoreResults(false, $digest);
			$log->debug('No digest was stored, return...');
			return false;
		}
		$log->info('Storing digest ...', MstConsts::LOGENTRY_MAIL_SEND);
		
		$article = new stdClass();
		$article->id = 0;
		$article->asset_id = 0;
		$article->title = __('Digest', 'wp-mailster') . ' ' . $mList->name . ' ' . $dateUtils->formatDateWithoutTimeAsConfigured($digestQueue[0]->digest_time);
		$article->alias = strtolower(str_replace(' ', '-', trim($article->title)));
		$article->introtext = $digestMail->Body;
		$article->state = $mList->archive2article_state;
		$article->catid = $mList->archive2article_cat;
		$article->created = $dateUtils->getCurrTimeDbFormat();
		$article->created_by = $mList->archive2article_author;
		$article->created_by_alias = '';
		$article->modified = $article->created;
		$article->modified_by = $article->created_by;
		
		$error = false;
		/*$jContentUtils = MstFactory::getJContentUtils();
		$log->debug('Storing article: '.print_r($article, true));
		$articleId = $jContentUtils->storeArticle($article);
		if(!$articleId){
			$log->error('Failed to store digest article: '.print_r($article, true));
			$error = true;
		}*/

        $error = true; // TODO CHANGE ME
        $log->error('DigestSender->storePendingDigestArticle NOT IMPLEMENTED IN WORDPRESS!!!'); // TODO IMPLEMENT ME
		
		$this->processSendOrStoreResults($error, $digest);
		
		unset($digestMail); // we don't need this object anymore now
		$log->info('Time left after storing this digest: ' . ($execEnd - time()));
	}
	
	function sendPendingDigest($digest, $minDuration, $execEnd){
		$log = MstFactory::getLogger();
		$mstConfig = MstFactory::getConfig();
		$mstQueue = MstFactory::getMailQueue();
		$mailingListUtils = MstFactory::getMailingListUtils();
        /** @var MailsterModelUser $userModel */
		$userModel = MstFactory::getUserModel();
		$digestUser = $userModel->getUserData($digest->user_id, $digest->is_core_user);
        $log->debug('sendPendingDigest: User in digest obj: '.$digest->user_id.', is_core_user: '.$digest->is_core_user.', found user model: '.print_r($digestUser, true));
		$userName = $digestUser->name .'<'.$digestUser->email.'> (ID: '.$digest->user_id.', is_core_user: '.$digest->is_core_user.')';
		$digestQueue = $mstQueue->getMailQueueForDigest($digest->id);
		$digestQueueSize = count($digestQueue);
		$log->debug('sendPendingDigest: Digest to send for user '.$userName. ' is for list ' . $digest->list_id. ', will be built from '.$digestQueueSize.' mails', MstConsts::LOGENTRY_MAIL_SEND);
        $nrDigestsSent = 0;

		$mList = $mailingListUtils->getMailingList($digest->list_id);		
		$listLocked = $mailingListUtils->isListLocked($digest->list_id);
		
		if($listLocked){
			$log->debug('List ' . $digest->list_id . ' of mail is locked!');
			$listLockInvalid = $mailingListUtils->isListLockInvalid($digest->list_id);
			if($listLockInvalid){
				$log->debug('Lock of list ' . $digest->list_id . ' is invalid, continue with digest sending (but do not reset lock)');
			}else{
				$log->debug('Do not prepare digest further, locking of list '.$digest->list_id.' is valid');
				return; // exit function
			}
		}

        $sendThrottlingActive = $mailingListUtils->isSendThrottlingActive();
        $sendLimitReached = ($sendThrottlingActive && $mailingListUtils->isSendLimitReached());
		if($sendLimitReached){
			$log->debug('Do not prepare digest further, send limit for list '.$digest->list_id.' reached');
			return; // exit function
		}
		
		$digestMail = $this->prepareDigestMail4Recipient($digest, $digestQueue, $mList);
		if(!$digestMail){
			$log->debug('No digest to send, update DB anyway (act as no send errors)...', MstConsts::LOGENTRY_MAIL_SEND);
			$this->processSendOrStoreResults(false, $digest);
			$log->debug('No digest was send, return...', MstConsts::LOGENTRY_MAIL_SEND);
			return false;
		}
		$log->info('Sending digest ID '.$digest->id.' . . .', MstConsts::LOGENTRY_MAIL_SEND);

        if(!is_null($digestUser)){
            $loggingLevel = $mstConfig->getLoggingLevel(); // get current Logging Level
            $isDebugMode = ($loggingLevel == $log->getLoggingLevel(MstLog::DEBUG));
            $log->debug('Logging level: ' . $loggingLevel . ', is debug: ' . ($isDebugMode ? 'true' : 'false'), MstConsts::LOGENTRY_MAIL_SEND);

            if($isDebugMode && $digestMail->Mailer == 'smtp'){
                $log->debug('*** Start SMTP debug ***', MstConsts::LOGENTRY_MAIL_SEND);
                $digestMail->SMTPDebug = 2;
            }
			$runTimeError = '';
            try{
				$sendOk = $digestMail->Send();
			}catch(Exception $e){
				$runTimeError = 'Exception ' . get_class($e) . ' Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
			}
            if($isDebugMode && $digestMail->Mailer == 'smtp'){
                $log->debug('*** Stop SMTP debug ***', MstConsts::LOGENTRY_MAIL_SEND);
            }

            $error	= $digestMail->IsError();

            $this->processSendOrStoreResults($error, $digest);

            if($error == true) { // send errors?
                $errorMsg  = 'Sending of digest ' . $digest->id . ' failed!';
                $errorMsg .= ' Last error: '. $digestMail->ErrorInfo;
                $errorMsg .= ' Runtime error: '.$runTimeError;
                $log->error($errorMsg, MstConsts::LOGENTRY_MAIL_SEND);
                $log->error('Email was: '.print_r($digestMail, true), MstConsts::LOGENTRY_MAIL_SEND);
                $sendError = true;
            }else{
                $mailingListUtils->writeListStat($digest->list_id, 1, 1);
                $nrDigestsSent++;
            }
            $log->info('Time left after sending this digest: ' . ($execEnd - time()), MstConsts::LOGENTRY_MAIL_SEND);
        }else{
            $log->warning('No digest user info, thus digest email cannot be sent, digestMail: '.print_r($digestMail, true));
        }
		unset($digestMail); // we don't need this object anymore now
        return $nrDigestsSent;
	}
	
	function processSendOrStoreResults($error, $digest){
		$log = MstFactory::getLogger();
		$mstQueue 	= MstFactory::getMailQueue();
        /** @var MailsterModelDigest $digestModel */
		$digestModel = MstFactory::getDigestModel();
		$log->debug('processSendOrStoreResults Send Results of sending digest ID '.$digest->id.': '.($error ? 'FAILED' : 'successful'));
		if($error == false) { // send errors?
			$mstQueue->removeDigestMailsFromQueue($digest);
			$digestModel->updateSendingDate($digest->id);
		}else{
		//	$mstQueue->incrementDigestError($mail->id, $recipient->email, $maxSendAttempts);
		}
	}
	
	function prepareDigestMail4Recipient($digest, $digestQueue, $mList, $replaceAttachIds=false){
		$log = MstFactory::getLogger();
		$log->debug('prepareDigestMail4Recipient()');
		$log->debug(print_r($digest, true));
		$log->debug('Digest Queue of digest ID '.$digest->id);
		$log->debug(print_r($digestQueue, true));
		$dateUtils = MstFactory::getDateUtils();
        /** @var MailsterModelUser $userModel */
		$userModel = MstFactory::getUserModel();
		$mailModel = MstFactory::getMailModel();
		$threadModel = MstFactory::getThreadModel();
		$attachUtils = MstFactory::getAttachmentsUtils();
		if($digest->user_id != MstConsts::DIGEST_USER_ID_MEANING_DIGEST_TO_ARTICLE){
			$digestUser = $userModel->getUserData($digest->user_id, $digest->is_core_user);
		}else{
			$digestUser = null;
		}
		$mailUtils = MstFactory::getMailUtils();
		$listEmail = $mList->list_mail;
		
		$threadSubjects = array();
		$threadBodySections = array();
		$lastThreadId = -1;
		$threadIndex = -1;
		
		$recipientMail = null;
		
		if(count($digestQueue) <= 0){
			$log->debug('prepareDigestMail4Recipient - Nothing to send out, skip');
			return false; // nothing to send out
		}
		
		for($i=0, $n=count($digestQueue); $i < $n; $i++){
			$digestMail = &$digestQueue[$i];
			
			if($lastThreadId !== $digestMail->thread_id){
				$threadIndex++;
				$threadSubjects[] = $threadModel->getThreadSubject($digestMail->thread_id);
				$threadBodySections[] = new stdClass();
				$threadBodySections[$threadIndex]->thread_id = $digestMail->thread_id;
				$threadBodySections[$threadIndex]->threadMessages = array();
			}			
			
			$mail = $mailModel->getData($digestMail->mail_id);
			$mail->attachments = $attachUtils->getAttachmentsOfMail($digestMail->mail_id);
			$digestMailPart = $this->prepareDigestMailPart($mail, $mList, $replaceAttachIds);
			$log->debug('Digest Mail Part for mail ID '.$mail->id);
			$log->debug(print_r($digestMailPart, true));

			if($i==0){
				$recipientMail = $digestMailPart;
			}
			
			$threadMessage = new stdClass();
			$threadMessage->body = $digestMailPart->Body;
			$threadMessage->from_email = $digestMailPart->From;
			$threadMessage->from_name = $digestMailPart->FromName;
			$threadMessage->date = $dateUtils->formatDateAsConfigured($mail->receive_timestamp);
			
			$threadBodySections[$threadIndex]->threadMessages[] = $threadMessage;
		}
				
		$htmlBody = $this->buildDigestHtml($digest, $threadSubjects, $threadBodySections);
		$plainBody = $this->buildDigestPlain($digest, $threadSubjects, $threadBodySections);
		
		$recipientMail->html = $htmlBody;
		$recipientMail->IsHTML(true);
		$recipientMail->Body = $htmlBody;
		$recipientMail->AltBody = $plainBody;
		
		$subject = $mailUtils->modifyMailSubjectWithMailIndependentVars($mList) . $digest->summaryStr;
		$recipientMail->setSubject($subject);
		$log->debug('Set digest subject to: '.$subject);
		
		$recipientMail->From = trim($mList->list_mail);
		$recipientMail->FromName = trim(str_replace(',', ' ', $mList->name)); // names may not contain commas
		
		if(!is_null($digestUser)){
			if(property_exists($digestUser, 'email') && !is_null($digestUser->email) && strtolower(trim($digestUser->email)) !== '') {
				if (strtolower(trim($digestUser->email)) !== strtolower(trim($listEmail))) {
					$log->debug('Next mail to be included in digest: ' . $digestMail->mail_id, MstConsts::LOGENTRY_MAIL_SEND);
					$digestUser->name = str_replace(',', ' ', $digestUser->name); // names may not contain commas
					$log->debug('Add ' . $digestUser->name . ' <' . $digestUser->email . '> to TO recipients for digest', MstConsts::LOGENTRY_MAIL_SEND);
					$recipientMail->AddAddress($digestUser->email, $digestUser->name);
				} else {
					$log->warning('prepareDigestMail4Recipient: Do not send digest, recipient ' . $digestUser->email
						. ' is the email address of the mailing list (' . $listEmail . ')', MstConsts::LOGENTRY_MAIL_SEND);
				}
			}else{
				$log->debug('prepareDigestMail4Recipient: Do not add recipient addressee (empty email address)');
			}
		}else{
			$log->debug('prepareDigestMail4Recipient: Do not add recipient addressee (user not existing)');
		}
		
		$log->debug('prepareDigestMail4Recipient: Mail with recipients: ' . print_r($recipientMail, true), MstConsts::LOGENTRY_MAIL_SEND);
		return $recipientMail;		
	}
	
	function buildDigestHtml($digest, $threadSubjects, $threadBodySections){
		$env = MstFactory::getEnvironment();
		
		$headline = '';
		$headline .= '<div style="font-family: arial; font-weight: bold; color: #222222; padding: 0px">';
		$headline .= $digest->summaryStr;
		$headline .= '</div>';
		$headline .= "\r\n";
		
		$toc = '';
		$toc .= '<ul style="margin-left:3px; padding-left:0px">';
		foreach($threadSubjects As $key=>$threadSubject){
			$toc .= '<li type="sqare" style="color: #333333">';
			$toc .= '<a type="sqare" style="color:#2266dd;text-decoration:none;" href="#thread_section'.$key.'">';
			$toc .= $threadSubject;
			$toc .= '</a>';
			$toc .= '</li>';
		}
		$toc .= '</ul>';
		$toc .= "\r\n";
		
		$content = '';
		foreach($threadBodySections As $key=>$threadBodySection){
			$content .= '<div style="background-color: #f3f3f3; font-family: arial; border-top: 1px solid #e3e3e3; padding: 4px 0 5px 32px; ">';
      		$content .= $threadSubjects[$key];
			$content .= '</div>';
			$content .= "\r\n";
			foreach($threadBodySection->threadMessages As $threadKey=>$threadMessage){
				$content .= '<ul>';
				$content .= '<span style="color:#222222; font-weight: bold;">';
				$content .= $threadMessage->from_name .'<'.$threadMessage->from_email.'>';
				$content .= '</span>&nbsp;';
				$content .= $threadMessage->date;
				$content .= '<br/>';
				$content .= '<br/>';
				$content .= nl2br($threadMessage->body);
				$content .= '</ul>';
				$content .= "\r\n";
			}
		}

		$html = '';
		$html .= $headline;
		$html .= $toc;
		$html .= $content;
		
		return $html;
	}
	
	function buildDigestPlain($digest, $threadSubjects, $threadBodySections){
		$env = MstFactory::getEnvironment();
		
		$headline = '';
		$headline .= '============================================================================='."\r\n";
		$headline .= $digest->summaryStr."\r\n";
		$headline .= '============================================================================='."\r\n";
		
		$toc = "\r\n";
		foreach($threadSubjects As $key=>$threadSubject){
			$toc .= ' - '. $threadSubject. "\r\n";
		}
		$toc .= "\r\n";
		$toc .= "\r\n";
		
		$content = '';
		foreach($threadBodySections As $key=>$threadBodySection){
			//$threadUrl = 'tbd';
			$content .= '============================================================================='."\r\n";
      		$content .= $threadSubjects[$key]."\r\n";
      		//$content .= 'Url: '.$threadUrl."\r\n";
			$content .= '============================================================================='."\r\n";			
			foreach($threadBodySection->threadMessages As $threadKey=>$threadMessage){				
				$content .= '---------- '.($threadKey+1).' / '. count($threadBodySection->threadMessages).' ----------'."\r\n";
				$content .= $threadMessage->from_name .'<'.$threadMessage->from_email.'>'."\r\n";	
				$content .= $threadMessage->date."\r\n";
				$content .= "\r\n";
				$content .= $threadMessage->body;
				$content .= "\r\n";
				$content .= "\r\n";
			}
		}

		$html = '';
		$html .= $headline;
		$html .= $toc;
		$html .= $content;
		
		return $html;
	}
	
	function prepareDigestMailPart($mail, $mList, $replaceAttachIds=false){// Prepare E-Mail Part
		$log 			= MstFactory::getLogger();
		$mailUtils 		= MstFactory::getMailUtils();
		$mstConfig 		= MstFactory::getConfig();
		$threadUtils 	= MstFactory::getThreadUtils();
		$attachUtils 	= MstFactory::getAttachmentsUtils();
		$env 			= MstFactory::getEnvironment();
		$mailSender 	= MstFactory::getMailSender();
		
		$log->debug('Prepare digest mail parts content, working with: ' . print_r($mail, true), MstConsts::LOGENTRY_MAIL_SEND);
		
		// add/remove/convert parts according to list settings (i.e. HTML only mail without plain text part or vice versa)		
		$mail = $mailUtils->addRemoveConvertBodyParts($mail, $mList); 
		// do modifications (header, footer, subject)
		$mail = $mailUtils->modifyMailContent($mList, $mail);
		// load template
		$mail2send = $mailSender->getListMailTmpl($mList);
		
		//$noFromName = ( is_null($mail->from_name) || (trim($mail->from_name) === '') );
		
		$mail2send->From = trim($mail->from_email);
		$mail2send->FromName = trim(str_replace(',', ' ', $mail->from_name)); // names may not contain commas
					
		// Bounce Handling here
		$bounceAddress = trim($mList->list_mail); // default
		if($mList->bounce_mode == MstConsts::BOUNCE_MODE_LIST_ADDRESS){
			$bounceAddress = trim($mList->list_mail); // bounces return to list	
		}elseif($mList->bounce_mode == MstConsts::BOUNCE_MODE_DEDICATED_ADDRESS){
			$bounceAddress = trim($mList->bounce_mail); // bounces go to dedicated and fixed address
		}
		
		$mail2send->addCustomHeader(MstConsts::MAIL_HEADER_RETURN_PATH . ': <' . $bounceAddress . '>'); // try to set return path
		$mail2send->addCustomHeader(MstConsts::MAIL_HEADER_ERRORS_TO . ': ' . $bounceAddress); // try to ensure return/error path
		
		// Fixed in Mailster 0.4.1 -> Sender is always the mailing list...
		$senderAddress = trim($mList->list_mail); // default
		$mail2send->Sender = $senderAddress;
		$mail2send->addCustomHeader(MstConsts::MAIL_HEADER_SENDER . ': ' . $senderAddress); //  make sure Sender is really set correct		
			
		if($mstConfig->addMailsterMailHeaderTag()){
			$mail2send->addCustomHeader(MstConsts::MAIL_HEADER_MAILSTER_TAG); // tag mail as a Mailster mail
		}	
		
		if((!is_null($mail->in_reply_to)) && (strlen($mail->in_reply_to)>0)){
			// This mail is a reply
			$log->debug('This is a reply, adding In-Reply-To header...', MstConsts::LOGENTRY_MAIL_SEND);
			$mail2send->addCustomHeader(MstConsts::MAIL_HEADER_IN_REPLY_TO . ': ' . $mail->in_reply_to);
			if($mList->clean_up_subject > 0){
				$mail->subject = $threadUtils->getThreadSubject($mail->thread_id);
				// as we just undo the subject modifications (e.g. prefix), we have to do this again:
				$mail->subject = $mailUtils->modifyMailSubject($mail, $mList);
			}
			$replyPrefix = $mstConfig->getReplyPrefix();
			if($mstConfig->addSubjectPrefixToReplies()){	
				$log->debug('Adding reply prefix: ' . $replyPrefix, MstConsts::LOGENTRY_MAIL_SEND);
				$mail->subject = $replyPrefix . ' ' . $mail->subject;
			}else{
				$log->debug('Do not add reply prefix (' . $replyPrefix . ')', MstConsts::LOGENTRY_MAIL_SEND);
			}
		}
		
		$mail2send->setSubject($mail->subject);

		$digestMailFormat = $mstConfig->getDigestMailFormat();
		if($digestMailFormat === MstConsts::DIGEST_MAIL_FORMAT_PLAIN){
			if(is_null($mail->body) || strlen(trim($mail->body)) == 0){
				$log->debug('Send digest as plain text mail', MstConsts::LOGENTRY_MAIL_SEND);
				$mail->body = $mailUtils->getPlainTextVersionOfHTMLBody($mail->html);
				$log->debug('Input for conversion was: '.print_r($mail->html, true));
				$log->debug('Output of conversion was: '.print_r($mail->body, true));
			}
			$mail2send->IsHTML(false);
			$mail2send->setBody($mail->body);
			$mail2send->Body = $mail->body;
			$mail2send->AltBody=$mail->body;
		}else{
			if(is_null($mail->html) || strlen(trim($mail->html)) == 0){
				$log->debug('Send digest as HTML mail', MstConsts::LOGENTRY_MAIL_SEND);
				$mail->html = $mailUtils->getHTMLVersionOfPlaintextBody($mail->body);
				$log->debug('Input for conversion was: '.print_r($mail->body, true));
				$log->debug('Output of conversion was: '.print_r($mail->html, true));
			}
			
			if($replaceAttachIds && count($mail->attachments) > 0){
				$log->debug('Replace inline attachment content IDs with attachments');
				$mail->html = $mailUtils->replaceContentIdsWithAttachments($mail->html, $mail->attachments);
			}
			
			$mail2send->IsHTML(true);
			$mail2send->setBody($mail->html);
			$mail2send->Body = $mail->html;
			$mail2send->AltBody=$mail->html;
		}

        $subscribeHeaderSubject = urlencode(sprintf(__('Subscribe to %s', 'wp-mailster'), $mList->name));
        $unsubscribeHeaderSubject = urlencode(sprintf(__('Unsubscribe from %s', 'wp-mailster'), $mList->name));
		
		$mail2send->addCustomHeader(MstConsts::MAIL_HEADER_PRECEDENCE . ': list');		
		$mail2send->addCustomHeader($mailUtils->getListIDMailHeader($mList));		
		$mail2send->addCustomHeader(MstConsts::MAIL_HEADER_LIST_UNSUBSCRIBE . ': <mailto:' . trim($mList->admin_mail) . '?subject='.$unsubscribeHeaderSubject.'>'); // admin gets unsubscribe requests
		$mail2send->addCustomHeader(MstConsts::MAIL_HEADER_LIST_ARCHIVE . ': <' . home_url() . '>'); // archive currently not directly linked
		$mail2send->addCustomHeader(MstConsts::MAIL_HEADER_LIST_POST . ': <mailto:' . trim($mList->list_mail) . '>'); // address for posting new posts/replies
		$mail2send->addCustomHeader(MstConsts::MAIL_HEADER_LIST_HELP . ': <mailto:' . trim($mList->admin_mail) . '?subject=help>'); // admin gets help requests
		$mail2send->addCustomHeader(MstConsts::MAIL_HEADER_LIST_SUBSCRIBE . ': <mailto:' . trim($mList->admin_mail) . '?subject='.$subscribeHeaderSubject.'>'); // admin gets subscribe requests
	//	$mail2send->addCustomHeader(MstConsts::MAIL_HEADER_MSG_ID . ': ' . $mail->id); // insert mail ID, this can be used to identify the mail within Mailster
		$mail2send->addCustomHeader(MstConsts::MAIL_HEADER_BEEN_THERE . ': ' . trim($mList->list_mail)); // we have been here...	
		
		return $mail2send;
	}
	
}

