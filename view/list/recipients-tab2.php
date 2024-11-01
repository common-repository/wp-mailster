<?php if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
	die( 'These are not the droids you are looking for.' );
}
?>
<div class="mst_container">
	<div class="wrap">
		<h4>
			<?php _e("List Users", 'wp-mailster');  ?>
			<a href="?page=mst_mailing_lists&amp;subpage=managemembers&amp;lid=<?php echo $lid; ?>" class="add-new-h2"><?php _e( "Edit List Members", 'wp-mailster' ); ?></a>
		</h4>
		<table id="mst_table" class="wp-list-table widefat fixed striped posts">
			<thead>
			<tr>
				<td width="8%"><?php _e("Num", 'wp-mailster'); ?></td>
				<td><?php _e("Name", 'wp-mailster'); ?></td>
				<td><?php _e("Email", 'wp-mailster'); ?></td>
                <td><?php _e("User Type", 'wp-mailster'); ?></td>
			</tr>
			</thead>
			<tfoot>
			<tr>
				<td width="8%"><?php _e("Num", 'wp-mailster'); ?></td>
				<td><?php _e("Name", 'wp-mailster'); ?></td>
				<td><?php _e("Email", 'wp-mailster'); ?></td>
                <td><?php _e("User Type", 'wp-mailster'); ?></td>
			</tr>
			</tfoot>
			<tbody id="the-list">
			<?php
			if( !empty( $listUsers ) ){

                $log = MstFactory::getLogger();

                foreach($listUsers as $key => $single_tm) {
                    $User = new MailsterModelUser($single_tm->user_id);
                    $userData = $User->getUserData($single_tm->user_id, $single_tm->is_core_user);
                    $listUsers[$key]->email = $userData->email;
                    $listUsers[$key]->userName = (!$userData->name || (trim($userData->name) == "")) ? __("(no name)", 'wp-mailster') : $userData->name;
                }

             //   $log->debug('recipients-tab2 listUsers unsorted: '.print_r($listUsers, true));

                usort($listUsers, function($a, $b) {return strcasecmp($a->userName, $b->userName);}); // sort by name

             //   $log->debug('recipients-tab2 listUsers   sorted: '.print_r($listUsers, true));

				$index = 1;

                $editNonce = wp_create_nonce( 'mst_edit_user' );
				foreach($listUsers as $single_tm){
                    $link = sprintf(
                        '<a href="?page=mst_users_add&amp;user_action=%s&amp;sid=%s&amp;core=%d&amp;_wpnonce=%s" title="%s">%s</a>',
                        'edit',
                        $single_tm->user_id,
                        $single_tm->is_core_user,
                        $editNonce,
                        __('Edit User', 'wp-mailster').': '.$single_tm->userName,
                        $single_tm->userName
                    );
					?>
					<tr>
						<td class="post-title page-title column-title"><?php echo $index++; ?></td>
						<td><?php echo $link; ?></td>
						<td><?php echo $single_tm->email; ?></td>
                        <td><?php echo ($single_tm->is_core_user === true || $single_tm->is_core_user == 1) ? 'WordPress' : 'WP Mailster'; ?></td>
					</tr>
					<?php
				}
			}
			?>
			</tbody>
		</table>
	</div>
</div>