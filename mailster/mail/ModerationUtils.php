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

class MstModerationUtils
{
    public static function sendRequestForModeration($mailId, $notifySenderIfListSettingsWantTo = true){
        $log = MstFactory::getLogger();
        $log->debug('MstModerationUtils::sendRequestForModeration');
        $mailUtils = MstFactory::getMailUtils();
        $mailingListUtils = MstFactory::getMailingListUtils();
        $mail = $mailUtils->getMail($mailId);
        $mList = $mailingListUtils->getMailingList($mail->list_id);

        $mailer = self::getModerationRequestMailTmpl($mail, $mList);
        try {
            $mailer->Send();  // send notification
        } catch (Exception $e) {
            $exceptionErrorMsg = 'Exception ' . get_class($e) . ' Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
            $log->error('MstModerationUtils::sendRequestForModeration error: '.$exceptionErrorMsg);
            $log->error('Email was: '.print_r($mailer, true));
        }
        $error =  $mailer->IsError();
        if($error == true) { // send errors?
            $log->error('Sending moderation request failed! Last error: ' . $mailer->ErrorInfo);
            $log->error('Email was: '.print_r($mailer, true));
        }else{
            $log->debug('MstModerationUtils::sendRequestForModeration Sent moderation request');
        }

        if($notifySenderIfListSettingsWantTo){
            if($mList->mod_info_sender_moderation){
                self::notifySenderOfModerationOfMail($mailId, $mail, $mList);
            }
        }
    }

    protected static function getApprovalUrl($hashkey, $mailId){
        $log = MstFactory::getLogger();
        $hashUtils = MstFactory::getHashUtils();
        $approveRand = rand();
        $approveHash = $hashUtils->getModerationKey($approveRand, $hashkey);
        $approvalUrl = get_site_url().'?wpmst-moderate=approve&m=' . $mailId . '&h=' . $approveHash . '&s=' . $approveRand;
        $log->debug('getApprovalUrl: '.$approvalUrl);
        return $approvalUrl;
    }

    protected static function getRejectionUrl($hashkey, $mailId){
        $log = MstFactory::getLogger();
        $hashUtils = MstFactory::getHashUtils();
        $declineRand = rand();
        $declineHash = $hashUtils->getModerationKey($declineRand, $hashkey);
        $rejectUrl = get_site_url().'?wpmst-moderate=reject&m=' . $mailId . '&h=' . $declineHash . '&s=' . $declineRand;
        $log->debug('getRejectionUrl: '.$rejectUrl);
        return $rejectUrl;
    }

