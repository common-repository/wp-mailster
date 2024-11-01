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

class MstSendEvent
{

	public static function newQueueMail($mailId, $recipCount, $emailSize){
		self::storeEvent($mailId, MstConsts::SEND_EVENT_NEW_QUEUE_MAIL, null, $recipCount, $emailSize, null, null, null);
	}
	public static function sendingRunStarted($mailId, $triggerType, $sendSessionId){
		self::storeEvent($mailId, MstConsts::SEND_EVENT_SENDING_RUN_STARTED, null, $triggerType, $sendSessionId, null, null, null);
	}
	public static function mailPrepared($mailId, $remainingRecipCount, $mailSendErrorCount){
		self::storeEvent($mailId, MstConsts::SEND_EVENT_MAIL_PREPARED, null, $remainingRecipCount, $mailSendErrorCount, null, null, null);
	}
	public static function mailPreparedForRecips($mailId, $recips, $remainingRecipCount, $mailSendErrorCount){
		self::storeEvent($mailId, MstConsts::SEND_EVENT_MAIL_PREPARED_FOR_RECIPS, $recips, $remainingRecipCount, $mailSendErrorCount, null, null, null);
	}
	public static function mailSendOk($mailId){
		self::storeEvent($mailId, MstConsts::SEND_EVENT_MAIL_SEND_OK, null, null, null, null, null, null);
	}
	public static function mailSendError($mailId, $recips, $errorMsg, $mailSendErrorCount, $maxSendErrorCount){
		self::storeEvent($mailId, MstConsts::SEND_EVENT_MAIL_SEND_ERROR, $recips, $mailSendErrorCount, $maxSendErrorCount, null, null, $errorMsg);
	}
	public static function recipientQueueRemovalDueErrors($mailId, $recips, $singleRecipErrorCount, $mailSendErrorCount, $maxSendErrorCount){
		self::storeEvent($mailId, MstConsts::SEND_EVENT_RECIP_ERROR_QUEUE_REMOVAL, $recips, $singleRecipErrorCount, $mailSendErrorCount, $maxSendErrorCount, null, null);
	}
	public static function sendingAbortedDueErrors($mailId, $recips, $mailSendErrorCount, $maxSendErrorCount, $remainingRecipCount){
		self::storeEvent($mailId, MstConsts::SEND_EVENT_SENDING_ABORTED, null, $mailSendErrorCount, $maxSendErrorCount, $remainingRecipCount, null, null);
	}
	public static function sendingFinished($mailId, $mailSendErrorCount){
		self::storeEvent($mailId, MstConsts::SEND_EVENT_SENDING_FINISHED, null, $mailSendErrorCount, null, null, null, null);
	}
	public static function sendingRunStopped($mailId, $triggerType, $sendSessionId){
		self::storeEvent($mailId, MstConsts::SEND_EVENT_SENDING_RUN_STOPPED, null, $triggerType, $sendSessionId, null, null, null);
	}
	public static function mailResend($mailId, $recipCount, $oldListId, $newListId, $userId){
		self::storeEvent($mailId, MstConsts::SEND_EVENT_MAIL_RESEND, null, $recipCount, $oldListId, $newListId, $userId, null);
	}
    public static function moderationApprovalDecision($mailId){
        self::storeEvent($mailId, MstConsts::SEND_EVENT_MODERATION_APPROVAL, null, null, null, null, null, null, null);
    }
    public static function moderationRejectionDecision($mailId){
        self::storeEvent($mailId, MstConsts::SEND_EVENT_MODERATION_REJECTION, null, null, null, null, null, null, null);
    }
    public static function newModeratedMail($mailId){
        self::storeEvent($mailId, MstConsts::SEND_EVENT_MODERATION_STARTED, null, null, null, null, null, null);
    }

