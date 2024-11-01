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
 * Base Model
 *
 */
class MailsterModel
{
	var $_id = null;
	var $_data = null;

	function __construct($id = null){			
		$this->setId($id);
	}

	function setId($id, $forceReload=false)
	{
		$this->_id	    = (int)$id;
		$this->_loadData($forceReload);
	}

	public function getId() {
		return $this->_id;
	}

	function _loadData($forceReload = false)
	{	
		if ( $this->_id ) {		
			if ( empty ( $this->_data ) || $forceReload) {
				global $wpdb;
				$table_name = $this->getTable();
				$query = 'SELECT *'
						. ' FROM '.$table_name
						. ' WHERE id = '.$this->_id
						;
				$result = $wpdb->get_results( $query );
				$this->_data = $result;
				return $this->_data;
			}
		}
		return true;
	}

	function _initData()
	{
		if (empty($this->_data))
		{
			$this->_data = null;
			return (boolean) $this->_data;
		}
		return true;
	}
	
	public function getTable(){
		global $wpdb;
		$table_name = $wpdb->prefix . " ";
		return $table_name;
	}
	
	/**
	 * Method to get the record form.
	 *
	 * @param	array	$data		Data for the form.
	 * @param	boolean	$loadData	True if the form is to load its own data (default case), false if not.
	 * @return	mixed	A JForm object on success, false on failure
	 * @since	1.6
	 */
	public function getForm($data = array(), $loadData = true)
	{
        return false;
	}
	
	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return	mixed	The data for the form.
	 * @since	1.6
	 */
	public function getFormData()
	{
		// Check the session for previously entered form data.
		global $wpdb;
		$tablename = $this->getTable();
		if($this->_id) {
			$data = $wpdb->get_row( "SELECT * FROM " . $tablename . " WHERE id = " . $this->_id );
		} else {
			$this->_initData();
			$data = $this->_data;
		}
		return $data;
	}
	
	public function saveData($options, $action = 'add') {
		global $wpdb;
        $result = false;

		if ( $action == 'add' ) { //adding a new item
			$columns = $wpdb->insert( $this->getTable(), $options ); 
			$wpdb->show_errors(true);
			if ( $columns ) {
				$result = $wpdb->insert_id;
				$this->setId($wpdb->insert_id);
			} else {
				$result = false;
                MstFactory::getLogger()->error('MailsterModel->saveData, action: '.$action.', options: '.print_r($options, true));
                MstFactory::getLogger()->error('MailsterModel->saveData wpdb last error: '.$wpdb->last_error.', last query: '.print_r($wpdb->last_query, true));
			}
		} else if ( $action == 'edit' ) { //editing an existing item
			$result = $wpdb->update( $this->getTable(), $options, array( 'id' => $this->_id ) ); 
			if( false === $result ) {
                // do nothing, this is surely an error
                MstFactory::getLogger()->error('MailsterModel->saveData, action: '.$action.', options: '.print_r($options, true));
                MstFactory::getLogger()->error('MailsterModel->saveData wpdb last error: '.$wpdb->last_error.', last query: '.print_r($wpdb->last_query, true));
			} else { //even if result = 0
				$result = true;
			}
		//	$this->_loadData();
		}			
		return $result;			
	}

	public function getAll() {
		global $wpdb;
		$result = $wpdb->get_results("select * from " . $this->getTable());
		return $result;
	}

	protected function _getList($query, $limitstart=0,	$limit=0) {
		global $wpdb;
		$result = $wpdb->get_results( $query );
		return $result;
	}

    protected function setError($errorMessage, $method = null){
        MstFactory::getLogger()->error(get_class($this).(is_null($method)?': ' : '->'.$method.': ').$errorMessage);
    }

}