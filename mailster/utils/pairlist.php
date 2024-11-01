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
	
	function pairList($pairListId, $submitTask, $selectArray, $listStrings, $includeFiltering=true)
	{
		$listsJS = 'lists.js';
		$listsCSS = 'lists.css';

		$ID = $pairListId . '_'; // unique pair list identifier and precode
		
		if($includeFiltering){
			//$mstUtils = MstFactory::getUtils();
			//$mstUtils->addSelectListFilter();
		}
		$imgPath = $this->WPMST_PLUGIN_DIR_URL.'/asset/images/';
		$magnifierImg = '<img src="' . $imgPath . '13-magnifier.png' . '" alt="" class="selectListMagnifierImg" />';	
		?>		
		<div id="<?php echo $ID;?>outerPairListContainer" class="outerPairListContainer">
			<div id="<?php echo $ID;?>selectListPair" class="selectListPair">
				<div id="<?php echo $ID;?>leftSelectList" class="leftSelectList">
				<div id="<?php echo $ID;?>leftSelectListTitle" class="leftSelectListTitle"><?php echo $listStrings->leftTitle; ?></div>		
				<?php if($includeFiltering): ?><div id="<?php echo $ID;?>leftSelectListFilterTitle" class="leftSelectListFilterTitle"><?php echo $magnifierImg; ?><input type="text" id="<?php echo $ID;?>leftPairListFilter" name="<?php echo $ID;?>leftPairListFilter" class="selectlistfilter_field" value="" placeholder="<?php _e('Type text to filter', 'wp-mailster'); ?>" title="<?php _e('Type text to filter', 'wp-mailster'); ?>"/></div><?php endif; ?>
				<select id="<?php echo $ID;?>selectLeft" class="pairListSelection <?php echo $includeFiltering ? 'sel2_filter_list' : ''; ?>" multiple="multiple" >     
					<?php
						
						for($i=0, $n=count( $selectArray ); $i < $n; $i++) {
							$entry = $selectArray[$i];
							?>
							<option value="<?php echo $entry->value; ?>"><?php echo $entry->text; ?></option>
							<?php
						}
					?>
				  </select>
				  <div class="selectListFooter"><ul><li><a id="<?php echo $ID;?>leftSelectAll" href="#"><?php echo $listStrings->selectAll; ?></a></li><li><a id="<?php echo $ID;?>leftSelectInv" href="#"><?php echo $listStrings->selectInv; ?></a></li><li><a id="<?php echo $ID;?>leftSelectNone" href="#"><?php echo $listStrings->selectNone; ?></a></li></ul></div>		
				</div>
				<div id="<?php echo $ID;?>selectListControl" class="selectListControl">
				<ul>
				  <li><input id="<?php echo $ID;?>MoveAllRight" type="button" value=" >> " title="<?php _e('select all', 'wp-mailster'); ?>" class="selListButton" /></li>
				  <li><input id="<?php echo $ID;?>MoveRight" type="button" value=" > " title="<?php _e('add selected', 'wp-mailster'); ?>" class="selListButton" /></li>
				  <li>&nbsp;</li>
				  <li><input id="<?php echo $ID;?>MoveLeft" type="button" value=" < " title="<?php _e('remove selected', 'wp-mailster'); ?>" class="selListButton" /></li>
				  <li><input id="<?php echo $ID;?>MoveAllLeft" type="button" value=" << " title="<?php _e('remove all', 'wp-mailster'); ?>" class="selListButton" /></li>
				  </ul>
				</div>
				<div id="<?php echo $ID;?>rightSelectList" class="rightSelectList">  
				<?php if($includeFiltering): ?><div id="<?php echo $ID;?>rightSelectListFilterTitle" class="rightSelectListFilterTitle">&nbsp;</div><?php endif; ?>
				<div id="<?php echo $ID;?>rightSelectListTitle" class="rightSelectListTitle"><?php echo $listStrings->rightTitle; ?></div>
				  <select name="<?php echo $ID;?>selectRight[]" id="<?php echo $ID;?>selectRight" class="pairListSelection" multiple="multiple" >          
				  </select>
				 <div class="selectListFooter"><ul><li><a id="<?php echo $ID;?>rightSelectAll" href="#"><?php echo $listStrings->selectAll; ?></a></li><li><a id="<?php echo $ID;?>rightSelectInv" href="#"><?php echo $listStrings->selectInv; ?></a></li><li><a id="<?php echo $ID;?>rightSelectNone" href="#"><?php echo $listStrings->selectNone; ?></a></li></ul></div>
				</div>			
			</div>
			<div id="<?php echo $ID;?>selListPairSubmit" class="submitContainer">
				<input id="<?php echo $ID;?>selListPairSubmitButton" type="submit" value="<?php echo $listStrings->submitButton; ?>" title="<?php echo $listStrings->submitTitle; ?>" class="submitButton" onclick="document.getElementById('task').value='<?php echo $submitTask; ?>';"/>
			</div>
		</div>
	<?php
	}

?>
