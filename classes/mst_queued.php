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

class Mst_queued extends Mst_WP_List_Table {

	/** Class constructor */
	public function __construct() {

        add_action( 'admin_enqueue_scripts', array( &$this, 'admin_header' ));
		parent::__construct( array(
			'singular' => __( 'Queued Email', 'wp-mailster' ), //singular name of the servered records
			'plural'   => __( 'Queued Emails', 'wp-mailster' ), //plural name of the servered records
			'ajax'     => true //does this table support ajax?
        ) );

	}

	/**
	 * Retrieve queued data from the database
	 *
	 * @param int $per_page
	 * @param int $page_number
	 *
	 * @return mixed
	 */
	public static function mst_get_queued( $per_page = 20, $page_number = 1) {
		global $wpdb;
        $sql = self::getQueuedMailsQuery();

		if ( ! empty( $_REQUEST['orderby'] ) ) {
			$sql .= ' ORDER BY ' . sanitize_sql_orderby( $_REQUEST['orderby'] );
			$sql .= ! empty( $_REQUEST['order'] ) ? ' ' . sanitize_text_field( $_REQUEST['order'] ) : ' ASC';
		} else {
            $sql .= ' ORDER BY mail_id ASC' ;
            $_REQUEST['orderby'] = 'mail_id';
            $_REQUEST['order'] = 'ASC';
        }

		$sql .= " LIMIT $per_page";
		$sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;

		$result = $wpdb->get_results( $sql, 'ARRAY_A' );

		return $result;
	}

    /**
     * Get all queued emails, take into account search variable
     * @return string
     */
    protected static function getQueuedMailsQuery(){
        global $wpdb;
        $sql = 'SELECT m.mail_id, m.name, m.email, m.error_count, m.lock_id, m.is_locked, m.last_lock, ma.id, ma.subject'
            . ' FROM ' . $wpdb->prefix . 'mailster_queued_mails m'
            . ' LEFT JOIN ' . $wpdb->prefix . 'mailster_mails ma ON (m.mail_id = ma.id)';

        $where = array();
        $where[] = 'm.mail_id = m.mail_id'; // need to add at least one always-true-conditions to be sure nothing breaks if non of the following goe into where()
        if( isset($_GET['mailId'])) {
            $where[] = " m.mail_id=" . intval($_GET['mailId']);
        }

        if ( ! empty( $_REQUEST['s'] ) ) {
            $searchTerm = sanitize_text_field( $_REQUEST['s'] );
            $where[] = '(m.name LIKE \'%'.$searchTerm.'%\' OR m.email LIKE  \'%'.$searchTerm.'%\' OR ma.subject LIKE  \'%'.$searchTerm.'%\')';
        }

        if(count($where)){
            $sql .= ' WHERE '.implode(' AND ', $where);
        }

        return $sql;
    }

	/**
	 * Delete a queued record.
	 */
	public static function mst_delete_queued($mailId, $email) {
		$mailQueue = MstFactory::getMailQueue();
        return $mailQueue->removeMailFromQueue($mailId, $email);
	}

    /**
     * Delete all entries in the queue
     */
    public static function mst_clear_queue() {
        $mailQueue = MstFactory::getMailQueue();
        return $mailQueue->clearQueue();
    }
	
	/**
	 * Returns the count of records in the database.
	 *
	 * @return null|string
	 */
	public static function record_count() {
		global $wpdb;
        $sql = self::getQueuedMailsQuery();
		$sql = "SELECT COUNT(*) FROM (".$sql.") UQM";
		return $wpdb->get_var( $sql );
	}

	/** Text displayed when no servers data is available */
	public function no_items() {
		_e( 'No Queued emails.', 'wp-mailster' );
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
			'<input type="checkbox" name="bulk-action[]" value="%s" />', $item['mail_id'].':'.$item['email']
		);
	}

	/**
	 * Method for name column
	 *
	 * @param array $item an array of DB data
	 *
	 * @return string
	 */
	function column_name( $item ) {
		return esc_html($item["name"]);
	}

	function column_subject( $item ) {
		global $wpdb;
		$query = "SELECT subject FROM " . $wpdb->prefix . "mailster_mails WHERE id = " . $item['mail_id'];
		$subject = $wpdb->get_var( $query );
        $subject = (is_null($subject) || strlen($subject) == 0) ? '<em>'.__( '(No Subject)', 'wp-mailster' ).'</em>' : esc_html($subject);
        $edit_nonce = wp_create_nonce( 'mst_resend_archived' );
        return (sprintf( '<a href="?page=mst_queued&subpage=details&sid=%s&_wpnonce=%s">', absint( $item['mail_id'] ), $edit_nonce ) . '<strong>' . $subject . '</strong></a>');
	}
	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	function get_columns() {
		$columns = array(
			'cb'      => '<input type="checkbox" />',
            'subject'   => __( 'Subject', 'wp-mailster'),
			'name'      => __( 'Name', 'wp-mailster' ),
			'email'    => __( 'Email', 'wp-mailster' ),
			'mail_id'    => __( 'Mail ID', 'wp-mailster' )
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
			'name' => array( 'name', true ),
			'email' => array( 'email', true ),
            'mail_id' => array( 'mail_id', true )
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
            'bulk-delete' => __('Delete Selected', 'wp-mailster'),
            'bulk-clear-queue' => __('Clear Complete Queue', 'wp-mailster')
        );
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

		$this->items = self::mst_get_queued( $per_page, $current_page);
	}


    public function process_bulk_action() {
        $log = MstFactory::getLogger();
        //Detect when a bulk action is being triggered...
        $action = $this->current_action();
        $log->debug('mst_queued -> process_bulk_action: '.$action);
        switch ($action) {
            case 'bulk-delete':
                $selected_ids = esc_sql( $_REQUEST['bulk-action'] );
                if($selected_ids) {
                    foreach ( $selected_ids as $id ) {
                        list($mailId, $email) = explode(':', $id);
                        self::mst_delete_queued($mailId, $email);
                    }
                }
                break;
            case 'bulk-clear-queue':
                self::mst_clear_queue();
            default:
                # code...
                break;
        }
    }

}