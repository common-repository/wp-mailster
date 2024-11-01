<?php
	if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
		die( 'These are not the droids you are looking for.' );
	}
$uid   = (isset($_GET['uid']) && $_GET['uid']!=''?intval($_GET['uid']):'');
$lid   = (isset($_GET['lid']) && $_GET['lid']!=''?intval($_GET['lid']):'');

if ( ! $lid ) {
	if ( isset( $_POST['lid'] ) ) {
		$lid = intval($_POST['lid']);
	}
}


if(isset($_POST['add_user']) && $_POST['add_user'] == 'add') {
	$message = $this->wpmst_view_message("updated", __("New user added successfully.", 'wp-mailster'));
}elseif(isset($_POST['add_user']) == 'edit') {
	$message = $this->wpmst_view_message("updated", __("User updated successfully.", 'wp-mailster'));
}

$title  = __("New Mailing List", 'wp-mailster');
$button = __('Add Mailing List', 'wp-mailster');
$action = 'add';
$message = "";
$options = array();

include_once $this->WPMST_PLUGIN_DIR."/models/MailsterModelList.php";
include_once $this->WPMST_PLUGIN_DIR."/models/MailsterModelServer.php";

$List = null;
if($lid) {
	$List = new MailsterModelList($lid);
} else {
	$List = new MailsterModelList();
}
$ServerIn = new MailsterModelServer();
$ServerOut = new MailsterModelServer();

