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
//todo !!!
class MstDateUtils
{
    /**
     * Determine time zone from WordPress options and return as object.
     * Based on https://github.com/Rarst/wpdatetime
     * @return DateTimeZone
     */
    public static function getWpTimezone() {
        if(function_exists('wp_timezone')){
            return wp_timezone(); // since WP 5.3
        }

        $timezone_string = get_option( 'timezone_string' );
        if (!empty( $timezone_string)){
            return new DateTimeZone($timezone_string);
        }

        $offset  = (float) get_option( 'gmt_offset' );
        $hours   = (int) $offset;
        $minutes = ( $offset - $hours );

        $sign      = ( $offset < 0 ) ? '-' : '+';
        $abs_hour  = abs( $hours );
        $abs_mins  = abs( $minutes * 60 );
        $offset = sprintf( '%s%02d:%02d', $sign, $abs_hour, $abs_mins );
        return new DateTimeZone($offset);
    }
	
	public static function getCurrTimeDbFormat(){
		$dbDate = current_time("mysql");
		return $dbDate;
	}

    public static function getCurrTimeDbFormatInUTC(){
        $dbDate = current_time("mysql", 1);
        return $dbDate;
    }
	
	public static function getDate($dbDate){
		$timestamp = strtotime($dbDate);
		return $timestamp;
	}

	public static function formatDate($dbDate=null, $formatStr = null, $nullDateStr = '-'){	
		if(is_null($dbDate)){		
			$dbDate = self::getCurrTimeDbFormat();
		}
		if(is_null($formatStr)){
            $formatStr = '%Y-%m-%d %H:%M:%S';
        }
        $formatStr = self::strftime2date($formatStr);
        $timestamp = strtotime($dbDate);
        if($timestamp) {
        	return date_i18n($formatStr, $timestamp);
        }
        return $nullDateStr;
	}
	
	public static function formatDateAsConfigured($dbDate=null, $nullDateStr = '-'){
        $log = MstFactory::getLogger();
		if(is_null($dbDate)){
            $dbDateConverted = self::getCurrTimeDbFormat();
		}else {
            $tz = self::getWpTimezone();
            try {
                $dateTimeObj = new DateTime($dbDate, new DateTimeZone('UTC'));
                $dateTimeObj->setTimezone($tz);
                $dbDateConverted = $dateTimeObj->format('Y-m-d H:i:s');
                $log->debug('formatDateAsConfigured dbDate: ' . $dbDate . ' -> converted = ' . $dbDateConverted);
            } catch (Exception $e) {
                $log->error('formatDateAsConfigured exception converting dbDate: ' . $dbDate . ', error: '.$e->getMessage());
                return $nullDateStr;
            }
        }

		$mstConf = MstFactory::getConfig();
		$formatStr = $mstConf->getDateFormat();
		return self::formatDate($dbDateConverted, $formatStr, $nullDateStr);
	}
	
	public static function formatDateWithoutTimeAsConfigured($dbDate=null, $nullDateStr = '-'){
		if(is_null($dbDate)){		
			$dbDate = self::getCurrTimeDbFormat();
		}
		$mstConf = MstFactory::getConfig();
		$formatStr = $mstConf->getDateFormatWithoutTime();
		return self::formatDate($dbDate, $formatStr, $nullDateStr);
	}
	
	public static function getTimeAgo($dbDate, $nullDateStr = '', $dbDateNow = null){		
		$dbUtils = MstFactory::getDBUtils();
		$jDate = self::getDate($dbDate);
		if(!is_null($jDate)){
			if(is_null($dbDateNow)){
				$dbDateNow = time(); //$dbUtils->getDateTimeNow(); //todo
			}
			$jDateNow = $dbDateNow; //self::getDate($dbDateNow);				
			$diff =  $jDateNow- - $jDate;
			$timeArr = self::timeDiff2Arr($diff);
			return self::getTimeStr($timeArr, __("%s ago", 'wp-mailster') );
		}
		return $nullDateStr;
	}
	
