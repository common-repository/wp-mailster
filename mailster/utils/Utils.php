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

class MstUtils
{
	public static function addTabs(){
		require_once('tabs.php');
	} 
	
	public static function addTable(){
		require_once('table.php');
	}
	
	public static function addTips(){
		require_once('tips.php');
	}
	
	public static function insertPairlist($pairListId, $submitTask, $selectArray, $listStrings){
		require_once('pairlist.php');
		pairList($pairListId, $submitTask, $selectArray, $listStrings);		
	}
		
	public static function jsonDecode($jsonStr){
		require_once('JSON.php');
 		$json = new MstJSON();
 		return $json->decode($jsonStr);
	}
	
	public static function jsonEncode($varObj){
		require_once('JSON.php');
 		$json = new MstJSON();
 		return $json->encode($varObj);
 		
	}		
	
	public static function getCaptcha($cType){
		require_once('Captcha.php');
		return new MstCaptcha($cType);
	}
}

