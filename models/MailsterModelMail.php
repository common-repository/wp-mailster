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

require_once plugin_dir_path( __FILE__ )."../models/MailsterModel.php";
/**
 * Mail Model
 *
 */
class MailsterModelMail extends MailsterModel
{
	var $_id = null;
	var $_data = null;
	var $_filterByFrontendMailArchiveACL = false;

	function __construct(){
		parent::__construct();
		$array = null;
		if(isset($_REQUEST['cid'])) {
			$array = wp_parse_id_list($_REQUEST['cid']);
		}
		if(!$array) {
			$array[0] = 0;
		}
		$this->setId(intval($array[0]));
	}

	function setId($id, $forceReload=false)
	{
		$this->_id	    = $id;
		$this->_data	= null;
	}

	function &getData($id=false, $filterByFrontendMailArchiveACL=false)
	{
		if($id){
			$this->setId($id);
			$this->_filterByFrontendMailArchiveACL = $filterByFrontendMailArchiveACL;
		}
		if (!$this->_loadData()){
			$this->_initData();
		}
		return $this->_data;
	}

	function _loadData($forceReload = false)
	{
		if ( empty ( $this->_data ) || $forceReload) {
			$frontendACLFilterQueryPart = '';
			global $wpdb;
			$query = 'SELECT *'
				. ' FROM ' . $wpdb->prefix . 'mailster_mails'
				. ' WHERE id = '.$this->_id
				. $frontendACLFilterQueryPart
			;
			$this->_data = $wpdb->get_row( $query );

			return (boolean) $this->_data;
		}
		return true;
	}

	function _initData()
	{
		if (empty($this->_data))
		{
			$mail = new stdClass();
			$mail->id						= 0;
			$mail->list_id					= null;
			$mail->thread_id				= null;
			$mail->hashkey					= null;
			$mail->message_id				= null;
			$mail->in_reply_to				= null;
			$mail->references_to			= null;
			$mail->receive_timestamp		= null;
			$mail->from_name				= null;
			$mail->from_email				= null;
			$mail->sender_user_id			= null;
			$mail->sender_is_core_user		= null;
			$mail->sender_dmarc_relevant	= 0;
			$mail->orig_to_recips			= null;
			$mail->orig_cc_recips			= null;
			$mail->subject					= null;
			$mail->body						= null;
			$mail->html						= null;
			$mail->has_attachments			= 0;
			$mail->attachments				= null;
            $mail->content_type             = null;
			$mail->fwd_errors				= 0;
			$mail->fwd_completed			= 0;
			$mail->fwd_completed_timestamp	= 0;
			$mail->blocked_mail				= 0;
			$mail->bounced_mail				= 0;
            $mail->moderation_status        = 0;
			$mail->no_content				= 0;
			$mail->has_send_report			= 0;
			$mail->size_in_bytes			= -1;

			$this->_data		= $mail;
			return (boolean) $this->_data;
		}
		return true;
	}

	public function getTable($type = 'mailster_mails', $prefix = '', $config = array()){
		global $wpdb;
		$table_name = $wpdb->prefix . $type;
		return $table_name;
	}

	function store($data)
	{
		global $wpdb;
		$columns = array();
		$values = array();
		foreach($data as $key => $value) {
			$columns[] = $key;
			$values[] = $value;
		}

		$wpdb->insert(self::getTable(), $columns, $values);
		return $wpdb->insert_id;
	}
}//Class end

