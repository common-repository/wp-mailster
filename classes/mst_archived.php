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
    die( 'These are not the droids you are looking for.' );
}

require_once(__DIR__ . '/mst_wp_list_table.php');

class Mst_archived extends Mst_WP_List_Table {

	/** Class constructor */
	public function __construct() {
		add_action('load-edit.php',         array(&$this, 'process_bulk_action'));
		parent::__construct( array(
			'singular' => __( 'Archived Email', 'wp-mailster' ), //singular name of the servered records
			'plural'   => __( 'Archived Emails', 'wp-mailster' ), //plural name of the servered records
			'ajax'     => true //does this table support ajax?
        ) );

	}
	
	/**
	 * Retrieve archived emails data from the database
	 *
	 * @param int $per_page
	 * @param int $page_number
	 * @param string $state
	 *
	 * @return mixed
	 */
	public static function mst_get_archived( $per_page = 20, $page_number = 1) {
		global $wpdb;
        $sql = self::getMailArchiveQuery();

		if ( ! empty( $_REQUEST['orderby'] ) ) {
			$sql .= ' ORDER BY ' . sanitize_sql_orderby($_REQUEST['orderby']);
			$sql .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( sanitize_text_field($_REQUEST['order']) ) : ' ASC';
		}else{
            $sql .= ' ORDER BY receive_timestamp DESC';
            $_REQUEST['orderby'] = 'receive_timestamp';
            $_REQUEST['order'] = 'DESC';
        }

		$sql .= " LIMIT $per_page";
		$sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;


		$result = $wpdb->get_results( $sql, 'ARRAY_A' );

		return $result;
	}

    /**
     * Get all mails, take into account search variable
     * @return string
     */
    protected static function getMailArchiveQuery(){
        global $wpdb;

        $where = array();
        $where[] = 'l.id = l.id'; // need to add at least one always-true-conditions to be sure nothing breaks if non of the following goe into where()

        if( isset($_REQUEST['selectedlid']) ) {
            $where[] = "l.id = ". intval($_REQUEST['selectedlid']);
        }

        $state = 'processed';
        if( isset( $_REQUEST['state'] )) {
            $state = sanitize_text_field( $_REQUEST['state'] );
        }

        if( $state == "bounced" ) {
            $where[] = 'm.bounced_mail = \'1\'';
        }elseif( $state == "blocked" ){
            $where[] = 'm.blocked_mail = \'1\'';
        }elseif( $state == "mod_in_moderation" ){
            $where[] = 'm.moderation_status = \''.MstConsts::MAIL_FLAG_MODERATED_IN_MODERATION.'\'';
        }elseif( $state == "mod_rejected" ){
            $where[] = 'm.moderation_status = \''.MstConsts::MAIL_FLAG_MODERATED_REJECTED.'\'';
        }elseif( $state == "processed" ){
            $where[] = 'm.bounced_mail = \'0\'';
            $where[] = 'm.blocked_mail = \'0\'';
            $where[] = 'm.moderation_status >= \'0\'';
        }

        if ( ! empty( $_REQUEST['s'] ) ) {
            $searchTerm = sanitize_text_field( $_REQUEST['s'] );
            $where[] = '(from_name LIKE \'%'.$searchTerm.'%\''
                        . ' OR from_email LIKE \'%'.$searchTerm.'%\''
                        . ' OR subject LIKE \'%'.$searchTerm.'%\''
                        . ' OR body LIKE \'%'.$searchTerm.'%\''
                        . ' OR html LIKE \'%'.$searchTerm.'%\')';
        }

        $whereStr = ' WHERE '.implode(' AND ', $where);

        $sql= 'SELECT m.*, l.name'
            . ' FROM ' . $wpdb->prefix . 'mailster_mails AS m'
            . ' LEFT JOIN ' . $wpdb->prefix . 'mailster_lists AS l'
            . ' ON (m.list_id = l.id)'
            . $whereStr
        ;

        return $sql;
    }

