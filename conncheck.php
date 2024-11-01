<?php
	if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
		die( 'These are not the droids you are looking for.' );
	}

//connection check
add_action( 'wp_ajax_conncheck', 'conncheck_callback' );
function conncheck_callback() {
	
	$task = sanitize_text_field($_POST["task"]);
	$mstUtils 	= MstFactory::getUtils();
	$mstConfig	= MstFactory::getConfig();
	$log	= MstFactory::getLogger();
    /** @var MailsterModelServer $serverModel */
    $serverModel = MstFactory::getServerModel();
	$resultObj = new stdClass();
	$res = __("Connection Check called", 'wp-mailster');
	$mailSettingsLink = 'https://wpmailster.com/doc/mail-provider-settings';
	$ajaxParams =sanitize_text_field($_POST['mtrAjaxData']);
	$ajaxParams = $mstUtils->jsonDecode(stripslashes($ajaxParams));
	
	if($task == 'inboxConnCheck'){
		$res = __("Inbox Check called", 'wp-mailster');

        $in_server 	 = $ajaxParams->{'in_server'};
		$in_host 	 = trim($ajaxParams->{'in_host'});
		$in_port 	 = trim($ajaxParams->{'in_port'});
		$in_user 	 = $ajaxParams->{'in_user'};
		$in_pw 		 = $ajaxParams->{'in_pw'};
		$in_secure	 = $ajaxParams->{'in_secure'};
		$in_sec_auth = $ajaxParams->{'in_sec_auth'};
		$in_protocol = $ajaxParams->{'in_protocol'};
		$in_params 	 = $ajaxParams->{'in_params'};
		
		if (extension_loaded('imap')){

            $useSecAuth = $in_sec_auth !== '0' ? '/secure' : '';
            if(is_null($in_secure) || $in_secure === ''){
                $useSec = '/notls'; // against opportunistic STARTTLS
            }else{
                $useSec = '/' . $in_secure;
            }
            $protocol = $in_protocol !== '' ? '/' . $in_protocol : '';

			$openTimeoutOld = imap_timeout(IMAP_OPENTIMEOUT);
			$chgTimeoutSuccess = imap_timeout(IMAP_OPENTIMEOUT, $mstConfig->getMailboxOpenTimeout());
			$host = '{' . $in_host . ':' . $in_port . $useSecAuth . $protocol . $useSec . $in_params . '}'. 'INBOX';
			$mBox = @imap_open ($host, $in_user, $in_pw);
			$imapErrors = imap_errors();				
			if($mBox){
				$res = __("Inbox settings OK", 'wp-mailster');
				imap_close($mBox); // close mail box
			}else{						
				if($imapErrors){
					$errorMsg = "\n"."\n".__("Errors", 'wp-mailster').":\n";
					foreach($imapErrors as $error){
						$errorMsg =  $errorMsg."\n" . $error;
					}
				}else{
					$errorMsg = "\n"."\n".__("No error messages available", 'wp-mailster');
				}
				$errorMsg =  $errorMsg."\n";
				$res = __("Connection NOT ok. Check your settings!", 'wp-mailster'). $errorMsg;
			}
			$chgTimeoutSuccess = imap_timeout(IMAP_OPENTIMEOUT, $openTimeoutOld);						
		}else{
			$res = __("Connection not possible, no IMAP extension available in PHP installation.", 'wp-mailster')."\n";
			$res .= __("Install PHP with IMAP library support.", 'wp-mailster'); // "Install PHP with IMAP support.";
		}
	
	}else if($task == 'outboxConnCheck'){

       	$out_server 	 = $ajaxParams->{'out_server'};
		$list_name 		 = $ajaxParams->{'list_name'};
		$admin_email 	 = $ajaxParams->{'admin_email'};
		$use_cms_mailer  = $ajaxParams->{'use_cms_mailer'};
		$out_email 	 	 = $ajaxParams->{'out_email'};
		$out_user 	 	 = $ajaxParams->{'out_user'};
		$out_pw 	 	 = $ajaxParams->{'out_pw'};
		$out_host 		 = $ajaxParams->{'out_host'};
		$out_secure 	 = $ajaxParams->{'out_secure'};
		$out_sec_auth  	 = $ajaxParams->{'out_sec_auth'};
		$out_port	  	 = $ajaxParams->{'out_port'};
		$out_name 		 = get_bloginfo("name");
		
		$res = __("Sender check called", 'wp-mailster');
		 
		$body = __("This is a test message sent from the mailing list", 'wp-mailster') . ' \'' . $list_name  . '\' ';
		$body .= __("at the webpage", 'wp-mailster') . ' \'' . $out_name . '\'' . "\n";
		$body .= __("You receive this email because you are the admin of the list.", 'wp-mailster') . "\n\n";
		$body .= __("Working settings", 'wp-mailster') . ":\n";
		$body .= '----------------------' . "\n";
		if($use_cms_mailer !== '1')
		{
			$body .= __("Host/Server", 'wp-mailster').': ' . $out_host . "\n";
			$body .= __("Port", 'wp-mailster').': ' . $out_port . "\n";
			$body .= __("User/Login", 'wp-mailster') . ': ' . $out_user . "\n";
			$body .= __("Password", 'wp-mailster') . ': ****** ('.__("hidden", 'wp-mailster'). ")\n";
			$body .= __("Secure setting", 'wp-mailster').': ' . ( $out_secure != '' ? strtoupper($out_secure) : __("None", 'wp-mailster') ). "\n";
			$body .= __("Use secure authentication", 'wp-mailster').': ' . ($out_sec_auth == '0' ? __("No", 'wp-mailster') : __("Yes", 'wp-mailster')) . "\n\n";
			$body .= __("If you use a public mail provider consider sharing your settings with others users", 'wp-mailster')."\n";
			$body .= ' (-> ' . $mailSettingsLink . ')'. "\n";
		}else{
		 	$body = $body . 'CMS Mailer'. "\n";
		}
		
		$mail2send = MstFactory::getMailer();
        if(property_exists($mail2send, 'SMTPAutoTLS')){
            $mail2send->SMTPAutoTLS = false;
        }

        if(property_exists($mail2send, 'Debugoutput')){
            $mailerVersion = null;
            if(property_exists($mail2send, 'Version')){
                $mailerVersion = $mail2send->Version;
            }elseif(defined(get_class($mail2send).'::VERSION')){
                $mailerVersion = $mail2send::VERSION;
            }elseif(defined('MstMailer::VERSION')){
                $mailerVersion = MstMailer::VERSION;
            }
            $mail2send->Debugoutput = function($str, $level) use ($mailerVersion, $log) {
                if(method_exists($log, 'debug')){
                    $vStr = is_null($mailerVersion) ? '(unknown)' : '(v'.$mailerVersion.')';
                    $mailerLogEntry = rtrim("DEBUG PHPMAILER $vStr\t$level\t$str");
                    $log->debug($mailerLogEntry);
                }
            };
        }

		if($use_cms_mailer !== '1')
		{
			$mail2send->From  = $out_email;
			$mail2send->useSMTP($out_sec_auth == '0' ? false : true, $out_host, $out_user, $out_pw, $out_secure, $out_port); 
			$mail2send->setSender(array($out_email, $list_name));	
		}		
		
	    $mail2send->setSubject(__("WP Mailster - Mail Sender Test Email", 'wp-mailster'));
		$mail2send->IsHTML(true);
		$mail2send->Body = nl2br(htmlentities($body));
		$mail2send->AltBody = $body;

        $exceptionErrorMsg = '';
        $sendOk = false;

        if($mail2send->Mailer == 'smtp'){
            $mail2send->SMTPDebug = 2;
        }

        try{
            $mail2send->AddAddress($admin_email, $list_name . ' ' . __("Admin", 'wp-mailster'));
            $mail2send->addReplyTo($out_email);
            $sendOk = $mail2send->Send();
        } catch (Exception $e) {
            $exceptionErrorMsg = ' Exception ' . get_class($e) . ' Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
        }

		if( true === $sendOk ) {
			$res = __("Sender settings OK", 'wp-mailster') . ".\n";
			$res .= __("WP Mailster has sent a test mail to the mailing list's admin", 'wp-mailster') . "\n";
			$res .= '(' . __("Email", 'wp-mailster') . ': ' . $admin_email . ') ' . "\n\n";
			$res .= __("Go check it!", 'wp-mailster');
		} else {
			$res = __("Errors while sending a test mail to mailing list admin", 'wp-mailster') . ' ';
			$res .= '(' . $admin_email . ').' . "\n" . __("Connection NOT ok. Check your settings!", 'wp-mailster') ."\n\n";
			$res .= __("Errors", 'wp-mailster') . ":\n".$exceptionErrorMsg;
		}		
	}

    //MstFactory::getLogger()->debug('conncheck res: '.$res);
	$resultObj->checkresult = $res;
    //MstFactory::getLogger()->debug('conncheck obj: '.print_r($resultObj, true));
    $json = json_encode($resultObj);
    //MstFactory::getLogger()->debug('conncheck json: '.$json);
    echo $json;
	wp_die(); // this is required to terminate immediately and return a proper response
}


