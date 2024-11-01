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
	
	
	class MstParameter
	{
		
		public function __construct($data = '', $path = '')
		{
		}		
		
		 static public function getParameterFromJParameter($paramObj){
			$mstParam =  new MstParameter();
			if(is_object($paramObj)){
				foreach ($paramObj as $varName => $val) {
		            $mstParam->$varName = $val;
		        }
			}
	        return $mstParam;
		}
				//todo probably delete this?
		public function renderItOldSchool($name = 'params', $group = '_default'){			
					
			$params = $this->getParams($name, $group);
			$html = array ();
			$html[] = '<table width="100%" class="paramlist admintable" cellspacing="1">';
		
			if ($description = $this->_xml[$group]->attributes('description')) {
				// add the params description to the display
				$desc    = __($description, 'wp-mailster');
				$html[]    = '<tr><td class="paramlist_description" colspan="2">'.$desc.'</td></tr>';
			}
		
			foreach ($params as $param)
			{
				$html[] = '<tr>';
		
				if ($param[0]) {
					$html[] = '<td width="40%" class="paramlist_key"><span class="editlinktip">'.$param[0].'</span></td>';
					$html[] = '<td class="paramlist_value">'.$param[1].'</td>';
				} else {
					$html[] = '<td class="paramlist_value" colspan="2">'.$param[1].'</td>';
				}
		
				$html[] = '</tr>';
			}
		
			if (count($params) < 1) {
				$html[] = "<tr><td colspan=\"2\"><i>".__('There are no parameters for this item', 'wp-mailster')."</i></td></tr>";
			}
		
			$html[] = '</table>';
		
			return implode("\n", $html);
			
		}
		
		public function loadElement($type, $new = false)
		{
			
			if(strtolower($type)==='radio'){
				$type = 'MailsterRadio';
				
				$signature = md5($type);
				
				if ((isset($this->_elements[$signature]) && !($this->_elements[$signature] instanceof __PHP_Incomplete_Class))  && $new === false) {
					return  $this->_elements[$signature];
				}

				$elementClass   =   'MstElement'.$type;
				if (!class_exists($elementClass)) {
					$mstRadioElement = dirname(__FILE__)."/".'parameter'."/".'element'."/".'mailsterradio.php';
					require_once($mstRadioElement);
				}
				
				if (!class_exists($elementClass)) {
					$false = false;
					return $false;
				}
				
				$mstRadioElement = new $elementClass($this);
				$this->_elements[$signature] = $mstRadioElement;
				
				return $this->_elements[$signature];
			}else{		
			//	return parent::loadElement($type, $new);
			}
		}
		
	}

