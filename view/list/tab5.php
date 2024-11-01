<?php if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
	die( 'These are not the droids you are looking for.' );
}
?>
<table class="form-table">
	<tbody>
		<?php
		$this->mst_display_truefalse_field( __("Mail copy to sender", 'wp-mailster'), 'copy_to_sender', $options->copy_to_sender, false, __("The sender of an email can also get the forwarded email. By receiving the copy he can verify that the email was forwarded successfully to the mailing list.", 'wp-mailster') );
		$this->mst_display_input_field( __("Email Size Limit", 'wp-mailster'), 'mail_size_limit', $options->mail_size_limit, null, false, true,  __("Maximum allowed email size in kByte. If emails are larger than this size they are blocked and not forwarded. 0 and empty field means no size limit - all emails no matter how large are forwarded..", 'wp-mailster') );

        if(MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_FILTER)){
		    $this->mst_display_truefalse_field( __("Email content filtering", 'wp-mailster'), 'filter_mails', $options->filter_mails, false, __("Emails can be filtered based on their content. If one of the words to filter is found, then the email is not forwarded to the mailing list and is shown as filtered in the email archive.", 'wp-mailster') );
        }else{
            $this->mst_display_sometext( __('Email content filtering', 'wp-mailster'), sprintf(__("Available in %s", 'wp-mailster'), MstFactory::getV()->getMinV4Ft(MstVersionMgmt::MST_FT_ID_FILTER)), __('Emails can be filtered based on their content. If one of the words to filter is found, then the email is not forwarded to the mailing list and is shown as filtered in the email archive.', 'wp-mailster'));
        }
		$this->mst_display_truefalse_field( __("Allow bulk messages (not recommended)", 'wp-mailster'), 'allow_bulk_precedence', $options->allow_bulk_precedence, false, __("Messages with the email header Precendence:bulk are not forwarded by default to avoid sending out messages like Out-Of-Office notifications. You can disable the filtering mechanism, but you should only do this when you are fully aware of the potential negative results.", 'wp-mailster'));
		?>
		<tr class="form-field">
			<th scope="row">
				<label for="sending_allowed"><?php _e("Allowed to send/post", 'wp-mailster'); ?></label>
			</th>
			<td>
				<label for="sending_public1">
					<input id="sending_public1" type="radio" name="sending_public" value="1" title="<?php _e('You can either allow everybody to post (every email is forwarded to the mailing list) or you can limit the right to send to certain persons or groups of persons', 'wp-mailster'); ?>" <?php if($options->sending_public != 0) { echo 'checked="checked"'; } ?> >
					<?php _e("Everybody (Public)", 'wp-mailster'); ?>
					<span class="hTip" title="<?php _e("You can either allow everybody to post (every email is forwarded to the mailing list) or you can limit the right to send to certain persons or groups of persons", 'wp-mailster'); ?>">
						<span class="dashicons dashicons-info"></span>
					</span>
				</label>
				<br>
				<label for="sending_public0">
					<input id="sending_public0" type="radio" name="sending_public" value="0" title="<?php _e('You can either allow everybody to post (every email is forwarded to the mailing list) or you can limit the right to send to certain persons or groups of persons', 'wp-mailster'); ?>" <?php if($options->sending_public == 0) { echo 'checked="checked"'; } ?> >
					<?php _e("Restricted, allowed senders", 'wp-mailster'); ?>
				</label>
				<br>
				<ul class="subchoices">
					<li>
						<label for="sending_recipients">
							<input type='checkbox' name='sending_recipients' id="sending_recipients" <?php if($options->sending_recipients) echo 'checked="checked"'; ?> value="1">
							<?php _e("All recipients", 'wp-mailster'); ?>
						</label>
					</li>
					<li>
						<label for="sending_admin">
							<input type='checkbox' name='sending_admin' id="sending_admin" <?php if($options->sending_admin) echo 'checked="checked"'; ?> value="1">
							<?php _e("List administrator", 'wp-mailster'); ?>
						</label>
					</li>
					<li>
						<label for="sending_group">
							<input type='checkbox' name='sending_group' id="sending_group" <?php if($options->sending_group) echo 'checked="checked"'; ?> value="1">
							<?php _e("Group", 'wp-mailster'); ?>
						</label>

						<?php
						include_once $this->WPMST_PLUGIN_DIR."/models/MailsterModelGroup.php";
						$Groups = new Mst_groups();
						$groups = $Groups->getAllGroups();
						if( $groups ) { ?>
						<select name="sending_group_id" id="sending_group_id" >
							<?php foreach ( $groups as $group ) { ?>
							<option value="<?php echo $group->id; ?>" <?php echo ($options->sending_group_id == $group->id?'selected="selected"':''); ?>><?php echo $group->name; ?></option>
							<?php } ?>
						</select>
						<?php } else {
							$this->mst_display_hidden_field("sending_group_id", 0);
                            ?><em><?php echo __('Create groups first', 'wp-mailster'); ?></em><?php
                        }?>
					</li>
				</ul>							
			</td>
		</tr>
	</tbody>
</table>