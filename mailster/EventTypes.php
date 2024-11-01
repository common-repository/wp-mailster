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
	
	class MstEventTypes
	{
		const NEW_LIST_MAIL = 0;
		const NEW_BLOCKED_MAIL = 1;
		const NEW_BOUNCED_MAIL = 2;
		const NEW_FILTERED_MAIL = 3;
		const USER_SUBSCRIBED_ON_WEBSITE = 4;
		const USER_UNSUBSCRIBED_ON_WEBSITE = 5;
		const SEND_ERROR = 6;
        const NEW_MODERATED_MAIL = 7;
		
		public static function getAllTriggerTypes(){
			$triggers = array();
			$triggers[] = self::NEW_LIST_MAIL;
			$triggers[] = self::NEW_BLOCKED_MAIL;
			$triggers[] = self::NEW_BOUNCED_MAIL;
			$triggers[] = self::NEW_FILTERED_MAIL;
			$triggers[] = self::USER_SUBSCRIBED_ON_WEBSITE;
			$triggers[] = self::USER_UNSUBSCRIBED_ON_WEBSITE;
			$triggers[] = self::SEND_ERROR;
            $triggers[] = self::NEW_MODERATED_MAIL;
			return $triggers;
		}
	}
	