	private static function storeEvent($mailId, $eventId, $recips, $int1, $int2, $int3, $int4, $msg){
		$log = MstFactory::getLogger();
        $eventStr = self::getEventName($eventId);
		$log->debug('storeEvent: Attempting to store '.$eventStr. ' send event...');
		$mailUtils	= MstFactory::getMailUtils();
		$listUtils 	= MstFactory::getMailingListUtils();
		$mstUtils	= MstFactory::getUtils();

		$mail = $mailUtils->getMail($mailId);
        $log->debug('storeEvent: Got mail info for mail '.$mailId);
		$mList	= $listUtils->getMailingList($mail->list_id);
        $log->debug('storeEvent: Got list info for list '.$mail->list_id);

		if($mList->save_send_reports <= 0){
			$log->debug('storeEvent: Do not need to store send report event, is turned off for list');
			return false; // do not save send report
		}

		if($mail->has_send_report <= 0){
			$log->debug('storeEvent: Updating has send report flag of email ' . $mailId);
			self::setHasSendReportFlag($mailId, true); // set flag that we have a send report for this email
		}

		global $wpdb;

		if(!is_null($recips) && is_array($recips)){
			$recips = $mstUtils->jsonEncode($recips);
		}
        $log->debug('storeEvent: Encoded recipients');

        $recips = is_null($recips) ? 'NULL' : '\'' . str_replace("'", "\'", $recips) . '\'';
		$msg 	= is_null($msg) ? 'NULL' : '\'' . $msg . '\'';
		$int1 	= is_null($int1) ? -1 : $int1;
		$int2 	= is_null($int2) ? -1 : $int2;
		$int3 	= is_null($int3) ? -1 : $int3;
		$int4	= is_null($int4) ? -1 : $int4;
		$result = false;
		$errorMsg = '';
        try {
            $query = 'INSERT INTO '
	            . $wpdb->prefix . 'mailster_send_reports'
	            . ' ('
	            . ' id,'
	            . ' mail_id,'
	            . ' event_time,'
	            . ' event_type,'
	            . ' recips,'
	            . ' int_val1,'
	            . ' int_val2,'
	            . ' int_val3,'
	            . ' int_val4,'
	            . ' msg'
	            . ' ) VALUES ('
	            . ' NULL,'
	            . ' \'' . $mailId . '\','
	            . ' NOW(),'
	            . ' \'' . $eventId . '\','
	            . ' ' . $recips . ','
	            . ' \'' . $int1 . '\','
	            . ' \'' . $int2 . '\','
	            . ' \'' . $int3 . '\','
	            . ' \'' . $int4 . '\','
	            . ' ' . $msg . ''
	            . ' )';
	        $result = $wpdb->query( $query );
        } catch (Exception $e) {
            $errorMsg = 'Exception ' . get_class($e) . ' Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
        }
		if(false === $result){
			$log->error('storeEvent: Failed to store send event: ' . $eventStr . ', error was: ' . $errorMsg);
		}else{
			$log->debug('storeEvent: Successfully stored send event: ' . $eventStr);
		}

		return $result;
	}

	public static function getEventName($eventId){
		$eventStr = '';
		switch($eventId){
			case MstConsts::SEND_EVENT_NEW_QUEUE_MAIL:
				$eventStr = __( 'New email', 'wp-mailster' );
				break;
			case MstConsts::SEND_EVENT_SENDING_RUN_STARTED:
				$eventStr = __( 'Sending run started', 'wp-mailster' );
				break;
			case MstConsts::SEND_EVENT_MAIL_PREPARED:
				$eventStr = __( 'Email prepared for sending', 'wp-mailster' );
				break;
			case MstConsts::SEND_EVENT_MAIL_PREPARED_FOR_RECIPS:
				$eventStr = __( 'Email prepared for recipients', 'wp-mailster' );
				break;
			case MstConsts::SEND_EVENT_MAIL_SEND_OK:
				$eventStr = __( 'Sending successful', 'wp-mailster' );
				break;
			case MstConsts::SEND_EVENT_MAIL_SEND_ERROR:
				$eventStr = __( 'Send error', 'wp-mailster' );
				break;
			case MstConsts::SEND_EVENT_RECIP_ERROR_QUEUE_REMOVAL:
				$eventStr = __( 'Removed recipient from queue due to errors', 'wp-mailster' );
				break;
			case MstConsts::SEND_EVENT_SENDING_ABORTED:
				$eventStr = __( 'Aborted sending due errors', 'wp-mailster' );
				break;
			case MstConsts::SEND_EVENT_SENDING_FINISHED:
				$eventStr = __( 'Sending finished', 'wp-mailster' );
				break;
			case MstConsts::SEND_EVENT_SENDING_RUN_STOPPED:
				$eventStr = __( 'Sending run stopped', 'wp-mailster' );
				break;
			case MstConsts::SEND_EVENT_MAIL_RESEND:
				$eventStr = __( 'Email resent', 'wp-mailster' );
				break;
            case MstConsts::SEND_EVENT_MODERATION_APPROVAL:
                $eventStr = __( 'Moderator approved', 'wp-mailster' );
                break;
            case MstConsts::SEND_EVENT_MODERATION_REJECTION:
                $eventStr = __( 'Moderator rejected', 'wp-mailster' );
                break;
            case MstConsts::SEND_EVENT_MODERATION_STARTED:
                $eventStr = __( 'Moderation started', 'wp-mailster' );
                break;
			default:
				$eventStr = __( 'Unknown send event', 'wp-mailster' );
			break;
		}
		return $eventStr;
	}