	/**
	 * Delete a server record.
	 *
	 * @param int $id server ID
	 */
	public static function mst_delete_archived( $id ) {
		global $wpdb;
        $log = MstFactory::getLogger();
        $log->debug('mst_delete_archived, id: '.print_r($id, true));
		$wpdb->delete(
			"{$wpdb->prefix}mailster_mails",
            array( 'id' => $id ),
            array( '%d' )
		);
	}
	
	/**
	 * Returns the count of records in the database.
	 *
	 * @return null|string
	 */
	public static function record_count() {
		global $wpdb;
        $sql = self::getMailArchiveQuery();
        $sql = "SELECT COUNT(*) FROM (".$sql.") MT";
		return $wpdb->get_var( $sql );
	}

	/** Text displayed when no servers data is available */
	public function no_items() {
		_e( 'No Archived emails.', 'wp-mailster' );
	}

	/**
	 * Render a column when no column specific method exist.
	 *
	 * @param array $item
	 * @param string $column_name
	 *
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {
        return esc_html($item[ $column_name ]);
	}

	/**
	 * Render the bulk edit checkbox
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="bulk-action[]" value="%s" />', $item['id']
		);
	}

	/**
	 * Method for subject column
	 *
	 * @param array $item an array of DB data
	 *
	 * @return string
	 */
	function column_subject( $item ) {

		$delete_nonce = wp_create_nonce( 'mst_delete_archived' );
		$edit_nonce = wp_create_nonce( 'mst_resend_archived' );
        $mod_approve_nonce = wp_create_nonce( 'mst_moderate_approve_archived' );
        $mod_reject_nonce = wp_create_nonce( 'mst_moderate_reject_archived' );

        $subject = $item["subject"];
        $subject = (is_null($subject) || strlen($subject) == 0) ? '<em>'.__( '(No Subject)', 'wp-mailster' ).'</em>' : $subject;

		$actions = array();
		if($item["no_content"] == 0){
            $title = (
                sprintf(
                    '<a href="?page=mst_archived&subpage=details&sid=%s&_wpnonce=%s">',
                    absint( $item['id'] ),
                    $edit_nonce
                )
                .'<strong>' . esc_html($subject) . '</strong></a>'
            );
            $actions['resend'] = sprintf(
                    '<a href="?page=mst_archived&subpage=resend&eid=%s&_wpnonce=%s">%s</a>',
                    absint( $item['id'] ),
                    $edit_nonce,
                    __("Resend", 'wp-mailster')
                );
        }else{
            $title = '<strong title="'.__('Email content not archived', 'wp-mailster').'">' . esc_html($subject) . '</strong>'; // no link for no email content
        }

		$actions['delete'] = sprintf(
				'<a href="?page=mst_archived&action=delete&sid=%s&_wpnonce=%s">%s</a>',
				absint( $item['id'] ),
				$delete_nonce,
				__("Delete", 'wp-mailster')
        );

        if($item["moderation_status"] == MstConsts::MAIL_FLAG_MODERATED_IN_MODERATION){
            $actions['moderate_approve'] = sprintf(
                '<a href="?page=mst_archived&action=moderate_approve&sid=%s&_wpnonce=%s">%s</a>',
                absint( $item['id'] ),
                $mod_approve_nonce,
                __("Approve", 'wp-mailster')
            );
            $actions['moderate_reject'] = sprintf(
                '<a href="?page=mst_archived&action=moderate_reject&sid=%s&_wpnonce=%s">%s</a>',
                absint( $item['id'] ),
                $mod_reject_nonce,
                __("Reject", 'wp-mailster')
            );
        }elseif($item["moderation_status"] == MstConsts::MAIL_FLAG_MODERATED_REJECTED){
            $actions['moderate_approve'] = sprintf(
                '<a href="?page=mst_archived&action=moderate_approve&sid=%s&_wpnonce=%s">%s</a>',
                absint( $item['id'] ),
                $mod_approve_nonce,
                __("Approve", 'wp-mailster')
            );
        }
		return $title . $this->row_actions( $actions );
	}

