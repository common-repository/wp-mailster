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

class MstMailUtils
{		

	public static function getMail($mailId){
		global $wpdb;
		$query = 'SELECT * FROM ' . $wpdb->prefix . 'mailster_mails WHERE id=\'' . $mailId . '\'';
		$mail = $wpdb->get_row( $query );
		return $mail;
	}
	
	public static function extractCharset($parameters){
		$charset = '';
		for($p = 0; $p < count($parameters); $p++){
			$keyValArray = $parameters[$p];
			$attr = $keyValArray['attribute'];
			$val = $keyValArray['value'];
			if(strtoupper($attr) == "CHARSET"){
				$charset = $val;
				break;
			}					
		}		
		return $charset;
	}
	
	public static function getHeaderValue($rawHeader, $field){
		$value = null;
		$log = MstFactory::getLogger();
		$headers = self::getHeaderFieldsAndContents($rawHeader);
						
		$index = self::arraySearchWithVariations($headers->headerFields, $field);
				
		if($index !== false){
			$value = $headers->fieldContents[$index];
			$log->debug('MailUtils::getHeaderValue() Found header '.$field.' -> val: '.$value);
		}else{
			$log->debug('MailUtils::getHeaderValue() Did not find header '.$field.' in: '.print_r($headers->shrinkedHeaders, true));
		}
		return $value;
	}
	
	public static function getHeaderFieldsAndContents($rawHeader){
		$headers = new stdClass();
		$shrinkedHeaders = array();
		$headerFields = array();
		$fieldContents = array();
		$patternHeaderLines = '/^(\w+(?:[-.]?\w+)*)\s*:\s*(.*\n(?:^[ \t].*\n)*)/m';
		$patternLine = '/(.*?): (.*)/';
		preg_match_all($patternHeaderLines, $rawHeader, $matches);
		for($i=0;$i<count($matches[0]);$i++){
		    $matches[0][$i] = str_replace("\t", "", $matches[0][$i]);
		    $matches[0][$i] = str_replace("\r", "", $matches[0][$i]);
		    $matches[0][$i] = str_replace("\n", " ", $matches[0][$i]);
		    preg_match($patternLine, $matches[0][$i], $lineElements);
		    $shrinkedHeaders[trim($lineElements[1])] = trim($lineElements[2]);
			$headerFields[] = trim($lineElements[1]);
			$fieldContents[] = trim($lineElements[2]);
		}
		$headers->shrinkedHeaders = $shrinkedHeaders;
		$headers->headerFields = $headerFields;
		$headers->fieldContents = $fieldContents;
		return $headers;
	}
	
	public static function getContentTypeString($type){
		switch($type){
			case MstConsts::MAIL_TYPE_PLAIN:
				return MstConsts::MAIL_TYPE_PLAIN_STR;
			case  MstConsts::MAIL_TYPE_MULTIPART:
				return MstConsts::MAIL_TYPE_MULTIPART_STR;
			case  MstConsts::MAIL_TYPE_MESSAGE:
				return MstConsts::MAIL_TYPE_MESSAGE_STR;
			case  MstConsts::MAIL_TYPE_APPLICATION:
				return MstConsts::MAIL_TYPE_APPLICATION_STR;
			case  MstConsts::MAIL_TYPE_AUDIO:
				return MstConsts::MAIL_TYPE_AUDIO_STR;
			case  MstConsts::MAIL_TYPE_IMAGE:
				return MstConsts::MAIL_TYPE_IMAGE_STR;
			case  MstConsts::MAIL_TYPE_VIDEO:
				return MstConsts::MAIL_TYPE_VIDEO_STR;
			case  MstConsts::MAIL_TYPE_OTHER:
				return MstConsts::MAIL_TYPE_OTHER_STR;
			default:
				return MstConsts::MAIL_TYPE_OTHER_STR;
		}
	}

    public static function extractContentType($contentTypeRaw){
        $log = MstFactory::getLogger();
        if($contentTypeRaw && strlen($contentTypeRaw) > 0){
            $contentTypeParts = explode(';', $contentTypeRaw);
            if(is_array($contentTypeParts)){
                $res = $contentTypeParts[0];
            }else{
                $res = $contentTypeRaw;
            }
            $res = strtolower(trim($res));
            $log->debug('extractContentType() From '.$contentTypeRaw.', extracted: '.$res);
            return $res;
        }else{
            return null;
        }
    }
	
	public static function getContentId($mailPart){
		$log = MstFactory::getLogger();
		if(!is_null($mailPart)){
			$contentId = array_key_exists('id', $mailPart) ? trim($mailPart['id']) : null;
			if(!is_null($contentId) && (strlen($contentId)>0)){
				$contentId = str_replace('<', '', $contentId);
				$contentId = str_replace('>', '', $contentId);
				$log->debug('Extracted content id: ' . $contentId);
				return $contentId;
			}
		}
		return false;
	}
	
	public static function getAttachmentParameters($parameters, $maxLength=255){
		$log = MstFactory::getLogger();
		$params = '';
		for($p = 0; $p < count($parameters); $p++){
			if(array_key_exists('attribute', $parameters[$p])){
				$attribute = (trim($parameters[$p]['attribute']));
				$value = $parameters[$p]['value'];
				$log->debug('Parameter ' . ($p+1) . ': ' . $attribute . '=' . $value);
				if(	(strtoupper($attribute) !== 'NAME')
					&& (strtoupper($attribute) !== 'FILENAME')
                    && (strtoupper($attribute) !== 'NAME*')
                    && (strtoupper($attribute) !== 'FILENAME*')){
						$attrVal = '; '.$attribute.'='.$value;
						if((strlen($params)+strlen($attrVal)) <= $maxLength){
							$log->debug('-> Add to parameters...');
							$params .= $attrVal;
						}else{
							$log->warning('Parameters too long to all be stored!');
						}
					}else{											
						$log->debug('-> Parameter of a type we do not want to safe');
					}
			}
		}
		return $params;
	}

    protected static function getAttachmentFilenameFromParameterArray($parameters){
        $log = MstFactory::getLogger();
        $attachmentFileName = MstConsts::ATTACHMENT_NO_FILENAME_FOUND;
        $fileNamePriorityArray = array('NAME'=>false, 'FILENAME'=>false, 'NAME*'=>false, 'FILENAME*'=>false);
        if( count ( $parameters ) > 0 ){
            foreach ( $parameters as $parameter ){
                if(!array_key_exists('attribute', $parameter) || !array_key_exists('value', $parameter)){
                    continue; // skip if mandatory "attribute" + "value" attributes do not exist
                }
                $keys = array_keys($fileNamePriorityArray);
                $log->debug('keys: '.print_r($keys, true));
                $log->debug('parameter: '.print_r($parameter, true));
                for($i=0; $i < count($keys); ++$i) {
                    $log->debug('check '.$i.' '.strtoupper($parameter['attribute']).' against '.$keys[$i]);
                    if ( strtoupper($parameter['attribute']) == $keys[$i] ){
                        $fileNamePriorityArray[$keys[$i]] = $parameter['value'];
                    }
                }
            }
            $log->debug('Found attach filename candidates: '.print_r($fileNamePriorityArray, true));
            foreach($fileNamePriorityArray AS $key=>$fileNameCandidate){
                if($fileNameCandidate !== false){
                    $log->debug('Winner is attribute '.$key.' with value: '.$fileNameCandidate);
                    // found the winner, do some prep-work if needed
                    if($key === 'NAME*' || $key === 'FILENAME*'){
                        $log->debug('Need to convert attach filename with rfc5987 approach');
                        $posOfStart = strpos($fileNameCandidate, "''");
                        if($posOfStart !== false){
                            $fileNameCandidate = substr($fileNameCandidate, $posOfStart+2);
                            $log->debug(' -> after cutting of start: '.$fileNameCandidate);
                            $fileNameCandidate = urldecode($fileNameCandidate);
                            $log->debug(' -> after urldecode: '.$fileNameCandidate);
                        }
                    }
                    return $fileNameCandidate;
                }
            }
        }
        $log->debug('Was not able to find a filename in given parameters array: '.print_r($parameters, true));
        return $attachmentFileName;
    }

    public static function getAttachmentFilename($mailPart){
        $log = MstFactory::getLogger();
        $log->debug('Get Attachment filename...');
        $attachmentFileName = MstConsts::ATTACHMENT_NO_FILENAME_FOUND;

        if (array_key_exists('ifparameters', $mailPart) && $mailPart['ifparameters']){
            $log->debug('Checking parameters...');
            $attachmentFileName = self::getAttachmentFilenameFromParameterArray($mailPart['parameters']);
        }

        if($attachmentFileName === MstConsts::ATTACHMENT_NO_FILENAME_FOUND){
            if (array_key_exists('ifdparameters', $mailPart) && $mailPart['ifdparameters']){
                $log->debug('Checking dparameters...');
                $attachmentFileName = self::getAttachmentFilenameFromParameterArray($mailPart['dparameters']);
            }
        }

        if($attachmentFileName === MstConsts::ATTACHMENT_NO_FILENAME_FOUND){
            $contentId = self::getContentId($mailPart);
            if ($contentId){
                $log->debug('Taking content id as filename: ' . $contentId);
                $attachmentFileName = $contentId;
            }
        }

        if($attachmentFileName === MstConsts::ATTACHMENT_NO_FILENAME_FOUND){
            // we are in trouble, no filename found
            $log->warning('Could not find filename for attachment, will return "' . MstConsts::ATTACHMENT_NO_FILENAME_FOUND . '" for: '.print_r($mailPart, true));
            $attachmentFileName = apply_filters('wpmailster_email_processing_attachment_default_filename', $attachmentFileName, $mailPart);
            $log->warning('After filter: '.$attachmentFileName);
        }
        return $attachmentFileName;
    }