	public static function getEventExtraInformation(&$entry){
		$log = MstFactory::getLogger();
		$eventImg = '/../asset/images/';
		$mstUtils	= MstFactory::getUtils();
		$mailUtils = MstFactory::getMailUtils();

		if($entry->recips && $entry->recips != null){
			$entry->recips = $mstUtils->jsonDecode($entry->recips);
			if(!isset($entry->recips->to)){
				return;
			}
			$entry->recips->toImg = plugins_url( $eventImg.'16-user-to.png', dirname(__FILE__) );
			$entry->recips->ccImg = plugins_url( $eventImg.'16-user-cc.png', dirname(__FILE__) );
			$entry->recips->bccImg = plugins_url( $eventImg.'16-user-bcc.png', dirname(__FILE__) );
			$entry->recips->toStr = $mailUtils->getRecipInfoStr($entry->recips->to, false, false, "\r\n");
			$entry->recips->ccStr = $mailUtils->getRecipInfoStr($entry->recips->cc, false, false, "\r\n");
			$entry->recips->bccStr = $mailUtils->getRecipInfoStr($entry->recips->bcc, false, false, "\r\n");
		}
	}

	public static function getEventDescription($event){
		$mstApp = MstFactory::getApplication();
		$eventDesc = '';
		$int1 = $event->int_val1;
		$int2 = $event->int_val2;
		$int3 = $event->int_val3;
		$int4 = $event->int_val4;
		switch($event->event_type){
			case MstConsts::SEND_EVENT_NEW_QUEUE_MAIL:
				$int2 = $int2/1024;
				$eventDesc = sprintf(__( 'New mailing list email to %d recipients (size: %d kByte)' , 'wp-mailster'), $int1, $int2 );
				break;
			case MstConsts::SEND_EVENT_SENDING_RUN_STARTED:
            case MstConsts::SEND_EVENT_SENDING_RUN_STOPPED:
				$triggerType = $mstApp->getTriggerSourceName($int1);
				$eventDesc = sprintf(__( 'Sending run triggered by %s (Session ID: %d)' , 'wp-mailster'), $triggerType, $int2 );
				break;
			case MstConsts::SEND_EVENT_MAIL_PREPARED:
			case MstConsts::SEND_EVENT_MAIL_PREPARED_FOR_RECIPS:
				$eventDesc = sprintf(__( 'Email has %d remaining recipients and %d send errors yet.' , 'wp-mailster'), $int1, $int2 );
				break;
			case MstConsts::SEND_EVENT_MAIL_SEND_OK:
				$eventDesc = sprintf(__( 'Successfully sent email, no send errors' , 'wp-mailster') );
				break;
			case MstConsts::SEND_EVENT_MAIL_SEND_ERROR:
				$eventDesc = sprintf(__( 'Send error while sending. Email has %d send errors (max. allowed: %d)' , 'wp-mailster'), $int1, $int2 ) . '<br/>' . __('ERROR', 'wp-mailster')  . ': ' . $event->msg;
				break;
			case MstConsts::SEND_EVENT_RECIP_ERROR_QUEUE_REMOVAL:
				$eventDesc = sprintf(__( 'Recipient caused %d send errors. There are %d total send errors (max. allowed: %d)' , 'wp-mailster'), $int1, $int2, $int3 );
				break;
			case MstConsts::SEND_EVENT_SENDING_ABORTED:
				$eventDesc = sprintf(__( 'Email will not be sent anymore due to %d send errors (max. allowed: %d). %d remaining recipients in the email queue.' , 'wp-mailster'), $int1, $int2, $int3 );
				break;
			case MstConsts::SEND_EVENT_SENDING_FINISHED:
				$eventDesc = sprintf(__( 'Successfully completed sending/forwarding process (%d send errors while sending)' , 'wp-mailster'), $int1 );
				break;
			case MstConsts::SEND_EVENT_MAIL_RESEND:
				$user = get_user_by( "ID", $int4 );
                $userModel = MstFactory::getUserModel();
                $userObj   = $userModel->getUserData($user->ID, true);
                $userName = (property_exists($userObj, 'name') && $userObj->name && !empty($userObj->name) && (strlen(trim($userObj->name))>0)) ? $userObj->name : $user->display_name;
				$eventDesc = sprintf(__( 'User %s sent email of the list %d with resend to list %d (%d recipients added to queue)' , 'wp-mailster'), $userName, $int1, $int2, $int3);
				break;
            case MstConsts::SEND_EVENT_MODERATION_APPROVAL:
                $eventDesc = sprintf(__( 'The email was approved after being reviewed by a moderator. It is forwarded to the mailing list.' , 'wp-mailster') );
                break;
            case MstConsts::SEND_EVENT_MODERATION_REJECTION:
                $eventDesc = sprintf(__( 'The email was rejected after being reviewed by a moderator. It is therefore not forwarded to the mailing list. Remaining queue entries (if any) will be removed.' , 'wp-mailster') );
                break;
            case MstConsts::SEND_EVENT_MODERATION_STARTED:
                $eventDesc = sprintf(__( 'The email will be reviewed by the moderators' , 'wp-mailster') );
                break;
			default:
				$eventDesc = sprintf(__( 'Send event has the ID %d' , 'wp-mailster'), $event->event_type );
			break;
		}
		return $eventDesc;
	}
	public static function getEventImg($eventId){
		$eventImg = '/../asset/images/';
		switch($eventId){
			case MstConsts::SEND_EVENT_NEW_QUEUE_MAIL:
            case MstConsts::SEND_EVENT_MODERATION_STARTED:
				$eventImg .= '16-send-event-new-queue-mail.png';
				break;
			case MstConsts::SEND_EVENT_SENDING_RUN_STARTED:
				$eventImg .= '16-send-event-send-run-started.png';
				break;
			case MstConsts::SEND_EVENT_MAIL_PREPARED:
				$eventImg .= '16-send-event-mail-prepared.png';
				break;
			case MstConsts::SEND_EVENT_MAIL_PREPARED_FOR_RECIPS:
				$eventImg .= '16-send-event-recips-prepared.png';
				break;
			case MstConsts::SEND_EVENT_MAIL_SEND_OK:
            case MstConsts::SEND_EVENT_MODERATION_APPROVAL:
				$eventImg .= '16-send-event-send-ok.png';
				break;
			case MstConsts::SEND_EVENT_MAIL_SEND_ERROR:
				$eventImg .= '16-send-event-send-error.png';
				break;
			case MstConsts::SEND_EVENT_RECIP_ERROR_QUEUE_REMOVAL:
				$eventImg .= '16-send-event-recip-queue-removal.png';
				break;
			case MstConsts::SEND_EVENT_SENDING_ABORTED:
            case MstConsts::SEND_EVENT_MODERATION_REJECTION:
				$eventImg .= '16-send-event-send-aborted.png';
				break;
			case MstConsts::SEND_EVENT_SENDING_FINISHED:
				$eventImg .= '16-send-event-send-finished.png';
				break;
			case MstConsts::SEND_EVENT_SENDING_RUN_STOPPED:
				$eventImg .= '16-send-event-send-run-stopped.png';
				break;
			case MstConsts::SEND_EVENT_MAIL_RESEND:
				$eventImg .= '16-send-event-resend.png';
				break;
			default:
				$eventImg .= '';
			break;
		}
		return plugins_url( $eventImg, dirname(__FILE__) );
	}

