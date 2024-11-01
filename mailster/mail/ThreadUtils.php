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

class MstThreadUtils
{		
	public static function getThreadIdOfMail($mail){
		$log = MstFactory::getLogger();
		$mailUtils 	= MstFactory::getMailUtils();
		$inReplyTo 	= $mail->in_reply_to;
		$references = $mail->references_to;
		$origReferences = $mail->references_to_orig;
		$allReferences = '';
        $threadId = false;
		$foundThread = false;
		
		if(!is_null($origReferences) && (strlen(trim($origReferences))>0)){
			$references = $origReferences;
		}
		
		$log->debug('Searching for thread ID for mail ' . $mail->id . '(in list ' . $mail->listId . ')');
		
		if(!is_null($references) && (strlen(trim($references))>0)){
			$allReferences = $allReferences . ' ' . trim($references);
		}
		if(!is_null($inReplyTo) && (strlen(trim($inReplyTo))>0)){
			$allReferences = $allReferences . ' ' . trim($inReplyTo); 
		}
				
		$allReferences = trim($allReferences);
		$foundMailsterThreadReference = false;
		$threadReference = null;
		if(strlen($allReferences) > 0){
			$log->debug('All references: ' . $allReferences);
			$refArr = explode(' ', $references);
			for($i=0; $i < count($refArr); $i++){
				$foundMailsterThreadReference = false;
				$ref = $refArr[$i];
				$ref = str_replace(array('<','>'), array('',''),$ref);
				$ref = trim(strtolower($ref));
				$log->debug('Search in ' . $ref . ' for Mailster thread reference: ' .
							 MstConsts::MAIL_HEADER_MAILSTER_REFERENCE_DOMAIN);
				$pos = strpos($ref, MstConsts::MAIL_HEADER_MAILSTER_REFERENCE_DOMAIN);
				if($pos !== false){
					$foundMailsterThreadReference = true;
					$threadReference = substr($ref, 0, $pos-1);
					$log->debug('Found Mailster thread reference: ' . $ref 
								. ' -> take: ' . $threadReference);
					$foundThread = false;
					if($foundMailsterThreadReference){
						$threadId = self::getThreadIdByReferenceId($threadReference);
						if($threadId){
							$log->debug('ID of found thread: ' . $threadId);
							$foundThread = true;
							break;
						}else{
							$log->error('Could not find ID for thread reference: ' . $threadReference);
						}
					}
				}
			}
		}
							
		if(!$foundThread){
			// thread could be new or could just reference be missing... 
			$threadId = self::getThreadIdByMailArchiveSearch($mail); 
			if($threadId){
				return $threadId;
			}
			return false;			
		}
		
		return $threadId;
	}
	
	public static function hasThreadAttachments($threadId){
		$log = MstFactory::getLogger();
		global $wpdb;
		$query = 'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'mailster_attachments'
					. ' WHERE mail_id in ('
						. ' SELECT id  FROM ' . $wpdb->prefix . 'mailster_mails'
						. ' WHERE thread_id=\'' . $threadId . '\''
					. ' )'
					. ' AND disposition=\'' . MstConsts::DISPOSITION_TYPE_ATTACH . '\'';
		$count = $wpdb->get_var( $query );
		if($count > 0){
			return true;
		}
		return false;
	}
	
	public static function updateReferences($mailId, $references){
		$log = MstFactory::getLogger();
		global $wpdb;
		$log->debug('Updating mail ' . $mailId . ' with references ' . $references);
		
		//$query = 'UPDATE ' . $wpdb->prefix . 'mailster_mails SET references_to =' . $references . ' WHERE id=\'' . $mailId . '\'';		
		$errorMsg = '';
        try {
        	$result = $wpdb->update(
        		$wpdb->prefix . 'mailster_mails',
        		array( "references_to" => $references ),
        		array( "id" => $mailId ),
        		array( "%s" ),
        		array( "%d" )
        	);
        } catch (Exception $e) {
            $errorMsg = 'Exception ' . get_class($e) . ' Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
            $log->error('Updating of references failed, ' . $errorMsg);
            return false;
        }
		if(!$result){
			$log->error('Updating of references failed, ' . $errorMsg);
		}
		return $result;	
	}
	
