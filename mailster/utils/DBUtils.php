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

class MstDBUtils
{
	public static function getDateTimeNow(){
		$query = 'SELECT NOW()';
		return self::getDateTimeResult($query);
	}
	
	public static function getDateTimeTodayLastMidnight(){
		$query = 'SELECT concat( CURDATE( ) , \' 00:00:00\' ) ';
		return self::getDateTimeResult($query);
	}
	
	public static function getDateTimeTomorrowNextMidnight(){
		$query = 'SELECT concat( CURDATE( ) , \' 00:00:00\' )  + INTERVAL 1 DAY';
		return self::getDateTimeResult($query);
	}
	
	public static function getDateTimeLastSundayMidnight(){
		$query = 'SELECT concat( DATE( CURDATE( ) + INTERVAL( 1 - DAYOFWEEK( CURDATE( ) ) ) DAY ) , \' 00:00:00\' )';
		return self::getDateTimeResult($query);
	}
	
	public static function getDateTimeNextSundayMidnight(){
		$query = 'SELECT concat( DATE( CURDATE( ) + INTERVAL( 1 - DAYOFWEEK( CURDATE( ) ) ) DAY ) , \' 00:00:00\' ) + INTERVAL 1 WEEK';
		return self::getDateTimeResult($query);
	}
	
	public static function getDateTimeThisMonthLastDayMidnight(){
		$query = 'SELECT concat(LAST_DAY(CURDATE()), \' 00:00:00\')';
		return self::getDateTimeResult($query);
	}
	
	public static function getDateTimeLastMonthLastDayMidnight(){
		$query = 'SELECT concat(LAST_DAY(DATE_SUB(NOW(), INTERVAL 1 MONTH)), \' 00:00:00\')';
		return self::getDateTimeResult($query);
	}
	
	protected static function getDateTimeResult($query){
		global $wpdb;
		$dateTime = $wpdb->get_var( $query );
		return $dateTime;
	}

    /**
     * Get the UTC/Unixtimestamp $date in the format that the DB would change it to when it would be produced through NOW() and the MySQL timezone setting
     * @param $date Timestamp according to UTC, in Y-m-d H:i:s format
     * @return null|string Timestamp according to the locale of the DB connection
     */
    public static function getTimestampFromDate($date){
        $query = 'SELECT CONVERT_TZ(\''.$date.'\', \'+00:00\',\'SYSTEM\') AS utc_conv_time';
        return self::getDateTimeResult($query);
	}
	
	public static function userTableCollationOk(){
		global $wpdb;
		$cEmailCollation = self::getCollation( $wpdb->base_prefix . 'users', 'user_email');
		$cNameCollation = self::getCollation( $wpdb->base_prefix . 'users', 'display_name');
		$mEmailCollation = self::getCollation( $wpdb->prefix . 'mailster_users', 'email');
		$mNameCollation = self::getCollation( $wpdb->prefix . 'mailster_users', 'name');
		return ( ($cEmailCollation === $mEmailCollation) && ($cNameCollation === $mNameCollation) );
	}
	
	public static function getCollation($tableName, $field=null){
		global $wpdb;
		$query = 'SHOW FULL COLUMNS FROM ' . $tableName . ' WHERE 1=1';

        $array = $wpdb->get_results($query, ARRAY_A);
		if(is_null($field)){
			foreach($array as $key=>$column){
				$collation = $column['Collation'];
				if(!is_null($collation) && (strlen($collation) > 0) ){
					return $collation;
				}
			}
		}else{
			foreach($array as $resultfield) {
				if( $resultfield['Field'] == $field) {
					return $resultfield['Collation'];
				}
			}
		}
		return null;
	}
	