    public static function ucwordsWithDelimiters($string, $delimiters = false){
        if(!$delimiters){
            $delimiters = array('-');
        }
        $string = ucwords(strtolower($string));
        foreach($delimiters as $delimiter){
            if (strpos($string, $delimiter) !== false){
                $string = implode($delimiter, array_map('ucfirst', explode($delimiter, $string)));
            }
        }
        return $string;
    }
	
	public static function arraySearchWithVariations($array, $field){
		$index = array_search($field, $array);
		
		if($index === false){
			$index = array_search(strtolower($field), $array);
		}
		if($index === false){
			$index = array_search(strtoupper($field), $array);
		}
		if($index === false){
			$index = array_search(ucfirst($field), $array);
		}
		if($index === false){
			$index = array_search(ucwords($field), $array);
		}
        if($index === false){
            $index = array_search(self::ucwordsWithDelimiters($field), $array);
        }
		
		return $index;
	}
	
	public static function getMailSize($mailStructure){
		$log = MstFactory::getLogger();
		$mailSize = false;		
		if(array_key_exists('bytes', $mailStructure)){ // check if total mail size is supplied by server
			$mailSize = $mailStructure['bytes'];
			$log->debug('Total email size in structure: ' . $mailSize);
		}
		if(!$mailSize){ // if we do not have the mail size we have to look at the parts
			$log->debug('Total email size was not in structure, adding sub-parts...');
			$mailSize = 0; // set size to zero
			$parts = $mailStructure['parts'];											 
			foreach($parts as $p){ // go through each part on the highest mail part level
				if(array_key_exists('bytes', $p)){
					$log->debug('Part has size ' . $p['bytes']);
					$mailSize += $p['bytes']; // add up all subpart sizes
				}
			}
		}
		$log->debug('Email size: ' . $mailSize . ' bytes');
		return $mailSize;
	}
	
	public static function isBouncedMail($rawHeader, $allowPrecedenceBulkMessages = false) {
		$log = MstFactory::getLogger();
		$headers = self::getHeaderFieldsAndContents($rawHeader);
		
		$autoSubmittedValue = self::getHeaderValue($rawHeader, MstConsts::MAIL_HEADER_AUTO_SUBMITTED);
		if($autoSubmittedValue && !is_null($autoSubmittedValue)){
			if(strtolower(trim($autoSubmittedValue)) === 'auto-replied' || strtolower(trim($autoSubmittedValue)) === 'auto-generated'){
				$log->debug('isBouncedMail: found ' . MstConsts::MAIL_HEADER_AUTO_SUBMITTED . ' (value: '.$autoSubmittedValue.') in header -> bounced!');
	    		return true;
			}
		}

        $precedenceValue = self::getHeaderValue($rawHeader, MstConsts::MAIL_HEADER_PRECEDENCE);
        if($precedenceValue && !is_null($precedenceValue)){
            if(strtolower(trim($precedenceValue)) === 'bulk' && !$allowPrecedenceBulkMessages){
                $log->debug('isBouncedMail: found ' . MstConsts::MAIL_HEADER_PRECEDENCE . ' (value: '.$precedenceValue.') in header -> bounced!');
                return true;
            }elseif(strtolower(trim($precedenceValue)) === 'bulk' && $allowPrecedenceBulkMessages){
                $log->debug('isBouncedMail: found ' . MstConsts::MAIL_HEADER_PRECEDENCE . ' (value: '.$precedenceValue.') in header, DO NOT regard it as bounced email because of allow_bulk_precedence setting!');
            }
        }

		$returnPath = 'Return-Path';
		$returnPathLowerCase = 'Return-path';
		$xReturnPath = 'X-Return-Path';
		$xEnvelopeFrom = 'X-Envelope-From';
	
		$index = self::arraySearchWithVariations($headers->headerFields, $returnPath);
		
		if($index === false){
			$index = self::arraySearchWithVariations($headers->headerFields, $returnPathLowerCase);
		}		
		if($index === false){
			$index = self::arraySearchWithVariations($headers->headerFields, $xReturnPath);
		}
		if($index === false){
			$index = self::arraySearchWithVariations($headers->headerFields, $xEnvelopeFrom);
		}
		
		if($index !== false){
			$fieldVal = $headers->fieldContents[$index];
			if ( trim($fieldVal) == "<>" ){
				$log->info('isBouncedMail: found <> in ' .  $headers->headerFields[$index] . ' -> bounced!');
                return true;
	    	}elseif(strpos(trim($fieldVal), "@") === FALSE){
				$log->info('isBouncedMail: found a non-at-sign containing string in ' . $headers->headerFields[$index] .   ': ' . $fieldVal . ' -> bounced!');
                return true;
	    	}elseif(strtoupper(trim($fieldVal)) === 'MAILER-DAEMON@DOMAIN' || strtoupper(trim($fieldVal)) === '<MAILER-DAEMON@DOMAIN>'){
	    		$log->info('isBouncedMail: found "MAILER-DAEMON@DOMAIN" in ' . $headers->headerFields[$index] .   ': ' . $fieldVal . ' -> bounced!');
	    		return true;
	    	}else{
	    		$log->debug('isBouncedMail: not bounced, value in ' . $headers->headerFields[$index] . ': ' . $fieldVal);
	    	}
		}else{
			$log->debug('isBouncedMail: Did not find any headers that can contain <>: ' . print_r($headers->headerFields, true));
		}
		
        return false;
	}

    public static function isBlockedEmailAddress($senderAddress){
        $log = MstFactory::getLogger();
        $mstConf = MstFactory::getConfig();
        $blockedEmailAddresses = $mstConf->getBlockedEmailAddresses();
        $senderAddressParts = self::splitEmailAddressInLocalAndDomainPart(strtolower(trim($senderAddress)));
        if(!is_null($blockedEmailAddresses) && is_array($blockedEmailAddresses)){
            foreach ($blockedEmailAddresses as $blockedEmailAddress) {
                if(strtolower(trim($senderAddress)) === strtolower(trim($blockedEmailAddress))){
                    $log->debug('isBlockedEmailAddress '.$senderAddress.' matches '.$blockedEmailAddress);
                    return true; // exact match of email address and blocked email address
                }
                $blockedAddressParts = self::splitEmailAddressInLocalAndDomainPart(strtolower(trim($blockedEmailAddress)));
                if($blockedAddressParts->domain === '*' || $blockedAddressParts->domain === ''){
                    // blocked email address is defined as a wildcard like john@* or john@
                    // therefore, we only have to match the local part
                    if(strtolower(trim($senderAddressParts->local)) === strtolower(trim($blockedAddressParts->local))){
                        $log->debug('isBlockedEmailAddress '.$senderAddress.' matches '.$blockedEmailAddress . ' (local part)');
                        return true; // local part match of email address and blocked email address
                    }
                }
                if($blockedAddressParts->local === '*' || $blockedAddressParts->local === ''){
                    if(strtolower(trim($senderAddressParts->domain)) === strtolower(trim($blockedAddressParts->domain))){ // blocked email address is defined as a wildcard like *@mail.ru or @mail.ru
                        $log->debug('isBlockedEmailAddress '.$senderAddress.' matches '.$blockedEmailAddress . ' (domain part)');
                        return true; // domain part match of email address and blocked email address
                    }
                    if($blockedAddressParts->tld === '*'){ // top-level-domain also defined as wildcard (e.g. *@example.*)
                        if(strtolower(trim($senderAddressParts->host)) === strtolower(trim($blockedAddressParts->host))){
                            $log->debug('isBlockedEmailAddress '.$senderAddress.' matches '.$blockedEmailAddress . ' (host name part)');
                            return true; // host name match of email address and blocked email address
                        }
                    }
                    if($blockedAddressParts->host === '*'){ // host name also defined as wildcard (e.g. *@*.in)
                        if(strtolower(trim($senderAddressParts->tld)) === strtolower(trim($blockedAddressParts->tld))){
                            $log->debug('isBlockedEmailAddress '.$senderAddress.' matches '.$blockedEmailAddress . ' (TLD part)');
                            return true; // top-level-domain match of email address and blocked email address
                        }
                    }
                }
            }
        }
        // no match with blocked email address
        return false;
    }


    public static function isSentFromDMARC_PolicyRejectProvider($senderAddress){
        $log = MstFactory::getLogger();
        $mstConf = MstFactory::getConfig();
        $dmarcProviders = $mstConf->getDMARC_Providers();
        $senderAddressParts = self::splitEmailAddressInLocalAndDomainPart(strtolower(trim($senderAddress)));
        $log->debug('isSentFromDMARC_PolicyRejectProvider: check '.print_r($senderAddressParts, true).' against '.print_r($dmarcProviders, true));
        if(!is_null($dmarcProviders) && is_array($dmarcProviders)){
            foreach ($dmarcProviders as $dmarcProvider) {
                if(strtolower(trim($senderAddressParts->domain)) === strtolower(trim($dmarcProvider))){
                    $log->debug('isSentFromDMARC_PolicyRejectProvider: Found among DMARC providers [domain]: '.$dmarcProvider);
                    return true; // exact match of email address domain and dmarc provider domain
                }
                $dmarcProviderParts = self::splitEmailAddressInLocalAndDomainPart(strtolower(trim($dmarcProvider)));
                $log->debug('isSentFromDMARC_PolicyRejectProvider: Compare now with provider parts: '.print_r($dmarcProviderParts, true));
                if($dmarcProviderParts->tld === '*' || $dmarcProviderParts->tld === ''){
                    // dmarc provider is defined as a wildcard like greatprovider@* or greatprovider@
                    // therefore, we only have to match the domain's second level part (=hostname)
                    if($senderAddressParts->host === $dmarcProviderParts->host){
                        $log->debug('isSentFromDMARC_PolicyRejectProvider: Found among DMARC providers [host]: '.$dmarcProviderParts->host);
                        return true; // hostname part match of email address and dmarc provider
                    }
                }
            }
        }
        $log->debug('isSentFromDMARC_PolicyRejectProvider: No match among DMARC providers');
        // no match with dmarc provider
        return false;
    }

