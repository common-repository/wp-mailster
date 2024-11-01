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
	
	class MstCacheUtils
	{		
		public static function getRecipientState($listId){	
			global $wpdb;
			$query = 'SELECT cstate FROM ' . $wpdb->prefix . 'mailster_lists WHERE id =\'' . $listId . '\'';
			$state = $wpdb->get_var( $query );
			return $state;
		}		
		
		public static function newRecipientState($listId){
            $log = MstFactory::getLogger();
			$oldState = self::getRecipientState($listId);
            $log->debug('newRecipientState old state: '.$oldState);
			$newState = $oldState + 1;
            $log->debug('newRecipientState new state: '.$newState);
			self::saveRecipientState($listId, $newState);
            $checkState = self::getRecipientState($listId);
            $log->debug('newRecipientState check state from DB: '.$checkState.' (equal to '.$newState.': '.(($checkState == $newState)?'yes':'NO!!!').')');
		}
				
		public static function saveRecipientState($listId, $state){
			$log = MstFactory::getLogger();
			global $wpdb;
			$result = false;
			$errorMsg = '';
            try {
               $result = $wpdb->update(
					$wpdb->prefix."mailster_lists",
					array( "cstate" => $state ),
					array( "id" => $listId ),
					array("%s"),
					array("%d")
				); // update cache version/state
            } catch (Exception $e) {
                $errorMsg = 'Exception ' . get_class($e) . ' Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
            }
			if(false === $result){
				$log->error('Updating of cache version for list ' . $listId . ' failed: ' .$errorMsg);
			}
		}
	}

