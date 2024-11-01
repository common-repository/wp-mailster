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

class MstConverterUtils{

    public static function sanitizePasswordField($str){
        $filtered = $str;
        $filtered = preg_replace('/[\r\n\t ]+/', ' ', $filtered); // remove new lines
        $filtered = trim($filtered); // trim leading/trailing whitespaces
        // remove everything besides alphanumerical and special characters @!§$%&/~*#.:,_ and spaces
        $filtered = preg_replace("/[^A-Za-z0-9@!?§$%&\\/\\~\\:\\*\\-\\.\\#\\+,_ ]+/i", '', $filtered);
        MstFactory::getLogger()->debug('sanitizePasswordField From '.$str.' to: '.$filtered);
        return $filtered;
    }

	public static function object2Array($obj2conv){
		$array=array();
		if(!is_scalar($obj2conv) && $obj2conv) {
            foreach($obj2conv as $id => $object) {
                if(is_scalar($object)) {
                    $array[$id]=$object;
                } else {
                    $array[$id]=MstConverterUtils::object2Array($object);
                }
            }
            return $array;
		} else {
			return $obj2conv;
		}
	}

	public static function array2Object($arr){
		if (is_array($arr)) {
			$obj = new stdClass();
			foreach ($arr as $key => $val){
				$obj->$key = $val;
			}
		}else{ 
			$obj = $arr;
		}
		return $obj;
	}


	public static function imapUtf8($str){
		$log = MstFactory::getLogger();
		$testStr = 'h';
		$res = imap_utf8($testStr); // TEST for uppercase bug...
		if($res !== 'h'){  // If it is converted to uppercase we got a PHP version with the bug
			$log->warning('PHP version WITH imap_utf8 bug, use custom conversion');
			$ret = '';
			$headerParts = imap_mime_header_decode($str);
			for($i=0;$i<count($headerParts);$i++){
				if ($headerParts[$i]->charset == 'default')	{
					$headerParts[$i]->charset = 'ISO-8859-1'; // ISO-8859-1
				}
                $ret .= self::iconv_Wrapper($headerParts[$i]->charset, 'UTF-8//TRANSLIT', $headerParts[$i]->text);
			}
		}else{ // Test went ok, we can use the normal method
			$ret = imap_utf8($str);
		}
		return $ret;
	}

	public static function getStringAsNativeUtf8($str){
		$log = MstFactory::getLogger();
		$log->debug('getStringAsNativeUtf8 called for: ' . $str);
		$convStr = '';
		$subLines = preg_split('/[\r\n]+/',$str); // split multi-line subjects
		$log->debug('subLines: ' . print_r($subLines, true));
		for($i=0; $i < count($subLines); $i++){ // go through lines
			$convLine = '';
			$linePartArr = imap_mime_header_decode(trim($subLines[$i])); // split and decode by charset
			$log->debug('linePart Arr ' . $i . ': ' . print_r($linePartArr, true));
			for($j=0; $j < count($linePartArr); $j++){
				if ($linePartArr[$j]->charset === 'default'){
					//$log->debug('Do not replace default charset for linePartArr entry No '.$j);
					$convLine .= ($linePartArr[$j]->text);
				}else{
                    $convLine .= self::iconv_Wrapper($linePartArr[$j]->charset, 'UTF-8//TRANSLIT', $linePartArr[$j]->text);
				}				
				$log->debug('appended convLine ' . $j . ': ' . $convLine);
			}
			$convStr .= $convLine; // append to whole subject
			$log->debug('appended convStr ' . $i . ': ' . $convStr);
		}
		$log->debug('getStringAsNativeUtf8 result: ' . $convStr);
		return $convStr; // return converted subject
	}

	public static function decode_qprint($str) {
		$str = preg_replace("/\=([A-F][A-F0-9])/","%$1",$str);
		$str = urldecode($str);
		$str = utf8_encode($str);
		return $str;
	}

    public static function iconv_Wrapper($inCharset, $outCharset, $str){
        $log = MstFactory::getLogger();
        if(function_exists('iconv')){
            $log->debug('iconv_Wrapper -> iconv -> '.$inCharset.' TO '.$outCharset);
            return iconv($inCharset, $outCharset, $str);
        }
        // no iconv function available, trouble, try it with mb_convert_encoding (http://php.net/manual/function.mb-convert-encoding.php)
        if(function_exists('mb_convert_encoding')){
            $log->warning('iconv_Wrapper No iconv function, take mb_convert_encoding');
            $log->debug('iconv_Wrapper -> mb_convert_encoding -> '.$inCharset.' TO '.$outCharset);
            $outCharset = str_replace('//TRANSLIT', '', $outCharset); // mb_convert_encoding does not support translit functionality (that iconv does)
            $log->debug('Removal of TRANSLIT yielded: '.$outCharset);
            if(strlen($inCharset) < 1){
                $log->debug('Put NULL as inCharset');
                $inCharset = null;
            }
            return mb_convert_encoding($str, $outCharset, $inCharset);
        }
        // no iconv function available, trouble, log it and return original string
        $log->error('iconv_Wrapper No iconv and mb_convert_encoding available!');
        return $str;
    }

