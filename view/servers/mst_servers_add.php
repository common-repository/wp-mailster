<?php
if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
	die( 'These are not the droids you are looking for.' );
}
require_once $this->WPMST_PLUGIN_DIR."/models/MailsterModelList.php";
include_once $this->WPMST_PLUGIN_DIR."/models/MailsterModelServer.php";

$sid   = (isset($_GET['sid']) && $_GET['sid']!=''?intval($_GET['sid']):'');

if ( ! $sid ) {
	if ( isset( $_POST['sid'] ) ) {
		$sid = intval($_POST['sid']);
	}
}

$title  = __("New Mail Server", 'wp-mailster');
$button = __('Add Mail Server', 'wp-mailster');
$action = 'add';
$message = "";
$options = array();

// add-server_

$Server = null;
if($sid) {
	$Server = new MailsterModelServer($sid);
} else {
	$Server = new MailsterModelServer();
}
$log = MstFactory::getLogger();

if( isset( $_POST['server_action'] ) ) { //if form is submitted	
	if ( isset( $_POST['add_server'] ) ) {
        $nonce = sanitize_text_field( $_REQUEST['_wpnonce'] );
        if ( ! wp_verify_nonce( $nonce, 'mst_add_server' ) ) {
            die( 'Stop CSRF!' );
        }
        $log->debug('mst_servers_add->add_server nonce: '.$nonce.' ok');
		if( $_POST['sid'] ) {
			$server_options['id'] = intval($_POST['sid']);
		}
		if( isset( $_POST['server_type'] ) ) {
			$server_options['server_type'] = intval($_POST['server_type']);
		} else {
			$server_options['server_type'] = "";
		}
		if( isset( $_POST['server_name'] ) ) {
			$server_options['name'] = sanitize_text_field($_POST['server_name']);
		} else {
			$server_options['name'] = "New server";
		}
		if( isset( $_POST['server_host'] ) ) {
			$server_options['server_host'] = sanitize_text_field($_POST['server_host']);
		} else {
			$server_options['server_host'] = "";
		}
		if( isset( $_POST['server_port'] ) ) {
			$server_options['server_port'] = intval($_POST['server_port']);
		} else {
			$server_options['server_port'] = "";
		}
		if( isset( $_POST['protocol'] ) ) {
			$server_options['protocol'] = sanitize_text_field($_POST['protocol']);
		} else {
			$server_options['protocol'] = "SMTP";
		}
		if( isset( $_POST['secure_protocol'] ) && $_POST['secure_protocol'] != 'none') {
			$server_options['secure_protocol'] = sanitize_text_field($_POST['secure_protocol']);
		} else {
			$server_options['secure_protocol'] = "none";
		}
		if( isset( $_POST['secure_authentication'] ) ) {
			$server_options['secure_authentication'] = intval($_POST['secure_authentication']);
		} else {
			$server_options['secure_authentication'] = 0;
		}
		if( isset( $_POST['connection_parameter'] ) ) {
			$server_options['connection_parameter'] = sanitize_text_field($_POST['connection_parameter']);
		} else {
			$server_options['connection_parameter'] = "";
		}
		$server_options['published'] = 1;
        $server_options['user_edited'] = 1; // if we save it here, it has to be edited by user
		$Server->saveData($server_options, sanitize_text_field($_POST['add_server']));
		$sid = $Server->getId();
	}
}	
$values = null;
if($sid) {
	$title = __("Edit Mail Server", 'wp-mailster');
	$button = __('Update Mail Server', 'wp-mailster');
	$action = 'edit'; 
}
$options = $Server->getFormData();
if( ! isset($options->id) ) {
	$options->id = "";
}