	public static function updateMailWithThreadId($mailId, $threadId){
		$log = MstFactory::getLogger();
		$log->debug('Updating mail ' . $mailId . ' with thread ID ' . $threadId);
		
		// Update Mail
		global $wpdb;
		//$query = 'UPDATE ' . $wpdb->prefix . 'mailster_mails SET thread_id = \'' . $threadId . '\' WHERE id=\'' . $mailId . '\'';		
		$errorMsg = '';
		$result = false;
        try {
            $result = $wpdb->update(
        		$wpdb->prefix . 'mailster_mails',
        		array( "thread_id" => $threadId ),
        		array( "id" => $mailId ),
        		array( "%d" ),
        		array( "%d" )
        	);
        } catch (Exception $e) {
            $errorMsg = 'Exception ' . get_class($e) . ' Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
        }
		if( false === $result ) {
			$log->error('updateMailWithThreadId Step #1 - Updating of Thread ID failed, ' . $errorMsg . ' ' .$wpdb->last_error );
		}
		
		// Update Thread
		//$query = 'UPDATE ' . $wpdb->prefix . 'mailster_threads SET last_mail_id = \'' . $mailId . '\' WHERE id=\'' . $threadId . '\'';		
		$errorMsg = '';		
        try {
        	 $result = $wpdb->update(
        		$wpdb->prefix . 'mailster_threads',
        		array( "last_mail_id" => $mailId ),
        		array( "id" => $threadId ),
        		array( "%d" ),
        		array( "%d" )
        	);
        } catch (Exception $e) {
            $errorMsg = 'Exception ' . get_class($e) . ' Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
        }
		if( false === $result ) {
			$log->error('updateMailWithThreadId Step #2 - Updating of Thread ID failed, ' . $errorMsg. ' ' .$wpdb->last_error. ' in query '. $wpdb->last_query );
		}
		
		// Update Digest
		//$query = 'UPDATE ' . $wpdb->prefix . 'mailster_digest_queue SET thread_id = \'' . $threadId . '\' WHERE mail_id=\'' . $mailId . '\'';		
		$errorMsg = '';
        try {
            $result = $wpdb->update(
        		$wpdb->prefix . 'mailster_digest_queue',
        		array( "thread_id" => $threadId ),
        		array( "mail_id" => $mailId ),
        		array( "%d" ),
        		array( "%d" )
        	);
        } catch (Exception $e) {
            $errorMsg = 'Exception ' . get_class($e) . ' Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
        }
		if( false === $result ){
			$log->error('updateMailWithThreadId Step #3 - Updating of Thread ID failed, ' . $errorMsg. ' ' .$wpdb->last_error. ' in query '. $wpdb->last_query );
		}
		return $result;	
	}
	
	private static function searchMailArchiveForSimilarSubject($subject, $listId, $mailId2Filter){
		$log = MstFactory::getLogger();
		$log->debug('Searching for mail with subject: ' . $subject . ' (list id: ' . $listId . ')');
		global $wpdb;
		$query = 'SELECT * FROM ' . $wpdb->prefix . 'mailster_mails '
				. ' WHERE subject LIKE \'' . $subject . '\''
				. ' AND list_id =\'' . $listId . '\' AND id <> \'' . $mailId2Filter . '\'';
		$error = false;
		$errorMsg = '';
        try {
            $res = $wpdb->get_results( $query );
        } catch (Exception $e) {
            $error = true;
            $errorMsg = 'Exception ' . get_class($e) . ' Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
            $log->error('Search for similar subject failed, '.$errorMsg);
            return null;
        }
		return $res;		
	}
	