    public static function splitEmailAddressInLocalAndDomainPart($emailAddress){
        $atParts = explode('@',$emailAddress);
        if(count($atParts) >= 2){
            $domainPart = array_pop($atParts); #remove last element.
            $localPart = implode('@',$atParts);
        }else{
            $domainPart = $emailAddress;
            $localPart = '';
        }
        $addressParts = new stdClass();
        $addressParts->local = $localPart;
        $addressParts->domain = $domainPart;
        if($addressParts->domain && strlen($addressParts->domain) >= 3){
            $domParts = explode('.',$addressParts->domain);
            if(count($domParts) >= 2){
                $tldPart = array_pop($domParts); #remove last element.
                $hostName = implode('.',$domParts);
            }else{
                $tldPart = '';
                $hostName = $addressParts->domain;
            }
        }else{
            $tldPart = '';
            $hostName = $addressParts->domain;
        }
        $addressParts->tld = $tldPart;
        $addressParts->host = $hostName;
        return $addressParts;
    }

    public static function shortenSubjectIfNeeded($subject, $maxLength=255){
        if($subject && (strlen($subject) > $maxLength)){
            $log = MstFactory::getLogger();
            $log->info('shortenSubjectIfNeeded subject has length of '.strlen($subject).', will be shortened to '.$maxLength);
            $log->info('shortenSubjectIfNeeded Subject before modification: '.$subject);
            $subject = substr($subject, 0, $maxLength);
            $log->info('shortenSubjectIfNeeded Subject after modification: '.$subject);
        }
        return $subject;
    }

	public static function checkMailWithWordsToFilter($mail){
		$containsBadWords = false;
		$body = $mail->body;
		$subject = $mail->subject;
		$mstConf = MstFactory::getConfig();
    	$badWords = $mstConf->getWordsToFilter();
    	if(!is_null($badWords) && is_array($badWords)){
			foreach ($badWords as $word) {
				$word = trim($word); // do not include white spaces before/after word(s)
			    $pos = stripos ($subject, $word); // case insensitive search for bad word in subject
			    if ($pos !== false) {
			    	$containsBadWords = true;
			    	break;
			    }
			    $pos = stripos ($body, $word); // case insensitive search for bad word in body
			    if ($pos !== false) {
			    	$containsBadWords = true;
			    	break;
			    }
			}
			unset($word);
    	}
		return $containsBadWords;
	}
	
	public static function undoSubjectModifications($mail, $mList){
		$log = MstFactory::getLogger();
		$subject = $mail->subject;
		$log->debug('Subject before modification undo: ' . $subject);
		if ($mList->clean_up_subject == 1){
			$log->debug('Cleaning up subject...');
			if( (is_null($mList->subject_prefix) == false) && (trim($mList->subject_prefix) !== "") && (strlen(trim($mList->subject_prefix)) > 0) ){
				if( (!is_null($mail->in_reply_to)) && (strlen($mail->in_reply_to)>0)){ 
					// deleting everything before prefix	
					$log->debug('Is a reply, deleting everything before mailing list subject prefix...');	
					$subjectPrefix = MstMailUtils::getSubjectPrefix($mList->subject_prefix, $mList, $mail);
					$pos = strpos($subject, $subjectPrefix);
					if($pos !== false){
						$subject = substr($subject, $pos);
						$log->debug('Found prefix, new subject: ' . $subject);	
					}else{
						$log->debug('Prefix not found, subject remains: ' . $subject);	
					}
				}
			}else{
				if( (!is_null($mail->in_reply_to)) && (strlen(trim($mail->in_reply_to))>0)){ 
					// try to guess what to delete
					$log->debug('Is a reply, guessing/deleting common reply prefixes...');	
					$toDelete = array('Re: ', 'RE: ', 'Aw: ', 'AW: ');
					$emptyRep = array('',     '',     '',     '');
					$subject = str_replace($toDelete, $emptyRep, $subject);
					$log->debug('1st result: ' . $subject);
					$toDelete = array('Re:', 'RE:', 'Aw:', 'AW:');
					$emptyRep = array('',     '',     '',     '');
					$subject = str_replace($toDelete, $emptyRep, $subject);
					$log->debug('2nd result: ' . $subject);	
				}
			}			
		}
		if( (is_null($mList->subject_prefix) == false) && (trim($mList->subject_prefix) !== "") && (strlen(trim($mList->subject_prefix)) > 0) ){
			
			$subjectPrefix = MstMailUtils::getSubjectPrefix($mList->subject_prefix, $mList, $mail);
			if(strlen($subjectPrefix) > 0 ){
				$log->debug('subject prefix to search: ' . $subjectPrefix);
				$prefixPos = strpos($subject, $subjectPrefix);
				$log->debug('Search Result: ' . $prefixPos);
				if($prefixPos !== false){
					$part1 = '';
					$part2 = substr($subject, $prefixPos+strlen($subjectPrefix));
					if($prefixPos > 0){
						$part1 = substr($subject, 0, $prefixPos);
					}
					$subject = $part1 . $part2;
					$log->debug('After deleting prefix: ' . $subject);
				}
			}
		}
		
		$log->debug('Subject after modification undo: ' . $subject);
		$mail->subject = $subject;
		return $mail;
	}

	public static function undoMailBodyModifications($mail, $mList){
        $log = MstFactory::getLogger();
        $log->debug('undoMailBodyModifications, mail before: '.print_r($mail, true));
		$mail = MstMailUtils::removeMailHeader($mail, $mList, true); // html
		$mail = MstMailUtils::removeMailHeader($mail, $mList, false); // plain
		$mail = MstMailUtils::removeMailFooter($mail, $mList, true); // html
		$mail = MstMailUtils::removeMailFooter($mail, $mList, false); // plain
        $log->debug('undoMailBodyModifications, mail after: '.print_r($mail, true));
		return $mail;
	}
	
	public static function removeMailHeader($mail, $mList, $htmlRepresentation){		
		$log = MstFactory::getLogger();
		$mstConfig = MstFactory::getConfig();
		$altTextVars = $mstConfig->isUseAlternativeTextVars();
		if($htmlRepresentation){
			$body = $mail->html;
			$log->debug('removeMailHeader() Will search for HTML header markers...');
			$headerStart = MstConsts::CUSTOM_HTML_MAIL_HEADER_START;
			$headerEnd = MstConsts::CUSTOM_HTML_MAIL_HEADER_STOP;		
			$log->debug('removeMailHeader() Searching: ' . $headerStart);
			$headerStartPos = stripos($body, $headerStart); // case insens. search
			if($headerStartPos !== false){		
				$log->debug('removeMailHeader() Header start HTML: ' . $headerStartPos . ', now searching: ' . $headerEnd);
				$headerEndPos = stripos($body, $headerEnd); // case insens. search
				$log->debug('removeMailHeader() Header end HTML: ' . $headerEndPos);
				if($headerEndPos !== false){
					$log->debug('removeMailHeader() Found HTML start and end pos, start: ' . $headerStartPos . ', end: ' . $headerEndPos);
					$part1 = substr($body, 0, $headerStartPos);
					$part2 = substr($body, ($headerEndPos+strlen($headerEnd)));
					$body = $part1 . $part2;
					$log->debug('removeMailHeader() After deleting HTML header: ' . $body);
				}else{	
					$log->debug('removeMailHeader() Could not find HTML header end marker');
				}
			}else{	
				$log->debug('removeMailHeader() Could not find HTML header start marker');
			}
		}else{
			$body = $mail->body;
			$customHeader = $mList->custom_header_plain;
			if((!is_null($customHeader)) && (strlen(trim($customHeader)) > 0) ){
				$txt_email = MstConsts::TEXT_VARIABLES_EMAIL;
				$txt_name = MstConsts::TEXT_VARIABLES_NAME;
				$txt_date = MstConsts::TEXT_VARIABLES_DATE;
				$txt_unsub_url = MstConsts::TEXT_VARIABLES_UNSUBSCRIBE_URL;
				if($altTextVars){
					$txt_email = MstConsts::TEXT_VARIABLES_EMAIL_ALT;
					$txt_name = MstConsts::TEXT_VARIABLES_NAME_ALT;
					$txt_date = MstConsts::TEXT_VARIABLES_DATE_ALT;
					$txt_unsub_url = MstConsts::TEXT_VARIABLES_UNSUBSCRIBE_URL_ALT;
				}
				$txtVars = array($txt_email, $txt_name, $txt_date, $txt_unsub_url);
				$repVals = array('', '', '', '');
				$customHeader = str_replace($txtVars, $repVals, $customHeader); // eliminate wildcards that now cannot be found
				$header = trim(MstMailUtils::replaceWildcards($customHeader, $mList, $mail));
				if(strlen($header) > 0 ){
					$log->debug('removeMailHeader() Header to search: ' . $header);
					$headerPos = strpos($body, $header);
					$log->debug('removeMailHeader() Search PLAIN Header Result: ' . $headerPos);
					if($headerPos !== false){
						$part1 = substr($body, 0, $headerPos);
						$part2 = substr($body, $headerPos+strlen($header));
						$body = $part1 . $part2;
						$log->debug('removeMailHeader() After deleting PLAIN header: ' . $body);
					}else{	
						$log->debug('removeMailHeader() Could not find PLAIN header, nothing will be removed');
					}
				}
			}
		}	
		if($htmlRepresentation){
			$mail->html = $body;
		}else{
			$mail->body = $body;
		}
		return $mail;			
	}
	
