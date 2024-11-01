<?php if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
	die( 'These are not the droids you are looking for.' );
}
?>
<table class="form-table">
	<tbody>
		<?php 
		$this->mst_display_truefalse_field( __("Use WordPress mailer", 'wp-mailster'), 'use_cms_mailer', $options->use_cms_mailer, false,  __("You can either use the settings of WordPress's global configuration or use a connection to a SMTP server", 'wp-mailster') );
		
		$this->mst_display_select_field( __("Server", 'wp-mailster'), 'server_out_id',
			$ServerOut->getSMTPServers(),
			$options->server_out_id, 
			null,
			false,
			 __("SMTP server", 'wp-mailster')
		);
		$this->mst_display_input_field( __("User/Login", 'wp-mailster'), 'mail_out_user', $options->mail_out_user, null, false, false, __("User that is used to login to the mailbox. Often the email address or the email address without the domain part.", 'wp-mailster') );
		$this->mst_display_password_field( __("Password", 'wp-mailster'), 'mail_out_pw', $options->mail_out_pw, null, false, false, __("Email Password. Allowed special characters: @!$%&/~*#.:,_", 'wp-mailster'), '<span id="mail_out_pw_msg" class="invalidCharMsg"></span>');
		
		?>
		<tr>
			<td colspan="2">
				<a href="#outbox_settings" id="show_settings_out"><?php _e("Show / edit server settings"); ?></a>
				<input type="hidden" name="out_edited" value="0" id="out_edited">
			</td>
		</tr>
		<tr id="outbox_settings" class="">
			<td colspan="2">
				<table>
					<?php
					$this->mst_display_hidden_field('server_name_out', $serverOutOptions->name);
					//$this->mst_display_input_field( __("Name", 'wp-mailster'), 'server_name_out', $serverOutOptions->name, null, false, false, __("A friendly name for your server. This will appear in the dropdown menus", 'wp-mailster') );
					$this->mst_display_input_field( __("Host/Server", 'wp-mailster'), 'server_host_out', $serverOutOptions->server_host, null, false, false, __("Domain/IP of the mail server", 'wp-mailster') );
					$this->mst_display_input_field( __("Port", 'wp-mailster'), 'server_port_out', $serverOutOptions->server_port, null, false, true, __("Port number of the mail service (depends on the protocol)", 'wp-mailster') );
					$this->mst_display_select_field( __("Secure setting", 'wp-mailster'), 'secure_protocol_out',
						array(
							"" => __("None", 'wp-mailster'),
							"ssl" => __("SSL", 'wp-mailster'),
							"tls" => __("TLS", 'wp-mailster')
						),
						$serverOutOptions->secure_protocol,
						null,
						false,
						__("Security settings related to the communication between WP Mailster and the mail server", 'wp-mailster')
					);
					$this->mst_display_truefalse_field( __("Use secure authentication", 'wp-mailster'), 'secure_authentication_out', $serverOutOptions->secure_authentication, false, __("Secure authentication = send encrypted password, must be supported by the mail server", 'wp-mailster') );
					?>
					<tr>
						<td colspan="2" >
							<a href="https://wpmailster.com/doc/mail-provider-settings?utm_source=wpmst&utm_medium=provider&utm_campaign=list-settings" target="_blank">
								<?php _e("Not sure which settings you need? Have a look at what other WP Mailster users have used!", 'wp-mailster'); ?>
							</a>
						</td>
					</tr>
				</table>
			</td>
		</tr>
		<tr class="form-field">
			<td colspan="2">
				<a href="#" id="outboxConnectionCheck2"><span class="dashicons dashicons-update donotshowlink"></span><span class="donotshowlink">&nbsp;</span><?php _e("Test connection", 'wp-mailster'); ?></a>
				<div id="progressIndicator2" style="display:inline; margin:5px; padding-right:20px;min-height:30px;width:30px;">&nbsp;</div>
			</td>
		</tr>
	</tbody>
</table>
<script type="text/javascript">
	jQuery(document).ready(function() {
		if( jQuery('#use_cms_mailerYes').is(":checked") ){
			toggleMailer(true);
		}

		jQuery('#use_cms_mailerYes').click(function () {
			if(this.checked == true){
				toggleMailer(true);
			}
		});
		jQuery('#use_cms_mailerNo').click(function () {
			if(this.checked == true){
				toggleMailer(false);
			}
		});

	});
	function toggleMailer(useCmsMailer){
		if(useCmsMailer == true){
			jQuery('#server_out_id').attr('disabled', 'disabled');
			jQuery('#mail_out_host').attr('disabled', 'disabled');
			jQuery('#mail_out_user').attr('disabled', 'disabled');
			jQuery('#mail_out_pw').attr('disabled', 'disabled');
			jQuery('#mail_out_port').attr('disabled', 'disabled');
			jQuery('#mail_out_use_secure').attr('disabled', 'disabled');
			jQuery('#mail_out_use_sec_auth0').attr('disabled', 'disabled');
			jQuery('#mail_out_use_sec_auth1').attr('disabled', 'disabled');
			jQuery('#outbox_settings').hide();
		}else{
			jQuery('#server_out_id').removeAttr('disabled');
			jQuery('#mail_out_host').removeAttr('disabled');
			jQuery('#mail_out_user').removeAttr('disabled');
			jQuery('#mail_out_pw').removeAttr('disabled');
			jQuery('#mail_out_port').removeAttr('disabled');
			jQuery('#mail_out_use_secure').removeAttr('disabled');
			jQuery('#mail_out_use_sec_auth0').removeAttr('disabled');
			jQuery('#mail_out_use_sec_auth1').removeAttr('disabled');
			if(jQuery('#server_out_id').val() == 0){
				jQuery('#outbox_settings').show();
			}
		}
	}
</script>