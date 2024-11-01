<?php if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
	die( 'These are not the droids you are looking for.' );
}
?>
<table class="form-table">
	<tbody>
	<?php
	$this->mst_display_select_field( __("Server", 'wp-mailster'), 'server_inb_id',
		$ServerIn->getInboxServers(),
		$options->server_inb_id,
		false,
		false,
		__("Inbox server", 'wp-mailster')
	);
	$this->mst_display_input_field( __("User/Login", 'wp-mailster'), 'mail_in_user', $options->mail_in_user, null, false, false, __("User that is used to login to the mailbox. Often the email address or the email address without the domain part.", 'wp-mailster') );
	$this->mst_display_password_field( __("Password", 'wp-mailster'), 'mail_in_pw', $options->mail_in_pw, null, false, false, __("Email Password. Allowed special characters: @!$%&/~*#.:,_", 'wp-mailster'), '<span id="mail_in_pw_msg" class="invalidCharMsg"></span>');
	?>
	<tr>
		<td colspan="2">
			<a href="#inbox_settings" id="show_settings_inb"><?php _e("Show / edit server settings"); ?></a>
			<input type="hidden" name="inb_edited" value="0" id="inb_edited">
		</td>
	</tr>
	<tr id="inbox_settings" class="">
		<td colspan="2">
			<table>
				<?php
				$this->mst_display_hidden_field('server_name_in', $serverInOptions->name);
				//$this->mst_display_input_field( __("Name", 'wp-mailster'), 'server_name_in', $serverInOptions->name, null, false, false, __("A friendly name for your server. This will appear in the dropdown menus", 'wp-mailster') );
				$this->mst_display_input_field( __("Host/Server", 'wp-mailster'), 'server_host_in', $serverInOptions->server_host, null, false, false, __("Domain/IP of the mail server", 'wp-mailster') );
				$this->mst_display_input_field( __("Port", 'wp-mailster'), 'server_port_in', $serverInOptions->server_port, null, false, true, __("Port number of the mail service (depends on the protocol)", 'wp-mailster') );
				$this->mst_display_select_field( __("Protocol", 'wp-mailster'), 'protocol_in',
					array(
						"pop3" => __("POP3", 'wp-mailster'),
						"imap" => __("IMAP", 'wp-mailster'),
						"nntp" => __("NNTP", 'wp-mailster')
					),
					$serverInOptions->protocol,
					null,
					false,
					__("Protocol of the mail server", 'wp-mailster')
				);
				$this->mst_display_select_field( __("Secure setting", 'wp-mailster'), 'secure_protocol_in',
					array(
						"" => __("None", 'wp-mailster'),
						"ssl" => __("SSL", 'wp-mailster'),
						"tls" => __("TLS", 'wp-mailster')
					),
					$serverInOptions->secure_protocol,
					null,
					false,
					__("Security settings related to the communication between WP Mailster and the mail server", 'wp-mailster')
				);
				$this->mst_display_truefalse_field( __("Use secure authentication", 'wp-mailster'), 'secure_authentication_in', $serverInOptions->secure_authentication, false, __("Secure authentication = send encrypted password, must be supported by the mail server", 'wp-mailster') );
				$this->mst_display_input_field( __("Special Parameters", 'wp-mailster'), 'connection_parameter_in', $serverInOptions->connection_parameter, null, false, false, __("Special Parameters (optional), not needed for all mail servers. For example when you use a server with a self signed certificate you have to use the parameter /novalidate-cert to deactivate the certificate check during the connection start.", 'wp-mailster') );
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
			<a href="#" id="inboxConnectionCheck2">
				<span class="dashicons dashicons-update donotshowlink"></span><span class="donotshowlink">&nbsp;</span><?php _e("Test connection", 'wp-mailster'); ?>
				<div id="progressIndicator1" style="display:inline; margin:5px; padding-right:20px;min-height:30px;width:30px;"></div>
			</a>
		</td>
	</tr>
	</tbody>
</table>