    public static function getModerationRequestMailTmpl($mail, $mList){
        $log = MstFactory::getLogger();
        $env = MstFactory::getEnvironment();
        $mstConfig = MstFactory::getConfig();
        $mailSender = MstFactory::getMailSender();
        $subject = sprintf(__('Please moderate mail: %s', 'wp-mailster'), $mail->subject);
        $senderStr = ((!is_null($mail->from_name) && (strlen($mail->from_name)>0)) ? ($mail->from_name . ' ') : '') . '<'.$mail->from_email.'>';

        $approveLink = self::getApprovalUrl($mail->hashkey, $mail->id);
        $declineLink = self::getRejectionUrl($mail->hashkey, $mail->id);

        $header = htmlentities(sprintf(__('Sender of the mail: %s', 'wp-mailster'), $senderStr)).'<br/>';
        $header .= htmlentities(sprintf(__('Mailing list: %s', 'wp-mailster'),  $mList->name)).'<br/><br/>';
        $modDecision = '<hr/>'.htmlentities(__('Moderation Decision', 'wp-mailster')).':<br/>';
        $modDecision .= '<a href="'.$approveLink.'" >'.htmlentities(__('Approve message', 'wp-mailster')).'</a><br/>';
        $modDecision .= '<a href="'.$declineLink.'" >'.htmlentities(__('Reject message', 'wp-mailster')).'</a><hr/><br/>';

        if(!is_null($mail->html) && (strlen(trim($mail->html)) > 0)){
            $origMsgContent = $mail->html;
        }else{
            $origMsgContent = $mail->body;
        }

        $body = $header . $modDecision . '<br/>' . htmlentities(__('Message', 'wp-mailster')).':<br/>' . $origMsgContent . '<br/>' . $modDecision;

        $replyTo = array($mList->admin_mail, '');
        $mailer = $mailSender->getListMailTmpl($mList);

        if(is_null($mailer)){
            $log->error('getModerationRequestMailTmpl No Mailer object - exit method');
            return false;
        }

        try {
            $mailer->addReplyTo($replyTo[0], $replyTo[1]);
        } catch (Exception $e) {
            $exceptionErrorMsg = 'Exception ' . get_class($e) . ' Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
            $log->error('getModerationRequestMailTmpl addReplyTo for '.print_r($replyTo, true).' caused exception: '.$exceptionErrorMsg);
        }

        $mailer->FromName = 'WP Mailster';

        //$mailer->addCustomHeader(MstConsts::MAIL_HEADER_RETURN_PATH . ': <>'); // try to set return path to NULL
        $mailer->addCustomHeader(MstConsts::MAIL_HEADER_AUTO_SUBMITTED . ': auto-generated'); // indicate this was generated and we do not want a response

        $recipients = self::getModerationRequestRecipients($mList);
        $mailer->SingleTo = true; // one mail per recipient

        for($j=0; $j<count($recipients); $j++){
            $recip = &$recipients[$j];
            try{
                $mailer->AddAddress($recip->email, $recip->name); // add all recipients of this notify
            } catch (Exception $e) {
                $exceptionErrorMsg = 'Exception ' . get_class($e) . ' Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
                $log->error('getModerationRequestMailTmpl AddAdress for recipient '.$recip->email.' (name: '.$recip->name.') caused exception: '.$exceptionErrorMsg);
            }
        }

        $mailer->setSubject($subject);
        $mailer->IsHTML(true);
        $mailer->setBody($body);

        if($mstConfig->includeAttachmentsInModerationRequests()){
            if(intval($mail->has_attachments) > 0) {
                $log->debug('getModerationRequestMailTmpl Adding attachments');
                $mailer = $mailSender->prepareAttachments($mailer, $mail); // add attachments...
            }else{
                $log->debug('getModerationRequestMailTmpl Mail has no attachments');
            }
        }else{
            $log->debug('getModerationRequestMailTmpl Attachment adding is turned off');
        }

        return $mailer;
    }

    public static function getModerationRequestRecipients($mList){
        $recipients = array();
        $recip = new stdClass();
        $recip->email = $mList->admin_mail;
        $recip->name = '';
        $recipients[] = $recip;
        return $recipients;
    }

    public static function isEmailAddressOneOfTheModerators($email, $mList){
        $email = strtolower(trim($email));
        $modRequestRecipients = self::getModerationRequestRecipients($mList);
        foreach($modRequestRecipients AS $modRequestRecipient){
            if(strtolower(trim($modRequestRecipient->email)) === $email){
                return true;
            }
        }
        return false;
    }

    public static function approveMail($mailId, $enqueueMail = true, $mail = null, $mList = null){
        $log = MstFactory::getLogger();
        $mailUtils = MstFactory::getMailUtils();
        $mailQueue = MstFactory::getMailQueue();
        $sendEvents = MstFactory::getSendEvents();
        $listUtils = MstFactory::getMailingListUtils();
        if(is_null($mail)){
            $mail = $mailUtils->getMail($mailId);
        }
        if(is_null($mList)){
            $mList = $listUtils->getMailingList($mail->list_id);
        }
        $log->debug('ModerationUtils::approveMail - Update mail '.$mailId.' with status '.MstConsts::MAIL_FLAG_MODERATED_APPROVED.' (old status: '.$mail->moderation_status.')');

        $res = new stdClass();
        $res->already_declined = false;
        $res->already_approved = false;
        $success = false;
        if($mail->moderation_status !== MstConsts::MAIL_FLAG_MODERATED_REJECTED){
            if($mail->moderation_status == MstConsts::MAIL_FLAG_MODERATED_APPROVED){
                $res->already_approved = true;
            }
            $success = self::updateModerationStatus($mailId, MstConsts::MAIL_FLAG_MODERATED_APPROVED);

            $sendEvents->moderationApprovalDecision($mailId);

            if($enqueueMail){
                if(!$res->already_approved) {
                    $log->debug('ModerationUtils::approveMail - Enqueue mail ' . $mailId . ' in list ID ' . $mList->id);
                    $mailQueue->enqueueMail($mail, $mList);
                }else{
                    $log->debug('ModerationUtils::approveMail - Do NOT enqueue mail ' . $mailId . ' in list ID ' . $mList->id . ', because was already approved!');
                }
            }
        }else{
            $res->already_declined = true;
        }
        $res->success = $success;

        return $res;
    }

