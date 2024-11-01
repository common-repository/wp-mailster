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
/**
 * Mailing List Model
 *
 */
class MailsterModelList extends MailsterModel
{
	var $_id = null;
	var $_data = null;

	function __construct($id = null){			
		parent::__construct((int)$id);
	}

	function _initData()
	{
		if (empty($this->_data))
		{
			$list = new stdClass();
			$list->id						= 0;
			$list->asset_id					= 0;
			$list->name						= null;
			$list->admin_mail				= null;
			$list->list_mail				= null;
			$list->subject_prefix			= null;
			$list->mail_in_user				= null;
			$list->mail_in_pw				= null;
			$list->server_inb_id			= 0;
			$list->server_out_id			= 0;
			/*$list->mail_in_port				= null;
			$list->mail_in_use_secure		= null;
			$list->mail_in_protocol			= null;
			$list->mail_in_params			= null;
			$list->mail_out_user			= null;
			$list->mail_out_pw				= null;
			$list->mail_out_host			= null;
			$list->mail_out_port			= null;
			$list->mail_out_use_secure		= null;*/
			$list->custom_header_plain		= MstConsts::TEXT_VARIABLES_NAME . ' <' . MstConsts::TEXT_VARIABLES_EMAIL . '> (' . MstConsts::TEXT_VARIABLES_DATE . '):';
			$list->custom_header_html		= MstConsts::TEXT_VARIABLES_NAME . ' <' . MstConsts::TEXT_VARIABLES_EMAIL . '> (' . MstConsts::TEXT_VARIABLES_DATE . '):';
			$list->custom_footer_plain		= null;
			$list->custom_footer_html		= null;
			$list->mail_format_conv			= null;
			$list->alibi_to_mail			= null;
							
			$list->published				= 1;
			$list->active					= 1;
			$list->use_cms_mailer   		= 1;
			$list->mail_in_use_sec_auth		= 0;
			$list->mail_out_use_sec_auth	= 0;
			$list->public_registration		= 1;
			$list->sending_public			= 1;
			$list->sending_recipients		= 0;
			$list->sending_admin			= 0;
			$list->sending_group			= 0;
			$list->sending_group_id			= 0;
            $list->mod_mode                 = 0;
            $list->mod_moderated_group      = 0;
            $list->mod_approve_recipients   = 0;
            $list->mod_approve_group        = 0;
            $list->mod_approve_group_id     = 0;
            $list->mod_info_sender_moderation   = 0;
            $list->mod_info_sender_approval     = 0;
            $list->mod_info_sender_rejection    = 0;
			$list->allow_subscribe			= 1;	
			$list->allow_unsubscribe		= 1;
			$list->reply_to_sender			= 0;	
			$list->copy_to_sender			= 1;			
			$list->disable_mail_footer		= 0;		
			$list->addressing_mode			= 1;
			$list->mail_from_mode			= 0;
			$list->name_from_mode			= 0;
			$list->archive_mode				= 0;
			$list->archive_retention        = 0;
			$list->archive2article			= 0;
			$list->archive2article_author	= 0;
			$list->archive2article_cat		= 0;
			$list->archive2article_state	= 1;
			$list->archive_offline			= 0;
			$list->bounce_mode				= 0;
			$list->bounce_mail				= null;
			$list->bcc_count				= 10;	
			$list->incl_orig_headers		= 0;
			$list->max_send_attempts		= 5;
			$list->filter_mails				= 0;
			$list->allow_bulk_precedence	= 0;
			$list->clean_up_subject			= 1;
			$list->mail_format_altbody		= 1;
			
			$list->lock_id					= 0;
			$list->is_locked				= 0;
			$list->last_lock				= null;
			$list->last_check				= null;
			$list->last_mail_retrieved		= null;
			$list->last_mail_sent			= null;

			$list->cstate					= 0;
			$list->mail_size_limit			= 0;
			$list->notify_not_fwd_sender	= 1;
			$list->save_send_reports		= 7;
			$list->subscribe_mode			= 1;
			$list->unsubscribe_mode			= 1;
			$list->welcome_msg				= 1;
			$list->welcome_msg_admin		= 0;
			$list->goodbye_msg				= 1;
			$list->goodbye_msg_admin		= 0;
			$list->allow_digests			= 0;
			$list->front_archive_access		= 0;
			
			$this->_data					= $list;
			return (boolean) $this->_data;
		}
		return true;
	}
	
	public function getTable($type = 'mailster_lists', $prefix = '', $config = array()){
		global $wpdb;
		$table_name = $wpdb->prefix . "mailster_lists";
		return $table_name;
	}

