<?php
	if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
		die( 'These are not the droids you are looking for.' );
	}
global $wpdb;
$lid   = (isset($_GET['lid']) && $_GET['lid']!=''?intval($_GET['lid']):'');
if ( ! $lid ) {
	if ( isset( $_POST['lid'] ) ) {
		$lid = intval($_POST['lid']);
	}
}
include_once $this->WPMST_PLUGIN_DIR."/models/MailsterModelList.php";
include_once $this->WPMST_PLUGIN_DIR."/models/MailsterModelGroup.php";
$messages = array();
$list = null;
if($lid) {
	$list = new MailsterModelList($lid);
} else {
	$list = new MailsterModelList();
}
$Group = new MailsterModelGroup();
$log = MstFactory::getLogger();

$log->debug('mst_list_groups_add POST: '.print_r($_POST, true));
if ( isset( $_POST[ "tab" ] ) && intval($_POST[ "tab" ]) == 3 ) {

    $nonce = sanitize_text_field( $_REQUEST['_wpnonce'] );
    if ( ! wp_verify_nonce( $nonce, 'mst_add_list_groups' ) ) {
        $log->warning('mst_list_groups_add CSRF stop!');
        die( 'Stop CSRF!' );
    }

    $log->debug('mst_list_groups_add -> tab 3');
	//remove existing groups and add the new groups
	$newGroupUserCount = 0;
	$totalNewCount = 0;
	$allOldGroups  = $list->getAllListGroups();
    $nrGroupsOld = count($allOldGroups);
    $nrGroupsNew = 0;
    $nrGroupsOldAndNew = 0;
    $nrGroupsAdded = 0;
    if(array_key_exists('to', $_POST) && $_POST[ 'to' ] && is_array($_POST[ 'to' ]) ){
        foreach( $_POST[ 'to' ] as $k => $group_id ) {
            if( isset( $group_id ) && $group_id != '' ) {
                $nrGroupsNew++;
                $foundInOldGroups = false;
                foreach ($allOldGroups as $oldGroup){
                    if ($group_id == $oldGroup->group_id) {
                        $foundInOldGroups = true;
                        $nrGroupsOldAndNew++;
                        $newGroupUserCount += $Group->countMembers($group_id);
                    }
                }
                if(!$foundInOldGroups){
                    $nrGroupsAdded++;
                }
                $totalNewCount += $Group->countMembers( $group_id );
            }
        }
    }

	$mstRecipients = MstFactory::getRecipients();
	$oldRecipCount = $mstRecipients->getTotalRecipientsCount($lid); // get old, cached number
	//find out how many are deleted
	$stayingUsersCount = $totalNewCount - $newGroupUserCount; // these are the users that stay
	$deletedCount = $oldRecipCount - $stayingUsersCount;
	$futureCount = $oldRecipCount - $deletedCount + $newGroupUserCount;

    $log->debug('oldRecipCount: '.$oldRecipCount);
    $log->debug('newGroupUserCount: '.$newGroupUserCount);
    $log->debug('totalNewCount: '.$totalNewCount);
    $log->debug('stayingUsersCount: '.$stayingUsersCount);
    $log->debug('deletedCount: '.$deletedCount);
    $log->debug('futureCount: '.$futureCount);

	if($futureCount >  MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_REC) ) {
		$messages[] = $this->wpmst_view_message("error", __("Too many recipients, max list members limit reached.", 'wp-mailster'));
	} else {
		$res = $list->emtpyGroups();
        $log->debug('mst_list_groups_add emptyGroups result: '.$res);
        if(array_key_exists('to', $_POST) && $_POST[ 'to' ] && is_array($_POST[ 'to' ]) ){
            foreach ( $_POST['to'] as $k => $group_id ) {
                if ( isset( $group_id ) && $group_id != '' ) {
                    $log->debug('mst_list_groups_add add group ID '.intval($group_id));
                    $list->addGroupById( intval( $group_id ) );
                }
            }
        }
        if($newGroupUserCount > 0 || $deletedCount > 0){
            $mstRecipients->recipientsUpdated($lid);
        }
		$messages[] = $this->wpmst_view_message( "updated", __( "Group(s) successfully updated", 'wp-mailster' ) );
	}
}

$listGroups = $list->getAllListGroups();
$selected = array();
foreach( $listGroups as $listGroup ) {
	$selected[] = $listGroup->group_id;
}
$allGroups  = $Group->getAllGroups();
$nonListGroups = array();
$existingListGroups = array();
for($i=0;$i<count($allGroups);$i++){
    if(in_array($allGroups[$i]->id, $selected)){
        $existingListGroups[] = $allGroups[$i];
    }else{
        $nonListGroups[] = $allGroups[$i];
    }
}
$log->debug('mst_list_groups_add existingListGroups: '.print_r($existingListGroups, true));
$log->debug('mst_list_groups_add nonListGroups: '.print_r($nonListGroups, true));
$listData = $list->getData();
?>
<div class="mst_container">
	<div class="wrap">
		<h2><?php echo $listData[0]->name . " - " . __("Manage Groups", 'wp-mailster'); ?></h2>
        <?php $this->wpmst_print_messages($messages); ?>
		<div id="mst_list_members" class="mst_listing mst_list_members">
			<div class="wptl_container">
				<div class="wrap">
					<h4><?php _e("Manage List Groups", "mailster"); ?></h4>
					<form action="" method="post" onsubmit="return markAllAndSubmit();">
						<?php wp_nonce_field( 'mst_add_list_groups' ); ?>
						<div class="ms2side__header"><?php _e("Choose groups to add to list", 'wp-mailster'); ?></div>
                        <div class="ms2side__div">
                            <div class="ms2side__select">
                                <select name="from[]" id="multiselect" class="form-control" size="8" multiple="multiple"><?php
                                    if (!empty($allGroups)) {
                                        foreach( $nonListGroups as $listGroup ) {
                                        ?><option value="<?php echo $listGroup->id; ?>"><?php echo $listGroup->name; ?></option><?php } ?>
                                    <?php
                                    } else { ?>
                                        <option><?php esc_html_e("No groups found", 'wp-mailster'); ?></option><?php
                                    } ?>
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
                                    foreach( $existingListGroups as $listGroup ) {
                                        ?><option value="<?php echo $listGroup->id; ?>"><?php echo $listGroup->name; ?></option><?php } ?>
                                </select>
                            </div>
                        </div>

                        <table style="display:block;clear:both;width:100%;">
                            <tr class="form-field">
                                <th scope="row"><label for="submit"></label></th>
                                <td>
                                    <a href="<?php echo admin_url(); ?>admin.php?page=mst_mailing_lists&amp;subpage=recipients&amp;lid=<?php echo $lid; ?>"><?php _e("Back", 'wp-mailster'); ?></a>
                                    <input type="hidden" name="tab" value="3">
                                    <input type="submit" class="button-primary" name="user_action" value="<?php _e("Save changes" ,'wp-mailster'); ?>">
                                </td>
                            </tr>
					</table>
				</form>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript">
    function markAllAndSubmit(){
        return true;
    }
</script>