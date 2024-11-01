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

class MstEnvironment
{
	public static function imapExtensionInstalled(){
		return (extension_loaded('imap') && function_exists('imap_open'));
	}
	
	public static function domExtensionInstalled(){
		return extension_loaded('dom');
	}
	
	public static function openSSLExtensionInstalled(){
		return extension_loaded('openssl');
	}

    public static function charsetConversionFunctionAvailable(){
        return (function_exists('iconv') || function_exists('mb_convert_encoding')); // one of the two is required
    }

	public static function getImapVersion(){
		$version = 'UNKNOWN';
		if(MstEnvironment::imapExtensionInstalled()){
			$phpInfo = MstEnvironment::getPHPInfoArray();
            if($phpInfo && array_key_exists('imap', $phpInfo)){
                $imapInfo = $phpInfo['imap'];
                if(is_array($imapInfo)){
                    $version = print_r($imapInfo, true);
                }else{
                    $version = $imapInfo;
                }
            }
		}
		return $version;
	}
	
	public static function getOpenSSLVersion(){
        $version = 'UNKNOWN';
		if(MstEnvironment::openSSLExtensionInstalled()){
			$phpInfo = MstEnvironment::getPHPInfoArray();
            if($phpInfo && array_key_exists('openssl', $phpInfo)){
                $openSSLInfo = $phpInfo['openssl'];
                if(is_array($openSSLInfo)){
                    $version = print_r($openSSLInfo, true);
                }else{
                    $version = $openSSLInfo;
                }
            }
        }
		return $version;
		
	}

	public static function getPHPInfoArray(){
        if(!function_exists('phpinfo')){
            return null;
        }
		ob_start();
		phpinfo(-1);		 
		$pi = preg_replace(
		array('#^.*<body>(.*)</body>.*$#ms', '#<h2>PHP License</h2>.*$#ms',
			'#<h1>Configuration</h1>#',  "#\r?\n#", "#</(h1|h2|h3|tr)>#", '# +<#',
			"#[ \t]+#", '#&nbsp;#', '#  +#', '# class=".*?"#', '%&#039;%',
			'#<tr>(?:.*?)" src="(?:.*?)=(.*?)" alt="PHP Logo" /></a>'
			.'<h1>PHP Version (.*?)</h1>(?:\n+?)</td></tr>#',
			'#<h1><a href="(?:.*?)\?=(.*?)">PHP Credits</a></h1>#',
			'#<tr>(?:.*?)" src="(?:.*?)=(.*?)"(?:.*?)Zend Engine (.*?),(?:.*?)</tr>#',
			"# +#", '#<tr>#', '#</tr>#'),
			array('$1', '', '', '', '</$1>' . "\n", '<', ' ', ' ', ' ', '', ' ',
			'<h2>PHP Configuration</h2>'."\n".'<tr><td>PHP Version</td><td>$2</td></tr>'.
			"\n".'<tr><td>PHP Egg</td><td>$1</td></tr>',
			'<tr><td>PHP Credits Egg</td><td>$1</td></tr>',
			'<tr><td>Zend Engine</td><td>$2</td></tr>' . "\n" .
			'<tr><td>Zend Egg</td><td>$1</td></tr>', ' ', '%S%', '%E%'),
			ob_get_clean());
		$sections = explode('<h2>', strip_tags($pi, '<h2><th><td>'));
		unset($sections[0]);
		$pi = array();
		foreach($sections as $section){
			$n = substr($section, 0, strpos($section, '</h2>'));
			preg_match_all(
			'#%S%(?:<td>(.*?)</td>)?(?:<td>(.*?)</td>)?(?:<td>(.*?)</td>)?%E%#',
			$section, $askapache, PREG_SET_ORDER);
			foreach($askapache as $m){
                $pVal = '';
                if(isset($m[2])){
                    if(!isset($m[3]) || $m[2]==$m[3]){
                        $pVal = $m[2];
                    }else{
                        $pVal = array_slice($m,2);
                    }
                    $pi[$n][$m[1]]= $pVal;
                }
            }
		}
		return $pi;
	}


}