	public static function removeMailFooter($mail, $mList, $htmlRepresentation){
		$log = MstFactory::getLogger();
		$mstConfig = MstFactory::getConfig();
		$altTextVars = $mstConfig->isUseAlternativeTextVars();
		if($htmlRepresentation){
			$body = $mail->html;
			$log->debug('Will search for html footer markers...');
			$footerStart = MstConsts::CUSTOM_HTML_MAIL_FOOTER_START;
			$footerEnd = MstConsts::CUSTOM_HTML_MAIL_FOOTER_STOP;	
			$log->debug('Searching: ' . $footerStart);			
			$footerStartPos = strripos($body, $footerStart); // case insens. rev. search
			if($footerStartPos !== false){		
				$log->debug('Footer start: ' . $footerStartPos . ', now searching: ' . $footerEnd);			
				$footerEndPos = strripos($body, $footerEnd); // case insens. rev. search
				$log->debug('Footer end: ' . $footerEndPos);
				if($footerEndPos !== false){
					$log->debug('Found start and end pos, start: ' . $footerStartPos . ', end: ' . $footerEndPos);
					$part1 = substr($body, 0, $footerStartPos);
					$part2 = substr($body, ($footerEndPos+strlen($footerEnd)));
					$body = $part1 . $part2;
					$log->debug('After deleting footer: ' . $body);
				}else{	
					$log->debug('Could not find footer end marker');
				}
			}else{	
				$log->debug('Could not find footer start marker');
			}
		}else{
			$body = $mail->body;
			$customFooter = $mList->custom_footer_plain;
			if( (!is_null($customFooter)) && (strlen(trim($customFooter)) > 0) ){
				
				$txt_email = MstConsts::TEXT_VARIABLES_EMAIL;
				$txt_name = MstConsts::TEXT_VARIABLES_NAME;
				$txt_date = MstConsts::TEXT_VARIABLES_DATE;
				$txt_unsub_url = MstConsts::TEXT_VARIABLES_UNSUBSCRIBE_URL;
				if($altTextVars){
					$txt_email = MstConsts::TEXT_VARIABLES_EMAIL_ALT;
					$txt_name = MstConsts::TEXT_VARIABLES_NAME_ALT;
					$txt_date = MstConsts::TEXT_VARIABLES_DATE_ALT;
					$txt_unsub_url = MstConsts::TEXT_VARIABLES_UNSUBSCRIBE_URL_ALT;
				}
				$txtVars = array($txt_email, $txt_name, $txt_date, $txt_unsub_url); 
				$repVals = array('', '', '', '');
				$customFooter = str_replace($txtVars, $repVals, $customFooter); // eliminate wildcards that now cannot be found
				$footer = trim(MstMailUtils::replaceWildcards($customFooter, $mList, $mail));
				if(strlen($footer) > 0 ){
					$log->debug('footer to search: ' . $footer);
					$footerPos = strpos($body, $footer);
					$log->debug('Search Result: ' . $footerPos);
					if($footerPos !== false){
						$part1 = substr($body, 0, $footerPos);
						$part2 = '';
						if($footerPos+strlen($footer)<strlen($body)){						
							$part2 = substr($body, $footerPos+strlen($footer));
						}
						$body = $part1 . $part2;
						$log->debug('After deleting footer: ' . $body);
					}
				}
			}
			
			$disableFooter = ($mList->disable_mail_footer == 1 ? true : false);
			if(!$disableFooter){
				$s1 = "\x0d\x0a" . "\x0d\x0a" . "\x0d\x0a" 
					. "\x0d\x0a" . "\x0d\x0a" . __( 'powered by WP Mailster - the flexible mailing list solution for WordPress, more information: ', 'wp-mailster'). "https://wpmailster.com\x0d\x0a" ;
				$pos = strpos($body, $s1);
				if($pos===false){
					$s1 = __( 'powered by WP Mailster - the flexible mailing list solution for WordPress, more information: ', 'wp-mailster'). "https://wpmailster.com\x0d\x0a" ;
					$pos = strpos($body, $s1);
				}
				if($pos===false){
					$s1 = __( 'powered by WP Mailster - the flexible mailing list solution for WordPress, more information: ', 'wp-mailster'). "https://wpmailster.com";
					$pos = strpos($body, $s1);
				}
				if($pos!==false){
                    $log->debug('Located footer, will remove: "'.$s1.'"');
					$body = str_replace($s1, '', $body);
				}else{	
					$log->debug('Did not locate footer yet');
					$s1 = __( 'powered by WP Mailster - the flexible mailing list solution for WordPress, more information: https://wpmailster.com', 'wp-mailster');
					$len = strlen($s1);
					$cutP = floor($len/3);
					$subPos1 = strpos($s1, ' ', $cutP);
					$subPos2 = strpos($s1, ' ', $cutP*2-1);
					if(($subPos1 !== false) && ($subPos2  !== false)
						 && ($subPos1 > 0) && ($subPos2 > 0)){
						$ss1 = substr($s1, 0, $subPos1);
						$ss3 = substr($s1, $subPos2);
						$log->debug('Could split footer for searching, ss1=' . $ss1 . ', ss2=' . $ss3);
						$pos1 = strpos($body, $ss1);
						$pos3 = strpos($body, $ss3);
						if(($pos1 !== false) && ($pos3  !== false)
						 && ($pos1 > 0) && ($pos3 > 0)){
							$log->debug('Found outer parts at pos ' . $pos1 . ' and ' . $pos3 . ' (total: ' . strlen($body) . '), string to remove: '. substr($body,$pos1,$pos3+strlen($ss3)));
						 	$b1 = substr($body, 0, $pos1);
						 	$b2 = substr($body, $pos3+strlen($ss3));
						 	$body = $b1 . $b2;
						 }
					}				
				}	
				$log->debug('After deleting footer: ' . $body);
			}else{				
				$log->debug('Could not find footer, Copyright footer disabled, nothing will be removed');
			}
		}
	
		if($htmlRepresentation){
			$mail->html = $body;
		}else{
			$mail->body = $body;
		}
		return $mail;		
	}
	
	public static function replaceWildcards($text, $mList, $mail, $useHtml=false){
		$log = MstFactory::getLogger();
        /** @var MailsterModelUser $userModel */
        $userModel = MstFactory::getUserModel();
		if(is_null($text) || (strlen(trim($text)) == 0)){
			return $text;
		}
		
		$mstConfig = MstFactory::getConfig();
		$dateUtils = MstFactory::getDateUtils();
        $mstUtils = MstFactory::getUtils();
		$subscrUtils = MstFactory::getSubscribeUtils();
        $includeEmailInUnsubscribeUrl = ($mList->addressing_mode == MstConsts::ADDRESSING_MODE_TO);
		$unsubscrUrl = $subscrUtils->getUnsubscribeURL($mail, $includeEmailInUnsubscribeUrl, 'mst-unsub-email-placeholder');
		$atEmail = $mail->from_email;
		$atName = $mail->from_name;
        $atDescription = '';
        if($mail->sender_user_id > 0){
            $userRow = $userModel->getUserData($mail->sender_user_id, $mail->sender_is_core_user);
            $atDescription = $userRow->description;
			
			if(is_null($atName) || (strlen(trim($atName)) == 0)){
				$atName = $userRow->name; // fill user name from user info (useful when no original sender name was present in header)
			}
        }
	    $atDate = $dateUtils->formatDateAsConfigured($mail->receive_timestamp);
        $log->debug('replaceWildcards receive_timestamp: '.$mail->receive_timestamp .' -> formatDateAsConfigured: '.$atDate);

		$atList = $mList->name;
		$atSite = get_bloginfo('name');
		$atPostEmail = $mList->list_mail;
		$atAdminEmail = $mList->admin_mail;	
		$altTextVars = $mstConfig->isUseAlternativeTextVars();

        $atOrigTO = self::getRecipInfoStr($mstUtils->jsonDecode($mail->orig_to_recips), true, $useHtml);
        $atOrigCC = self::getRecipInfoStr($mstUtils->jsonDecode($mail->orig_cc_recips), true, $useHtml);

        if($useHtml){
            $atOrigTO = nl2br($atOrigTO);
            $atOrigCC = nl2br($atOrigCC);
        }

        $log->debug('replaceWildcards orig_to_recips: '.$mail->orig_to_recips.', orig_cc_recips: '.$mail->orig_cc_recips.', $atOrigTO: '.$atOrigTO.', atOrigCC: '.$atOrigCC);

        $txt_email = MstConsts::TEXT_VARIABLES_EMAIL;
		$txt_name = MstConsts::TEXT_VARIABLES_NAME;
        $txt_description = MstConsts::TEXT_VARIABLES_DESCRIPTION;
		$txt_date = MstConsts::TEXT_VARIABLES_DATE;
		$txt_list = MstConsts::TEXT_VARIABLES_LIST;
		$txt_site = MstConsts::TEXT_VARIABLES_SITE;
		$txt_unsub_url = MstConsts::TEXT_VARIABLES_UNSUBSCRIBE_URL;
		$txt_post_email = MstConsts::TEXT_VARIABLES_POST_EMAIL;
		$txt_admin_email = MstConsts::TEXT_VARIABLES_ADMIN_EMAIL;
        $txt_orig_to = MstConsts::TEXT_VARIABLES_ORIGINAL_RECIPIENTS_TO;
        $txt_orig_cc = MstConsts::TEXT_VARIABLES_ORIGINAL_RECIPIENTS_CC;

		if($altTextVars){
			$txt_email = MstConsts::TEXT_VARIABLES_EMAIL_ALT;
			$txt_name = MstConsts::TEXT_VARIABLES_NAME_ALT;
            $txt_description = MstConsts::TEXT_VARIABLES_DESCRIPTION_ALT;
			$txt_date = MstConsts::TEXT_VARIABLES_DATE_ALT;
			$txt_list = MstConsts::TEXT_VARIABLES_LIST_ALT;
			$txt_site = MstConsts::TEXT_VARIABLES_SITE_ALT;
			$txt_unsub_url = MstConsts::TEXT_VARIABLES_UNSUBSCRIBE_URL_ALT;
			$txt_post_email = MstConsts::TEXT_VARIABLES_POST_EMAIL_ALT;
			$txt_admin_email = MstConsts::TEXT_VARIABLES_ADMIN_EMAIL_ALT;
            $txt_orig_to = MstConsts::TEXT_VARIABLES_ORIGINAL_RECIPIENTS_TO_ALT;
            $txt_orig_cc = MstConsts::TEXT_VARIABLES_ORIGINAL_RECIPIENTS_CC_ALT;
		}

		$txtVars = array($txt_email, $txt_name, $txt_description, $txt_date, $txt_list, $txt_site, $txt_post_email, $txt_admin_email, $txt_orig_to, $txt_orig_cc);
		$repVals = array($atEmail, $atName, $atDescription, $atDate, $atList, $atSite, $atPostEmail, $atAdminEmail, $atOrigTO, $atOrigCC);
		
		if($unsubscrUrl){
            // 1st Special case: we deal with text editors that put the site's URL automatically in front of the unsubscribe placeholder (= a mess)
            $editorMess = home_url().$txt_unsub_url;
            $text = str_replace($editorMess, $unsubscrUrl, $text);

            // 2nd Special case: we deal with text editors that put http:// before our unsubscribe placeholder (= a mess)
            $editorMess = 'http://'.$txt_unsub_url;
            $text = str_replace($editorMess, $unsubscrUrl, $text);

            // 3rd Special case: we deal with text editors that put https:// before our unsubscribe placeholder (= a mess)
            $editorMess = 'https://'.$txt_unsub_url;
            $text = str_replace($editorMess, $unsubscrUrl, $text);

            // Should this not apply or should the "regular" form also be around, then do also replacements with that
			$txtVars[] = $txt_unsub_url;
			$repVals[] = $unsubscrUrl;

		}

        $text = str_replace($txtVars, $repVals, $text);
		return $text;
	}
	
