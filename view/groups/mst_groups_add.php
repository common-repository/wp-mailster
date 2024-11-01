<?php
if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
	die('These are not the droids you are looking for.');
}

include_once $this->WPMST_PLUGIN_DIR."/models/MailsterModelGroup.php";
include_once $this->WPMST_PLUGIN_DIR."/models/MailsterModelUser.php";

$sid   = (isset($_GET['sid']) && $_GET['sid']!=''?intval($_GET['sid']):'');
if ( ! $sid ) {
	if ( isset( $_POST['sid'] ) ) {
		$sid = intval($_POST['sid']);
	}
}

$title  = __("New Group", 'wp-mailster');
$button = __('Add Group', 'wp-mailster');
$action = 'add';
$message = "";
$options = array();

$log = MstFactory::getLogger();
$Group = null;
$User = new MailsterModelUser();

if($sid) {
	$Group = new MailsterModelGroup($sid);
} else {
	$Group = new MailsterModelGroup();
}

$haveDoneActions = false;
$log->debug('mst_groups_add POST: '.print_r($_POST, true));
if( isset($_POST['group_action']) && !is_null($_POST['group_action']) ) { //if form is submitted
	if ( isset( $_POST[ 'add_group' ] ) ) {
        $haveDoneActions = true;
        $nonce = sanitize_text_field( $_REQUEST['_wpnonce'] );
        if ( ! wp_verify_nonce( $nonce, 'mst_groups_add' ) ) {
            $log->warning('mst_groups_add CSRF stop!');
            die( 'Stop CSRF!' );
        }
		$addGroup = sanitize_text_field($_POST['add_group']);
		if( intval($_POST[ 'sid' ]) ) {
			$group_options[ 'id' ] = intval($_POST[ 'sid' ]);
		}
		$group_options[ 'name' ] = sanitize_text_field($_POST[ 'group_name' ]);
		
		$Group->saveData( $group_options, $addGroup );
		$sid = $Group->getId();

        // TODO FIXME CHECK BEFORE SAVE ACTIONS IN WHICH LIST SUBSCRIBED,
        // TODO AFTERWARDS CHECK AGAIN, COMPARE BECAUSE OF ADMIN WELCOME-GOODBYE MESSAGE

		//if we are editing an existing group, we can add members
		if ( $addGroup == 'edit' ) {
            $recip = MstFactory::getRecipients();
            $groupUsersBefore = $Group->getAllUsers();
            $userListsMembershipsBefore = array();
            foreach($groupUsersBefore AS $groupUser){
                $userListsMembershipsBefore[$groupUser->user_id.'-'.$groupUser->is_core_user] = $recip->getListsUserIsMemberOf($groupUser->user_id, $groupUser->is_core_user);
            }
		    $log->debug('add_group -> edit (now emptying all group users, then re-adding)');
			//remove all members and then insert all new ones
			$delRes = $Group->emtpyUsers();
			$log->debug('add_group -> edit  Group id '.$sid.' #deleted users by emptying: '.$delRes);
			//insert the selected members in the group
			if( array_key_exists('to', $_POST) && $_POST['to'] ) {
                $log->debug('add_group -> edit  Values in "to": '.print_r($_POST['to'], true));
                $added2GroupCount = 0;
				foreach ( $_POST['to'] as $k => $v ) {
					if( isset( $v ) && $v != '' ) {
						$val = explode( '-', $v );
						$user_id = intval($val[0]);
						$is_core_user = intval($val[1]);
						$res = $Group->addUserById( $user_id, $is_core_user );
						if($res){
						    $log->debug('add_group added by id: '.$v);
                            $added2GroupCount++;
                        }else{
                            $log->error('add_group failed to add to group: '.$v);
                        }
					}
				}
                $log->debug('add_group -> edit  #Added to group: '.$added2GroupCount);

                $groupUsersAfter = $Group->getAllUsers();
                $userListsMembershipsAfter = array();
                foreach($groupUsersAfter AS $groupUser){
                    $userListsMembershipsAfter[$groupUser->user_id.'-'.$groupUser->is_core_user] = $recip->getListsUserIsMemberOf($groupUser->user_id, $groupUser->is_core_user);
                }

                $subscrUtils = MstFactory::getSubscribeUtils();
                $mailingListUtils = MstFactory::getMailingListUtils();
                $allUsersInChangeScope = array_unique(array_merge(array_Keys($userListsMembershipsBefore), array_Keys($userListsMembershipsAfter)));
                $log->debug('add_group userListsMembershipsBefore: '.print_r($userListsMembershipsBefore, true));
                $log->debug('add_group userListsMembershipsAfter: '.print_r($userListsMembershipsAfter, true));
                $log->debug('add_group allUsersInChangeScope: '.print_r($allUsersInChangeScope, true));
                foreach($allUsersInChangeScope AS $userInChangeScope){
                    $userInScopeArr = explode('-', $userInChangeScope);
                    $userInScopeUserId = $userInScopeArr[0];
                    $userInScopeIsCoreUser = $userInScopeArr[1];
                    $userData = MstFactory::getUserModel()->getUserData($userInScopeUserId, $userInScopeIsCoreUser);
                    $wasPriorGroupUser = array_key_exists($userInChangeScope, $userListsMembershipsBefore);
                    $isNowGroupUser = array_key_exists($userInChangeScope, $userListsMembershipsAfter);
                    $listsMembershipsBefore = $wasPriorGroupUser ? $userListsMembershipsBefore[$userInChangeScope] : array();
                    $listsMembershipsAfter = $isNowGroupUser ? $userListsMembershipsAfter[$userInChangeScope] : array();
                    $intersect = array_intersect($listsMembershipsBefore, $listsMembershipsAfter);
                    $listsMembershipDifference = array_merge(array_diff($listsMembershipsBefore, $intersect), array_diff($listsMembershipsAfter, $intersect));
                    $log->debug('add_group user ID '.$userInScopeUserId.' (core: '.intval($userInScopeIsCoreUser).'), editing group '.$sid.', listsMembershipDifference: '.print_r($listsMembershipDifference, true));
                    foreach($listsMembershipDifference AS $listIdDiffering){
                        $mList = $mailingListUtils->getMailingList($listIdDiffering);
                        $log->debug('add_group user ID '.$userInScopeUserId.' (core: '.intval($userInScopeIsCoreUser).') differing for list ID '.$listIdDiffering);
                        if($mList->welcome_msg_admin > 0 || $mList->goodbye_msg_admin > 0) {
                            $log->debug('add_group list ID has admin welcome/goodbye messages active');
                            $msgType = null;
                            if ($wasPriorGroupUser && !$isNowGroupUser) {
                                $msgType = MstConsts::SUB_TYPE_UNSUBSCRIBE;
                                $log->debug('add_group user ID '.$userInScopeUserId.' (core: '.intval($userInScopeIsCoreUser).') was group user of group '.$sid.', not anymore -> send goodbye message');
                            } elseif (!$wasPriorGroupUser && $isNowGroupUser) {
                                $msgType = MstConsts::SUB_TYPE_SUBSCRIBE;
                                $log->debug('add_group user ID '.$userInScopeUserId.' (core: '.intval($userInScopeIsCoreUser).') was not group user of group '.$sid.', now is -> send welcome message');
                            } else {
                                $log->debug('add_group user ID '.$userInScopeUserId.' (core: '.intval($userInScopeIsCoreUser).') no change in membership to group '.$sid);
                            }
                            if($msgType){
                                $log->debug('add_group will send welcome/goodbye message');
                                $subscrUtils->sendWelcomeOrGoodbyeSubscriberMsg($userData->name, $userData->email, $listIdDiffering, $msgType);
                                $log->debug('add_group DONE SENDING welcome/goodbye message');
                            }
                        }else{
                            $log->debug('add_group list ID does not have admin welcome/goodbye messages activated');
                        }
                    }
                }
			}
		}
	}
}	
$values = null;
if($sid) {
	$title = __("Edit Group", 'wp-mailster');
	$button = __('Update Group', 'wp-mailster');
	$action = 'edit'; 
}

