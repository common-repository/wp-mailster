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

class MstPluginUtils
{
    public static function resetMailPluginTimes(){

        $minSendTime 		= get_option('wpmst_cfg_minsendtime', 60);
        $minCheckTime 		= get_option('wpmst_cfg_minchecktime', 240);
        $minMaintenanceTime = get_option('wpmst_cfg_minmaintenance', 3600);

        if($minSendTime < 10){
            if(class_exists('MstFactory')){
                MstFactory::getLogger()->warning('resetMailPluginTimes minSendTime is: '.$minSendTime.', reset to 60');
            }
            $minSendTime = 60;
        }
        if($minCheckTime < 10){
            if(class_exists('MstFactory')){
                MstFactory::getLogger()->warning('resetMailPluginTimes minChecktime is: '.$minCheckTime.', reset to 240');
            }
            $minCheckTime = 240;
        }
        if($minMaintenanceTime < 60){
            if(class_exists('MstFactory')){
                MstFactory::getLogger()->warning('resetMailPluginTimes minMaintenanceTime is: '.$minMaintenanceTime.', reset to 3600');
            }
            $minMaintenanceTime = 3600;
        }

        $tNow = time();
        if($tNow > 0){
            $lastExecRetrieve = $tNow - $minCheckTime;
            $lastExecSending = $tNow - $minSendTime;
            $lastExecMaintenance = $tNow - $minMaintenanceTime;
            update_option('wpmst_cfg_last_exec_retrieve', $lastExecRetrieve);
            update_option('wpmst_cfg_last_exec_sending', $lastExecSending);
            update_option('wpmst_cfg_last_exec_maintenance', $lastExecMaintenance);
            return true;
        }else{
            MstFactory::getLogger()->warning('resetMailPluginTimes negative time tNow!');
            return false;
        }
    }

    public static function getNextMailCheckTime(){
        $minCheckTime = intval(get_option('wpmst_cfg_minchecktime', 240));
        if($minCheckTime < 10){
            if(class_exists('MstFactory')){
                MstFactory::getLogger()->warning('getNextMailCheckTime minChecktime is: '.$minCheckTime.', reset to 240');
            }
            $minCheckTime = 240;
        }
        $lastCheckTime = intval(get_option('wpmst_cfg_last_exec_retrieve', 0));
        return $lastCheckTime + $minCheckTime;
    }

    public static function getNextMailSendTime(){
        $minSendTime = intval(get_option('wpmst_cfg_minsendtime', 60));
        if($minSendTime < 10){
            if(class_exists('MstFactory')){
                MstFactory::getLogger()->warning('getNextMailSendTime minSendTime is: '.$minSendTime.', reset to 60');
            }
            $minSendTime = 60;
        }
        $lastSendTime =	intval(get_option('wpmst_cfg_last_exec_sending', 0));
        return $lastSendTime + $minSendTime;
    }

    public static function getNextMaintenanceTime(){
        $minMaintenanceTime = intval(get_option('wpmst_cfg_minmaintenance', 3600));
        if($minMaintenanceTime < 60){
            if(class_exists('MstFactory')){
                MstFactory::getLogger()->warning('getNextMaintenanceTime minMaintenanceTime is: '.$minMaintenanceTime.', reset to 3600');
            }
            $minMaintenanceTime = 3600;
        }
        $lastMaintenanceTime = intval(get_option('wpmst_cfg_last_exec_maintenance', 0));
        return $lastMaintenanceTime + $minMaintenanceTime;
    }

}
