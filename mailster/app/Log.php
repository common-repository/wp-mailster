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
    die('These droids are not the droids you are looking for.');
}

	class MstLog
	{
		
		const DEBUG = 	'DEBU';
		const INFO = 	'INFO';
		const WARNING = 'WARN';
		const ERROR = 	'ERRO';
		
	    public static function log($msg, $level='INFO', $typeNr=0)
	    {
            $isHeartbeatRequest = false;
            $isRestUrlPath = false;
            if(array_key_exists('action', $_POST)){
                $isHeartbeatRequest = ($_POST['action'] === 'heartbeat');
            }
            if(array_key_exists('REQUEST_URI', $_SERVER)) {
                $restUrlPath = trim(parse_url(home_url('/wp-json/'), PHP_URL_PATH), '/');
                $requestUrl = trim($_SERVER['REQUEST_URI'], '/');
                $isRestUrlPath = (strpos($requestUrl, $restUrlPath) === 0);
            }
            if($isHeartbeatRequest && ($level == self::DEBUG)){
                return true; // do not log anything DEBUG during heartbeat requests
            }

            $isInstallEntry = ($typeNr == MstConsts::LOGENTRY_INSTALLER);
            if( self::loggingLevelSufficient( $level ) || $isInstallEntry ) { // during install don't do other tests...

                $level = strtoupper($level);
                if(is_array($msg)){
                    $msg = print_r($msg, true);
                }
                if(is_object($msg)){
                    $msg = serialize($msg);
                }

                $requestId = sprintf("%08x", abs(crc32($_SERVER['REMOTE_ADDR'] . $_SERVER['REQUEST_TIME'] . $_SERVER['REMOTE_PORT'])));

                $loggingPossible = self::loggingPossible();
                $loggingForced = self::isLoggingForced();

                if(!$loggingPossible) {
                    try {
                        self::initFile();
                    } catch (Exception $e) {
                        return false;
                    }
                    $loggingPossible = self::loggingPossible();
                }

                if( $loggingPossible || $loggingForced ){
                    $logFileName = self::getLogFile();
                    $logFile = fopen($logFileName, "a");
                    if($logFile){
                        $msg = date("Y-m-d H:i:s") . ' ' . $requestId . ' ' . $level . ' ' . $msg;
                        if(fwrite($logFile, $msg . "\n") !== false){ //write to the file
                            return true;
                        }
                    }
                }

            }
            return false;
	    }
	    
	    public static function warning($msg, $typeNr=0)
	    {
	        return self::log($msg, self::WARNING, $typeNr);
	    }
	    public static function error($msg, $typeNr=0)
	    {
	        return self::log($msg, self::ERROR, $typeNr);
	    }
	    public static function debug($msg, $typeNr=0)
	    {
	        return self::log($msg, self::DEBUG, $typeNr);
	    }
	    public static function info($msg, $typeNr=0)
	    {
	        return self::log($msg, self::INFO, $typeNr);
	    }
	    
	    public static function getLoggingLevel($entryType){
	    	switch($entryType){
	    		case self::ERROR:
	    			return MstConsts::LOG_LEVEL_ERROR;
	    		case self::WARNING:
	    			return MstConsts::LOG_LEVEL_WARNING;
	    		case self::INFO:
	    			return MstConsts::LOG_LEVEL_INFO;
	    		case self::DEBUG:
	    			return MstConsts::LOG_LEVEL_DEBUG;
	    	}
	    	return 0;
	    }

	    public static function getLoggingLevelStr($entryTypeNr){
	    	switch($entryTypeNr){
	    		case MstConsts::LOG_LEVEL_ERROR:
	    			return __('Error', 'wp-mailster' );
	    		case MstConsts::LOG_LEVEL_WARNING:
	    			return __('Warning', 'wp-mailster' );
	    		case MstConsts::LOG_LEVEL_INFO:
	    			return __('Info', 'wp-mailster' );
	    		case MstConsts::LOG_LEVEL_DEBUG:
	    			return __('Debug', 'wp-mailster' );
	    	}
	    	return 0;
	    }
	    
	    public static function getLoggingTypeStr($typeNr){
	    	switch($typeNr){
	    		case MstConsts::LOGENTRY_INSTALLER:
	    			return __('Installer', 'wp-mailster' );
	    		case MstConsts::LOGENTRY_PLUGIN:
	    			return __('Plugin', 'wp-mailster' );
	    		case MstConsts::LOGENTRY_MAIL_RETRIEVE:
	    			return __('Mail Retrieving', 'wp-mailster' );
	    		case MstConsts::LOGENTRY_MAIL_SEND:
	    			return __('Mail Sending', 'wp-mailster' );
	    	}
	    	return "";
	    }
	 
	    public static function loggingLevelSufficient($entryType){
	    	$loggingLevel = self::getCurrentLoggingLevel();
	    	if($loggingLevel > 0){ // when Logging Level is zero then nothing will be logged
		    	$entryLevel = self::getLoggingLevel($entryType); // get Level that this entry needs
	            if($entryLevel <= $loggingLevel){ 
	            	return true; // entry can be logged
	            }
	    	}
            return false; // entry may not be logged
		}
		
		public static function isLoggingActive(){
			return (self::getCurrentLoggingLevel() > 0);
		}
		
		public static function getCurrentLoggingLevel(){
			$mstConf = MstFactory::getConfig();
			return $mstConf->getLoggingLevel(); // get current Logging Level			
		}
		
		public static function getCurrentLoggingLevelString(){
			$loggingLevel = self::getCurrentLoggingLevel();
			return self::getLoggingLevelStr($loggingLevel);
		}
		
		public static function getLogFileFolder(){
            $uploadDir = wp_upload_dir();
            $uploadDir = $uploadDir['basedir'];
            return $uploadDir.'/wp-mailster/';
		}
		
		public static function getLogFile($logFileName = 'wpmst.log.php'){
			$logPath = self::getLogFileFolder();
			$logFile = $logPath . $logFileName;
			return $logFile;
		}

	    public static function loggingPossible($logFileName = 'wpmst.log.php', $addPath=true){
	    	$fileUtils = MstFactory::getFileUtils();
            if($addPath){
                $logPath = self::getLogFileFolder();
                $filePath = $logPath . $logFileName;
                $takeFileNameAsPath = false;
            }else{
                $filePath = $logFileName;
                $logPath = null;
                $takeFileNameAsPath = true;
            }
            
			return $fileUtils->fileWritable($logFileName, $logPath, $takeFileNameAsPath);
		}

		public static function testLoggingWorked(){
			// do this with the highest level so that it goes through if logging is active and possible at all
	    	return self::error(('testLoggingWorked -> '.rand(1, 100)));
		}
	    
	    public static function isLoggingForced(){
			$mstConf = MstFactory::getConfig();
			return $mstConf->isLoggingForced();
	    }
	    
	    public static function isLogFile2Big($fileLimitInByte, $logFileName = 'wpmst.log.php'){
	    	if(self::loggingPossible($logFileName)){ // log writable?
	    		$logFile = self::getLogFile($logFileName);
	    		if(!is_file($logFile)){ // log file existing?
	    			return false; // not existing, cannot be to big...
	    		}
	    		$lSize = filesize($logFile);
	    		return ($lSize > $fileLimitInByte);
	    	}
	    }

		protected static function generateFileHeader()
		{
			$head = array();
			// Build the log file header.
			// If the no php flag is not set add the php die statement.
			if ( !file_exists(self::getLogFile()) )
			{
				// Blank line to prevent information disclose: https://bugs.php.net/bug.php?id=60677
				$head[] = '#';
				$head[] = '<?php die(\'Forbidden.\'); ?>';
			}

			$head[] = '#Date: ' . date('Y-m-d H:i:s') . ' UTC';
			$head[] = '';

			return implode("\n", $head);
		}

        /**
         * @throws RuntimeException
         */
        public static function initFile(){
            $logsDirectory = rtrim(self::getLogFileFolder(),'/');
            if (!is_dir($logsDirectory)){ // Make sure log directory exists
                mkdir($logsDirectory, 0755, true);
            }

			$logFile = self::getLogFile();
			if(file_exists($logFile)){ // We only need to make sure the file exists
				return;
			}

			// Build the log file header.
			$head = self::generateFileHeader();

            if (!file_exists($logFile)){
                $logFileHandler = fopen($logFile, "w");
                if($logFileHandler){
					if (fwrite($logFileHandler, $head . "\n") === false) {
						throw new RuntimeException('Cannot write to log file ' . $logFile);
					}
					fclose($logFileHandler);
				}
            }
		}
	    
	}