	public static function replaceWildcardsThatAreMailIndependent($text, $mList){	
		$log = MstFactory::getLogger();
		if(is_null($text) || (strlen(trim($text)) == 0)){
			return $text;
		}
		
		$mstConfig = MstFactory::getConfig();
		
		$atList = $mList->name;
		$atSite = home_url();
		$atPostEmail = $mList->list_mail;
		$atAdminEmail = $mList->admin_mail;
		$altTextVars = $mstConfig->isUseAlternativeTextVars();
		
		$txt_list = MstConsts::TEXT_VARIABLES_LIST;
		$txt_site = MstConsts::TEXT_VARIABLES_SITE;
		$txt_post_email = MstConsts::TEXT_VARIABLES_POST_EMAIL;
		$txt_admin_email = MstConsts::TEXT_VARIABLES_ADMIN_EMAIL;
		if($altTextVars){
			$txt_list = MstConsts::TEXT_VARIABLES_LIST_ALT;
			$txt_site = MstConsts::TEXT_VARIABLES_SITE_ALT;
			$txt_post_email = MstConsts::TEXT_VARIABLES_POST_EMAIL_ALT;
			$txt_admin_email = MstConsts::TEXT_VARIABLES_ADMIN_EMAIL_ALT;
		}
		$txtVars = array($txt_list, $txt_site, $txt_post_email, $txt_admin_email);
		$repVals = array($atList, $atSite, $atPostEmail, $atAdminEmail);
		
		$newText = str_replace($txtVars, $repVals, $text);
		return $newText;
	}
	
	public static function replaceRecipientWildcards($text, $mList, $recipientArr){	
		$log = MstFactory::getLogger();
		if(is_null($text) || (strlen(trim($text)) == 0)){
			return $text;
		}
		$newText = $text;
		
		$recip = $recipientArr[0];
		$recipName = $recip->name;
		$recipEmail = $recip->email;
		
		$mstConfig = MstFactory::getConfig();
		$altTextVars = $mstConfig->isUseAlternativeTextVars();
		
		$txt_recip_email = MstConsts::TEXT_VARIABLES_RECIPIENT_EMAIL;
		$txt_recip_name = MstConsts::TEXT_VARIABLES_RECIPIENT_NAME;
		if($altTextVars){
			$txt_recip_email = MstConsts::TEXT_VARIABLES_RECIPIENT_EMAIL_ALT;
			$txt_recip_name = MstConsts::TEXT_VARIABLES_RECIPIENT_NAME_ALT;
		}
		$txtVars = array($txt_recip_name, $txt_recip_email, 'mst-unsub-email-placeholder');
		$repVals = array($recipName, $recipEmail, urlencode($recipEmail));
		$newText = str_replace($txtVars, $repVals, $text);
		
		return $newText;
	}
	
	public static function getSubjectPrefix($subjectPrefixWithWildcards, $mList, $mail){		
		$mstConfig = MstFactory::getConfig();
		$altTextVars = $mstConfig->isUseAlternativeTextVars();		
		$txt_email = MstConsts::TEXT_VARIABLES_EMAIL;
		$txt_name = MstConsts::TEXT_VARIABLES_NAME;
		$txt_date = MstConsts::TEXT_VARIABLES_DATE;
		$txt_unsub_url = MstConsts::TEXT_VARIABLES_UNSUBSCRIBE_URL;
		if($altTextVars){
			$txt_email = MstConsts::TEXT_VARIABLES_EMAIL_ALT;
			$txt_name = MstConsts::TEXT_VARIABLES_NAME_ALT;
			$txt_date = MstConsts::TEXT_VARIABLES_DATE_ALT;
			$txt_unsub_url = MstConsts::TEXT_VARIABLES_UNSUBSCRIBE_URL_ALT;
		}
		$txtVars = array($txt_email, $txt_name, $txt_date, $txt_unsub_url);
		$repVals = array('', '', '', '');
		$customPrefix = str_replace($txtVars, $repVals, $subjectPrefixWithWildcards); // eliminate wildcards that now cannot be found
		$subjectPrefix = trim(MstMailUtils::replaceWildcards($customPrefix, $mList, $mail));
		return $subjectPrefix;
	}
	
	public static function getReplyToArray($listObj, $senderMail, $senderName){
		$replyTo = null;
		if($listObj->reply_to_sender == 0){ // reply to list
			$replyTo = array($listObj->list_mail, $listObj->name);
		}elseif($listObj->reply_to_sender == 1){ // reply to sender (and optional to list with reply-to-all for CC address...)
			$replyTo = array($senderMail, $senderName);
		}elseif($listObj->reply_to_sender == 2){ // no explicit reply-to header
			$replyTo = array(); // don't set reply to!
		}else{ // reply to list (default)
			$replyTo = array($listObj->list_mail, $listObj->name);
		}
		return $replyTo;
	}
	
	public static function addRemoveConvertBodyParts($mail, $mList){
		$log = MstFactory::getLogger();
		$log->debug('MstMailUtils::addRemoveConvertBodyParts');
		if($mList->mail_format_conv == MstConsts::MAIL_FORMAT_CONVERT_HTML){
			// we want to have a HTML email
			$log->debug('addRemoveConvertBodyParts: MAIL_FORMAT_CONVERT_HTML');
			if(is_null($mail->html) || strlen(trim($mail->html)) == 0){
				$log->debug('addRemoveConvertBodyParts: Original HTML empty, convert plain text to HTML version');
				$mail->html = self::getHTMLVersionOfPlaintextBody($mail->body);
			}
			if($mList->mail_format_altbody == MstConsts::MAIL_FORMAT_ALTBODY_YES){ // include alt. body?
				$log->debug('addRemoveConvertBodyParts: Include alt-body');
				if(is_null($mail->body) || strlen(trim($mail->body)) == 0){ // plain text version there?
					$log->debug('addRemoveConvertBodyParts: Plain text empty, convert HTML version for alt-body');
					$mail->body = self::getPlainTextVersionOfHTMLBody($mail->html); // generate plain text version
				}
			}else{ // do not include alt. body
				$log->debug('addRemoveConvertBodyParts: Do NOT Include alt-body');
				$mail->body = null; // remove plain text part
			}
			$mail->html = self::wellFormatHTMLBody($mail->html);
		}elseif($mList->mail_format_conv == MstConsts::MAIL_FORMAT_CONVERT_PLAIN){
			// we want to have a plain text email
			$log->debug('addRemoveConvertBodyParts: MAIL_FORMAT_CONVERT_PLAIN');
			if(is_null($mail->body) || strlen(trim($mail->body)) == 0){
				$log->debug('addRemoveConvertBodyParts: Original plain text empty, convert HTML version');
				$mail->body = self::getPlainTextVersionOfHTMLBody($mail->html);
			}
			$mail->html = null; // remove html part
		}elseif($mList->mail_format_conv == MstConsts::MAIL_FORMAT_CONVERT_NONE){
			// take the parts as given, only change: when html only, then include plain text version if wanted
			$log->debug('addRemoveConvertBodyParts: MAIL_FORMAT_CONVERT_NONE (take the parts as given)');
			if(!is_null($mail->html) && strlen(trim($mail->html)) > 0){ // is html mail
				$log->debug('addRemoveConvertBodyParts: Is HTML mail');
				if(is_null($mail->body) || strlen(trim($mail->body)) == 0){ // no plain text version
					if($mList->mail_format_altbody == MstConsts::MAIL_FORMAT_ALTBODY_YES){ // plain text version needed
						$log->debug('addRemoveConvertBodyParts: Use converted HTML as plain text for alt-body');
						$mail->body = self::getPlainTextVersionOfHTMLBody($mail->html); // generate plain text version
					}else{
						$log->debug('addRemoveConvertBodyParts: No need to include altbody for this HTML email');
					}
				}
				$mail->html = self::wellFormatHTMLBody($mail->html);
			}else{
				$log->debug('addRemoveConvertBodyParts: No HTML email, we do not have to build or remove anything');
			}
		}
		
		return $mail;
	}
	
