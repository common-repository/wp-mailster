<?php
// load WP core
require_once(dirname(__FILE__).'/../../../../../wp-load.php');

if( isset($_REQUEST['success']) ) {
	success();
} else {
	failed();
}

function success() {
    $unsubscribeHeader = __('Unsubscription', 'wp-mailster');
    $unsubscribeHeader = apply_filters('wpmailster_unsubscribe_dialog_success_header', $unsubscribeHeader);
    $message = sanitize_text_field($_GET['mes']);
    $message = wp_strip_all_tags($message);
    $message = apply_filters('wpmailster_unsubscribe_dialog_success_message', $message);
    ?>
    <h2 class="componentheading mailsterUnsubscriberHeader"><?php echo esc_html($unsubscribeHeader); ?></h2>
    <div class="contentpane">
        <div id="mailsterContainer">
            <div id="mailsterUnsubscriber" class="mailsterUnsubscriptionSuccess">
                <div id="mailsterUnsubscriberDescription"><?php echo esc_html($message); ?></div>
            </div>
        </div>
    </div>
	<?php
}
function failed() {
    $unsubscribeHeader = __('Unsubscription', 'wp-mailster');
    $unsubscribeHeader = apply_filters('wpmailster_unsubscribe_dialog_failure_header', $unsubscribeHeader);
    $message = sanitize_text_field(htmlspecialchars($_GET['mes'], ENT_QUOTES, 'UTF-8'));
    $message = wp_strip_all_tags($message);
    $message = apply_filters('wpmailster_unsubscribe_dialog_failure_error_msg', $message);
    ?>
    <h2 class="componentheading mailsterUnsubscriberHeader"><?php echo esc_html($unsubscribeHeader); ?></h2>
    <div class="contentpane">
        <div id="mailsterContainer">
            <div id="mailsterUnsubscriber" class="mailsterUnsubscriptionFailed">
                <div id="mailsterUnsubscriberDescription"><?php echo esc_html($message); ?></div>
            </div>
        </div>
    </div>
	<?php
}