	public static function alterCollation($tableName, $field, $newCollation){
		$log = MstFactory::getLogger();
		global $wpdb;
		$colExists = false;
        $definition = null;
		$colData = self::getTableColumns($tableName);
		foreach ($colData as $valCol) {
			if ($valCol->Field == $field) {
				$colExists = true;
				$log->debug(print_r($valCol, true));
				$definition = $valCol->Type . ' ';
				if($valCol->Default){
					$defaultVal = ' DEFAULT ' . $valCol->Default;
				}else{
					$defaultVal = ' ';
				}
				if(strtoupper($valCol->Null) === 'YES'){
					$nullVal = 'NULL';
				}else{
					$nullVal = 'NOT NULL';
					if($valCol->Key && $valCol->Key !== ''){
						$defaultVal = ' ';
					}
				}
				$definition .= $nullVal . $defaultVal . ' ' . $valCol->Extra;				
				break;
			}
		}		
		if ($colExists && $definition) {
			$query =  'ALTER TABLE ' . $tableName . ' CHANGE ' . $field . ' '. $field .' ' . $definition .' COLLATE ' .$newCollation;
			$errorMsg = '';
            $result = false;
            try {
                $result = $wpdb->query( $query );
            } catch (Exception $e) {
                $errorMsg = 'Exception ' . get_class($e) . ' Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
                $log->error('Error while changing collation from ' . $field . ' to ' . $newCollation . ' in ' . $tableName . ', Message: ' . $errorMsg);
                $log->error('Query was: '.$query);
                return -1;
            }
			if (!$result){
				$log->error('Could not change collation from ' . $field . ' to ' . $newCollation . ' in ' . $tableName . ', Message: ' . $errorMsg);
                $log->error('Query was: '.$query);
				return -1;
			}	
			$log->info('Changed collation of ' . $field . ' to ' . $newCollation . ' in ' . $tableName );
			return 1;
		}	
		$log->info('' . $field . ' not in ' . $tableName . ', not changing collation to ' . $newCollation);
		return 0;	
	}
	
	public static function isColExisting($tblName, $col){
		$colExists = false;
		global $wpdb;
		$result = $wpdb->query(
			$wpdb->prepare( "SELECT * FROM information_schema.COLUMNS WHERE TABLE_NAME = %s AND COLUMN_NAME = %s", $tblName, $col )
		);
		if($result !== false && $result !== 0) {
			$colExists = true;
		}
		return $colExists;
	}
	
	public static function isTableExisting($tblName){
		global $wpdb;
		$tblName = $wpdb->prefix.$tblName;
		$query = 'SHOW TABLES LIKE \''.$tblName.'\'';
		$results = $wpdb->query( $query );
		if( $results !== false && $results !== 0) {
			return true;
		} else {
			return false;
		}
	}
	
	public static function deleteCol($tblName, $col){
        $log = MstFactory::getLogger();
		global $wpdb;
		$query = 'ALTER TABLE ' . $tblName . ' DROP ' . $col;
		$errorMsg = '';
        try {
            $result = $wpdb->query( $query );
        } catch (Exception $e) {
            $errorMsg = 'Exception ' . get_class($e) . ' Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
            $log->error('failed: deleteCol for table '.$tblName.' and col '.$col.': '.$errorMsg);
            $log->error('Query was: '.$query);
            return false;
        }
		if (!$result){
		    $errorMsg = $wpdb->last_error;
            $log->error('failed: deleteCol for table '.$tblName.' and col '.$col.': '.$errorMsg);
            $log->error('Query was: '.$query);
			return false;
		}		
		return true;
	}
	
	public static function deleteColIfExists($tblName, $col){
		$colExists = self::isColExisting($tblName, $col);
		if($colExists){
			return self::deleteCol($tblName, $col);
		}
		return 0;
	}
	
	public static function getTableColumns($tblName){
		global $wpdb;
		$query = 'SHOW COLUMNS FROM '.$tblName;
		$results = $wpdb->get_results( $query );
		if( ! $results ) {
			return -1;
		}
		return $results;
	}
	
