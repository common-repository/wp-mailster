<?php
/*
 * Plugin Name: WP Mailster Free
 * Plugin URI: https://www.wpmailster.com
 * Description: The Mailing List Plugin For WordPress
 * Author: brandtoss
 * Author URI: https://www.wpmailster.com
 * Version: 1.8.16.0
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Requires at least: 5.3
 * Requires PHP: 5.6
 *
 * Text Domain: wp-mailster
 * Domain Path: /languages
 *
 * WP Mailster is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * WP Mailster is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with WP Mailster. If not, see <http://www.gnu.org/licenses/>.
 *
 */

if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
    die('These droids are not the droids you are looking for.');
}
require_once( plugin_dir_path( __FILE__ )."admin/mst_config.php" );
require_once( plugin_dir_path( __FILE__ )."widget/subscribe-widget.php" );

global $wpdb;
$wpdb->show_errors(true);

class wpmst_mailster {
    public $WPMST_PLUGIN_BASENAME;
    public $WPMST_PLUGIN_DIR;
    public $WPMST_PLUGIN_FILE;
    public $WPMST_PLUGIN_DIR_URL;
    public $WPMST_PLUGIN_URL;

	private $table_name,$user_table;

	public $mailingLists_obj;
	public $server_obj;
	public $groups_obj;
	public $users_obj;
    public $queued_obj;
    public $archive_obj;
    public $mailster_users;
    public $mailster_groups;
    public $mailster_group_users;
    public $mailster_lists;
   
    public function __construct( $file_path ){
    	$this->wpmst_define_data( $file_path );
    	require_once $this->WPMST_PLUGIN_DIR."/mailster/includes.php"; // Get all essential includes
    	require_once $this->WPMST_PLUGIN_DIR."/models/MailsterModel.php";
    	require_once $this->WPMST_PLUGIN_DIR."/mailster/Consts.php";
    	require_once $this->WPMST_PLUGIN_DIR."/models/MailsterModelList.php";
    	require_once $this->WPMST_PLUGIN_DIR."/models/MailsterModelServer.php";
    	require_once $this->WPMST_PLUGIN_DIR."/models/plgSystemMailster.php";
    	require_once $this->WPMST_PLUGIN_DIR."/mailster/Factory.php";

		/* Define plugin directory paths and urls.  */
		load_plugin_textdomain( 'wp-mailster', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

		//register menu
		add_action( 'admin_menu', array($this, 'wpmst_mailster_menu'));
		
		/*Activation and Deactivation Hooks*/
		/*activate tables*/	
		register_activation_hook( __FILE__, 'mailster_install' );
		
		/*add scripts*/
		add_action( 'admin_enqueue_scripts', array( $this, 'wpmst_mailster_admin_enqueues' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'wpmst_wp_mailster_enqueues' ) );

        /*  */
		
		/*ajax handling*/
		add_action( 'wp_ajax_wpmst_pagination_request', array( $this, 'wpmst_pagination_request' ) );
		add_action( 'wp_ajax_nopriv_wpmst_pagination_request', array( $this, 'wpmst_pagination_request' ) );
		
		add_action( 'wp_ajax_wpmst_delete_users', array( $this, 'wpmst_delete_users' ) );
		add_action( 'wp_ajax_nopriv_wpmst_delete_users', array( $this, 'wpmst_delete_users' ) );
		
		add_action( 'wp_ajax_wpmst_delete_groups', array( $this, 'wpmst_delete_groups' ) );
		add_action( 'wp_ajax_nopriv_wpmst_delete_groups', array( $this, 'wpmst_delete_groups' ) );
		
		add_action( 'wp_ajax_wpmst_delete_lists', array( $this, 'wpmst_delete_lists' ) );
		add_action( 'wp_ajax_nopriv_wpmst_delete_lists', array( $this, 'wpmst_delete_lists' ) );
		
		add_action( 'wp_ajax_wpmst_activate_lists', array( $this, 'wpmst_activate_lists' ) );
		add_action( 'wp_ajax_nopriv_wpmst_activate_lists', array( $this, 'wpmst_activate_lists' ) );
		
		add_action( 'wp_ajax_wpmst_deactivate_lists', array( $this, 'wpmst_deactivate_lists' ) );
		add_action( 'wp_ajax_nopriv_wpmst_deactivate_lists', array( $this, 'wpmst_deactivate_lists' ) );
		
		add_action( 'wp_ajax_wpmst_delete_user_list', array( $this, 'wpmst_delete_user_list' ) );
		add_action( 'wp_ajax_nopriv_wpmst_delete_user_list', array( $this, 'wpmst_delete_user_list' ) );
		
		add_action( 'wp_ajax_wpmst_delete_user_group', array( $this, 'wpmst_delete_user_group' ) );
		add_action( 'wp_ajax_nopriv_wpmst_delete_user_group', array( $this, 'wpmst_delete_user_group' ) );
		
		add_action( 'wp_ajax_wpmst_delete_group_list', array( $this, 'wpmst_delete_group_list' ) );
		add_action( 'wp_ajax_nopriv_wpmst_delete_group_list', array( $this, 'wpmst_delete_group_list' ) );

        add_action( 'wp_ajax_wpmst_delete_notify', array( $this, 'wpmst_delete_notify' ) );
        add_action( 'wp_ajax_nopriv_wpmst_delete_notify', array( $this, 'wpmst_delete_notify' ) );

        add_action( 'wp_ajax_wpmst_subscribe_plugin', array( $this, 'wpmst_subscribe_plugin' ) );
        add_action( 'wp_ajax_nopriv_wpmst_subscribe_plugin', array( $this, 'wpmst_subscribe_plugin' ) );

        add_action( 'wp_ajax_wpmst_unsubscribe_plugin', array( $this, 'wpmst_unsubscribe_plugin' ) );
        add_action( 'wp_ajax_nopriv_wpmst_unsubscribe_plugin', array( $this, 'wpmst_unsubscribe_plugin' ) );

		/*handle active ajax*/
		add_action( 'wp_ajax_wpmst_active_request', array( $this, 'wpmst_active_request' ) );
		add_action( 'wp_ajax_nopriv_wpmst_active_request', array( $this, 'wpmst_active_request' ) );

		/* languages */
        add_action('init', array( $this, 'wpmst_load_textdomain' ) );
		
		//tables
		global $wpdb;
		$this->mailster_users =  $wpdb->prefix . 'mailster_users';
		$this->mailster_groups =  $wpdb->prefix . 'mailster_groups'; 
		$this->mailster_group_users =  $wpdb->prefix . 'mailster_group_users';
		$this->mailster_lists =  $wpdb->prefix . 'mailster_lists';

		//register configuration settings
		add_action( 'admin_init', 'mst_setup' );
		function mst_setup() {
			//register our settings
            register_setting( 'wp_mailster_settings', 'wpmst_cfg_mailster_db_version' );
			register_setting( 'wp_mailster_settings', 'wpmst_cfg_license_key' );
			register_setting( 'wp_mailster_settings', 'wpmst_cfg_current_version' );
			register_setting( 'wp_mailster_settings', 'wpmst_cfg_version_license' );
			register_setting( 'wp_mailster_settings', 'wpmst_cfg_uninstall_delete_data' );
			register_setting( 'wp_mailster_settings', 'wpmst_cfg_cron_job_key' );
			register_setting( 'wp_mailster_settings', 'wpmst_cfg_undo_line_wrapping' );
			register_setting( 'wp_mailster_settings', 'wpmst_cfg_logging_level' );
			register_setting( 'wp_mailster_settings', 'wpmst_cfg_mail_date_format' );
			register_setting( 'wp_mailster_settings', 'wpmst_cfg_mail_date_format_without_time' );
			register_setting( 'wp_mailster_settings', 'wpmst_cfg_add_reply_prefix' );
			register_setting( 'wp_mailster_settings', 'wpmst_cfg_reply_prefix' );
			register_setting( 'wp_mailster_settings', 'wpmst_cfg_trigger_source' );
			register_setting( 'wp_mailster_settings', 'wpmst_cfg_mail_from_field' );
			register_setting( 'wp_mailster_settings', 'wpmst_cfg_name_from_field' );
			register_setting( 'wp_mailster_settings', 'wpmst_cfg_blocked_email_addresses' );
			register_setting( 'wp_mailster_settings', 'wpmst_cfg_words_to_filter' );
			register_setting( 'wp_mailster_settings', 'wpmst_cfg_keep_blocked_mails_for_days' );
			register_setting( 'wp_mailster_settings', 'wpmst_cfg_keep_bounced_mails_for_days' );
			register_setting( 'wp_mailster_settings', 'wpmst_cfg_recaptcha2_public_key' );
			register_setting( 'wp_mailster_settings', 'wpmst_cfg_recaptcha2_private_key' );
			register_setting( 'wp_mailster_settings', 'wpmst_cfg_recaptcha_theme' );
			register_setting( 'wp_mailster_settings', 'wpmst_cfg_use_alt_txt_vars' );
			register_setting( 'wp_mailster_settings', 'wpmst_cfg_include_body_in_blocked_bounced_notifies' );
			register_setting( 'wp_mailster_settings', 'wpmst_cfg_max_mails_per_hour' );
			register_setting( 'wp_mailster_settings', 'wpmst_cfg_max_mails_per_minute' );
			register_setting( 'wp_mailster_settings', 'wpmst_cfg_wait_between_two_mails' );
			register_setting( 'wp_mailster_settings', 'wpmst_cfg_imap_opentimeout' );
			register_setting( 'wp_mailster_settings', 'wpmst_cfg_digest_format_html_or_plain' );
			register_setting( 'wp_mailster_settings', 'wpmst_cfg_force_logging' );
			register_setting( 'wp_mailster_settings', 'wpmst_cfg_log_file_warning_size_mb' );
			register_setting( 'wp_mailster_settings', 'wpmst_cfg_last_mail_sent_at' );
			register_setting( 'wp_mailster_settings', 'wpmst_cfg_last_hour_mail_sent_in' );
			register_setting( 'wp_mailster_settings', 'wpmst_cfg_nr_of_mails_sent_in_last_hour' );
			register_setting( 'wp_mailster_settings', 'wpmst_cfg_last_day_mail_sent_in' );
			register_setting( 'wp_mailster_settings', 'wpmst_cfg_nr_of_mails_sent_in_last_day' );
			register_setting( 'wp_mailster_settings', 'wpmst_cfg_minchecktime' );
			register_setting( 'wp_mailster_settings', 'wpmst_cfg_minsendtime' );
			register_setting( 'wp_mailster_settings', 'wpmst_cfg_minmaintenance' );
            register_setting( 'wp_mailster_settings', 'wpmst_cfg_last_exec_retrieve' );
            register_setting( 'wp_mailster_settings', 'wpmst_cfg_last_exec_sending' );
            register_setting( 'wp_mailster_settings', 'wpmst_cfg_last_exec_maintenance' );
			register_setting( 'wp_mailster_settings', 'wpmst_cfg_maxexectime' );
			register_setting( 'wp_mailster_settings', 'wpmst_cfg_minduration' );
            register_setting( 'wp_mailster_settings', 'wpmst_cfg_registration_add_to_lists' );
            register_setting( 'wp_mailster_settings', 'wpmst_cfg_registration_add_to_groups' );
            register_setting( 'wp_mailster_settings', 'wpmst_cfg_dmarc_providers_policy_reject' );
            register_setting( 'wp_mailster_settings', 'wpmst_cfg_dmarc_adjust_for_policy_reject_providers' );


            $log = MstFactory::getLogger();
            $dbVersion = get_option('wpmst_cfg_mailster_db_version');
            if(!$dbVersion){
                $log->info('mst_setup No wpmst_cfg_mailster_db_version');
                mailster_install_or_update();
            }else{
                $currentVersion = "1.8.16.0";
                $currentVersion = implode('.', array_slice(explode('.', $currentVersion), 0, 3)); // only extract first three version levels
                if(version_compare($dbVersion, $currentVersion, '<')){
                    $log->info('mst_setup DB version '.$dbVersion.' smaller than current version '.$currentVersion);
                    mailster_install_or_update();
                }elseif('false' === 'true' && ( !array_key_exists('action', $_POST) || $_POST['action'] != 'heartbeat')){
                    $log->info('mst_setup DEV OPTION Force DB Update active!!!');
                    mailster_install_or_update();
                }
            }

		}
	}



	/*	Define plugins paths */
	public function wpmst_define_data( $file_path ) {
		$this->WPMST_PLUGIN_BASENAME  = plugin_basename( $file_path );
		$this->WPMST_PLUGIN_DIR       = trailingslashit( dirname( trailingslashit( WP_PLUGIN_DIR ).$this->WPMST_PLUGIN_BASENAME ) );
		$this->WPMST_PLUGIN_FILE      = trailingslashit( WP_PLUGIN_DIR ).$this->WPMST_PLUGIN_BASENAME;
		$this->WPMST_PLUGIN_DIR_URL   = trailingslashit( plugins_url( dirname( $this->WPMST_PLUGIN_BASENAME )) );
		$this->WPMST_PLUGIN_URL       = trailingslashit( plugins_url( $this->WPMST_PLUGIN_BASENAME ) );
	}

	
	/*loading scripts*/
	function wpmst_mailster_admin_enqueues(){

        $includeStylesAndScripts = false;
	    $currScreen = get_current_screen();
	    if($currScreen && property_exists($currScreen, 'base')){
            $base = $currScreen->base;
            if($base === 'toplevel_page_wpmst_mailster_intro'
                || $base === 'wp-mailster_page_mst_mailing_list_add'
                || $base === 'wp-mailster_page_mst_mailing_lists'
                || $base === 'wp-mailster_page_mst_queued'
                || $base === 'wp-mailster_page_mst_servers'
                || $base === 'wp-mailster_page_mst_servers_add'
                || $base === 'wp-mailster_page_mst_users'
                || $base === 'wp-mailster_page_mst_users_add'
                || $base === 'wp-mailster_page_mst_groups'
                || $base === 'wp-mailster_page_mst_groups_add'
                || $base === 'wp-mailster_page_mst_archived'
                || $base === 'wp-mailster_page_wpmst_settings'
                || $base === 'admin_page_mst_mailing_list_add'
                || $base === 'admin_page_mst_groups_add'
            ){
                $includeStylesAndScripts = true;
            }
        }
	    if($includeStylesAndScripts) {
            if (class_exists('MstFactory')) {
                $log = MstFactory::getLogger();
                //$log->debug('wpmst_mailster_admin_enqueues on ' . $base);
            }

            wp_register_style('mst_print_style', $this->WPMST_PLUGIN_DIR_URL . 'asset/css/mst_print.css');
            wp_enqueue_style('mst_print_style');

            wp_register_style('jquery-ui-style', $this->WPMST_PLUGIN_DIR_URL . 'asset/css/jquery-ui.css');
            wp_enqueue_style('jquery-ui-style');

            wp_register_style('mst_mailster_style', $this->WPMST_PLUGIN_DIR_URL . 'asset/css/mailster.css');
            wp_enqueue_style('mst_mailster_style');

            wp_register_style('multiselect2side_style', $this->WPMST_PLUGIN_DIR_URL . 'asset/css/jquery.multiselect2side.css');
            wp_enqueue_style('multiselect2side_style');

            wp_enqueue_script('jquery-ui-core');
            wp_enqueue_script('jquery-ui-tabs');

            wp_enqueue_script('multiselect2side', $this->WPMST_PLUGIN_DIR_URL . 'asset/js/jquery.multiselect.js', array('jquery'));

            wp_register_style( 'select2css', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css');
            wp_register_script( 'select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', array( 'jquery' ));
            wp_enqueue_style( 'select2css' );
            wp_enqueue_script( 'select2' );

            wp_enqueue_script('mailster_js', $this->WPMST_PLUGIN_DIR_URL . 'asset/js/mst_script.js', array('jquery', 'multiselect2side', 'jquery-ui-core', 'jquery-ui-tabs'));

            wp_enqueue_script('mailster_admin_list_utils', $this->WPMST_PLUGIN_DIR_URL . 'asset/js/admin.list.utils.js', array('jquery', 'jquery-ui-core', 'jquery-ui-dialog'));

            wp_localize_script('mailster_js', 'mailster_js_lang', array(
                'non_valid_key' => __('Non valid key. You do not have access to premium automatic updates.<br><br>If you update the plugin, it will become a free version.', 'wp-mailster'),
                'please_allow_send' => __('Please allow the plugin to send data to the author in the respective option. Otherwise the license key can not be validated.', 'wp-mailster'),
                'checking_license_key' => __('Checking license key...', 'wp-mailster'),
                'license_key_correct' => __('License key correct.', 'wp-mailster'),
                'product_edition' => __('Product Edition', 'wp-mailster'),
                'license_status' => __('License Status', 'wp-mailster'),
                'status_active' => __('Active', 'wp-mailster'),
                'status_expired' => __('Expired', 'wp-mailster'),
                'expires_in_days_on_date' => __('expires in {days} days on {date}', 'wp-mailster'),
                'expired_on_date' => __('expired on {date}', 'wp-mailster'),
                'search_placeholder' => __('Search', 'wp-mailster'),
                'make_sure_to_save' => __('Make sure to save the settings!', 'wp-mailster')
            ));
        }
	}

	function wpmst_wp_mailster_enqueues(){
		wp_enqueue_script('jquery-ui-core');

        wp_enqueue_script( 'wpmst-subscribe-form-ajax', $this->WPMST_PLUGIN_DIR_URL.'asset/js/wpmstsubscribe.js', array('jquery'));
        wp_localize_script( 'wpmst-subscribe-form-ajax', 'wpmst_ajax_object', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) ); // setting ajaxurl

        wp_register_script( 'wpmst-recaptcha-v2', 'https://www.google.com/recaptcha/api.js?onload=onLoadInitCaptchas&render=explicit' );

	}

    /*  */

	function wpmst_load_textdomain() {
	    load_plugin_textdomain( 'wp-mailster', FALSE, dirname( plugin_basename(__FILE__) ) . '/languages/' );
	}

	/* Creating Menus */
	public function wpmst_mailster_menu() {
		$admin_level = 'edit_posts';
		$user_level  = 'read';
		/* Adding menus */
		add_menu_page(__('WP Mailster','wp-mailster'), __('WP Mailster', 'wp-mailster'), $admin_level, 'wpmst_mailster_intro', array($this,'wpmst_mailster_intro'), "dashicons-email-alt");
		
		//mailing list page
		include_once $this->WPMST_PLUGIN_DIR."/classes/mst_mailing_lists.php";
		$hook = add_submenu_page ('wpmst_mailster_intro', __('Mailing Lists','wp-mailster'), __('Mailing Lists', 'wp-mailster'), $admin_level, 'mst_mailing_lists', array ($this, 'mst_mailing_list_page'));
		add_action( "load-$hook", array( $this, 'mst_mailing_list_screen_option' ) );


		$hook = add_submenu_page('', __('Add Mailing List','wp-mailster'), __('Add Mailing List', 'wp-mailster'), $admin_level, 'mst_mailing_list_add',array($this,'mst_mailing_list_add'));
		$hook = add_submenu_page('', __('Manage Recipients','wp-mailster'), __('Manage Recipients', 'wp-mailster'), $admin_level, 'mst_recipient_management',array($this,'mst_recipient_management'));
		$hook = add_submenu_page('', __('Add Recipient','wp-mailster'), __('Add Recipient', 'wp-mailster'), $admin_level, 'mst_list_members_add',array($this,'mst_list_members_add'));
		$hook = add_submenu_page('', __('Add Group','wp-mailster'), __('Add Group', 'wp-mailster'), $admin_level, 'mst_list_groups_add',array($this,'mst_list_groups_add'));
		
		//queued emails page
		include_once $this->WPMST_PLUGIN_DIR."/classes/mst_queued.php";
		$hook = add_submenu_page ('wpmst_mailster_intro', __('Queued Emails','wp-mailster'), __('Queued Emails', 'wp-mailster'), $admin_level, 'mst_queued', array ($this, 'mst_queued_page'));
		add_action( "load-$hook", array( $this, 'mst_queued_screen_option' ) );

		//server page
		include_once $this->WPMST_PLUGIN_DIR."/classes/mst_servers.php";
		$hook = add_submenu_page ('wpmst_mailster_intro', __('Servers','wp-mailster'), __('Servers', 'wp-mailster'), $admin_level, 'mst_servers', array ($this, 'mst_servers_page'));
		add_action( "load-$hook", array( $this, 'mst_servers_screen_option' ) );
		$hook = add_submenu_page('', __('Add Server','wp-mailster'), __('Add Server', 'wp-mailster'), $admin_level, 'mst_servers_add',array($this,'mst_servers_add'));

		//user page
		include_once $this->WPMST_PLUGIN_DIR."/classes/mst_users.php";
		$hook = add_submenu_page ('wpmst_mailster_intro', __('Users','wp-mailster'), __('Users', 'wp-mailster'), $admin_level, 'mst_users', array ($this, 'mst_users_page'));
		add_action( "load-$hook", array( $this, 'mst_users_screen_option' ) );
		$hook = add_submenu_page('', __('Add User','wp-mailster'), __('Add User', 'wp-mailster'), $admin_level, 'mst_users_add',array($this,'mst_users_add'));

		//groups list page
		include_once $this->WPMST_PLUGIN_DIR."/classes/mst_groups.php";
		$hook = add_submenu_page ('wpmst_mailster_intro', __('Groups','wp-mailster'), __('Groups', 'wp-mailster'), $admin_level, 'mst_groups', array ($this, 'mst_groups_page'));
		add_action( "load-$hook", array( $this, 'mst_groups_screen_option' ) );
		$hook = add_submenu_page('', __('Add Group','wp-mailster'), __('Add Group', 'wp-mailster'), $admin_level, 'mst_groups_add', array($this,'mst_groups_add'));
		// mail archive
        include_once $this->WPMST_PLUGIN_DIR."/classes/mst_archived.php";
        $hook = add_submenu_page( 'wpmst_mailster_intro', __( 'Archived Emails', 'wp-mailster' ), __( 'Archived Emails', 'wp-mailster' ), $admin_level, 'mst_archived', array(
            $this,
            'mst_archived_page'
        ) );
        add_action( "load-$hook", array( $this, 'mst_archived_screen_option' ) );
        //settings page
        $hook = add_submenu_page('wpmst_mailster_intro', __('Settings','wp-mailster'), __('Settings', 'wp-mailster'), $admin_level, 'wpmst_settings', array($this,'mst_settings_page'));

        if(MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_DIGEST)) {
            // TODO Missing managing digests from backend?!
		}
    }



/*********************************************\
 *********************************************
 
 Mailing Lists

 *********************************************
\*********************************************/	