add_action( 'wp_ajax_getInboxStatus', 'getInboxStatus_callback' );
function getInboxStatus_callback() {

	$mstUtils 	= MstFactory::getUtils();
	$listUtils	= MstFactory::getMailingListUtils();
	$mailbox 	= MstFactory::getMailingListMailbox();
	$resultArray = array();
	$res = __( 'Get Inbox status called', 'wp-mailster' );
	$ajaxParams = sanitize_text_field($_POST['mtrAjaxData']);
	$ajaxParams = $mstUtils->jsonDecode(stripslashes($ajaxParams));
	$res = true;
	
	$mailboxStatus = __(" NOT ok. Check your settings!", 'wp-mailster');
	$listId = $ajaxParams->{'listId'};
	$mList = $listUtils->getMailingList($listId);
	if($listUtils->lockMailingList($mList->id)){
		if($mailbox->open($mList)){
			$mailboxStatus = $mailbox->getInboxStatus();
			$mailbox->close();
		}else{
			$res = false;
		}
		$listUtils->unlockMailingList($mList->id);
	}else{
		$res = false;
	}
		
	$res = ($res ? 'true' : 'false');
	
	$mailboxStatusText =__( 'Inbox status', 'wp-mailster' ) . print_r($mailboxStatus, true);
	$resultArray['res'] = $res;
	$resultArray['mailboxStatus'] = $mailboxStatus;
	$resultArray['mailboxStatusText'] = $mailboxStatusText;
	echo json_encode($resultArray);
	wp_die(); 
}