    /**
     * Method for fwd_errors column
     *
     * @param array $item an array of DB data
     *
     * @return string
     */
    function column_list_id( $item ) {
        return $item['name'];
    }

	/**
	 * Method for fwd_errors column
	 *
	 * @param array $item an array of DB data
	 *
	 * @return string
	 */
	function column_fwd_errors( $item ) {
		if($item['fwd_errors'] > 1 ) {
			return '<span class="dashicons dashicons-no" title="'.__('Send errors occurred', 'wp-mailster').'"></span>';
		} else {
			return '';
		}
	}

	/**
	 * Method for fwd_completed column
	 *
	 * @param array $item an array of DB data
	 *
	 * @return string
	 */
	function column_fwd_completed( $item ) {
		if($item['fwd_completed'] == 1 ) {
			return '<span class="dashicons dashicons-yes" title="'.__('Sending completed', 'wp-mailster').'"></span>';
		} else {
			return '';
		}
	}

	function column_receive_timestamp($item){
	    /** MstDateUtils $dateUtils */
	    $dateUtils = MstFactory::getDateUtils();
        return $dateUtils->formatDateAsConfigured($item['receive_timestamp']);
    }

    function column_fwd_completed_timestamp($item){
        /** MstDateUtils $dateUtils */
        $dateUtils = MstFactory::getDateUtils();
        if(is_null($item['fwd_completed_timestamp'])){
            return '';
        }
        return $dateUtils->formatDateAsConfigured($item['fwd_completed_timestamp']);
    }

    function column_has_attachments($item){
        $attachImg = '&nbsp;';
        if(intval($item['has_attachments']) > 0){
            $mstImgPath =  plugins_url( '/asset/images/', dirname(__FILE__) );
            $attachImg = '<span class="mailster_has_attach" title="' .__( 'Has attachments', 'wp-mailster' ) .'">📎</span>';
        }
        return $attachImg;
    }

	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	function get_columns() {
		$columns = array(
			'cb'      => '<input type="checkbox" />',
            'subject'    => __( 'Subject', 'wp-mailster' ),
            'has_attachments' => '📎',
			'from_name'    => __( 'From Name', 'wp-mailster' ),
			'from_email'    => __( 'From Email', 'wp-mailster' ),
			'receive_timestamp'    => __( 'Date', 'wp-mailster' ),
			'fwd_completed_timestamp'    => __( 'Sent date', 'wp-mailster' ),
			'fwd_errors' => __('Errors', 'wp-mailster'),
			'fwd_completed' => __('Sent', 'wp-mailster'),
			'list_id' => __('In Mailing List', 'wp-mailster'),
			'id' => __('ID', 'wp-mailster'),
        );

		return $columns;
	}

	/**
	 * Columns to make sortable.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'subject' => array( 'subject', true ),
			'receive_timestamp' => array( 'receive_timestamp', true ),
            'id' => array( 'id', true )
		);

		return $sortable_columns;
	}

	/**
	 * Returns an associative array containing the bulk action
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
        $actions = array(
            'bulk-delete' => __('Delete', 'wp-mailster'),
            'bulk-resend' => __('Resend', 'wp-mailster')
        );
        if(array_key_exists('state', $_GET) && $_GET['state'] === 'mod_in_moderation'){
            $actions['bulk-moderate_approve'] = __('Moderation: Approve', 'wp-mailster');
            $actions['bulk-moderate_reject'] = __('Moderation: Reject', 'wp-mailster');
        }
		return $actions;
	}

	/**
	 * Handles data query and filter, sorting, and pagination.
	 */
	public function prepare_items() {

		$this->_column_headers = $this->get_column_info();

		/** Process bulk action */
        $this->process_bulk_action();

		$per_page     = $this->get_items_per_page( 'edit_post_per_page', 20 );
		$current_page = $this->get_pagenum();
		$total_items  = self::record_count();

		$this->set_pagination_args( array(
			'total_items' => $total_items, //WE have to calculate the total number of items
			'per_page'    => $per_page //WE have to determine how many items to show on a page
        ) );

		$this->items = self::mst_get_archived( $per_page, $current_page );
	}


