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

class Mst_groups extends Mst_WP_List_Table {

	/** Class constructor */
	public function __construct() {

		parent::__construct( array(
			'singular' => __( 'Group', 'wp-mailster' ), //singular name of the grouped records
			'plural'   => __( 'Groups', 'wp-mailster' ), //plural name of the grouped records
			'ajax'     => false //does this table support ajax?
        ) );

	}

	/**
	 * Retrieve mailing groups data from the database
	 *
	 * @param int $per_page
	 * @param int $page_number
	 *
	 * @return mixed
	 */
	public static function mst_get_groups( $per_page = 20, $page_number = 1 ) {
		global $wpdb;
        $sql = self::getGroupsQuery();

		if ( ! empty( $_REQUEST['orderby'] ) ) {
			$sql .= ' ORDER BY ' . sanitize_sql_orderby( $_REQUEST['orderby'] );
			$sql .= ! empty( $_REQUEST['order'] ) ? ' ' . sanitize_text_field( $_REQUEST['order'] ) : ' ASC';
		} else {
            $sql .= ' ORDER BY name ASC' ;
            $_REQUEST['orderby'] = 'name';
            $_REQUEST['order'] = 'ASC';
        }

		$sql .= " LIMIT $per_page";
		$sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;


		$result = $wpdb->get_results( $sql, 'ARRAY_A' );

		return $result;
	}


    /**
     * Get all groups, take into account search variable
     * @return string
     */
    protected static function getGroupsQuery(){
        global $wpdb;
        $sql = "SELECT * FROM {$wpdb->prefix}mailster_groups";
        if ( ! empty( $_REQUEST['s'] ) ) {
            $searchTerm = sanitize_text_field( $_REQUEST['s'] );
            $sql .= ' WHERE name LIKE \'%'.$searchTerm.'%\'';
        }
        return $sql;
    }

	/**
	 * Delete a group record.
	 *
	 * @param int $id group ID
	 */
	public static function mst_delete_group( $id ) {
		global $wpdb;

		$wpdb->delete(
			"{$wpdb->prefix}mailster_groups",
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
        $sql = self::getGroupsQuery();
        $sql = "SELECT COUNT(*) FROM (".$sql.") GT";
		return $wpdb->get_var( $sql );
	}

	/** Text displayed when no groups data is available */
	public function no_items() {
		_e( 'No Groups available.', 'wp-mailster' );
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
		switch ( $column_name ) {
			case 'name':
				return $item[ $column_name ];
			default:
				return $item[ $column_name ];
				//return print_r( $item, true ); //Show the whole array for troubleshooting purposes
		}
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

		$delete_nonce = wp_create_nonce( 'mst_delete_group' );
		$edit_nonce = wp_create_nonce( 'mst_edit_group' );

		$title = sprintf( 
			'<a href="?page=mst_groups&amp;subpage=%s&amp;sid=%s&amp;_wpnonce=%s"><strong>%s</strong></a>',
			'edit',
			absint( $item['id'] ), 
			$edit_nonce,
			esc_html($item["name"])
		);

		$actions = array(
			'edit' => sprintf( 
				'<a href="?page=%s&amp;subpage=%s&amp;sid=%s&amp;_wpnonce=%s">%s</a>',
				sanitize_text_field( $_REQUEST['page'] ),
				'edit',
				absint( $item['id'] ),
				$edit_nonce,
				__("Edit", 'wp-mailster')
			),
			'delete' => sprintf( 
				'<a href="?page=%s&action=%s&id=%s&_wpnonce=%s">%s</a>',
				sanitize_text_field( $_REQUEST['page'] ),
				'delete',
				absint( $item['id'] ), 
				$delete_nonce ,
				__("Delete", 'wp-mailster')
			)
        );
		return $title . $this->row_actions( $actions );
	}
	
	/**
	 * Render the members counter column
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	function column_member_count( $item ) {
		global $wpdb;

		$sql = "SELECT count(user_id) as countt FROM {$wpdb->prefix}mailster_group_users where group_id = {$item['id']}";
		$result = $wpdb->get_results( $sql, 'ARRAY_A' );
		return $result[0]['countt'];
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
			'member_count' => __( '#Users in Group', 'wp-mailster' ),
            'id' => __( 'ID', 'wp-mailster' )
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
            'id' => array( 'id', true ),
			/*'member_count' => array( 'group_mail', false )*/
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
			'bulk-delete' => __('Delete', 'wp-mailster')
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

		$this->items = self::mst_get_groups( $per_page, $current_page );
	}

	public function process_bulk_action() {

		//Detect when a bulk action is being triggered...
		switch ($this->current_action()) {
			case 'delete':
				// In our file that handles the request, verify the nonce.
				$nonce = sanitize_text_field( $_REQUEST['_wpnonce'] );

				if ( ! wp_verify_nonce( $nonce, 'mst_delete_group' ) ) {
					die( 'Stop CSRF!' );
				} else {
					self::mst_delete_group( absint( $_GET['id'] ) );
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
					self::mst_delete_group( intval($id) );
				}
			}
		}
	}

	public function mst_get_lists(){

	}

	public function getAllGroups() {
		global $wpdb;
		$sql = "SELECT * FROM {$wpdb->prefix}mailster_groups ORDER BY name";
		$result = $wpdb->get_results( $sql );
		return $result;
	}
}