	/**
	 * Mailing Lists Page
	 */
	public function mst_mailing_list_page() {
		if( isset($_GET['subpage']) && $_GET['subpage'] == "edit") {
			$this->mst_mailing_list_add();
		} else if( isset($_GET['subpage']) && $_GET['subpage'] == "recipients") {
			$this->mst_recipient_management();
		} else if( isset($_GET['subpage']) && $_GET['subpage'] == "digests") {
            $this->mst_digest_management();
        } else if( isset($_GET['subpage']) && $_GET['subpage'] == "managemembers") {
			$this->mst_list_members_add();
		}  else if( isset($_GET['subpage']) && $_GET['subpage'] == "managegroups") {
			$this->mst_list_groups_add();
		} else {

			?>
			<div class="wrap">
				<h2>
					<?php _e( "Mailing Lists", 'wp-mailster' ); ?>
					<a href="<?php echo admin_url(); ?>admin.php?page=mst_mailing_list_add"
					   class="add-new-h2"><?php _e( "Add New", 'wp-mailster' ); ?></a>
				</h2>

				<div id="poststuff">
					<div id="post-body" class="metabox-holder">
						<div id="post-body-content">
							<div class="meta-box-sortables ui-sortable">
								<form method="get">
									<input type="hidden" name="page"
									       value="<?php echo sanitize_text_field( $_REQUEST['page'] ); ?>"/>
									<?php
									$this->mailingLists_obj->prepare_items();
									$this->mailingLists_obj->search_box( __( 'Search', 'wp-mailster' ), 'search_box' );
									$this->mailingLists_obj->display(); ?>
								</form>
							</div>
						</div>
					</div>
					<br class="clear">
				</div>
			</div>
			<?php
		}
	}

	/**
	 * Mailing List Screen options
	 */
	public function mst_mailing_list_screen_option() {
		
		$option = 'per_page';
		$args   = array(
			'label'   => 'Mailing Lists',
			'default' => 20,
			'option'  => 'edit_post_per_page'
        );

		add_screen_option( $option, $args );

		$this->mailingLists_obj = new Mst_mailing_lists();
	}

/*********************************************\
 *********************************************
 
 Settings

 *********************************************
\*********************************************/	

	/**
	 * Settings Page
	 */
	public function mst_settings_page() {	
		include $this->WPMST_PLUGIN_DIR."/view/settings/mst_settings.php";
	}

/*********************************************\
 *********************************************

 Diagnosis

 *********************************************
\*********************************************/

	/**
	 * Settings Page
	 */
	public function wpmst_mailster_diagnosis_page() {
		if( isset( $_GET['action']) && $_GET['action'] == "fixdb") {
			include $this->WPMST_PLUGIN_DIR."/view/diagnosis/fixcollation.php";
		} else {
			include $this->WPMST_PLUGIN_DIR . "/view/diagnosis/mst_diagnosis.php";
		}
	}

/*********************************************\
 *********************************************
 
 Queued Emails

 *********************************************
\*********************************************/	


	/**
	 * Queued Page
	 */
	public function mst_queued_page() {
		if( isset( $_GET['subpage']) && $_GET['subpage'] == "details") {
			$this->mst_email_page();
		} else {
			?>
			<div class="wrap">
				<h2>
					<?php _e( "Queued Emails", 'wp-mailster' ); ?>
				</h2>
				<div id="poststuff">
					<div id="post-body" class="metabox-holder">
						<div id="post-body-content">
							<div class="meta-box-sortables ui-sortable">
								<form method="get" class="admin-table-with-custom-checks">
									<input type="hidden" name="page" value="<?php echo sanitize_text_field( $_REQUEST['page'] ); ?>"/>
									<?php
									$this->queued_obj->prepare_items();
									$this->queued_obj->search_box( __( 'Search', 'wp-mailster' ), 'search_box' );
									$this->queued_obj->display();
                                    ?>
								</form>
							</div>
						</div>
					</div>
					<br class="clear">
				</div>
			</div>
            <div id="dialog-confirm-delete" title="<?php echo __('Confirmation Required', 'wp-mailster') ?>" class="hidden">
                <?php echo __('Are you sure you want to delete the selected queue entries?', 'wp-mailster') ?>
            </div>
            <div id="dialog-confirm-clear" title="<?php echo __('Confirmation Required', 'wp-mailster') ?>" class="hidden">
                <?php echo __('Are you sure you want to delete ALL queue entries?', 'wp-mailster') ?>
            </div>
			<?php
		}
	}