	public static function getTableRowCount($tblName){
        $log = MstFactory::getLogger();
		global $wpdb;
		$query = 'SELECT COUNT(*) FROM '.$tblName;
		$errorMsg = '';
        try {
            $result = $wpdb->get_var( $query );
        } catch (Exception $e) {
            $errorMsg = 'Exception ' . get_class($e) . ' Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
            $log->error('failed: getTableRowCount for table '.$tblName.': '.$errorMsg);
            $log->error('Query was: '.$query);
            return -1;
        }
		if (is_null($result)){
            $errorMsg = $wpdb->last_error;
            $log->error('failed: getTableRowCount for table '.$tblName.': '.$errorMsg);
            $log->error('Query was: '.$query);
			return -1;
		}
        return intval($result);
	}
	
	public static function alterDefaultValue($table, $col, $newDefault){
		$log = MstFactory::getLogger();
		global $wpdb;
		$colExists 	= false;
		$defaultNeedsAltering 	= false;	
		$colData = self::getTableColumns($table);		
		foreach ($colData as $valCol) {
			if ($valCol->Field == $col) {
				$colExists = true;
				if($valCol->Default){
					if($valCol->Default != $newDefault){
						$defaultNeedsAltering = true;
					}
				}else{
					$defaultNeedsAltering = true;
				}
			}
		}
				
		if ($colExists && $defaultNeedsAltering) {
			$query =  $wpdb->prepare( 
				'ALTER TABLE %s ALTER %s SET DEFAULT %s',
				$table,
				$col,
				$newDefault
			);

			$errorMsg = '';
            try {
                $result = $wpdb->query( $query );
            } catch (Exception $e) {
                $errorMsg = 'Exception ' . get_class($e) . ' Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
                $log->error('Error while altering default of ' . $col . ' of table ' . $table . ' to: ' . $newDefault);
                $log->error('Exception: '.$e->getMessage());
                $log->error('Query was: '.$query);
                return -1;
            }
			if (!$result){
			    $errorMsg = $wpdb->last_error;
				$log->error('Error while altering default of ' . $col . ' of table ' . $table . ' to: ' . $newDefault.', error: '.$errorMsg);
                $log->error('Query was: '.$query);
				return -1;
			}	
			$log->info('Cannot alter default value -> column'  .  $col . ' is not in ' . $table);
			return 1;
		}	
		$log->info('Column ' . $col . ' already in ' . $table);
		return 0;
		
	}
	
	public static function addColIfNotExists($table, $col, $atts, $afterCol ) {		
		$log = MstFactory::getLogger();
		global $wpdb;
		$colExists 	= false;
		$colData = self::getTableColumns($table);		
		foreach ($colData as $valCol) {
			if ($valCol->Field == $col) {
				$colExists = true;
				break;
			}
		}		
		if (!$colExists) {	

            $query = 'ALTER TABLE '.$table.' ADD '.$col.' '.$atts.' AFTER '.$afterCol;
			$errorMsg = '';
            $log->debug('addColIfNotExists query: '.$query);
            try {
                $result = $wpdb->query( $query );
            } catch (Exception $e) {
                $errorMsg = 'Exception ' . get_class($e) . ' Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
                $log->error('Error while adding ' . $col . ' to ' . $table . ', Message: ' . $errorMsg);
                $log->error('Query was: '.$query);
                return -1;
            }
			if(!$result){
			    $errorMsg = $wpdb->last_error;
				$log->error('Could not add column ' . $col . ' to ' . $table . ', Message: ' . $errorMsg.', query was: '.$query);
                $log->error('Query was: '.$query);
				return -1;
			}	
			$log->info('Added ' . $col . ' to ' . $table);
			return 1;
		}	
		$log->info('Column ' . $col . ' already in ' . $table);
		return 0;
	}
	