	private static function getMailWithSimilarSubject($mail){
		$log = MstFactory::getLogger();
		$subject = $mail->subject;
		$listId = $mail->listId;
		$mailId = $mail->id;
		$similarMail = null;
		$log->debug('Searching for mail ' . $mail->id . ' (list id: ' . $listId . ') mail with similar subject: ' . $subject);
		
		$res = self::searchMailArchiveForSimilarSubject($subject, $listId, $mailId); // exact same subject?
		if(count($res) > 0){
			$similarMail = $res[0];	
			$log->debug('1 - Similar Subject Found: mail ' . $similarMail->id . ' with subject: ' . $similarMail->subject);
		}
		
		if(is_null($similarMail)){
			$res = self::searchMailArchiveForSimilarSubject('%'.$subject, $listId, $mailId); // similar at the end?
			if(count($res) > 0){
				$similarMail = $res[0];	
				$log->debug('2 - Similar Subject Found: mail ' . $similarMail->id . ' with subject: ' . $similarMail->subject);
			}
		}
		
		if(is_null($similarMail)){
			$res = self::searchMailArchiveForSimilarSubject($subject.'%', $listId, $mailId); // similar at the start?
			if(count($res) > 0){
				$similarMail = $res[0];	
				$log->debug('2 - Similar Subject Found: mail ' . $similarMail->id . ' with subject: ' . $similarMail->subject);
			}
		}
		
		if(is_null($similarMail)){
			$log->debug('No mail with similar subject found');
			return false;
		}
		
		return $similarMail;		
	}
	
	private static function getMailWithMessageIdOfInReplyTo($mail){
		$log = MstFactory::getLogger();
		$msgId 	= $mail->in_reply_to;
		$listId = $mail->listId;
		
		$log->debug('Search mail with message_id ' . $msgId . ' from in_reply_to (and of list ' . $listId . ')');
		
		global $wpdb;
		$query = 'SELECT * FROM ' . $wpdb->prefix . 'mailster_mails '
				. ' WHERE list_id =\'' . $listId . '\''
				. ' AND message_id = \'' . $msgId . '\'';
		
		$error = false;
		$errorMsg = '';
        try {
            $res = $wpdb->get_row( $query );
        } catch (Exception $e) {
            $error = true;
            $errorMsg = 'Exception ' . get_class($e) . ' Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
            $log->error('Search for message id failed, '.$errorMsg);
            return false;
        }
		if($res){
			$log->debug('Found mail with message ID from in-reply-to, has mail id: ' . $res->id);
			return $res;
		}else{
			return false;
		}
	}
	
	private static function getMailWithSameInReplyTo($mail){
		$log = MstFactory::getLogger();
		$inReplyTo 	= $mail->in_reply_to;
		$listId 	= $mail->listId;
		
		$log->debug('Search mail with same in-reply-to: ' . $inReplyTo . ' (and of list ' . $listId . ')');
		
		global $wpdb;
		$query = 'SELECT * FROM ' . $wpdb->prefix . 'mailster_mails '
				. ' WHERE list_id =\'' . $listId . '\''
				. ' AND in_reply_to = \'' . $inReplyTo . '\''
				. ' AND id <> \'' . $mail->id . '\'';
		$error = false;
		$errorMsg = '';
        try {
            $res = $wpdb->get_row( $query );
        } catch (Exception $e) {
            $error = true;
            $errorMsg = 'Exception ' . get_class($e) . ' Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
            $log->error('Search for same in-reply-to failed, '.$errorMsg);
            return false;
        }
		if($res){
			$log->debug('Found mail with same in-reply-to, has mail id: ' . $res->id);
			return $res;
		}else{
			return false;
		}
	}
	
	private static function getMailWithReference($mail){
		$log = MstFactory::getLogger();
		$references = $mail->references_to;
		$listId 	= $mail->listId;
		
		$refs = explode(' ', $references);
		$log->debug('Search mail with one of references ' . print_r($refs, true) . ' (and of list ' . $listId . ')');
		
		global $wpdb;
		for($i=0; $i < count($refs); $i++){
			$curRef = trim($refs[$i]);
			$query = 'SELECT * FROM ' . $wpdb->prefix . 'mailster_mails '
					. ' WHERE list_id =\'' . $listId . '\''
					. ' AND references_to LIKE \'' . '%'.$curRef .'%\''
					. ' AND id <> \'' . $mail->id . '\'';
			$error = false;
			$errorMsg = '';
            try {
                $res = $wpdb->get_row( $query );
            } catch (Exception $e) {
                $error = true;
                $errorMsg = 'Exception ' . get_class($e) . ' Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
                $log->error('Search for same in-reply-to failed, '.$errorMsg);
                return false;
            }
			if($res){
				$log->debug('Found mail with reference to ' . $refs[$i] . ': mail id ' . $res->id);
				return $res;
			}
		}
		return false;		
	}
	