$log = MstFactory::getLogger();
$converterUtils = MstFactory::getConverterUtils();
$notifyUtils = MstFactory::getNotifyUtils();
$log->debug('mst_mailing_list_add');
if( isset( $_POST['list_action'] ) ) { //if form is submitted
    $log->debug('mst_mailing_list_add -> list_action');

    $nonce = sanitize_text_field( $_REQUEST['_wpnonce'] );
    if ( ! wp_verify_nonce( $nonce, 'mst_mailing_list_add' ) ) {
        die( 'Stop CSRF!' );
    }
	
	if ( isset( $_POST['add_list'] ) ) {
        $log->debug('mst_mailing_list_add -> list_action -> add_list, post:');
        $log->debug(print_r($_POST, true));

		//tab1
		$options['name'] = sanitize_text_field($_POST['name']);
		$options['list_mail'] = sanitize_email($_POST['list_mail']);
		$options['admin_mail'] = sanitize_email($_POST['admin_mail']);
		$options['active'] = intval($_POST['active']);
        if( isset( $_POST['front_archive_access'] ) ) {
		    $options['front_archive_access'] = intval($_POST['front_archive_access']);
        }else{
            $options['front_archive_access'] = 0;
        }
		
		//tab2
		$log = MstFactory::getLogger();
		$log->debug("inb_edited: ".sanitize_text_field($_POST['inb_edited'])." server id: ".$_POST['server_inb_id']." server name: ".sanitize_text_field($_POST['server_name_in']) );
		$server_options_in['server_type'] = MstConsts::SERVER_TYPE_MAIL_INBOX;
		if( isset( $_POST['server_name_in'] ) ) {
			$server_options['name'] = sanitize_text_field($_POST['server_name_in']);
		} else {
			$server_options['name'] = "New server";
		}
		if( isset( $_POST['server_host_in'] ) ) {
			$server_options['server_host'] = sanitize_text_field($_POST['server_host_in']);
		} else {
			$server_options['server_host'] = "";
		}
		if( isset( $_POST['server_port_in'] ) ) {
			$server_options['server_port'] = intval($_POST['server_port_in']);
		} else {
			$server_options['server_port'] = "";
		}
		if( isset( $_POST['protocol_in'] ) ) {
			$server_options['protocol'] = sanitize_text_field($_POST['protocol_in']);
		} else {
			$server_options['protocol'] = "";
		}
		if( isset( $_POST['secure_protocol_in'] ) && $_POST['secure_protocol_in'] != 'none' ) {
			$server_options['secure_protocol'] = sanitize_text_field($_POST['secure_protocol_in']);
		} else {
			$server_options['secure_protocol'] = "";
		}
		if( isset( $_POST['secure_authentication_in'] ) ) {
			$server_options['secure_authentication'] = sanitize_text_field($_POST['secure_authentication_in']);
		} else {
			$server_options['secure_authentication'] = 0;
		}
		if( isset( $_POST['connection_parameter_in'] ) ) {
			$server_options['connection_parameter'] = sanitize_text_field($_POST['connection_parameter_in']);
		} else {
			$server_options['connection_parameter'] = "";
		}
		$server_options['published'] = 1;
        $server_options['user_edited'] = 1; // if we save it here, it has to be edited by user

		if ( $_POST['server_inb_id'] == 0 ) { //add a new server
			$created = $ServerIn->saveData( $server_options, 'add' );
			if ( $created ) {
				$options['server_inb_id'] = $ServerIn->getId();
			} else {
				$message .= __( "There was an error while saving the inbox server. Please try again.", 'wp-mailster' );
			}
		} else if( $_POST['inb_edited'] != 0 ) { //edit existing server
			$options['server_inb_id'] = intval($_POST['server_inb_id']);
			$ServerIn = new MailsterModelServer(intval($_POST['server_inb_id']));
			$created = $ServerIn->saveData($server_options, 'edit');
		} else {
			$options['server_inb_id'] = intval($_POST['server_inb_id']);
		}
		$options['mail_in_user'] = sanitize_text_field($_POST['mail_in_user']);
        $options['mail_in_pw'] = $converterUtils->sanitizePasswordField($_POST['mail_in_pw']);
		
		//tab3		
		$options['use_cms_mailer'] = intval($_POST['use_cms_mailer']);

		$server_options_out['server_type'] = MstConsts::SERVER_TYPE_SMTP;
		$server_options_out['protocol'] = "smtp";
		if( isset($_POST['server_name_out']) ) {
			$server_options_out['name'] = sanitize_text_field($_POST['server_name_out']);
		} else {
			$server_options_out['name'] = "New Server (smtp)";
		}
		if( isset($_POST['server_host_out']) ) {
			$server_options_out['server_host'] = sanitize_text_field($_POST['server_host_out']);
		} else {
			$server_options_out['server_host'] = "";
		}
		if( isset($_POST['server_port_out']) ) {
			$server_options_out['server_port'] = sanitize_text_field($_POST['server_port_out']);
		} else {
			$server_options_out['server_port'] = "";
		}
		if( isset($_POST['secure_protocol_out']) ) {
			$server_options_out['secure_protocol'] = sanitize_text_field($_POST['secure_protocol_out']);
		} else {
			$server_options_out['secure_protocol'] = "";
		}
		if( isset($_POST['secure_authentication_out']) ) {
			$server_options_out['secure_authentication'] = sanitize_text_field($_POST['secure_authentication_out']);
		} else {
			$server_options_out['secure_authentication'] = "";
		}
		$server_options_out['published'] = 1;
		if ( ( isset($_POST['server_out_id']) && $_POST['server_out_id'] == 0 ) && $options['use_cms_mailer'] == 0 ) { //add a new server
			$created = $ServerOut->saveData($server_options_out, 'add');
			if($created) {				
				$options['server_out_id'] = $ServerOut->getId();
			} else {
				$message .= __("There was an error while saving the outbox server. Please try again.", 'wp-mailster');
			}
		}  else if( ( isset($_POST['server_out_id']) && $_POST['server_out_id'] != 0 ) && $options['use_cms_mailer'] == 0 && $_POST['out_edited'] == 1) {
			$ServerOut = new MailsterModelServer(intval($_POST['server_out_id']));
			$created = $ServerOut->saveData($server_options_out, 'edit');
			$options['server_out_id'] = intval($_POST['server_out_id']);
		} else if($options['use_cms_mailer'] == 1) { //use WP mailer
			$options['server_out_id'] = 0;
		}else {
			$options['server_out_id'] = intval($_POST['server_out_id']);
		}
		if( isset( $_POST['mail_out_user'] ) ) {
			$options['mail_out_user'] = sanitize_text_field($_POST['mail_out_user']);
		}
		if( isset( $_POST['mail_out_pw'] ) ) {
            $options['mail_out_pw'] = $converterUtils->sanitizePasswordField($_POST['mail_out_pw']);
		}

        $addWhitespace = false;
        if(substr($_POST['subject_prefix'], -1) == ' '){ // last character of subject prefix a space?
            $addWhitespace = true;
        }
		
		//tab4
        if(function_exists('sanitize_textarea_field')){
            $options['subject_prefix'] = sanitize_textarea_field($_POST['subject_prefix']); // sanitize but preserve white spaces
            $options['custom_header_plain'] = sanitize_textarea_field($_POST['custom_header_plain']);
            $options['custom_footer_plain'] = sanitize_textarea_field($_POST['custom_footer_plain']);
        }else{
            $options['subject_prefix'] = sanitize_text_field($_POST['subject_prefix']);
            $options['custom_header_plain'] = sanitize_text_field($_POST['custom_header_plain']);
            $options['custom_footer_plain'] = sanitize_text_field($_POST['custom_footer_plain']);
        }

        $options['subject_prefix'] = $options['subject_prefix'] . ($addWhitespace ? ' ' : ''); // re-add whitespace at the end that got lost during sanitization

		$options['clean_up_subject'] = intval($_POST['clean_up_subject']);
		$options['mail_format_conv'] = intval($_POST['mail_format_conv']);
        if( isset( $_POST['disable_mail_footer'] ) ) {
		    $options['disable_mail_footer'] = intval($_POST['disable_mail_footer']);
        }else{
            $options['disable_mail_footer'] = 0;
        }
		$options['mail_format_altbody'] = intval($_POST['mail_format_altbody']);
		$options['custom_header_html']  = wp_kses_post($_POST['custom_header_html']);
		$options['custom_footer_html'] = wp_kses_post($_POST['custom_footer_html']);

		//tab5
		$options['copy_to_sender'] = intval($_POST['copy_to_sender']);
		$options['mail_size_limit'] = intval($_POST['mail_size_limit']);
        if( isset( $_POST['filter_mails'] ) ) {
		    $options['filter_mails'] = intval($_POST['filter_mails']);
        }else{
            $options['filter_mails'] = 0;
        }
		$options['allow_bulk_precedence'] = intval($_POST['allow_bulk_precedence']);
		$options['sending_public'] = intval($_POST['sending_public']);
		if( isset( $_POST['sending_recipients'] ) ) {
			$options['sending_recipients'] = intval($_POST['sending_recipients']);
		} else {
			$options['sending_recipients'] = 0;
		}
		if( isset( $_POST['sending_admin'] ) ) {
			$options['sending_admin'] = intval($_POST['sending_admin']);
		} else {
			$options['sending_admin'] = 0;
		}
		if( isset( $_POST['sending_group'] ) ) {
			$options['sending_group'] = intval($_POST['sending_group']);
		} else {
			$options['sending_group'] = 0;
		}
		if( isset( $_POST['sending_group_id'] ) ) {
			$options['sending_group_id'] = intval($_POST['sending_group_id']);
		} else {
			$options['sending_group_id'] = 0;
		}


        //moderation tab
        if( isset( $_POST['mod_mode'] ) ) {
            $options['mod_mode'] = intval($_POST['mod_mode']);
        } else {
            $options['mod_mode'] = 0;
        }
        if( isset( $_POST['mod_moderated_group'] ) ) {
            $options['mod_moderated_group'] = intval($_POST['mod_moderated_group']);
        } else {
            $options['mod_moderated_group'] = 0;
        }
        if( isset( $_POST['mod_approve_recipients'] ) ) {
            $options['mod_approve_recipients'] = intval($_POST['mod_approve_recipients']);
        } else {
            $options['mod_approve_recipients'] = 0;
        }
        if( isset( $_POST['mod_approve_group'] ) ) {
            $options['mod_approve_group'] = intval($_POST['mod_approve_group']);
        } else {
            $options['mod_approve_group'] = 0;
        }
        if( isset( $_POST['mod_approve_group_id'] ) ) {
            $options['mod_approve_group_id'] = intval($_POST['mod_approve_group_id']);
        } else {
            $options['mod_approve_group_id'] = 0;
        }
        if( isset( $_POST['mod_info_sender_moderation'] ) ) {
            $options['mod_info_sender_moderation'] = intval($_POST['mod_info_sender_moderation']);
        } else {
            $options['mod_info_sender_moderation'] = 0;
        }
        if( isset( $_POST['mod_info_sender_approval'] ) ) {
            $options['mod_info_sender_approval'] = intval($_POST['mod_info_sender_approval']);
        } else {
            $options['mod_info_sender_approval'] = 0;
        }
        if( isset( $_POST['mod_info_sender_rejection'] ) ) {
            $options['mod_info_sender_rejection'] = intval($_POST['mod_info_sender_rejection']);
        } else {
            $options['mod_info_sender_rejection'] = 0;
        }

		
		//tab6
		$options['addressing_mode'] = intval($_POST['addressing_mode']);
		if( isset($_POST['bcc_count']) ) {
			$options['bcc_count'] = intval( $_POST['bcc_count'] );
		} else {
			$options['bcc_count'] = 10;
		}
		if( isset( $_POST[ "incl_orig_headers" ] ) ) {
			$options['incl_orig_headers'] = intval($_POST['incl_orig_headers']);
		} else {
			$options['incl_orig_headers'] = 0;
		}
		$options['mail_from_mode'] = intval($_POST['mail_from_mode']);
		$options['name_from_mode'] = intval($_POST['name_from_mode']);
		$options['reply_to_sender'] = intval($_POST['reply_to_sender']);
		if( isset($_POST['bounce_mail']) ) {
			$options['bounce_mail'] = sanitize_email($_POST['bounce_mail']);
		} else {
			$options['bounce_mail'] = "";
		}
		$options['reply_to_sender'] = intval($_POST['reply_to_sender']);
		$options['bounce_mode'] = intval($_POST['bounce_mode']);
		$options['max_send_attempts'] = intval($_POST['max_send_attempts']);
		$options['save_send_reports'] = intval($_POST['save_send_reports']);

		//tab7
		$options['allow_subscribe'] = intval($_POST['allow_subscribe']);
        if( isset( $_POST['public_registration'] ) ) {
            $options['public_registration'] = intval($_POST['public_registration']);
        }else{
            $options['public_registration'] = 1;
        }
        if( isset( $_POST['subscribe_mode'] ) ) {
		    $options['subscribe_mode'] = intval($_POST['subscribe_mode']);
        }else{
            $options['subscribe_mode'] = 0;
        }
		$options['welcome_msg'] = intval($_POST['welcome_msg']);
		$options['welcome_msg_admin'] = intval($_POST['welcome_msg_admin']);
        $options['allow_unsubscribe'] = intval($_POST['allow_unsubscribe']);
        if( isset( $_POST['unsubscribe_mode'] ) ) {
		    $options['unsubscribe_mode'] = intval($_POST['unsubscribe_mode']);
        }else{
            $options['unsubscribe_mode'] = 0;
        }
		$options['goodbye_msg'] = intval($_POST['goodbye_msg']);
		$options['goodbye_msg_admin'] = intval($_POST['goodbye_msg_admin']);
		if( isset($_POST['allow_digests']) ) {
			$options['allow_digests'] = intval($_POST['allow_digests']);
		} else {
			$options['allow_digests'] = 0;
		}

		//tab8
		if( isset($_POST['archive_mode']) ) {
			$options['archive_mode'] = intval($_POST['archive_mode']);
		}else{
            $options['archive_mode'] = 0;
        }
        if( isset($_POST['archive_retention']) ) {
            $options['archive_retention'] = intval($_POST['archive_retention']);
        }else{
            $options['archive_retention'] = 0;
        }
		if( isset($_POST['archive2article']) ) {
			$options['archive2article'] = intval($_POST['archive2article']);
		}
		if( isset( $_POST['archive2article_author'] ) ) {
			$options['archive2article_author'] = intval($_POST['archive2article_author']);
		}
		if( isset( $_POST['archive2article_cat'] ) ) {
			$options['archive2article_cat'] = intval($_POST['archive2article_cat']);
		}
		if( isset( $_POST['archive2article_state'] ) ) {
			$options['archive2article_state'] = intval($_POST['archive2article_state']);
		}

		//tab9
		$options['notify_not_fwd_sender'] = intval($_POST['notify_not_fwd_sender']);
		
		//save or update data
		$saved = $List->saveData($options, sanitize_text_field($_POST['add_list']));
		if ( $saved == null ) { //unsuccessful save
			$message = $this->wpmst_view_message("updated", __("Something went wrong, data not saved. Please try again.", 'wp-mailster'));
		} else { 
			$message = $this->wpmst_view_message("updated", __("Mailing list saved successfully.", 'wp-mailster'));
			$lid = $List->getId();
		}

        for($i=0; $i<100; $i++){ // FIXME hope for the best that we do not have to deal with more than 100 notifys
            $notifyIdInput = 'notifyId' . $i;
            $notifyId = array_key_exists($notifyIdInput, $_POST) ? intval($_POST[$notifyIdInput]) : -1;
            if($notifyId >= 0){
                $triggerTypeInput = 'triggerType'.$i;
                $targetTypeInput = 'targetType'.$i;
                $targetIdInput = 'targetId'.$i;

                $triggerType = intval($_POST[$triggerTypeInput]);
                $targetType = intval($_POST[$targetTypeInput]);
                $targetId = array_key_exists($targetIdInput, $_POST) ? intval($_POST[$targetIdInput]) : 0;

                $notify = $notifyUtils->createNewNotify();
                $notify->id = $notifyId;
                $notify->list_id = $lid;
                $notify->notify_type = MstNotify::NOTIFY_TYPE_LIST_BASED;
                $notify->trigger_type = $triggerType;
                $notify->target_type = $targetType;
                $notify->setTargetId($targetId);
                $log->debug('Save Notify: '.print_r($notify, true));

                $res = $notifyUtils->storeNotify($notify);
            }
        }

	}
}	
$values = null;
$options = $List->getFormData();
if($lid) {
    $title = __("Edit Mailing List", 'wp-mailster') . ' &dash; ' . $options->name;
    $button = __('Update Mailing List', 'wp-mailster');
    $action = 'edit';
}