	public static function encodeText($text, $encoding, $charset){
		$log = MstFactory::getLogger();
		$charset = strtoupper($charset);
		$log->debug('encodeText: Encoding: ' . $encoding . ', Charset: ' . $charset);
		/*
		 Encoding
			0	7BIT
			1	8BIT
			2	BINARY
			3	BASE64
			4	QUOTED-PRINTABLE
			5	OTHER
		 */
		
		if ($encoding == 0) {
            $log->debug('encodeText: Encoding 7-bit '.$charset.' text with iconv-Wrapper');
            $tmpText = self::iconv_Wrapper($charset, "UTF-8//TRANSLIT", $text);
            $log->debug('encodeText: Result of iconv-Wrapper conversion: '.$tmpText);
            if(strlen(trim($tmpText))==0 && strlen(trim($text)) > 0){
                $log->warning('encodeText: Result of iconv-Wrapper conversion did remove message content');
                if(function_exists('mb_convert_encoding')) {
                    $log->warning('Try with mb_convert_encoding...');
                    ini_set('mbstring.substitute_character', "none");
                    $tmpText= mb_convert_encoding($text, 'UTF-8', 'UTF-8');
                    $log->warning('mb_convert_encoding result: '.$tmpText);
                    if(strlen(trim($tmpText))>0){
                        $tmpText = self::iconv_Wrapper($charset, "UTF-8//TRANSLIT", $tmpText);
                        $log->warning('result after new iconv-Wrapper try: '.$tmpText);
                        if(strlen(trim($tmpText))==0){
                            $log->warning('Result still empty, revert to original text (no conversion) [1]');
                            // nothing to convert, text variable is still unaltered, nothing to do
                        }else{
                            $log->warning('Result now not empty anymore: '.$tmpText);
                            $text = $tmpText;
                        }
                    }else{
                        $log->warning('mb_convert_encoding returned empty string, revert to original text (no conversion) [2]');
                    }
                }else{
                    $log->warning('No mb_convert_encoding function available, revert to original text (no conversion) [3]');
                    // nothing to convert, text variable is still unaltered, nothing to do
                }
            }else{
                // everything ok, take the result of iconv
                $text = $tmpText;
            }
		}elseif($encoding == 1) {
			if($charset == ""){
				$log->debug('encodeText: Encoding with imapUtf8');
				$text = MstConverterUtils::imapUtf8($text);
				$log->debug('encodeText: Result of imapUtf8: '.$text);
			}else{
                if($charset !== "UTF-8"){
                    if($charset === "ISO-8859-2"){ // German, Bosnian, Croatian, Polish, Czech, ...
                        $log->debug('encodeText: Convert ISO-8859-2 to UTF-8...');
                        $text = self::iconv_Wrapper($charset, "UTF-8//TRANSLIT", $text);
                    }elseif($charset === "ISO-8859-7"){ // Greek
                        $log->debug('encodeText: Convert ISO-8859-7 to UTF-8...');
                        $text = self::iconv_Wrapper($charset, "UTF-8//TRANSLIT", $text);
                    }elseif($charset === "ISO-2022-JP"){ // Japanese
                        $log->debug('encodeText: Convert ISO-2022-JP to UTF-8...');
                        $text = self::iconv_Wrapper($charset, "UTF-8//TRANSLIT", $text);
                    }elseif($charset === "ISO-8859-13"){ // Baltic
                        $log->debug('encodeText: Convert ISO-8859-13 to UTF-8...');
                        $text = self::iconv_Wrapper($charset, "UTF-8//TRANSLIT", $text);
                    }elseif($charset === "WINDOWS-1251"){ // Windows 1251 (Russian, Ukrainian, Bulgarian)
                        $log->debug('encodeText: Convert WINDOWS-1251 to UTF-8...');
                        $text = self::iconv_Wrapper($charset, "UTF-8//TRANSLIT", $text);
                    }elseif($charset === "WINDOWS-1252"){ // Windows 1252 (Western European)
                        $log->debug('encodeText: Convert WINDOWS-1252 to UTF-8...');
                        $text = self::iconv_Wrapper($charset, "UTF-8//TRANSLIT", $text);
                    }elseif($charset === "WINDOWS-1257"){ // Windows 1257 (Baltic)
                        $log->debug('encodeText: Convert WINDOWS-1257 to UTF-8...');
                        $text = self::iconv_Wrapper($charset, "UTF-8//TRANSLIT", $text);
                    }elseif($charset === "US-ASCII"){ // US-ASCII does not really need a conversion since it is a direct UTF-8 subset, but do it for the laughs
                        $log->debug('encodeText: Convert US-ASCII to UTF-8...');
                        $text = self::iconv_Wrapper($charset, "UTF-8//TRANSLIT", $text);
                    }else{
                        $log->debug('encodeText: Encoding with decode_qprint');
                        $text = MstConverterUtils::decode_qprint($text);
                    }
                    $log->debug('encodeText: Result of UTF-8-related conversion: '.$text);
                }else{
                    $log->debug('encodeText: Is UTF-8, do not encode');
                }
			}
		}elseif ($encoding == 3) {
			$log->debug('encodeText: Encoding with base64_decode');
			$text = base64_decode($text);
			$log->debug('encodeText: Result of base64_decode: '.$text);
			$text = MstConverterUtils::encodeText($text, 1, $charset);
		}elseif ($encoding == 4) {
			$log->debug('encodeText: Encoding with quoted_printable_decode');
			$text = quoted_printable_decode($text);
			$log->debug('encodeText: Result of quoted_printable_decode: '.$text);
			$text = MstConverterUtils::encodeText($text, 1, $charset);
		}else{
			$log->debug('encodeText: Different encoding: '.$encoding. ', do not decode');
		}
		return $text;
	}