	public static function wellFormatHTMLBody($html){
		$log = MstFactory::getLogger();
		
		$startOfHtml = strpos(strtolower($html), strtolower('<html')); // find first occurence of opening html tag
		$startOfBody = strpos(strtolower($html), strtolower('<body')); // find first occurence of opening body tag
		$endOfHtml = strrpos(strtolower($html), strtolower('</html>')); // find last occurence of closing html tag
		$endOfBody = strrpos(strtolower($html), strtolower('</body>')); // find last occurence of closing body tag
		
		if(($startOfHtml === false) && ($startOfBody === false) && ($endOfHtml === false) && ($endOfBody === false)){
			$log->debug('wellFormatHTMLBody: does not contain HTML and BODY wrap, add those');
			$html = '<html><head></head><body>'.$html.'</body></html>';
			$log->debug('wellFormatHTMLBody: result: '.$html);
		}elseif(($startOfBody === false) && ($endOfBody === false)){
			$log->debug('wellFormatHTMLBody: does only contain no BODY wrap, add that');
			$startOfHtml = strpos(strtolower($html), strtolower('>'), $startOfHtml) + 1;
			$insertPos = $startOfHtml;			
			$htmlBeforeBodyBegin = substr($html, 0, $insertPos);
			$htmlAfterBodyBegin = substr($html, $insertPos);
			$html = $htmlBeforeBodyBegin . '<head></head><body>'.$htmlAfterBodyBegin;

			$endOfHtml = strrpos(strtolower($html), strtolower('</html>')); // find last occurence of closing html tag
			$insertPos = $endOfHtml;
			$htmlBeforeBodyEnd = substr($html, 0, $insertPos);
			$htmlAfterBodyEnd = substr($html, $insertPos);			
			$html = $htmlBeforeBodyEnd . '</body>'.$htmlAfterBodyEnd;
			$log->debug('wellFormatHTMLBody: result: '.$html);			
		}else{
			$log->debug('wellFormatHTMLBody: nothing to do here');
		}
				
		return $html;
	}
	
	public static function getPlainTextVersionOfHTMLBody($html){
		$log = MstFactory::getLogger();
		$log->debug('getPlainTextVersionOfHTMLBody - Convert: '.$html);
		$plain = $html;
		$ok = MstFactory::loadLibrary(MstConsts::LIB_HTML2TEXT);
		if($ok){
			$log->debug('getPlainTextVersionOfHTMLBody: Library loaded');
            $h2t = new MstHtml2Text($html);
            $plain = $h2t->getText();
		}
		$log->debug('getPlainTextVersionOfHTMLBody - Result: '.$plain);
		return $plain;
	}
	
	public static function getHTMLVersionOfPlaintextBody($plain){
		$html = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">'
				 . '<html>'
					 . '<head>'
					 	. '<meta http-equiv="content-type" content="text/html; charset=utf-8">'
					 . '</head>'
					 . '<body bgcolor="#ffffff" text="#000000">'
					 	. nl2br($plain)
					 . '</body>'
				 . '</html>';
		return $html;
  
	}
	
	public static function getListIDMailHeader($mList){
		$site = home_url();
		$listIdHeader = MstConsts::MAIL_HEADER_LIST_ID . ': ' . $mList->name . ' at ' .  $site;
        $domainUrl = parse_url($site, PHP_URL_HOST);
		$listIdHeader .= ' <list' . $mList->id . '.' . $domainUrl . '>';
		return $listIdHeader;      
	}

	private static function appendMailHeader($mList, $body, $isHtml=false){
		$log = MstFactory::getLogger();
		if(!is_null($body)){
			if($isHtml){
				if(!is_null($mList->custom_header_html) && strlen($mList->custom_header_html) > 0){
                    $log->debug('HTML Header is:');
                    $log->debug($mList->custom_header_html);
					$body = MstConsts::CUSTOM_HTML_MAIL_HEADER_START
								. $mList->custom_header_html
								. nl2br("\x0d\x0a") 
								. MstConsts::CUSTOM_HTML_MAIL_HEADER_STOP 
								. $body;
				}else{
					$log->debug('HTML Header empty, do not add anything');
				}
			}else{
				if(!is_null($mList->custom_header_plain) && strlen($mList->custom_header_plain) > 0){
                    $log->debug('Plain Header is:');
                    $log->debug($mList->custom_header_plain);
					$body = $mList->custom_header_plain . "\x0d\x0a" . $body;
				}else{
					$log->debug('Plain header empty, do not add anything');
				}
			}
		}
		return $body;
	}

	private static function appendMailFooter($mList, $body, $isHtml=false){
		$log = MstFactory::getLogger();
		$disableFooter = ($mList->disable_mail_footer == 1 ? true : false);

		if(!is_null($body)){
			if($isHtml){
				if(!is_null($mList->custom_footer_html) && strlen($mList->custom_footer_html) > 0){
                    $log->debug('HTML Footer is:');
                    $log->debug($mList->custom_footer_html);
					$body .= (MstConsts::CUSTOM_HTML_MAIL_FOOTER_START . nl2br("\x0d\x0a"). $mList->custom_footer_html);
				}
			}else{
				if(!is_null($mList->custom_footer_plain) && strlen($mList->custom_footer_plain) > 0){
                    $log->debug('Plain Footer is:');
                    $log->debug($mList->custom_footer_plain);
					$body .= ("\x0d\x0a". $mList->custom_footer_plain);
				}
			}
			
			if(!MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_D_FOOTER) || !$disableFooter){
				$body .= ($isHtml ? nl2br("\x0d\x0a" . "\x0d\x0a" . "\x0d\x0a" 
						. "\x0d\x0a" . "\x0d\x0a") : ("\x0d\x0a" . "\x0d\x0a" . "\x0d\x0a" 
						. "\x0d\x0a" . "\x0d\x0a")) . __( 'powered by WP Mailster - the flexible mailing list solution for WordPress, more information: ', 'wp-mailster') . "https://wpmailster.com"
						. ($isHtml ? nl2br("\x0d\x0a") : ("\x0d\x0a"));
			}
			
			if($isHtml){
				if(!is_null($mList->custom_footer_html) && strlen($mList->custom_footer_html) > 0){
					$body .= (MstConsts::CUSTOM_HTML_MAIL_FOOTER_STOP);
				}
			}
		}
		
