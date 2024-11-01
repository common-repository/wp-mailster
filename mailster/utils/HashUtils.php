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

class MstHashUtils
{	
	
	public static function checkUnsubscribeKeyOfMail($mailId, $salt, $hash){	
		global $wpdb;
        $log = MstFactory::getLogger();
        $log->debug('checkUnsubscribeKeyOfMail mail ID: '.$mailId.' check for salt '.$salt.', hash: '.$hash);
		if($mailId > 0){
			$query = 'SELECT * FROM ' . $wpdb->prefix . 'mailster_mails WHERE id=\'' . $mailId . '\'';
			$mail = $wpdb->get_row( $query );
			if($mail){
				$hKey = $mail->hashkey;
				$saltedKey = $hKey . $salt;
				$originalKey = sha1($saltedKey);
                $log->debug('checkUnsubscribeKeyOfMail mail ID: '.$mailId.' given for check: '.$hash.', should be equal to hash: '.$originalKey);
				return ( $originalKey === $hash );
			}
		}
		return false;
	}
	
	public static function getUnsubscribeKey($salt, $hashkey){
		return sha1($hashkey.$salt); // SHA1 hash for unsubscribe link verification
	}	
	
	public static function getSubscribeKey($salt, $hashkey){
		return sha1($hashkey.$salt); // SHA1 hash for subscribe link verification
	}

    public static function getModerationKey($salt, $hashkey){
        return sha1($hashkey.$salt); // SHA1 hash for moderation link verification
    }
	
	public static function getMailHashkey(){
		return MstHashUtils::getFixedLengthRandomString(45, true); // for DB
	}	
	
	public static function getSubscriptionHashkey(){
		return MstHashUtils::getFixedLengthRandomString(45, true); // for DB
	}
	
	public static function getFixedLengthRandomNumber($strLength=3) {
		$chars = '0123456789';
		return MstHashUtils::getRndString($chars, $strLength);
	}
	
	public static function getFixedLengthRandomString($strLength=8, $uppercase=false) {		
		if($uppercase){
			$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		}else{
			$chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
		}
		return MstHashUtils::getRndString($chars, $strLength);
	}
	
	public static function getRndString($chars, $strLength) {
		$charCount = strlen($chars);
		$rndString = '';	
		for ($i = 0; $i < $strLength; $i++) {
			$rndString = $rndString . $chars[mt_rand(0, $charCount - 1)];
		}
		return $rndString;
	}
	
}
