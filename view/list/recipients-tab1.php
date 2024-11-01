<?php if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
	die( 'These are not the droids you are looking for.' );
} ?>
<?php
    MstFactory::getLogger()->debug('Recipients in tab1: '.print_r($allRecipients, true));
?>
<div class="mst_container">
	<div class="wrap">
		<h4>
			<?php _e("All Recipients", 'wp-mailster');  ?>
		</h4>
        <?php echo (MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_REC) < 100000) ? sprintf(__('%s recipients', 'wp-mailster'), count($allRecipients).'&nbsp;&#47;&nbsp;'. MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_REC)).'<br/>'.'<br/>' : ''; ?>
			<table id="mst_table" class="wp-list-table widefat fixed striped posts">
				<thead>
					<tr>
						<td width="8%"><?php _e("Num", 'wp-mailster'); ?></td>
						<td><?php _e("Name", 'wp-mailster'); ?></td>
						<td><?php _e("Email", 'wp-mailster'); ?></td>
                        <?php if(MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_DIGEST)): ?>
                            <td><?php _e("Digest", 'wp-mailster'); ?></td>
                        <?php endif; ?>
					</tr>
				</thead>
			<tfoot>
				<tr>
					<td width="8%"><?php _e("Num", 'wp-mailster'); ?></td>
					<td><?php _e("Name", 'wp-mailster'); ?></td>
					<td><?php _e("Email", 'wp-mailster'); ?></td>
                    <?php if(MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_DIGEST)): ?>
                    <td><?php _e("Digest", 'wp-mailster'); ?></td>
                    <?php endif; ?>
				</tr>
			</tfoot>						
			<tbody id="the-list">
			<?php
			if( !empty( $allRecipients ) ){
				$index = 1;
				foreach($allRecipients as $recipient) {
                    $editNonce = wp_create_nonce( 'mst_edit_digest' );

                    $digestLink = sprintf(
                        '?page=mst_mailing_lists&subpage=digests&did=%d&lid=%d&uid=%d&ic=%d&_wpnonce=%s',
                        ($recipient->digest ? $recipient->digest->id : 0),
                        $listData[0]->id,
                        $recipient->user_id,
                        $recipient->is_core_user ? 1 : 0,
                        $editNonce
                    );
			?>
				<tr>
					<td class="post-title page-title column-title"><?php echo $index++; ?></td>
					<td><?php
                        if( !$recipient->name || trim($recipient->name) == "" ) {
                            $userName = __("(no name)", 'wp-mailster');
                        } else {
                            $userName = $recipient->name;
                        }
                        echo $userName; ?></td>
					<td><?php echo $recipient->email; ?></td>
                    <?php if(MstFactory::getV()->getFtSetting(MstVersionMgmt::MST_FT_ID_DIGEST)): ?>
                        <td><a href="<?php echo $digestLink; ?>" title="<?php echo __('Edit Digest', 'wp-mailster'); ?>"><?php echo $recipient->digestChoiceStr; ?></a></td>
                    <?php endif; ?>
				</tr>
			<?php	
				}
			}
			?>
			</tbody>
		</table>
	</div>
</div>