    public function saveData($options, $action = 'add'){
        if(!MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_D_FOOTER)){
            $options['disable_mail_footer'] = 0;
        }
        if(!MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_FILTER)){
            $options['filter_mails'] = 0;
        }
        if(!MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_MODERATE)){
            $options['mod_mode'] = 0;
        }
        if(!MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_DBL_OPT)){
            if($options['subscribe_mode'] == MstConsts::SUBSCRIBE_MODE_DOUBLE_OPT_IN){
                $options['subscribe_mode'] = MstConsts::SUBSCRIBE_MODE_DEFAULT;
            }
            if($options['unsubscribe_mode'] == MstConsts::UNSUBSCRIBE_MODE_DOUBLE_OPT_IN){
                $options['unsubscribe_mode'] = MstConsts::UNSUBSCRIBE_MODE_DEFAULT;
            }
        }
        if(!MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_DIGEST)){
            $options['allow_digests'] = 0;
        }
        if(!MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_D_ARCHIVE)){
            $options['archive_mode'] = MstConsts::ARCHIVE_MODE_ALL;
        }
        if(!MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_EARCHIVE)){
            if(isset($options['archive2article'])){
                $options['archive2article'] = 0;
            }
            if(isset($options['archive_offline'])){
                $options['archive_offline'] = 0;
            }
        }

        return parent::saveData($options, $action);
    }
	
	public function saveServer($serverOptions, $serverId, $isOutServer = true) {
		global $wpdb;
		$action = "add";
		$Server = new MailsterModelServer();
		if ( ! $serverId ) { //not known, check if already exists				
			if ( $Server->checkIfExists( $serverOptions[ 'server_host' ] ) ) {
				$action = "edit";
			}
		}
		$result = $Server->saveData($serverOptions, $action);
		if ( $result ) { 
			$serverValues = $Server->getFormData();

			//save the appropriate value depending on whether this is about the in or the out server
			$column = 'server_inb_id';
			if($isOutServer) {
				$column = 'server_out_id';
			}
			$result = $wpdb->update( 
				$this->getTable(), 
				array( 
					$column => $serverValues->id
				), 
				array( 'ID' => $this->_id ), 
				array( '%d' ), 
				array( '%d' ) 
			);
		}
		return $result;
	}

	public function getServerLists($server_id) {
		global $wpdb;
		$result = $wpdb->get_results( 
			"SELECT id, name "
			. " FROM " . $this->getTable()
			. " WHERE server_inb_id = " . $server_id 
			. " OR server_out_id = " . $server_id
			);
		return $result;
	}	


	public function addUser($user) {
		global $wpdb;
		//check if user is already a member
		$core = "false";
		if($user->isCoreUser()) {
			$core = "true";
		}
		$res = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'mailster_list_members WHERE user_id = ' . $user->getId() . ' AND list_id = ' . $this->_id . ' AND is_core_user = ' . $core);
		if(!$res) {
			$columns = $wpdb->insert(
				$wpdb->prefix . 'mailster_list_members',
				array(
					'list_id' => $this->_id,
					'user_id' => $user->getId(),
					'is_core_user' => $user->isCoreUser()
				),
				array(
					'%d',
					'%d',
					'%d'
				)
			);
			if ($columns) {
				return $wpdb->insert_id;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	public function addUserById($user_id, $iscore) {
		global $wpdb;
		$columns = $wpdb->insert( 
			$wpdb->prefix.'mailster_list_members', 
			array( 
				'list_id' => $this->_id, 
				'user_id' => $user_id,
				'is_core_user' => $iscore
			), 
			array( 
				'%d', 
				'%d', 
				'%d' 
			) 
		);
		if ( $columns ) {
			return true;
		} else {
			return false;
		}
	}

	public function addGroupById($group_id) {
		global $wpdb;
		$columns = $wpdb->insert( 
			$wpdb->prefix.'mailster_list_groups', 
			array( 
				'list_id' => $this->_id, 
				'group_id' => $group_id
			), 
			array( 
				'%d', 
				'%d' 
			) 
		);
		if ( $columns ) {
			return true;
		} else {
			return false;
		}
	}

	public function removeUser( $user ) {
		global $wpdb;
		return $wpdb->delete( 
			$wpdb->prefix.'mailster_list_members',
			array( 
				'list_id' => $this->_id, 
				'user_id' => $user->getId(),
				'is_core_user' => $user->isCoreUser()
			),
			array( 
				'%d', 
				'%d', 
				'%d' 
			)
		);
	}

    public function removeUserById( $user_id, $is_core_user ) {
        global $wpdb;
        return $wpdb->delete(
            $wpdb->prefix.'mailster_list_members',
            array(
                'list_id' => $this->_id,
                'user_id' => $user_id,
                'is_core_user' => $is_core_user
            ),
            array(
                '%d',
                '%d',
                '%d'
            )
        );
    }

	public function emtpyUsers() {
		global $wpdb;
		return $wpdb->delete( 
			$wpdb->prefix.'mailster_list_members',
			array( 
				'list_id' => $this->_id
			),
			array( 
				'%d' 
			)
		);
	}

	public function emtpyGroups() {
		global $wpdb;
		return $wpdb->delete( 
			$wpdb->prefix.'mailster_list_groups',
			array( 
				'list_id' => $this->_id
			),
			array( 
				'%d' 
			)
		);
	}

	public function getAllListMembers() {
		global $wpdb;
		$query = "SELECT * FROM " . $wpdb->prefix . "mailster_list_members WHERE list_id=" . $this->_id;
		return $wpdb->get_results($query, OBJECT);
	}

	public function getAllListGroups() {
		global $wpdb;
		$query = "SELECT * FROM " . $wpdb->prefix . "mailster_list_groups WHERE list_id=" . $this->_id;
		return $wpdb->get_results($query);
	}

	function getData($id=null, $forceReload=false)
	{
		if(!is_null($id)){
			$this->setId($id);
		}
		if ($this->_loadData($forceReload))
		{

		}
		else  $this->_initData();

		return $this->_data;
	}
}