    public static function rejectMail($mailId, $mail = null){
        $log = MstFactory::getLogger();
        $mailQueue = MstFactory::getMailQueue();
        $mailUtils = MstFactory::getMailUtils();
        $sendEvents = MstFactory::getSendEvents();
        if(is_null($mail)){
            $mail = $mailUtils->getMail($mailId);
        }
        $res = new stdClass();
        $res->already_declined = false;
        $res->already_approved = false;
        $success = false;

        $log->debug('ModerationUtils::rejectMail - Update mail '.$mailId.' with status '.MstConsts::MAIL_FLAG_MODERATED_REJECTED.' (old status: '.$mail->moderation_status.')');
        if($mail->moderation_status == MstConsts::MAIL_FLAG_MODERATED_REJECTED){
            $success = true;
            $res->already_declined = true;
        }else{
            $success = self::updateModerationStatus($mailId, MstConsts::MAIL_FLAG_MODERATED_REJECTED);
            $mailQueue->removeAllRecipientsOfMailFromQueue($mailId);
            $sendEvents->moderationRejectionDecision($mailId);
        }

        $res->success = $success;

        return $res;
    }

    public static function updateModerationStatus($mailId, $newModerationStatus){
        $log = MstFactory::getLogger();
        global $wpdb;

        try {
            $result = $wpdb->update(
                $wpdb->prefix . 'mailster_mails',
                array(
                    'moderation_status' => $newModerationStatus
                ),
                array( "id" => $mailId),
                array(
                    "%d"
                ),
                array("%d")
            );
            if($result === false){
                $log->error('updateModerationStatus: Updating of moderation status failed, last error: ' . $wpdb->last_error);
                return false;
            }else{
                $log->debug('updateModerationStatus: Successfully updated moderation status of mail ID ' . $mailId);
                return true;
            }
        } catch (Exception $e) {
            $errorMsg = 'Exception ' . get_class($e) . ' Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
            $log->error('updateModerationStatus: exception: ' . $errorMsg);
        }
        return false;
    }

    public static function notifySenderOfModerationOfMail($mailId, $mail = null, $mList = null){
        $log = MstFactory::getLogger();
        $mailUtils = MstFactory::getMailUtils();
        $listUtils = MstFactory::getMailingListUtils();
        if(is_null($mail)){
            $mail = $mailUtils->getMail($mailId);
        }
        if(is_null($mList)){
            $mList = $listUtils->getMailingList($mail->list_id);
        }

        $subject = sprintf( __('Your email "%s" is moderated', 'wp-mailster'), $mail->subject);
        $body = sprintf( __('Your email "%s" sent to the mailing list %s will be reviewed by the moderator before distribution to the mailing list.'), $mail->subject, $mList->name);

        if($mList->mod_info_sender_approval && $mList->mod_info_sender_rejection){
            $body .= "\r\n\r\n".__('You will receive a notification when the moderator has reviewed your message.', 'wp-mailster');
        }

        $mailer = self::getSenderNotificationMailTemplate($subject, $body, $mail, $mList);
        try {
            $mailer->Send();  // send notification
        } catch (Exception $e) {
            $exceptionErrorMsg = 'Exception ' . get_class($e) . ' Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
            $log->error('MstModerationUtils::notifySenderOfModerationOfMail error: '.$exceptionErrorMsg);
            $log->error('Email was: '.print_r($mailer, true));
        }
        $error =  $mailer->IsError();
        if($error == true) { // send errors?
            $log->error('Sending moderation notification failed! Last error: ' . $mailer->ErrorInfo);
            $log->error('Email was: '.print_r($mailer, true));
        }else{
            $log->debug('MstModerationUtils::notifySenderOfModerationOfMail Sent notification');
        }
    }

