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
	class MstVersionMgmt
	{
        const MST_PT_NAME = 'WP Mailster Free';
        const MST_PT_DATE = 'October 2024';

        const MST_PT_TOP = '1';
        const MST_PT_SUB = '8';
        const MST_PT_FIX = '16';
        const MST_PT_BET = '-';
        const MST_PT_BLD = '266';

		const MST_FT_REC_OCT    = '62';
        const MST_FT_MLT_OCT    = '3';
        const MST_FT_CAPTCHA    = 'yeah';
        const MST_FT_D_FOOTER   = 'yeah';
        const MST_FT_DBL_OPT    = 'yeah';
        const MST_FT_FILTER     = 'yeah';
        const MST_FT_NOTIFY     = 'yeah';
        const MST_FT_THROTTLE   = 'yeah';
        const MST_FT_FARCHIVE   = 'yeah';
        const MST_FT_CB_INT     = 'yeah';
        const MST_FT_DD_CRON    = 'yeah';
        const MST_FT_MODERATE   = 'yeah';
        const MST_FT_DIGEST     = 'yeah';
        const MST_FT_D_ARCHIVE  = 'yeah';
        const MST_FT_EARCHIVE   = 'yeah';
        const MST_FT_REG_PLG    = 'yeah';

        const MST_UD_OK = 'true';
        const MST_UD_VERSION = 'Mailster Club';
        const MST_UD_OPT_1 = 'WP Mailster Club';
        const MST_UD_OPT_2 = 'WP Mailster Society';
        const MST_UD_OPT_3 = 'WP Mailster Enterprise';
        const MST_UD_LINK = 'https://wpmailster.com/product/wp-mailster-club?utm_source=wpmst&utm_medium=upgrade&utm_campaign=intro';
        
        const MST_FT_ID_REC = 'MAX_REC';
        const MST_FT_ID_MLT = 'MAX_MLT';
        const MST_FT_ID_CAPTCHA = 'CAPTCHA';
        const MST_FT_ID_D_FOOTER = 'D_FOOTER';
        const MST_FT_ID_DBL_OPT  = 'DBL_OPT';
        const MST_FT_ID_FILTER   = 'FILTER';
        const MST_FT_ID_NOTIFY   = 'NOTIFY';
        const MST_FT_ID_THROTTLE = 'THROTTLE';
        const MST_FT_ID_FARCHIVE = 'FARCHIVE';
        const MST_FT_ID_CB_INT   = 'CB_INT';
        const MST_FT_ID_DD_CRON  = 'DD_CRON';
        const MST_FT_ID_MODERATE = 'MODERATE';
        const MST_FT_ID_DIGEST   = 'DIGEST';
        const MST_FT_ID_D_ARCHIVE = 'D_ARCHIVE';
        const MST_FT_ID_EARCHIVE = 'EARCHIVE';
        const MST_FT_ID_REG_PLG= 'REG_PLG';

        /**
         * @return array
         */
        public static function getFtMap(){
            $ftMap = array();
            $ftMap[self::MST_FT_ID_REC]      = octdec(self::MST_FT_REC_OCT);
            $ftMap[self::MST_FT_ID_MLT]      = octdec(self::MST_FT_MLT_OCT);
            $ftMap[self::MST_FT_ID_CAPTCHA]  = (self::MST_FT_CAPTCHA === 'true');
            $ftMap[self::MST_FT_ID_D_FOOTER] = (self::MST_FT_D_FOOTER === 'true');
            $ftMap[self::MST_FT_ID_DBL_OPT]  = (self::MST_FT_DBL_OPT === 'true');
            $ftMap[self::MST_FT_ID_FILTER]   = (self::MST_FT_FILTER === 'true');
            $ftMap[self::MST_FT_ID_NOTIFY]   = (self::MST_FT_NOTIFY === 'true');
            $ftMap[self::MST_FT_ID_THROTTLE] = (self::MST_FT_THROTTLE === 'true');
            $ftMap[self::MST_FT_ID_FARCHIVE] = (self::MST_FT_FARCHIVE === 'true');
            $ftMap[self::MST_FT_ID_CB_INT]   = (self::MST_FT_CB_INT === 'true');
            $ftMap[self::MST_FT_ID_DD_CRON]  = (self::MST_FT_DD_CRON === 'true');
            $ftMap[self::MST_FT_ID_MODERATE]  = (self::MST_FT_MODERATE === 'true');
            $ftMap[self::MST_FT_ID_DIGEST]   = (self::MST_FT_DIGEST === 'true');
            $ftMap[self::MST_FT_ID_D_ARCHIVE] = (self::MST_FT_D_ARCHIVE === 'true');
            $ftMap[self::MST_FT_ID_EARCHIVE] = (self::MST_FT_EARCHIVE === 'true');
            $ftMap[self::MST_FT_ID_REG_PLG] = (self::MST_FT_REG_PLG === 'true');
            return $ftMap;
        }
        
        public static function getMinV4Ft($ft){
            $ftMap[self::MST_FT_ID_REC]      = null;
            $ftMap[self::MST_FT_ID_MLT]      = null;
            $ftMap[self::MST_FT_ID_CAPTCHA]  = self::MST_UD_OPT_1;
            $ftMap[self::MST_FT_ID_D_FOOTER] = self::MST_UD_OPT_1;
            $ftMap[self::MST_FT_ID_DBL_OPT]  = self::MST_UD_OPT_1;
            $ftMap[self::MST_FT_ID_FILTER]   = self::MST_UD_OPT_1;
            $ftMap[self::MST_FT_ID_NOTIFY]   = self::MST_UD_OPT_1;
            $ftMap[self::MST_FT_ID_THROTTLE] = self::MST_UD_OPT_1;
            $ftMap[self::MST_FT_ID_FARCHIVE] = self::MST_UD_OPT_2;
            $ftMap[self::MST_FT_ID_CB_INT]   = self::MST_UD_OPT_2;
            $ftMap[self::MST_FT_ID_DD_CRON]  = self::MST_UD_OPT_2;
            $ftMap[self::MST_FT_ID_MODERATE] = self::MST_UD_OPT_2;
            $ftMap[self::MST_FT_ID_DIGEST]   = self::MST_UD_OPT_2;
            $ftMap[self::MST_FT_ID_D_ARCHIVE] = self::MST_UD_OPT_1;
            $ftMap[self::MST_FT_ID_EARCHIVE] = self::MST_UD_OPT_3;
            $ftMap[self::MST_FT_ID_REG_PLG] = self::MST_UD_OPT_1;
            return $ftMap[$ft];
        }

        /**
         * @param $ft
         * @return mixed
         */
        public static function getFtSetting($ft){
            $ftMap = self::getFtMap();
            if(array_key_exists($ft, $ftMap)){
                return $ftMap[$ft];
            }
            return null;
        }

        /**
         * @param bool $includeVersion
         * @param bool $includeBuild
         * @return string
         */
        public static function getProductName($includeVersion=false, $includeBuild=true){
            if($includeVersion){
                return (self::MST_PT_NAME.' '.self::getProductVersion($includeBuild));
            }
            return self::MST_PT_NAME;
        }

        /**
         * @return string
         */
        public static function getProductDate(){
            return self::MST_PT_DATE;
        }

        /**
         * @param bool $includeBuild
         * @param bool $includeBeta
         * @return string
         */
        public static function getProductVersion($includeBuild=false, $includeBeta=true){
            $version = self::MST_PT_TOP.'.'.self::MST_PT_SUB.'.'.self::MST_PT_FIX;
            if($includeBeta && self::MST_PT_BET && strlen(self::MST_PT_BET)>0 && self::MST_PT_BET !== '-'){
                $version .= ' - '.self::MST_PT_BET;
            }
            if($includeBuild){
                return ($version.' BUILD '.self::MST_PT_BLD);
            }
            return $version;
        }

        /**
         * @return bool
         */
        public static function upgradePossible(){
            return (self::MST_UD_OK === 'true');
        }

        /**
         * @return string
         */
        public static function getUpgradeLink(){
            return self::MST_UD_LINK;
        }

        /**
         * @return string
         */
        public static function getUpgradeOption(){
            return self::MST_UD_VERSION;
        }
		
	}