//load servers 
if ( $lid ) {
	$ServerIn->setId($options->server_inb_id);
	$ServerOut->setId($options->server_out_id);
} else {
	$options->mail_out_user = "";
	$options->mail_out_pw = "";
}

$serverInOptions = $ServerIn->getFormData();
$serverOutOptions = $ServerOut->getFormData();


$userModel = MstFactory::getUserModel();
$groupsModel = MstFactory::getGroupModel();
$notifyUtils 	= MstFactory::getNotifyUtils();
$targetTypes 	= $notifyUtils->getAvailableTargetTypes();
$triggerTypes 	= $notifyUtils->getAvailableTriggerTypes();
$notifies		= $notifyUtils->getNotifiesOfMailingList($lid);
$wpUsers = $userModel->getAllWpUsers();
$groups = $groupsModel->getAllGroups();

$triggerTypeOptionsHtml = '';
foreach($triggerTypes AS $triggerType=>$triggerName){
    $triggerTypeOptionsHtml .= '<option value="'.$triggerType.'">'.$triggerName.'</option>';
}
$targetTypeOptionsHtml = '';
foreach($targetTypes AS $key=>$targetType){
    $targetTypeOptionsHtml .= '<option value="'.$targetType->type.'">'.$targetType->name.'</option>';
}
//$log->debug('list/mst_mailing_list_add.php wpUsers: '.print_r($wpUsers, true));
$wpUserOptionsHtml = '';
foreach($wpUsers AS $key=>$wpUser){
    //$log->debug('list/mst_mailing_list_add.php key: '.$key.', wpUser: '.print_r($wpUser, true));
    $wpUserOptionsHtml .= '<option value="'.$wpUser->uid.'">'.(trim($wpUser->Name) === '' ? $wpUser->display_name : $wpUser->Name).' ('.$wpUser->Email.')</option>';
}
$groupsOptionsHtml = '';
foreach($groups AS $key=>$group){
    $groupsOptionsHtml .= '<option value="'.$group->id.'">'.$group->name.'</option>';
}

