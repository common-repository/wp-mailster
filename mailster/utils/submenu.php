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
	
	function subMenu($identifier)
	{
		$canDo = MstFactory::getAuthorization()->getActions();
		$identifier = strtolower($identifier);
		$mainMenue 		= false;
		$mailingLists 	= false;
        $servers     	= false;
		$users		 	= false;
		$digests	 	= false;
		$groups		 	= false;
		$mailArchive 	= false;
		$config		 	= false;
		$info			= false;

        $serverExisting = count(MstFactory::getServersModel()->getData()) > 0;
		
		//Create Submenu
		if($identifier == "" || $identifier == "mailster")
		{
			$mainMenue = true;
		}
		if($identifier == "mailinglists")
		{
			$mailingLists = true;
		}
        if($identifier == "servers")
        {
            $servers = true;
        }
		if($identifier == "users")
		{
			$users = true;
		}
		if($identifier == "digests")
		{
			$digests = true;
		}
		if($identifier == "groups")
		{
			$groups = true;
		}
		if($identifier == "groupusers")
		{
			$groups = true;
		}
		if($identifier == "listmembers")
		{
			$mailingLists = true;
		}
		if($identifier == "mails")
		{
			$mailArchive = true;
		}
		if($identifier == "config")
		{
			$config = true;
		}
		if($identifier == "info")
		{
			$info = true;
		}
		if($identifier == "csv")
		{
			
		}
		if($identifier == "diagnosis")
		{
			$info = true;
		}
		if($identifier == "resend")
		{
			
		}
		if($identifier == "mailqueue")
		{
			
		}
		if($identifier == "log")
		{
			$info = true;	
		}
	}

