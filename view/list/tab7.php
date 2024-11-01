<?php if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
	die( 'These are not the droids you are looking for.' );
}
?>
<table class="form-table">
	<tbody>
		<?php
		$this->mst_display_truefalse_field( __("Allow subscription", 'wp-mailster'), 'allow_subscribe', $options->allow_subscribe, false, __("Determines whether it is possible for a user to (self-)subscribe to a mailing list through a subscribe form. If inactive users can only be added by administrators using WP Mailster's mailing list recipient management.", 'wp-mailster'));
		$this->mst_display_truefalse_field( __("Public subscription", 'wp-mailster'), 'public_registration', $options->public_registration, false, __("When active, unregistered users can also subscribe to the mailing list. Prerequisite: subscription is allowed.", 'wp-mailster'));
        if(MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_DBL_OPT)){
            $this->mst_display_truefalse_field( __("Use Double Opt-In subscribe", 'wp-mailster'), 'subscribe_mode', $options->subscribe_mode, false, __("The Double Opt-In mode requires the confirmation of the subscription (by clicking a link in an email). This ensures that no person can subscribe someone else out of malice or error.", 'wp-mailster'));
        }else{
            $this->mst_display_sometext( __('Use Double Opt-In subscribe', 'wp-mailster'), sprintf(__("Available in %s", 'wp-mailster'), MstFactory::getV()->getMinV4Ft(MstVersionMgmt::MST_FT_ID_DBL_OPT)), __('The Double Opt-In mode requires the confirmation of the subscription (by clicking a link in an email). This ensures that no person can subscribe someone else out of malice or error.', 'wp-mailster'));
        }
		$this->mst_display_truefalse_field( __("Send welcome message on subscribe", 'wp-mailster'), 'welcome_msg', $options->welcome_msg, false, __("Subscriber get a welcome message when they subscribe - not when subscribers are added by an administrator", 'wp-mailster'));
		$this->mst_display_truefalse_field( __("Send welcome message when added in backend", 'wp-mailster'), 'welcome_msg_admin', $options->welcome_msg_admin, false, __("When an administrator adds subscribers in the backend they also receive a welcome message", 'wp-mailster'));

        $this->mst_display_spacer();

		$this->mst_display_truefalse_field( __("Allow unsubscription", 'wp-mailster'), 'allow_unsubscribe', $options->allow_unsubscribe, false, __("Determines whether it is possible for a user to (self-)unsubscribe from a mailing list through an unsubscribe form. If inactive users can only be removed by administrators using WP Mailster's mailing list recipient management.", 'wp-mailster'));
        if(MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_DBL_OPT)){
		    $this->mst_display_truefalse_field( __("Use Double Opt-In unsubscribe", 'wp-mailster'), 'unsubscribe_mode', $options->unsubscribe_mode, false, __("The Double Opt-In mode requires the confirmation of the unsubscription (by clicking a link in an email). This ensures that no person can unsubscribe someone else out of malice or error.", 'wp-mailster'));
        }else{
            $this->mst_display_sometext( __('Use Double Opt-In unsubscribe', 'wp-mailster'), sprintf(__("Available in %s", 'wp-mailster'), MstFactory::getV()->getMinV4Ft(MstVersionMgmt::MST_FT_ID_DBL_OPT)), __('The Double Opt-In mode requires the confirmation of the unsubscription (by clicking a link in an email). This ensures that no person can unsubscribe someone else out of malice or error.', 'wp-mailster'));
        }
		$this->mst_display_truefalse_field( __("Send goodbye message on unsubscribe", 'wp-mailster'), 'goodbye_msg', $options->goodbye_msg, false, __("Unsubscribers get a goodbye message when they unsubscribe - not when subscribers are removed by an administrator", 'wp-mailster'));
		$this->mst_display_truefalse_field( __("Send goodbye message when removed in backend", 'wp-mailster'), 'goodbye_msg_admin', $options->goodbye_msg_admin, false, __("When an administrator removes subscribers through the backend, the can get a goodbye message", 'wp-mailster'));

        $this->mst_display_spacer();

        if(MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_DIGEST)){
		    $this->mst_display_truefalse_field( __("Allow digests", 'wp-mailster'), 'allow_digests', $options->allow_digests, false, __("When activated, subscriber can decide whether they want to receive the messages in digests (daily/weekly/monthly)", 'wp-mailster'));
        }else{
            $this->mst_display_sometext( __('Allow digests', 'wp-mailster'), sprintf(__("Available in %s", 'wp-mailster'), MstFactory::getV()->getMinV4Ft(MstVersionMgmt::MST_FT_ID_DIGEST)), __('When activated, subscriber can decide whether they want to receive the messages in digests (daily/weekly/monthly)', 'wp-mailster'));
        }
		?>
	</tbody>
</table>