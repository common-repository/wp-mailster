<?php
if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
	die('These are not the droids you are looking for.');
}
$message = "";
$messageType = null;
$log = MstFactory::getLogger();
$mailUtils 	= MstFactory::getMailUtils();
$dateUtils 	= MstFactory::getDateUtils();
$mstUtils 	= MstFactory::getUtils();
$fileUtils	= MstFactory::getFileUtils();
//$mstUtils->addTabs();
//$mstUtils->addTips();

include_once $this->WPMST_PLUGIN_DIR."/models/MailsterModelMail.php";
$sid   = (isset($_GET['sid']) && $_GET['sid']!=''?intval($_GET['sid']):'');
if ( ! $sid ) {
	if ( isset( $_POST['sid'] ) ) {
		$sid = intval($_POST['sid']);
	}
}
$Email = MstFactory::getMailModel();
if($sid) {
	$Email->setId($sid);
}
$data = $Email->getFormData();

$sendEvents = MstFactory::getSendEvents();
$sendreport = $sendEvents->getSendEventsForMail($sid);

$attachUtils = MstFactory::getAttachmentsUtils();
$attachs = $attachUtils->getAttachmentsOfMail($sid);

$mListUtils = MstFactory::getMailingListUtils();
$moderationUtils = MstFactory::getModerationUtils();

$nonce = sanitize_text_field( $_REQUEST['_wpnonce'] );
if( isset( $_GET['action'] ) && $_GET['action'] == "removeRemainingQueueEntries" ) {
	$mailId = $data->id;
	$log->debug('removeRemainingQueueEntries() for mail ID '.$mailId);
	$mailQueue = MstFactory::getMailQueue();
	$noQueueEntriesBefore = $mailQueue->getNumberOfQueueEntriesForMail($mailId);
	$mailQueue->removeAllRecipientsOfMailFromQueue($mailId);
	$noQueueEntriesAfter = $mailQueue->getNumberOfQueueEntriesForMail($mailId);
	$message = sprintf(__('Removed %d queue entries', 'wp-mailster'), $noQueueEntriesBefore);
    $messageType = 'SUCCESS';
	$log->debug('removeRemainingQueueEntries() #Queue Entries before: '.$noQueueEntriesBefore.', after: '.$noQueueEntriesAfter);
}
if( isset( $_GET['action'] ) && $_GET['action'] == "resetErrorCount" ) {
	$mailId = $data->id;
	$log->debug('resetErrorCountContinueSending() for mail ID '.$mailId);
	$mailQueue = MstFactory::getMailQueue();
	$noQueueEntries = $mailQueue->getNumberOfQueueEntriesForMail($mailId);
	$mailQueue->resetMailAsUnblockedAndUnsent($mailId);
	$message = sprintf( __('Error status reset. Continue mail sending to remaining %d recipients in queue.', 'wp-mailster'), $noQueueEntries);
    $messageType = 'SUCCESS';
}