	public static function getSendEventsForMail($mailId, $limit=0){
		global $wpdb;
		$query = 	'SELECT * '
		. ' FROM ' . $wpdb->prefix . 'mailster_send_reports'
		. ' WHERE mail_id = \'' . $mailId . '\''
		. ' ORDER BY event_time ASC, id ASC';
		if($limit > 0){
			$query .= (' LIMIT '.$limit);
		}
		$sendEvents = $wpdb->get_results( $query );
		foreach($sendEvents AS $entry){
			$entry->name = self::getEventName($entry->event_type);
			$entry->desc = self::getEventDescription($entry);
			$entry->extras = self::getEventExtraInformation($entry);
			$entry->imgPath = self::getEventImg($entry->event_type);
		}
		return $sendEvents;
	}

	public static function getMailsWithSendReportOlderThan($listId, $ageInDays){
		global $wpdb;
		$query = ' SELECT mail_id'
					 . ' FROM ' . $wpdb->prefix . 'mailster_send_reports'
					 . ' WHERE mail_id IN (SELECT id FROM ' . $wpdb->prefix . 'mailster_mails '
					 					. ' WHERE list_id = \'' . $listId . '\''
					 					. ' AND DATEDIFF(NOW(), fwd_completed_timestamp) > ' . $ageInDays.')'
					 . ' GROUP by mail_id ';
		$mails = $wpdb->get_results( $query );
		return $mails;
	}

