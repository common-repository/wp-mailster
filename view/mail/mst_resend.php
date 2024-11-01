<?php
if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
	die('These are not the droids you are looking for.');
}
$model = MstFactory::getMailModel ();
$listsModel = MstFactory::getListsModel();

$eid = wp_parse_id_list($_REQUEST['eid']);
$mails = array();
if( is_array( $eid) ) {
	foreach($eid as $emailid) {
		$mailModel = MstFactory::getMailModel();
		$mailForResend = $mailModel->getData($emailid);
		if(intval($mailForResend->no_content) == 0){
            $mails[] = $mailForResend; // only add emails for resend where content is (still) present
        }
	}
} else {
	$mailModel = new MailsterModelMail();
	$mails[0] = $mailModel->getData($eid);
}
$lists = $listsModel->getData();
?>
<form enctype="multipart/form-data" method="post" name="adminForm" id="adminForm">
    <input type="hidden" name="resend-selection-done" value="1" />
	<table class="adminform">
		<tr>
			<th><?php _e( 'Emails to resend', 'wp-mailster' ); echo ' (' . count( $mails ) . ')'; ?></th>
			<th><?php _e( 'Choose target mailing lists', 'wp-mailster' ); ?></th>
		</tr>
		<tr>
			<td width="310px" style="vertical-align:top;">
				<table width="300px">
					<?php
					for($i=0, $n=count( $mails ); $i < $n; $i++) {
						$mail = &$mails[$i];
						?>
						<tr>
							<td><?php echo ($i+1); ?></td>
							<td>
								<input type="hidden" name="mails[]" value="<?php echo $mail->id; ?>" />
								<?php echo $mail->subject; ?>
							</td>
						</tr>
						<?php
					}
					?>
				</table>
			</td>
			<td width="210px" style="vertical-align:top;">
				<select id="targetLists" name="targetLists[]" multiple size="10" style="width:200px">
					<?php
					for($i=0, $n=count( $lists ); $i < $n; $i++) {
						$list = &$lists[$i];
						?>
						<option value="<?php echo $list->id; ?>"><?php echo $list->name; ?></option>
						<?php
					}
					?>
				</select>
			</td>
			<td width="100px" style="vertical-align:top;">
				<input type="submit" value="<?php _e( 'Resend', 'wp-mailster' ); ?>" class="submitButton"
				       title="<?php _e( 'Resend emails to recipients of the selected mailing lists', 'wp-mailster' ); ?>" />
			</td>
			<td>&nbsp;</td>
		</tr>
	</table>
</form>