<?php if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
	die( 'These are not the droids you are looking for.' );
}
?>
<table class="form-table">
	<tbody>
	<?php
	$this->mst_display_input_field( __("Mailing list name", 'wp-mailster'), 'name', $options->name, null, true, false,  __("Choose a unique and presentable name - the name of the mailing list is also used in the frontend.", 'wp-mailster'));
	$this->mst_display_input_field( __("Mailing list address", 'wp-mailster'), 'list_mail', $options->list_mail, null, true, false, __("Email address used for sending emails to the mailing list. Belongs to the mailbox settings defined in the next tab.", 'wp-mailster'));
	$this->mst_display_input_field( __("Mailing list admin email", 'wp-mailster'), 'admin_mail', $options->admin_mail, null, true, false, __("The mailing list administrator is responsible for managing the mailing list. Please provide an existing email address so that the mailing list administrator can get important notifications.", 'wp-mailster'));
	$this->mst_display_truefalse_field( __("Active", 'wp-mailster'), 'active', $options->active, false, __("Determines whether or not the mailing list retrieves the emails in the mailing list's inbox and forwards them to the recipients.", 'wp-mailster'));

    if(MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_FARCHIVE)){
        $this->mst_display_select_field( __("Allowed to access emails in frontend", 'wp-mailster'), 'front_archive_access',
            array(
                0 => __("All users", 'wp-mailster'),
                1 => __("Logged-in users", 'wp-mailster'),
                2 => __("Logged-in subscribers (of the mailing lists)", 'wp-mailster'),
                3 => __("Nobody", 'wp-mailster')
            ),
            $options->front_archive_access,
            false,
            false,
            __("User authorized to access the content of the mailing list emails in the frontend email archives", 'wp-mailster')
        );
    }else{
        $this->mst_display_sometext( __('Allowed to access emails in frontend', 'wp-mailster'), sprintf(__("Available in %s", 'wp-mailster'), MstFactory::getV()->getMinV4Ft(MstVersionMgmt::MST_FT_ID_FARCHIVE)), __('User authorized to access the content of the mailing list emails in the frontend email archives', 'wp-mailster'));
    }
    ?>
	</tbody>
</table>