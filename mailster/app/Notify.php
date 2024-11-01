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
	
	class MstNotify
	{
		const NOTIFY_TYPE_GENERAL = 0;
		const NOTIFY_TYPE_LIST_BASED = 1;
		
		const TARGET_TYPE_LIST_ADMIN = 0;
		const TARGET_TYPE_CORE_USER = 1;
		const TARGET_TYPE_USER_GROUP = 2;
		
		var $id = 0;
		var $notify_type = null;
		var $trigger_type = null;
		var $target_type = null;
		var $list_id = null;
		var $group_id = null;
		var $user_id = null;
		
		public function setTargetId($targetId = -1, $targetType = null){
			if(!is_null($targetType)){
				$this->target_type = $targetType;
			}
			switch($this->target_type){
				case self::TARGET_TYPE_LIST_ADMIN:
					$this->user_id = 0;
					$this->group_id = 0;
					break;
				case self::TARGET_TYPE_CORE_USER:
					$this->user_id = $targetId;
					$this->group_id = 0;
					break;
				case self::TARGET_TYPE_USER_GROUP:
					$this->user_id = 0;
					$this->group_id = $targetId;
					break;
			}
		}
		
		
	}

