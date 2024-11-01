<?php
	/**
	 * @copyright (C) 2016 - 2024 Holger Brandt IT Solutions
	 * @license GNU/GPL, see license.txt
	 * WP Mailster is free software; you can redistribute it and/or
	 * modify it under the terms of the GNU General Public License 2
	 * as published by the Free Software Foundation.
	 * 
	 * WP Mailster is distributed in the hope that it will be useful,
	 * but WITHOUT ANY WARRANTY; without even the implied warranty of
	 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	 * GNU General Public License for more details.
	 * 
	 * You should have received a copy of the GNU General Public License
	 * along with WP Mailster; if not, write to the Free Software
	 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA
	 * or see http://www.gnu.org/licenses/.
	 */

if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
	die('These are not the droids you are looking for.');
}

class MstMailingList
{
    public $id;
    public $asset_id;
    public $name;
    public $admin_mail;
    public $list_mail;
    public $subject_prefix;
    public $mail_in_user;
    public $mail_in_pw;
    public $mail_in_host;
    public $mail_in_port;
    public $mail_in_use_secure;
    public $mail_in_protocol;
    public $mail_in_params;
    public $mail_out_user;
    public $mail_out_pw;
    public $mail_out_host;
    public $mail_out_port;
    public $mail_out_use_secure;
    public $server_inb_id;
    public $server_out_id;
    public $custom_header_plain;
    public $custom_header_html;
    public $custom_footer_plain;
    public $custom_footer_html;
    public $mail_format_conv;
    public $alibi_to_mail;

    public $published;
    public $active;
    public $use_cms_mailer;
    public $mail_in_use_sec_auth;
    public $mail_out_use_sec_auth;
    public $public_registration;
    public $sending_public;
    public $sending_recipients;
    public $sending_admin;
    public $sending_group;
    public $sending_group_id;
    public $mod_mode;
    public $mod_moderated_group;
    public $mod_approve_recipients;
    public $mod_approve_group;
    public $mod_approve_group_id;
    public $mod_info_sender_moderation;
    public $mod_info_sender_approval;
    public $mod_info_sender_rejection;
    public $allow_subscribe;
    public $allow_unsubscribe;
    public $reply_to_sender;
    public $copy_to_sender;
    public $disable_mail_footer;
    public $addressing_mode;
    public $mail_from_mode;
    public $name_from_mode;
    public $archive_mode;
    public $archive_retention;
    public $archive2article;
    public $archive2article_author;
    public $archive2article_cat;
    public $archive2article_state;
    public $archive_offline;
    public $bounce_mode;
    public $bounce_mail;
    public $bcc_count;
    public $incl_orig_headers;
    public $max_send_attempts;
    public $filter_mails;
    public $allow_bulk_precedence;
    public $clean_up_subject;
    public $mail_format_altbody;

    public $lock_id;
    public $is_locked;
    public $last_lock;
    public $last_check;
    public $last_mail_retrieved;
    public $last_mail_sent;

    public $cstate;
    public $mail_size_limit;
    public $notify_not_fwd_sender;
    public $save_send_reports;
    public $subscribe_mode;
    public $unsubscribe_mode;
    public $welcome_msg;
    public $welcome_msg_admin;
    public $goodbye_msg;
    public $goodbye_msg_admin;
    public $allow_digests;
    public $front_archive_access;

    public function __construct(){

    }

	function MstMailingList(){
        self::__construct();
	}
	
	static public function getInstance($tblObj){
		$mstList =  new MstMailingList();
		if(is_object($tblObj)){
			foreach ($tblObj as $varName => $val) {
	            $mstList->$varName = $val;
	        }
		}else{
			return null;
		}
        return $mstList;
	}
	
	
}
