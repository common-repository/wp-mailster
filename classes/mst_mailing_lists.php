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

class Mst_mailing_lists extends Mst_WP_List_Table {

	/** Class constructor */
	public function __construct() {
		parent::__construct( array(
			'singular' => __( 'Mailing List', 'wp-mailster' ), //singular name of the listed records
			'plural'   => __( 'Mailing Lists', 'wp-mailster' ), //plural name of the listed records
			'ajax'     => true //does this table support ajax?
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
	public static function mst_get_lists( $per_page = 20, $page_number = 1 ) {
        global $wpdb;
		$sql = self::getMailingListsQuery();
        $log = MstFactory::getLogger();

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
        $recips = MstFactory::getRecipients();
        foreach($result AS $key=>$list){
            $result[$key]['recipCount'] = $recips->getTotalRecipientsCount($list['id']);
        }

		return $result;
	}

    /**
     * Get all mailing lists, take into account search variable
     * @return string
     */
    protected static function getMailingListsQuery(){
        global $wpdb;
        $sql = "SELECT * FROM {$wpdb->prefix}mailster_lists";
        if ( ! empty( $_REQUEST['s'] ) ) {
            $searchTerm = sanitize_text_field( $_REQUEST['s'] );
            $sql .= ' WHERE name LIKE \'%'.$searchTerm.'%\' OR list_mail LIKE  \'%'.$searchTerm.'%\'';
        }
        return $sql;
    }

	/**
	 * Delete a list record.
	 *
	 * @param int $id list ID
	 */
	public static function mst_delete_list( $id ) {
		global $wpdb;

		$wpdb->delete(
			"{$wpdb->prefix}mailster_lists",
            array( 'id' => $id ),
            array( '%d' )
		);
	}
    /**
     * Copy (duplicate) a list record.
     *
     * @param int $id list ID
     */
    public static function mst_copy_list( $id ) {
        global $wpdb;
        $log = MstFactory::getLogger();
        $listModel = MstFactory::getListModel();
        $rows = $listModel->getData($id, true);
        $row = $rows[0];
        $log->debug('mst_copy_list copy list '.$id.', copy row: '.print_r($row, true));
        if($row && $row->id > 0){
            $row->id = null;
            $row->active = 0;
            $row->name = $row->name . ' - ' . _x('Copy', 'noun', 'default');
            $row->last_check = null;
            $row->last_mail_retrieved = null;
            $row->last_mail_sent = null;

        }

        $listModel->saveData(get_object_vars($row));

    }
	/**
	 * Activate a list record.
	 *
	 * @param int $id list ID
	 */
	public static function mst_activate_list( $id ) {
		global $wpdb;

		$wpdb->update( 
			"{$wpdb->prefix}mailster_lists",
			array( 'active' => 1 ), 
			array( 'id' => $id ) 
		);
	}

	/**
	 * Deactivate a list record.
	 *
	 * @param int $id list ID
	 */
	public static function mst_deactivate_list( $id ) {
		global $wpdb;

		$result = $wpdb->update( 
			"{$wpdb->prefix}mailster_lists", 
			array( 'active' => 0 ), 
			array( 'id' => $id ) 
		);
	}

    /**
     * Duplicate a list record.
     *
     * @param int $id list ID
     */
    public static function mst_duplicate_list( $id ) {
        global $wpdb;
        $log = MstFactory::getLogger();
        $log->debug('mst_duplicate_list for id: '.$id);
        $listModel = MstFactory::getListModel();
        $mListObj = $listModel->getData($id, true);
        if(is_array($mListObj) && count($mListObj)>0){
            $mListObj = $mListObj[0];
        }
        $log->debug('mst_duplicate_list mListObj: '.print_r($mListObj, true));

        $mListArr = (array) $mListObj;
        $log->debug('mst_duplicate_list mListArr 1: '.print_r($mListArr, true));
        $mListArr['id'] = null; // reset id
        $mListArr['active'] = 0; // deactivate
        $mListArr['name'] = $mListArr['name'] . ' (' . strtoupper(__('Copy',  'wp-mailster' )).')';  // make name a little different
        $mListArr['last_check'] = null; // reset timestamp
        $mListArr['last_mail_retrieved'] = null; // reset timestamp
        $mListArr['last_mail_sent'] = null; // reset timestamp

        $log->debug('mst_duplicate_list mListArr 2: '.print_r($mListArr, true));

        $listModel->saveData($mListArr);
    }

	/**
	 * Returns the count of records in the database.
	 *
	 * @return null|string
	 */
	public static function record_count() {
		global $wpdb;
        $sql = self::getMailingListsQuery();
		$sql = "SELECT COUNT(*) FROM (".$sql.") UM";
		return $wpdb->get_var( $sql );
	}

	/** Text displayed when no lists data is available */
	public function no_items() {
		_e( 'No Lists available.', 'wp-mailster' );
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

	function column_active( $item ) {
		if($item['active']) {
			$deactivate_nonce = wp_create_nonce( 'mst_deactivate_list' );
			return sprintf( 
				'<a href="?page=%s&amp;action=%s&amp;id=%s&amp;_wpnonce=%s" title="%s"><span class="dashicons dashicons-yes"></span></a>',
				sanitize_text_field( $_REQUEST['page'] ),
				'deactivate',
				absint( $item['id'] ), 
				$deactivate_nonce ,
				__("Activated - click to Deactivate", 'wp-mailster')
			);
		} else {
			$activate_nonce = wp_create_nonce( 'mst_activate_list' );
			return sprintf( 
				'<a href="?page=%s&amp;action=%s&amp;id=%s&amp;_wpnonce=%s" title="%s"><span class="dashicons  dashicons-no-alt"></span></a>',
				sanitize_text_field( $_REQUEST['page'] ),
				'activate',
				absint( $item['id'] ), 
				$activate_nonce ,
				__("Deactivated - click to Activate", 'wp-mailster')
			);
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

        $copy_nonce = wp_create_nonce( 'mst_copy_list' );
		$delete_nonce = wp_create_nonce( 'mst_delete_list' );
		$edit_nonce = wp_create_nonce( 'mst_edit_list' );

		$title = sprintf(
            '<a href="?page=%s&subpage=edit&lid=%s&_wpnonce=%s"><strong>' . esc_html($item["name"]) . '</strong></a>',
            sanitize_text_field( $_REQUEST['page'] ), absint( $item['id'] ),
            $edit_nonce
        );

		$activeThing = "";
		if($item['active']) {
			$deactivate_nonce = wp_create_nonce( 'mst_deactivate_list' );
			$activeThing = sprintf(
				'<a href="?page=%s&amp;action=%s&amp;id=%s&amp;_wpnonce=%s" title="%s"><span class="dashicons dashicons-yes"></span></a>',
				sanitize_text_field( $_REQUEST['page'] ),
				'deactivate',
				absint( $item['id'] ),
				$deactivate_nonce ,
				__("Activated - click to Deactivate", 'wp-mailster')
			);
		} else {
			$activate_nonce = wp_create_nonce( 'mst_activate_list' );
			$activeThing = sprintf(
				'<a href="?page=%s&amp;action=%s&amp;id=%s&amp;_wpnonce=%s" title="%s"><span class="dashicons  dashicons-no-alt"></span></a>',
				sanitize_text_field( $_REQUEST['page'] ),
				'activate',
				absint( $item['id'] ),
				$activate_nonce ,
				__("Deactivated - click to Activate", 'wp-mailster')
			);
		}

		$actions = array(
			'edit' => sprintf( 
				'<a href="?page=%s&subpage=%s&lid=%s&_wpnonce=%s">%s</a>',
				sanitize_text_field( $_REQUEST['page'] ),
				'edit',
				absint( $item['id'] ),
				$edit_nonce,
				__("Edit", 'wp-mailster')
			),
            'manage' => sprintf(
                '<a href="?page=%s&subpage=%s&lid=%d">%s</a>',
                sanitize_text_field( $_REQUEST['page'] ),
                'recipients',
                absint( $item['id'] ),
                __("Manage recipients", 'wp-mailster')
            ),
            'copy' => sprintf(
                '<a href="?page=%s&action=%s&list=%s&_wpnonce=%s">%s</a>',
                sanitize_text_field( $_REQUEST['page'] ),
                'copy',
                absint( $item['id'] ),
                $copy_nonce ,
                __("Copy", 'wp-mailster')
            ),
			'delete' => sprintf( 
				'<a href="?page=%s&action=%s&list=%s&_wpnonce=%s">%s</a>',
				sanitize_text_field( $_REQUEST['page'] ),
				'delete',
				absint( $item['id'] ), 
				$delete_nonce ,
				__("Delete", 'wp-mailster')
			)/*,
			'activate' => $activeThing*/
        );
		return $title . $this->row_actions( $actions );
	}

    function column_recipCount( $item ) {
        return sprintf(
            '<a href="?page=%s&subpage=%s&lid=%d"><span title="%s">%d</span></a>',
            sanitize_text_field( $_REQUEST['page'] ),
            'recipients',
            absint( $item['id'] ),
            __("Manage recipients", 'wp-mailster'),
            $item["recipCount"]);
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
			'active' => __( 'Status', 'wp-mailster' ),
			'list_mail' => __( 'Mailing list email', 'wp-mailster' ),
            'recipCount' => __( '#Recipients', 'wp-mailster' ),
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
			'list_mail' => array( 'list_mail', false ),
            'id' => array( 'id', false )
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
			'bulk-activate' => __('Activate', 'wp-mailster'),
			'bulk-deactivate' => __('Deactivate', 'wp-mailster'),
            'bulk-duplicate' => __('Duplicate', 'wp-mailster'),
			'bulk-delete' => __('Delete', 'wp-mailster')
		);
		return $actions;
	}

	/**
	 * Handles data query and filter, sorting, and pagination.
	 */
	public function prepare_items() {
		/** Process bulk action */
		$this->process_bulk_action();
		$this->_column_headers = $this->get_column_info();

		$per_page     = $this->get_items_per_page( 'edit_post_per_page', 20 );
		$current_page = $this->get_pagenum();
		$total_items  = self::record_count();

		$this->set_pagination_args( array(
			'total_items' => $total_items, //WE have to calculate the total number of items
			'per_page'    => $per_page //WE have to determine how many items to show on a page
        ) );

		$this->items = self::mst_get_lists( $per_page, $current_page );
	}

	public function process_bulk_action() {

		//Detect when a bulk action is being triggered...
		switch ($this->current_action()) {
			case 'delete':
				// In our file that handles the request, verify the nonce.
				$nonce = sanitize_text_field( $_REQUEST['_wpnonce'] );

				if ( ! wp_verify_nonce( $nonce, 'mst_delete_list' ) ) {
					die( 'Stop CSRF!' );
				} else {
					self::mst_delete_list( absint( $_GET['list'] ) );
				}
				break;

            case 'copy':
                // In our file that handles the request, verify the nonce.
                $nonce = sanitize_text_field( $_REQUEST['_wpnonce'] );

                if ( ! wp_verify_nonce( $nonce, 'mst_copy_list' ) ) {
                    die( 'Stop CSRF!' );
                } else {

                    if(count(MstFactory::getMailingListUtils()->getAllMailingLists(false))+1 > MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_MLT)){
                        die('Too many mailing lists for this production edition, cannot copy');
                    }else{
                        self::mst_copy_list( absint( $_GET['list'] ) );
                    }
                }
                break;

			case 'activate':
				$nonce = sanitize_text_field( $_REQUEST['_wpnonce'] );
				if ( ! wp_verify_nonce( $nonce, 'mst_activate_list' ) ) {
					die( 'Stop CSRF!' );
				} else {
					self::mst_activate_list( absint( $_GET['id'] ) );
					//wp_redirect( esc_url( add_query_arg() ) );
					$query = array();

					if ( ! empty( $deleted ) )
						$query['message'] = 'activated';
				}
				break;

			case 'deactivate':
				$nonce = sanitize_text_field( $_REQUEST['_wpnonce'] );
				if ( ! wp_verify_nonce( $nonce, 'mst_deactivate_list' ) ) {
					die( 'Stop CSRF!' );
				} else {
					self::mst_deactivate_list( absint( $_GET['id'] ) );
				}
				break;

			case 'bulk-delete':
				$selected_ids = esc_sql( $_REQUEST['bulk-action'] );
				if($selected_ids) {
					foreach ( $selected_ids as $id ) {
						self::mst_delete_list( intval($id) );
					}
				}
				break;

			case 'bulk-activate':
				$selected_ids = esc_sql( $_REQUEST['bulk-action'] );
				if($selected_ids) {
					foreach ( $selected_ids as $id ) {
						self::mst_activate_list( intval($id) );
					}
				}
				break;

			case 'bulk-deactivate':
				$selected_ids = esc_sql( $_REQUEST['bulk-action'] );
				if($selected_ids) {
					foreach ( $selected_ids as $id ) {
						self::mst_deactivate_list( intval($id) );
					}
				}
				break;

            case 'bulk-duplicate':
                $selected_ids = esc_sql( $_REQUEST['bulk-action'] );
                if($selected_ids) {
                    foreach ( $selected_ids as $id ) {
                        self::mst_duplicate_list( intval($id) );
                    }
                }
                break;

			default:
				# code...
				break;
		}
	}
}