for($i=0; $i<count($notifies); $i++){
    $notify = &$notifies[$i];
    $notifyTriggerTypeSelect = '';
    foreach($triggerTypes AS $triggerType=>$triggerName){
        $notifyTriggerTypeSelect .= '<option value="'.$triggerType.'" '. ($notify->trigger_type == $triggerType ? 'selected="selected"' : '').'>'.$triggerName.'</option>';
    }
    $notifyTargetTypeSelect = '';
    foreach($targetTypes AS $key=>$targetType){
        $notifyTargetTypeSelect .= '<option value="'.$targetType->type.'" '. ($notify->target_type == $targetType->type ? 'selected="selected"' : '').'>'.$targetType->name.'</option>';
    }

    $notify->triggerTypes = $notifyTriggerTypeSelect;
    $notify->targetTypes = $notifyTargetTypeSelect;
    if($notify->target_type == MstNotify::TARGET_TYPE_CORE_USER){
        $notifyTargetChoiceSelect = '';
        foreach($wpUsers AS $wpUser){
            $notifyTargetChoiceSelect .= '<option value="'.$wpUser->uid.'" '. ($notify->user_id == $wpUser->uid ? 'selected="selected"' : '').'>'.$wpUser->Name.' ('.$wpUser->Email.')</option>';
        }
        $notify->targetChoice = $notifyTargetChoiceSelect;
    }elseif($notify->target_type == MstNotify::TARGET_TYPE_USER_GROUP){
        $notifyTargetChoiceSelect = '';
        foreach($groups AS $key=>$group){
            $notifyTargetChoiceSelect .= '<option value="'.$group->id.'" '. ($notify->group_id == $group->id ? 'selected="selected"' : '').'>'.$group->name.'</option>';
        }
        $notify->targetChoice = $notifyTargetChoiceSelect;
    }
    $notifies[$i] = $notify;
}

