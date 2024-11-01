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


if ( preg_match( '#' . basename( __FILE__ ) . '#', $_SERVER['PHP_SELF'] ) ) {
		die( 'These are not the droids you are looking for.' );
	}

	class mst_frontend_mailing_list extends wpmst_mailster {
        public static function mst_shortcode_not_available_mst_mailing_lists($atts = array(), $content = null) {
            return sprintf(__( "Shortcode %s not available in product edition %s", 'wp-mailster' ), '[mst_mailing_lists]', MstFactory::getV()->getProductName());
        }
        public static function mst_shortcode_not_available_mst_emails($atts = array(), $content = null) {
            return sprintf(__( "Shortcode %s not available in product edition %s", 'wp-mailster' ), '[mst_emails]', MstFactory::getV()->getProductName());
        }

		public static function mst_mailing_lists_frontend($atts = array(), $content = null) {
            if ( is_admin()){
                return;
            }
            $log = MstFactory::getLogger();
            $log->debug('Shortcode mst_mailing_lists_frontend atts: '.print_r($atts, true).', subpage: '.(isset( $_GET['subpage'] ) ? $_GET['subpage'] : ' - not set -'));
			ob_start();
            if ( isset( $_GET['subpage'] ) && $_GET['subpage'] == 'mailstermails' ) {
				include "mails.php";
			} else if ( isset( $_GET['subpage'] ) && $_GET['subpage'] == 'mailstermail' ) {
				include "mail.php";
			} else if ( isset( $_GET['subpage'] ) && $_GET['subpage'] == 'mailsterthreads' ) {
				include "threads.php";
			} else if ( isset( $_GET['subpage'] ) && $_GET['subpage'] == 'mailsterthread' ) {
				include "thread.php";
			}else {
                $user = wp_get_current_user();
				if( isset( $_GET['subpage'] ) && $_GET['subpage'] == 'subscribe' ) {
                    $listId = intval($_GET['listID']);
                    if($listId > 0) {
                        if (!isset($_REQUEST['wpmst_been_here']) || $_REQUEST['wpmst_been_here'] !== 'subscribe-done') {
                            //subscribe the user
                            $subscrUtils = MstFactory::getSubscribeUtils();
                            $userObj = MstFactory::getUserModel()->getUserData($user->ID, true);
                            $userName = (property_exists($userObj, 'name') && $userObj->name && !empty($userObj->name) && (strlen(trim($userObj->name)) > 0)) ? $userObj->name : $user->display_name;
                            $success = $subscrUtils->subscribeUser($userName, $user->user_email, $listId, 0); // subscribing user...
                            $subscrUtils->sendWelcomeOrGoodbyeSubscriberMsg($userName, $user->user_email, $listId, MstConsts::SUB_TYPE_SUBSCRIBE);
                            if ($success == false) {
                                $mstRecipients = MstFactory::getRecipients();
                                $cr = $mstRecipients->getTotalRecipientsCount($listId);
                                if ($cr >= MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_REC)) {
                                    $log->debug('Too many recipients!');
                                    _e('Cannot subscribe', 'wp-mailster');
                                    _e('Too many recipients (Product limit)', 'wp-mailster');
                                }
                            } else {
                                // ####### TRIGGER NEW EVENT #######
                                $mstEvents = MstFactory::getEvents();
                                $mstEvents->userSubscribedOnWebsite($userName, $user->user_email, $listId);
                                // #################################
                                _e('Subscription successful', 'wp-mailster');
                                $_GET['listID'] = 0; // remove from request, we are done with that one
                            }
                            $_REQUEST['wpmst_been_here'] = 'subscribe-done';
                        }
                    }
				} else if( isset( $_GET['subpage'] ) && $_GET['subpage'] == 'unsubscribe' ) {
                    $listId = intval($_GET['listID']);
                    if($listId > 0) {
                        if (!isset($_REQUEST['wpmst_been_here']) || $_REQUEST['wpmst_been_here'] !== 'unsubscribe-done') {
                            //unsubscibe the user
                            $subscrUtils = MstFactory::getSubscribeUtils();
                            list($success, $tmpUser) = $subscrUtils->unsubscribeUser($user->user_email, $listId); // unsubscribing user...
                            if ($success) {
                                $subscrUtils->sendWelcomeOrGoodbyeSubscriberMsg($tmpUser['name'], $user->user_email, $listId, MstConsts::SUB_TYPE_UNSUBSCRIBE);
                                // ####### TRIGGER NEW EVENT #######
                                $mstEvents = MstFactory::getEvents();
                                $mstEvents->userUnsubscribedOnWebsite($user->user_email, $listId);
                                // #################################
                                _e('Unsubscription successful', 'wp-mailster');
                                $_GET['listID'] = 0; // remove from request, we are done with that one
                            } else {
                                $log->error('subpage=unsubscribe Not possible to unsubscribe email '.$user->user_email.' from list '.$listId.', tmpUser: '.print_r($tmpUser, true));
                                _e('There was a problem unsubscribing you from this list', 'wp-mailster');
                            }
                            $_REQUEST['wpmst_been_here'] = 'unsubscribe-done';
                        }
                    }
				}

				$uid = ( isset( $_REQUEST['uid'] ) && $_REQUEST['uid'] != '' ? intval( $_REQUEST['uid'] ) : '' );
				$lid = ( isset( $_REQUEST['lid'] ) && $_REQUEST['lid'] != '' ? intval( $_REQUEST['lid'] ) : '' );
                global $wpdb;
				$query = "SELECT id, name, admin_mail, list_mail, active, allow_subscribe, allow_unsubscribe FROM " . $wpdb->prefix . "mailster_lists WHERE front_archive_access != ".MstConsts::FRONT_ARCHIVE_ACCESS_NOBODY;
				$mLists = $wpdb->get_results($query);
				MstFactory::getAuthorization();
				?>
				<div class="mst_container">
					<div class="wrap">
						<h4><?php _e( "Mailing Lists", 'wp-mailster' ); ?></h4>
						<form>
							<table id="mst_table" class="wp-list-table widefat fixed striped posts">
								<thead>
                                    <tr>
                                        <td class="mailster_lists_name"><?php _e( "Name", 'wp-mailster' ); ?></td>
                                        <td class="mailster_lists_list_email"><?php _e( "Mailing list email", 'wp-mailster' ); ?></td>
                                        <td class="mailster_lists_emails"><?php _e( "Emails", 'wp-mailster' ); ?></td>
                                        <td class="mailster_lists_actions"></td>
                                    </tr>
								</thead>
								<tbody id="the-list">
								<?php
									if ( ! empty( $mLists ) ) {
										foreach ( $mLists as $mList ) {
											if(MstAuthorization::userHasAccess($mList->id)) {
												$id = $mList->id;
												?>
												<tr>
													<td class="mailster_lists_name"><?php echo $mList->name; ?></td>
													<td class="mailster_lists_list_email"><?php echo $mList->list_mail; ?></td>
													<td class="mailster_lists_emails">
														<a href="?subpage=mailstermails&lid=<?php echo $id; ?>"><?php _e( "View emails", 'wp-mailster' ); ?></a><br/>
														<a href="?subpage=mailsterthreads&lid=<?php echo $id; ?>"><?php _e( "View emails in threads", 'wp-mailster' ); ?></a>
													</td>
													<td class="mailster_lists_actions">
														<?php
														$actionPossible = false;
														$actionTitle = '';
														$actionLink = '';
														$altActionTitle = '';
														$backlink ="";
														$listUtils = MstFactory::getMailingListUtils();
														$list = $listUtils->getMailingList($id);

														if($listUtils->isSubscribed($id)){
															$actionTitle = __( 'Unsubscribe', 'wp-mailster');
															$altActionTitle = 'Login to unsubscribe';
															$actionLink =  '?subpage=unsubscribe&listID='. $id.'&bl='.$backlink ;
															if($mList->allow_unsubscribe){
																$actionPossible = true;
															}
														}else{
															$actionTitle = __( 'Subscribe', 'wp-mailster');
                                                            $altActionTitle = 'Login to subscribe';
															$actionLink = '?subpage=subscribe&listID='. $id.'&bl='.$backlink ;
															if($mList->allow_subscribe){
																$actionPossible = true;
															}
														}
														if($actionPossible && is_user_logged_in()){
															?><a href="<?php echo $actionLink; ?>" class="<?php echo (($user && ($user->ID > 0)) ? 'wpuser' : 'guest'); ?>"><?php echo $actionTitle; ?></a>
                                                            <?php }else{
                                                        ?><em class="guest please-login-to-subscribe"><?php echo $altActionTitle; ?></em><?php
                                                            } ?>
													</td>
												</tr>
												<?php
													}
												}
											} else {
											?>
										<tr>
											<td align="center"
												colspan="5"><?php _e( "No record found", 'wp-mailster' ); ?>!
											</td>
										</tr>
										<?php } ?>
								</tbody>
							</table>
						</form>
					</div>
				</div>
				<?php
			}
			return ob_get_clean();
		}

		public static function mst_emails_frontend($atts = array(), $content = null) {
            if ( is_admin()){
                return;
            }
            $log = MstFactory::getLogger();
            $log->debug('Shortcode mst_emails_frontend atts: '.print_r($atts, true));
            if(is_array($atts) && array_key_exists('lid', $atts)){
			    $listId = (int)$atts['lid'];
                $log->debug('Shortcode mst_emails_frontend listId: '.$listId);
            }else{
                $listId = 0;
                $log->debug('Shortcode mst_emails_frontend for all lists (listId=0)');
            }
            if(is_array($atts) && array_key_exists('order', $atts)){
                $orderParam = strtolower(trim($atts['order']));
                switch($orderParam){
                    case 'asc':
                    case 'old':
                    case 'oldfirst':
                    case 'oldestfirst':
                        $msgOrder = 'asc';
                        break;
                    case 'new':
                    case 'desc':
                    case 'rfirst':
                    case 'newestfirst':
                        $msgOrder = 'rfirst'; // recent first
                        break;
                    default:
                        $msgOrder = 'rfirst';
                }
            }else{
                $msgOrder = 'rfirst';
            }

            ob_start();
			if ( isset( $_GET['subpage'] ) && $_GET['subpage'] == 'mailstermail' ) {
				include "mail.php";
			} else {
                include "mails.php";
			}
            return ob_get_clean();
		}

		public static function mst_profile($atts = array(), $content = null) {
            if ( is_admin()){
                return;
            }
			ob_start();
			if ( isset( $_GET['subpage'] ) && $_GET['subpage'] == 'profile_subscribe' ) {
				include "profile_subscribe.php";
                include "profile.php";
			} else if ( isset( $_GET['subpage'] ) && $_GET['subpage'] == 'profile_unsubscribe' ) {
				include "profile_unsubscribe.php";
				include "profile.php";
			} else if ( isset( $_GET['subpage'] ) && $_GET['subpage'] == 'digest' ) {
				include "profile_digest.php";
				include "profile.php";
			} else {
				include "profile.php";
			}
			return ob_get_clean();
		}
	}