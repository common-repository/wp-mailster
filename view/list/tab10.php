<?php if (preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) {
	die( 'These are not the droids you are looking for.' );
}
?>
<p>
	<?php _e("This tab presents various tools that work with the mailing list. Please be careful when using them and read the documentation first in case of questions.", 'wp-mailster'); ?>
</p>
<?php


if ( ! $lid ) {
	if ( isset( $_POST['lid'] ) ) {
		$lid = intval($_POST['lid']);
	}
}
if( $lid ) { ?>
<table class="form-table">
	<tbody>
		<tr>
			<td width="10px"><div tabindex="-1" id="getInboxStatusProgressIndicator" style="display:inline; margin:5px; padding-right:20px;min-height:30px;width:30px;">&nbsp;</div></td>
			<td>
				<a id="getInboxStatus" href="#"><?php _e("Retrieve inbox status", 'wp-mailster'); ?></a>
			</td>
		</tr>
		<tr>
			<td width="10px"><div tabindex="-1" id="removeFirstMailLinkProgressIndicator" style="display:inline; margin:5px; padding-right:20px;min-height:30px;width:30px;">&nbsp;</div></td>
			<td>
				<a id="removeFirstMailLink" href="#"><?php _e("Delete first email in inbox", 'wp-mailster'); ?></a>
			</td>
		</tr>
		<tr>
			<td width="10px"><div tabindex="-1" id="removeAllMailsLinkProgressIndicator" style="display:inline; margin:5px; padding-right:20px;min-height:30px;width:30px;">&nbsp;</div></td>
			<td>
				<a id="removeAllMailsLink" href="#"><?php _e("Delete all emails in inbox", 'wp-mailster'); ?></a>
			</td>
		</tr>
		<tr>
			<td width="10px"><div tabindex="-1" id="removeAllMailsInSendQueueProgressIndicator" style="display:inline; margin:5px; padding-right:20px;min-height:30px;width:30px;">&nbsp;</div></td>
			<td>
				<a id="removeAllMailsInSendQueue" href="#"><?php _e("Remove all emails in the send queue", 'wp-mailster'); ?></a>
			</td>
		</tr>
		<?php
        $hideUnlock = 'display:none;';
		$listUtils = MstFactory::getMailingListUtils();
		if( ($lid > 0) && ($listUtils->isListLocked($lid)) ){
            $hideUnlock = '';
        }
		?>
		<tr style="<?php echo $hideUnlock; ?>">
			<td width="10px"><div tabindex="-1" id="unlockMailingListProgressIndicator" style="display:inline; margin:5px; padding-right:20px;min-height:30px;width:30px;">&nbsp;</div></td>
			<td>
				<a id="unlockMailingList" href="#"><?php _e( 'Unlock mailing list', 'wp-mailster' ); ?></a>
			</td>
		</tr>
		<tr>
			<td colspan="2">
				<pre id="tool_info_display"></pre>
			</td>
		</tr>
	</tbody>
</table>
<?php } ?>