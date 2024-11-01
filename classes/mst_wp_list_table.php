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
if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Mst_WP_List_Table extends WP_List_Table {

    /** Class constructor */
    public function __construct($args = array()) {
        if(!array_key_exists('hook_suffix', $GLOBALS)){
            $GLOBALS['hook_suffix'] = ''; // to circumvent the issue of PHP Notice:  Undefined index: hook_suffix
        }
        parent::__construct($args);
        add_action('admin_footer', array($this, 'loadJavascriptActions'));
    }


    function loadJavascriptActions(){ // override with extending functionality ?>
        <script type="text/javascript">
            if (/^((?!chrome|android).)*safari/i.test(navigator.userAgent)) {
                document.body.classList.add("safari"); // This is Safari, so add a specific class to the body
            }else{
                document.body.classList.add("non-safari");
            }
            (function ($) {
            })(jQuery);
        </script>
        <?php
    }
}
