<?php
	if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
		die( 'These are not the droids you are looking for.' );
	}

	// If uninstall is not called from WordPress, exit
	if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
		exit();
	}


	if(get_option('wpmst_cfg_uninstall_delete_data')) {
		global $wpdb;
		$wpdb->query( "DROP TABLE IF EXISTS `" . $wpdb->prefix . "mailster_attachments`" );
		$wpdb->query( "DROP TABLE IF EXISTS `" . $wpdb->prefix . "mailster_digests`" );
		$wpdb->query( "DROP TABLE IF EXISTS `" . $wpdb->prefix . "mailster_digest_queue`" );
		$wpdb->query( "DROP TABLE IF EXISTS `" . $wpdb->prefix . "mailster_groups`" );
		$wpdb->query( "DROP TABLE IF EXISTS `" . $wpdb->prefix . "mailster_group_users`" );
		$wpdb->query( "DROP TABLE IF EXISTS `" . $wpdb->prefix . "mailster_lists`" );
		$wpdb->query( "DROP TABLE IF EXISTS `" . $wpdb->prefix . "mailster_list_groups`" );
		$wpdb->query( "DROP TABLE IF EXISTS `" . $wpdb->prefix . "mailster_list_members`" );
		$wpdb->query( "DROP TABLE IF EXISTS `" . $wpdb->prefix . "mailster_list_stats`" );
		$wpdb->query( "DROP TABLE IF EXISTS `" . $wpdb->prefix . "mailster_log`" );
		$wpdb->query( "DROP TABLE IF EXISTS `" . $wpdb->prefix . "mailster_mails`" );
		$wpdb->query( "DROP TABLE IF EXISTS `" . $wpdb->prefix . "mailster_notifies`" );
		$wpdb->query( "DROP TABLE IF EXISTS `" . $wpdb->prefix . "mailster_oa_attachments`" );
		$wpdb->query( "DROP TABLE IF EXISTS `" . $wpdb->prefix . "mailster_oa_mails`" );
		$wpdb->query( "DROP TABLE IF EXISTS `" . $wpdb->prefix . "mailster_queued_mails`" );
		$wpdb->query( "DROP TABLE IF EXISTS `" . $wpdb->prefix . "mailster_send_reports`" );
		$wpdb->query( "DROP TABLE IF EXISTS `" . $wpdb->prefix . "mailster_servers`" );
		$wpdb->query( "DROP TABLE IF EXISTS `" . $wpdb->prefix . "mailster_subscriptions`" );
		$wpdb->query( "DROP TABLE IF EXISTS `" . $wpdb->prefix . "mailster_threads`" );
		$wpdb->query( "DROP TABLE IF EXISTS `" . $wpdb->prefix . "mailster_users`" );

        unregister_setting( 'wp_mailster_settings', 'wpmst_cfg_mailster_db_version' );
		unregister_setting( 'wp_mailster_settings', 'wpmst_cfg_license_key' );
		unregister_setting( 'wp_mailster_settings', 'wpmst_cfg_current_version' );
		unregister_setting( 'wp_mailster_settings', 'wpmst_cfg_version_license' );
		unregister_setting( 'wp_mailster_settings', 'wpmst_cfg_uninstall_delete_data' );
		unregister_setting( 'wp_mailster_settings', 'wpmst_cfg_allow_send' );
		unregister_setting( 'wp_mailster_settings', 'wpmst_cfg_cron_job_key' );
		unregister_setting( 'wp_mailster_settings', 'wpmst_cfg_undo_line_wrapping' );
		unregister_setting( 'wp_mailster_settings', 'wpmst_cfg_logging_level' );
		unregister_setting( 'wp_mailster_settings', 'wpmst_cfg_mail_date_format' );
		unregister_setting( 'wp_mailster_settings', 'wpmst_cfg_mail_date_format_without_time' );
		unregister_setting( 'wp_mailster_settings', 'wpmst_cfg_add_reply_prefix' );
		unregister_setting( 'wp_mailster_settings', 'wpmst_cfg_reply_prefix' );
		unregister_setting( 'wp_mailster_settings', 'wpmst_cfg_trigger_source' );
		unregister_setting( 'wp_mailster_settings', 'wpmst_cfg_mail_from_field' );
		unregister_setting( 'wp_mailster_settings', 'wpmst_cfg_name_from_field' );
		unregister_setting( 'wp_mailster_settings', 'wpmst_cfg_blocked_email_addresses' );
		unregister_setting( 'wp_mailster_settings', 'wpmst_cfg_words_to_filter' );
		unregister_setting( 'wp_mailster_settings', 'wpmst_cfg_keep_blocked_mails_for_days' );
		unregister_setting( 'wp_mailster_settings', 'wpmst_cfg_keep_bounced_mails_for_days' );
		unregister_setting( 'wp_mailster_settings', 'wpmst_cfg_recaptcha2_public_key' );
		unregister_setting( 'wp_mailster_settings', 'wpmst_cfg_recaptcha2_private_key' );
		unregister_setting( 'wp_mailster_settings', 'wpmst_cfg_recaptcha_theme' );
		unregister_setting( 'wp_mailster_settings', 'wpmst_cfg_use_alt_txt_vars' );
		unregister_setting( 'wp_mailster_settings', 'wpmst_cfg_include_body_in_blocked_bounced_notifies' );
		unregister_setting( 'wp_mailster_settings', 'wpmst_cfg_max_mails_per_hour' );
		unregister_setting( 'wp_mailster_settings', 'wpmst_cfg_max_mails_per_minute' );
		unregister_setting( 'wp_mailster_settings', 'wpmst_cfg_wait_between_two_mails' );
		unregister_setting( 'wp_mailster_settings', 'wpmst_cfg_imap_opentimeout' );
		unregister_setting( 'wp_mailster_settings', 'wpmst_cfg_digest_format_html_or_plain' );
		unregister_setting( 'wp_mailster_settings', 'wpmst_cfg_force_logging' );
		unregister_setting( 'wp_mailster_settings', 'wpmst_cfg_log_file_warning_size_mb' );
		unregister_setting( 'wp_mailster_settings', 'wpmst_cfg_last_mail_sent_at' );
		unregister_setting( 'wp_mailster_settings', 'wpmst_cfg_last_hour_mail_sent_in' );
		unregister_setting( 'wp_mailster_settings', 'wpmst_cfg_nr_of_mails_sent_in_last_hour' );
		unregister_setting( 'wp_mailster_settings', 'wpmst_cfg_last_day_mail_sent_in' );
		unregister_setting( 'wp_mailster_settings', 'wpmst_cfg_nr_of_mails_sent_in_last_day' );
		unregister_setting( 'wp_mailster_settings', 'wpmst_cfg_minchecktime' );
		unregister_setting( 'wp_mailster_settings', 'wpmst_cfg_minsendtime' );
		unregister_setting( 'wp_mailster_settings', 'wpmst_cfg_minmaintenance' );
        unregister_setting( 'wp_mailster_settings', 'wpmst_cfg_last_exec_retrieve' );
        unregister_setting( 'wp_mailster_settings', 'wpmst_cfg_last_exec_sending' );
        unregister_setting( 'wp_mailster_settings', 'wpmst_cfg_last_exec_maintenance' );
        unregister_setting( 'wp_mailster_settings', 'wpmst_cfg_maxexectime' );
		unregister_setting( 'wp_mailster_settings', 'wpmst_cfg_minduration' );
        unregister_setting( 'wp_mailster_settings', 'wpmst_cfg_registration_add_to_lists' );
        unregister_setting( 'wp_mailster_settings', 'wpmst_cfg_registration_add_to_groups' );
        unregister_setting( 'wp_mailster_settings', 'wpmst_cfg_dmarc_providers_policy_reject' );
        unregister_setting( 'wp_mailster_settings', 'wpmst_cfg_dmarc_adjust_for_policy_reject_providers' );

		delete_option('wpmst_cfg_mailster_db_version' );
		delete_option('wpmst_cfg_license_key' );
		delete_option('wpmst_cfg_current_version' );
		delete_option('wpmst_cfg_version_license' );
		delete_option('wpmst_cfg_uninstall_delete_data' );
		delete_option('wpmst_cfg_allow_send' );
		delete_option('wpmst_cfg_cron_job_key' );
		delete_option('wpmst_cfg_undo_line_wrapping' );
		delete_option('wpmst_cfg_logging_level' );
		delete_option('wpmst_cfg_mail_date_format' );
		delete_option('wpmst_cfg_mail_date_format_without_time' );
		delete_option('wpmst_cfg_add_reply_prefix' );
		delete_option('wpmst_cfg_reply_prefix' );
        delete_option('wpmst_cfg_trigger_source' );
		delete_option('wpmst_cfg_mail_from_field' );
		delete_option('wpmst_cfg_name_from_field' );
		delete_option('wpmst_cfg_blocked_email_addresses' );
		delete_option('wpmst_cfg_words_to_filter' );
		delete_option('wpmst_cfg_keep_blocked_mails_for_days' );
		delete_option('wpmst_cfg_keep_bounced_mails_for_days' );
		delete_option('wpmst_cfg_recaptcha2_public_key' );
		delete_option('wpmst_cfg_recaptcha2_private_key' );
		delete_option('wpmst_cfg_recaptcha_theme' );
		delete_option('wpmst_cfg_use_alt_txt_vars' );
		delete_option('wpmst_cfg_include_body_in_blocked_bounced_notifies' );
		delete_option('wpmst_cfg_max_mails_per_hour' );
		delete_option('wpmst_cfg_max_mails_per_minute' );
		delete_option('wpmst_cfg_wait_between_two_mails' );
		delete_option('wpmst_cfg_imap_opentimeout' );
		delete_option('wpmst_cfg_digest_format_html_or_plain' );
		delete_option('wpmst_cfg_force_logging' );
		delete_option('wpmst_cfg_log_file_warning_size_mb' );
		delete_option('wpmst_cfg_last_mail_sent_at' );
		delete_option('wpmst_cfg_last_hour_mail_sent_in' );
		delete_option('wpmst_cfg_nr_of_mails_sent_in_last_hour' );
		delete_option('wpmst_cfg_last_day_mail_sent_in' );
		delete_option('wpmst_cfg_nr_of_mails_sent_in_last_day' );
		delete_option('wpmst_cfg_minchecktime' );
		delete_option('wpmst_cfg_minsendtime' );
		delete_option('wpmst_cfg_minmaintenance' );
        delete_option('wpmst_cfg_last_exec_retrieve' );
        delete_option('wpmst_cfg_last_exec_sending' );
        delete_option('wpmst_cfg_last_exec_maintenance' );
		delete_option('wpmst_cfg_maxexectime' );
		delete_option('wpmst_cfg_minduration' );
        delete_option( 'wp_mailster_settings', 'wpmst_cfg_registration_add_to_lists' );
        delete_option( 'wp_mailster_settings', 'wpmst_cfg_registration_add_to_groups' );
        delete_option( 'wp_mailster_settings', 'wpmst_cfg_dmarc_providers_policy_reject' );
        delete_option( 'wp_mailster_settings', 'wpmst_cfg_dmarc_adjust_for_policy_reject_providers' );
	}