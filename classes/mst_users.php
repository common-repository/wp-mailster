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

class Mst_users extends Mst_WP_List_Table {

	/** Class constructor */
	public function __construct() {

		parent::__construct( array(
			'singular' => __( 'User', 'wp-mailster' ), //singular name of the usered records
			'plural'   => __( 'Users', 'wp-mailster' ), //plural name of the usered records
			'ajax'     => true //does this table support ajax?
        ) );

	}

	/**
	 * Retrieve mailing users data from the database
	 *
	 * @param int $per_page
	 * @param int $page_number
	 *
	 * @return mixed
	 */
	public static function mst_get_users( $per_page = 20, $page_number = 1 ) {
		global $wpdb;

		$sql = self::getUserQuery();

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

        //MstFactory::getLogger()->debug('mst_users.php sql: '.$sql);
        //MstFactory::getLogger()->debug('mst_users.php result:'.print_r($result, true));

		return $result;
	}

    /**
     * Get SQL query for getting the union of WP core users + WP Mailster users, take into account search variable
     * @return string
     */
    protected static function getUserQuery(){
        global $wpdb;
        // IF(field1 IS NULL or field1 = '', 'empty', field1)
        $sql = 'SELECT id, name, email, notes, is_core_user '
            . ' FROM ('
                . ' SELECT id as id, CONVERT(name USING utf8mb4) as name, CONVERT(email USING utf8mb4) as email, CONVERT(notes USING utf8mb4) as notes, 0 as is_core_user'
                . ' FROM ' . $wpdb->prefix . 'mailster_users'
                . ' UNION ALL '
                . ' SELECT id, CONVERT(IF(name IS NULL or name = \'\', display_name, name) USING utf8mb4) AS name, CONVERT(email USING utf8mb4) as email, CONVERT(notes USING utf8mb4) as notes, is_core_user'
                . ' FROM ('
                    . ' SELECT id, GROUP_CONCAT(meta_value SEPARATOR \' \') AS name, display_name, email, notes, is_core_user'
                    . ' FROM ('
                        . ' SELECT ID as id, user_email as email, display_name, CONCAT(display_name, " (WordPress User)") as notes, 1 as is_core_user, meta_value'
                        . ' FROM ' . $wpdb->base_prefix . 'users wpusr'
                        . ' LEFT JOIN ' . $wpdb->base_prefix . 'usermeta wpusrmeta ON ( wpusr.id = wpusrmeta.user_id )'
                        . ' WHERE meta_key IN ( \'first_name\', \'last_name\' )'
                        . ' ORDER BY meta_key ASC'
                    . ' ) DTBL'
                    . ' GROUP BY id, display_name, email, notes, is_core_user'
                . ' ) DTBLCORE'
            . ') AS UNIF_USR';

        if ( ! empty( $_REQUEST['s'] ) ) {
            $searchTerm = sanitize_text_field( $_REQUEST['s'] );
            $sql .= ' WHERE name LIKE \'%'.$searchTerm.'%\' OR email LIKE  \'%'.$searchTerm.'%\' OR notes LIKE  \'%'.$searchTerm.'%\'';
        }

        return $sql;
    }

	/**
	 * Delete a user record.
	 *
	 * @param int $id user ID
	 */
	public static function mst_delete_user( $id ) {
		global $wpdb;

		$wpdb->delete(
			"{$wpdb->prefix}mailster_users",
            array( 'id' => $id ),
            array( '%d' )
		);
	}
	/**
	 * Activate a user record.
	 *
	 * @param int $id user ID
	 */
	public static function mst_activate_user( $id ) {
		global $wpdb;

		$wpdb->update( 
			"{$wpdb->prefix}mailster_users", 
			array( 'active' => 1 ), 
			array( 'id' => $id ) 
		);
	}
	/**
	 * Deactivate a user record.
	 *
	 * @param int $id user ID
	 */
	public static function mst_deactivate_user( $id ) {
		global $wpdb;

		$result = $wpdb->update( 
			"{$wpdb->prefix}mailster_users", 
			array( 'active' => 0 ), 
			array( 'id' => $id ) 
		);
	}

