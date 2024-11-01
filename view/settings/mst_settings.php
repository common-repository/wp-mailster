<?php
	if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
		die('These are not the droids you are looking for.');
	}
$mstConfig = MstFactory::getConfig();
?>

<script type="text/javascript">
jQuery(document).ready(function() {
    jQuery('#wpmst_cfg_license_key').trigger('input'); // so that license info is checked on page load
});
</script>
<div class="wrap">
<h2><?php _e("WP Mailster Settings", 'wp-mailster'); ?></h2>
    <?php $this->mst_display_hidden_field('wpmst_cfg_license_key_old_prev', trim(get_option('wpmst_cfg_license_key'))); ?>
<form method="post" action="options.php">
	<?php settings_fields( 'wp_mailster_settings' ); ?>
	<?php do_settings_sections( 'wp_mailster_settings' ); ?>
    <input type="hidden" id="wpmst-licreavluate-nonce" value="<?php echo wp_create_nonce('wpmst_licreavluate'); ?>" />
	<table class="form-table mst-general-settings">
		<?php
		/*  */
		?>
		<?php
        $cronjobKeyTitle = esc_html__("The cronjob key is used for dedicated cronjobs. You can choose this key yourself. Please use only alphanumerical (A-Z letters and numbers) characters for the cronjob key. No special characters or spaces allowed. The cronjob has to be provided with the parameter \"key\" in the cronjob URL (e.g. www.example.com?wpmst_cronjob=execute&task=all&key=pass123).", 'wp-mailster');
        if(MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_DD_CRON)) {
		    $this->mst_display_input_field(
                __("Cron Job Key", 'wp-mailster'),
                'wpmst_cfg_cron_job_key', get_option('wpmst_cfg_cron_job_key'), "secret777", false, false,
                $cronjobKeyTitle
            );
		?>
		<tr>
			<td colspan="2">
			<?php _e("You can use this url for your cron jobs:", 'wp-mailster'); ?> <?php echo get_site_url(); ?>?wpmst_cronjob=execute&task=all&key=<span id="cron_job_key_url_example"><?php echo get_option('wpmst_cfg_cron_job_key'); ?></span>
			</td>
		</tr>
		<?php
        }else{
            $this->mst_display_hidden_field('wpmst_cfg_cron_job_key', get_option('wpmst_cfg_cron_job_key') );
        }
        $aInPrEd = '';
        $disabled = array();
        if(!MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_DD_CRON)) {
            $disabled = array("cron");
        }
		$this->mst_display_select_field( __("Trigger source", 'wp-mailster'), 'wpmst_cfg_trigger_source',
			array(
				"all" =>  __("All page loads (front- and backend, recommended when not using a cron job)", 'wp-mailster'),
				"admin" =>  __("Backend activity only (recommended for cron jobs in the free edition)", 'wp-mailster'),
				"cron" =>  __("Dedicated cronjobs only (WP Mailster Society & Enterprise only, recommended for cron jobs)", 'wp-mailster')
			),
			get_option('wpmst_cfg_trigger_source'),
			false,
			false,
			__("The trigger source determines which page loads of the website are used to retrieve and send emails in the background. WP Mailster needs to have regular site activity (page loads), otherwise the email delivery is delayed or never done.", 'wp-mailster'),
			$disabled
		);

		$this->mst_display_truefalse_field( __("Add subject prefix to replies", 'wp-mailster'), 'wpmst_cfg_add_reply_prefix', $mstConfig->addSubjectPrefixToReplies(), false, __("Add text defined below in front of the subject in mails that are replies (to a previous post)", 'wp-mailster'));
		$this->mst_display_input_field( __("Reply Subject Prefix", 'wp-mailster'), 'wpmst_cfg_reply_prefix', $mstConfig->getReplyPrefix(), __("Re:", 'wp-mailster'), false, false, __("Text to put in front of the subject of reply mails.", 'wp-mailster') );
		$this->mst_display_truefalse_field( __("Undo Line Wrapping", 'wp-mailster'), 'wpmst_cfg_undo_line_wrapping', get_option('wpmst_cfg_undo_line_wrapping'), false, __("Some email servers automatically do a line wrapping after a fixed number of characters. This option removes those line breaks.", 'wp-mailster'));
        $this->mst_display_input_field( __("Date/Time format in WP Mailster", 'wp-mailster' ), 'wpmst_cfg_mail_date_format', $mstConfig->getDateFormat(), 'd/m/Y H:i:s', false, false, __( "Format analog to PHP date time format, see http://php.net/manual/en/function.date.php", 'wp-mailster' ) );
        $this->mst_display_input_field( __("Date-only format in WP Mailster", 'wp-mailster'), 'wpmst_cfg_mail_date_format_without_time',$mstConfig->getDateFormatWithoutTime(), 'd/m/Y', false, false, __("Format analog to PHP date time format, see http://php.net/manual/en/function.date.php", 'wp-mailster') );

        $emailParam = 'From email address';
        $fromEmailLabel = __($emailParam, 'wp-mailster');
        MstFactory::getLogger()->debug('mst_settings name: '.$emailParam);
        MstFactory::getLogger()->debug('mst_settings email: '.$fromEmailLabel);
        $this->mst_display_select_field($fromEmailLabel , 'wpmst_cfg_mail_from_field',
			array(
				0 => __("Sender address", 'wp-mailster'),
				1 => __("Mailing list address", 'wp-mailster')
			),
			get_option('wpmst_cfg_mail_from_field'),
			false,
			false,
            __('The emails forwarded from Mailster use this name in the From field.', 'wp-mailster'),
            array(),
            false,
            false,
            '',
            'regular-text'
		);

        $nameParam = 'From name';
        $fromNameLabel = __($nameParam, 'wp-mailster');
        MstFactory::getLogger()->debug('mst_settings name: '.$nameParam);
        MstFactory::getLogger()->debug('mst_settings name: '.$fromNameLabel);
		$this->mst_display_select_field($fromNameLabel , 'wpmst_cfg_name_from_field',
			array(
				"sender_name" => __("Sender name", 'wp-mailster'),
				"list_name" => __("Mailing list name", 'wp-mailster'),
                "sender_name_via_list_name" => __("Sender name via list name", 'wp-mailster')
			),
			get_option('wpmst_cfg_name_from_field', 'sender_name_via_list_name'),
			false,
			false,
            __('The emails forwarded from Mailster use this name in the From field.', 'wp-mailster'),
            array(),
            false,
            false,
            '',
            'regular-text'
		);
		$this->mst_display_textarea_field( __("Blocked Email Addresses", 'wp-mailster'), 'wpmst_cfg_blocked_email_addresses', get_option('wpmst_cfg_blocked_email_addresses', 'bounce@*, bounces@*, mailer-daemon@*'), null, false, __("Emails coming from one of the configured email addresses will not be forwarded. Here you can configure both complete email addresses as so called wildcard addresses. Wildcard means, that only the local part (the name before the @-character) is checked for, not the domain of the email address. An example is the wildcard address john@* - that means that all email addresses that start with john@ will not be forwarded.", 'wp-mailster') );

        $aInPrEd = '';
        $readonly = false;
        if(!MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_FILTER)) {
            $readonly = true;
            $aInPrEd = sprintf(__( "Available in %s", 'wp-mailster' ), MstFactory::getV()->getMinV4Ft(MstVersionMgmt::MST_FT_ID_FILTER));
            echo '<tr><td colspan="2"><span class="mst-pro-only">'.sprintf(__( "Feature \"%s\" available in %s", 'wp-mailster' ), __( "Email content filtering", 'wp-mailster' ), MstFactory::getV()->getMinV4Ft(MstVersionMgmt::MST_FT_ID_FILTER)).'</span></td></tr>';
        }

		$this->mst_display_textarea_field( __("Words to filter", 'wp-mailster'), 'wpmst_cfg_words_to_filter', get_option('wpmst_cfg_words_to_filter', 'BadWord, Very Bad And Ugly Words, Really Really Bad And Ugly Words'), null, false, ($readonly ? $aInPrEd.' - ' : '').__("When mail filtering is activated in the mailing list, mails containing on of these words in the subject or body are not forwarded. Separate the key words with comma.", 'wp-mailster'), $readonly );
		$this->mst_display_select_field( __("Keep blocked emails for #days", 'wp-mailster'), 'wpmst_cfg_keep_blocked_mails_for_days',
			array(
				1 => '1',
				3 => '3',
				7 => '7',
				14 => '14',
				30 => '30',
				90 => '90',
				365 => '365'				
			),
			get_option('wpmst_cfg_keep_blocked_mails_for_days', 30),
			false,
			false,
			__("After the time period the emails are deleted to keep a smaller database", 'wp-mailster')
		);
		$this->mst_display_select_field( __("Keep bounced emails for #days", 'wp-mailster'), 'wpmst_cfg_keep_bounced_mails_for_days',
			array(
				1 => '1',
				3 => '3',
				7 => '7',
				14 => '14',
				30 => '30',
				90 => '90',
				365 => '365'				
			),
			get_option('wpmst_cfg_keep_bounced_mails_for_days', 30),
			false,
			false,
			__("After the time period the emails are deleted to keep a smaller database", 'wp-mailster'),
            array(),
            false,
            false,
            'spacer-bottom'
		);
        $this->mst_display_truefalse_field( __("DMARC: adjust sender settings", 'wp-mailster'),
            'wpmst_cfg_dmarc_adjust_for_policy_reject_providers',
            get_option('wpmst_cfg_dmarc_adjust_for_policy_reject_providers', 1),
            false,
            __("When an email comes from one of the given providers enforcing policy=reject, then automatically adjust sender settings to improve deliverability", 'wp-mailster'),
            false,
            false,
            ''
        );
        $this->mst_display_textarea_field( __("DMARC providers", 'wp-mailster'),
            'wpmst_cfg_dmarc_providers_policy_reject',
            get_option('wpmst_cfg_dmarc_providers_policy_reject', 'aol.*, yahoo.*'),
            null,
            false,
            esc_html__('Email providers that have a DMARC policy with the setting "REJECT". Here you can configure both complete domain names as well as so called wildcard domains. Wildcard means, that only the host name part (the name before the period character) is checked for, not the top-level domain of the email address. An example is the wildcard domain example.* - that means that all email addresses coming from a domain with "example" as the host name are matched, examples: "example.com", "example.net" and "example.co.uk"', 'wp-mailster'),
            false,
            false,
            'spacer-bottom'
        );
		$regPlgDisabled = !MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_REG_PLG);
		if($regPlgDisabled){
            echo '<tr><td colspan="2"><span class="mst-pro-only">'.sprintf(__( "Feature \"%s\" available in %s", 'wp-mailster' ), __( "Registration Plugin", 'wp-mailster' ), MstFactory::getV()->getMinV4Ft(MstVersionMgmt::MST_FT_ID_REG_PLG)).'</span></td></tr>';
        }
        $lists = MstFactory::getMailingListUtils()->getAllMailingLists();
        $listChoice = array();
        for($i=0;$i<count($lists);$i++){
            $listChoice[$lists[$i]->id] = $lists[$i]->name;
        }
        $this->mst_display_select_field(__("Add user to mailing list(s) on registration", 'wp-mailster'), 'wpmst_cfg_registration_add_to_lists',
            $listChoice,
            $mstConfig->getOnRegistrationAddToLists(),
            false,
            false,
            __("When user register at your site, they can be automatically added as subscribers to one or multiple of the mailing lists", 'wp-mailster'),
            array(),
            $regPlgDisabled,
            false,
            '',
            'regular-text '.($regPlgDisabled?'':'select2'),
            true
        );
        $groups = MstFactory::getGroupModel()->getAllGroups();
        $groupChoice = array();
        for ($i = 0; $i < count($groups); $i++) {
            $groupChoice[$groups[$i]->id] = $groups[$i]->name;
        }
        $this->mst_display_select_field(__("Add user to group(s) on registration", 'wp-mailster'), 'wpmst_cfg_registration_add_to_groups',
            $groupChoice,
            $mstConfig->getOnRegistrationAddToGroups(),
            false,
            false,
            __("When user register at your site, they can be automatically added to one or multiple of the WP user groups", 'wp-mailster'),
            array(),
            $regPlgDisabled,
            false,
            'spacer-bottom',
            'regular-text '.($regPlgDisabled?'':'select2'),
            true
        );
        MstFactory::getLogger()->debug("\r\n" . "\r\n" . "\r\n" . 'mst_settings - add_to_lists: ' . print_r(get_option('wpmst_cfg_registration_add_to_lists', array()), true) . ' add_to_groups: ' . print_r(get_option('wpmst_cfg_registration_add_to_groups', array()), true) . "\r\n" . "\r\n");

        $aInPrEd = '';
		$readonly = false;
		$colors = array();
		$languages = array();
		if(!MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_CAPTCHA)) {
			$readonly = true;
			$colors = array('red', 'white', 'blackglass', 'clean');
			$languages = array('en', 'nl', 'fr', 'de', 'pt', 'ru', 'es', 'tr');
            $aInPrEd = sprintf(__( "Available in %s", 'wp-mailster' ), MstFactory::getV()->getMinV4Ft(MstVersionMgmt::MST_FT_ID_CAPTCHA));
            echo '<tr><td colspan="2"><span class="mst-pro-only">'.sprintf(__( "Feature \"%s\" available in %s", 'wp-mailster' ), __( "Captcha", 'wp-mailster' ), MstFactory::getV()->getMinV4Ft(MstVersionMgmt::MST_FT_ID_CAPTCHA)).'</span></td></tr>';
		}

		$this->mst_display_input_field( __( "reCAPTCHA v2 public key", 'wp-mailster' ), 'wpmst_cfg_recaptcha2_public_key', get_option('wpmst_cfg_recaptcha2_public_key'), null, false, false, ($readonly ? $aInPrEd.' - ' : '').__( "To use the reCAPTCHA (which is provided externally through Google) you have to have a private and public API key for your domain. reCAPTCHA API keys can be retrieved for free from https://www.google.com/recaptcha/admin/create", 'wp-mailster' ), $readonly );
		$this->mst_display_input_field( __( "reCAPTCHA v2 private key", 'wp-mailster' ), 'wpmst_cfg_recaptcha2_private_key', get_option('wpmst_cfg_recaptcha2_private_key'), null, false, false, ($readonly ? $aInPrEd.' - ' : '').__( "To use the reCAPTCHA (which is provided externally through Google) you have to have a private and public API key for your domain. reCAPTCHA API keys can be retrieved for free from https://www.google.com/recaptcha/admin/create", 'wp-mailster' ), $readonly );
		$this->mst_display_select_field( __( "reCAPTCHA Theme", 'wp-mailster' ), 'wpmst_cfg_recaptcha_theme',
			array(
				'light'    => __( "Light", 'wp-mailster' ),
				'dark'     => __( "Dark", 'wp-mailster' )
			),
			$mstConfig->getRecaptchaTheme(),
			false,
			false,
            ($readonly ? $aInPrEd.' - ' : '').__( "Change the design of the captcha to fit your site appearance", 'wp-mailster' ),
			$colors,
            $readonly,
            false,
            'spacer-bottom'
		);

        $aInPrEd = '';
        $readonly = false;
        if(!MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_THROTTLE)) {
            $readonly = true;
            $waitBetween = array('0', '0.1', '0.5', '1', '2', '5', '10', '20', '30');
            $aInPrEd = sprintf(__( "Available in %s", 'wp-mailster' ), MstFactory::getV()->getMinV4Ft(MstVersionMgmt::MST_FT_ID_THROTTLE));
            echo '<tr><td colspan="2"><span class="mst-pro-only">'.sprintf(__( "Feature \"%s\" available in %s", 'wp-mailster' ), __( "Send Throttling", 'wp-mailster' ), MstFactory::getV()->getMinV4Ft(MstVersionMgmt::MST_FT_ID_THROTTLE)).'</span></td></tr>';
        }
        $this->mst_display_input_field( __("Send limit (per hour)", 'wp-mailster'), 'wpmst_cfg_max_mails_per_hour', $mstConfig->getMaxMailsPerHour(), null, false, false, ($readonly ? $aInPrEd.' - ' : '').__("Max. number of emails to send per hour. 0 and empty field means unthrottled/unlimited sending.", 'wp-mailster'), $readonly );
        $this->mst_display_input_field( __("Send limit (per minute)", 'wp-mailster'), 'wpmst_cfg_max_mails_per_minute', $mstConfig->getMaxMailsPerMinute(), null, false, false, ($readonly ? $aInPrEd.' - ' : '').__("Max. number of emails to send per minute. 0 and empty field means unthrottled/unlimited sending.", 'wp-mailster'), $readonly );
        $this->mst_display_select_field( __("Wait between the sending of two emails", 'wp-mailster'), 'wpmst_cfg_wait_between_two_mails',
            array(
                '0' => __( "0 seconds - Do not wait", 'wp-mailster' ),
                '0.1' => __( "0.1 seconds", 'wp-mailster' ),
                '0.5' => __( "0.5 seconds", 'wp-mailster' ),
                '1' => sprintf( __( '%d seconds', 'wp-mailster' ), 1),
                '2' => sprintf( __( '%d seconds', 'wp-mailster' ), 2),
                '5' => sprintf( __( '%d seconds', 'wp-mailster' ), 5),
                '10' => sprintf( __( '%d seconds', 'wp-mailster' ), 10),
                '20' => sprintf( __( '%d seconds', 'wp-mailster' ), 20),
                '30' => sprintf( __( '%d seconds', 'wp-mailster' ), 30)
            ),
            $mstConfig->getWaitBetweenTwoMails(),
            false,
            false,
            ($readonly ? $aInPrEd.' - ' : '').__( "Wait between the sending of two emails at least the defined time period (in seconds). This can throttle the sending process and is there for you, in case your webhoster has send limit requirements. Enter 0 to not throttle the sending (this is also the default). ATTENTION! This feature ONLY works in the throttling-supporting product editions!", 'wp-mailster' ),
            $colors,
            $readonly,
            false,
            ''
        );


        $this->mst_display_input_field( __("Minimal time between email retrievals", 'wp-mailster'), 'wpmst_cfg_minchecktime',
            get_option('wpmst_cfg_minchecktime', 240),
            null,
            false,
            true,
            __("Try to retrieve emails every x seconds", 'wp-mailster'),
            false,
            false,
            'spacer-top'
        );
        $this->mst_display_input_field( __("Minimal time between email sending", 'wp-mailster'), 'wpmst_cfg_minsendtime', get_option('wpmst_cfg_minsendtime', 60), null, false, true, __("Continue to forward/send enqueued mails every x seconds", 'wp-mailster'));
        $this->mst_display_input_field( __("Minimal time between maintenance runs", 'wp-mailster'), 'wpmst_cfg_minmaintenance', get_option('wpmst_cfg_minmaintenance', 3600), null, false, true, __("Execute system maintenance every x seconds (recommended: at least 3600 seconds = 1 hour, not more than 86400 seconds = 24 hours)", 'wp-mailster'));
        $this->mst_display_input_field( __("Max. execution time", 'wp-mailster'), 'wpmst_cfg_maxexectime', get_option('wpmst_cfg_maxexectime', 12), null, false, true, __("Max. time (seconds) to take for retrieving/sending of emails. Recommended: not more than half of the PHP Max Execution Time setting", 'wp-mailster'));
        $this->mst_display_input_field( __("Minimal operation duration", 'wp-mailster'), 'wpmst_cfg_minduration', get_option('wpmst_cfg_minduration', 2), null, false, true, __("Time (seconds) that is needed to complete an action like connect to a mailbox, adjust it to server performance -> increase it when you experience time outs, has to be significantly lower than Max. Execution Time", 'wp-mailster'));
        $this->mst_display_input_field( __("Open mailbox timeout", 'wp-mailster'), 'wpmst_cfg_imap_opentimeout',
            $mstConfig->getMailboxOpenTimeout(),
            null,
            false,
            true,
            __("Time in seconds for a mailbox connection to be established. Otherwise a timeout occurs.", 'wp-mailster'),
            false,
            false,
            'spacer-bottom'
        );

        $this->mst_display_truefalse_field( __("Alternative text variables", 'wp-mailster'), 'wpmst_cfg_use_alt_txt_vars', $mstConfig->isUseAlternativeTextVars(), false, __("You can use alternative text variables. This is only recommended for the rare case when the text variables are incompatible with e.g. your mailing server", 'wp-mailster'));
        $this->mst_display_truefalse_field( __("Include mail body in bounced/blocked mail notifications", 'wp-mailster'), 'wpmst_cfg_include_body_in_blocked_bounced_notifies', $mstConfig->includeBodyInBouncedBlockedNotifications(), false, __("The text of the bounced/blocked email can be included in the notification messages", 'wp-mailster'));
        $this->mst_display_truefalse_field( __("Include attachments in moderation requests", 'wp-mailster'), 'wpmst_cfg_include_attachments_in_moderation_requests', $mstConfig->includeAttachmentsInModerationRequests(), false, __("Moderators get sent all attachments in the emails asking them to make a moderation decision. That way they can have a look at the attachment contents as well.", 'wp-mailster'));
		$this->mst_display_select_field( __("Message format to use within digests", 'wp-mailster'), 'wpmst_cfg_digest_format_html_or_plain',
			array(
				'plain' =>  __("Plain", 'wp-mailster'),
				'html' =>  __("HTML", 'wp-mailster')
			),
			$mstConfig->getDigestMailFormat(),
			false,
			false,
			__("Either the original HTML is used or a conversion into plain text can be done to get a more uniform looking digest", 'wp-mailster')
		);
		$this->mst_display_select_field( __("Logging Level", 'wp-mailster'), 'wpmst_cfg_logging_level',
			array(
				0 =>  __("Disabled", 'wp-mailster'),
				1 =>  __("Errors only", 'wp-mailster'),
				3 =>  __("Normal", 'wp-mailster'),
				4 =>  __("Max. Logging (Debug)", 'wp-mailster')
			),
			get_option('wpmst_cfg_logging_level', MstConsts::LOG_LEVEL_INFO),
			false,
			false,
			__("The logging level determines which kind of events (e.g. an error while retrieving mails) should be written to WP Mailster's log file", 'wp-mailster'),
            array(),
            false,
            false,
            'spacer-top'
		);

		$this->mst_display_truefalse_field( __("Force logging", 'wp-mailster'), 'wpmst_cfg_force_logging', $mstConfig->isLoggingForced(), false, __("Deactivates checks whether log dir is existing and log file is writable. Use with caution! This can not work e.g. if your log path is configured wrong.", 'wp-mailster'));
		$this->mst_display_input_field( __("Log file warning size limit (MB)", 'wp-mailster'), 'wpmst_cfg_log_file_warning_size_mb', $mstConfig->getLogFileSizeWarningLevel(), null, false, true, __("When this size of the log file (in megabyte) is exceeded a warning message is produced", 'wp-mailster') );

		$this->mst_display_truefalse_field( __("Delete data when uninstalling the plugin", 'wp-mailster'), 'wpmst_cfg_uninstall_delete_data',
            get_option('wpmst_cfg_uninstall_delete_data'),
            false,
            __("Checking this option will remove all WP Mailster related data, including your lists, groups and WP Mailster users.", 'wp-mailster'),
            false,
            false,
            'spacer-top'
        );

        /*
        $this->mst_display_hidden_field('wpmst_cfg_last_mail_sent_at', $mstConfig->getLastMailSentAt() );
        $this->mst_display_hidden_field('wpmst_cfg_last_hour_mail_sent_in', $mstConfig->getLastHourMailSentIn() );
        $this->mst_display_hidden_field('wpmst_cfg_nr_of_mails_sent_in_last_hour', $mstConfig->getNrOfMailsSentInLastHour() );
        $this->mst_display_hidden_field('wpmst_cfg_last_day_mail_sent_in', $mstConfig->getLastDayMailSentIn() );
        $this->mst_display_hidden_field('wpmst_cfg_nr_of_mails_sent_in_last_day', $mstConfig->getNrOfMailsSentInLastDay() );

        $this->mst_display_hidden_field('wpmst_cfg_last_exec_retrieve', get_option('wpmst_cfg_last_exec_retrieve', -1) );
        $this->mst_display_hidden_field('wpmst_cfg_last_exec_sending', get_option('wpmst_cfg_last_exec_sending', -1) );
        $this->mst_display_hidden_field('wpmst_cfg_last_exec_maintenance', get_option('wpmst_cfg_last_exec_maintenance', -1) );*/
		?>
	</table>
	<?php submit_button(); ?>
    <br/>
</form>
</div>

<script>
    jQuery('.select2').select2({ //See the Dot
        theme: "classic"
    });
</script>