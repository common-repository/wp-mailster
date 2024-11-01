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
	class MstApplication
	{
		public static function detectSystemProblems(){
			$res = new stdClass();
			$res->error = false;
			$res->warningOnly = false;
			$res->errorMsg = "";
			$res->autoFixLinkName = false;
			$res->autoFixAvailable = false;
			$res->hideSysDiagnosisLink = false;
			
			$log		= MstFactory::getLogger();
			$env 		= MstFactory::getEnvironment();
			$dbUtils 	= MstFactory::getDBUtils();
			$mstConf	= MstFactory::getConfig();
			$fileUtils	= MstFactory::getFileUtils();
            $listUtils  = MstFactory::getMailingListUtils();
			
			
			$currentVersion = get_bloginfo('version');
			$wpVersionOk	= version_compare($currentVersion,'5.3.0'); // at least WordPress 5.3.0
			$phpVersionOk 	= (floatval(phpversion()) >= 5.6); // at least PHP 5.6
			$dbCollationOk 	= $dbUtils->userTableCollationOk();
			$imapExtOk 		= $env->imapExtensionInstalled();
            $openSslInstalled = $env->openSSLExtensionInstalled();
            $charSetConvAvail = $env->charsetConversionFunctionAvailable();
            $sslNeededInLists = $listUtils->isOpenSslNeededForActiveLists();
			$logFileTooBig	= $log->isLogFile2Big($mstConf->getLogFileSizeWarningLevel()*1024*1024);
			
			if ( $wpVersionOk < 0 ) {
				$res->error = true;
				$res->errorMsg = __("WordPress version insufficient, please check the minimum system requirements", 'wp-mailster');
			}elseif(!$phpVersionOk){
				$res->error = true;
				$res->errorMsg = __("PHP version insufficient, please check the minimum system requirements", 'wp-mailster');
			}elseif(!$dbCollationOk){
				$res->error = true;
				$res->errorMsg = __("Database collation error", 'wp-mailster');
				$res->autoFixAvailable = false; // TODO set true when link below is ready
				$res->autoFixLink = null; //TODO
			}elseif(!$openSslInstalled && $sslNeededInLists){
                $res->error = true;
                $res->errorMsg = __("OpenSSL not installed, please check system requirements", 'wp-mailster');
            }elseif(!$charSetConvAvail){
                $res->error = true;
                $res->errorMsg = __("No charset conversion functionality available, please check system requirements", 'wp-mailster');
            }elseif(!$imapExtOk){
				$res->error = true;
				$res->errorMsg = __("PHP IMAP extension not installed, please check the minimum system requirements", 'wp-mailster');
			}elseif($logFileTooBig){
				$res->error = true;
				$res->warningOnly = true;
				$res->errorMsg = __("Log file is becoming very large", 'wp-mailster') . ' (' . $fileUtils->getFileSizeOfFile($log->getLogFile()) . ')';
				$res->autoFixAvailable = false; // TODO set true when link below is ready
				$res->autoFixLink = ''; // TODO
				$res->autoFixLinkName = __("Delete log file", 'wp-mailster');
			}else{
                // for testing
                //$res->error = true;
                //$res->errorMsg = __("There is a problem", 'wp-mailster');
            }
			
			return $res;
		}
		
		public static function performMaintenance($minDuration=0, $execEnd=0){
			$log 		= MstFactory::getLogger();
            $config = MstFactory::getConfig();
			$sendEvents = MstFactory::getSendEvents();
			$subscrUtils = MstFactory::getSubscribeUtils();
            /** @var MailsterModelMails $mailsModel */
            $mailsModel = MstFactory::getMailsModel();
            $mailQueue = MstFactory::getMailQueue();
			$listUtils	= MstFactory::getMailingListUtils();
            $lists = $listUtils->getAllMailingLists();
            $keepBlockedEmailsForDays = $config->getKeepBlockedEmailsForDays();
            $keepBouncedEmailsForDays = $config->getKeepBouncedEmailsForDays();

			$log->info('Performing maintenance (cleanup)...');
            $log->debug('Time left: ' . ($execEnd - time()) . ' for performing maintenance (execEnd: ' . $execEnd . ', minDuration: ' . $minDuration . ')');

            $log->debug('Maintenance Task #1: Delete old blocked (older '.$keepBlockedEmailsForDays.' days) and bounced (older '.$keepBouncedEmailsForDays.' days) mails');
            $oldBlockedMails = $mailsModel->getEmailsOlderThanDays($keepBlockedEmailsForDays, 0, true);
            $oldBouncedMails = $mailsModel->getEmailsOlderThanDays($keepBouncedEmailsForDays, 0, false, true);
            $oldBlockedMails = $objects = array_map(function($o) { return $o->id; }, $oldBlockedMails);
            $oldBouncedMails = $objects = array_map(function($o) { return $o->id; }, $oldBouncedMails);
            $log->debug('Found '.count($oldBlockedMails).' blocked mails with IDs: '.implode(',', $oldBlockedMails));
            $log->debug('Found '.count($oldBouncedMails).' bounced mails with IDs: '.implode(',', $oldBouncedMails));
            $oldBlockedAndBouncedMailIds = array_merge($oldBlockedMails, $oldBouncedMails);
            $oldBlockedAndBouncedMailIds = array_unique($oldBlockedAndBouncedMailIds);
            $oldBlockedAndBouncedMailIds = array_merge($oldBlockedAndBouncedMailIds);
            $log->debug('Combined set and will now delete '.count($oldBlockedAndBouncedMailIds).' blocked/bounced mails with IDs: '.implode(',', $oldBlockedAndBouncedMailIds));
            $mailsModel->delete($oldBlockedAndBouncedMailIds);

            if(($execEnd > 0) && (($execEnd - time()) <= $minDuration)){
                $log->debug('Timeout after deleting blocked and bounced emails, skip further maintenance tasks');
                return;
            }else{
                $log->debug('Time left: ' . ($execEnd - time()) . ' for performing maintenance (execEnd: ' . $execEnd . ', minDuration: ' . $minDuration . ')');
            }

            $log->debug('Maintenance Task #2: Delete old send reports');
			foreach($lists AS $list){
				$mails = $sendEvents->getMailsWithSendReportOlderThan($list->id, $list->save_send_reports);

                if(($execEnd > 0) && (($execEnd - time()) <= $minDuration)){
                    $log->debug('Timeout, stop working on old send reports');
                    break;
                }

				$log->debug('#Found mails w/ old ('.$list->save_send_reports.' days & older) send reports ("'.$list->name . '", ID '.$list->id.'): '.count($mails));
				if($mails){
					foreach($mails AS $mail){
						$log->debug('Deleting send report of mail '.$mail->mail_id);
						$sendEvents->deleteSendReportOfMail($mail->mail_id);
						$sendEvents->setHasSendReportFlag($mail->mail_id, false);
					}
				}
			}

            if(($execEnd > 0) && (($execEnd - time()) <= $minDuration)){
                $log->debug('Timeout after/while deleting send reports, skip further maintenance tasks');
                return;
            }else{
                $log->debug('Time left: ' . ($execEnd - time()) . ' for performing maintenance (execEnd: ' . $execEnd . ', minDuration: ' . $minDuration . ')');
            }
			
			$log->debug('Maintenance Task #3: Delete old subscription info');
			$subInfos = $subscrUtils->getSubscriptionInfoOlderThan(MstConsts::SUBSCRIPTION_INFO_DEFAULT_STORE_DURATION);
			$log->debug('Found ' . count($subInfos) . ' subscription info to delete');
			if($subInfos){
				foreach($subInfos AS $subInfo){
                    if(($execEnd > 0) && (($execEnd - time()) <= $minDuration)){
                        $log->debug('Timeout, do not work on old subscription info');
                        break;
                    }
					$log->debug('Deleting subscription info: '.$subInfo->id . ' (created: '.$subInfo->sub_date.' -> older than '.MstConsts::SUBSCRIPTION_INFO_DEFAULT_STORE_DURATION.' days)');
					$subscrUtils->deleteSubscriptionInfo($subInfo->id);
				}
			}

            if(($execEnd > 0) && (($execEnd - time()) <= $minDuration)){
                $log->debug('Timeout after/while deleting old subscription info, skip further maintenance tasks');
                return;
            }else{
                $log->debug('Time left: ' . ($execEnd - time()) . ' for performing maintenance (execEnd: ' . $execEnd . ', minDuration: ' . $minDuration . ')');
            }

            $offlineArchivingDeactivatedCr = 0;
            $log->debug('Maintenance Task #4: Do offline archiving');
            foreach($lists AS $list){
                if($list->archive_offline > 0){
                    if(($execEnd > 0) && (($execEnd - time()) <= $minDuration)){
                        $log->debug('Timeout, do not work on offline archiving');
                        break;
                    }
                    $log->debug('Offline archiving enabled for list '.$list->name . ' (ID '.$list->id.'), search for live emails older than '.$list->archive_offline.' days...');

                    $mails = $mailsModel->getEmailsOlderThanDays($list->archive_offline, $list->id);
                    $log->debug('Found ' . count($mails) . ' mails older than '.$list->archive_offline.' days');
                    if($mails){
                        $mailIds = array();
                        $log->debug('Deleting send queue entries for '.count($mails) . ' mails');
                        foreach($mails AS $mail){
                            $mailQueue->removeAllRecipientsOfMailFromQueue($mail->id);
                            $mailIds[] = $mail->id;
                        }
                        $mailsModel->moveEmailsToOfflineArchive($mailIds);
                    }
                }else{
					$offlineArchivingDeactivatedCr++;
                }
            }
            $log->debug('Offline archiving disabled for #lists: '.$offlineArchivingDeactivatedCr);

            if(($execEnd > 0) && (($execEnd - time()) <= $minDuration)){
                $log->debug('Timeout after/while doing offline archiving, skip further maintenance tasks');
                return;
            }else{
                $log->debug('Time left: ' . ($execEnd - time()) . ' for performing maintenance (execEnd: ' . $execEnd . ', minDuration: ' . $minDuration . ')');
            }

			$log->debug('Maintenance Task #5: Delete regular emails from archive that are older than the retention period of the respective list');
			$allLists = $listUtils->getAllMailingLists();
			foreach($allLists AS $list){
				if($list->id > 0 && $list->archive_retention > 0){ // for lists that don't have an indefinite retention period
					$oldMails = $mailsModel->getEmailsOlderThanDays($list->archive_retention, $list->id, false, false, true, 100); // return max. 100 blocked emails' IDs
					$oldMails = $objects = array_map(function($o) { return $o->id; }, $oldMails);
					if(count($oldMails)>0) {
						$log->debug('Clear emails of list ID ' . $list->id . ' out of retention period, found ' . count($oldMails) . ' old mails, with IDs: ' . implode(',', $oldMails));
						$mailsModel->delete($oldMails);
					}else{
						$log->debug('No emails to clear for list ID ' . $list->id);
					}

					if(($execEnd > 0) && (($execEnd - time()) <= $minDuration)){
						$log->debug('Timeout after/while deleting emails out of retention period, skip further maintenance tasks');
						return;
					}
				}
			}

			if(($execEnd > 0) && (($execEnd - time()) <= $minDuration)){
				$log->debug('Timeout after/while deleting emails out of retention period, skip further maintenance tasks');
				return;
			}else{
				$log->debug('Time left: ' . ($execEnd - time()) . ' for performing maintenance (execEnd: ' . $execEnd . ', minDuration: ' . $minDuration . ')');
			}

			$log->debug('Maintenance Task #6: Remove digests of users no longer existing');
			$digestModel = MstFactory::getDigestModel();
			$digestModel->deleteDigestsOfNonExistingUsers();

			if(($execEnd > 0) && (($execEnd - time()) <= $minDuration)){
				$log->debug('Timeout after/while deleting digests of non-existing users, skip further maintenance tasks');
				return;
			}else{
				$log->debug('Time left: ' . ($execEnd - time()) . ' for performing maintenance (execEnd: ' . $execEnd . ', minDuration: ' . $minDuration . ')');
				$log->debug('Completed maintenance tasks');
			}

		}
		

		//todelete
		public static function getInstallInformation(){
			return false;
		}

		public static function getTriggerSourceName($triggerSrc){
			switch($triggerSrc){
				case MstConsts::TRIGGER_SOURCE_PLUGIN_BACKEND:
					return __("Plugin (backend activity)", 'wp-mailster');
					break;
				case MstConsts::TRIGGER_SOURCE_PLUGIN_FRONTEND:
					return __("Plugin (frontend activity)", 'wp-mailster');
					break;
				case MstConsts::TRIGGER_SOURCE_CRONJOB_TASK_FETCH_ALL_LISTS:
					return __("Cron job (fetch-all task)", 'wp-mailster');
					break;
				case MstConsts::TRIGGER_SOURCE_CRONJOB_TASK_FETCH_LIST:
					return __("Cron job (fetch task - single list)", 'wp-mailster');
					break;
				case MstConsts::TRIGGER_SOURCE_CRONJOB_TASK_SEND:
					return __("Cron job (send task)", 'wp-mailster');
					break;
				case MstConsts::TRIGGER_SOURCE_CRONJOB_TASK_ALL:
					return __("Cron job (all task)", 'wp-mailster');
					break;
					
			}
		}
	}


