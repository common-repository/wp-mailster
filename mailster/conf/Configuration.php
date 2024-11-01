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
	
class MstConfiguration
{
	public static function getKeepBlockedEmailsForDays() {
		return get_option('wpmst_cfg_keep_blocked_mails_for_days', 30);
	}
	public static function getKeepBouncedEmailsForDays() {
		return get_option('wpmst_cfg_keep_bounced_mails_for_days', 30);
	}
	
	public static function getLoggingLevel() {
		return get_option('wpmst_cfg_logging_level', MstConsts::LOG_LEVEL_INFO);
	}
	
	public static function getLogFileSizeWarningLevel() {
		return intval(get_option('wpmst_cfg_log_file_warning_size_mb', 50));
	}
	
	public static function isUseAlternativeTextVars() {
		return ( get_option('wpmst_cfg_use_alt_txt_vars') > 0 );
	}
	
	public static function isLoggingForced() {
		return ( intval(get_option('wpmst_cfg_force_logging', 0)) > 0 );
	}
	
	public static function useMailingListAddressAsFromField() {			
		return ( get_option('wpmst_cfg_mail_from_field') > 0 );
	}
	
	public static function useMailingListNameAsFromField() {
		return (get_option('wpmst_cfg_name_from_field') > 0 );
	}

    public static function getMailingListNameGlobalSetting(){
        /*
         * earlier global format:
         *      <option value="0">COM_MAILSTER_SENDER_NAME</option>
         *      <option value="1">COM_MAILSTER_MAILING_LIST_NAME</option>
         *
         */
        $nameFromField = get_option('wpmst_cfg_name_from_field', MstConsts::NAME_FROM_MODE_SENDER_NAME_VIA_LIST_NAME);
        switch($nameFromField){
            case "0": // old format for sender name
                return MstConsts::NAME_FROM_MODE_SENDER_NAME;
                break;
            case "1": // old format for list name
                return MstConsts::NAME_FROM_MODE_MAILING_LIST_NAME;
                break;
            case "sender_name": // new format for sender name
                return MstConsts::NAME_FROM_MODE_SENDER_NAME;
                break;
            case "list_name": // new format for list name
                return MstConsts::NAME_FROM_MODE_MAILING_LIST_NAME;
                break;
            case "sender_name_via_list_name": // format for sender name via list name
                return MstConsts::NAME_FROM_MODE_SENDER_NAME_VIA_LIST_NAME;
                break;
            default:
                return  MstConsts::NAME_FROM_MODE_SENDER_NAME_VIA_LIST_NAME;
        }
    }

	public static function getBlockedEmailAddresses() {
		$words = array();
		$wordsStr = get_option('wpmst_cfg_blocked_email_addresses');
        return self::extractWordsFromConfigString($wordsStr, $words);
    }
	
	public static function getWordsToFilter() {
		$words = array();
		$wordsStr = get_option('wpmst_cfg_words_to_filter');
        return self::extractWordsFromConfigString($wordsStr, $words, false);
	}
	
	public static function getDigestMailFormat() {
		$mailFormat = trim( get_option('wpmst_cfg_digest_format_html_or_plain', 'html') );
		if( !$mailFormat || is_null($mailFormat) || strlen( $mailFormat ) < 1 ) {
            $mailFormat = 'html';
		}
		return $mailFormat;
	}
	
	public static function getDateFormat() {
        $defaultFormat = get_option('date_format', 'd/m/Y') . " " . get_option('time_format', 'H:i:s');
		$dateFormat = trim( get_option('wpmst_cfg_mail_date_format', $defaultFormat) );
		if( is_null($dateFormat) || strlen( $dateFormat ) < 1 ) {
			$dateFormat = $defaultFormat;
		}
		return $dateFormat;
	}
	
	public static function getDateFormatWithoutTime() {
        $defaultFormat = get_option('date_format', 'd/m/Y');
		$dateFormat = trim( get_option('wpmst_cfg_mail_date_format_without_time', $defaultFormat) );
		if ( is_null($dateFormat) || strlen( $dateFormat ) < 1 ) {
			$dateFormat = $defaultFormat;
		}
		return $dateFormat;
	}
	
	public static function addMailsterMailHeaderTag() {	
		return true; // this will be non-optional going forward
	}
	
	public static function includeBodyInBouncedBlockedNotifications() {	
		if ( get_option('wpmst_cfg_include_body_in_blocked_bounced_notifies', 1) > 0 ) {
			return true;
		}
		return false;
	}

    public static function includeAttachmentsInModerationRequests() {
        if ( get_option('wpmst_cfg_include_attachments_in_moderation_requests', 1) > 0 ) {
            return true;
        }
        return false;
    }
	
	public static function isUndoLineWrapping(){	
		if ( get_option('wpmst_cfg_undo_line_wrapping') > 0 ) {
			return true;
		}
		return false;
	}

	public static function getMaxMailsPerMinute() {
		$default = 0;
		if ( MstFactory::getV()->getFtSetting( MstVersionMgmt::MST_FT_ID_THROTTLE ) ) {
			return get_option('wpmst_cfg_max_mails_per_minute', $default);
		} else {
			return $default;
		}
	}

