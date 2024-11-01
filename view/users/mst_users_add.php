<?php
	if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
		die( 'These are not the droids you are looking for.' );
	}
require_once $this->WPMST_PLUGIN_DIR."/models/MailsterModelUser.php";
require_once $this->WPMST_PLUGIN_DIR."/models/MailsterModelList.php";
require_once $this->WPMST_PLUGIN_DIR."/models/MailsterModelGroup.php";

$is_core_user = (isset($_GET['core']) && $_GET['core']!='')?intval($_GET['core']):0;
$sid   = (isset($_GET['sid']) && $_GET['sid']!='')?intval($_GET['sid']):'';

if ( ! $sid ) {
	if ( isset( $_POST['sid'] ) ) {
		$sid = intval($_POST['sid']);
	}
}
if ( ! $is_core_user ) {
	if ( isset( $_POST['core'] ) ) {
		$is_core_user = intval($_POST['core']);
	}
}

$title  = __("New Mailster User", 'wp-mailster');
$button = __('Add Mailster User', 'wp-mailster');
$action = 'add';
$options = array();

$log = MstFactory::getLogger();
$recip = MstFactory::getRecipients();
$User = null;
$subscribedListIdsOfUserBefore = array();
if($sid) {
    $userWasAlreadyExisting = true;
	$User = new MailsterModelUser($sid, $is_core_user);
    $log->debug('mst_users_add Already existing user (user_id: '.$sid.', core: '.$is_core_user.'): '.print_r($User, true));
    $subscribedListIdsOfUserBefore = $recip->getListsUserIsMemberOf($sid, $is_core_user);
} else {
    $userWasAlreadyExisting = false;
	$User = new MailsterModelUser();
    $log->debug('mst_users_add New user');
}

$messages = array();

if( isset( $_POST['user_action'] ) ) { //if form is submitted
    $log->debug('mst_users_add POST: '.print_r($_POST, true));
	if ( isset( $_POST['add_user'] ) ) {
        $nonce = sanitize_text_field( $_REQUEST['_wpnonce'] );
        if ( ! wp_verify_nonce( $nonce, 'mst_users_add' ) ) {
            $log->warning('mst_users_add CSRF stop!');
            die( 'Stop CSRF!' );
        }
		if( $_POST['sid'] ) {
			$user_options['id'] = intval($_POST['sid']);
		}
        $success = false;
		if ( ! $is_core_user ) { //save user data only for Mailster users
			$user_options['name'] = sanitize_text_field(stripslashes($_POST['user_name']));
			$user_options['email'] = sanitize_email($_POST['email']);
			$user_options['notes'] = sanitize_text_field(stripslashes($_POST['notes']));
            $log->debug('mst_users_add Mailster user save sanitized: '.print_r($user_options, true));
			$success = $User->saveData($user_options, sanitize_text_field($_POST['add_user']));
			$sid = $User->getId();
		}else{
            $success = true; // for core users that part is fine
        }

		if( isset( $_POST[ 'is_group_member' ] ) ) {
			foreach( $_POST['is_group_member'] as $groupid => $groupmember ) {
				if($groupmember) {
					$User->addToGroup(intval($groupid));
				} else {
					$User->removeFromGroup(intval($groupid));
				}
			}
		}
		if(isset($_POST['is_list_member'])) {
			foreach( $_POST['is_list_member'] as $listid => $listmember ) {
				if($listmember) {
					$User->addToList(intval($listid));
				} else {
					$User->removeFromList(intval($listid));
				}
			}
		}

		if($success && $userWasAlreadyExisting){
            $messages[] = $this->wpmst_view_message( "updated", __( "User successfully updated", 'wp-mailster' ) );
        }else if($success && !$userWasAlreadyExisting){
            $messages[] = $this->wpmst_view_message( "updated", __( "User successfully created", 'wp-mailster' ) );
        }else if(!$success){
            $messages[] = $this->wpmst_view_message( "error", __( "User could not be saved", 'wp-mailster' ) );
        }

        $subscribedListIdsOfUserAfter = $recip->getListsUserIsMemberOf($sid, $is_core_user);
        $allListIds = array_unique(array_merge($subscribedListIdsOfUserBefore, $subscribedListIdsOfUserAfter));

        $userData = MstFactory::getUserModel()->getUserData($sid, $is_core_user);
        $subscrUtils = MstFactory::getSubscribeUtils();
        $mailingListUtils = MstFactory::getMailingListUtils();
        foreach($allListIds AS $listId){
            $mList = $mailingListUtils->getMailingList($listId);
            if($mList->welcome_msg_admin > 0 || $mList->goodbye_msg_admin > 0) {
                $wasSubscribedBefore = in_array($listId, $subscribedListIdsOfUserBefore);
                $amSubscribedAfter = in_array($listId, $subscribedListIdsOfUserAfter);
                if ($wasSubscribedBefore && !$amSubscribedAfter && $mList->goodbye_msg_admin > 0) {
                    $log->debug('mst_users_add unsubscribed from list ID ' . $listId . ', send out goodbye message');
                    $subscrUtils->sendWelcomeOrGoodbyeSubscriberMsg($userData->name, $userData->email, $listId, MstConsts::SUB_TYPE_UNSUBSCRIBE);
                } elseif (!$wasSubscribedBefore && $amSubscribedAfter && $mList->welcome_msg_admin > 0) {
                    $log->debug('mst_users_add subscribed to list ID ' . $listId . ', send out welcome message');
                    $subscrUtils->sendWelcomeOrGoodbyeSubscriberMsg($userData->name, $userData->email, $listId, MstConsts::SUB_TYPE_SUBSCRIBE);
                } else {
                    $log->debug('mst_users_add no change on subscription for list ID ' . $listId);
                }
            }else{
                $log->debug('mst_users_add no admin goodbye/welcome messages for list ID '.$listId);
            }
        }
	}
}	
$values = null;
if($sid) {
	$title = __("Edit Mailster User", 'wp-mailster');
	$button = __('Update Mailster User', 'wp-mailster');
	$action = 'edit'; 
}
$options = $User->getFormData();