if($data){
    $listUtils = MstFactory::getMailingListUtils();
    $mList = $listUtils->getMailingList($data->list_id);
?>
	<script language="javascript" type="text/javascript">
		var $j = jQuery.noConflict();
		$j(document).ready(function(){
			prepareTabs();
			prepareTips();
		});
	</script>
	<h2><?php _e('Email Details', 'wp-mailster'); ?></h2>
	<?php
    if($message && strlen($message)>0){
        if(is_null($messageType)){
            $messageType = 'INFO';
        }
        ?>
        <div class="notice notice-<?php echo strtolower($messageType); ?> is-dismissible">
            <p><?php echo $message; ?></p>
        </div>
        <?php
    }?>
	<p>
		<?php
		$mailQueue = MstFactory::getMailQueue();
		$noQueueEntries = $mailQueue->getNumberOfQueueEntriesForMail($data->id);
        $reset_error_cr_nonce = wp_create_nonce( 'mst_reset_error_cr' );
        $remove_remain_queue_nonce = wp_create_nonce( 'mst_remove_remain_queue' );
		if($noQueueEntries) {
			$mailsExceededSendAttempts = $mailQueue->getPendingMailsThatExceededMaxSendAttempts($data->list_id);
			foreach($mailsExceededSendAttempts AS $mailExceededSA){
                if($mailExceededSA->id == $data->id) { ?>
                    <a class="button" href="?page=mst_queued&subpage=details&action=resetErrorCount&_wpnonce=<?php echo $reset_error_cr_nonce; ?>&sid=<?php echo $data->id; ?>"><?php _e('Reset send errors, continue sending', 'wp-mailster'); ?></a>
                <?php
                    break;
                }
			} ?>
			<a class="button" href="?page=mst_queued&subpage=details&action=removeRemainingQueueEntries&_wpnonce=<?php echo $remove_remain_queue_nonce; ?>&sid=<?php echo $data->id; ?>"><?php _e('Remove remaining queue entries', 'wp-mailster'); ?></a>
<?php   }else{
            // no more queue entries left
            if(($data->fwd_completed < 1) && ($data->fwd_errors > 0) && ($data->fwd_errors >= $mList->max_send_attempts)){
                // weird situation: forwarding is not set completed, there were more send errors than allowed send attempts, so email is not marked as completed (because no longer in send queue scope)
                // so just offer the button to reset the send errors
                ?>
                <a class="button" href="?page=mst_queued&subpage=details&action=resetErrorCount&_wpnonce=<?php echo $reset_error_cr_nonce; ?>&sid=<?php echo $data->id; ?>"><?php _e('Reset send errors, continue sending', 'wp-mailster'); ?></a>
                <?php
            }
        }

        if($data->moderation_status == MstConsts::MAIL_FLAG_MODERATED_IN_MODERATION){
            $mod_approve_nonce = wp_create_nonce( 'mst_moderate_approve_archived' );
            $mod_reject_nonce = wp_create_nonce( 'mst_moderate_reject_archived' );
            $approveLink = sprintf( '<a class="button" href="?page=mst_archived&action=moderate_approve&sid=%s&_wpnonce=%s"><strong>' . __('Approve', 'wp-mailster') . '</strong></a>', absint( $data->id ), $mod_approve_nonce );
            $rejectLink = sprintf( '<a class="button" href="?page=mst_archived&action=moderate_reject&sid=%s&_wpnonce=%s"><strong>' . __('Reject', 'wp-mailster') . '</strong></a>', absint( $data->id ), $mod_reject_nonce );
            echo __('Moderation', 'wp-mailster').' '.$approveLink.' '.$rejectLink;
        }

     ?>
	</p>
	<table cellspacing="0" cellpadding="0" border="0" width="100%" class="form-table">
		<tr>
			<td style="width:150px;text-align:right;"><label><?php _e( 'Sender address', 'wp-mailster' ); ?>:</label></td>
			<td style="width:500px;"><?php echo $data->from_name . '&nbsp; &lt;' . $data->from_email . '&gt;'; ?></td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td style="text-align:right;"><label><?php _e( 'Subject', 'wp-mailster' ); ?>:</label></td>
			<td style=""><strong><?php echo $data->subject; ?></strong></td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td style="text-align:right;"><label><?php _e( 'Date', 'wp-mailster' ); ?>:</label></td>
			<td style=""><?php echo ($data->receive_timestamp ? ($dateUtils->formatDateAsConfigured($data->receive_timestamp) . ' (' . __( 'Forward completed', 'wp-mailster' ).': ' . ($data->fwd_completed == '1' ? __( 'Yes', 'wp-mailster' ) . ' - ' . $dateUtils->formatDateAsConfigured($data->fwd_completed_timestamp) : __( 'No', 'wp-mailster' )) . ')') : ''); ?></td>
			<td>&nbsp;</td>
        </tr><?php
        if($data->moderation_status != MstConsts::MAIL_FLAG_MODERATED_NOT_MODERATED){
            ?>
            <tr>
                <td style="text-align:right;"><label><?php _e( 'Moderation Status', 'wp-mailster' ); ?>:</label></td>
                <td style=""><?php
                    if($data->moderation_status == MstConsts::MAIL_FLAG_MODERATED_APPROVED){
                        echo __( 'Approved', 'wp-mailster' );
                    }elseif($data->moderation_status == MstConsts::MAIL_FLAG_MODERATED_REJECTED){
                        echo __( 'Rejected', 'wp-mailster' );
                    }elseif($data->moderation_status == MstConsts::MAIL_FLAG_MODERATED_IN_MODERATION){
                        echo __( 'In moderation', 'wp-mailster' );
                    }
                    ?></td>
                <td>&nbsp;</td>
            </tr>
        <?php
        }
		if($data->size_in_bytes > 0){
			?>
			<tr>
				<td style="text-align:right;"><label><?php _e( 'Size', 'wp-mailster' ); ?>:</label></td>
				<td style=""><?php echo $fileUtils->getFileSizeStringForSizeInBytes($data->size_in_bytes); ?></td>
				<td>&nbsp;</td>
			</tr>
			<?php
		}
		?>
		<?php
		if($data->fwd_errors > 0){
			?>
			<tr>
				<td style="text-align:right;"><label><?php _e( 'Forward errors', 'wp-mailster' ); ?>:</label></td>
				<td style="color:red;"><?php _e( 'Yes', 'wp-mailster' ); echo ' (' . $data->fwd_errors . ')';?></td>
				<td>&nbsp;</td>
			</tr>
			<?php
		}
		$mailQueue = MstFactory::getMailQueue();
		$noQueueEntries = $mailQueue->getNumberOfQueueEntriesForMail($sid);
		$queueEntriesLink = "?page=mst_queued&mailId=".$data->id;
		?>
		<tr>
			<td style="text-align:right;"><label><?php _e( 'Mail Queue Entries', 'wp-mailster' ); ?>:</label></td>
			<td><?php echo ($noQueueEntries ? '<a href="'.$queueEntriesLink.'" target="_blank">'. __( 'Yes', 'wp-mailster' ) . ' (' . $noQueueEntries . ')'.'</a>' : __( 'No', 'wp-mailster' )); ?></td>
			<td>&nbsp;</td>
		</tr>
		<tr>
			<td style="text-align:right;vertical-align:top;"><label><?php _e( 'Original Header', 'wp-mailster' ); ?>:</label></td>
			<td style="padding:0;"><table><?php
					if(!is_null($data->orig_to_recips) && (strlen($data->orig_to_recips) > 0)){
						?><tr><th><?php _e( 'TO', 'wp-mailster' ); ?></th><td><?php
							$toRecips = $mstUtils->jsonDecode($data->orig_to_recips);
							foreach($toRecips As $k=>$toRecip){
								if($k > 0){	echo ', '; }
								echo htmlentities((strlen($toRecip->name)>0? $toRecip->name.' ' : ''). '<'.$toRecip->email.'>');
							}
							?></td></tr><?php
					}
					if(!is_null($data->orig_cc_recips) && (strlen($data->orig_cc_recips) > 0)){
						?><tr><th><?php _e( 'CC', 'wp-mailster' ); ?></th><td><?php
							$ccRecips = $mstUtils->jsonDecode($data->orig_cc_recips);
							foreach($ccRecips As $k=>$ccRecip){
								if($k > 0){	echo ', '; }
								echo htmlentities((strlen($ccRecip->name)>0? $ccRecip->name.' ' : ''). '<'.$ccRecip->email.'>');
							}
							?></td></tr><?php
					}
					?></table></td>
			<td>&nbsp;</td>
		</tr>
        <?php if($mList){ ?>
        <tr>
            <td style="text-align:right;"><label><?php _e( 'Mailing List', 'wp-mailster' ); ?>:</label></td>
            <td><?php echo $mList->name.' ('.$mList->list_mail.')'; ?></td>
            <td>&nbsp;</td>
        </tr>
		<?php
        }
		if($data->has_attachments == '1'){
			?>
			<tr>
				<td style="text-align:right;"><label><?php _e( 'Attachments', 'wp-mailster' ); ?>:</label></td>
				<td style="">
					<?php
					$attachStr = '';
					for($i=0; $i < count($attachs); $i++){
						$attach = &$attachs[$i];
						if($attach->disposition == MstConsts::DISPOSITION_TYPE_ATTACH){
                            $dwlLinkNonce = wp_create_nonce( 'mst_dwl_attachment'.intval($attach->id) );
                            $dwlLink = admin_url('admin.php?mst_download=attachment&id='.(int)$attach->id.'&_wpnonce='.$dwlLinkNonce.'&b=1');
							$attachStr = $attachStr . '<a href="' . $dwlLink . '" >' . rawurldecode($attach->filename) . '</a><br/>';
						}
					}
					echo $attachStr;
					?>
				</td>
				<td>&nbsp;</td>
			</tr>
			<?php
		}
		?>
		<tr>
			<td style="text-align:right;"><label>&nbsp;</label></td>
			<td style="width:100%;">
				<div id="tabs">
					<ul>
						<li>
							<a href="#first" ><?php _e( 'Body', 'wp-mailster' ); ?></a></li>
						<?php
						if($data->has_send_report){ // has email a send report attached?
							?>
							<li><a href="#second" ><?php _e( 'Send report', 'wp-mailster' ); ?></a></li>
							<?php
						}else{
							$title = __( 'No send report stored for this email', 'wp-mailster' );
							?>
							<li style="color:darkgrey; padding:0 15px;" title="<?php echo $title; ?>" ><?php _e( 'Send report', 'wp-mailster' ); ?></li>
							<?php
						}
						?>
					</ul>
					<div style="display: none; padding-left:20px; padding-top:5px;" id="first" class="tabDiv">
						<div style="width:99%; border:1px solid darkgrey; background-color:white; color:black;">
							<?php
							if(is_null($data->html) || strlen(trim($data->html))<1){
								$content = nl2br(htmlentities($data->body));
							}else{
								$content = $mailUtils->replaceContentIdsWithAttachments($data->html, $attachs);
							}
							echo $content;
							?>
						</div>
					</div>
					<div style="display: none; padding-left:20px; padding-top:5px;" id="second" class="tabDiv">
						<div style="width:1000px; border:1px solid darkgrey;">
							<table>
								<tr>
									<th width="130px"><?php _e( 'Date', 'wp-mailster' ); ?></th>
									<th width="20px">&nbsp;</th>
									<th width="200px"><?php _e( 'Event', 'wp-mailster' ); ?></th>
									<th width="450px"><?php _e( 'Description', 'wp-mailster' ); ?></th>
									<th width="200px"></th>
								</tr>
								<?php

								foreach($sendreport AS $event){
									?>
									<tr>
										<td width="130px" title="<?php esc_attr_e($event->event_time); ?>"><?php esc_html_e($dateUtils->formatDateAsConfigured($event->event_time)); ?></td>
										<td width="20px"><img src="<?php echo $event->imgPath; ?>" /></td>
										<td width="200px"><?php echo $event->name; ?></td>
										<td width="450px"><?php echo $event->desc; ?></td>
										<td width="200px"><?php
											if($event->recips != null){
												if(property_exists($event->recips, 'to') && count($event->recips->to) > 0){
													echo '<img src="'.$event->recips->toImg.'" title="' . $event->recips->toStr . '" class="hTipWI" style="margin:3px"/> ';
												}
												if(property_exists($event->recips, 'cc') && count($event->recips->cc) > 0){
													echo '<img src="'.$event->recips->ccImg.'" title="' . $event->recips->ccStr . '" class="hTipWI" style="margin:3px"/> ';
												}
												if(property_exists($event->recips, 'bcc') && count($event->recips->bcc) > 0){
													echo '<img src="'.$event->recips->bccImg.'" title="' . $event->recips->bccStr . '" class="hTipWI" style="margin:3px"/> ';
												}
											}
											?></td>
									</tr>
									<?php
								}
								?>
							</table>
						</div>
					</div>
				</div>
			</td>
			<td>&nbsp;</td>
		</tr>
	</table>
<?php
}else{
    echo '<strong style="color:red">Email ID '.$sid. ' not found!</strong>';
}