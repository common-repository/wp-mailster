<?php
	if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
		die('These are not the droids you are looking for.');
	}
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

	$dbUtils = MstFactory::getDBUtils();
	$dbUtils->checkAndFixDBCollations(false);
	?>

<div class="mst_container">
	<div class="wrap">
		<h2>Fixed Database Collation</h2>
	</div>
</div>