    public static function deleteSendReportOfMails($mailIds){
        $log = MstFactory::getLogger();
        global $wpdb;

        if (count($mailIds)){
            $mailIdsStr = implode( ',', $mailIds);
            $query = 'DELETE FROM ' . $wpdb->prefix . 'mailster_send_reports'
                . ' WHERE mail_id IN ('. $mailIdsStr .')';
            try {
                $result = $wpdb->query( $query );
            } catch (Exception $e) {
                $errorMsg = 'Exception ' . get_class($e) . ' Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
                $log->error('deleteSendReportOfMails Failed to delete send reports of these mails: '.$mailIdsStr.', error: ' . $errorMsg);
                return false;
            }
        }
        return true;

    }

	public static function deleteSendReportOfMail($mailId){
        $log = MstFactory::getLogger();
		global $wpdb;
		$query = 	'DELETE FROM ' . $wpdb->prefix . 'mailster_send_reports'
		. ' WHERE mail_id = \'' . $mailId . '\'';
        try {
		    $result = $wpdb->query( $query );
        } catch (Exception $e) {
            $errorMsg = 'Exception ' . get_class($e) . ' Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
            $log->error('deleteSendReportOfMail Failed to delete send reports of mail ID: '.$mailId.', error: ' . $errorMsg);
            return false;
        }
		return $result;
	}

	public static function setHasSendReportFlag($mailId, $hasSendReport){
		$log = MstFactory::getLogger();
		$has_send_report = $hasSendReport ? '1' : '0';
		$log->debug('Updating mail ' . $mailId . ': ' . ($hasSendReport ? 'has send report' : 'does not have send report'));

		// Update Mail
		//$query = 'UPDATE ' . $wpdb->prefix . 'mailster_mails SET has_send_report = \'' . $has_send_report . '\' WHERE id=\'' . $mailId . '\'';
		global $wpdb;
		$errorMsg = '';
        try {
        	$wpdb->update(
        		$wpdb->prefix . 'mailster_mails',
        		array(
        			'has_send_report' => $has_send_report
        		),
        		array(
        			"id" => $mailId
        		),
        		array("%d"),
        		array("%d")
        	);
        } catch (Exception $e) {
            $errorMsg = 'Exception ' . get_class($e) . ' Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
            $log->error('Updating of send report flag failed, ' . $errorMsg);
        }
	}

}