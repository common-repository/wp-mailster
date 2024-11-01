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

class Mst_group_members extends Mst_WP_List_Table {

	/** Class constructor */
	public function __construct() {

		parent::__construct( array(
			'singular' => __( 'Group Member', 'wp-mailster' ), //singular name of the listed records
			'plural'   => __( 'Group Members', 'wp-mailster' ), //plural name of the listed records
			'ajax'     => false //does this table support ajax?
        ) );

	}

	/**
	 * Retrieve mailing lists data from the database
	 *
	 * @param int $per_page
	 * @param int $page_number
	 *
	 * @return mixed
	 */
	public static function mst_get_group_members( $per_page = 20, $page_number = 1 ) {

		global $wpdb;

		$sql = "SELECT * FROM {$wpdb->prefix}mailster_group_users";

		if ( ! empty( $_REQUEST['orderby'] ) ) {
			$sql .= ' ORDER BY ' . sanitize_sql_orderby( $_REQUEST['orderby'] );
			$sql .= ! empty( $_REQUEST['order'] ) ? ' ' . sanitize_text_field( $_REQUEST['order'] ) : ' ASC';
		}

		$sql .= " LIMIT $per_page";
		$sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;


		$result = $wpdb->get_results( $sql, 'ARRAY_A' );

		return $result;
	}

	/**
	 * Delete a list record.
	 *
	 * @param int $id list ID
	 */
	public static function mst_delete_group_member( $id ) {
		global $wpdb;

		$wpdb->delete(
			"{$wpdb->prefix}mailster_group_users",
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

		$sql = "SELECT COUNT(*) FROM {$wpdb->prefix}mailster_group_users";

		return $wpdb->get_var( $sql );
	}

	/** Text displayed when no lists data is available */
	public function no_items() {
		_e( 'No Group Members available.', 'wp-mailster' );
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
	 * Method for name column
	 *
	 * @param array $item an array of DB data
	 *
	 * @return string
	 */
	function column_name( $item ) {

        $delete_nonce = wp_create_nonce( 'mst_delete_group_user' );
		$edit_nonce = wp_create_nonce( 'mst_edit_group_user' );
		if( $item["name"] == "" ) {
			$username = __("(no name)", 'wp-mailster');
		} else {
			$username = $item["name"];
		}
		$title = sprintf(
			'<a href="?page=wpmst_groups&amp;group_action=%s&amp;id=%s&amp;_wpnonce=%s"><strong>%s</strong></a>',
			'edit',
			absint( $item['id'] ),
			$edit_nonce,
			esc_html($username)
		);

		$actions = array(
			'delete' => sprintf(
				'<a href="?page=%s&action=%s&id=%s&_wpnonce=%s">%s</a>',
				sanitize_text_field( $_REQUEST['page'] ),
				'delete',
				absint( $item['id'] ),
				$delete_nonce ,
				__("Remove", 'wp-mailster')
			),
        );
		return $title . $this->row_actions( $actions );
	}

	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	function get_columns() {
		$columns = array(
			'cb'      => '<input type="checkbox" />',
			'name'    => __( 'Name', 'wp-mailster' ),
			'email' => __( 'Email', 'wp-mailster' ),
			'role' => __('Role', 'wp-mailster' )
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
			'role' => array( 'role', true ),
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
			'bulk-delete' => __('Remove', 'wp-mailster')
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

		$this->items = self::mst_get_group_members( $per_page, $current_page );
	}

	public function process_bulk_action() {
		$list = intval($_GET['list']);
		//Detect when a bulk action is being triggered...
		switch ($this->current_action()) {
			case 'delete':
				// In our file that handles the request, verify the nonce.
				$nonce = esc_attr( sanitize_text_field($_REQUEST['_wpnonce']) );

				if ( ! wp_verify_nonce( $nonce, 'mst_delete_group_user' ) ) {
					die( 'Stop CSRF!' );
				} else {
					self::mst_delete_group_user( absint( $list ) );
				}
				break;
			default:
				# code...
				break;
		}
		
		
		// If the delete bulk action is triggered

		if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-delete' )
		     || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-delete' )
		) {

			$selected_ids = wp_parse_id_list($_POST['bulk-action']);

			// loop over the array of record IDs and delete them
			if($selected_ids) {
				foreach ( $selected_ids as $id ) {
					self::mst_delete_group_user( intval($id) );

				}
			}
		}
	}
}