	public static function getInTime($dbDate, $nullDateStr = '', $dbDateNow = null){		
		$dbUtils = MstFactory::getDBUtils();
		$jDate = self::getDate($dbDate);
		if(!is_null($jDate)){
			if(is_null($dbDateNow)){
				$dbDateNow = time(); //$dbUtils->getDateTimeNow(); //todo
			}
			$jDateNow = $dbDateNow; // self::getDate($dbDateNow); //todo
			$diff =  $jDate - $jDateNow;
			$timeArr = self::timeDiff2Arr($diff);
			return self::getTimeStr($timeArr, __("in %s", 'wp-mailster') );
		}
		return $nullDateStr;
	}
	
	private static function getTimeStr($timeArr, $stringPattern){
		$tInfo = 0;
		$tUnitStr = __("second", 'wp-mailster');
		
		if($timeArr['years'] > 0){
			$tInfo = $timeArr['years'];
			$tUnitStr = (($timeArr['years'] > 1) 	?  __("years", 'wp-mailster') 		:  __("year", 'wp-mailster') );
		}elseif($timeArr['days'] > 0){
			$tInfo = $timeArr['days'];
			$tUnitStr = (($timeArr['days'] > 1) 	?  __("days", 'wp-mailster') 		:  __("day", 'wp-mailster') );
		}elseif($timeArr['hours'] > 0){
			$tInfo = $timeArr['hours'];
			$tUnitStr = (($timeArr['hours'] > 1) 	? __("hours", 'wp-mailster') 		:  __("hour", 'wp-mailster') );
		}elseif($timeArr['mins'] > 0){
			$tInfo = $timeArr['mins'];
			$tUnitStr = (($timeArr['mins'] > 1) 	?  __("minutes", 'wp-mailster') 	:  __("minute", 'wp-mailster') );
		}elseif($timeArr['secs'] > 0){
			$tInfo = $timeArr['secs'];
			$tUnitStr = (($timeArr['secs'] > 1) 	?  __("seconds", 'wp-mailster') 	:  __("second", 'wp-mailster') );
		}
		
		$tStr = $tInfo . ' ' . $tUnitStr;
		return sprintf($stringPattern, $tStr);
	}
	
	private static function timeDiff2Arr($tSecs){
		$timeArr = array();
		
		$minsInSecs = 60;
		$hourInSecs = 60*$minsInSecs;
		$dayInSecs = 24*$hourInSecs;
		$yearInSecsSimplified = 365*$dayInSecs;
		
		if($tSecs > 0){
			$years 	= floor($tSecs/$yearInSecsSimplified); 
			$days 	= floor(($tSecs - ($years*$yearInSecsSimplified))/$dayInSecs);
			$hours 	= floor(($tSecs - ($years*$yearInSecsSimplified) - ($days*$dayInSecs))/$hourInSecs);
			$mins	= floor(($tSecs - ($years*$yearInSecsSimplified) - ($days*$dayInSecs) - ($hours * $hourInSecs))/$minsInSecs);
			$secs 	= ($tSecs - ($years*$yearInSecsSimplified) - ($days*$dayInSecs) - ($hours * $hourInSecs) - ($mins*$minsInSecs));
			
			$timeArr['years'] 	= $years;
			$timeArr['days']	= $days;
			$timeArr['hours']	= $hours;
			$timeArr['mins'] 	= $mins;
			$timeArr['secs']	= $secs;
		}else{
			$timeArr['years'] 	= 0;
			$timeArr['days']	= 0;
			$timeArr['hours']	= 0;
			$timeArr['mins'] 	= 0;
			$timeArr['secs']	= 0;
		}
		
		return $timeArr;
	}	
	
	public static function strftime2date($format){
		$dateAlphabet     = array('a',  'A',  'd',  'D',  'F',  'g',  'G',  'h',  'H',  'i',  'j',  'l',  'm',  'M',  'n',  'r',                   's',  'T',  'w',  'W', 'y',  'Y',  'z',  'm/d/Y', 'M',  "\n", 'g:i a', 'G:i', "\t", 'H:i:s', '%');
		$strftimeAlphabet = array('%p', '%p', '%d', '%a', '%B', '%I', '%H', '%I', '%H', '%M', '%e', '%A', '%m', '%b', '%m', '%a, %e %b %Y %T %Z', '%S', '%Z', '%w', '%V', '%y', '%Y', '%j', '%D',    '%h', '%n', '%r',    '%R',  '%t', '%T',    '%%');
		return str_replace($strftimeAlphabet, $dateAlphabet, $format);
	}
	
}