    /**
     * Source https://raw.githubusercontent.com/ramsey/array_column/master/src/array_column.php
     * Copyright (c) 2013-2015 Ben Ramsey (http://benramsey.com)
     * @copyright Copyright (c) Ben Ramsey (http://benramsey.com)
     * @license http://opensource.org/licenses/MIT MIT
     * Returns the values from a single column of the input array, identified by
     * the $columnKey.
     * This simple library provides functionality for array_column() to versions of PHP earlier than 5.5.
     * It mimics the functionality of the built-in function in every way.
     *
     * Optionally, you may provide an $indexKey to index the values in the returned
     * array by the values from the $indexKey column in the input array.
     *
     * @param array $input A multi-dimensional array (record set) from which to pull
     *                     a column of values.
     * @param mixed $columnKey The column of values to return. This value may be the
     *                         integer key of the column you wish to retrieve, or it
     *                         may be the string key name for an associative array.
     * @param mixed $indexKey (Optional.) The column to use as the index/keys for
     *                        the returned array. This value may be the integer key
     *                        of the column, or it may be the string key name.
     * @return array
     */
    public static function array_column($input = null, $columnKey = null, $indexKey = null)
    {
        // Using func_get_args() in order to check for proper number of
        // parameters and trigger errors exactly as the built-in array_column()
        // does in PHP 5.5.
        $argc = func_num_args();
        $params = func_get_args();

        if ($argc < 2) {
            trigger_error("array_column() expects at least 2 parameters, {$argc} given", E_USER_WARNING);
            return null;
        }

        if (!is_array($params[0])) {
            trigger_error(
                'array_column() expects parameter 1 to be array, ' . gettype($params[0]) . ' given',
                E_USER_WARNING
            );
            return null;
        }

        if (!is_int($params[1])
            && !is_float($params[1])
            && !is_string($params[1])
            && $params[1] !== null
            && !(is_object($params[1]) && method_exists($params[1], '__toString'))
        ) {
            trigger_error('array_column(): The column key should be either a string or an integer', E_USER_WARNING);
            return false;
        }

        if (isset($params[2])
            && !is_int($params[2])
            && !is_float($params[2])
            && !is_string($params[2])
            && !(is_object($params[2]) && method_exists($params[2], '__toString'))
        ) {
            trigger_error('array_column(): The index key should be either a string or an integer', E_USER_WARNING);
            return false;
        }

        $paramsInput = $params[0];
        $paramsColumnKey = ($params[1] !== null) ? (string) $params[1] : null;

        $paramsIndexKey = null;
        if (isset($params[2])) {
            if (is_float($params[2]) || is_int($params[2])) {
                $paramsIndexKey = (int) $params[2];
            } else {
                $paramsIndexKey = (string) $params[2];
            }
        }

        $resultArray = array();

        foreach ($paramsInput as $row) {
            $key = $value = null;
            $keySet = $valueSet = false;

            if ($paramsIndexKey !== null && array_key_exists($paramsIndexKey, $row)) {
                $keySet = true;
                $key = (string) $row[$paramsIndexKey];
            }

            if ($paramsColumnKey === null) {
                $valueSet = true;
                $value = $row;
            } elseif (is_array($row) && array_key_exists($paramsColumnKey, $row)) {
                $valueSet = true;
                $value = $row[$paramsColumnKey];
            }

            if ($valueSet) {
                if ($keySet) {
                    $resultArray[$key] = $value;
                } else {
                    $resultArray[] = $value;
                }
            }

        }

        return $resultArray;
    }


}