	public static function getThreadIdByMailArchiveSearch($mail){
		$log = MstFactory::getLogger();
		$mailInThread = null;
		$log->debug('Get Thread ID for mail ' . $mail->id . ' with archive search');
		
		if(!(is_null($mail->in_reply_to)) && (strlen(trim($mail->in_reply_to)) > 0)){
			$log->debug('We can search for thread by looking at the in_reply_to entry');
			$mailInThread = self::getMailWithMessageIdOfInReplyTo($mail);			
			if(!($mailInThread)){
				$mailInThread = self::getMailWithSameInReplyTo($mail);
			}
		}
				
		if(!($mailInThread)){
			if(!(is_null($mail->references_to)) && (strlen(trim($mail->references_to)) > 0)){
				$log->debug('We can search for thread by looking at the references entries');
				$mailInThread = self::getMailWithReference($mail);
			}
		}
	
		/*	
		if(!($mailInThread)){
			// TODO ENHANCE
			// not active, need to watch out to not catch unrelated mail 
			//	$mailInThread = self::getMailWithSimilarSubject($mail);
		}
		*/
		
		if(!($mailInThread)){
			$log->debug('Could not find thread id with archive search');
			return false;
		}else{
			$log->debug('Found thread id: ' . $mailInThread->thread_id);
			return $mailInThread->thread_id;	
		}
	}
	
	public static function createNewThread($mail){
		$log = MstFactory::getLogger();
		
		$refKey = self::generateReferenceId($mail);
		$log->debug('Create new thread id, use generated reference key ' . $refKey);
		
		global $wpdb;
		/*$query = 'INSERT INTO ' . $wpdb->prefix . 'mailster_threads ('
				. ' id,'
				. ' first_mail_id,'
				. ' last_mail_id,'
				. ' ref_message_id'
				. ') VALUES ('
				. ' NULL, \'' . $mail->id . '\', \'' . $mail->id . '\', ' . $wpdb->_real_escape($refKey)
				. ')'; */
		$query = 'INSERT INTO ' . $wpdb->prefix . 'mailster_threads ('
		         . ' id,'
		         . ' first_mail_id,'
		         . ' last_mail_id,'
		         . ' ref_message_id'
		         . ') VALUES ('
		         . ' NULL, \'' . $mail->id . '\', \'' . $mail->id . '\', \'' . $wpdb->_real_escape($refKey)
		         . '\')';
		$errorMsg = '';
        try {
            $result = $wpdb->query( $query );
        } catch (Exception $e) {
            $errorMsg = 'Exception ' . get_class($e) . ' Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
        }
		$threadId = $wpdb->insert_id; 
		if($threadId < 1){
			$log->error('Failed to insert new thread, '.$errorMsg);
			return false;
		}else{
			$log->debug('New thread id: ' . $threadId);
			return $threadId;
		}
	}
	
	public static function generateReferenceId($mail){
		$udate = $mail->udate_timestamp;
		$hexDate = dechex($udate);
		$rdStr = '';
		for($i=0; $i<10; $i++){
			$rdStr .= dechex(rand(0, 9));
		}
		$rdStr = $hexDate.'.'.$rdStr;		
		return substr($rdStr, 0, 16);
	}
	
	public static function getAllMessagesOfThread($threadId){		
		global $wpdb;
		$query = 'SELECT * FROM ' . $wpdb->prefix . 'mailster_mails '
				. ' WHERE thread_id =\'' . $threadId . '\'';
		$res = $wpdb->get_results( $query );
		return $res;
	}
		
	public static function getThreadByReferenceId($refKey){
		global $wpdb;
		$query = 'SELECT * FROM ' . $wpdb->prefix . 'mailster_threads WHERE ref_message_id=\'' . $refKey . '\'';
		$thread = $wpdb->get_row( $query );
		return $thread;	
	}
	
