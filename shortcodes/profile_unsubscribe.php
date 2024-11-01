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


    if(isset($_GET['listID'])) {
		$listId = intval($_GET['listID']);
	} else {
		$listId = 0;
	}
	$message = "";

	if( !is_user_logged_in() ){
		_e("You need to login to access this section", 'wp-mailster');
	} else {
		$log         = MstFactory::getLogger();
		$user = wp_get_current_user();
		$subscrUtils = MstFactory::getSubscribeUtils();

		$listUtils = MstFactory::getMailingListUtils();
		$mList     = $listUtils->getMailingList( $listId );
		if ( $mList ) {
			if ( $mList->allow_unsubscribe ) {
				if ( $mList->unsubscribe_mode != MstConsts::UNSUBSCRIBE_MODE_DOUBLE_OPT_IN ) {
					$log->debug( 'Double Opt-in unsubscribe mode not activated (frontend)' );
					$success = $subscrUtils->unsubscribeUserId( $user->ID, true, $listId );
					$tmpUser = $subscrUtils->getUserByEmail( $user->user_email );
					$subscrUtils->sendWelcomeOrGoodbyeSubscriberMsg( $tmpUser['name'], $user->user_email, $listId, MstConsts::SUB_TYPE_UNSUBSCRIBE );
					if ( $success ) {
						$message = __("Unsubscription Successful", 'wp-mailster');
                        // ####### TRIGGER NEW EVENT #######
                        $mstEvents = MstFactory::getEvents();
                        $mstEvents->userUnsubscribedOnWebsite($user->user_email, $listId);
                        // #################################
					} else {
						$message = __("Unsubscription Failed", 'wp-mailster');
					}
				} else {
					$log->debug( 'Double Opt-in unsubscribe mode (frontend)' );
					$subscrUtils->unsubscribeUserWithDoubleOptIn( $user->user_email, $listId );
					$message = __("Unsubscription Successful. Please confirm by clicking the link in the confirmation email that was sent to you.", 'wp-mailster');
				}
			}
		}
		echo $message;
	}