	/**
	 * Queued Screen options
	 */
	public function mst_queued_screen_option() {
		
		$option = 'per_page';
		$args   = array(
			'label'   => 'Queued Emails',
			'default' => 20,
			'option'  => 'edit_post_per_page'
        );

		add_screen_option( $option, $args );

		$this->queued_obj = new Mst_queued();
	}

/*********************************************\
 *********************************************

Archived Emails

 *********************************************
\*********************************************/


	/**
	 * Archive Page
	 */
	public function mst_archived_page() {
        $log = MstFactory::getLogger();
        $log->debug('wp-mailster.php / mst_archived_page / REQ: '.print_r($_REQUEST, true));
		if( isset( $_GET['subpage']) && $_GET['subpage'] == "details") {
            $log->debug('mst_archived_page -> mst_email_page');
			$this->mst_email_page();
		} else if( isset( $_GET['subpage']) && $_GET['subpage'] == "resend" && !isset( $_REQUEST['resend-selection-done']) ) {
            $log->debug('mst_archived_page -> mst_resend');
			$this->mst_resend();
		}  else if( isset( $_GET['action']) && $_GET['action'] == "bulk-resend" && !isset( $_REQUEST['resend-selection-done']) ) {
            $log->debug('mst_archived_page -> mst_resend via bulk-resend');
            $ids = wp_parse_id_list($_REQUEST['bulk-action']);
            $_REQUEST['eid'] = $ids;
            $this->mst_resend();
        } else {
            $log->debug('mst_archived_page -> general part');
		    // when resend screen was used to trigger the resending...
            if( isset( $_REQUEST['resend-selection-done']) ) {
                $mstQueue = MstFactory::getMailQueue();
                $mListUtils = MstFactory::getMailingListUtils();
                $attachUtilts = MstFactory::getAttachmentsUtils();

                $mailIds = wp_parse_id_list($_POST['mails']);
                $listIds = wp_parse_id_list($_POST['targetLists']);

                if (!is_null($mailIds) && !is_null($listIds)) {
                    $log->debug('mst_archived_page Process Resends...');
                    for ($i = 0; $i < count($mailIds); $i++) {
                        $mailId = intval($mailIds[$i]);
                        $log->debug('Mail ' . $mailId . ' should be resend');
                        for ($j = 0; $j < count($listIds); $j++) {
                            $model = MstFactory::getMailModel();
                            $model->setId($mailId);
                            $mail = $model->getData();    // need to get mail every loop as it gets modified below
                            $listId = intval($listIds[$j]);
                            $mList = $mListUtils->getMailingList($listId);
                            $log->debug('Re-enqueue mail ' . $mailId . ' (from list ' . $mail->list_id . ') in mailing list: ' . $mList->id);

                            if ($mail->list_id == $listId) {
                                $log->debug('Email origins in the same list (id ' . $mail->list_id . '), therefore set it as unblocked and unsent');
                                $mstQueue->resetMailAsUnblockedAndUnsent($mailId);
                                $log->debug('Add recipients to queue for reset email...');
                                $mstQueue->enqueueMail($mail, $mList);
                            } else {
                                $log->debug('Email origins in a different list (id ' . $mail->list_id . '), thus create a copy and enqueue it as a new email...');
                                $mailCopyId = $mstQueue->saveAndEnqueueMail($mail, $mList);

                                if ($mail->has_attachments > 0) {
                                    $log->debug('Email contains attachments, therefore copy attachments table entries...');
                                    $attachUtilts->copyAttachments2Mail($mailId, $mailCopyId);
                                }
                            }

                            // ####### SAVE SEND EVENT  ########
                            $sendEvents = MstFactory::getSendEvents();
                            $recipCount = $mstQueue->getNumberOfQueueEntriesForMail($mailId);
                            $sendEvents->mailResend($mailId, $recipCount, $mail->list_id, $listId, get_current_user_id());
                            // #################################

                        }
                    }
                    ?>
                    <div class="notice notice-success">
                        <p><strong><?php echo sprintf(__('Sent %d emails to %d mailing lists', 'wp-mailster'), count($mailIds), count($listIds)); ?></strong></p>
                    </div><?php
                }
            }
            ?>

			<div class="wrap">
				<h2>
					<?php _e( "Archived Emails", 'wp-mailster' ); ?>
				</h2>

				<div id="poststuff">
					<div id="post-body" class="metabox-holder">
						<div id="post-body-content">
							<div class="meta-box-sortables ui-sortable">
								<form method="get" id="archivedMailsForm">
									<input type="hidden" name="page"
									       value="<?php echo sanitize_text_field( $_REQUEST['page'] ); ?>"/>
									<?php
									$this->archive_obj->prepare_items();
									$this->archive_obj->search_box( __( 'Search', 'wp-mailster' ), 'search_box' );
									$this->archive_obj->display(); ?>
								</form>
							</div>
						</div>
					</div>
					<br class="clear">
				</div>
			</div>
			<script>
				jQuery(document).ready(function () {
					jQuery('form#archivedMailsForm').submit(function (e) {
						if (jQuery('#bulk-action-selector-top').val() === 'bulk_resend') {
							e.preventDefault();
							var nonce = '<?php echo wp_create_nonce( 'mst_resend_archived' ); ?>';
							var checked = jQuery('input[name="bulk-action[]"]:checked');
							var url = 'admin.php?page=mst_archived&subpage=resend&_nonce=' + nonce;
							jQuery(checked).each(function (i, c) {
								url += '&eid[]=' + jQuery(c).val();
							});
							document.location.href = url;
						}

					});
				});
				jQuery('.ewc-filter-cat').change(function (event) {
					var catFilter = jQuery(this).val();
					document.location.href = 'admin.php?page=mst_archived&state=' + catFilter;
				});
			</script>
			<?php
		}
	}

	/**
	 * Archived Screen options
	 */
	public function mst_archived_screen_option() {

		$option = 'per_page';
		$args   = array(
			'label'   => 'Archived Emails',
			'default' => 20,
			'option'  => 'edit_post_per_page'
        );

		add_screen_option( $option, $args );

		$this->archive_obj = new Mst_archived();
	}

	/**
	 * Resend archived emails
	 */
	public function mst_resend() {
		include $this->WPMST_PLUGIN_DIR."/view/mail/mst_resend.php";
	}

/*********************************************\
 *********************************************
 
 Servers

 *********************************************
\*********************************************/	


	/**
	 * Servers Page
	 */
	public function mst_servers_page() {
		if( isset($_GET['subpage']) && $_GET['subpage'] == "edit") {
			$this->mst_servers_add();
		} else {
			?>
			<div class="wrap">
				<h2>
					<?php _e( "Servers", 'wp-mailster' ); ?>
					<a href="<?php echo admin_url(); ?>admin.php?page=mst_servers_add"
					   class="add-new-h2"><?php _e( "Add New", 'wp-mailster' ); ?></a>
				</h2>

				<div id="poststuff">
					<div id="post-body" class="metabox-holder">
						<div id="post-body-content">
							<div class="meta-box-sortables ui-sortable">
								<form method="get">
									<input type="hidden" name="page"
									       value="<?php echo sanitize_text_field( $_REQUEST['page'] ); ?>"/>
									<?php
									$this->server_obj->prepare_items();
									$this->server_obj->search_box( __( 'Search', 'wp-mailster' ), 'search_box' );
									$this->server_obj->display(); ?>
								</form>
							</div>
						</div>
					</div>
					<br class="clear">
				</div>
			</div>
			<?php
		}
	}

	/**
	 * Servers Screen options
	 */
	public function mst_servers_screen_option() {
		
		$option = 'per_page';
		$args   = array(
			'label'   => 'Servers',
			'default' => 20,
			'option'  => 'edit_post_per_page'
        );

		add_screen_option( $option, $args );

		$this->server_obj = new Mst_servers();
	}

/*********************************************\
 *********************************************
 
 Users

 *********************************************
\*********************************************/	

	/**
	 * Users Page
	 */
	public function mst_users_page() {
		if( isset($_GET['subpage']) && $_GET['subpage'] == "edit") {
			$this->mst_users_add();
		} else {
			?>
			<div class="wrap">
				<h2>
					<?php _e( "Users", 'wp-mailster' ); ?>
					<a href="<?php echo admin_url(); ?>admin.php?page=mst_users_add"
					   class="add-new-h2"><?php _e( "Add New", 'wp-mailster' ); ?></a>
				</h2>

				<div id="poststuff">
					<div id="post-body" class="metabox-holder">
						<div id="post-body-content">
							<div class="meta-box-sortables ui-sortable">
								<form method="get">
									<input type="hidden" name="page"
									       value="<?php echo sanitize_text_field( $_REQUEST['page'] ); ?>"/>
									<?php
									$this->users_obj->prepare_items();
									$this->users_obj->search_box(__('Search', 'wp-mailster'), 'search_box');
									$this->users_obj->display(); ?>
								</form>
							</div>
						</div>
					</div>
					<br class="clear">
				</div>
			</div>
			<?php
		}
	}

	/**
	 * Users Screen options
	 */
	public function mst_users_screen_option() {
		
		$option = 'per_page';
		$args   = array(
			'label'   => 'Users',
			'default' => 20,
			'option'  => 'edit_post_per_page'
        );

		add_screen_option( $option, $args );

		$this->users_obj = new Mst_users();
	}

/*********************************************\
 *********************************************
 
 Groups

 *********************************************
\*********************************************/	


	/**
	 * Groups Page
	 */
	public function mst_groups_page() {
		if( isset($_GET['subpage']) && $_GET['subpage'] == "edit") {
			$this->mst_groups_add();
		} else {

			?>
			<div class="wrap">
				<h2>
					<?php _e( "Groups", 'wp-mailster' ); ?>
					<a href="<?php echo admin_url(); ?>admin.php?page=mst_groups_add"
					   class="add-new-h2"><?php _e( "Add New", 'wp-mailster' ); ?></a>
				</h2>

				<div id="poststuff">
					<div id="post-body" class="metabox-holder">
						<div id="post-body-content">
							<div class="meta-box-sortables ui-sortable">
								<form method="post">
									<?php
									$this->groups_obj->prepare_items();
                                    $this->groups_obj->search_box( __( 'Search', 'wp-mailster' ), 'search_box' );
									$this->groups_obj->display(); ?>
								</form>
							</div>
						</div>
					</div>
					<br class="clear">
				</div>
			</div>
			<?php
		}
	}

	/**
	 * Groups Screen options
	 */
	public function mst_groups_screen_option() {
		
		$option = 'per_page';
		$args   = array(
			'label'   => 'Groups',
			'default' => 20,
			'option'  => 'edit_post_per_page'
        );

		add_screen_option( $option, $args );

		$this->groups_obj = new Mst_groups();
	}



/*********************************************\
 *********************************************
 
 Fields

 *********************************************
\*********************************************/

	function mst_display_hidden_field($name, $value) {
		?>
		<input type="hidden" name="<?php echo $name; ?>" id="<?php echo $name; ?>" value="<?php echo $value; ?>">
		<?php
	}

	function mst_display_input_field($title, $name, $value, $placeholder = null, $required = false, $isSmall = false, $info = null, $readonly = false, $embedHtml = false, $outerClasses = false) {
		?>
		<tr class="<?php if($required) { echo 'form-required '; } if($outerClasses) { echo $outerClasses; } ?>" >
			<th scope="row">
				<label for="<?php echo $name; ?>"><?php echo $title; ?> </label>
			</th>
			<td>
				<input type="text"
				       name="<?php echo $name; ?>"
				       value="<?php echo $value; ?>"
				       placeholder="<?php echo $placeholder; ?>"
					<?php echo($required == true ?'required':''); ?>
					<?php echo($info != null ?'title="'.$info.'"':''); ?>
					   class="<?php  echo ( $isSmall == true? 'small-text':'regular-text' ); ?>"
					   id="<?php echo $name; ?>"
					<?php echo($readonly?'disabled="disabled"':''); ?>
				>
				<?php if ( $required ) { ?>
					<span class="mst_required">*</span>
				<?php } ?>
				<?php if ( $info ) { ?>
					<span class="hTip" title="<?php echo $info ?>">
						<span class="dashicons dashicons-info"></span>
					</span>
				<?php } ?>
                <?php if ( $embedHtml ) {
                    echo $embedHtml;
                } ?>
			</td>
		</tr>
		<?php
	}
	function mst_display_textarea_field($title, $name, $value, $placeholder = null, $required = false, $info = null, $readonly = false, $embedHtml = false, $outerClasses = false) {
		?>
        <tr class="<?php if($required) { echo 'form-required '; } if($outerClasses) { echo $outerClasses; } ?>" >
			<th scope="row">
				<label for="<?php echo $name; ?>"><?php echo $title; ?> </label>
			</th>
			<td>
				<textarea
					name="<?php echo $name; ?>"
					placeholder="<?php echo $placeholder; ?>"
					<?php echo($required == true ?'required':''); ?>
					<?php echo($info != null ?'title="'.$info.'"':''); ?>
					class=""
					id="<?php echo $name; ?>"
                    <?php echo($readonly?'disabled="disabled"':''); ?>><?php echo $value; ?></textarea>
				<?php if ( $required ) { ?>
					<span class="mst_required">*</span>
				<?php } ?>
				<?php if ( $info ) { ?>
					<span class="hTip" title="<?php echo $info ?>">
						<span class="dashicons dashicons-info"></span>
					</span>
				<?php } ?>
                <?php if ( $embedHtml ) {
                    echo $embedHtml;
                } ?>
			</td>
		</tr>
		<?php
	}

