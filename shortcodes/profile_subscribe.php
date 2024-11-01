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


    $log = MstFactory::getLogger();
	$user = wp_get_current_user();
	$subscrUtils = MstFactory::getSubscribeUtils();

	if(isset($_GET['listID'])) {
		$listId = intval($_GET['listID']);
	} else {
		$listId = 0;
	}
	$message = "";

	if( !is_user_logged_in() ){
		_e("You need to login to access this section", 'wp-mailster');
	} else {
		$listUtils = MstFactory::getMailingListUtils();
		$mList     = $listUtils->getMailingList( $listId );
        $userModel = MstFactory::getUserModel();
        $userObj   = $userModel->getUserData($user->ID, true);
        $userName = (property_exists($userObj, 'name') && $userObj->name && !empty($userObj->name) && (strlen(trim($userObj->name))>0)) ? $userObj->name : $user->display_name;
		if ( $mList ) {
			if ( $mList->allow_subscribe ) {
				if ( $mList->subscribe_mode != MstConsts::SUBSCRIBE_MODE_DOUBLE_OPT_IN ) {
					$log->debug( 'Double Opt-in subscribe mode not activated (frontend)' );
					$success = $subscrUtils->subscribeUserId( $user->ID, true, $listId );
					$subscrUtils->sendWelcomeOrGoodbyeSubscriberMsg( $userName, $user->user_email, $listId, MstConsts::SUB_TYPE_SUBSCRIBE );
					if ( $success ) {
						$message = __("Subscription Successful", 'wp-mailster');
                        // ####### TRIGGER NEW EVENT #######
                        $mstEvents = MstFactory::getEvents();
                        $mstEvents->userSubscribedOnWebsite( $userName, $user->user_email, $listId);
                        // #################################
					} else {
						$message = __("Subscription Failed", 'wp-mailster');
					}
				} else {
					$log->debug( 'Double Opt-in subscribe mode (frontend)' );
					$subscrUtils->subscribeUserWithDoubleOptIn($user->user_email, $userName, $listId );
					$message = __("Subscription Successful. Please confirm by clicking the link in the confirmation email that was sent to you.", 'wp-mailster');
				}
			}
		}
		echo $message;
	}