	public static function getMaxMailsPerHour() {
		$default = 0;
		if ( MstFactory::getV()->getFtSetting( MstVersionMgmt::MST_FT_ID_THROTTLE ) ) {
			return get_option('wpmst_cfg_max_mails_per_hour', $default);
		} else {
			return $default;
		}
	}

	public static function getWaitBetweenTwoMails() {			
		$default = 0;	
		if ( MstFactory::getV()->getFtSetting( MstVersionMgmt::MST_FT_ID_THROTTLE ) ) {
			$waitTime = get_option('wpmst_cfg_wait_between_two_mails', $default);
            $waitTime = str_replace(',', '.', $waitTime);
		} else {
            $waitTime = $default;
		}
        return floatval($waitTime);
	}
	
	public static function getLastMailSentAt() {
		$default = -1;	
		return intval(get_option('wpmst_cfg_last_mail_sent_at', $default));
	}
	
	public static function getLastHourMailSentIn() {
		$default = -1;	
		return intval(get_option('wpmst_cfg_last_hour_mail_sent_in', $default));
	}
	
	public static function getNrOfMailsSentInLastHour() {
		$default = -1;	
		return intval(get_option('wpmst_cfg_nr_of_mails_sent_in_last_hour', $default));
	}
	
	public static function getLastDayMailSentIn() {
		$default = -1;	
		return get_option('wpmst_cfg_last_day_mail_sent_in', $default);
	}

	public static function getOnRegistrationAddToLists(){
        $default = array();
        $add2Lists = get_option('wpmst_cfg_registration_add_to_lists', $default);
        if(!$add2Lists){
            return array();
        }
        return $add2Lists;
    }

	public static function getNrOfMailsSentInLastDay() {
		$default = -1;
		return get_option('wpmst_cfg_nr_of_mails_sent_in_last_day', $default);
	}

	public static function setLastMailSentAt($lastMailSentAtTime){
		update_option('wpmst_cfg_last_mail_sent_at', $lastMailSentAtTime);
	}
	
	public static function setLastHourMailSentIn($lastHourMailSentIn){
		update_option('wpmst_cfg_last_hour_mail_sent_in', $lastHourMailSentIn);
	}
	
	public static function setNrOfMailsSentInLastHour($nrOfMailsSentInLastHour){
		update_option( 'wpmst_cfg_nr_of_mails_sent_in_last_hour', $nrOfMailsSentInLastHour );
	}
	
	public static function setLastDayMailSentIn($lastDayMailSentIn){
		update_option( 'wpmst_cfg_last_day_mail_sent_in', $lastDayMailSentIn );
	}
	
	public static function setNrOfMailsSentInLastDay($nrOfMailsSentInLastDay){
		update_option( 'wpmst_cfg_nr_of_mails_sent_in_last_day', $nrOfMailsSentInLastDay );
	}

    public static function getOnRegistrationAddToGroups(){
        $default = array();
        $add2Groups = get_option('wpmst_cfg_registration_add_to_groups', $default);
        if(!$add2Groups){
            return array();
        }
        return $add2Groups;
    }
	
	public static function addSubjectPrefixToReplies() {
		if ( get_option('wpmst_cfg_add_reply_prefix', 1) > 0 ) {
			return true;
		}
		return false;
	}

	public static function getMailboxOpenTimeout() {
        $default = 25;
        $imapOpenTimeout = intval(trim(get_option('wpmst_cfg_imap_opentimeout', $default)));
		if ($imapOpenTimeout && strlen($imapOpenTimeout) > 0 && $imapOpenTimeout > 0 ) {
			return $imapOpenTimeout;
		}
		return $default;
	}	
	
	public static function getReplyPrefix() {		
		$replyPrefix = get_option('wpmst_cfg_reply_prefix', 'Re:');
        if($replyPrefix){
            return $replyPrefix;
        }
        return 'Re:';
	}

	public static function getRecaptchaV2Keys() {
		$keys = array();
		$keys[ 'public' ] = get_option('wpmst_cfg_recaptcha2_public_key', '');
		$keys[ 'private' ] = get_option('wpmst_cfg_recaptcha2_private_key', '');
		return $keys;
	}

    public static function getRecaptchaTheme(){
        return get_option('wpmst_cfg_recaptcha_theme', 'light');
    }

    public static function adjustSettingsForDMARC_Providers(){
	    if(get_option('wpmst_cfg_dmarc_adjust_for_policy_reject_providers', 1) > 0){
            return true;
        }
        return false;
    }

    public static function getDMARC_Providers(){
        $words = array();
        $wordsStr = get_option('wpmst_cfg_dmarc_providers_policy_reject', 'aol.*, yahoo.*');
        return self::extractWordsFromConfigString($wordsStr, $words);
    }

    protected static function extractWordsFromConfigString($wordsStr, $words, $returnTrimmedVersion=true): array
    {
        $words = explode(',', $wordsStr);
        if ($words) {
            $nrWords = count($words);
            for ($i = 0; $i < $nrWords; $i++) {
                $word = $words[$i];
                $word = trim($word);
                if (($word === '') || ($word === MstConsts::NO_PARAMETER_SUPPLIED_FLAG)) {
                    unset($words[$i]); // remove empty element
                } else {
                    if($returnTrimmedVersion) {
                        $words[$i] = $word; // take trimmed version
                    }
                }
            }
            $words = array_values($words); // re-index
        } else {
            $words = array();
        }
        return $words;
    }
}