$mstUtils = MstFactory::getUtils();
?>
<script>
var rowCounter = <?php echo count($notifies);	?>;
jQuery(document).ready(function() {
    jQuery("#mst_hide_footer").click(function(){
        if(jQuery(this).hasClass("mst_open_setting")) {
            jQuery(this).text('Show custom footer');
            jQuery(this).removeClass("mst_open_setting");
            jQuery(".mst_custom_footer").hide();
        }else{
            jQuery(this).text('Hide custom footer');
            jQuery(this).addClass("mst_open_setting");
            jQuery(".mst_custom_footer").show();
        }
    });
    jQuery("#mst_hide_header").click(function(){
        if(jQuery(this).hasClass("mst_open_setting")) {
            jQuery(this).text('Show custom header');
            jQuery(this).removeClass("mst_open_setting");
            jQuery(".mst_custom_header").hide();
        }else{
            jQuery(this).text('Hide custom header');
            jQuery(this).addClass("mst_open_setting");
            jQuery(".mst_custom_header").show();
        }
    });
});
function targetTypeChanged(rowNr){
    var targetType = jQuery('#targetType'+rowNr).val();
    if(targetType == 0){ // List administrator
        jQuery('#targetId'+rowNr).children().remove();
        jQuery('#targetId'+rowNr).hide();
    }else if(targetType == 1){ // WP User
        jQuery('#targetId'+rowNr).children().remove();
        jQuery('#copyStationUsers').children().clone().appendTo('#targetId'+rowNr);
        jQuery('#targetId'+rowNr).show();
    }else if(targetType == 2){ // User group
        jQuery('#targetId'+rowNr).children().remove();
        jQuery('#copyStationGroups').children().clone().appendTo('#targetId'+rowNr);
        jQuery('#targetId'+rowNr).show();
    }
}
function archiveModeChanged(archiveModeOn){
    if(archiveModeOn == false){
        //jQuery('input[name="archive2article"]').attr('disabled', 'disabled');
       //jQuery('#archive_offline').attr('disabled', 'disabled');
        jQuery('#archive_retention').attr('disabled', 'disabled');
        //archive2ArticleChanged(false);
    }else{
        //jQuery('input[name="archive2article"]').removeAttr('disabled');
        //jQuery('#archive_offline').removeAttr('disabled');
        jQuery('#archive_retention').removeAttr('disabled');
        //var archive2articleOn = (parseInt($j('input[name="archive2article"]:checked').val()) == 1);
        //archive2ArticleChanged(archive2articleOn);
    }
}