	/**
	 * Returns the count of records in the database.
	 *
	 * @return null|string
	 */
	public static function record_count() {
		global $wpdb;
        $sql = self::getUserQuery();
        $sql = 'SELECT COUNT(*) FROM ('.$sql.') UT';
		$count = $wpdb->get_var( $sql );
		return $count;
	}

	/** Text displayed when no users data is available */
	public function no_items() {
		_e( 'No users available.', 'wp-mailster' );
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
		if ( ! $item[ 'is_core_user' ] ) {
			return sprintf(
				'<input type="checkbox" name="bulk-action[]" value="%s" />', $item['id']
			);
		} else {
			return ' ';
		}
	}

	/**
	 * Method for name column
	 *
	 * @param array $item an array of DB data
	 *
	 * @return string
	 */
	function column_name( $item ) {

		$delete_nonce = wp_create_nonce( 'mst_delete_user' );
		$edit_nonce = wp_create_nonce( 'mst_edit_user' );

		if( $item["name"] == "" ) {
			$username = __("(no name)", 'wp-mailster');
		} else {
			$username = $item["name"];
		}

		if ( ! $item[ 'is_core_user' ] ) {
			$title = sprintf(
                '<a href="?page=%s&amp;subpage=%s&amp;sid=%s&amp;_wpnonce=%s"><strong>' . esc_html($username) . '</strong></a>',
                sanitize_text_field( $_REQUEST['page'] ),
                "edit",
                absint( $item['id'] ),
                $edit_nonce
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
					'<a href="?page=%s&amp;action=%s&amp;user=%s&amp;_wpnonce=%s">%s</a>',
					sanitize_text_field( $_REQUEST['page'] ),
					'delete',
					absint( $item['id'] ), 
					$delete_nonce ,
					__("Delete", 'wp-mailster')
				)
            );
		} else {
			$title = sprintf( '<a href="?page=mst_users_add&amp;user_action=edit&amp;sid=%s&amp;core=%d&amp;_wpnonce=%s"><strong>' . $item["name"] . '</strong></a>', absint( $item['id'] ), 1, $edit_nonce );
			$actions = array(
				'edit' => sprintf( 
					'<a href="?page=mst_users_add&amp;user_action=%s&amp;sid=%s&amp;core=%d&amp;_wpnonce=%s">%s</a>',
					'edit',
					absint( $item['id'] ),
					1,
					$edit_nonce,
					__("Edit", 'wp-mailster')
				)
            );
		}
        return $title . $this->row_actions( $actions );
	}

	/**
	 * Method for email column
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	function column_email( $item ) {
		return $item[ 'email' ];
	}

	/**
	 * Method for email column
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	function column_notes( $item ) {
		return $item[ 'notes' ];
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
			'notes' => __( 'Description', 'wp-mailster' )
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
			'email' => array( 'email', true )
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

		$this->items = self::mst_get_users( $per_page, $current_page );
	}

	public function process_bulk_action() {

		//Detect when a bulk action is being triggered...
		switch ($this->current_action()) {
			case 'delete':
				// In our file that handles the request, verify the nonce.
				$nonce = sanitize_text_field( $_REQUEST['_wpnonce'] );

				if ( ! wp_verify_nonce( $nonce, 'mst_delete_user' ) ) {
					die( 'Stop CSRF!' );
				} else {
					self::mst_delete_user( absint( $_GET['user'] ) );
				}
				break;
			case 'bulk-delete':
				$selected_ids = wp_parse_id_list($_REQUEST['bulk-action']);

				// loop over the array of record IDs and delete them
				if($selected_ids) {
					foreach ( $selected_ids as $id ) {
						self::mst_delete_user( intval($id) );
					}
				}
				break;
			default:
				# code...
				break;
		}
	}
}