$List = new MailsterModelList();
$lists = $List->getAll();
$Group = new MailsterModelGroup();
$groups = $Group->getAll();

?>
<div class="mst_container">
	<div class="wrap">
		<h2><?php echo $title; ?></h2>
        <?php $this->wpmst_print_messages($messages); ?>
		<form action="" method="post">
			<?php wp_nonce_field( 'mst_users_add' ); ?>
			<table class="form-table">
				<tbody>
					<?php
					$this->mst_display_hidden_field("sid", $sid);
					$this->mst_display_hidden_field("core", $is_core_user);
					$this->mst_display_hidden_field("add_user", $action);
					if( ! $is_core_user ) {
						$this->mst_display_input_field( __("Name", 'wp-mailster'), 'user_name', $options->name, null, false, false, null );
						$this->mst_display_input_field( __("Email", 'wp-mailster'), 'email', $options->email, null, false, false, null );
						$this->mst_display_input_field( __("Description", 'wp-mailster'), 'notes', $options->notes, null, false, false, null );
					} else {
                        $userData = MstFactory::getUserModel()->getUserData($sid, $is_core_user);
                    ?>
                        <h3><?php echo $userData->name.' ('.$userData->email.')' ?> <a href="<?php echo get_edit_user_link( $sid ); ?>"><?php _e("Edit WordPress User", "wpsmt-mailster"); ?></a></h3>

					<?php } ?>
				</tbody>
			</table>
			<input type="submit" class="button-primary" name="user_action" value="<?php echo $button; ?>">
		
			<?php if( $sid ) { ?>
			<h4><?php _e("User member of Groups", 'wp-mailster'); ?></h4>
			<table>
				<tr>
					<th><?php _e("Group Name", 'wp-mailster'); ?></th>
					<th><?php _e("Group Member", 'wp-mailster'); ?></th>
				</tr>
				<?php
				$i=0;
				foreach($groups as $group) { ?>
				<tr>
					<td>
						<?php echo $group->name; ?>
					</td>
					<td>
					<?php
					$checked = false;
					if( $User->isUserInGroup($sid, $is_core_user, $group->id) ) {
						$checked = true;
					}
					$this->mst_display_simple_radio_field('is_group_member[' . $group->id . ']', 1, 'is_group_member'.$i.'1', __("Yes", 'wp-mailster'), $checked, false);
					$this->mst_display_simple_radio_field('is_group_member[' . $group->id . ']', 0, 'is_group_member'.$i.'0', __("No", 'wp-mailster'), !$checked, false);
					$i++;	
					?>
					</td>
				</tr>
					<?php
				}
				?>
			</table>

			<h4><?php _e("User member of Lists", 'wp-mailster'); ?></h4>
			<table>
				<tr>
					<th><?php _e("List Name", 'wp-mailster'); ?></th>
					<th><?php _e("List Member", 'wp-mailster'); ?></th>
				</tr>
				<?php
				$i=0;
				foreach($lists as $list) { ?>
				<tr>
					<td>
						<?php echo $list->name; ?>
					</td>
					<td>
					<?php
					$checked = false;
					if( $User->isUserInList($sid, $is_core_user, $list->id) ) {
						$checked = true;
					}
					$this->mst_display_simple_radio_field('is_list_member[' . $list->id . ']', 1, 'is_list_member'.$i.'1', __("Yes", 'wp-mailster'), $checked, false);
					$this->mst_display_simple_radio_field('is_list_member[' . $list->id . ']', 0, 'is_list_member'.$i.'0', __("No", 'wp-mailster'), !$checked, false);
					$i++;	
					?>
					</td>
				</tr>
					<?php
				}
				?>
			</table>
			<?php } ?>
			

		</form>
	</div>
</div>