	public static function changeColType($table, $col, $newDefinition ) {	
		$log = MstFactory::getLogger();
		global $wpdb;
		$colExists 	= false;
		$colTypeDifferent = false;
		$colData = self::getTableColumns($table);
		$earlierDefinition = null;
		foreach ($colData as $valCol) {
			if ($valCol->Field == $col) {
				$colExists = true;
				$definition = $valCol->Type . ' ';
				if($valCol->Default){
					$defaultVal = ' DEFAULT ' . $valCol->Default;
				}else{
					$defaultVal = ' ';
				}
				if(strtoupper($valCol->Null) === 'YES'){
					$nullVal = 'NULL';
				}else{
					$nullVal = 'NOT NULL';
					if($valCol->Key && $valCol->Key !== ''){
						$defaultVal = ' ';
					}
				}
                $earlierDefinition = trim(strtolower($valCol->Type));
                if(trim(strtolower($valCol->Type)) !== (trim(strtolower($newDefinition)))){
                    $log->info('changeColType() Difference between ' . trim(strtolower($valCol->Type)) . ' and ' . trim(strtolower($newDefinition)) . ' in ' . $table .', recheck with null value' );
                    if(trim(strtolower($valCol->Type.' '.$nullVal)) !== (trim(strtolower($newDefinition)))){
                        $log->info('changeColType() Difference between ' . trim(strtolower($valCol->Type.' '.$nullVal)) . ' and ' . trim(strtolower($newDefinition)) . ' in ' . $table );
                        $colTypeDifferent = true;
                    }else{
                        $log->info('changeColType() No difference between ' . trim(strtolower($valCol->Type.' '.$nullVal)) . ' and ' . trim(strtolower($newDefinition)) . ' in ' . $table );
                    }
                }else{
                    $log->info('changeColType() No difference between ' . trim(strtolower($valCol->Type)) . ' and ' . trim(strtolower($newDefinition)) . ' in ' . $table );
                }
				$definition .= $nullVal . $defaultVal . ' ' . $valCol->Extra;				
				break;
			}
		}	
		if ($colExists) {	
			if($colTypeDifferent){
				$query =  'ALTER TABLE ' . $table . ' MODIFY ' . $col . ' ' . $newDefinition;
				$result = $wpdb->query( $query );

				if(!$result){
					$log->error('Error while modifying column ' . $col . ' to ' . $newDefinition . ' in ' . $table . ', Message: ' . $wpdb->last_error);
                    $log->error('changeColType() query was: '.$query);
					return -1;
				}	
				$log->info('Modified ' . $col . ' to ' . $newDefinition . ' (previously: ' .  $earlierDefinition . ') in ' . $table );
				return 1;
			}
			$log->info('' . $col . ' type is already ' . $newDefinition  . ', no need to modify');
			return 0;
		}	
		$log->info('' . $col . ' not in ' . $table . ', not modifying to ' . $newDefinition);
		return 0;
	}
	
	
	public static function renameCol($table, $oldName, $newName ) {	
		$log = MstFactory::getLogger();
		global $wpdb;
		$colExists 	= false;
        $definition = false;
		$colData = self::getTableColumns($table);
		foreach ($colData as $valCol) {
			if ($valCol->Field == $oldName) {
				$colExists = true;
				$definition = $valCol->Type . ' ';
				if($valCol->Default){
					$defaultVal = ' DEFAULT ' . $valCol->Default;
				}else{
					$defaultVal = ' ';
				}
				if(strtoupper($valCol->Null) === 'YES'){
					$nullVal = 'NULL';
				}else{
					$nullVal = 'NOT NULL';
					if($valCol->Key && $valCol->Key !== ''){
						$defaultVal = ' ';
					}
				}
				$definition .= $nullVal . $defaultVal . ' ' . $valCol->Extra;				
				break;
			}
		}		
		if ($colExists && $definition) {
			$query =  $wpdb->prepare(
                'ALTER TABLE %1s CHANGE %1s %1s %1s',
				$table,
				$oldName,
				$newName,
				$definition
			);
			$errorMsg = '';
            try {
                $result = $wpdb->query( $query );
            } catch (Exception $e) {
                $errorMsg = 'Exception ' . get_class($e) . ' Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
                $log->error('Error while renaming ' . $oldName . ' to ' . $newName . ' in ' . $table . ', Message: ' . $errorMsg);
                $log->error('Query was: '.$query);
                return -1;
            }
			if(!$result){
			    $errorMsg = $wpdb->last_error;
				$log->error('Error while renaming ' . $oldName . ' to ' . $newName . ' in ' . $table . ', Message: ' . $errorMsg);
                $log->error('Query was: '.$query);
				return -1;
			}	
			$log->info('Renamed ' . $oldName . ' to ' . $newName . ' in ' . $table );
			return 1;
		}	
		$log->info('' . $oldName . ' not in ' . $table . ', not renaming to ' . $newName);
		return 0;
	}
	