	public static function getThreadIdByReferenceId($refKey){
		$log = MstFactory::getLogger();
		$log->debug('Get thread ID by reference message id: ' . $refKey);
		$thread = self::getThreadByReferenceId($refKey);
		if($thread){
			$log->debug('Thread id found for reference message id: ' . $thread->id);
			return $thread->id;
		}
		return false;	
	}
	
	public static function getThread($threadId){
		global $wpdb;
		$query = 'SELECT * FROM ' . $wpdb->prefix . 'mailster_threads WHERE id=\'' . $threadId . '\'';
		$thread = $wpdb->get_row( $query );
		return $thread;
	}
	
	public static function getThreadSubject($threadId){
        global $wpdb;
        $log = MstFactory::getLogger();
		$thread = self::getThread($threadId);
		if($thread){
			$query = 'SELECT subject FROM ' . $wpdb->prefix . 'mailster_mails WHERE id=\'' . $thread->first_mail_id . '\'';
			$subject = $wpdb->get_var( $query );
            if($subject){
		    	return trim($subject);
            }
		}else{
            $log->warning('ThreadUtils::getThreadSubject Could not find thread of thread ID '.$threadId);
            if($threadId > 0){
                $log->debug('ThreadUtils::getThreadSubject As thread based query for first email went wrong, try to directly find email(s) for '.$threadId);
                $query = 'SELECT subject FROM ' . $wpdb->prefix . 'mailster_mails WHERE thread_id=\'' . $threadId . '\' ORDER BY id ASC LIMIT 1';
                $subject = $wpdb->get_var( $query );
                if($subject){
                    $log->info('ThreadUtils::getThreadSubject Managed to find email(s) for '.$threadId.', return subject: '.$subject);
                    return trim($subject);
                }else{
                    $log->warning('ThreadUtils::getThreadSubject Failed to find email(s) for '.$threadId);
                }
            }else{
                $log->error('ThreadUtils::getThreadSubject Thread ID is '.$threadId);
            }
        }
        $log->warning('ThreadUtils::getThreadSubject Returning "No subject found for thread" for thread ID '.$threadId);
        return '<No subject found for thread>';
	}
	
	public static function getReferenceIdByThreadId($threadId){
		$log = MstFactory::getLogger();
		$thread = self::getThread($threadId);
		if($thread){
			$log->debug('Found thread ' . $threadId . ' by thread id');
			return $thread->ref_message_id;
		}
		$log->error('Could not find thread ' . $threadId . ' by thread id');
		return false;	
	}
	
	private static function generateThreadReference($refId){
		return '<' . strtoupper($refId) . '@' . MstConsts::MAIL_HEADER_MAILSTER_REFERENCE_DOMAIN . '>';
	}
	
	public static function getThreadReference($threadId){
		$log = MstFactory::getLogger();
		$refId = self::getReferenceIdByThreadId($threadId);
		if($refId){
			return self::generateThreadReference($refId);
		}
		$log->error('Could not find thread, therefore could not generate thread reference');
		return '';	
	}
	
	public static function cleanUpReferencesString($referencesStr){
		if((!is_null($referencesStr)) && (strlen($referencesStr)>0)){
			$referencesStr = preg_replace('/\r\n|\r|\t/', " ", $referencesStr); // replace line breaks with withespaces
			$refs = explode(' ', $referencesStr); // explode at whitespaces
			$refs = array_unique($refs); // make unique
			$refs = array_values($refs); // re-index
			
			
			foreach ($refs as $i => $ref) {
				$ref = trim($ref);
				$len = strlen($ref);
				if(is_null($ref) || $len<1){
					unset($refs[$i]); // delete empty element
				}elseif($ref[0] === '<'){
					if($ref[$len-1] !== '>'){
						unset($refs[$i]); // delete incomplete element
					}
				}
			}
			$refs = array_values($refs); // re-index
			
			$referencesStr = implode(' ', $refs);
		}
		return $referencesStr;
	}
	
