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
	
	
	$tipsJS = 'tips.js';
	$tipTipJS = 'jquery.tipTip.minified.js';
	$tipsCSS = 'tips.css';
	$imgPath = $this->WPMST_PLUGIN_DIR_URL.'/asset/images/';
	?>
	<div style="display:none;">
		<img id="infoIconZero" class="infoIcon" src="<?php echo $imgPath;?>16-info.png" width="16px" />
	</div>	
	<?php 
	wp_enqueue_script('tipTip', $this->WPMST_PLUGIN_DIR_URL.'/asset/js/' . $tipTipJS, array('jquery'));
	wp_enqueue_script('tips', $this->WPMST_PLUGIN_DIR_URL.'/asset/js/' . $tipsJS, array('jquery'));
	wp_register_style( 'tips_style',$this->WPMST_PLUGIN_DIR_URL.'asset/css/'. $tipsCSS);
	wp_enqueue_style('tips_style');	
?>