	public static function createIndexIfNotExists($table, $indexName, $cols, $type='INDEX'){
		$log = MstFactory::getLogger();
		global $wpdb;
		$indexExists = false;
		$query = 'SHOW INDEXES FROM ' . $table;
		$result = $wpdb->get_results( $query );
		for($i=0; $i < count($result); $i++){
			$currIndex = &$result[$i];
			$currIndexName = $currIndex->Key_name;
			if($currIndexName == $indexName){
				$indexExists = true;
				break;
			}
		}
		
		$query = '';
		
		if(!$indexExists){
		
			$query = 'ALTER TABLE ' . $table . ' ADD INDEX ' . $indexName . ' ( ' . $cols[0];
			for($i=1; $i < count($cols); $i++){
				$query .= ', ' . $cols[$i];
			}
			$query .= ')';
			$errorMsg = '';
            try {
                $result = $wpdb->query( $query );
            } catch (Exception $e) {
                $errorMsg = 'Exception ' . get_class($e) . ' Error No: ' . $e->getCode() . ', Message: ' . $e->getMessage();
                $log->error('Error while creating index ' . $indexName . ' in ' . $table . ', columns: ' . print_r($cols, true) . ', Message: ' . $errorMsg);
                $log->error('Query was: '.$query);
                return -1;
            }
			if(false === $result){
			    $errorMsg = $wpdb->last_error;
				$log->error('Error while creating index ' . $indexName . ' in ' . $table . ', columns: ' . print_r($cols, true) . ', Message: ' . $errorMsg);
                $log->error('Query was: '.$query);
				return -1;
			}	
			$log->info('Created index ' . $indexName . ' in ' . $table );
			return 1;
		}		
		$log->info('Index ' . $indexName . ' already in ' . $table );
		return 0;
	}