function passwordFieldChanged(fieldAbbrev){
    let fieldContent = false;
    let pwFldId = false;
    let msgFldId = false;
    if(fieldAbbrev === 'in'){
        pwFldId  = "#mail_in_pw";
        msgFldId = "#mail_in_pw_msg";
    }else if(fieldAbbrev === 'out'){
        pwFldId  = "#mail_out_pw";
        msgFldId = "#mail_out_pw_msg";
    }
    if(pwFldId) {
        fieldContent = jQuery(pwFldId).val();
        if (fieldContent) {
            let re = new RegExp('[a-zA-Z0-9!@#\$%\&*/~.:,_]*', 'gi')
            let remainingChars = fieldContent.replace(re, "");
            if (remainingChars && remainingChars.length > 0) {
                jQuery(pwFldId).removeClass('invalidChar').addClass('invalidChar');
                jQuery(msgFldId).html('<?php echo esc_js(__('Some special characters must not be used. Allowed special characters are: @!$%&/~*#.:,_', 'wp-mailster')); ?>')
            }else{
                jQuery(pwFldId).removeClass('invalidChar');
                jQuery(msgFldId).html('')
            }
        }
    }
}

function moderationModeChanged(){
    var moderationModeOff = jQuery('#mod_no_moderation').is(':checked');
    if(moderationModeOff == true){
        jQuery('#mod_approve_recipients').attr('disabled', 'disabled');
        jQuery('#mod_approve_group').attr('disabled', 'disabled');
        jQuery('#mod_approve_group_id').attr('disabled', 'disabled');
        jQuery('#mod_info_sender_moderation').attr('disabled', 'disabled');
        jQuery('#mod_info_sender_approval').attr('disabled', 'disabled');
        jQuery('#mod_info_sender_rejection').attr('disabled', 'disabled');
        jQuery('#mod_moderated_group').attr('disabled', 'disabled');
    }else{
        jQuery('#mod_approve_recipients').removeAttr('disabled');
        jQuery('#mod_approve_group').removeAttr('disabled');
        var moderationApproveGroupChosen = jQuery("#mod_approve_group").is(':checked');
        if(moderationApproveGroupChosen == true){
            jQuery('#mod_approve_group_id').removeAttr('disabled');
        }else{
            jQuery('#mod_approve_group_id').attr('disabled', 'disabled');
        }
        jQuery('#mod_info_sender_moderation').removeAttr('disabled');
        jQuery('#mod_info_sender_approval').removeAttr('disabled');
        jQuery('#mod_info_sender_rejection').removeAttr('disabled');
        var moderationModeMembersOfGroup = jQuery('#mod_for_group').is(':checked');
        if(moderationModeMembersOfGroup == true){
            jQuery('#mod_moderated_group').removeAttr('disabled');
        }else{
            jQuery('#mod_moderated_group').attr('disabled', 'disabled');
        }
    }
}
function addRow(tableId, rows, rowOffset){
    var tblHtml = '';
    var cols;
    var col;
    for (var i = 0; i < rows.length; i++){
        cols = rows[i];
        tblHtml = tblHtml + '<tr id="' + tableId + '_row' + rowOffset + '">';
        for (var j = 0; j < cols.length; j++){
            col = cols[j];
            tblHtml = tblHtml + '<td>' + col + '</td>';
        }
        tblHtml = tblHtml + '</tr>';
    }
    tableId = '#' + tableId + ' > tbody:last';
    jQuery(tableId).append(tblHtml);
}
function removeTableRow(sourceId){
    var buttonNamePattern = 'removeNotifyButton';
    var rowNr = (sourceId).substr(buttonNamePattern.length);
    removeTableRowWithRowNr(rowNr);
}
function removeTableRowWithRowNr(rowNr){
    var notifyId = jQuery('#notifyId' + rowNr).val();
    if(notifyId == 0){
        // not in DB, can be deleted from DOM right away
        jQuery('#notifiesTbl_row' + rowNr).remove();
    }else{
        var ajaxurl = jQuery('#ajax_url').val();
        jQuery.ajax({
            url: ajaxurl,
            type: "POST",
            data: {
                action: 'wpmst_delete_notify',
                notifyId: notifyId,
                rowNr: rowNr
            },
            success:function(data){
                console.log(data);
                var resultObject = jQuery.parseJSON(data);
                console.log(resultObject);
                if(resultObject.res == 'true'){
                    jQuery(('#removeNotifyButtonProgressIndicator'+rowNr)).removeClass('mtrActivityIndicator');
                    alert(<?php echo $mstUtils->jsonEncode(__( 'Deleted Notification', 'wp-mailster' )); ?>);
                    var rowNr = resultObject.rowNr;
                    jQuery('#notifyId' + rowNr).val('0');
                    removeTableRowWithRowNr(rowNr);
                }
            }
        });
    }
}


</script>
<div class="mst_container">
	<div class="wrap">
		<h2><?php echo $title; ?></h2>
		<?php echo (isset($message) && $message!=''?$message:'');?>
		<form action="" method="post">
			<?php wp_nonce_field( 'mst_mailing_list_add' ); ?>
			<div id="tabs">
				<ul>
					<li><a href="#mst_general"><?php _e("General", 'wp-mailster'); ?></a></li>
					<li><a href="#mailbox_settings"><?php _e("Mailbox settings", 'wp-mailster'); ?></a></li>
					<li><a href="#sender_settings"><?php _e("Sender settings", 'wp-mailster'); ?></a></li>
					<li><a href="#mst_mailing_content"><?php _e("Mail content", 'wp-mailster'); ?></a></li>
					<li><a href="#list_behaviour"><?php _e("List behaviour", 'wp-mailster'); ?></a></li>
                    <li><a href="#moderate"><?php _e("Moderation", 'wp-mailster'); ?></a></li>
					<li><a href="#sending_behaviour"><?php _e("Sending behaviour", 'wp-mailster'); ?></a></li>
					<li><a href="#subscribing"><?php _e("Subscribing", 'wp-mailster'); ?></a></li>
					<li><a href="#archiving"><?php _e("Archiving", 'wp-mailster'); ?></a></li>
					<li><a href="#notifications"><?php _e("Notifications", 'wp-mailster'); ?></a></li>
					<li><a href="#tabs-10"><?php _e("Tools", 'wp-mailster'); ?></a></li>
				</ul>
				
				<div id="mst_general" class="mst_listing mst_general">
					<?php require_once($this->WPMST_PLUGIN_DIR.'view/list/tab1.php'); ?>
				</div>		
				<!-- inb server -->
				<div id="mailbox_settings" class="mst_listing">
					<?php require_once($this->WPMST_PLUGIN_DIR.'view/list/tab2.php'); ?>
				</div>
				<!-- out server -->
				<div id="sender_settings" class="mst_listing">
					<?php require_once($this->WPMST_PLUGIN_DIR.'view/list/tab3.php'); ?>
				</div>

				<div id="mst_mailing_content" class="mst_listing mst_mailing_content">
					<?php require_once($this->WPMST_PLUGIN_DIR.'view/list/tab4.php'); ?>
				</div>

				<!-- list behaviour -->
				<div id="list_behaviour" class="mst_listing">
					<?php require_once($this->WPMST_PLUGIN_DIR.'view/list/tab5.php'); ?>
				</div>

                <!-- moderation -->
                <div id="moderate" class="mst_listing">
                    <?php require_once($this->WPMST_PLUGIN_DIR.'view/list/tab_moderate.php'); ?>
                </div>
				
				<!-- sending behaviour -->
				<div id="sending_behaviour" class="mst_listing">
					<?php require_once($this->WPMST_PLUGIN_DIR.'view/list/tab6.php'); ?>
				</div>
				
				<div id="subscribing" class="mst_listing">
					<?php require_once($this->WPMST_PLUGIN_DIR.'view/list/tab7.php'); ?>
				</div>


				<div id="archiving">
					<?php require_once($this->WPMST_PLUGIN_DIR.'view/list/tab8.php'); ?>
				</div>

				<div id="notifications" class="mst_listing">
					<?php require_once($this->WPMST_PLUGIN_DIR.'view/list/tab9.php'); ?>
				</div>
				
				<div id="tabs-10">
					<?php require_once($this->WPMST_PLUGIN_DIR.'view/list/tab10.php'); ?>
				</div>

			</div>
			<input type="hidden" name="add_list" value="<?php echo $action; ?>" />
			<input type="hidden" name="lid" id="lid" value="<?php echo $lid; ?>" />
			<input type="submit" class="button-primary" name="list_action" value="<?php echo $button; ?>">
		</form>		
	</div>
</div>
<script type="text/javascript">
jQuery(document).ready(function() {

    jQuery('#addNotifyButton').click(function () {
        rowCounter++;
        var cols = new Array();
        var buttonId = 'removeNotifyButton' + rowCounter;
        var targetType = 'targetType' + rowCounter;
        <?php
            $targetTypesCleaned = $targetTypeOptionsHtml;
            $targetTypesCleaned = (string)str_replace(array("\r", "\r\n", "\n"), '', $targetTypesCleaned);
            $targetTypesCleaned = (string)str_replace(array("'"), "\'", $targetTypesCleaned);
            
            $triggerTypesCleaned = $triggerTypeOptionsHtml;
            $triggerTypesCleaned = (string)str_replace(array("\r", "\r\n", "\n"), '', $triggerTypesCleaned);
            $triggerTypesCleaned = (string)str_replace(array("'"), "\'", $triggerTypesCleaned);
        ?>
        cols[0] = rowCounter + '*';
        cols[1] = '<select id="triggerType' + rowCounter +'" name="triggerType';
        cols[1] = cols[1] + rowCounter +'" class="triggerTypeClass" style="width:130px;">';
        cols[1] = cols[1] + '<?php echo $triggerTypesCleaned; ?>' + '</select>';
        cols[2] = '<select id="targetType' + rowCounter +'" name="targetType';
        cols[2] = cols[2] + rowCounter +'" class="targetTypeClass" style="width:130px;">';
        cols[2] = cols[2] + '<?php echo $targetTypesCleaned; ?>' + '</select>';
        cols[3] = '<select id="targetId' + rowCounter +'"name="targetId';
        cols[3] = cols[3] + rowCounter +'" class="targetIdClass" style="width:130px;"></select>';
        cols[4] = '<a id="' + buttonId +'" href="#" class="notifierRemoverClass">';
        cols[4] = cols[4] + '<span class="wpmst-dashicons dashicons dashicons-minus">&nbsp;</span>';
        cols[4] = cols[4] + '<?php echo __('Undo Add', 'wp-mailster'); ?>';
        cols[4] = cols[4] + '</a>';
        cols[4] = cols[4] + '<input type="hidden" id="notifyId' + rowCounter +'" name="notifyId' + rowCounter +'" value="0" />';
        var rows = new Array();
        rows[0] = cols;
        addRow('notifiesTbl', rows, rowCounter);
        jQuery('#'+buttonId).click(function(event) {
            removeTableRow(event.target.id);
        });
        jQuery('#'+targetType).change(function(event) {
            var selectNamePattern = 'targetType';
            var rowNr = (this.name).substr(selectNamePattern.length);
            targetTypeChanged(rowNr);
        });
        jQuery('#'+targetType).change();
    });

    jQuery('.targetTypeClass').change(function (event) {
        var selectNamePattern = 'targetType';
        var rowNr = (this.name).substr(selectNamePattern.length);
        targetTypeChanged(rowNr);
    });
    
    jQuery(".notifierRemoverClass").click(function(event){
		removeTableRow(event.target.id);
	});

    jQuery("input[name=mod_mode]").change(function () {
        moderationModeChanged();
    });
    jQuery('#mod_approve_group').change(function() {
        moderationModeChanged();
    });
    jQuery('#mail_in_pw').keyup(function() {
        passwordFieldChanged('in');
    });
    jQuery('#mail_out_pw').keyup(function() {
        passwordFieldChanged('out');
    });

    jQuery("input[name=archive_mode]").change(function() {
        if(parseInt(jQuery("input[name=archive_mode]:checked").val()) > 0){
            archiveModeChanged(false);
        }else{
            archiveModeChanged(true);
        }

    });

    moderationModeChanged();
    window.scrollTo(0, 0); // scroll to top to initialize
});
	
</script>