	public static function getAllReferencesOfThread($threadId, $referencesLimit=0, $asArray=false){
		$allRefs = array();
		$threadMails = self::getAllMessagesOfThread($threadId);
		for($i=0; $i < count($threadMails); $i++){
			$mail = $threadMails[$i];
			$references = trim($mail->references_to);
			if((!is_null($references)) && (strlen($references)>0)){
				$references = preg_replace('/\r\n|\r|\t/', " ", $references); // replace line breaks with withespaces
				$refs = explode(' ', $references); // explode at whitespaces
				$allRefs = array_merge($allRefs, $refs); // merge together
				$allRefs = array_unique($allRefs); // make unique
				$allRefs = array_values($allRefs); // re-index
			}
		}
		if($referencesLimit > 0){
			if(count($allRefs) > $referencesLimit){
				$halfOne = floor($referencesLimit/2);
				$halfTwo = ceil($referencesLimit/2);
				$lengthPart2Remove = count($allRefs)-$halfOne-$halfTwo;
				array_splice($allRefs, $halfOne, $lengthPart2Remove); // remove inner part
			}
		}
		if(!$asArray){
			return implode(' ', $allRefs);
		}
		return $allRefs;
	}
	
	public static function removeMailsterThreadReference($references){
		$log = MstFactory::getLogger();
		$log->debug('Removing mailster message references...');
		$foundMailsterThreadReference = false;
        $mstRef = null;

        if(!is_null($references) && (strlen($references) > 0)){
			$refArr = explode(' ', $references);
			for($i=0; $i < count($refArr); $i++){
				$ref = $refArr[$i];
				$ref = str_replace(array('<','>'), array('',''), $ref);
				$ref = trim(strtolower($ref));
				$pos = strpos($ref, MstConsts::MAIL_HEADER_MAILSTER_REFERENCE_DOMAIN);
				if($pos !== false){
					$foundMailsterThreadReference = true;
					$threadReference = substr($ref, 0, $pos-1);
					$mstRef = self::generateThreadReference($threadReference);
				}
			}
			if($foundMailsterThreadReference){
				$log->debug('Found Mailster Thread reference, removing ' . $mstRef);
				$references = str_replace((' ' . $mstRef), '', $references);
			}else{
				$log->debug('Did not find Mailster Thread reference, cannot remove: ' . $references);				
			}
		}
				
		return $references;
	}

    public static function shortenReferencesIfNeeded($references, $referencesLengthLimit=191){
        $log = MstFactory::getLogger();

        if(strlen($references) >= $referencesLengthLimit){
            $log->debug('shortenReferencesIfNeeded: Shorting needed!');
            $strArrayParts = explode(' ', $references);
            $reAddReferences = true;
            $newCombinedLength = 0;
            $loopCr = 0;
            $frontEntries = array();
            $backEntries = array();
            while($reAddReferences){
                if($loopCr%2 == 0){
                    $frontEntry = trim(array_shift($strArrayParts));
                    if(is_null($frontEntry) || strlen($frontEntry)==0){
                        $reAddReferences = false;
                    }else{
                        if(($newCombinedLength+strlen($frontEntry)) >= $referencesLengthLimit){
                            $reAddReferences = false;
                        }else{
                            $frontEntries[] = $frontEntry;
                            $newCombinedLength += strlen($frontEntry);
                            $newCombinedLength++; // for space needed for separator
                        }
                    }
                }else{
                    $backEntry = trim(array_pop($strArrayParts));
                    if(is_null($backEntry) || strlen($backEntry)==0){
                        $reAddReferences = false;
                    }else{
                        if(($newCombinedLength+strlen($backEntry)) >= $referencesLengthLimit){
                            $reAddReferences = false;
                        }else{
                            $backEntries[] = $backEntry;
                            $newCombinedLength += strlen($backEntry);
                            $newCombinedLength++; // for space needed for separator
                        }
                    }
                }
                $loopCr++;
            }

            $finalRefArray = $frontEntries;
            for($i=count($backEntries)-1;$i>=0;$i--){
                $finalRefArray[] = $backEntries[$i];
            }
            $newRefStr = trim(implode(' ', $finalRefArray));
            $log->debug('shortenReferencesIfNeeded before shorting: '.$references);
            $log->debug('shortenReferencesIfNeeded after shorting: '.$newRefStr);
        }else{
            $log->debug('shortenReferencesIfNeeded: Shorting not needed');
            $newRefStr = $references;
        }
        return $newRefStr;
    }
	
}