	function mst_display_password_field($title, $name, $value, $placeholder = null, $required = false, $isSmall = false, $info = null, $embedHtml = false) {
		?>
		<tr class="<?php if($required) { echo 'form-required'; } ?>" >
			<th scope="row">
				<label for="<?php echo $name; ?>"><?php echo $title; ?> </label>
			</th>
			<td>
				<input type="password"
				       name="<?php echo $name; ?>"
				       value="<?php echo $value; ?>"
				       placeholder="<?php echo $placeholder; ?>"
					<?php echo($required == true ?'required':''); ?>
					<?php echo($info != null ?'title="'.$info.'"':''); ?>
					   class="<?php  echo ( $isSmall == true? 'small-text':'regular-text' ); ?>"
					   id="<?php echo $name; ?>"
				>
				<?php if ($required) { ?>
					<span class="mst_required">*</span>
				<?php } ?>
				<?php if ( $info ) { ?>
					<span class="hTip" title="<?php echo $info ?>">
						<span class="dashicons dashicons-info"></span>
					</span>
				<?php } ?>
                <?php if ( $embedHtml ) {
                    echo $embedHtml;
                } ?>
			</td>
		</tr>
		<?php
	}

	function mst_display_select_field($title, $name, $options = array(), $value = null, $placeholder = null, $required = false, $info = null, $readonlyOptions = array(), $disabled = false, $embedHtml = false, $outerClasses = false, $selectClasses = false, $multiSelect = false) {
        ?>
		<tr class="<?php if($required) { echo 'form-required '; } if($outerClasses) { echo $outerClasses; } ?>" >
			<th scope="row">
				<label for="<?php echo $name; ?>"><?php echo $title; ?> </label>
			</th>
			<td>
				<select name="<?php echo $name; ?><?php if ($multiSelect) { echo '[]'; }?>" id="<?php echo $name; ?>" <?php if ($required) { echo "required"; } ?> <?php if ($disabled) { echo 'disabled="disabled"'; } ?> <?php if ($selectClasses) { echo 'class="'.$selectClasses.'"'; } ?>  <?php if ($multiSelect) { echo 'multiple="multiple"'; } ?> >
					<?php
						foreach ( $options as $key => $option_value ) {
							$readonly = "";
							if( ! empty( $readonlyOptions ) ) {
								if( in_array($key, $readonlyOptions) ) {
									$readonly = "disabled";
								}
							}
							$valueMatch = false;
							if(is_array($value)){
                                if( in_array($key, $value) ) {
                                    $valueMatch = true;
                                }
                            }else{
                                $valueMatch = ($value == $key);
                            }?>
							<option value="<?php echo $key; ?>" <?php echo ($valueMatch?'selected':''); ?> <?php echo $readonly; ?> ><?php echo $option_value; ?></option>
						<?php } ?>
				</select>
				<?php if ($required) { ?>
					<span class="mst_required">*</span>
				<?php } ?>
				<?php if ( $info ) { ?>
					<span class="hTip" title="<?php echo $info ?>">
						<span class="dashicons dashicons-info"></span>
					</span>
				<?php } ?>
                <?php if ( $embedHtml ) {
                    echo $embedHtml;
                } ?>
			</td>
		</tr>
		<?php
	}

    function mst_display_truefalse_field($title, $name, $checked = false, $required = false, $info = null, $disabled = false, $embedHtml = false, $outerClasses = false) {
		?>
		<tr class="<?php if($required) { echo 'form-required '; }  if($outerClasses) { echo $outerClasses; } ?>" >
			<th scope="row">
				<?php echo $title; ?>
			</th>
			<td>
				<label for="<?php echo $name; ?>Yes">
					<input type='radio'
					       name='<?php echo $name; ?>'
					       id="<?php echo $name; ?>Yes"
						<?php echo($checked == true || $checked === 1 ?'checked':''); ?>
						   value="1"
                        <?php if ($disabled) { echo 'disabled="disabled"'; } ?>
					>
					<?php _e("Yes", 'wp-mailster'); ?>
				</label>
				<label for="<?php echo $name; ?>No">
					<input type='radio'
					       name='<?php echo $name; ?>'
					       id="<?php echo $name; ?>No"
						<?php echo($checked == false || $checked === 0 ?'checked':''); ?>
						   value="0"
                        <?php if ($disabled) { echo 'disabled="disabled"'; } ?>
					>
					<?php _e("No", 'wp-mailster'); ?>
				</label>
				<?php if ( $info ) { ?>
					<span class="hTip" title="<?php echo $info ?>">
						<span class="dashicons dashicons-info"></span>
					</span>
				<?php } ?>
                <?php if ( $embedHtml ) {
                    echo $embedHtml;
                } ?>
			</td>
		</tr>
		<?php
	}

    function mst_display_sometext($title, $someText, $info = null) {
        ?>
        <tr class="" >
            <th scope="row">
                <?php echo $title; ?>
            </th>
            <td>
                <label><?php echo $someText; ?></label>
                <?php if ( $info ) { ?>
                    <span class="hTip" title="<?php echo esc_attr($info); ?>">
						<span class="dashicons dashicons-info"></span>
					</span>
                <?php } ?>
            </td>
        </tr>
    <?php
    }

    function mst_display_spacer() {
        ?>
        <tr class="" >
            <th scope="row">
                &nbsp;
            </th>
            <td>
                <label>&nbsp;</label>
            </td>
        </tr>
    <?php
    }

	function mst_display_radio_field($title, $name, $value, $id, $text, $checked = false, $required = false) {
		?>
		<tr class="<?php if($required) { echo 'form-required'; } ?>" >
			<th scope="row">
				<?php echo $title; ?>
			</th>
			<td>
				<label for="<?php echo $id; ?>">
					<input type='radio' name='<?php echo $name; ?>' id="<?php echo $id; ?>" <?php if($checked) echo 'selected'; ?> value="<?php echo $value; ?>">
					<?php echo $text; ?>
				</label>
			</td>
		</tr>
		<?php
	}

	function mst_display_multiple_radio_fields($title, $name, $fields, $checked = 0, $required = false) {
		?>
		<tr class="<?php if($required) { echo 'form-required'; } ?>" >
			<th scope="row">
				<?php echo $title; ?>
			</th>
			<td>
				<?php foreach($fields as $field) { ?>
					<label for="<?php echo $field->id; ?>">
						<input type='radio' name='<?php echo $name; ?>' id="<?php echo $field->id; ?>" <?php echo( $checked == $field->value? 'checked' : ''); ?> <?php echo( $field->title != null? 'title="'.$field->title.'"' : ''); ?> value="<?php echo $field->value; ?>">
						<?php echo $field->text; ?>
					</label><?php if(property_exists($field, 'additionalHtml')){ echo $field->additionalHtml; } ?><br>
				<?php } ?>
			</td>
		</tr>
		<?php
	}

	function mst_display_simple_radio_field($name, $value, $id, $text, $checked = false, $required = false, $title = null, $disabled = false) {
		?>
		<label for="<?php echo $id; ?>">
			<input type='radio' name='<?php echo $name; ?>' id="<?php echo $id; ?>" <?php if($checked) echo 'checked'; ?> value="<?php echo $value; ?>"<?php echo ($title ? ' title="'.$title.'"' : ''); ?><?php echo ($disabled ? ' disabled="disabled"' : ''); ?> >
			<?php echo $text; ?>
		</label>
		<?php
	}



	public function wpmst_mailster_intro(){
		if( isset( $_GET['subpage']) && $_GET['subpage'] == "diagnosis") {
			if( isset( $_GET['action']) && $_GET['action'] == "fixdb") {
				include $this->WPMST_PLUGIN_DIR."/view/diagnosis/fixcollation.php";
			} else {
				include $this->WPMST_PLUGIN_DIR . "/view/diagnosis/mst_diagnosis.php";
			}
		} else if( isset( $_GET['subpage']) && $_GET['subpage'] == "import") {
			include $this->WPMST_PLUGIN_DIR."/view/csv/import.php";
		} else if( isset( $_GET['subpage']) && $_GET['subpage'] == "export") {
			include $this->WPMST_PLUGIN_DIR."/view/csv/export.php";
		} else {
			include $this->WPMST_PLUGIN_DIR."/view/stats/mst_intro.php";
		}
	}
	public function mst_email_page(){
		include $this->WPMST_PLUGIN_DIR."/view/mail/mst_emaildetails.php";
	}
	/* mailing List groups */
	public function wpmst_list_groups(){
		include $this->WPMST_PLUGIN_DIR."/view/mst_list_groups.php";
	}		
	/* Add List Groups */
	public function mst_list_groups_add(){
		include $this->WPMST_PLUGIN_DIR."/view/list/mst_list_groups_add.php";
	}

	/* Add Groups */
	public function mst_groups_add(){
		include $this->WPMST_PLUGIN_DIR."/view/groups/mst_groups_add.php";
	}
	/* Add Servers */
	public function mst_servers_add(){
		include $this->WPMST_PLUGIN_DIR."/view/servers/mst_servers_add.php";
	}
	/* Add Users */
	public function mst_users_add(){
		include $this->WPMST_PLUGIN_DIR."/view/users/mst_users_add.php";
	}	
	/* Add mailing List */
	public function mst_mailing_list_add(){
		include $this->WPMST_PLUGIN_DIR."/view/list/mst_mailing_list_add.php";
	}
	/* Manage List Recipients */
	public function mst_recipient_management(){
		include $this->WPMST_PLUGIN_DIR."/view/list/mst_recipient_management.php";
	}
	/* Add List Recipients */
	public function mst_list_members_add(){
		include $this->WPMST_PLUGIN_DIR."/view/list/mst_list_members_add.php";
	}
    /* Manage Recipients' Digests */
    public function mst_digest_management(){
        include $this->WPMST_PLUGIN_DIR."/view/list/mst_digest_management.php";
    }
	

	public function wpmst_view_message($type,$message){
		return "<div class='" . $type . "'><p><strong>" . $message . "</strong></p></div>";
	}

    public function wpmst_print_messages($messages){
        if($messages && is_array($messages) && count($messages) > 0){
            foreach($messages AS $message){
                echo $message;
            }
        }else{
            if($messages && is_scalar($messages)){
                echo $messages;
            }
        }
    }

	/*perpage limit*/
	function wpmst_perpage_limit($page){
		$perpage = 5; 
		$start = ($page - 1) * $perpage;
		return array( $perpage, $start );
	}
	/*pagination*/
	function wpmst_get_pagination( $total_count, $page,$perpage){
		$prevLinks = 'disabled';
		$nextLinks = 'disabled';
		$prev_page = '';
		$next_page = '';
		$last_page = ''; 
		 
		$pages = ceil( $total_count/$perpage );
		
			if( $pages > 1 ){
				$last_page = $pages;
				$prev_page = $page - 1;
				$next_page = $page + 1;
				if( $page == 1 || $page > 1 ){
					$nextLinks = 'wptl_pagi_nav';
					$prev_page = '';
				} 
				if( $page > 1 ){
					$prevLinks = 'wptl_pagi_nav';
					$prev_page = $page - 1;
					$next_page = $page + 1;
				} 
				if( $page == $pages ){
					$nextLinks = 'disabled';
					$prev_page = $page - 1;
					$next_page = '';
					$last_page = '';
				}
			 
			}
		return array( $pages, $prevLinks, $nextLinks, $prev_page, $next_page, $last_page );
	}
	
	/*Handling ajax request*/
	public function wpmst_pagination_request() {
		$currentUrl = sanitize_text_field($_POST['currentUrl']);
		$paged = intval($_POST['paged']);
		if ( ! $paged ) {
			$paged = 0;
		}
		$url_array = parse_url($currentUrl);

		/* modify current url query string. */
        $query_array = array();
		if(!empty($url_array['query'])){
			parse_str($url_array['query'], $query_array);
				if (array_key_exists('paged', $query_array)) unset($query_array['paged']);
					$query_array['paged'] = $paged;
		}
		
		$paramString = http_build_query($query_array);
		$new_url = $url_array['scheme'].'://'.$url_array['host'].$url_array['path'].'?'.$paramString;
		echo $new_url; 
		die;
	}
	public function wpmst_active_request() {
		global $wpdb;
		$id = intval($_POST['id']);
		if( null === $id ) {
			die;
		}
		$active = intval($_POST['active']);
		if(null === $active) {
			die;
		}
		echo $result = $wpdb->update($this->mailster_lists,array('active'=>$active),array( 'id' => $id ));
		die;
	}
	public function wpmst_delete_users(){
		global $wpdb;
		$delposts = wp_parse_id_list($_POST['deleteid']);
		foreach($delposts as $delpost){
			$delpost = intval($delpost);
			if( null !== $delpost) {
				$table = $wpdb->base_prefix . "mailster_users";
				$wpdb->delete( $table, array( 'ID' => $delpost ) );
			}
		}
	die;
	}
	
