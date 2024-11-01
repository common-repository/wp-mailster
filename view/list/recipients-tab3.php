<?php if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
	die( 'These are not the droids you are looking for.' );
}
?>
<div class="mst_container">
	<div class="wrap">
		<h4>
			<?php _e("List Groups", 'wp-mailster');  ?>
			<a href="?page=mst_mailing_lists&amp;subpage=managegroups&amp;lid=<?php echo $lid; ?>" class="add-new-h2"><?php _e( "Edit List Groups", 'wp-mailster' ); ?></a>
		</h4>			
			<table id="mst_table" class="wp-list-table widefat fixed striped posts">
				<thead>
					<tr>
						<td width="8%"><?php _e("Num", 'wp-mailster'); ?></td>
						<td><?php _e("Name", 'wp-mailster'); ?></td>
                        <td><?php _e("#Users in Group", 'wp-mailster'); ?></td>
					</tr>
				</thead>
			<tfoot>
				<tr>
					<td width="8%"><?php _e("Num", 'wp-mailster'); ?></td>
					<td><?php _e("Name", 'wp-mailster'); ?></td>
                    <td><?php _e("#Users in Group", 'wp-mailster'); ?></td>
				</tr>
			</tfoot>						
			<tbody id="the-list">
			<?php
			if( !empty( $listGroups ) ){
				$index = 1;


                foreach($listGroups as $key => $listGroup){
                    $current_group = new MailsterModelGroup($listGroup->group_id);
                    $groupData = $current_group->getFormData();
                    $listGroups[$key]->groupUserCount = $current_group->getTotal();
                    $listGroups[$key]->groupName = $groupData->name;
                }

                usort($listGroups, function($a, $b) {return strcasecmp($a->groupName, $b->groupName);}); // sort by name

                $edit_nonce = wp_create_nonce( 'mst_edit_group' );
				foreach($listGroups as $listGroup){
                    $groupEditLink = sprintf(
                        '<a href="?page=mst_groups&amp;subpage=%s&amp;sid=%s&amp;_wpnonce=%s" title="%s">%s</a>',
                        'edit',
                        absint( $listGroup->group_id ),
                        $edit_nonce,
                        __('Edit Group', 'wp-mailster').': '.$listGroup->groupName,
                        $listGroup->groupName
                    );
			?>
				<tr>
					<td class="post-title page-title column-title"><?php echo $index++; ?></td>
					<td><?php echo $groupEditLink; ?></td>
                    <td><?php echo $listGroup->groupUserCount; ?></td>
				</tr>
			<?php	
				}
			}
			?>
			</tbody>
		</table>
	</div>
</div>