	public function process_bulk_action() {
        $log = MstFactory::getLogger();
		//Detect when a bulk action is being triggered...
        $action = $this->current_action();
        $log->debug('mst_archived -> process_bulk_action: '.$action);
		switch ($action) {
			case 'delete':
				// In our file that handles the request, verify the nonce.
				$nonce = sanitize_text_field( $_REQUEST['_wpnonce'] );
				if ( ! wp_verify_nonce( $nonce, 'mst_delete_archived' ) ) {
					die( 'Stop CSRF!' );
				} else {
                    $log->debug('delete email '.absint( $_GET['sid'] ));
					self::mst_delete_mail( absint( $_GET['sid'] ) );
				}
				break;

			case 'resend':
				// In our file that handles the request, verify the nonce.
				$nonce = esc_attr( sanitize_text_field($_REQUEST['_wpnonce']) );
				if ( ! wp_verify_nonce( $nonce, 'mst_resend_archived' ) ) {
					die( 'Stop CSRF!' );
				} else {
					$ids = wp_parse_id_list($_GET['id']);
					$edit_nonce = wp_create_nonce( 'mst_resend_archived' );
					$url = "?page=mst_resend&_wpnonce=". $edit_nonce;
					foreach($ids as $id) {
						$url .= "&eid[]=" . intval($id);
					}
				}
				break;

            case 'moderate_approve':
                // In our file that handles the request, verify the nonce.
                $nonce = sanitize_text_field( $_REQUEST['_wpnonce'] );
                if ( ! wp_verify_nonce( $nonce, 'mst_moderate_approve_archived' ) ) {
                    die( 'Stop CSRF!' );
                } else {
                    $log->debug('moderate_approve email '.absint( $_GET['sid'] ));
                    self::mst_moderate_approve_message( absint( $_GET['sid'] ) );
                }
                break;

            case 'moderate_reject':
                // In our file that handles the request, verify the nonce.
                $nonce = sanitize_text_field( $_REQUEST['_wpnonce'] );
                if ( ! wp_verify_nonce( $nonce, 'mst_moderate_reject_archived' ) ) {
                    die( 'Stop CSRF!' );
                } else {
                    $log->debug('moderate_reject email '.absint( $_GET['sid'] ));
                    self::mst_moderate_reject_message( absint( $_GET['sid'] ) );
                }
                break;

			case 'bulk-resend':
			    // not needed, requests will be catched in wp-mailster.php
				break;

			case 'bulk-delete':
				$selected_ids = esc_sql( $_REQUEST['bulk-action'] );
				if($selected_ids) {
                    $log->debug('bulk-delete emails: '.print_r($selected_ids, true));
					foreach ( $selected_ids as $id ) {
						self::mst_delete_mail( intval($id) );
					}
				}
				break;

            case 'bulk-moderate_approve':
                $selected_ids = esc_sql( $_REQUEST['bulk-action'] );
                if($selected_ids) {
                    $log->debug('bulk-moderate_approve emails: '.print_r($selected_ids, true));
                    foreach ( $selected_ids as $id ) {
                        self::mst_moderate_approve_message( intval($id) );
                    }
                }
                break;

            case 'bulk-moderate_reject':
                $selected_ids = esc_sql( $_REQUEST['bulk-action'] );
                if($selected_ids) {
                    $log->debug('bulk-moderate_reject emails: '.print_r($selected_ids, true));
                    foreach ( $selected_ids as $id ) {
                        self::mst_moderate_reject_message( intval($id) );
                    }
                }
                break;

			default:
				# code...
				break;
		}
	}