?>
<div class="mst_container">
	<div class="wrap">
		<h2><?php echo $title; ?></h2>
		<?php echo (isset($message) && $message!=''?$message:'');?>
		<form action="" method="post">
			<?php wp_nonce_field( 'mst_add_server' ); ?>
			<table class="form-table">
				<tbody>
					<?php
					$this->mst_display_hidden_field("sid", $options->id);
					$serverTypes = array( MstConsts::SERVER_TYPE_MAIL_INBOX => __("Inbox Server", 'wp-mailster'), MstConsts::SERVER_TYPE_SMTP => __("SMTP Server", 'wp-mailster') ) ;
					$this->mst_display_select_field(__("Server Type", 'wp-mailster'), "server_type", $serverTypes, $options->server_type, null, false, null);
					$this->mst_display_hidden_field('server_name', $options->name);
					$this->mst_display_input_field( __("Host/Server", 'wp-mailster'), 'server_host', $options->server_host, null, false, false, __("Domain/IP of the mail server", 'wp-mailster') );
					$this->mst_display_input_field( __("Port", 'wp-mailster'), 'server_port', $options->server_port, null, false, true, __("Port number of the mail service (depends on the protocol)", 'wp-mailster') );
					$this->mst_display_select_field( __("Secure setting", 'wp-mailster'), 'secure_protocol',
						array(
							"" => __("None", 'wp-mailster'),
							"ssl" => __("SSL", 'wp-mailster'),
							"tls" => __("TLS", 'wp-mailster')
						),
						$options->secure_protocol,
						null,
						false,
						__("Security settings related to the communication between WP Mailster and the mail server", 'wp-mailster')
					);
					$this->mst_display_truefalse_field( __("Use secure authentication", 'wp-mailster'), 'secure_authentication', $options->secure_authentication, false, __("Secure authentication = send encrypted password, must be supported by the mail server", 'wp-mailster') );
					if($options->server_type != "1") { //not an SMTP server
					?>
					<tr id="inbox_settings" <?php if ( MstConsts::SERVER_TYPE_MAIL_INBOX == $options->secure_protocol || ! isset( $options->secure_protocol ) ) { echo "style='display:table-row;'"; } ?>>
						<td colspan="2" >							
							<table>						
								<?php						
								$this->mst_display_select_field( __("Protocol", 'wp-mailster'), 'protocol',
									array(
										"pop3" => __("POP3", 'wp-mailster'),
										"imap" => __("IMAP", 'wp-mailster'),
										"nntp" => __("NNTP", 'wp-mailster')
									),
									$options->protocol,
									null,
									false,
									__("Protocol of the mail server", 'wp-mailster')
								);
								$this->mst_display_input_field( __("Special Parameters", 'wp-mailster'), 'connection_parameter', $options->connection_parameter, null, false, false, __("Special Parameters (optional), not needed for all mail servers. For example when you use a server with a self signed certificate you have to use the parameter /novalidate-cert to deactivate the certificate check during the connection start.", 'wp-mailster') );
								?>							
							</table>
						</td>
					</tr>
					<?php } ?>
					<tr>
						<td colspan="2" >
							<a href="https://wpmailster.com/doc/mail-provider-settings?utm_source=wpmst&utm_medium=provider&utm_campaign=list-settings" target="_blank">
								<?php _e("Not sure which settings you need? Have a look at what other WP Mailster users have used!", 'wp-mailster'); ?>
							</a>
						</td>
					</tr>
				</tbody>
			</table>
		    <input type="hidden" name="add_server" value="<?php echo $action; ?>" />
		    <input type="hidden" name="sid" value="<?php echo $sid; ?>" />
            <input type="submit" class="button-primary" name="server_action" value="<?php echo $button; ?>">
        </form>

	<?php 
	$lists = $Server->getLists();		
	if ( $lists ) {
	?>
	<h4><?php _e("Sever used for the following mailing lists", 'wp-mailster'); ?></h4>
	<table>
		<tr>
			<th><?php _e("Number", 'wp-mailster'); ?></th>
			<th><?php _e("List Name", 'wp-mailster'); ?></th>
		</tr>
		<?php
		$i = 1;
		foreach ( $lists as $list ) { 
			$edit_nonce = wp_create_nonce( 'mst_edit_list' );
		?>
		<tr>
			<td><?php echo $i; ?></td>
			<td>
				<a href="?page=mst_mailing_list_add&amp;list_action=edit&amp;lid=<?php echo $list->id; ?>&amp;_wpnonce=<?php echo $edit_nonce; ?>">
					<?php echo $list->name; ?>
				</a>
			</td>
		</tr>
			<?php 
			$i++;
		}
		?>
	</table>
	<?php } else { ?>
	<p><?php _e("Not used in any lists.", 'wp-mailster'); ?></p>
	<?php } ?>
	
	</div>
</div>