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
    die('These droids are not the droids you are looking for.');
}

class MstMailRetriever
{

	function retrieveMailsOfActiveMailingLists($minDuration, $execEnd, $onlyNotCheckedSinceSeconds=false){
		$log = MstFactory::getLogger();
		$listUtils = MstFactory::getMailingListUtils();
		$mailingLists = $listUtils->getActiveMailingLists(true, true, $onlyNotCheckedSinceSeconds);
		$nrLists = count($mailingLists);
        if($onlyNotCheckedSinceSeconds){
            $log->debug('There are ' . $nrLists . ' active mailing lists not checked since '.intval($onlyNotCheckedSinceSeconds).' seconds', MstConsts::LOGENTRY_MAIL_RETRIEVE);
        }else{
		    $log->debug('There are ' . $nrLists . ' active mailing lists', MstConsts::LOGENTRY_MAIL_RETRIEVE);
        }
        $nrRetrievedMessages = 0;
		for($i = 0; $i < $nrLists; $i++) {		
			$timeLeft = $execEnd - time();
			if($timeLeft > $minDuration){
                $nrRetrievedMessages = $nrRetrievedMessages + $this->retrieveMailsOfMailingList($mailingLists[$i], $minDuration, $execEnd); // store mails in DB
				$log->debug('Time left: ' . $timeLeft . ' sec after retrieving from ' . ($i+1) . '/' . $nrLists . ' lists');
			}else{
				$log->debug('Timeout while retrieving mails of active mailing lists, Time left: ' . $timeLeft . ' sec (min. duration is ' . $minDuration.' sec)');
				break;
			}
		}
        return array($nrRetrievedMessages, $nrLists);
	}
	
	function retrieveMailsOfMailingList($mList, $minDuration, $execEnd){
		$log = MstFactory::getLogger();
		$listUtils = MstFactory::getMailingListUtils();
        $nrRetrievedMessages = 0;
		if($listUtils->lockMailingList($mList->id)){
			$log->debug('Successfully locked list ' . $mList->name . ' (id: ' . $mList->id . ')', MstConsts::LOGENTRY_MAIL_RETRIEVE);
			$mailbox = MstFactory::getMailingListMailbox();
			if($mailbox->open($mList)){
                $nrRetrievedMessages = $mailbox->retrieveAllMessages($minDuration, $execEnd);
				$mailbox->close();
			}else{
				$log->error('Mailbox Connection NOT ok:  ' . $mList->name . ' (id: ' . $mList->id . ') Errors: ' . $mailbox->getErrors(), MstConsts::LOGENTRY_MAIL_RETRIEVE);
			}
			
			if($listUtils->unlockMailingList($mList->id)){				
				$log->debug('Successfully unlocked list ' . $mList->name . ' (id: ' . $mList->id . ')', MstConsts::LOGENTRY_MAIL_RETRIEVE);
			}else{		
				$listUtils->isListLocked($mList->id) ? 		$log->error('Could not unlock list ' . $mList->name . ' (id: ' . $mList->id . ')') 
													  	: 	$log->debug('List was already unlocked');
			}
		}else{
			$log->debug('List ' . $mList->name . ' (id: ' . $mList->id . ') could not be locked!', MstConsts::LOGENTRY_MAIL_RETRIEVE);
		}
        return $nrRetrievedMessages;
	}
	
}