		return $body;
	}
	
	public static function modifyMailContent($mList, $mail) {	
		$log = MstFactory::getLogger();
		$log->debug('MstMailUtils::modifyMailContent');
		
		$body = $mail->body;
		$log->debug('MstMailUtils::modifyMailContent -> plain body before adding header/footer: '.$body);
		$body = self::appendMailHeader($mList, $body); // add header in plain text part
		$body = self::appendMailFooter($mList, $body); // add footer in plain text part
		$log->debug('MstMailUtils::modifyMailContent -> plain body after adding header/footer: '.$body);
		
		$html = trim($mail->html);
		if((!is_null($html)) && (strlen($html)>0)){ // has mail HTML part?
			$log->debug('MstMailUtils::modifyMailContent -> has HTML part');
			$log->debug('MstMailUtils::modifyMailContent -> html before adding header/footer: '.$html);
			
			// add header in html part
			$startOfHtml = strpos(strtolower($html), strtolower('<html')); // find first occurence of opening html tag
			if($startOfHtml !== false){
				$startOfHtml = strpos(strtolower($html), strtolower('>'), $startOfHtml) + 1;
				$startOfBody = strpos(strtolower($html), strtolower('<body')); // find first occurence of opening body tag
				if($startOfBody !== false){
					$startOfBody = strpos(strtolower($html), strtolower('>'), $startOfBody) + 1;
					$insertPos = max($startOfBody, $startOfHtml);
				}else{
					$insertPos = $startOfHtml;
				}
				$htmlBeforeHeader = substr($html, 0, $insertPos);
				$htmlAfterHeader = substr($html, $insertPos);
				$htmlAfterHeader = self::appendMailHeader($mList, $htmlAfterHeader, true);
				$html = $htmlBeforeHeader . $htmlAfterHeader;
			}			
						
			// add footer in html part
			$endOfHtml = strrpos(strtolower($html), strtolower('</html>')); // find last occurence of closing html tag
			if($endOfHtml){
				$endOfBody = strrpos(strtolower($html), strtolower('</body>')); // find last occurence of closing body tag
				if($endOfBody){
					$insertPos = min($endOfBody, $endOfHtml);
				}else{
					$insertPos = $endOfHtml;
				}
				
				$htmlBeforeFooter = substr($html, 0, $insertPos);
				$htmlAfterFooter = substr($html, $insertPos);
				$htmlBeforeFooter = self::appendMailFooter($mList, $htmlBeforeFooter, true);
				$html = $htmlBeforeFooter . $htmlAfterFooter;
			}
			
			$log->debug('MstMailUtils::modifyMailContent -> html after adding header/footer: '.$html);
		}

		$log->debug('MstMailUtils::modifyMailContent now replacing wildcards...');
		
		$mail->body 	= self::replaceWildcards($body, $mList, $mail, false);
		$mail->html 	= self::replaceWildcards($html, $mList, $mail, true);
		$mail->subject 	= self::modifyMailSubject($mail, $mList);
		
		$log->debug('MstMailUtils::modifyMailContent plain after replacing wildcards result: '.$mail->body);
		$log->debug('MstMailUtils::modifyMailContent html after replacing wildcards result: '.$mail->html);
		$log->debug('MstMailUtils::modifyMailContent subject after replacing wildcards result: '.$mail->subject);
		
		return $mail;
	}
	
	public static function modifyMailSubject($mail, $mList){
		$subject = $mList->subject_prefix . $mail->subject;
		return self::replaceWildcards($subject, $mList, $mail);
	}
	
	public static function modifyMailSubjectWithMailIndependentVars($mList){
		$subject = $mList->subject_prefix;
		return self::replaceWildcardsThatAreMailIndependent($subject, $mList);
	}

    public static function convertRelativeToAbsoluteURL($url){
        if (parse_url($url, PHP_URL_SCHEME) != '') return $url; // return if already absolute URL
        $envUtils = MstFactory::getEnvironment();
        $siteBaseUrl = home_url ();
        if($url && strlen($url)){
            if(substr($url, 0, strlen($siteBaseUrl)) !== $siteBaseUrl){
                if($url[0] === '/'){
                    $url = substr($url, 1);
                }
                $url = $siteBaseUrl.$url;
            }
        }
        return $url;
    }

    public static function getRecipInfoStr($recips, $separateWithCommaInsteadOfNewline=false, $useHtml=true, $overWriteNewLineString=false){
        $recipStr = '';
        if(is_array($recips) && count($recips) > 0){
            for($i=0; $i < count($recips); $i++){
                if($recips[$i] && property_exists($recips[$i], 'email')){
                    $email = $recips[$i]->email;
                    if($useHtml){
                        $email = '<a href="mailto:'.$recips[$i]->email.'">'.$email.'</a>';
                    }
                    $recipStr .=  ($recips[$i]->name ?  ($recips[$i]->name.' (') : ''). '' . $email . '' . ($recips[$i]->name ?  ')' : '');
                    if($separateWithCommaInsteadOfNewline){
                        $recipStr .= ", ";
                    }else{
                        if($overWriteNewLineString){
                            $recipStr .= $overWriteNewLineString;
                        }else {
                            if ($useHtml) {
                                $recipStr .= "<br/>\r\n";
                            } else {
                                $recipStr .= "\r\n";
                            }
                        }
                    }
                }
            }
        }elseif(is_object($recips)){
            if(property_exists($recips, 'email')){
                $email = $recips->email;
                if($useHtml) {
                    $email = '<a href="mailto:' . $recips->email . '">' . $email . '</a>';
                }
                $recipStr .=  ($recips->name ?  ($recips->name.' (') : ''). '' . $email . '' . ($recips->name ?  ')' : '');
                if($separateWithCommaInsteadOfNewline){
                    $recipStr .= ", ";
                }else{
                    if($overWriteNewLineString){
                        $recipStr .= $overWriteNewLineString;
                    }else {
                        if ($useHtml) {
                            $recipStr .= "<br/>\r\n";
                        } else {
                            $recipStr .= "\r\n";
                        }
                    }
                }
            }
        }
        $recipStr = trim($recipStr);
        $recipStr = rtrim($recipStr, ',');
        return $recipStr;
    }

    public static function convertRelativeToAbsoluteSiteURLsInHTMLText($text){
        // Original PHP code by Chirp Internet: www.chirp.com.au
        // Please acknowledge use of this code by including this header.
        $regexp = "/<a\s[^>]*href=(\"??)([^\" >]*?)\\1[^>]*>(.*)<\/a>/siU";
        $callback = function( $matches ){
            $search = $matches[2];
            $replace = MstMailUtils::convertRelativeToAbsoluteURL($matches[2]);
            $subject = $matches[0];
            return str_replace($search, $replace, $subject);
        };
        return preg_replace_callback($regexp, $callback, $text);
    }

    public static function convertRelativeToAbsoluteImageSourcesInHTMLText($text){
        // Original PHP code by Chirp Internet: www.chirp.com.au
        // Please acknowledge use of this code by including this header.
        $regexp = "/<img\s[^>]*src=(\"??)([^\" >]*?)\\1[^>]*>(.*)<\/img>/siU";
        $callback = function( $matches ){
            $search = $matches[2];
            $replace = MstMailUtils::convertRelativeToAbsoluteURL($matches[2]);
            $subject = $matches[0];
            return str_replace($search, $replace, $subject);
        };
        $text = preg_replace_callback($regexp, $callback, $text);

        $regexp = '/src="([^"]+)"/';
        $callback = function( $matches ){
            $search = $matches[1];
            $replace = MstMailUtils::convertRelativeToAbsoluteURL($matches[1]);
            $subject = $matches[0];
            return str_replace($search, $replace, $subject);
        };
        $text = preg_replace_callback($regexp, $callback, $text);
        return $text;
    }

	public static function replaceContentIdsWithAttachments($content, $attachs){
		$log = MstFactory::getLogger();
		$cids = array();
		$cidPattern = '"cid:';
		$startPos = 0;
		$pos1 = true;
		while($pos1){			
			$log->debug('search for ' . $cidPattern . ' from '  . $startPos);	
			$pos1 = strpos($content, $cidPattern, $startPos);
			if($pos1 !== false){
				$log->debug('cId pos found at ' . $pos1);
				$pos2 = strpos($content, '"', $pos1+1);
				if($pos2 !== false){
					$cId = substr($content, $pos1+strlen($cidPattern), $pos2-$pos1-strlen($cidPattern));
					$log->debug('cId entry: ' . $cId);
					$cids[] = $cId;
				}
				$startPos = $pos1 + 1;
			}
		}

        $log->debug('replaceContentIdsWithAttachments, attachs: '.print_r($attachs, true));
        $uploads = wp_upload_dir();
        $uploadUrl = $uploads['baseurl'];
		for($i=0; $i < count($cids); $i++){
			$cId = $cids[$i];					
			for($j=0; $j < count($attachs); $j++){
				$attach = &$attachs[$j];
				$contentId = $attach->content_id;
				if( !is_null($contentId) && (trim($contentId) !== '' )){
					if($contentId === $cId){
						$fPath = str_replace('\\', '/', ($attach->filepath."/"));
						$fPath = $fPath.$attach->filename;
						$fPath = rawurlencode($fPath);
						$fPath = str_replace('%2F', '/', $fPath);
                        $fileUri = $uploadUrl.$fPath;
                        $log->debug('replaceContentIdsWithAttachments, $fPath: '.$fileUri);
						$content = str_replace($cidPattern.$cId, '"'.$fileUri, $content);
					}
				}
			}
		}
		
		return $content;
	}
	
	public static function isValidEmail($email, $allowMultiple=false){
		$email = trim($email);
		
		if(!$allowMultiple){
			$commaPos = strpos($email, ',');
			if($commaPos !== false){
				return false;
			}
		}else{
			$emailAddresses = explode(',', $email);
			foreach($emailAddresses AS $email){	
				if(!self::isValidEmail($email, false)){
					return false;
				}
			}
		}
		
		$atPos = strpos($email, '@');
		if($atPos != false && $atPos > 0 && $atPos < (strlen($email)-1)){
			$namePart = substr($email, 0, $atPos);
			$addressPart = substr($email, $atPos+1);
			
			$dotPos = strpos($addressPart, '.');
			if($dotPos != false && $dotPos > 0 && $dotPos < strlen($addressPart)){
				$domainPart = substr($addressPart, 0, $dotPos);
				$tldPart = substr($addressPart, $dotPos+1);
								
				if( (strlen($namePart) > 0) && (strlen($domainPart) > 0) && (strlen($tldPart) > 0) ){
					return true;
				}
			}
		}
		return false;
	}
	
	public static function hasLineLongerThan($str, $maxLineLength = 998){
		$log = MstFactory::getLogger();
		$arr = preg_split('/(*ANYCRLF)\R/', $str);
		$log->debug('hasLineLongerThan() Text of length '.strlen($str).' is built of '.count($arr).' lines');
		foreach($arr AS $line){
			if(strlen($line) > $maxLineLength){
				$log->debug('hasLineLongerThan() Found line longer than '.$maxLineLength.' chars, length is: '.strlen($line));
				$log->debug('hasLineLongerThan() Found line is: '.$line);
				return true;
			}
		}
		return false;
	}
	
	public static function htmlWordwrapIfNeeded($str){
		$log = MstFactory::getLogger();
		$maxLineLength = 998; // RFC2822 says lines MUST not be longer than 998 characters
		$isLineTooLong = self::hasLineLongerThan($str, $maxLineLength);
		if($isLineTooLong){
			$log->debug('htmlWordwrapIfNeeded() HTML contains line longer than '.$maxLineLength.' chars, do HTML safe wordwrap');
						
			if(!self::doesStrContainHtmlSafeWordwrapCriticalTags($str)){
				$log->debug('htmlWordwrapIfNeeded() Does not contain HTML safe wordwrap critical tags, therefore do simple replace');
				$log->debug('HTML Body before simple line splitting: '.$str);
				$str = self::htmlWordwrapBySimpleReplace($str);
				$log->debug('HTML Body after simple line splitting: '.$str);
				$isLineTooLong = self::hasLineLongerThan($str, $maxLineLength);
				$log->debug('htmlWordwrapIfNeeded() HTML still contains line longer than '.$maxLineLength.' chars, do style tags splitting next');
			}

            if($isLineTooLong){
                $log->debug('htmlWordwrapIfNeeded() Line (still) too long, now do splitting via style tags');
                $log->debug('HTML Body before style tag splitting: '.$str);
                $str = self::htmlWordwrapByWorkingInStyleTags($str, 0, 30, "\r\n", $maxLineLength);
                $log->debug('HTML Body after style tag splitting: '.$str);
                $isLineTooLong = self::hasLineLongerThan($str, $maxLineLength);
                $log->debug('htmlWordwrapIfNeeded() HTML still contains line longer than '.$maxLineLength.' chars, do the complex HTML safe wordwrap');
            }
			
			if($isLineTooLong){
				$log->debug('HTML Body before complex line splitting: '.$str);
				$str = self::htmlSafeWordwrapStr($str);
                $log->debug('htmlWordwrapIfNeeded() Done doing complex HTML safe wordwrap');
				$log->debug('HTML Body after complex line splitting: '.$str);

                $isLineTooLong = self::hasLineLongerThan($str, $maxLineLength);
            }

            if($isLineTooLong){
                $log->warning('htmlWordwrapIfNeeded() AFTER ALL DONE - HTML still contains line longer than '.$maxLineLength.' chars');
                $log->info('HTML AFTER ALL DONE: '.$str);
            }else{
                $log->debug('htmlWordwrapIfNeeded() Success, no more lines longer than '.$maxLineLength.' chars');
            }

		}else{
			$log->debug('htmlWordwrapIfNeeded() HTML contains NO line longer than '.$maxLineLength.' chars');
		}
		return $str;
	}
	
	public static function htmlWordwrapBySimpleReplace($str){
		$nl = preg_replace('#<br\s*/?>#i', "<br/>\r\n", $str); // replace <br> and <br /> and so on with "<br/>" and a new line
		return $nl;
	}
	
	public static function doesStrContainHtmlSafeWordwrapCriticalTags($str){
		if(strpos($str,'<pre') !== false) {
			return true;
		}
		return false;
	}

    public static function htmlWordwrapByWorkingInStyleTags($str, $recursionCount=0, $maxRecursions=70, $wrapStr="\r\n", $maxLineLength = 998){
        $log = MstFactory::getLogger();
        $log->debug('htmlWordwrapByWorkingInStyleTags START with recursion '.$recursionCount);
        $arr = preg_split('/(*ANYCRLF)\R/', $str);
        foreach($arr AS $key => $line){
            if(strlen($line) > $maxLineLength){
                $log->debug('htmlWordwrapByWorkingInStyleTags need to work on line index '.$key);
                $log->debug('htmlWordwrapByWorkingInStyleTags line before: '.$line);

                if(preg_match_all('/style="(.*)"/U', $line, $matches, PREG_OFFSET_CAPTURE)){
                    $log->debug('htmlWordwrapByWorkingInStyleTags Nr of found potential matches for splitting: '.count($matches[1]));
                    $candidate = null;
                    $bestPos = -1;
                    foreach($matches[1] AS $k => $match){
                        $str = $match[0];
                        $ind = $match[1];

                        $semiPos = strpos($str, ';'); // a style tag alone is not enough, we want to split after semicolon within style rules
                        if($semiPos === false)
                            continue; // no semicolon found, so not a real candidate for splitting

                        if(($ind + $semiPos + 1) < $maxLineLength){
                            // ok, we have a save candidate, now let us see if there is an even better one...
                            while(($semiPos !== false) && (($ind + $semiPos + 1) < $maxLineLength)){
                                $bestPos = $semiPos+1;
                                $semiPos = strpos($str, ';', $bestPos); // see if there is another semicolon after the current best one
                            }

                            if(is_null($candidate) || (($candidate[1]+$candidate[2]) < ($ind + $bestPos))){
                                $log->debug('htmlWordwrapByWorkingInStyleTags '.(is_null($candidate) ? 'First candidate: ' : 'New best candidate: ').'ind: '.$ind.', bestPos: '.$bestPos.' -> sum: '.($ind + $bestPos));
                                $candidate = array($str, $ind, $bestPos);
                            }
                        }
                    }
                    $log->debug('htmlWordwrapByWorkingInStyleTags Done evaluating candidates');

                    if($candidate){
                        $log->debug('htmlWordwrapByWorkingInStyleTags Best candidate is ind: '.$candidate[1].', bestPos: '.$candidate[2].' -> sum: '.($candidate[1] + $candidate[2]));
                        $log->debug('htmlWordwrapByWorkingInStyleTags line before: '.$line);
                        $wrappedLine = substr_replace($line, $wrapStr, $candidate[1]+$candidate[2], 0);
                        $log->debug('htmlWordwrapByWorkingInStyleTags line after.: '.$wrappedLine);
                        $arr[$key] = $wrappedLine; // replace with better version
                    }else{
                        $log->debug('htmlWordwrapByWorkingInStyleTags None of the candidates found where usable in line index '.$key);
                    }
                }else{
                    $log->debug('htmlWordwrapByWorkingInStyleTags No style-splitting candidates found in line index '.$key);
                }

            }
        }

        $wrappedStr = implode($wrapStr, $arr);
        $isLineTooLong = self::hasLineLongerThan($wrappedStr, $maxLineLength);
        if($isLineTooLong){
            $log->debug('htmlWordwrapByWorkingInStyleTags After recursion '.$recursionCount.' line still too long');
            if(($maxRecursions <= 0) || (($recursionCount+1) <= $maxRecursions) ){
                $log->debug('htmlWordwrapByWorkingInStyleTags Queue next recursion...');
                $wrappedStr = self::htmlWordwrapByWorkingInStyleTags($wrappedStr, $recursionCount+1, $maxRecursions, $wrapStr, $maxLineLength);
            }else{
                $log->debug('htmlWordwrapByWorkingInStyleTags Already done enough recursions, return');
            }
        }else{
            $log->debug('htmlWordwrapByWorkingInStyleTags After recursion '.$recursionCount.' all lines within defined limit of '.$maxLineLength.', success!');
        }

        return $wrappedStr;
    }
	
	/**
	* Default regLineSize of 600 is well below the RFC2822 standard of 998 characters per line
	* Larger lines might be produced, but only when those line can not be seperated without modifying the view of the result
	*/
	public static function htmlSafeWordwrapStr($str, $regLineSize=600, $wrapStr="\r\n", $inTag=true, &$enteredCriticalTags=array(), $recursionCount=0, $leftRight='start', $maxRecursions=70){
		$log = MstFactory::getLogger();
		$startTime = time();
	    $criticalTags = array('pre'); // elements not allowing to insert line breaks without modifying the view of the result
	    
	    $newStr = $str; // fallback
	    $strLen = strlen($str);
	    $lastTagOpened = -1;
	    
	    if($strLen>$regLineSize){ // line still longer than regLineSize?
	        $openTagPos = strpos(substr($str, 0, $regLineSize),'<');
			if($openTagPos===false){ // can we start to search for space after our regLineSize or are there tags that need to be looked at?
				$spacePtr = ($regLineSize-1);
			}else{
				$spacePtr = ($openTagPos-1);
				if($spacePtr < 0){
					$spacePtr = 0;
				}
			}
			
	        for($i=$spacePtr; $i<$strLen; $i++){ // go through line, start at spacePtr position
	            $cha = $str[$i];
	            if(!$inTag && $cha === '<'){
	                $lastTagOpened = $i;
	                $inTag = true;
	                continue;
	            }
	            if($inTag && $cha === '>'){
	                if($lastTagOpened>=0){
	                    $isOpeningTag = true; 	// default, opening tags are e.g. <div>
	                    $isClosingTag = false; 	// default, closing tags are e.g. </div>
	                    $lastTagStr = trim(substr($str, $lastTagOpened+1, $i-$lastTagOpened-1)); // remove < and >
	                    
	                    if($lastTagStr[0]==='/'){ // find closing tags
	                        $isOpeningTag = false;
	                        $isClosingTag = true;
	                        $lastTagStr = trim(substr($lastTagStr, 1));
	                    }
	                    if($lastTagStr[strlen($lastTagStr)-1]==='/'){ // find self-closing tags like <br/>
	                        $isOpeningTag = false;
	                        $isClosingTag = false;
	                        $lastTagStr = trim(substr($lastTagStr, 0, strlen($lastTagStr)-1));
	                    }
	                    
						$pos = strpos($lastTagStr, ' ');
						if($pos!==false){
							$lastTagStr = trim(substr($lastTagStr, 0, $pos)); // reduce tags to tag name, stripping contained elements like class="..."
						}
						$lastTagStr = strtolower($lastTagStr);
	                    
	                    if(in_array($lastTagStr, $criticalTags)){
	                        if($isOpeningTag){
	                            $enteredCriticalTags[] = $lastTagStr;
	                        }
	                        if($isClosingTag){                             
	                            if(end($enteredCriticalTags) === $lastTagStr){ // is closing tag the last opened critical tag?
	                                $matchingCriticalTag = array_pop($enteredCriticalTags);
	                            }
	                        }
	                    }                    
	                    $lastTagOpened = -1;
	                }
	                $inTag = false;
	                continue;
	            }
	            if(count($enteredCriticalTags) == 0 && !$inTag && $cha === ' '){ // seperate string when we are not in a tag, there are no open critical tags and we found a space
	                $spacePtr = $i; // this is our first choice for a potential line split
					for($k=($i+1);$k<$strLen;$k++){ // search for a later space so that line length can be closer to regLineSize
						if($k > $regLineSize) break; // line would be longer than regLineSize, take what we have
						if($str[$k] === '<') break; // cancel if we come across tags , take what we have
						if($str[$k] === ' '){
							$spacePtr = $k; // found a space closer to regLineSize
						}
					}
	                $left = substr($str, 0, $spacePtr);
	                $right = substr($str, $spacePtr+1);
	                $leftHash = sha1($left);
	                $rightHash = sha1($right);
	                //$log->debug('Next Left Part produced in '.$recursionCount.' '.$leftRight. ' (Hash: '.$leftHash.'):'."\r\n".$left);
	                //$log->debug('Next Right Part produced in '.$recursionCount.' '.$leftRight.' (Hash: '.$rightHash.'):'."\r\n".$right);
	                if( ($maxRecursions <= 0) || (($recursionCount+1) <= $maxRecursions) ){
		                $left = self::htmlSafeWordwrapStr($left, $regLineSize, $wrapStr, $inTag, $enteredCriticalTags, ($recursionCount+1), 'left'); // further seperate left part
		                $right = self::htmlSafeWordwrapStr($right, $regLineSize, $wrapStr, $inTag, $enteredCriticalTags, ($recursionCount+1), 'right'); // further seperate right part
		                //$log->debug('Returned from Left Hash: '.$leftHash.' and Right Hash: '.$rightHash);
		                $newStr = $left.$wrapStr.$right;
		                //$log->debug('Inserted line break after '.strlen($left).' before '.strlen($right).' chars');
	                }else{
	                	$log->debug('htmlSafeWordwrapStr() RETURN because of maxRecursions: '.$maxRecursions.', current: '.$recursionCount);
	                }
	                break;
	            }
	        }       
	    }
	    $endTime = time();
	    $timeNeeded = ($endTime-$startTime);
	    if($timeNeeded > 0){
	    	$log->debug('htmlSafeWordwrapStr() needed '.($endTime-$startTime).' seconds (recursion count: '.$recursionCount.' '.$leftRight.')');
	    }
	    return $newStr;
	}

	
}