	public function wpmst_delete_groups(){
		global $wpdb;
		$delgroups = wp_parse_id_list($_POST['deleteid']);
		foreach($delgroups as $delgroup){
			$delgroup = intval($delgroup);
			if( null !== $delgroup) {
				$table = $wpdb->base_prefix . "mailster_groups";
				$wpdb->delete( $table, array( 'ID' => $delgroup ) );
			}
		}
	die;
	}
	
	public function wpmst_delete_lists(){
		global $wpdb;
		$delgroups = wp_parse_id_list($_POST['deleteid']);
		foreach($delgroups as $delgroup){
			$delgroup = intval($delgroup);
			if( null !== $delgroup) {
				$table = $wpdb->base_prefix . "mailster_lists";
				$wpdb->delete( $table, array( 'ID' => $delgroup ) );
			}
		}
	die;
	}
	
	public function wpmst_activate_lists(){
		global $wpdb;
		$delgroups = wp_parse_id_list($_POST['activateid']);
		foreach($delgroups as $delgroup){
			$delgroup = intval($delgroup);
			if( null !== $delgroup) {
				$table = $wpdb->base_prefix . "mailster_lists";
				$wpdb->update( $table, array( 'active' => 1 ), array( 'ID' => $delgroup ) );
			}
		}
	die;
	}
	
	public function wpmst_deactivate_lists(){
		global $wpdb;
		$delgroups = wp_parse_id_list($_POST['deactivateid']);
		foreach($delgroups as $delgroup){
			$delgroup = intval($delgroup);
			if( null !== $delgroup) {
				$table = $wpdb->base_prefix . "mailster_lists";
				$wpdb->update( $table, array( 'active' => 0 ), array( 'ID' => $delgroup ) );
			}
		}
	die;
	}
	
	public function wpmst_delete_user_list(){
		global $wpdb;
		$delgroups = wp_parse_id_list($_POST['deleteid']);
		foreach($delgroups as $delgroup){
			$delgroup = intval($delgroup);
			if( null !== $delgroup) {
				$table = $wpdb->base_prefix . "mailster_list_members";
				$wpdb->delete( $table, array( 'user_id' => $delgroup ) );
			}
		}
	die;
	}
	
	public function wpmst_delete_user_group(){
		global $wpdb;
		$delgroups = wp_parse_id_list($_POST['deleteid']);
		$groupid = intval($_POST['groupid']);
		if(null !== $groupid) {
			foreach ( $delgroups as $delgroup ) {
				$delgroup = intval( $delgroup );
				if ( null !== $delgroup ) {
					$wpdb->query( "delete from {$wpdb->prefix}mailster_group_users WHERE user_id=$delgroup AND group_id=$groupid" );
				}
			}
		}
	die;
	}
	
	public function wpmst_delete_group_list(){
		global $wpdb;
		$delgroups = wp_parse_id_list($_POST['deleteid']);
		foreach($delgroups as $delgroup){
			$delgroup = intval($delgroup);
			if( null !== $delgroup) {
				$table = $wpdb->base_prefix . "mailster_list_groups";
				$wpdb->delete( $table, array( 'group_id' => $delgroup ) );
			}
		}
        die;
	}

    public function wpmst_delete_notify(){
        $notifyId = intval($_POST['notifyId']);
        $rowNr = intval($_POST['rowNr']);
        $notifyUtils = MstFactory::getNotifyUtils();
        $mstUtils = MstFactory::getUtils();
        $res = ($notifyUtils->deleteNotify($notifyId) ? 'true' : 'false');
        $resultArray = array();
        $resultArray['res'] = $res;
        $resultArray['notifyId'] = $notifyId;
        $resultArray['rowNr'] = $rowNr;
        $jsonStr  = $mstUtils->jsonEncode($resultArray);
        echo $jsonStr;
        die;

    }

    public function wpmst_subscribe_plugin(){
        check_ajax_referer( 'wpmst_subscribe_plugin_nonce' );
        global $wpdb;
        $log = MstFactory::getLogger();
        $listUtils = MstFactory::getMailingListUtils();
        $subscrUtils = MstFactory::getSubscribeUtils();
        $mstUtils = MstFactory::getUtils();
        $resultObj = new stdClass();
        $res = 'Ajax called';
        $log->debug('wpmst_subscribe_plugin POST: '.print_r($_POST, true));

        $formId = sanitize_text_field($_REQUEST[ MstConsts::SUBSCR_POST_IDENTIFIER ]);
        $name = sanitize_text_field($_REQUEST[ MstConsts::SUBSCR_NAME_FIELD]);
        $email = sanitize_email($_REQUEST[MstConsts::SUBSCR_EMAIL_FIELD]);
        $listId = intval($_REQUEST[MstConsts::SUBSCR_ID_FIELD]);
        $digest = 0;
        if( isset($_REQUEST[MstConsts::SUBSCR_DIGEST_FIELD]) ) {
            $digest = sanitize_text_field($_REQUEST[ MstConsts::SUBSCR_DIGEST_FIELD ]);
        }else{
            $digest = MstConsts::DIGEST_NO_DIGEST;
        }


        $errors = 0;
        $resultMsg = '';
        $errorMsgs = array();
        $res = false;
        $foundInSession = false;
        $captchaRes = '';
        $add2Group = 0;
        $submitTxt = $submitConfirmTxt = $errorTxt = null;

        $transientId = 'wpmst_subscribe_forms'.'_'.$formId;
        $log->debug('wpmst_subscribe_plugin Getting info for transient ID '.$transientId.' from session...');
        $formInSession = get_transient($transientId);

        if($formInSession !== false){
            $log->debug('wpmst_subscribe_plugin Found in session formId: '.$formId.', obj: '.print_r($formInSession, true));
            $foundInSession = true;
            $captchaRes = property_exists($formInSession, 'captcha') ? $formInSession->captcha : false;
            $submitTxt = property_exists($formInSession, 'submitTxt') ? $formInSession->submitTxt : 'SUBMITTED OKAY!!!111';
            $submitConfirmTxt = property_exists($formInSession, 'submitConfirmTxt') ? $formInSession->submitConfirmTxt : 'SUBMITTED, NEED TO CONFIRM OKAY!!!111';
            $errorTxt = property_exists($formInSession, 'errorTxt') ? $formInSession->errorTxt : 'PROBLEM SUBSCRIBING!!!111';
            $add2Group = property_exists($formInSession, 'subscribeAdd2Group') ? $formInSession->subscribeAdd2Group : 0;
        }

        $noName = array('id'=>'no_name', 'msg'=>__( 'Please provide a name', 'wp-mailster' ));
        $noEmail = array('id'=>'no_email', 'msg'=>__( 'Please provide your email address', 'wp-mailster' ));
        $invalidEmail = array('id'=>'invalid_email', 'msg'=> __( 'Invalid email address', 'wp-mailster' ));
        $noListChosen = array('id'=>'no_list', 'msg'=> __( 'You have no mailing list chosen', 'wp-mailster' ));
        $tooMuchRecipients = array('id'=>'too_much_recip', 'msg'=>__( 'Too many recipients (Product limit)', 'wp-mailster' ));
        $registrationInactive = array('id'=>'reg_inactive', 'msg'=>__( 'Registration currently not possible', 'wp-mailster' ));
        $registrationOnlyForRegisteredUsers = array('id'=>'reg_only_registered', 'msg'=>__( 'Subscribing not allowed for unregistered users. Please login first.', 'wp-mailster' ));
        $captchaCodeWrong = array('id'=>'captcha_wrong', 'msg'=> __( 'The captcha code you entered was wrong, please try again', 'wp-mailster' ));

        if($foundInSession){
            $log->debug('wpmst_subscribe_plugin Post data: name='.$name.', email='.$email.', digest='.$digest.', listId='.$listId);

            if ($email === "") {
                $errorMsgs[] =  $noEmail;
                $errors++;
            }else if (!preg_match("/^.+?@.+$/", $email)) {
                $errorMsgs[] =  $invalidEmail ;
                $errors++;
            }
            if (($listId == "") || ($listId <= 0)) {
                $errorMsgs[] =  $noListChosen;
                $errors++;
            }

            $captchaValid = true;
            if($captchaRes){
                $mstCaptcha = $mstUtils->getCaptcha($captchaRes);
                $captchaValid = $mstCaptcha->isValid();
            }
            if($captchaValid == false){
                $errorMsgs[] =  $captchaCodeWrong;
                $errors++;
            }

            if ($errors <= 0) {
                $log->debug('wpmst_subscribe_plugin No errors in Post detected');

                if(($name === "")){ // name unknown and doesn't need to be supplied
                    $name = $email; // copy email in name to have something in the DB as the name
                }

                $list = $listUtils->getMailingList($listId);
                if($list->allow_subscribe == '1'){
                    if($list->public_registration == '1' || ($subscrUtils->isUserLoggedIn())){

                        if($list->subscribe_mode != MstConsts::SUBSCRIBE_MODE_DOUBLE_OPT_IN){
                            // default subscription
                            $log->debug('Double Opt-in subscribe mode not activated');
                            $log->debug('All OK, we can insert in DB');
                            $resultMsg = $submitTxt;
                            $success = $subscrUtils->subscribeUser($name, $email, $listId, $digest); // subscribing user...
                            $subscrUtils->sendWelcomeOrGoodbyeSubscriberMsg($name, $email, $listId, MstConsts::SUB_TYPE_SUBSCRIBE);
                            if($success == false){
                                $mstRecipients = MstFactory::getRecipients();
                                $cr = $mstRecipients->getTotalRecipientsCount($listId);
                                if($cr >= MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_REC)){
                                    $errors = $errors + 1;
                                    $log->debug('Too many recipients!');
                                    $errorMsgs[] =  $tooMuchRecipients;
                                }
                            }else{
                                if($add2Group){
                                    $log->debug('User is added to group '.$add2Group.' after subscribe');
                                    $subscrUser = $subscrUtils->getUserByEmail($email, true);
                                    $subscrUser->group_id = $add2Group;
                                    $log->debug(print_r($subscrUser, true));
                                    $groupUserModel = MstFactory::getGroupUsersModel();
                                    $add2GroupSuccess = $groupUserModel->store($subscrUser);
                                    $log->debug('User added to group '.$add2Group.': '.($add2GroupSuccess ? 'Yes' : 'No'));
                                }
                                // ####### TRIGGER NEW EVENT #######
                                $mstEvents = MstFactory::getEvents();
                                $mstEvents->userSubscribedOnWebsite($name, $email, $listId);
                                // #################################
                                $res = true;
                            }
                        }else{
                            $log->debug('Double Opt-in subscribe mode');
                            $resultMsg = $submitConfirmTxt;
                            $subscrUtils->subscribeUserWithDoubleOptIn($email, $name, $listId, $add2Group, $digest);
                            $res = true;
                        }

                    }else{
                        $errors = $errors + 1;
                        $log->debug('Cannot subscribe - registration is not allowed for not logged in users');
                        $errorMsgs[] = $registrationOnlyForRegisteredUsers;
                    }
                }else{
                    $errors = $errors + 1;
                    $log->debug('Cannot subscribe - registration is not allowed!');
                    $errorMsgs[] = $registrationInactive;
                }
            }else{
                $log->error('wpmst_subscribe_plugin: Errors in Post detected: '.print_r($errorMsgs, true));
            }

            if($res && $transientId){
                $log->debug('Removing info of transient ID '.$transientId.' from session...');
                delete_transient($transientId);
            }

        }else{
            $log->debug('Form ID '.$formId.' not found in session');
        }

        if($errors > 0){
            $resultMsg = $errorTxt;
        }

