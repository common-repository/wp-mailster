<?php
// load WP core
require_once(dirname(__FILE__).'/../../../../../wp-load.php');

if( isset($_REQUEST['success']) ) {
	success();
} else {
	failed();
}

function success() {
    $subscribeHeader = __('Subscription', 'wp-mailster');
    $subscribeHeader = apply_filters('wpmailster_subscribe_dialog_success_header', $subscribeHeader);
    $subscribeSuccessMessage = __('Subscription successful', 'wp-mailster');
    $subscribeSuccessMessage = apply_filters('wpmailster_subscribe_dialog_success_message', $subscribeSuccessMessage);
	?>
	<h2 class="componentheading mailsterSubscriberHeader"><?php echo esc_html($subscribeHeader); ?></h2>
	<div class="contentpane">
		<div id="mailsterContainer">
			<div id="mailsterSubscriber">

				<div id="mailsterSubscriberDescription" class="success" ><?php echo esc_html($subscribeSuccessMessage); ?></div>
			</div>
		</div>
	</div>
	<?php
}
function failed() {
    $subscribeHeader = __('Subscription', 'wp-mailster');
    $subscribeHeader = apply_filters('wpmailster_subscribe_dialog_failure_header', $subscribeHeader);
    $subscribeFailureMessage = __('Subscription failed', 'wp-mailster');
    $subscribeFailureMessage = apply_filters('wpmailster_subscribe_dialog_failure_message', $subscribeFailureMessage);
    ?>
	<h2 class="componentheading mailsterSubscriberHeader"><?php echo esc_html($subscribeHeader); ?></h2>
	<div class="contentpane">
		<div id="mailsterContainer">
			<div id="mailsterSubscriber">
				<div id="mailsterSubscriberDescription" class="failure" ><?php echo esc_html($subscribeFailureMessage); ?></div>
			</div>
		</div>
	</div>
	<?php
}