	/**
	 * Delete a mail record.
	 *
	 * @param int $id mail ID
	 */
	public static function mst_delete_mail($id) {
	    if($id > 0) {
            $cid = array();
            $cid[] = $id;
            /** @var MailsterModelMails $mailModel */
            $mailModel = MstFactory::getMailsModel();
            return $mailModel->delete($cid);
        }
	    return false;
	}

    public static function mst_moderate_approve_message($id){
        $log = MstFactory::getLogger();
        $mailUtils = MstFactory::getMailUtils();
        $mListUtils = MstFactory::getMailingListUtils();
        $moderationUtils = MstFactory::getModerationUtils();
        $log->debug('mst_moderate_approve_message for mail ID '.$id);
        $mail = $mailUtils->getMail($id);
        if($mail){
            $mList = $mListUtils->getMailingList($mail->list_id);
            $res = $moderationUtils->approveMail($id, true, $mail, $mList);
            if($res->success){
                $log->debug('mst_moderate_approve_message Approval of mail '.$id.' (through backend) successful');
                if($mList->mod_info_sender_approval){
                    $moderationUtils->notifySenderOfApprovedMail($id, $mail);
                }
                $message = sprintf(__('Approved mail "%s"', 'wp-mailster'), $mail->subject);
                return true;
            }else{
                $log->error('mst_moderate_approve_message Approval of mail '.$id.' (through backend) failed');
                $message = sprintf(__('Error while approving mail "%s"', 'wp-mailster'), $mail->subject);
            }
        }else{
            $log->error('mst_moderate_approve_message Did not find email '.$id);
        }
        return false;
    }

    public static function mst_moderate_reject_message($id){
        $log = MstFactory::getLogger();
        $mailUtils = MstFactory::getMailUtils();
        $mListUtils = MstFactory::getMailingListUtils();
        $moderationUtils = MstFactory::getModerationUtils();
        $log->debug('declineModeratedMessage() for mail ID '.$id);
        $mail = $mailUtils->getMail($id);
        if($mail){
            $mList = $mListUtils->getMailingList($mail->list_id);
            $res = $moderationUtils->rejectMail($id, $mail);
            if($res->success){
                $log->debug('declineModeratedMessage Rejection of mail '.$id.' (through backend) successful');
                if($mList->mod_info_sender_rejection){
                    $moderationUtils->notifySenderOfRejectedMail($id, $mail);
                }
                $message = sprintf(__('Rejected mail "%s"', 'wp-mailster'), $mail->subject);
                return true;
            }else{
                $log->error('declineModeratedMessage Rejection of mail '.$id.' (through backend) failed');
                $message = sprintf(__('Error while rejecting mail "%s"', 'wp-mailster'), $mail->subject);
            }
        }else{
            $log->error('mst_moderate_reject_message Did not find email '.$id);
        }
        return false;
    }

	/**
	 * Add the dropdown menu for "state"
	 */
	function extra_tablenav( $which ) {
		$move_on_url = '&state=';
		if ( $which == "top" ){
			?>
			<div class="alignleft actions bulkactions">
				<?php
				$states = array(
					"processed" => __("Processed Emails", 'wp-mailster'),
					"blocked" => __("Blocked/Filtered Emails", 'wp-mailster'),
					"bounced" => __("Bounced Emails", 'wp-mailster'),
                    "mod_in_moderation" => __("Moderation: Emails in moderation", 'wp-mailster'),
                    "mod_rejected" => __("Moderation: Rejected emails", 'wp-mailster')
				);
				?>
				<select name="state" class="ewc-filter-cat">
					<?php
					foreach( $states as $stateid => $state ){
						$selected = '';
						if(isset($_GET['state'])) {
							if ( $_GET['state'] == $stateid ) {
								$selected = ' selected = "selected"';
							}
						}
						?>
						<option value="<?php echo $move_on_url . $stateid; ?>" <?php echo $selected; ?>><?php echo $state; ?></option>
						<?php
					}
					?>
				</select>
			</div>

			<?php
		}
		if ( $which == "bottom" ){
			//The code that goes after the table is there
		}
	}

}