        $resultObj->res = $res;
        $resultObj->resultMsg = $resultMsg;
        $resultObj->errorMsgs = $errorMsgs;
        $jsonStr  = $mstUtils->jsonEncode($resultObj);
        echo $jsonStr;
        die;
    }


    public function wpmst_unsubscribe_plugin(){
        check_ajax_referer( 'wpmst_unsubscribe_plugin_nonce' );
        global $wpdb;
        $log = MstFactory::getLogger();
        $listUtils = MstFactory::getMailingListUtils();
        $subscrUtils = MstFactory::getSubscribeUtils();
        $recips = MstFactory::getRecipients();
        $mstUtils = MstFactory::getUtils();
        $resultObj = new stdClass();
        $res = 'Ajax Unsubscribe Called';

        $log->debug('wpmst_unsubscribe_plugin POST: '.print_r($_POST, true));

        $formId = sanitize_text_field($_REQUEST[MstConsts::SUBSCR_POST_IDENTIFIER]);
        $email = sanitize_email($_REQUEST[MstConsts::SUBSCR_EMAIL_FIELD]);
        $listId = intval($_REQUEST[MstConsts::SUBSCR_ID_FIELD]);

        $errors = 0;
        $resultMsg = '';
        $errorMsgs = array();
        $res = false;
        $foundInSession = false;
        $captchaRes = '';
        $removeFromGroup = 0;
        $submitTxt = $submitConfirmTxt = $errorTxt = null;

        $transientId = 'wpmst_subscribe_forms'.'_'.$formId;
        $log->debug('wpmst_unsubscribe_plugin Getting info for transient ID '.$transientId.' from session...');
        $formInSession = get_transient($transientId);

        if($formInSession !== false){
            $foundInSession = true;
            $captchaRes = property_exists($formInSession, 'captcha') ? $formInSession->captcha : false;
            $submitTxt = property_exists($formInSession, 'submitTxt') ? $formInSession->submitTxt : 'SUBMITTED OKAY!!!111';
            $submitConfirmTxt = property_exists($formInSession, 'submitConfirmTxt') ? $formInSession->submitConfirmTxt : 'SUBMITTED, NEED TO CONFIRM OKAY!!!111';
            $errorTxt = property_exists($formInSession, 'errorTxt') ? $formInSession->errorTxt : 'PROBLEM UNSUBSCRIBING!!!111';
            $removeFromGroup = property_exists($formInSession, 'unsubscribeRemoveFromGroup') ? $formInSession->unsubscribeRemoveFromGroup : 0;
        }

        $noEmail = array('id'=>'no_email', 'msg'=> __( 'Please provide your email address', 'wp-mailster' ));
        $invalidEmail = array('id'=>'invalid_email', 'msg'=>__( 'Please provide a valid email address', 'wp-mailster' ));
        $noListChosen = array('id'=>'no_list', 'msg'=>__( 'You have no mailing list chosen', 'wp-mailster' ));
        $unsubscribeInactive = array('id'=>'unsub_inactive', 'msg'=>__( 'Unsubscribing currently not possible', 'wp-mailster' ));
        $notSubscribed = array('id'=>'not_subscribed', 'msg'=>__( 'Email address is not subscribed', 'wp-mailster' ));
        $captchaCodeWrong = array('id'=>'captcha_wrong', 'msg'=>__( 'The captcha code you entered was wrong, please try again', 'wp-mailster' ));


        if($foundInSession){
            $log->debug('wpmst_unsubscribe_plugin Post data: email='.$email.', listId='.$listId);

            if ($email === "") {
                $errorMsgs[] =  $noEmail;
                $errors++;
            }
            else if (!preg_match("/^.+?@.+$/", $email)) {
                $errorMsgs[] =  $invalidEmail ;
                $errors++;
            }
            if (($listId == "") || ($listId <= 0)) {
                $errorMsgs[] =  $noListChosen;
                $errors++;
            }

            $captchaValid = true;
            if($captchaRes){
                $mstCaptcha = $mstUtils->getCaptcha($captchaRes);
                $captchaValid = $mstCaptcha->isValid();
            }
            if($captchaValid == false){
                $errorMsgs[] =  $captchaCodeWrong;
                $errors++;
            }

            $list = $listUtils->getMailingList($listId);
            if($list->allow_unsubscribe == '1'){
                $isRecipient = $recips->isRecipient($listId, $email);
                $log->debug('Check whether person with email ' . $email . ' is recipient of list ' . $listId . ', result: ' . ($isRecipient ? 'Is recipient' : 'NOT a recipient'));

                if($isRecipient == false){
                    $errorMsgs[] =  $notSubscribed;
                    $errors++;
                }

                if ($errors <= 0) {
                    if($list->unsubscribe_mode != MstConsts::UNSUBSCRIBE_MODE_DOUBLE_OPT_IN){
                        $log->debug('Double Opt-in unsubscribe mode not activated');
                        $log->debug('All OK, we can delete from DB');
                        $resultMsg = $submitTxt;
                        list($success, $tmpUser) = $subscrUtils->unsubscribeUser($email, $listId); // unsubscribing user...
                        if($success){
                            $subscrUtils->sendWelcomeOrGoodbyeSubscriberMsg($tmpUser['name'], $email, $listId, MstConsts::SUB_TYPE_UNSUBSCRIBE);
                            // ####### TRIGGER NEW EVENT #######
                            $mstEvents = MstFactory::getEvents();
                            $mstEvents->userUnsubscribedOnWebsite($email, $listId);
                            // #################################
                        }else{
                            $log->error('wpmst_unsubscribe_plugin Not possible to unsubscribe email '.$email.' from list '.$listId.', tmpUser: '.print_r($tmpUser, true));
                        }

                        if($removeFromGroup && $tmpUser && $tmpUser['user_found']){
                            $log->debug('User is removed from group '.$removeFromGroup.' after unsubscribe');
                            MstFactory::getGroupModel(); // to have it included
                            MstFactory::getUserModel(); // to have it included
                            $User = new MailsterModelUser($tmpUser['user_id'], $tmpUser['is_core_user']);
                            $removeResult = $User->removeFromGroup($removeFromGroup);
                            $log->debug('User was removed from group '.$removeFromGroup.': '.($removeResult !== false ? 'OK' : 'FAILED'));
                        }else{
                            $log->debug('removeFromGroup not active: '.$removeFromGroup);
                        }

                    }else{
                        $log->debug('Double Opt-in unsubscribe mode');
                        $resultMsg = $submitConfirmTxt;
                        $subscrUtils->unsubscribeUserWithDoubleOptIn($email, $listId, $removeFromGroup);
                    }
                    $res = true;
                }else{
                    $log->error('wpmst_unsubscribe_plugin: Errors in Post detected: '.print_r($errorMsgs, true));
                }
            }else{
                $errors = $errors + 1;
                $log->debug('Cannot unsubscribe - unsubscribing is not allowed!');
                $errorMsgs[] = $unsubscribeInactive;
            }

            if($res && $transientId){
                $log->debug('Removing info of transient ID '.$transientId.' from session...');
                delete_transient($transientId);
            }

        }else{
            $log->debug('Form ID '.$formId.' not found in session');
        }

        if($errors > 0){
            $resultMsg = $errorTxt;
        }

        $resultObj->res = $res;
        $resultObj->resultMsg = $resultMsg;
        $resultObj->errorMsgs = $errorMsgs;
        $jsonStr  = $mstUtils->jsonEncode($resultObj);
        echo $jsonStr;
        die;
    }
	
}
$wpmst_mailster = new wpmst_mailster(__FILE__);
function mst_get_version() {
	if( is_admin() ) {
        if ( !function_exists('get_plugin_data') ){
            require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
        }
	    $plugin_data = get_plugin_data( __FILE__ );
	    $plugin_version = $plugin_data['Version'];
	} else {
		$plugin_version = "not run through admin";
	}
    return $plugin_version;
}

function mst_execute_procedures() {
	$system = new plgSystemMailster();
	$system->onAfterInitialise();
}
add_action( 'shutdown', 'mst_execute_procedures' );

include_once  plugin_dir_path( __FILE__ ) . "conncheck.php";

add_action( 'wp_ajax_resetplgtimer', 'resetplgtimer_callback' );
function resetplgtimer_callback() {
	$pluginUtils = MstFactory::getPluginUtils();
	$mstUtils = MstFactory::getUtils();
	$resultArray = array();
	$res = __( 'Reset timer called', 'wp-mailster' );
	$ajaxParams = sanitize_text_field($_POST['mtrAjaxData']);
	$ajaxParams = $mstUtils->jsonDecode(stripslashes($ajaxParams));
	$task = $ajaxParams->{'task'};
	if($task == 'resetPlgTimer'){
		if($pluginUtils->resetMailPluginTimes()){
			$res = __( 'Reset', 'wp-mailster' ) . ' ' . __( 'Ok', 'wp-mailster' );
		}else{
			$res = __( 'Reset', 'wp-mailster' ) . ' ' . __( 'Not Ok', 'wp-mailster' );
		}
	}else{
		$res = __( 'Unknown task', 'wp-mailster' ) . ': ' . $task;
	}	
	$resultArray['checkresult'] = $res;
	echo json_encode($resultArray);
	exit();
}


add_action( 'wp_ajax_saveLicChkResult', 'wpmst_ajax_callback_saveLicChkResult' );
function wpmst_ajax_callback_saveLicChkResult() {
    $log = MstFactory::getLogger();
    $log->debug('wpmst_ajax_callback_saveLicChkResult');
    $mstUtils = MstFactory::getUtils();
    $resultArray = array();
    $res = __( 'Reset timer called', 'wp-mailster' );
    $ajaxParams = sanitize_text_field($_POST['mtrAjaxData']);
    $ajaxParams = $mstUtils->jsonDecode(stripslashes($ajaxParams));
    $log->debug('wpmst_ajax_callback_saveLicChkResult params: '.print_r($ajaxParams, true));
    $daysLeft = intval($ajaxParams->{'daysLeft'});
    $expiryDate = intval($ajaxParams->{'expiryDate'});
    $expiryStatus = intval($ajaxParams->{'expiryStatus'});
    $version = preg_replace("/[^a-z0-9.]+/i", "", $ajaxParams->{'version'});
    $lastCheckTime = time();
    update_option('wpmst_cfg_last_lic_days_left', $daysLeft);
    update_option('wpmst_cfg_last_lic_expiry_date', $expiryDate);
    update_option('wpmst_cfg_last_lic_expiry_status', $expiryStatus);
    update_option('wpmst_cfg_last_lic_version', $version);
    update_option('wpmst_cfg_last_lic_check_time', $lastCheckTime);
    $res = 'OK';
    $resultArray['checkresult'] = $res;
    $jsonStr  = $mstUtils->jsonEncode($resultArray);
    echo "[" . $jsonStr . "]";
    exit();
}


add_action( 'wp_ajax_wpmst_get_lic_subinfo', 'wpmst_ajax_callback_get_lic_subinfo' );
function wpmst_ajax_callback_get_lic_subinfo(){
    $log = MstFactory::getLogger();
    $log->debug('wpmst_ajax_callback_get_lic_subinfo');
    check_ajax_referer('wpmst_licsubinfo');
    global $wp_version;
    $key = sanitize_text_field($_REQUEST['key']);
    $url = sanitize_text_field($_REQUEST['url']);
    $mstVersion = mst_get_version();
    $dateFormat = get_option('date_format');

    $log->debug('wpmst_ajax_callback_get_lic_subinfo key: '.$key.', url: '.$url.', mstVersion: '.$mstVersion.', wpVersion: '.$wp_version.', dateFormat: '.$dateFormat);

    $mstAjaxData = (object)array(
        'key' => $key,
        'url' => $url,
        'wpversion' => $wp_version,
        'mstversion' => $mstVersion,
        'date_format' => $dateFormat
    );
    $mstAjaxDataStr = json_encode($mstAjaxData);
    $requestUri = 'https://www.wpmailster.com/subinfo'. '?mstAjaxData=' . $mstAjaxDataStr;
    $log->debug('wpmst_ajax_callback_get_lic_subinfo requestUri '.$requestUri);
    $response = wp_remote_post( $requestUri );

    if($response instanceof WP_Error){
        $log->error('wpmst_ajax_callback_get_lic_subinfo error '.$response->get_error_message());
        die( json_encode(array('error' => $response->get_error_message())) );
    }else{
        $log->debug('wpmst_ajax_callback_get_lic_subinfo resp: '.print_r($response['body'], true));
        die( $response['body'] );
    }
}


add_action( 'wp_ajax_wpmst_do_lic_reavulation', 'wpmst_ajax_callback_do_lic_reavulation' );
function wpmst_ajax_callback_do_lic_reavulation(){
    $log = MstFactory::getLogger();
    $log->debug('wpmst_ajax_callback_do_lic_reavulation');
    check_ajax_referer('wpmst_licreavluate');
    global $wp_version;
    $key = sanitize_text_field($_REQUEST['key']);
    $url = sanitize_text_field($_REQUEST['url']);
    $mstVersion = mst_get_version();
    $dateFormat = get_option('date_format');

    $log->debug('wpmst_ajax_callback_do_lic_reavulation key: '.$key.', url: '.$url.', mstVersion: '.$mstVersion.', wpVersion: '.$wp_version.', dateFormat: '.$dateFormat);

    $mstAjaxData = (object)array(
        'key' => $key,
        'url' => $url,
        'wpversion' => $wp_version,
        'mstversion' => $mstVersion,
        'date_format' => $dateFormat
    );
    $mstAjaxDataStr = json_encode($mstAjaxData);
    $requestUri = 'https://www.wpmailster.com/changeserial'. '?mstAjaxData=' . $mstAjaxDataStr;
    $log->debug('wpmst_ajax_callback_do_lic_reavulation requestUri '.$requestUri);
    $response = wp_remote_post( $requestUri );

    if($response instanceof WP_Error){
        $log->error('wpmst_ajax_callback_do_lic_reavulation error '.$response->get_error_message());
        die( json_encode(array('error' => $response->get_error_message())) );
    }else{
        $log->debug('wpmst_ajax_callback_do_lic_reavulation resp: '.print_r($response['body'], true));
        die( $response['body'] );
    }
}

//ajax load of server details
add_action("wp_ajax_wpmst_get_server_data", "wpmst_get_server_data");
function wpmst_get_server_data() {
	$serverId = intval($_POST['server_id']);
	if( null !== $serverId) {
		$Server = new MailsterModelServer($serverId);
		$ret    = $Server->getFormData();
		echo json_encode( $ret );
	}
	exit();
}

