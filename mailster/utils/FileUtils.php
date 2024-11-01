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

class MstFileUtils
{
	
	public static function isDirWritable($dir2check, $logResults=false, $level='WARNING'){
		$log = MstFactory::getLogger();
		$lastChar = substr($dir2check, -1);
		if($lastChar !== '/' && $lastChar !== '\\'){
			$dir2check .= "/";
		}
	
		if(!file_exists($dir2check)){
			if($logResults){
				$log->log('Dir ' . $dir2check . ' NOT existing!', $level);
			}
			return false;  // Directory not existing
		}
		if(!is_writable($dir2check)){
			if($logResults){
				$log->log('Dir ' . $dir2check . ' NOT writable!', $level);
			}
			return false;  // Directory not writable
		}
	
		if($logResults){
			$log->log('Dir ' . $dir2check . ' writable');
		}
		return true; // Directory writable
	}
	
	public static function isFileWritable($targetDir, $completeFilepath, $logResults=false, $level='WARNING'){
		$log = MstFactory::getLogger();
		$ok = self::isDirWritable($targetDir,$logResults,$level);
	
		if($ok){ // dir writable
			if(file_exists($completeFilepath) && !is_writable($completeFilepath)){
				if($logResults){
					$log->log('File ' . $completeFilepath . ' existing, but not writable', $level);
				}
				return false;  // File exists but is not writable
			}
			return true; // File writable
		}else{
			return false; // Dir not writable, therefore file not writable
		}
	}
	
	public static function fileWritable($fileName, $folderPath, $takeFileNameAsPath=false){
        if($takeFileNameAsPath){
            $folderPath = $fileName; // cannot check it
            $filePath = $fileName;
        }else{
            $folderPath = rtrim($folderPath, '/') . '/'; // make sure there is one trailing slash
            $filePath = $folderPath . $fileName;
        }
		if(!file_exists($folderPath)){
			return false;  // Log folder path wrong configured
		}
		if(!is_writable($folderPath)){
			return false;  // Directory not writable
		}
		if(file_exists($filePath) && !is_writable($filePath)){
			return false;  // Log file exists but is not writable
		}
		return true; // Directory writable, file may exist or not
	}
	
	public static function getFileSizeStringForSizeInBytes($sizeBytes){		
		if ($sizeBytes < 1024) {
			return $sizeBytes . ' ' . __( 'B', 'wp-mailster' );
		} elseif ($sizeBytes < 1048576) {
			return round($sizeBytes / 1024, 2) . ' ' . __( 'KB', 'wp-mailster' );
		} elseif ($sizeBytes < 1073741824) {
			return round($sizeBytes / 1048576, 2) . ' ' . __( 'MB', 'wp-mailster' );
		} elseif ($sizeBytes < 1099511627776) {
			return round($sizeBytes / 1073741824, 2) . ' ' . __( 'GB', 'wp-mailster' );
		} else {
			return round($sizeBytes / 1099511627776, 2) . ' ' . __( 'TB', 'wp-mailster' );
		}
	}
	
	public static function getFileSizeOfFile($filePath){
        $fileSize = file_exists($filePath) ? filesize($filePath) : 0;
        return self::getFileSizeStringForSizeInBytes($fileSize);
	}
	
}
