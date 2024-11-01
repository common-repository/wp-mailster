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
class MailsterModelServer extends MailsterModel
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
			$item = new stdClass();
			
			$item->name				= "";
			$item->server_type		= 0;
			$item->server_host		= "";
			$item->server_port		= 0;
			$item->secure_protocol	= "";
			$item->secure_authentication	= false;
			$item->protocol			= "";
			$item->connection_parameter		= "";
			$item->api_key1			= "";
			$item->api_key2			= "";
			$item->api_endpoint		= "";
			
			$this->_data = $item;
			return (boolean) $this->_data;
		}
		return true;
	}
	
	public function getInboxServers() {
		global $wpdb;
        $query = 'SELECT id, CONCAT(server_host, \':\', server_port, \' \', upper(protocol)) AS name'
			. ' FROM ' . $this->getTable()
			. ' WHERE server_type = '.MstConsts::SERVER_TYPE_MAIL_INBOX;
		$results = $wpdb->get_results( $query );
		$items = array(0 => __("New Server", 'wp-mailster'));
		foreach($results as $item) {
			$items[$item->id] = $item->name;
		}
		return $items;
	}

    function getSMTPServers() {
        global $wpdb;
        $query = 'SELECT id, CONCAT(server_host, \':\', server_port) AS name'
			. ' FROM ' . $this->getTable()
			. ' WHERE server_type = '.MstConsts::SERVER_TYPE_SMTP;
		$results = $wpdb->get_results( $query );
		$items = array(0 => __("New Server", 'wp-mailster'));
		foreach($results as $item) {
			$items[$item->id] = $item->name;
		}
		return $items;
    }

	public function getTable($type = 'mailster_servers', $prefix = '', $config = array()){
		global $wpdb;
		$table_name = $wpdb->prefix . "mailster_servers";
		return $table_name;
	}
	
	public function checkIfExists($server_host) {
		global $wpdb;
		$tablename = $this->getTable();
		$existinghost = $wpdb->get_var( $wpdb->prepare( 
			"SELECT 'server_host' FROM  $tablename WHERE 'server_host' = %s",
				$server_host
			)
		);

		return $existinghost;
	}

	//returns the lists that are using this server
	public function getLists() {
		$Lists = new MailsterModelList();
		return $Lists->getServerLists($this->_id);
	}

    /**
     * @param int $id Server ID
     * @return stdClass
     */
    function getServer($id){
		$this->setId($id);
		return $this->getData();
	}

	function &getData($id = null) {
		if ($this->_loadData())
		{

		}
		else  $this->_initData();

		return $this->_data;
	}

	function getProviderTypeSettings($providerType){
		global $wpdb;
		$query = 'SELECT *'
			.' FROM ' . $wpdb->prefix . 'mailster_servers'
			.' WHERE provider_type =\''.$providerType.'\'';
		return $wpdb->get_results( $query, ARRAY_A );
	}
}