//ajax check serial key
add_action("wp_ajax_wpmst_checkserial", "wpmst_ajax_checkserial");
function wpmst_ajax_checkserial() {
	if(function_exists('wpmst_key_validate')){
        $mstUtils = MstFactory::getUtils();
        $resultArray = array();
        $resultArray['license_before'] = get_option('wpmst_cfg_version_license');
		wpmst_key_validate();
        $resultArray['license_after'] = get_option('wpmst_cfg_version_license');
        $resultArray['license_changed'] = ($resultArray['license_before'] !== $resultArray['license_after']);
        $jsonStr  = $mstUtils->jsonEncode($resultArray);
        echo $jsonStr;
        die;
    }
}

add_action("wp_ajax_wpmst_deleteLogFile", "wpmst_deleteLogFile");
function wpmst_deleteLogFile(){
	$log = MstFactory::getLogger();
	$logFile = $log->getLogFile();

	if(unlink($logFile)){
		$result =  __( 'Deleted Log file', 'wp-mailster' );
	}else{
		$result = __( 'Failed to delete Log file', 'wp-mailster' );
	}
    try{
	    $log->initFile();
    }catch(Exception $e){
        /// no action here
    }
	echo $result;
	exit();
}


add_action( 'plugins_loaded', function() {
    if ( isset( $_GET['mst_download'] )){
        $log = MstFactory::getLogger();
        $fileName = null;
        $filePath = null;
        $mstDownload = sanitize_text_field($_GET['mst_download']);
        $log->debug('mst_download: '.$mstDownload);
        if($mstDownload === "wpmstlog") {
            if(!check_admin_referer('mst_dwl_log')){
                die('Not allowed to download attachment');
            }
            $filePath = $log->getLogFile();
            $fileName = 'wpmst.log';
        }elseif($mstDownload === 'attachment'){
            $attachId = intval($_GET['id']);
            $backendFlag = intval($_GET['b']);
            $attachUtils = MstFactory::getAttachmentsUtils();
            $attach = $attachUtils->getAttachment($attachId);
            $log->debug('mst_download attachment '.print_r($attach, true));
            if($backendFlag>0){
                if(!check_admin_referer('mst_dwl_attachment'.$attachId)){
                    die('Not allowed to download attachment [B]');
                }
            }else{
                $nonce = sanitize_text_field( $_REQUEST['_wpnonce'] );
                if(!wp_verify_nonce($nonce, 'mst_dwl_attach_frontend'.$attachId)){
                    die('Not allowed to download attachment [F]');
                }
            }
            $upload_dir = wp_upload_dir();
            $filePath = $upload_dir['basedir'].$attach->filepath.DIRECTORY_SEPARATOR.$attach->filename;
            $fileName = $attach->filename;
        }
        if(!is_null($filePath)){
            // $log->debug('Download '.$fileName.' in '.$filePath);
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . rawurldecode( $fileName ) . '"' );
            header('Content-Transfer-Encoding: binary' );
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0' );
            header('Pragma: public' );
            header('Content-Length: ' . filesize( $filePath ) );
            ob_clean();
            flush();
            readfile( $filePath );
            exit;
        }
    }
});


// profile short code [mst_profile] is always available
include_once( plugin_dir_path( __FILE__ ) . "shortcodes/mst_view_mailing_list.php");
add_shortcode( 'mst_profile', array( 'mst_frontend_mailing_list', 'mst_profile' ) );


// subscribe/unsubscribe short codes [mst_subscribe] / [mst_unsubscribe] are always available
include_once( plugin_dir_path( __FILE__ ) . "shortcodes/mst_view_subscribe.php");
add_shortcode( 'mst_subscribe', array( 'mst_frontend_subscribe', 'mst_subscribe' ) );
add_shortcode( 'mst_unsubscribe', array( 'mst_frontend_subscribe', 'mst_unsubscribe' ) );

//shortcodes in Society and Enterprise [mst_mailing_lists] and [mst_emails]
/*  */


/* @@REMOVE_START_MST_society@@ */
/* @@REMOVE_START_MST_enterprise@@ */
// add_shortcode( 'mst_mailing_lists', array( 'mst_frontend_mailing_list', 'mst_shortcode_not_available_mst_mailing_lists' ) );
// add_shortcode( 'mst_emails', array( 'mst_frontend_mailing_list', 'mst_shortcode_not_available_mst_emails' ) );
/* @@REMOVE_END_MST_enterprise@@ */
/* @@REMOVE_END_MST_society@@ */


/*  */


//message for free version downgrade
function wpmst_admin_notice() {
    $cfgCurrentVersion = 'free';
    $cfgVersionLicense = get_option('wpmst_cfg_version_license');
    if(($cfgVersionLicense == 'free' || $cfgVersionLicense == '') && $cfgCurrentVersion != 'free') {
        if(class_exists('MstFactory')){
            $log = MstFactory::getLogger();
            $log->warning('Potential downgrade alarm! wpmst_cfg_version_license = '.$cfgVersionLicense.', curr installed version = '.$cfgCurrentVersion);
        }
        ?>
        <div class="notice notice-warning is-dismissible wpmst-license-msg-notice">
            <p><?php _e( 'Your WP Mailster version will become the FREE edition the next time you update it. Go to <a href="admin.php?page=wpmst_settings">WPMailster settings</a> to add/update your license key', 'wp-mailster' ); ?></p>
        </div>
        <input type="hidden" id="wpmst-licreavluate-nonce" value="<?php echo wp_create_nonce('wpmst_licreavluate'); ?>" />
        <script type="text/javascript">
            var callBackLicEval = function reEvaluationResult(response){
                console.log(response);
                if(response) {
                    var resultObject = jQuery.parseJSON(response);
                    console.log(resultObject);
                    if(resultObject && resultObject.status == 0) {
                        jQuery('.wpmst-license-msg-notice').show(250);
                    } else {
                        jQuery('.wpmst-license-msg-notice').hide(250);
                        // update version_license in the background
                        var data = {
                            'action': 'wpmst_checkserial'
                        };
                        jQuery.post(ajaxurl, data, function(resp){
                            console.log(resp);
                            var resultData = JSON.parse(resp);
                            if(resultData) {
                                console.log(resultData);
                                console.log("license updated in the background");
                            }else {
                                console.log("Could not complete ajax request");
                            }
                        });
                    }
                }else{
                    console.log("Could not complete ajax request");
                    jQuery('.wpmst-license-msg-notice').show(250);
                }
            };
            jQuery(document).ready(function() {
                reEvaluateLic(<?php echo json_encode(trim(get_option('wpmst_cfg_license_key'))); ?>, callBackLicEval);
            });
        </script>
        <?php
    }
}
/*  */


/*  */


