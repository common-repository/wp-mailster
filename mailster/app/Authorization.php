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
	class MstAuthorization
	{


		/**
		 *  Get the (ACL) actions         */
		public static function getActions($assetId=null) {
            return true;
		}
		
		public static function isAllowed($action, $listId=null){
			return true;
		}
		
		public static function noPermissionForAction($action){
			$log = MstFactory::getLogger();
			$log->error(sprintf(__('No permission to do the action: %s', 'wp-mailster'), $action));
			return false;
		}
		
		public static function frontendMenuViewAccessOK(){
			return true;			
		}
		
		public static function userGroupAuthorizedInherited($gid){
			return true;
		}
		
		public static function printUnauthorizedMsg(){
			echo '<span class="mailster_unauthorized" style="color:red;">'.__("Not authorized", 'wp-mailster').'</span>';
		}

		public static function userHasAccess($listId) {
            $log = MstFactory::getLogger();
			if($listId > 0) {
				// specific mailing list
				$mailingListUtils = MstFactory::getMailingListUtils();
				$mList = $mailingListUtils->getMailingList( $listId );
                if($mList) {
                    // specific mailing list
                    $user = wp_get_current_user();
                    if ($mList->front_archive_access == MstConsts::FRONT_ARCHIVE_ACCESS_ALL_USERS) {
                        return true; // everyone can access
                    } elseif ($mList->front_archive_access == MstConsts::FRONT_ARCHIVE_ACCESS_LOGGED_IN_USERS) {
                        if ($user) {
                            return true; // user is logged in, everything good
                        } else {
                            //JFactory::getApplication()->enqueueMessage(JText::_('COM_MAILSTER_YOU_NEED_TO_LOGIN_TO_ACCESS_THIS_SECTION'), 'error');
                            return false;
                        }
                    } elseif ($mList->front_archive_access == MstConsts::FRONT_ARCHIVE_ACCESS_LOGGED_IN_SUBSCRIBERS_OF_MAILING_LIST) {
                        // specific list -> we can directly check whether user is subscriber...
                        $recip = MstFactory::getRecipients();
                        $subscriberLists = $recip->getListsUserIsMemberOf($user->ID, 1);
                        foreach ($subscriberLists as $subscrListId) {
                            if ($subscrListId == $mList->id) {
                                return true; // user is subscriber, everything good
                            }
                        }

                        //JFactory::getApplication()->enqueueMessage(JText::_('COM_MAILSTER_YOU_NEED_TO_BE_A_SUBSCRIBER_OF_THIS_MAILING_LIST_TO_ACCESS_THIS_SECTION'), 'error');
                        return false;
                    } elseif ($mList->front_archive_access == MstConsts::FRONT_ARCHIVE_ACCESS_NOBODY) {
                        return false; // nobody is allowed to see this lists mails in the frontend
                    }
                }else{
                    $log->warning('userHasAccess list ID '.$listId.' not (or no longer) existing');
                    return false; // no access
                }
			}
			return true;
		}

	}