    public static function notifySenderOfApprovedMail($mailId, $mail = null, $mList = null){
        $log = MstFactory::getLogger();
        $mailUtils = MstFactory::getMailUtils();
        $listUtils = MstFactory::getMailingListUtils();
        if(is_null($mail)){
            $mail = $mailUtils->getMail($mailId);
        }
        if(is_null($mList)){
            $mList = $listUtils->getMailingList($mail->list_id);
        }

        $subject = sprintf( __('Your email "%s" was approved', 'wp-mailster'), $mail->subject);
        $body = sprintf( __('Your email "%s" sent to the mailing list %s was approved by the moderator. It is now forwarded to the mailing list.'), $mail->subject, $mList->name);


        $mailer = self::getSenderNotificationMailTemplate($subject, $body, $mail, $mList);
        try {
            $mailer->Send();  // send notification
        } catch (Exception $e) {
            $exceptionErrorMsg = 'Exception ' . get_class($e) . ' Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
            $log->error('MstModerationUtils::notifySenderOfApprovedMail error: '.$exceptionErrorMsg);
            $log->error('Email was: '.print_r($mailer, true));
        }
        $error =  $mailer->IsError();
        if($error == true) { // send errors?
            $log->error('Sending moderation approval notification failed! Last error: ' . $mailer->ErrorInfo);
            $log->error('Email was: '.print_r($mailer, true));
        }else{
            $log->debug('MstModerationUtils::notifySenderOfApprovedMail Sent notification');
        }
    }

    public static function notifySenderOfRejectedMail($mailId, $mail = null, $mList = null){
        $log = MstFactory::getLogger();
        $mailUtils = MstFactory::getMailUtils();
        $listUtils = MstFactory::getMailingListUtils();
        if(is_null($mail)){
            $mail = $mailUtils->getMail($mailId);
        }
        if(is_null($mList)){
            $mList = $listUtils->getMailingList($mail->list_id);
        }

        $subject = sprintf( __('Your email "%s" was rejected', 'wp-mailster'), $mail->subject);
        $body = sprintf( __('Your email "%s" sent to the mailing list %s was rejected by the moderator. It is therefore not forwarded to the mailing list.'), $mail->subject, $mList->name);

        $mailer = self::getSenderNotificationMailTemplate($subject, $body, $mail, $mList);
        try {
            $mailer->Send();  // send notification
        } catch (Exception $e) {
            $exceptionErrorMsg = 'Exception ' . get_class($e) . ' Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
            $log->error('MstModerationUtils::notifySenderOfRejectedMail error: '.$exceptionErrorMsg);
            $log->error('Email was: '.print_r($mailer, true));
        }
        $error =  $mailer->IsError();
        if($error == true) { // send errors?
            $log->error('Sending moderation rejection notification failed! Last error: ' . $mailer->ErrorInfo);
            $log->error('Email was: '.print_r($mailer, true));
        }else{
            $log->debug('MstModerationUtils::notifySenderOfRejectedMail Sent notification');
        }
    }

    protected static function getSenderNotificationMailTemplate($subject, $body, $mail, $mList){
        $log = MstFactory::getLogger();
        $mailSender = MstFactory::getMailSender();
        $replyTo = array($mList->admin_mail, '');
        $mailer = $mailSender->getListMailTmpl($mList);

        if(is_null($mailer)){
            $log->error('getSenderNotificationMailTemplate No Mailer object - exit method');
            return false;
        }


        try {
            $mailer->addReplyTo($replyTo[0], $replyTo[1]);
        } catch (Exception $e) {
            $exceptionErrorMsg = 'Exception ' . get_class($e) . ' Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
            $log->error('getSenderNotificationMailTemplate addReplyTo for '.print_r($replyTo, true).' caused exception: '.$exceptionErrorMsg);
        }

        $mailer->FromName = 'WP Mailster';

        //$mailer->addCustomHeader(MstConsts::MAIL_HEADER_RETURN_PATH . ': <>'); // try to set return path to NULL
        $mailer->addCustomHeader(MstConsts::MAIL_HEADER_AUTO_SUBMITTED . ': auto-generated'); // indicate this was generated and we do not want a response

        $recipients = self::getModerationRequestRecipients($mList);
        $mailer->SingleTo = true; // one mail per recipient
        try{
            $mailer->AddAddress($mail->from_email, $mail->from_name); // add all recipients of this notify
        } catch (Exception $e) {
            $exceptionErrorMsg = 'Exception ' . get_class($e) . ' Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
            $log->error('getSenderNotificationMailTemplate AddAdress for recipient '.$mail->from_email.' (name: '.$mail->from_name.') caused exception: '.$exceptionErrorMsg);
        }

        $mailer->setSubject($subject);
        $mailer->IsHTML(false);
        $mailer->setBody($body);

        return $mailer;
    }
}