// [frontend endpoint] double opt in url
add_action( 'plugins_loaded', 'wpmst_double_opt_check', 1 );
function wpmst_double_opt_check() {
    if(isset($_GET['confirm_subscribe']) || isset($_GET['confirm_unsubscribe'])){  // only load stuff when needed
        $log = MstFactory::getLogger();
        $recips = MstFactory::getRecipients();
        $listUtils = MstFactory::getMailingListUtils();
        $hashUtils = MstFactory::getHashUtils();
        $subscribeUtils = MstFactory::getSubscribeUtils();
    }else{
        return; // nothing relevant follows below
    }

    $redirectLocation = null;
    if(isset($_GET['confirm_subscribe']) && $_GET['confirm_subscribe'] == "indeed") {
        $log->debug('indeed - subscribe: '.print_r($_REQUEST, true));
        $successful = 0;
        if( isset($_REQUEST['sm']) ) {
            $subscribeMode = intval($_REQUEST['sm']);
        } else {
            $subscribeMode = 0;
        }
        if( isset($_REQUEST['sa']) ) {
            $salt = intval($_REQUEST['sa']);
        } else {
            $salt = rand();
        }
        if( isset($_REQUEST['h']) ) {
            $hash = sanitize_text_field($_REQUEST['h']);
        } else {
            $hash = "";
        }
        if($subscribeMode == 0){
            $log->debug('Default subscribe, not yet existing...');
        } elseif($subscribeMode == MstConsts::SUBSCRIBE_MODE_DOUBLE_OPT_IN){
            if( isset( $_REQUEST["si"])) {
                $subscriptionId = intval($_REQUEST['si']);
            } else {
                $subscriptionId = 0;
            }
            $log->debug('Double Opt-In subscribe');
            $subscribeInfo = $subscribeUtils->getSubscribeInfo($subscriptionId);
            if(!is_null($subscribeInfo)){
                $log->debug('Found subscribe info: '.print_r($subscribeInfo, true));
                $saltedKeyHash = $hashUtils->getSubscribeKey($salt, $subscribeInfo->hashkey);
                $hashOk = ($saltedKeyHash==$hash);
            }else{
                $hashOk = false;
            }
            if($hashOk){
                $listId = $subscribeInfo->list_id;
                $subscrUtils = MstFactory::getSubscribeUtils();
                $success = $subscrUtils->subscribeUser($subscribeInfo->name, $subscribeInfo->email, $listId, $subscribeInfo->digest_freq);
                $subscrUtils->sendWelcomeOrGoodbyeSubscriberMsg($subscribeInfo->name, $subscribeInfo->email, $listId, MstConsts::SUB_TYPE_SUBSCRIBE);
                if($success){
                    $log->debug('Subscribing successful');
                    if($subscribeInfo->add2group){
                        $log->debug('User is added to group '.$subscribeInfo->add2group.' after subscribe');

                        $subscrUser = $subscrUtils->getUserByEmail($subscribeInfo->email, true);
                        $subscrUser->group_id = $subscribeInfo->add2group;
                        $log->debug('Subscr User:'.print_r($subscrUser, true));
                        $groupUserModel = MstFactory::getGroupUsersModel();
                        $add2GroupSuccess = $groupUserModel->store($subscrUser);
                        $log->debug('User added to group '.$subscribeInfo->add2group.': '.($add2GroupSuccess ? 'Yes' : 'No'));
                    }
                    // ####### TRIGGER NEW EVENT #######
                    $mstEvents = MstFactory::getEvents();
                    $mstEvents->userSubscribedOnWebsite($subscribeInfo->name, $subscribeInfo->email, $listId);
                    // #################################
                    $subscrUtils->deleteSubscriptionInfo($subscribeInfo->id);
                    $successful = 1;
                }else{
                    $log->debug('Subscribing failed');
                    $successful = 0;
                }
            }else{
                $log->debug('Subscribing failed, hash was not correct');
                $successful = 0;
            }
        }
        $redirectLocation = plugins_url() . "/wp-mailster/view/subscription/subscribe.php?success=" . $successful;

    }elseif(isset($_GET['confirm_unsubscribe']) && $_GET['confirm_unsubscribe'] == "indeed") {
        $log->debug('indeed - unsubscribe: '.print_r($_REQUEST, true));
        if( isset($_REQUEST['sm']) ) {
            $unsubscribeMode = intval($_REQUEST['sm']);
        } else {
            $unsubscribeMode = 0;
        }
        if( isset($_REQUEST['sa']) ) {
            $salt = intval($_REQUEST['sa']);
        } else {
            $salt = rand();
        }
        if( isset($_REQUEST['h']) ) {
            $hash = sanitize_text_field($_REQUEST['h']);
        } else {
            $hash = "";
        }

        $defaultErrorMsg = "";
        $notSubscribed = false;
        $unsubscribeCompletedOk = false;
        $unsubscribeFailed = false;
        $unsubscribeFormNeeded = false;
        $nonce = false;

        if($unsubscribeMode == 0){
            $log->debug('Default unsubscribe');
            if( isset($_REQUEST['m']) ) {
                $mailId = intval($_REQUEST['m']);
            } else {
                $mailId = 0;
            }
            if( isset($_REQUEST['ea']) ) {
                $email = sanitize_text_field($_REQUEST['ea']);
            } else {
                $email = null;
            }
            $listId = $listUtils->getMailingListIdByMailId($mailId);
            $hashOk = $hashUtils->checkUnsubscribeKeyOfMail($mailId, $salt, $hash);
            if($listId){
                $list = $listUtils->getMailingList($listId);
                $listName = $list->name;
            }else{
                $listName = null;
            }
            if($hashOk){
                $unsubscribeFormNeeded = true;
            }else{
                $unsubscribeFailed = true;
            }

            $requestId = sprintf("%08x", abs(crc32($_SERVER['REMOTE_ADDR'] . $_SERVER['REQUEST_TIME'] . $_SERVER['REMOTE_PORT'])));
            $transientId = 'mst_frontend_unsub_confirm'.'_'.$requestId;

            $unsubInfo = array();
            $unsubInfo['nonce_mst_frontend_unsub_confirm'] =  wp_create_nonce( 'mst_frontend_unsub_confirm' );
            $unsubInfo['hashOk'] = $hashOk;
            $unsubInfo['listId'] = $listId;
            $unsubInfo['listName'] = $listName;
            $unsubInfo['email'] = $email;
            $unsubInfo['mailId'] = $mailId;
            $unsubInfo['salt'] = $salt;
            $unsubInfo['hash'] = $hash;
            $unsubInfo['query'] = get_site_url().'?confirm_unsubscribe=indeed2';

            set_transient($transientId, $unsubInfo, HOUR_IN_SECONDS);

        } elseif($unsubscribeMode == MstConsts::UNSUBSCRIBE_MODE_DOUBLE_OPT_IN) {
            if( isset($_REQUEST['si']) ) {
                $subscriptionId = intval($_REQUEST['si']);
            } else {
                $subscriptionId = 0;
            }
            $log->debug('Double Opt-In unsubscribe with subscriptionId: '.$subscriptionId);
            $subscribeInfo = $subscribeUtils->getSubscribeInfo($subscriptionId);
            if(!is_null($subscribeInfo)){
                $log->debug('Found (un)subscribe info: '.print_r($subscribeInfo, true));
                $saltedKeyHash = $hashUtils->getUnsubscribeKey($salt, $subscribeInfo->hashkey);
                $hashOk = ($saltedKeyHash==$hash);
            }else{
                $hashOk = false;
                $notSubscribed = true;
                $log->debug('Did NOT find subscribe info');
            }

            if($hashOk){
                $log->debug('Hash OK');
                $listId = $subscribeInfo->list_id;
                $subscrUtils = MstFactory::getSubscribeUtils();
                $isRecipient = $recips->isRecipient($listId, $subscribeInfo->email);
                if($isRecipient){
                    $log->debug($subscribeInfo->email.' is a recipient of list '.$listId);
                    list($success, $tmpUser) = $subscrUtils->unsubscribeUser($subscribeInfo->email, $listId);
                    if($success){
                        $subscrUtils->sendWelcomeOrGoodbyeSubscriberMsg($tmpUser['name'], $subscribeInfo->email, $listId, MstConsts::SUB_TYPE_UNSUBSCRIBE);
                        $log->debug('Unsubscribing successful');
                        // ####### TRIGGER NEW EVENT #######
                        $mstEvents = MstFactory::getEvents();
                        $mstEvents->userUnsubscribedOnWebsite($subscribeInfo->email, $listId);
                        // #################################
                        $subscrUtils->deleteSubscriptionInfo($subscribeInfo->id);
                        $unsubscribeCompletedOk = true;
                    } else {
                        $log->error('Unsubscribing failed');
                        $log->error('wpmst_double_opt_check - double-opt-in - Not possible to unsubscribe email '.$subscribeInfo->email.' from list '.$listId.', tmpUser: '.print_r($tmpUser, true));
                        $unsubscribeFailed = true;
                    }

                    $removeFromGroup = $subscribeInfo->remove_from_group;
                    if($removeFromGroup && $tmpUser && $tmpUser['user_found']){
                        $log->debug('User is removed from group '.$removeFromGroup.' after unsubscribe');
                        MstFactory::getGroupModel(); // to have it included
                        MstFactory::getUserModel(); // to have it included
                        $User = new MailsterModelUser($tmpUser['user_id'], $tmpUser['is_core_user']);
                        $removeResult = $User->removeFromGroup($removeFromGroup);
                        $log->debug('User was removed from group '.$removeFromGroup.': '.($removeResult !== false ? 'OK' : 'FAILED'));
                    }else{
                        $log->debug('removeFromGroup not active: '.$removeFromGroup);
                    }

                } else {
                    $log->debug($subscribeInfo->email.' is NOT a recipient of list '.$listId);
                    $notSubscribed = true;
                }
            } else {
                $log->debug('Hash not ok');
                $unsubscribeFailed = true;
            }
        }


        $query = array();
        if($notSubscribed){
            $query[] = 'ns=1';
        }
        if($unsubscribeCompletedOk){
            $query[] = 'sc=1';
        }
        if($unsubscribeFailed){
            $query[] = 'sf=1';
        }
        if($unsubscribeFormNeeded){
            $query[] = 'uf=1';
            if(isset($requestId)) {
                $query[] = 'rid=' . urlencode($requestId);
            }
        }
        $queryStr = implode('&', $query);

        $redirectLocation = plugins_url() . "/wp-mailster/view/subscription/unsubscribe.php?" . $queryStr;

    }elseif(isset($_GET['confirm_unsubscribe']) && $_GET['confirm_unsubscribe'] == "indeed2") {
        $log->debug('indeed2 - unsubscribe');

        $requestId = sanitize_text_field($_REQUEST['rid']);
        $transientId = 'mst_frontend_unsub_confirm'.'_'.$requestId;
        $unsubInfo = get_transient($transientId);
        $log->debug('indeed2 - unsubscribe transientId: '.$transientId.', unsubInfo: '.print_r($unsubInfo, true));

        $listId = array_key_exists('listId', $unsubInfo) ? $unsubInfo['listId'] : 0;
        $mailId = array_key_exists('mailId', $unsubInfo) ? $unsubInfo['mailId'] : 0;
        $salt = array_key_exists('salt', $unsubInfo) ? $unsubInfo['salt'] : '';
        $hashOk = (boolean)(array_key_exists('hashOk', $unsubInfo) ? $unsubInfo['hashOk'] : false);
        $nonceFromSession = array_key_exists('nonce_mst_frontend_unsub_confirm', $unsubInfo) ? $unsubInfo['nonce_mst_frontend_unsub_confirm'] : '';

        $hash = array_key_exists('hash', $unsubInfo) ? $unsubInfo['hash'] : '';
        $removeFromGroup = array_key_exists('unsubscribeRemoveFromGroup', $unsubInfo) ? $unsubInfo['unsubscribeRemoveFromGroup'] : 0;
        $email = sanitize_text_field($_REQUEST['email']);
        $log->debug('listId: '.$listId.', '.'mailId: '.$mailId.', '.'salt: '.$salt.', '.'hashOk: '.$hashOk.', '.'nonceFromSession: '.$nonceFromSession.', '.'hash: '.$hash.', '.'email: '.$email);

        $hashOk = ($hashOk && $hashUtils->checkUnsubscribeKeyOfMail($mailId, $salt, $hash) && wp_verify_nonce($nonceFromSession, 'mst_frontend_unsub_confirm'));
        $log->debug('hashOk after all checks: '.($hashOk ? 'yes':'no'));
        $mList = $listUtils->getMailingList($listId);
        $isRecipient = $recips->isRecipient($listId, $email);
        $unsubscribeFailed = false;
        $unsubscribeCompletedOk = false;
        $doubleOptInConfirmSent = false;
        $notSubscribed = false;
        $message = null;

        if($hashOk){
            if($isRecipient){
                if($mList->unsubscribe_mode != MstConsts::UNSUBSCRIBE_MODE_DOUBLE_OPT_IN){
                    $log->debug('Double Opt-in unsubscribe mode not activated (frontend)');
                    list($success, $tmpUser) = $subscribeUtils->unsubscribeUser($email, $listId);
                    if($success){
                        $unsubscribeCompletedOk = true;
                        $message = __('Successfully unsubscribed','wp-mailster');
                        $subscribeUtils->sendWelcomeOrGoodbyeSubscriberMsg($tmpUser['name'], $email, $listId, MstConsts::SUB_TYPE_UNSUBSCRIBE);
                    }else{
                        $log->error('wpmst_double_opt_check - indeed2 - Not possible to unsubscribe email '.$email.' from list '.$listId.', tmpUser: '.print_r($tmpUser, true));
                        $unsubscribeFailed = true;
                        $message = __('Unsubscription failed','wp-mailster');
                    }

                    if($removeFromGroup && $tmpUser && $tmpUser['user_found']){
                        $log->debug('User is removed from group '.$removeFromGroup.' after unsubscribe');
                        MstFactory::getGroupModel(); // to have it included
                        MstFactory::getUserModel(); // to have it included
                        $User = new MailsterModelUser($tmpUser['user_id'], $tmpUser['is_core_user']);
                        $removeResult = $User->removeFromGroup($removeFromGroup);
                        $log->debug('User was removed from group '.$removeFromGroup.': '.($removeResult !== false ? 'OK' : 'FAILED'));
                    }else{
                        $log->debug('removeFromGroup not active: '.$removeFromGroup);
                    }

                }else{
                    $log->debug('Double Opt-in unsubscribe mode (frontend)');
                    $subscribeUtils->unsubscribeUserWithDoubleOptIn($email, $listId, $removeFromGroup);
                    $doubleOptInConfirmSent = true;
                    $message = __('An email was sent to you in order to confirm that you wanted to unsubscribe. Please follow the instructions in the email.','wp-mailster');
                }
            }else{
                $message = __('Email not subscribed','wp-mailster');
                $unsubscribeFailed = true;
            }
        }else{
            $message = __('Unsubscription failed','wp-mailster').' (Reason: invalid hash)';
            $unsubscribeFailed = true;
        }

        $query = array();
        if($unsubscribeCompletedOk){
            $query[] = 'success=1';
        }
        if($unsubscribeFailed){
            $query[] = 'success=0';
        }
        if($doubleOptInConfirmSent){
            $query[] = 'success=1';
            $query[] = 'dos=1';
        }
        $query[] = 'mes='.urlencode($message);

        $queryStr = implode('&', $query);

        $redirectLocation = plugins_url() . "/wp-mailster/view/subscription/unsubscribe2.php?" . $queryStr;
    }

    if($redirectLocation){
        $log->debug('redirect to '.$redirectLocation);
        if(wp_redirect($redirectLocation)){
            exit;
        }
    }
}

// [frontend endpoint] moderation decision
add_action( 'plugins_loaded', 'wpmst_moderation_decision', 1 );
function wpmst_moderation_decision() {
    if(isset($_GET['wpmst-moderate'])){  // only load stuff when needed
        $log = MstFactory::getLogger();
        $mailUtils = MstFactory::getMailUtils();
        $listUtils = MstFactory::getMailingListUtils();
        $hashUtils = MstFactory::getHashUtils();
        $moderationUtils = MstFactory::getModerationUtils();
    }else{
        return; // nothing relevant follows below
    }
    if( isset($_REQUEST['m']) ) {
        $mailId = intval($_REQUEST['m']);
    } else {
        $mailId = 0;
    }
    if( isset($_REQUEST['h']) ) {
        $hash = sanitize_text_field($_REQUEST['h']);
    } else {
        $hash = null;
    }
    if( isset($_REQUEST['s']) ) {
        $salt = intval($_REQUEST['s']);
    } else {
        $salt = null;
    }
    if($mailId <= 0 || is_null($hash) || is_null($salt)){
        $log->info('wpmst_moderation_decision Invalid parameter: mailId: '.$mailId.', hash: '.$hash.', salt: '.$salt);
        return; // nothing can be done
    }
    $mail = $mailUtils->getMail($mailId);

    $res = null;
    $doForward = false;
    $successful = false;
    $targetPage = 'approve'; // default

    if($mail){
        $doForward = true;
        $listId = $mail->list_id;
        $mList = $listUtils->getMailingList($listId);
        $saltedKeyHash = $hashUtils->getModerationKey($salt, $mail->hashkey);
        $hashOk = ($saltedKeyHash==$hash);
        if($hashOk){
            if(isset($_GET['wpmst-moderate']) && $_GET['wpmst-moderate'] == "approve"){
                $targetPage = 'approve';
                $log->debug('wpmst_moderation_decision - process approval for mail ID: '.$mailId);
                $res = $moderationUtils->approveMail($mailId, true, $mail, $mList);
                if($mList->mod_info_sender_approval && !$res->already_approved){
                    $moderationUtils->notifySenderOfApprovedMail($mailId, $mail);
                }
                if($res->success){
                    $log->debug('wpmst_moderation_decision Approval successful');
                    $successful = true;
                }else{
                    $log->warning('wpmst_moderation_decision Approving failed for mail ID '.$mailId);
                }
            }elseif(isset($_GET['wpmst-moderate']) && $_GET['wpmst-moderate'] == "reject"){
                $targetPage = 'reject';
                $log->debug('wpmst_moderation_decision - process rejection for mail ID: '.$mailId);
                $res = $moderationUtils->rejectMail($mailId, $mail);
                if($res->success){
                    $log->debug('wpmst_moderation_decision Rejection successful');
                    $successful = true;
                    if($mList->mod_info_sender_rejection && !$res->already_declined){
                        $moderationUtils->notifySenderOfRejectedMail($mailId, $mail);
                    }
                }else{
                    $log->warning('wpmst_moderation_decision Rejection failed for mail ID '.$mailId);
                }
            }
        }else{
            $log->warning('wpmst_moderation_decision Moderation failed, hash was not correct');
        }
    }else{
        $log->warning('wpmst_moderation_decision Moderation failed, mail ID not found');
    }

    if($doForward){
        $redirectLocation = plugins_url() . "/wp-mailster/view/moderation/".$targetPage.".php?success=" . $successful;
        $log->debug('wpmst_moderation_decision redirectLocation: '.$redirectLocation);

        if(wp_redirect($redirectLocation)){
            exit;
        }
    }
}

//ajax unsubscribe
add_action( 'wp_ajax_wpmst_unsubscribe', 'wpmst_unsubscribe_callback' );
add_action( 'wp_ajax_nopriv_wpmst_unsubscribe', 'wpmst_unsubscribe_callback' );
function wpmst_unsubscribe_callback() {
    include_once(plugin_dir_path( __FILE__ ) . "view/unsubscribe.php");
}
//ajax subscribe
add_action( 'wp_ajax_wpmst_subscribe', 'wpmst_subscribe_callback' );
add_action( 'wp_ajax_nopriv_wpmst_subscribe', 'wpmst_subscribe_callback' );
function wpmst_subscribe_callback() {
    include_once(plugin_dir_path( __FILE__ ) . "view/subscribe.php");
}