if( $sid ) {
	//get all users (wp and mailster)
    $allUsers  = $User->getAllUsers();
	//get all the group's users
	$grp_list = $Group->getAllUsers();
	$selected = array();
	foreach($grp_list as $gv){
		$selected[] = $gv->user_id.'-'.$gv->is_core_user;
	}

    $nonMembers = array();
    $existingMembers = array();
    for($i=0;$i<count($allUsers);$i++){
        $userIdValue = $allUsers[$i]->uid . '-' . $allUsers[$i]->is_core_user;
        if(in_array($userIdValue, $selected)){
            $existingMembers[] = $allUsers[$i];
        }else{
            $nonMembers[] = $allUsers[$i];
        }
    }

    if($haveDoneActions){
        $log->debug('After actions in nonMembers: '.print_r($nonMembers, true));
        $log->debug('After actions in existingMembers: '.print_r($existingMembers, true));
    }
}

$options = $Group->getFormData();
?>

<div class="mst_container">
	<div class="wrap">
		<h2><?php echo $title; ?></h2>
		<?php echo (isset($message) && $message!=''?$message:'');?>
		<form action="" method="post">
			<?php wp_nonce_field( 'mst_groups_add' ); ?>
			<table class="form-table">
				<tbody>
					<?php
					$this->mst_display_hidden_field( "sid", $sid );
					$this->mst_display_hidden_field( "add_group", $action );
                    if($sid > 0){
                        $this->mst_display_sometext(__("ID", "wp-mailster"), $sid);
                    }
					$this->mst_display_input_field( __("Group Name", 'wp-mailster'), 'group_name', $options->name, null, false, false, null );
					?>
				</tbody>
			</table>

			<?php if( $sid ) { ?>
                <div class="ms2side__header"><?php __("Choose users to add to group",'wp-mailster'); ?></div>
                <div class="ms2side__div">
                    <div class="ms2side__select">
                        <select name="from[]" id="multiselect" class="form-control" size="8" multiple="multiple"><?php
                            foreach( $nonMembers as $nonMember ) {
                                $userIdValue = $nonMember->uid . '-' . $nonMember->is_core_user;
                                $userType = (( isset( $nonMember->is_core_user ) && ($nonMember->is_core_user==1)) ? 'WP User' : 'Mailster');
                                $userName = ($nonMember->Name == "" ) ? __("(no name)", 'wp-mailster') : $nonMember->Name;
                                ?><option value="<?php echo $userIdValue; ?>"><?php echo $userName . '&lt;'.$nonMember->Email.'&gt; ('. $userType . ')'; ?></option><?php } ?>
                        </select>
                    </div>

                    <div class="ms2side__options">
                        <button type="button" id="multiselect_rightAll" class="btn btn-block" title="<?php echo __("Add all", 'wp-mailster');?>">&raquo;</button>
                        <button type="button" id="multiselect_rightSelected" class="btn btn-block" title="<?php echo __("Add selected", 'wp-mailster');?>">&gt;</button>
                        <button type="button" id="multiselect_leftSelected" class="btn btn-block" title="<?php echo __("Remove selected", 'wp-mailster');?>">&lt;</button>
                        <button type="button" id="multiselect_leftAll" class="btn btn-block" title="<?php echo __("Remove all", 'wp-mailster');?>">&laquo;</i></button>
                    </div>

                    <div class="ms2side__select">
                        <select name="to[]" id="multiselect_to" class="form-control" size="8" multiple="multiple"><?php
                            foreach( $existingMembers as $listmember ) {
                                $userIdValue = $listmember->uid . '-' . $listmember->is_core_user;
                                $userType = (( isset( $listmember->is_core_user ) && ($listmember->is_core_user==1)) ? 'WP User' : 'Mailster');
                                $userName = ($listmember->Name == "" ) ? __("(no name)", 'wp-mailster') : $listmember->Name;
                                ?><option value="<?php echo $userIdValue; ?>"><?php echo $userName . '&lt;'.$listmember->Email.'&gt; ('. $userType . ')'; ?></option><?php } ?>
                        </select>
                    </div>
                </div>

			<?php } ?>
			<input type="submit" class="button-primary" name="group_action" value="<?php echo $button; ?>">
		</form>
	</div>
</div>