	function checkAndFixDBCollations($atInstallTime = true){
		$log = MstFactory::getLogger();
		$dbUtils = new MstDBUtils();
		global $wpdb;
		$res = array();

        $logTypeNr = ($atInstallTime ? MstConsts::LOGENTRY_INSTALLER : 0);

        // need to use $wpdb->base_prefix since for multisite we still have only one [prefix]_users core table
		$cEmailCollation = $dbUtils->getCollation($wpdb->base_prefix . 'users', 'user_email');
		$cNameCollation = $dbUtils->getCollation($wpdb->base_prefix . 'users', 'user_nicename');
        $cUserNameCollation = $dbUtils->getCollation($wpdb->base_prefix . 'users', 'display_name');
		$mEmailCollation = $dbUtils->getCollation($wpdb->prefix . 'mailster_users', 'email');
		$mNameCollation = $dbUtils->getCollation($wpdb->prefix . 'mailster_users', 'name');
        $mNotesCollation = $dbUtils->getCollation($wpdb->prefix . 'mailster_users', 'notes');

		$collationOk = ( ($cEmailCollation === $mEmailCollation) && ($cNameCollation === $mNameCollation)  && ($cUserNameCollation === $mNotesCollation) );

		if($collationOk){
			$log->info('checkAndFixDBCollations() No need to fix DB Collations, all good', $logTypeNr);
		}else{
			$log->error('checkAndFixDBCollations() DB Collation not okay, we have to fix it', $logTypeNr);
		}

		if($cEmailCollation !== $mEmailCollation){
			$log->error('checkAndFixDBCollations() Collation not okay: '.$wpdb->base_prefix.'users->email('.$cEmailCollation.') VS '.$wpdb->prefix.'mailster_users->email('.$mEmailCollation.')', $logTypeNr);
			$chgCollationRes = $dbUtils->alterCollation($wpdb->prefix . 'mailster_users', 'email', $cEmailCollation);
			if($chgCollationRes >= 0){
				$log->info('checkAndFixDBCollations() Changed '.$wpdb->prefix.'mailster_users->email collation to WP core collation: '.$cEmailCollation, $logTypeNr);
				$res[] = 1;
			}else{
				$log->error('checkAndFixDBCollations() Failed to change '.$wpdb->prefix.'mailster_users->email collation to WP core collation: '.$cEmailCollation, $logTypeNr);
				$res[] = -1;
			}
			$mEmailCollation = $dbUtils->getCollation($wpdb->prefix . 'mailster_users', 'email');
			$log->info('checkAndFixDBCollations() '.$wpdb->prefix.'mailster_users->email collation afterwards: '.$mEmailCollation, $logTypeNr);
		}else{
			$res[] = 0;
		}
		if($cNameCollation !== $mNameCollation){
			$log->error('checkAndFixDBCollations() Collation not okay: '.$wpdb->base_prefix.'users->name('.$cNameCollation.') VS '.$wpdb->prefix.'mailster_users->name('.$mNameCollation.')', $logTypeNr);
			$chgCollationRes = $dbUtils->alterCollation($wpdb->prefix . 'mailster_users', 'name', $cNameCollation);
			if($chgCollationRes >= 0){
				$log->info('checkAndFixDBCollations() Changed '.$wpdb->prefix.'mailster_users->name collation to WP core collation: '.$cNameCollation, $logTypeNr);
				$res[] = 1;
			}else{
				$log->error('checkAndFixDBCollations() Failed to change '.$wpdb->prefix.'mailster_users->name collation to WP core collation: '.$cNameCollation, $logTypeNr);
				$res[] = -1;
			}
			$mNameCollation = $dbUtils->getCollation($wpdb->prefix . 'mailster_users', 'name');
			$log->info('checkAndFixDBCollations() '.$wpdb->prefix.'mailster_users->name collation afterwards: '.$mNameCollation, $logTypeNr);
		}else{
			$res[] = 0;
		}
        if($cUserNameCollation !== $mNotesCollation){
            $log->error('checkAndFixDBCollations() Collation not okay: '.$wpdb->base_prefix.'users->display_name('.$cUserNameCollation.') VS '.$wpdb->prefix.'mailster_users->notes('.$mNotesCollation.')', $logTypeNr);
            $chgCollationRes = $dbUtils->alterCollation($wpdb->prefix . 'mailster_users', 'notes', $cUserNameCollation);
            if($chgCollationRes >= 0){
                $log->info('checkAndFixDBCollations() Changed '.$wpdb->prefix.'mailster_users->notes collation to WP core collation: '.$cUserNameCollation, $logTypeNr);
                $res[] = 1;
            }else{
                $log->error('checkAndFixDBCollations() Failed to change '.$wpdb->prefix.'mailster_users->notes collation to WP core collation: '.$cUserNameCollation, $logTypeNr);
                $res[] = -1;
            }
            $mNotesCollation = $dbUtils->getCollation($wpdb->prefix . 'mailster_users', 'notes');
            $log->info('checkAndFixDBCollations() '.$wpdb->prefix.'mailster_users->notes collation afterwards: '.$mNotesCollation, $logTypeNr);
        }else{
            $res[] = 0;
        }

		return $res;
	}

}