add_action( 'wp_ajax_removeFirstMailFromMailbox', 'removeFirstMailFromMailbox_callback' );
function removeFirstMailFromMailbox_callback(){
	$mstUtils 	= MstFactory::getUtils();
	$listUtils	= MstFactory::getMailingListUtils();
	$mailbox 	= MstFactory::getMailingListMailbox();
	$resultArray = array();
	$res = __( 'Delete first email in inbox called', 'wp-mailster' );
	$ajaxParams = sanitize_text_field($_POST['mtrAjaxData']);
	$ajaxParams = $mstUtils->jsonDecode(stripslashes($ajaxParams));
	$res = true;
	
	$listId = $ajaxParams->{'listId'};
	$mList = $listUtils->getMailingList($listId);
	if($listUtils->lockMailingList($mList->id)){
		if($mailbox->open($mList)){
			$mailbox->removeFirstMailFromMailbox();
			$mailbox->close();
		}else{
			$res = false;
		}
		$listUtils->unlockMailingList($mList->id);
	}else{
		$res = false;
	}
		
	$res = ($res ? 'true' : 'false');
	
	$resultArray['res'] = $res;
    echo json_encode($resultArray);
	wp_die();
}

add_action( 'wp_ajax_removeAllMailsFromMailbox', 'removeAllMailsFromMailbox_callback' );
function removeAllMailsFromMailbox_callback(){
	$mstUtils 	= MstFactory::getUtils();
	$listUtils	= MstFactory::getMailingListUtils();
	$mailbox 	= MstFactory::getMailingListMailbox();
	$resultArray = array();
	$res = __( 'Remove all emails in the send queue called', 'wp-mailster' );
	$ajaxParams = sanitize_text_field($_POST['mtrAjaxData']);
	$ajaxParams = $mstUtils->jsonDecode(stripslashes($ajaxParams));
	$res = true;
	
	$listId = $ajaxParams->{'listId'};
	$mList = $listUtils->getMailingList($listId);
	if($listUtils->lockMailingList($mList->id)){
		if($mailbox->open($mList)){
			$mailbox->removeAllMailsFromMailbox();
			$mailbox->close();
		}else{
			$res = false;
		}
		$listUtils->unlockMailingList($mList->id);
	}else{
		$res = false;
	}
		
	$res = ($res ? 'true' : 'false');
	
	$resultArray['res'] = $res;
	echo json_encode($resultArray);
	wp_die();
}

add_action( 'wp_ajax_removeAllMailsInSendQueue', 'removeAllMailsInSendQueue_callback' );
function removeAllMailsInSendQueue_callback(){
	$mstUtils 	= MstFactory::getUtils();
	$mstQueue = MstFactory::getMailQueue();
	$resultArray = array();
	$res = __( 'Remove all emails in the send queue called', 'wp-mailster' );
	$ajaxParams = sanitize_text_field($_POST['mtrAjaxData']);
	$ajaxParams = $mstUtils->jsonDecode(stripslashes($ajaxParams));
	$res = true;
	
	$listId = $ajaxParams->{'listId'};
	$res = $mstQueue->removeAllMailsFromListFromQueue($listId);
		
	$res = ($res ? 'true' : 'false');
	
	$resultArray['res'] = $res;
    echo json_encode($resultArray);
	wp_die();
}

add_action( 'wp_ajax_unlockMailingList', 'unlockMailingList_callback' );
function unlockMailingList_callback(){
	$mstUtils 	= MstFactory::getUtils();
	$listUtils 	= MstFactory::getMailingListUtils();
	$resultArray = array();
	$res = __( 'Unlock mailing list called', 'wp-mailster' );
	$ajaxParams = sanitize_text_field($_POST['mtrAjaxData']);
	$ajaxParams = $mstUtils->jsonDecode(stripslashes($ajaxParams));
	$res = true;
	
	$listId = $ajaxParams->{'listId'};
	$res = $listUtils->unlockMailingList($listId);
	$res = ($res ? 'true' : 'false');
	
	$resultArray['res'] = $res;
    echo json_encode($resultArray);
	wp_die();
}